<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

use App\Models\User;

use Illuminate\Support\Facades\Validator;

use App\Models\{ Audits, Company, Configuration, HistoryDiesel, PackageDispatch, PackageManifest, PackageHistory, Permission, Role, Routes };

use Ixudra\Curl\Facades\Curl;
use Illuminate\Support\Str;

use Auth;
use DateTime;
use Session;
use Mail;
use DB;
use Log;

class UserController extends Controller
{
    public $paginate = 50;

    public function Index()
    {
        $this->UpdateDeleteUser();
        
        return view('user.index');
    }

    public function List(Request $request)
    {
        $userList = User::with('role')
                                ->with('package_not_exists')
                                ->with('routes_team')
                                ->orderBy('name', 'asc')
                                ->where('name', 'like', '%'. $request->get('textSearch') .'%')
                                ->whereNotIn('idRole', [3,4])
                                ->role($request->idRole)
                                ->status($request->status)
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
        $user->status       = $request->get('status');
        $user->idRole       = $request->get('idRole');

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
        $packageManifest = PackageManifest::where('company', '!=', 'INLAND LOGISTICS')->first();
        $company         = Company::where('name', 'INLAND LOGISTICS')->first();
        $created_at_temp = DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s'));
        $created_at      = $created_at_temp->format(DateTime::ATOM);

        $data = '{
                    "shipment_type": "pick_up",
                    "created_at": "",
                    "ship_date": "2021-10-14T16:46:53-0600",
                    "shipment": {
                        "shipper_package_id": "'. $packageManifest->Reference_Number_1 .'",
                        "ship_from": {
                            "facility_shortcode": "SMEWRD1",
                            "address_type": "ship_from",
                            "name": "Shipping",
                            "company": "NAPERVILLE199",
                            "phone": "2125551212",
                            "address_line1": "80 HEATHER DR",
                            "address_line2": "",
                            "address_line3": "",
                            "city_locality": "ROSLYN",
                            "state_province": "NY",
                            "postal_code": "11576"
                        },
                        "ship_to": {
                            "address_type": "",
                            "name": "'. $packageManifest->Dropoff_Contact_Name .'",
                            "company": "",
                            "phone": "'. $packageManifest->Dropoff_Contact_Phone_Number .'",
                            "address_line1": "'. $packageManifest->Dropoff_Address_Line_1 .'",
                            "address_line2": "", 
                            "address_line3": "",
                            "city_locality": "'. $packageManifest->Dropoff_City .'",
                            "state_province": "'. $packageManifest->Dropoff_Province .'",
                            "postal_code": "'. $packageManifest->Dropoff_Postal_Code .'",
                            "address_residential_indicator": true
                        },
                        "shipment_details": { 
                            "ship_date": "'. $created_at .'",
                            "weight": '. $packageManifest->Weight .',
                            "weight_unit": "lb",
                            "length": 0,
                            "shipper_notes_1": "Notes",
                            "width": 0,
                            "height": 0,
                            "signature_on_delivery": false,
                            "hazardous_goods": false,
                            "hazardous_goods_type": null,
                            "label_message": "Deliver behind planter at the front door.",
                            "contains_alcohol": false,
                            "insured_value": null,
                            "service_code": null,
                            "goods_type": null
                        }
                    }
                }';

        Log::info('Reference_Number_1: '. $packageManifest->Reference_Number_1);

        $curl = curl_init();
        
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.staging.inlandlogistics.co/api/v6/add-to-manifest',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10, 
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'authorization: '. $company->api_key_inland_insert
            ),
        ));

        $output      = json_decode(curl_exec($curl), 1);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if($http_status == 200)
        {
            return ['status' => $http_status, 'response' => $output];
        }
        else
        {
            return ['status' => $http_status, 'response' => $output];
        }

        Log::info('output');
        Log::info($output);

        return view('user.login');
    }

    public function ValidationLogin(Request $request)
    {
        $user = User::with(['role', 'routes_team.route'])->where('email', $request->get('email'))->where('status','Active')->first();

        if($user && $user->role->status ==1)
        {
            if(Hash::check($request->get('password'), $user->password))
            {
                Auth::login($user);

                return ['stateAction' => true, 'user' => $user];
            }
        }

        return ['stateAction' => false];;
    }

    public function Logout()
    {
        Auth::logout();

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

        $user = User::find(Auth::user()->id);

        if(!Hash::check($request->get('oldPassword'), $user->password))
        {
            return ['stateAction' => 'error-passwordOld'];
        }

        if($user->email ==  $request->get('newPassword'))
        {
            return ['stateAction' => 'error-passwordEmail'];
        }

        if($request->get('newPassword') != $request->get('confirmationPassword'))
        {
            return ['stateAction' => 'error-passwordConfirm'];
        }

        $user->password                = Hash::make($request->get('newPassword'));
        $user->changePasswordMandatory = 1;

        $user->save();

        Auth::login($user);
        
        return ['stateAction' => true];
    }

    public function ResetPassword($email)
    {
        $user = User::where('email', $email)->first();

        if($user)
        {
            $user->password                = Hash::make($email);
            $user->changePasswordMandatory = 0;

            $user->save();

            return ['stateAction' => true];
        }
        else
        {
            return [
                'stateAction' => true,
                'message' => 'The email does not exists!'
            ];
        }
    }

    //perfil
    public function Profile()
    {
        return view('user.profile');
    }
    public function UpdateProfile(UpdateProfileRequest $request)
    {
        $user = User::find(Auth::user()->id);


        $image = $request->file('image');
        $oldRouteImage =  public_path('avatar').'/'.$user->image;
        if($image){
            if($user->image != '' && file_exists($oldRouteImage)){
                unlink($oldRouteImage);
            }

            $imageName = 'user_'.$user->id.'_'.Str::random(10).'.'.$image->getClientOriginalExtension();
            $image->move( public_path('avatar'), $imageName);
            $user->image = $imageName;
        }

        $user->name = $request->name;
        $user->nameOfOwner = $request->nameOfOwner;
        $user->address = $request->address;
        $user->save();

        $user = User::find(Auth::user()->id);

        return [
            'user'=>$user
        ];
    }
    public function getProfile()
    {
        $permissions = Permission::OrderBy('position','ASC')->get();
        $user = User::with(['role','role.permissions', 'routes_team.route'])->where('id',Auth::user()->id)->first();

       return ['user'=> $user,'allPermissions'=> $permissions];
    }

    public function UpdateDeleteUser()
    {
        try
        {
            DB::beginTransaction();

            $userList = User::where('verificationForDelete', 0)
                                ->get()
                                ->take(2);

            foreach($userList as $user)
            {
                $user = User::find($user->id);

                $delete = Audits::where('user_id', $user->id)->first();

                if($delete == null)
                {
                    $delete = PackageHistory::where('idUser', $user->id)
                                            ->orWhere('idUserDispatch', $user->id)
                                            ->first();
                }

                if($delete)
                {
                    $user->deleteUser = 1;
                }

                $user->verificationForDelete = 1;

                $user->save();
            }

            DB::commit();
        }
        catch(Exception $e)
        {
            DB::rollback();
        }
    }
}