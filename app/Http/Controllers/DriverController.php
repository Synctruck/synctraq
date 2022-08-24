<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;

use App\Models\{Configuration, Driver, TeamRoute, User};

use Illuminate\Support\Facades\Validator;

use DB;
use Session;

class DriverController extends Controller
{
    private $apiKey;

    private $base64;

    private $headers;

    public $paginate = 20;

    public function __construct()
    {
        $this->apiKey = Configuration::first()->key_onfleet;

        $this->base64 = base64_encode($this->apiKey .':');

        $this->headers = [
                        'Authorization: Basic '. $this->base64,
                    ];
    }

    public function Index()
    {        
        return view('driver.index');
    }

    public function List(Request $request)
    {
        if(Session::get('user')->role->name == 'Administrador')
        {
            $userList = Driver::with(['dispatchs', 'role'])->orderBy('name', 'asc')
                                ->where('name', 'like', '%'. $request->get('textSearch') .'%')
                                ->where('idTeam', '!=', 0)
                                ->paginate($this->paginate);
        }
        else
        {
            $userList = Driver::with(['dispatchs', 'role'])->orderBy('name', 'asc')
                                ->where('name', 'like', '%'. $request->get('textSearch') .'%')
                                ->where('idTeam', Session::get('user')->id)
                                ->paginate($this->paginate);
        }
        
        $roleUser = Session::get('user')->role->name;

        return ['userList' => $userList, 'roleUser' => $roleUser];
    }

    public function ListAllByTeam($idTeam)
    {
        $listDriver = Driver::where('idTeam', $idTeam);

        if(env('APP_ENV') == 'local')
        {
            $listDriver = $listDriver->where('idOnfleet', '!=', '');
        }

        $listDriver = $listDriver->orderBy('name', 'asc')->get();

        return ['listDriver' => $listDriver];
    }

    public function Insert(Request $request)
    {        
        $validator = Validator::make($request->all(),

            [
                "idRole" => ["required"],
                "name" => ["required", "max:100"],
                "nameOfOwner" => ["required", "max:100"],
                "phone" => ["required"],
                "email" => ["required", "unique:user", "max:100"],
            ],
            [
                "idRole.required" => "Seleccione un rol",

                "name.required" => "El campo es requerido",
                "name.max"  => "Debe ingresar máximo 100 dígitos",

                "nameOfOwner.required" => "El campo es requerido",
                "nameOfOwner.max"  => "Debe ingresar máximo 150 dígitos",

                "phone.required" => "El campo es requerido",

                "email.unique" => "El correo ya existe",
                "email.required" => "El campo es requerido",
                "email.max"  => "Debe ingresar máximo 100 dígitos",
            ]
        );

        if($validator->fails())
        {
            return response()->json(["status" => 422, "errors" => $validator->errors()], 422);
        }

        $team = User::find($request->get('idTeam'));

        if(env('APP_ENV') == 'local')
        {
            $registerTeam = $this->RegisterOnfleet($team, $request);
        }
        else
        {
            $registerTeam = true;
        }
        
        if($registerTeam == 400)
        {
            return ['stateAction' => 'phoneIncorrect'];
        }

        if($registerTeam)
        {
            if(env('APP_ENV') == 'local')
            {
                $request['idOnfleet'] = explode('"', explode('"', explode('":', $registerTeam)[1])[1])[0];
            }

            $request['idRole']    = 4;
            $request['password']  = Hash::make($request->get('email'));
            $request['idTeam']    = $team->id;
            $request['nameTeam']  = $team->name;

            Driver::create($request->all());

            return ['stateAction' => true];
        }
        
        return ['stateAction' => 'notTeamOnfleet'];
    } 

    public function Get($id)
    {
        $driver = Driver::find($id);
        
        return ['driver' => $driver];
    }

