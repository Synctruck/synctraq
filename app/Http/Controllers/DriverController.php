<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;

use App\Models\{ Configuration, Driver, PackageDispatch, PackageHistory, TeamRoute, User};

use App\Http\Controllers\UserController;

use Illuminate\Support\Facades\Validator;

use App\Service\ServicePackageDispatch;


use Auth;
use DB;
use Log;
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
        $userList = Driver::with(['role', 'team'])
                                ->with('package_not_exists')
                                ->with('routes_team')
                                ->orderBy('name', 'asc')
                                ->where('name', 'like', '%'. $request->get('textSearch') .'%')
                                ->where('idRole', 4)
                                ->paginate($this->paginate);

        $roleUser = Auth::user()->role->name;

        return ['userList' => $userList, 'roleUser' => $roleUser];
    }

    public function ListAllByTeam($idTeam, $usageApp)
    {
        $listDriver = Driver::where('idTeam', $idTeam);

        if($usageApp == 'false')
        {
            $listDriver = $listDriver->where('usageApp', 'Onfleet');
        }
        else
        {
            $listDriver = $listDriver->where('usageApp', 'PODApp');
        }

        $listDriver = $listDriver->where('status', 'Active')
                                    ->orderBy('name', 'asc')
                                    ->get();

        return ['listDriver' => $listDriver];
    }

    public function ListUserByTeam($idTeam)
    {
        $listDriver = Driver::where('idTeam', $idTeam)
                            ->where('status', 'Active')
                            ->orderBy('name', 'asc')
                            ->get();

        return ['listDriver' => $listDriver];
    }

    public function Insert(Request $request)
    {
        $team = User::find($request->get('idTeam'));

        if($request->usageApp == 'Onfleet')
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

            $registerTeam = $this->RegisterOnfleet($team, $request);

            if($registerTeam == 400)
            {
                return ['stateAction' => 'phoneIncorrect'];
            }

            if($registerTeam)
            {
                $driverLast = Driver::all()->last();

                $request['id']        = $driverLast->id + 1;
                $request['idOnfleet'] = explode('"', explode('"', explode('":', $registerTeam)[1])[1])[0];
                $request['idRole']    = 4;
                $request['password']  = Hash::make($request->get('email'));
                $request['idTeam']    = $team->id;
                $request['nameTeam']  = $team->name;
                $request['usageApp']  = 'PODApp';

                Driver::create($request->all());

                $driver = Driver::where('email', $request->email)->first();

                $this->SynchronizeNewSystem($driver->id, $team->apiKey);

                return ['stateAction' => true];
            }

            return ['stateAction' => 'notTeamOnfleet'];
        }
        else
        {
            $validator = Validator::make($request->all(),
                [
                    "idRole" => ["required"],
                    "name" => ["required", "max:100"],
                    "nameOfOwner" => ["required", "max:100"],
                    "email" => ["required", "unique:user", "max:100"],
                ],
                [
                    "idRole.required" => "Seleccione un rol",

                    "name.required" => "El campo es requerido",
                    "name.max"  => "Debe ingresar máximo 100 dígitos",

                    "nameOfOwner.required" => "El campo es requerido",
                    "nameOfOwner.max"  => "Debe ingresar máximo 150 dígitos",

                    "email.unique" => "El correo ya existe",
                    "email.required" => "El campo es requerido",
                    "email.max"  => "Debe ingresar máximo 100 dígitos",
                ]
            );

            if($validator->fails())
            {
                return response()->json(["status" => 422, "errors" => $validator->errors()], 422);
            }

            $driverLast = Driver::all()->last();

            $driver = new Driver();
            $driver->id          = $driverLast->id + 1;
            $driver->idRole      = 4;
            $driver->name        = $request->name;
            $driver->nameOfOwner = $request->nameOfOwner;
            $driver->phone       = $request->phone;
            $driver->email       = $request->email;
            $driver->password    = Hash::make($request->email);
            $driver->usageApp    = 'PODApp';
            $driver->status      = $request->status;
            $driver->idTeam      = $team->id;
            $driver->nameTeam    = $team->name;
            $driver->save();

            $this->SynchronizeNewSystem($driver->id, $team->apiKey);

            return ['stateAction' => true];
        }
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

        $team = User::find($request->get('idTeam'));

        $request['nameTeam'] = $team->name;
        $user->update($request->all());

        return ['stateAction' => true];
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

    public function RegisterPODApp($request)
    {
        $data = [
                    "firstName" => $request->name,
                    "lastName" => $request->nameOfOwner,
                    "email" => $request->email,
                    "password" => 'Pa$$word1234',
                    "phoneNumber" => $request->phone,
                    "address" => '',
                    "meta" => [
                        "syncDriverId" => $request->id
                    ]
                ];

        $curl = curl_init();

        $configuration = Configuration::first();

        $apiKey  = 'T9M6HB3-CST4GFF-MGPH64Z-A01BRT7';
        $headers =  array(
                        'Content-Type: application/json',
                        'Authorization: '. $configuration->podAppKey
                    );

        curl_setopt_array($curl, array(
            CURLOPT_URL => $configuration->podAppUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $headers,
        ));

        $response    = curl_exec($curl);
        $response    = json_decode($response, true);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if($http_status >= 200 && $http_status <= 209)
        {
            Log::info($response);

            return ['statusCode' => true, 'response' => $response];
        }
        else
        {
            Log::info($response);

            return ['statusCode' => false, 'response' => $response];
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

    public function SynchronizeNewSystem($id, $apiKey)
    {
        $user = User::find($id);

        $registerSystemNew = $this->RegisterSystemNew($user, $user->id, $apiKey);

        if($registerSystemNew['statusCode'])
        {
            $user->driverId = $registerSystemNew['response']['data']['id'];
            $user->registerNewSystem = 1;
            $user->save();

            return ['statusCode' => true];
        }

        return ['statusCode' => false];
    }

    public function ListSystemNew()
    {
        $configuration = Configuration::first();
        $headers =  array(
                        'Content-Type: application/json',
                        'token: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJib2R5Ijp7ImlkIjoiNjU4MWI4MTM2OGU5NTk5YTdjODhkMzVhIiwiZW1haWwiOiJ3aWxjbTEyM0BnbWFpbC5jb20iLCJuYW1lIjoid2lsYmVyIGNhaHVhbmEiLCJsYXN0T3JnIjoiNjU3MjA4NWEyOTE1MzMzNjFjNGEwYWI4IiwicGVybWlzc2lvbnMiOiJlZGl1c2UsbGlzdXNlLHZpZXVzZSx2aWVyb2wsbGlzcm9sLGNyZXJvbCxsaWZycGUsZGVscm9sLGVkaXJvbCx2aWVzaGksbGlzc2hpcCx2aWVvcmcsY3Jlc2hpLGVkaXNoaSxsaXNvcmcsY3Jlb3JnLGNyZXVzZSxsaWFub20sc29hbm9tLGNyZWpvYixsaXNqb2IsdmVuZGFzLGxpc3JvdSxsaXNkcmlsb2MiLCJvcmdzIjpbeyJpZCI6IjY1NzIwODVhMjkxNTMzMzYxYzRhMGFiOCIsIm5hbWUiOiJTeW5jdHJ1Y2sifV0sImV4cCI6IjIwMjQtMDQtMjBUMDM6MDc6NTcuNTAwWiJ9LCJpYXQiOjE3MTM1MzkyNzcsImV4cCI6MTcxMzU4MjQ3NywiYXVkIjoic3luYy1zeXN0ZW0iLCJzdWIiOiJ3aWxjbTEyM0BnbWFpbC5jb20ifQ.By_byEi_jqLQQ0GMv-a5lRoU6M-eR_U6s_1tP0UuRzM',
                    );

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $configuration->podAppUrl .'/users',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => $headers,
        ));

        $response    = curl_exec($curl);
        $response    = json_decode($response, true);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        dd($response);
        try
        {
            DB::beginTransaction();

            foreach($response['data'] as $organization)
            {
                $user = User::where('email', $organization['contact']['email'])->first();

                if($user)
                {
                    $user->orgId = $organization['id'];
                    $user->apiKey = $organization['apiKey'];
                    $user->save();
                }
            }

            DB::commit();
            echo "success";
        }
        catch(Exception $e)
        {
            DB::rollback();
            echo "error";
        }
    }

    public function RegisterSystemNew($request, $idDriver, $apiKey)
    {
        $data = [
                    "firstName" => $request->name,
                    "lastName" => $request->nameOfOwner,
                    "email" => $request->email,
                    "roles" => [],
                    "meta" => [
                        "syncDriverId" => $idDriver
                    ]
                ];

        $configuration = Configuration::first();

        $headers =  array(
                        'Content-Type: application/json',
                        'Authorization: '. $apiKey,
                    );

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $configuration->podAppUrl .'/users',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $headers,
        ));

        $response    = curl_exec($curl);
        $response    = json_decode($response, true);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        Log::info($response['message']);
        if($http_status >= 200 && $http_status <= 299)
        {
            return ['response' => $response, 'statusCode' => true];
        }
        else
        {
            return ['response' => $response, 'statusCode' => false];
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

    public function ChangeStatusPackageDebrief($Reference_Number_1, $status, $comment)
    {
        $servicePackageDispatch = new ServicePackageDispatch();

        return ['statusAction' => $servicePackageDispatch->MoveToOtherStatus($Reference_Number_1, $status, $comment)];
    }
}
