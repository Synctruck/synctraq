<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;

use App\Models\{Configuration, Driver, PackageDispatch, PackageHistory, TeamRoute, User};

use App\Http\Controllers\UserController;

use Illuminate\Support\Facades\Validator;

use App\Service\ServicePackageDispatch;

use DB;
use Illuminate\Support\Facades\Auth;
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
        $configuration = Configuration::first();

        $userController = new UserController();
        $userController->UpdateDeleteUser();

        return view('driver.index');
    }

    public function List(Request $request)
    {
        if(Auth::user()->role->name == 'Administrador')
        {
            $userList = Driver::with(['role'])
                                ->with('package_not_exists')
                                ->with('routes_team')
                                ->orderBy('name', 'asc')
                                ->where('name', 'like', '%'. $request->get('textSearch') .'%')
                                ->where('idTeam', '!=', 0)
                                ->paginate($this->paginate);
        }
        else
        {
            $userList = Driver::with(['role'])
                                ->with('package_not_exists')
                                ->with('routes_team')
                                ->orderBy('name', 'asc')
                                ->where('name', 'like', '%'. $request->get('textSearch') .'%')
                                ->where('idTeam', Auth::user()->id)
                                ->paginate($this->paginate);
        }

        $roleUser = Auth::user()->role->name;

        return ['userList' => $userList, 'roleUser' => $roleUser];
    }

    public function ListAllByTeam($idTeam)
    {
        $listDriver = Driver::where('idTeam', $idTeam)
                            ->where('idOnfleet', '!=', '')
                            ->where('status', 'Active')
                            ->orderBy('name', 'asc')
                            ->get();

        return ['listDriver' => $listDriver];
    }

    public function Insert(Request $request)
    {
        $validator = Validator::make($request->all(),

            [
                "idRole" => ["required"],
                "name" => ["required", "max:100"],
                "nameOfOwner" => ["required", "max:100"],
                "phone" => ["required","unique:user"],
                "email" => ["required", "unique:user", "max:100"],
            ],
            [
                "idRole.required" => "Seleccione un rol",

                "name.required" => "El campo es requerido",
                "name.max"  => "Debe ingresar máximo 100 dígitos",

                "nameOfOwner.required" => "El campo es requerido",
                "nameOfOwner.max"  => "Debe ingresar máximo 150 dígitos",

                "phone.required" => "El campo es requerido",
                "phone.unique" => "El teléfono ya existe",

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

        $registerTeam = $this->RegisterOnfleet($team, $request);

        if($registerTeam == 400)
        {
            return ['stateAction' => 'phoneIncorrect'];
        }

        if($registerTeam)
        {
            $request['idOnfleet'] = explode('"', explode('"', explode('":', $registerTeam)[1])[1])[0];
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
        $user = Driver::find($id);

        if($user->idTeam != $request->get('idTeam'))
        {
            $packageDispatchList = PackageDispatch::where('idUserDispatch', $id)
                                    ->where('status', 'Dispatch')
                                    ->get();

            if(count($packageDispatchList) > 0)
            {
                return ['stateAction' => 'userPackageDispatch'];
            }
        }

        $validator = Validator::make($request->all(),

            [
                "idRole" => ["required"],
                "name" => ["required", "max:100"],
                "nameOfOwner" => ["required", "max:100"],
                "address" => ["required"],
                "phone" => ["required","unique:user,phone,$id","max:20"],
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
                "phone.unique" => "El teléfono ya existe",

                "email.unique" => "El correo ya existe",
                "email.required" => "El campo es requerido",
                "email.max"  => "Debe ingresar máximo 100 dígitos",
            ]
        );

        if($validator->fails())
        {
            return response()->json(["status" => 422, "errors" => $validator->errors()], 422);
        }

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

        if($updated)
        {
            $team = User::find($request->get('idTeam'));

            $updatedTeam = $this->UpdatedOnfleet($team, $request);

            if($updatedTeam)
            {
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

    public function ChangeStatus($id)
    {
        $user = User::find($id);

        $user->status = $user->status == 'Active' ? 'Inactive' : 'Active';

        $user->save();

        return ['stateAction' => true];
    }

    public function Delete($id)
    {
        $driver        = Driver::find($id);
        $driverOnfleet = $this->GetOnfleet($driver->idOnfleet);

        if($driverOnfleet)
        {
            $deleteOnfleet = $this->DeleteOnfleet($driver->idOnfleet);
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

    public function IndexDebrief()
    {
        return view('driver.index-debrief');
    }

    public function ListDebrief(Request $request, $idTeam)
    {
        $servicePackageDispatch = new ServicePackageDispatch();

        $idsDriver = $servicePackageDispatch->GetIdDriverPackageDebrief($idTeam);
        $driverList = Driver::where('idRole', 4)
                                ->whereIn('id', $idsDriver)
                                ->get();

        $newDriverList = [];

        foreach($idsDriver as $idDriver)
        {
            $driver = Driver::find($idDriver->idUserDispatch);

            if($driver) 
            {
                $data = [ 
                    'team' => $driver->nameTeam,
                    'idDriver' => $driver->id,
                    'fullName' => $driver->name .' '. $driver->nameOfOwner,
                    'email' => $driver->email,
                    'quantityOfPackages' => $idDriver->quantityOfPackages
                ];

                array_push($newDriverList, $data);
            }
        }

        return ['driverList' => $newDriverList]; 
    }

    public function ListPackagesDebrief($idDriver)
    {
        $servicePackageDispatch = new ServicePackageDispatch();

        return ['listPackages' => $servicePackageDispatch->ListPackagesDebrief($idDriver)];
    }

    public function ChangeStatusPackageDebrief($Reference_Number_1, $status)
    {
        $servicePackageDispatch = new ServicePackageDispatch();

        return ['statusAction' => $servicePackageDispatch->MoveToOtherStatus($Reference_Number_1, $status)];
    }
}