    public function Update(Request $request, $id)
    {
        $validator = Validator::make($request->all(),

            [
                "idRole" => ["required"],
                "name" => ["required", "unique:user,name,$id", "max:100"],
                "nameOfOwner" => ["required", "max:100"],
                "address" => ["required"],
                "phone" => ["required"],
                "email" => ["required", "unique:user,email,$id", "max:100"],
            ],
            [
                "idRole.required" => "Seleccione un rol",

                "name.unique" => "El nombre ya existe",
                "name.required" => "El campo es requerido",
                "name.max"  => "Debe ingresar máximo 100 dígitos",

                "nameOfOwner.required" => "El campo es requerido",
                "nameOfOwner.max"  => "Debe ingresar máximo 100 dígitos",

                "address.required" => "El campo es requerido",

                "phone.required" => "El campo es requerido",

                "email.unique" => "El correo ya existe",
                "email.required" => "El campo es requerido",
                "email.max"  => "Debe ingresar máximo 100 dígitos",
            ]
        );

        if($validator->fails())
        {
            return response()->json(["status" => 422, "errors" => $validator->errors()], 422);
        }

        if(env('APP_ENV') == 'local')
        {
            $listDriverOnfleet = $this->GetListOnfleet();

            $updated = false;

            foreach ($listDriverOnfleet as $driver)
            {
                if($driver['phone'] == $request->get('phone'))
                {
                    $request['idOnfleet'] = $driver['id'];

                    $updated = true;
                }
            } 
        }
        else
        {
            $updated = true;
        }

        if($updated)
        {
            $team = User::find($request->get('idTeam'));

            if(env('APP_ENV') == 'local')
            {
                $updatedTeam = $this->UpdatedOnfleet($team, $request);
            }
            else
            {
                $updatedTeam = true;
            }

            if($updatedTeam)
            {
                $user = Driver::find($id);
                
                $request['nameTeam'] = $team->name;

                $user->update($request->all()); 

                return ['stateAction' => true];
            }
        }
        else
        {
            return ['stateAction' => 'phoneNotExists'];
        }
    }

    public function Delete($id)
    {
        $driver = Driver::find($id);

        if(env('APP_ENV') == 'local')
        {
            $driverOnfleet = $this->GetOnfleet($driver->idOnfleet);

            if($driverOnfleet)
            {
                $deleteOnfleet = $this->DeleteOnfleet($driver->idOnfleet); 
            }
        }

        $driver->delete();

        return ['stateAction' => true];
    }

    public function GetListOnfleet()
    {
        $curl = curl_init("https://onfleet.com/api/v2/workers");

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);

        $output = json_decode(curl_exec($curl), 1);

        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if($http_status == 200)
        {
            return $output;
        }
        else
        {
            return false;
        }
    }

    public function RegisterOnfleet($team, $request)
    {
        $data = [
                    "name" => $request->get('name') .' '. $request->get('nameOfOwner'),
                    "phone" => $request->get('phone'),
                    "teams" => [$team->idOnfleet],
                    "vehicle" => [
                        "type" => "TRUCK",
                        "description"=>"",
                        "licensePlate"=>"",
                        "color"=>""
                    ]
                ];

        $curl = curl_init();

        $apiKey = '4c52f49c1db8d158f7ff1ace1722f341';

        $base64 = base64_encode($apiKey .':');

        curl_setopt($curl, CURLOPT_URL, 'https://onfleet.com/api/v2/workers');
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERPWD, '4c52f49c1db8d158f7ff1ace1722f341:');
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);
            
        $output = curl_exec($curl);

        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if($http_status == 200)
        {
            return $output;
        }
        elseif($http_status == 400)
        {

            return 400;
        }
        else
        {
            return false;
        }
    }

    public function UpdatedOnfleet($team, $request)
    {
        $data = [
                    "name" => $request->get('name') .' '. $request->get('nameOfOwner'),
                    "phone" => $request->get('phone'),
                    "teams" => [$team->idOnfleet],
                    "vehicle" => [
                        "type" => "TRUCK",
                        "description"=>"",
                        "licensePlate"=>"",
                        "color"=>""
                    ]
                ];

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, 'https://onfleet.com/api/v2/workers/'. $request->get('idOnfleet'));
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERPWD, '4c52f49c1db8d158f7ff1ace1722f341:');
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);
            
        $output = curl_exec($curl);

        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if($http_status == 200)
        {
            return $output;
        }
        elseif($http_status == 400)
        {

            return 400;
        }
        else
        {
            return false;
        }
    }

    public function GetOnfleet($idOnfleet)
    {
        $curl = curl_init("https://onfleet.com/api/v2/workers/". $idOnfleet);

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);

        $output = json_decode(curl_exec($curl), 1);

        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if($http_status == 200)
        {
            return $output;
        }
        else
        {
            return false;
        }
    }

    public function DeleteOnfleet($idOnfleet)
    {
        $curl = curl_init("https://onfleet.com/api/v2/workers/". $idOnfleet);

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);

        $output = json_decode(curl_exec($curl), 1);

        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if($http_status == 200)
        {
            return true;
        }
        else
        {
            return false;
        }
    }
}