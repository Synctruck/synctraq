<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CompanyController extends Controller
{
    public function index(){
        dd('inicio');
    }

    public function Login()
    {
        return view('landing/partner/login');
    }

    public function ValidationLogin(Request $request)
    {
        $company = Company::where('email', $request->get('email'))->first();

        if($company && $company->status =='Active')
        {
            if(Hash::check($request->get('password'), $company->password))
            {
                Auth::guard('partner')->login($company);
                return ['stateAction' => true,'company'=>$company];
            }
        }

        return ['stateAction' => false];;
    }

    public function Logout()
    {
        Auth::guard('partner')->logout();

        return redirect('/partners');
    }
}
