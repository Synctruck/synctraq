<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{ Company, CompanyStatus, PeakeSeasonCompany, RangeDieselCompany };

//use App\Models\{ BasicRates, Company, CompanyStatus, Configuration, DimFactor, PeakeSeason, RangeDieselSurcharge };

//use App\Http\Controllers\{ BasicRateController, DimFactorController, PeakeSeasonController, RangeDieselSurchargeController };

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
        $companyList = Company::orderBy('name', 'asc')->where('status', 'Active')->get();
        
        return ['companyList' => $companyList];
    }

    public function GetAllDelivery(Request $request)
    {
        $companyList = Company::orderBy('name', 'asc')
                                ->where('typeServices', 'PICK & DROP')
                                ->where('status', 'Active')
                                ->get();
        
        return ['companyList' => $companyList];
    }

    public function List(Request $request)
    {
        $companyList = Company::with(['company_status'])
                                ->where('name', 'like', '%'. $request->get('textSearch') .'%')
                                ->orWhere('email', 'like', '%'. $request->get('textSearch') .'%')
                                ->orderBy('name', 'asc')
                                ->paginate($this->paginate);
        
        return ['companyList' => $companyList];
    }

    public function Insert(Request $request)
    {
        $validator = Validator::make($request->all(),

            [
                "name" => ["required", "unique:company", "max:300"],
                "email" => ["required", "unique:company", "email", "max:50"],
                "password" => ["required", "max:250"],
                "length_field" => ["required", "numeric", "max:50"],
                "typeServices" => ["required"],
                "age21" => ["required"],
                "status" => ["required"],
            ],
            [
                "name.unique" => "Company already exists",
                "name.required" => "The field is required",
                "name.max"  => "You must enter a maximum of 300 digits",

                "email.unique" => "Email Company already exists",
                "email.required" => "The field is required",
                "email.max"  => "You must enter a maximum of 50 digits",
                "email.email" => "Enter a valid email address",

                "password.required" => "The field is required",
                "password.max"  => "You must enter a maximum of 250 digits",

                "length_field.required" => "The field is required",
                "length_field.max"  => "Debe ingresar máximo el número 50",

                "typeServices.required" => "Select an item",

                "age21.required" => "Select an item",

                "status.required" => "Select an item",
            ]
        );

        if($validator->fails())
        {
            return response()->json(["status" => 422, "errors" => $validator->errors()], 422);
        }

        try
        {
            DB::beginTransaction();

            $company = new Company();
            
            $company->name         = $request->get('name');
            $company->email        = $request->get('email');
            $company->password     = Hash::make($request->get('password'));
            $company->length_field = $request->get('length_field');
            $company->typeServices = $request->get('typeServices');
            $company->age21        = $request->get('age21');
            $company->status       = $request->get('status');
            $company->key_webhook  = '';
            $company->url_webhook  = '';
            $company->key_api      = base64_encode(uniqid() . $request->get('name'));
            $company->key_base64   = 'Basic '. base64_encode($company->key_api .':');
            
            $company->save();

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

            /*$baseRatesController = new BasicRateController();
            $baseRatesController->Insert($company->id);

            $dimFactorController = new DimFactorController();
            $dimFactorController->Insert($company->id);

            $peakeSeasonController = new PeakeSeasonController();
            $peakeSeasonController->Insert($company->id);

            $rangeDieselSurchargeController = new RangeDieselSurchargeController();
            $rangeDieselSurchargeController->Insert($company->id);*/

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
                "email" => ["required", "unique:company,email,$id", "email", "max:50"],
                "length_field" => ["required", "numeric", "max:50"],
                "typeServices" => ["required"],
            ],
            [
                "name.unique" => "Company already exists",
                "name.required" => "El campo es requerido",
                "name.max"  => "Debe ingresar máximo 300 dígitos",

                "email.unique" => "Email Company already exists",
                "email.required" => "The field is required",
                "email.max"  => "You must enter a maximum of 50 digits",
                "email.email" => "Enter a valid email address",

                "length_field.required" => "The field is required",
                "length_field.max"  => "Debe ingresar máximo el número 50",

                "typeServices.required" => "select an item",
            ]
        );

        if($validator->fails())
        {
            return response()->json(["status" => 422, "errors" => $validator->errors()], 422);
        }
        
        try
        {
            DB::beginTransaction();

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

            $company = Company::find($id);

            $company->name         = $request->get('name');
            $company->email        = $request->get('email');
            $company->length_field = $request->get('length_field');
            $company->typeServices = $request->get('typeServices');
            $company->age21        = $request->get('age21');
            $company->status       = $request->get('status');

            if($request->get('typeServices') == 'API')
            {
                $company->key_webhook = $request->get('key_webhook');
                $company->url_webhook = $request->get('url_webhook');
            }
            else
            {
                $company->key_webhook = '';
                $company->url_webhook = '';
            }

            $company->save();

            DB::commit();

            return ['stateAction' => true];
        }
        catch(Exception $e)
        {
            DB::rollback();

            return ['stateAction' => true];     
        }
    }

    public function Delete($id)
    {
        try
        {
            DB::beginTransaction();

            $statusCompanyList = CompanyStatus::where('idCompany', $id)->get();

            foreach($statusCompanyList as $statusCompany)
            {
                $statusCompany = CompanyStatus::find($statusCompany->id);

                $statusCompany->delete();
            }

            $company = Company::find($id);

            $company->delete();

            DB::commit();

            return ['stateAction' => true];
        }
        catch(Exception $e)
        {
            DB::rollback();

            return ['stateAction' => false];
        }
    }

    public function GetPeakeSeason($idCompany, $weight)
    {
        $peakeSeason = PeakeSeasonCompany::where('idCompany', $idCompany)->first();

        if(date('Y-m-d') >= $peakeSeason->start_date && date('Y-m-d') <= $peakeSeason->end_date)
        {
            if($weight <= $peakeSeason->lb1_weight)
            {
                $pricePeakeSeason = $peakeSeason->lb1_weight_price;
            }
            else if($weight > $peakeSeason->lb1_weight && $weight <= $peakeSeason->lb2_weight)
            {
                $pricePeakeSeason = $peakeSeason->lb2_weight_price;
            }
        }
        else
        {
            $pricePeakeSeason = 0.00;
        }

        return $pricePeakeSeason;
    }

    public function GetPercentage($idCompany, $dieselPrice)
    {
        $surchargePercentage = RangeDieselCompany::where('idCompany', $idCompany)
                                                    ->where('at_least', '<=', $dieselPrice)
                                                    ->where('but_less', '>=',  $dieselPrice)
                                                    ->first()->surcharge_percentage;

        return $surchargePercentage; 
    } 

    /*public function GetConfigurationRates($idCompany)
    {
        $dieselPrice = Configuration::first()->diesel_price;

        $basicRates  = BasicRates::where('idCompany', $idCompany)
                                    ->orderBy('weight', 'asc')
                                    ->get();

        $peakeSeason = PeakeSeasonCompany::where('idCompany', $idCompany)->first();

        if(date('Y-m-d') >= $peakeSeason->start_date && date('Y-m-d') <= $peakeSeason->end_date)
        {
            $peakeSeason = $peakeSeason;
        }
        else
        {
            $peakeSeason = null;
        }

        $surchargePercentage = RangeDieselSurcharge::where('idCompany', $idCompany)
                                                    ->where('at_least', '<=', $dieselPrice)
                                                    ->where('but_less', '>=',  $dieselPrice)
                                                    ->first()->surcharge_percentage;

        $dimFactor   = DimFactor::where('idCompany', $idCompany)->first()->factor;
        $lengthField = Company::select('length_field')->find($idCompany)->length_field;

        return ['basicRates' => $basicRates, 'peakeSeason' => $peakeSeason, 'surchargePercentage' => $surchargePercentage, 'lengthField' => $lengthField, 'dimFactor' => $dimFactor];
    }*/
}