<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;

use App\Models\{Configuration, Driver, Routes, TeamRoute, User};

use Illuminate\Support\Facades\Validator;

use DB;
use Session;

class TeamController extends Controller
{
    private $apiKey;

    private $base64;

    private $headers;

    public $paginate = 1000;

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
        return view('team.index');
    } 

    public function List(Request $request)
    {
        $teamsList = User::with(['drivers', 'role', 'routes_team'])
                            ->where('name', 'like', '%'. $request->get('textSearch') .'%')
                            ->where('idRole', 3)
                            ->orderBy('name', 'asc')
                            ->paginate($this->paginate);

        return ['userList' => $teamsList];
    }

    public function ListAll(Request $request)
    {
        $listTeam = User::where('idRole', 3)
                        ->where('idOnfleet', '!=', '')
                        ->where('status', 'Active')
                        ->orderBy('name', 'asc')
                        ->get();

        return ['listTeam' => $listTeam];
    }

    public function Insert(Request $request)
    {
        $validator = Validator::make($request->all(),

            [
                "idRole" => ["required"],
                "name" => ["required", "unique:user", "max:100"],
                "nameOfOwner" => ["required", "max:100"],
                "phone" => ["required"],
                "email" => ["required", "unique:user", "max:100"],
            ],
            [
                "idRole.required" => "Seleccione un rol",

                "name.unique" => "El nombre ya existe",
                "name.required" => "El campo es requerido",
                "name.max"  => "Debe ingresar máximo 100 dígitos",

                "nameOfOwner.unique" => "El nombre ya existe",
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

        $listTeamOnfleet = $this->GetListOnfleet();

        $register = false;

        foreach ($listTeamOnfleet as $team)
        {
            if($team['name'] == $request->get('name'))
            {
                $request['idOnfleet'] = $team['id'];
                $register = true;
            }
        }

        if($register)
        {
            try
            {
                DB::beginTransaction();

                $request['idRole']   = 3;
                $request['password'] = Hash::make($request->get('email'));

                $user = new User();

                $user->idRole             = $request->get('idRole');
                $user->name               = $request->get('name');
                $user->nameOfOwner        = $request->get('nameOfOwner');
                $user->phone              = $request->get('phone');
                $user->email              = $request->get('email');
                $user->password           = $request->get('password');
                $user->permissionDispatch = $request->get('permissionDispatch');
                $user->idOnfleet          = $request->get('idOnfleet');
                $user->status             = $request->get('status');

                $user->save();

                $user = User::where('email', $request->get('email'))->first();

                $routesName = explode(',', $request->get('routesName'));

                for($i = 0; $i < count($routesName); $i++)
                {
                    $route = Routes::where('name', $routesName[$i])->first();

                    if($route)
                    {
                        $teamRoute = new TeamRoute();

                        $teamRoute->idTeam  = $user->id;
                        $teamRoute->idRoute = $route->id;

                        $teamRoute->save();
                    }
                }

                DB::commit();

                return ['stateAction' => true];
            }
            catch(Exception $e)
            {
                DB::rollback();

                return ['stateAction' => false];
            }
        }
        else
        {
            return ['stateAction' => 'notTeamOnfleet'];
        }
    }

    public function Get($id)
    {
        $team = User::with('routes_team.route')->find($id);

        return ['team' => $team];
    }

    public function Update(Request $request, $id)
    {
        $validator = Validator::make($request->all(),

            [
                "idRole" => ["required"],
                "name" => ["required", "unique:user,name,$id", "max:100"],
                "nameOfOwner" => ["required", "unique:user,nameOfOwner,$id", "max:100"],
                "phone" => ["required"],
                "email" => ["required", "unique:user,email,$id", "max:100"],
            ],
            [
                "idRole.required" => "Seleccione un rol",

                "name.unique" => "El nombre ya existe",
                "name.required" => "El campo es requerido",
                "name.max"  => "Debe ingresar máximo 100 dígitos",

                "nameOfOwner.unique" => "El nombre ya existe",
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

        $listTeamOnfleet = $this->GetListOnfleet();

        $updated = false;

        foreach ($listTeamOnfleet as $team)
        {
            if($team['name'] == $request->get('name'))
            {
                $request['idOnfleet'] = $team['id'];
                $updated = true;
            }
        }

        if($updated)
        {
            try
            {
                DB::beginTransaction();

                $user = User::find($id);

                $user->name               = $request->get('name');
                $user->nameOfOwner        = $request->get('nameOfOwner');
                $user->address            = $request->get('address');
                $user->phone              = $request->get('phone');
                $user->email              = $request->get('email');
                $user->permissionDispatch = $request->get('permissionDispatch');
                $user->idOnfleet          = $request->get('idOnfleet');
                $user->status             = $request->get('status');

                $user->save();

                $listTeamRoute = TeamRoute::where('idTeam', $id)->get();

                foreach($listTeamRoute as $teamRoute)
                {
                    $teamRoute = TeamRoute::find($teamRoute->id);

                    $teamRoute->delete();
                }

                $routesName = explode(',', $request->get('routesName'));

                for($i = 0; $i < count($routesName); $i++)
                {
                    $route = Routes::where('name', $routesName[$i])->first();

                    if($route)
                    {
                        $teamRoute = new TeamRoute();

                        $teamRoute->idTeam  = $id;
                        $teamRoute->idRoute = $route->id;

                        $teamRoute->save();
                    }
                }

                DB::commit();

                return ['stateAction' => true];
            }
            catch(Exception $e)
            {
                DB::rollback();

                return ['stateAction' => false];
            }
        }
        else
        {
            return ['stateAction' => 'notTeamOnfleet'];
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
        $user = User::with('routes_team')->find($id);

        $teamOnfleet = $this->GetOnfleet($user->idOnfleet);

        if($teamOnfleet)
        {
            $deleteOnfleet = $this->DeleteOnfleet($user->idOnfleet);
        }

        foreach($user->routes_team as $teamRoute)
        {
            $teamRoute = TeamRoute::find($teamRoute->id);

            $teamRoute->delete();
        }

        $user->delete();

        return ['stateAction' => true];
    }

    public function GetListOnfleet()
    {
        $curl = curl_init("https://onfleet.com/api/v2/teams");

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

    public function RegisterOnfleet($request)
    {
        $data = ["name" => "Sunset","workers" => ["1LjhGUWdxFbvdsTAAXs0TFos","F8WPCqGmQYWpCkQ2c8zJTCpW"],"managers" => ["Mrq7aKqzPFKX22pmjdLx*ohM"],"hub" => "tKxSfU7psqDQEBVn5e2VQ~*O"];


        $curl = curl_init();

        $apiKey = '4c52f49c1db8d158f7ff1ace1722f341';

        $base64 = base64_encode($apiKey .':');

        curl_setopt($curl, CURLOPT_URL, 'https://onfleet.com/api/v2/teams');
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERPWD, '4c52f49c1db8d158f7ff1ace1722f341:');
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(

                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Basic '. $base64,
            )
        );

        $reponse = curl_exec($curl);

        return $reponse;

        Log::info($reponse);

        if($reponse === false)
        {
            Log::info('Curl error: ' . curl_error($curl));
        }
    }

    public function GetOnfleet($idOnfleet)
    {
        $curl = curl_init("https://onfleet.com/api/v2/teams/". $idOnfleet);

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
        $curl = curl_init("https://onfleet.com/api/v2/teams/". $idOnfleet);

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
