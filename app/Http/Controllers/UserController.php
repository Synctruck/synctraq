<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;

use App\Models\User;

use Illuminate\Support\Facades\Validator;

use Ixudra\Curl\Facades\Curl;

use Session;

class UserController extends Controller
{
    public $paginate = 50;

    public function Index()
    {
        return view('user.index');
    }

    public function List(Request $request)
    {
        $userList = User::with('role')->orderBy('name', 'asc')
                                ->where('name', 'like', '%'. $request->get('textSearch') .'%')
                                ->where('idRole', '=', 1)
                                ->paginate($this->paginate);

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

        $request['password'] = Hash::make($request->get('email'));

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
                "name" => ["required", "unique:user,name,$id", "max:100"],
                "nameOfOwner" => ["required", "unique:user,nameOfOwner,$id", "max:100"],
                "address" => ["required"],
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

        $user->update($request->all());

        return ['stateAction' => true];
    }

    public function Delete($id)
    {
        $user = User::find($id);

        $user->delete();

        return ['stateAction' => true];
    }

    public function Login()
    {

        //GET ALL
        /*$apiKey = '87c91d7a9a0f7480c0467ade52c999be';

        $base64 = base64_encode($apiKey .':');

        $headers = [
            'Authorization: Basic '. $base64,
        ];
        $ch = curl_init("https://onfleet.com/api/v2/tasks/all?from=1455072025000&state=3");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $output = json_decode(curl_exec($ch), 1);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        dd($output);

        dd(2);*/




        //$curl -i -X GET "https://onfleet.com/api/v2/auth/test" \ -u "thisIsNotAValidAPIKey:";


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
