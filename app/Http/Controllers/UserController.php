<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

use App\Models\User;

use Illuminate\Support\Facades\Validator;
use App\Models\PackageHistory;
use Ixudra\Curl\Facades\Curl;

use Session;
use DB;

class UserController extends Controller
{
    public $paginate = 50;

    public function Index()
    {
        return view('user.index');
    }

    public function List(Request $request)
    {
        $userList = User::with('role')
                                ->with('package_not_exists')
                                ->with('routes_team')
                                ->orderBy('name', 'asc')
                                ->where('name', 'like', '%'. $request->get('textSearch') .'%')
                                ->where('idRole', '=', 1)
                                ->paginate($this->paginate);


        foreach ($userList as $key => $user) {
            $history = PackageHistory::where('idUser',$user->id)
                                        ->orWhere('idUserManifest',$user->id)
                                        ->orWhere('idUserInbound',$user->id)
                                        ->orWhere('idUserReInbound',$user->id)
                                        ->orWhere('idUserDispatch',$user->id)
                                        ->orWhere('idUserReturn',$user->id)
                                        ->orWhere('idUserDelivery',$user->id)
                                        ->orWhere('idUserFailed',$user->id)
                                        ->select('id')
                                        ->first();
            $user->history = ($history)?true:false;
        }

        return ['userList' => $userList];
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
                "password" => ["required", "max:100"],
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

                "password.required" => "El campo es requerido",
            ]
        );

        if($validator->fails())
        {
            return response()->json(["status" => 422, "errors" => $validator->errors()], 422);
        }

        $request['password'] = Hash::make($request->get('password'));

        User::create($request->all());

        return ['stateAction' => true];
    }

    public function Get($id)
    {
        $user = User::find($id);

        return ['user' => $user];
    }

    public function Update(Request $request, $id)
    {
        $validator = Validator::make($request->all(),

            [
                "idRole" => ["required"],
                "name" => ["required","max:100"],
                "nameOfOwner" => ["required", "unique:user,nameOfOwner,$id", "max:100"],
                "address" => ["required"],
                "phone" => ["required"],
                "email" => ["required", "unique:user,email,$id", "max:100"],
            ],
            [
                "idRole.required" => "Seleccione un rol",

                "name.required" => "El campo es requerido",
                "name.max"  => "Debe ingresar máximo 100 dígitos",

                "nameOfOwner.unique" => "El nombre ya existe",
                "nameOfOwner.required" => "El campo es requerido",
                "nameOfOwner.max"  => "Debe ingresar máximo 150 dígitos",

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

        $user = User::find($id);

        $user->name        = $request->get('name');
        $user->nameOfOwner = $request->get('nameOfOwner');
        $user->phone       = $request->get('phone');
        $user->email       = $request->get('email');

        $user->save();

        return ['stateAction' => true];
    }

    public function Delete($id)
    {
        $user = User::find($id);
        // $user = User::with('')
        $user->delete();

        return ['stateAction' => true];
    }

    public function Login()
    {        
        $dateEnd  = date('Y-m-d H:i:s');
        $dateInit = date('Y-m-01 H:i:s', strtotime('-2 minute', strtotime($dateEnd)));

        $filename  = "Report-" . date('m-d-H-i-s', strtotime($dateInit)) .'-'. date('m-d-H-i-s', strtotime($dateEnd)) . ".csv";
        $delimiter = ",";

        $file   = fopen($filename, 'w');
        $fields = array('FECHA', 'HORA', 'PACKAGE ID', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'WEIGHT', 'ROUTE');

        fputcsv($file, $fields, $delimiter);
        
        $ListAssigns = PackageHistory::whereBetween('created_at', [$dateInit, $dateEnd])->get();

        foreach($ListAssigns as $assign)
        {

            $lineData = array(
                                date('m-d-Y', strtotime($assign->Date_manifest)),
                                date('H:i:s', strtotime($assign->Date_manifest)),
                                $assign->Reference_Number_1,
                                $assign->Dropoff_Contact_Name,
                                $assign->Dropoff_Contact_Phone_Number,
                                $assign->Dropoff_Address_Line_1,
                                $assign->Dropoff_City,
                                $assign->Dropoff_Province,
                                $assign->Dropoff_Postal_Code,
                                $assign->Weight,
                                $assign->Route
                            );

            fputcsv($file, $lineData, $delimiter);
        }
        
        rewind($file);
        fclose($file);

        return view('user.login');
    }

    public function ValidationLogin(Request $request)
    {
        $user = User::with(['role', 'routes_team.route'])->where('email', $request->get('email'))->first();

        if($user)
        {
            if(Hash::check($request->get('password'), $user->password))
            {
                Session::put('user', $user);

                return ['stateAction' => true];
            }
        }

        return ['stateAction' => false];;
    }

    public function Logout()
    {
        Session::flush();

        return redirect('/');
    }

    public function ChangePassword()
    {
        return view('user.changepassword');
    }

    public function SaveChangePassword(Request $request)
    {
        $validator = Validator::make($request->all(),

            [
                'oldPassword' => 'required',
                'newPassword' => 'required',
                'confirmationPassword' => 'required',
            ],
            [
                'oldPassword.required' => 'El campo es obligatorio',

                'newPassword.required' => 'El campo es obligatorio',

                'confirmationPassword.required' => 'El campo es obligatorio',
            ]
        );

        if($validator->fails())
        {
            return response()->json(["status" => 422, "errors" => $validator->errors()], 422);
        }

        $user = User::find(Session::get('user')->id);

        if(!Hash::check($request->get('oldPassword'), $user->password))
        {
            return ['stateAction' => 'error-passwordOld'];
        }

        if($request->get('newPassword') != $request->get('confirmationPassword'))
        {
            return ['stateAction' => 'error-passwordConfirm'];
        }

        $user->password = Hash::make($request->get('newPassword'));

        $user->save();

        return ['stateAction' => true];
    }
}
