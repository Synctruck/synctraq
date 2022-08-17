<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Company;
use App\Models\CompanyStatus;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

use DB;
use Session;

class CompanyController extends Controller
{
    public $paginate = 200;

    public function Index()
    {        
        return view('company.index');
    }

    public function GetAll(Request $request)
    {
        $companyList = Company::orderBy('name', 'asc')->get();
        
        return ['companyList' => $companyList];
    }

    public function List(Request $request)
    {
        $companyList = Company::with(['company_status'])
                                ->where('name', 'like', '%'. $request->get('textSearch') .'%')
                                ->orderBy('name', 'asc')
                                ->paginate($this->paginate);
        
        return ['companyList' => $companyList];
    }

    public function Insert(Request $request)
    {
        $validator = Validator::make($request->all(),

            [
                "name" => ["required", "unique:company", "max:300"],
            ],
            [
                "name.unique" => "Company already exists",
                "name.required" => "El campo es requerido",
                "name.max"  => "Debe ingresar máximo 300 dígitos",
            ]
        );

        if($validator->fails())
        {
            return response()->json(["status" => 422, "errors" => $validator->errors()], 422);
        }

        try
        {
            DB::beginTransaction();

            $request['key_api']    = base64_encode(uniqid() . $request->get('name'));
            $request['key_base64'] = 'Basic '. base64_encode($request['key_api'] .':');

            Company::create($request->all());

            $company = Company::where('name', $request->get('name'))->first();

            $statusList = ['ReInbound', 'On hold', 'Dispatch', 'Delivery', 'Inbound', 'ReturnCompany'];

            foreach($statusList as $status)
            {
                $companyStatus = new CompanyStatus();

                $companyStatus->id                = uniqid();
                $companyStatus->idCompany         = $company->id;
                $companyStatus->status            = $status;
                $companyStatus->statusCodeCompany = '';

                $companyStatus->save();
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

    public function Get($id)
    {
        $company = Company::with('company_status')->find($id);
        
        return ['company' => $company];
    }

    public function Update(Request $request, $id)
    {
        $validator = Validator::make($request->all(),

            [
                "name" => ["required", "unique:company,name,$id", "max:300"],
            ],
            [
                "name.unique" => "Company already exists",
                "name.required" => "El campo es requerido",
                "name.max"  => "Debe ingresar máximo 300 dígitos",
            ]
        );

        if($validator->fails())
        {
            return response()->json(["status" => 422, "errors" => $validator->errors()], 422);
        }

        $company = Company::find($id);
        
        $statusList = CompanyStatus::where('idCompany', $id)->get();


        foreach($statusList as $statusCompany)
        {
            $companyStatus = CompanyStatus::find($statusCompany->id);

            if($companyStatus->status == 'ReInbound')
            {
                $companyStatus->statusCodeCompany = $request->get('reInbound');
            }
            else if($companyStatus->status == 'On hold')
            {
                $companyStatus->statusCodeCompany = $request->get('onHold');
            }
            else if($companyStatus->status == 'Dispatch')
            {
                $companyStatus->statusCodeCompany = $request->get('dispatch');
            }
            else if($companyStatus->status == 'Delivery')
            {
                $companyStatus->statusCodeCompany = $request->get('delivery');
            }
            else if($companyStatus->status == 'Inbound')
            {
                $companyStatus->statusCodeCompany = $request->get('inbound');
            }
            else if($companyStatus->status == 'ReturnCompany')
            {
                $companyStatus->statusCodeCompany = $request->get('returnCompany');
            }

            $companyStatus->save();
        }

        $company->update($request->all()); 

        return ['stateAction' => true];
    }

    public function Delete($id)
    {
        try
        {
            DB::beginTransaction();

            $comment = Company::find($id);

            $comment->delete();

            $statusCompanyList = companyStatus::where('idCompany', $id)->get();

            foreach($statusCompanyList as $statusCompany)
            {
                $statusCompany = CompanyStatus::find($statusCompany->id);

                $statusCompany->delete();
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
}