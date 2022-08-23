<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;

use App\Models\{Company, CompanyStatus, PackageHistory, PackageManifest, PackageNotExists};

use DB;
use Session;

class PackageController extends Controller
{
    private $apiKey;

    private $base64;

    public function __construct()
    {
        $this->apiKey = '4c52f49c1db8d158f7ff1ace1722f341';

        $this->base64 = base64_encode($this->apiKey .':');
    }

    public function List(Request $request)
    {
        $packageList = Package::where('status', 'On hold')
                                ->orderBy('created_at', 'desc')
                                ->paginate(2000);
            
        $quantityPackage = Package::where('status', 'On hold')->get()->count();

        return ['packageList' => $packageList, 'quantityPackage' => $quantityPackage];
    }

    public function Insert(Request $request)
    {
        $headers = getallheaders();

        if(isset($headers['Authorization']))
        {
            $company = Company::where('key_base64', $headers['Authorization'])->first();

            if($company == null)
            {
                return response()->json(

                    [
                        'message' => 'Incorrect credentials (Authorization) for the use of the api',
                    ]
                , 401);
            }

            if(count($request->all()) != 2)
            {
                return response()->json(

                    [
                        'message' => 'The parameters sent are incorrect, check the correct structure',
                    ]
                , 400);
            }
            
            if($request->get('manifest') == null || $request->get('shipment') == null)
            {
                return response()->json(

                    [
                        'message' => 'The parameters sent are incorrect, check the correct structure',
                    ]
                , 400);
            }

            //data of manifest json
            $data['manifest_id']             = $request->get('manifest')['manifest_id'];
            $data['mixing_center_shortcode'] = $request->get('manifest')['mixing_center_shortcode'];

            //data of shipment json
            $data['Reference_Number_1'] = $request->get('shipment')['reference'];
            $data['company']   = $request->get('shipment')['company'];

            //data of shipment - ship_to json
            $data['address_type']   = $request->get('shipment')['ship_to']['address_type'];
            $data['Dropoff_Contact_Name'] = $request->get('shipment')['ship_to']['contact_name'];
            $data['Dropoff_Contact_Phone_Number'] = $request->get('shipment')['ship_to']['phone_number'];
            $data['Dropoff_Address_Line_1']  = $request->get('shipment')['ship_to']['address_line1'];
            $data['Dropoff_Address_Line_2']  = $request->get('shipment')['ship_to']['address_line2'];
            $data['address_line3']  = $request->get('shipment')['ship_to']['address_line3'];
            $data['Dropoff_City']  = $request->get('shipment')['ship_to']['city_locality'];
            $data['Dropoff_Province'] = $request->get('shipment')['ship_to']['state_province'];
            $data['Dropoff_Postal_Code']    = $request->get('shipment')['ship_to']['postal_code'];
            $data['address_residential_indicator'] = $request->get('shipment')['ship_to']['address_residential_indicator'];

            //data of shipment - shipment_details json
            $data['Weight']                = $request->get('shipment')['shipment_details']['weight'];
            $data['weight_unit']           = $request->get('shipment')['shipment_details']['weight_unit'];
            $data['width']                 = $request->get('shipment')['shipment_details']['width'];
            $data['height']                = $request->get('shipment')['shipment_details']['height'];
            $data['dimensions_unit']       = $request->get('shipment')['shipment_details']['dimensions_unit'];
            $data['signature_on_delivery'] = $request->get('shipment')['shipment_details']['signature_on_delivery'];
            $data['hazardous_goods']       = $request->get('shipment')['shipment_details']['hazardous_goods'];
            $data['hazardous_goods_type']  = $request->get('shipment')['shipment_details']['hazardous_goods_type'];
            $data['label_message']         = $request->get('shipment')['shipment_details']['label_message'];
            $data['shipper_notes_1']       = $request->get('shipment')['shipment_details']['shipper_notes_1'];
            $data['shipper_notes_2']       = $request->get('shipment')['shipment_details']['shipper_notes_2'];
            $data['contains_alcohol']      = $request->get('shipment')['shipment_details']['contains_alcohol'];
            $data['insured_value']         = $request->get('shipment')['shipment_details']['insured_value'];
            $data['service_code']          = $request->get('shipment')['shipment_details']['service_code'];
            $data['Route']                 = isset($request->get('shipment')['shipment_details']['route_name']) ? $request->get('shipment')['shipment_details']['route_name'] : '';
            $data['extra_data']            = $request->get('shipment')['shipment_details']['extra_data'];

            $validator = Validator::make($data,

                [
                    "Reference_Number_1" => ["required"],
                    "Dropoff_Contact_Name" => ["required"],
                    "Dropoff_Contact_Phone_Number" => ["required"],
                    "Dropoff_Address_Line_1" => ["required"],
                    "Dropoff_City" => ["required"],
                    "Dropoff_Province" => ["required"],
                    "Dropoff_Postal_Code" => ["required"],
                    "Weight" => ["required"],

                    "manifest_id" => ["required"],
                    "mixing_center_shortcode" => ["required"],

                    "company" => ["required"],

                    "address_type" => ["required"],
                    "address_residential_indicator" => ["required", "boolean"],

                    "weight_unit" => ["required"],
                    "width" => ["required"],
                    "height" => ["required"],
                    "dimensions_unit" => ["required"],
                    "signature_on_delivery" => ["required", "boolean"],
                    "hazardous_goods" => ["required", "boolean"],
                    "contains_alcohol" => ["required", "boolean"],
                ],
                [
                    "Reference_Number_1.required" => "The field is required",

                    "Dropoff_Contact_Name.required" => "The field is required",
                    "Dropoff_Contact_Phone_Number.required" => "The field is required",

                    "Dropoff_Address_Line_1.required" => "The field is required",

                    "Dropoff_City.required" => "The field is required",

                    "Dropoff_Province.required" => "The field is required",

                    "Dropoff_Postal_Code.required" => "The field is required",

                    "Weight.required" => "The field is required",

                    "manifest_id.required" => "The field is required",

                    "mixing_center_shortcode.required" => "The field is required",

                    "ship_date.required" => "The field is required",

                    "company.required" => "The field is required",

                    "address_type.required" => "The field is required",

                    "address_residential_indicator.required" => "The field is required",
                    "address_residential_indicator.boolean" => "The data to register must be true or false",

                    "weight_unit.required" => "The field is required",
                    "width.required" => "The field is required",
                    "height.required" => "The field is required",

                    "signature_on_delivery.required" => "The field is required",
                    "signature_on_delivery.boolean" => "The data to register must be true or false",

                    "hazardous_goods.required" => "The field is required",
                    "hazardous_goods.boolean" => "The data to register must be true or false",

                    "contains_alcohol.required" => "The field is required",
                    "contains_alcohol.boolean" => "The data to register must be true or false",
                ]
            );

            if($validator->fails())
            {
                return response()->json(["validations" => $validator->errors(), "message" => "Validation errors, check submitted fields"], 400);
            }

            $packageHistory = PackageHistory::where('Reference_Number_1', $data['Reference_Number_1'])
                                                    ->where('status', 'On hold')
                                                    ->first();

            if(!$packageHistory)
            {
                try
                {
                    DB::beginTransaction();

                    $package = new PackageManifest();

                    $package->company                       = $company->name;
                    $package->Reference_Number_1            = $data['Reference_Number_1'];
                    $package->Dropoff_Contact_Name          = $data['Dropoff_Contact_Name'];
                    $package->Dropoff_Contact_Phone_Number  = $data['Dropoff_Contact_Phone_Number'];
                    $package->Dropoff_Address_Line_1        = $data['Dropoff_Address_Line_1'];
                    $package->Dropoff_City                  = $data['Dropoff_City'];
                    $package->Dropoff_Province              = $data['Dropoff_Province'];
                    $package->Dropoff_Postal_Code           = $data['Dropoff_Postal_Code'];
                    $package->Weight                        = $data['Weight'];
                    $package->Route                         = $data['Route'];
                    $package->status                        = 'On hold';
                    $package->manifest_id                   = $data['manifest_id'];
                    $package->mixing_center_shortcode       = $data['mixing_center_shortcode'];
                    $package->address_type                  = $data['address_type'];
                    $package->Dropoff_Address_Line_2        = $data['Dropoff_Address_Line_2'];
                    $package->address_line3                 = $data['address_line3'];
                    $package->address_residential_indicator = $data['address_residential_indicator'];
                    $package->weight_unit                   = $data['weight_unit'];
                    $package->width                         = $data['width'];
                    $package->height                        = $data['height'];
                    $package->dimensions_unit               = $data['dimensions_unit'];
                    $package->signature_on_delivery         = $data['signature_on_delivery'];
                    $package->hazardous_goods               = $data['hazardous_goods'];
                    $package->hazardous_goods_type          = $data['hazardous_goods_type'];
                    $package->label_message                 = $data['label_message'];
                    $package->shipper_notes_1               = $data['shipper_notes_1'];
                    $package->shipper_notes_2               = $data['shipper_notes_2'];
                    $package->contains_alcohol              = $data['contains_alcohol'];
                    $package->insured_value                 = $data['insured_value'];
                    $package->service_code                  = $data['service_code'];
                    $package->extra_data                    = $data['extra_data'];

                    $package->save();

                    $packageHistory = new PackageHistory();

                    $packageHistory->id                            = uniqid();
                    $packageHistory->idCompany                     = $company->id;
                    $packageHistory->Reference_Number_1            = $data['Reference_Number_1'];
                    $packageHistory->Dropoff_Contact_Name          = $data['Dropoff_Contact_Name'];
                    $packageHistory->Dropoff_Contact_Phone_Number  = $data['Dropoff_Contact_Phone_Number'];
                    $packageHistory->Dropoff_Address_Line_1        = $data['Dropoff_Address_Line_1'];
                    $packageHistory->Dropoff_City                  = $data['Dropoff_City'];
                    $packageHistory->Dropoff_Province              = $data['Dropoff_Province'];
                    $packageHistory->Dropoff_Postal_Code           = $data['Dropoff_Postal_Code'];
                    $packageHistory->Weight                        = $data['Weight'];
                    $packageHistory->Route                         = $data['Route'];
                    $packageHistory->status                        = 'On hold';
                    $packageHistory->manifest_id                   = $data['manifest_id'];
                    $packageHistory->mixing_center_shortcode       = $data['mixing_center_shortcode'];
                    $packageHistory->address_type                  = $data['address_type'];
                    $packageHistory->Dropoff_Address_Line_2        = $data['Dropoff_Address_Line_2'];
                    $packageHistory->address_line3                 = $data['address_line3'];
                    $packageHistory->address_residential_indicator = $data['address_residential_indicator'];
                    $packageHistory->weight_unit                   = $data['weight_unit'];
                    $packageHistory->width                         = $data['width'];
                    $packageHistory->height                        = $data['height'];
                    $packageHistory->dimensions_unit               = $data['dimensions_unit'];
                    $packageHistory->signature_on_delivery         = $data['signature_on_delivery'];
                    $packageHistory->hazardous_goods               = $data['hazardous_goods'];
                    $packageHistory->hazardous_goods_type          = $data['hazardous_goods_type'];
                    $packageHistory->label_message                 = $data['label_message'];
                    $packageHistory->shipper_notes_1               = $data['shipper_notes_1'];
                    $packageHistory->shipper_notes_2               = $data['shipper_notes_2'];
                    $packageHistory->contains_alcohol              = $data['contains_alcohol'];
                    $packageHistory->insured_value                 = $data['insured_value'];
                    $packageHistory->service_code                  = $data['service_code'];
                    $packageHistory->extra_data                    = $data['extra_data'];
                    $packageHistory->Description                   = 'On hold - for company: '. $company->name;

                    $packageHistory->save();

                    $packageNotExists = PackageNotExists::find($request->get('Reference_Number_1'));

                    if($packageNotExists)
                    {
                        $packageNotExists->delete();
                    }

                    DB::commit();

                    return response()->json(

                        [
                            'message' => 'Package registered successfully',
                        ]
                    , 200);
                }
                catch(Exception $e)
                {
                    DB::rollback();

                    return response()->json(

                        [
                            'message' => 'Something went wrong with the registration process, please try again',
                        ]
                    , 500);
                }
            }

            return response()->json(

                [
                    'message' => 'The reference you are trying to register already exists',
                ]
            , 409);
        }
        else
        {
            return response()->json(

                [
                    'message' => 'Missing validation header (Authorization)',
                ]
            , 401);
        }
    }

    public function Get($Reference_Number_1)
    {
        $headers = getallheaders();

        if(isset($headers['Authorization']))
        {
            $company = Company::where('key_base64', $headers['Authorization'])->first();

            if($company == null)
            {
                return response()->json(

                    [
                        'message' => 'Incorrect credentials (Authorization) for the use of the api',
                    ]
                , 401);
            }

            $packageManifest = PackageHistory::where('Reference_Number_1', $Reference_Number_1)
                                            ->where('status', 'On hold')
                                            ->first();

            $packageHistoryList = PackageHistory::where('Reference_Number_1', $Reference_Number_1)
                                            ->orderBy('created_at')
                                            ->get();

            if(count($packageHistoryList) > 0)
            {
                $packageManifest = $packageHistoryList->first();

                $companyStatus = CompanyStatus::where('idCompany', $packageManifest->idCompany)->get();

                $packagesHistories = [];

                foreach($packageHistoryList as $packageHistory)
                {
                    $statusCompany = CompanyStatus::where('idCompany', $packageManifest->idCompany)
                                            ->where('status', $packageHistory->status)
                                            ->first();

                    if($statusCompany)
                    {
                        $statusCompany = $statusCompany->statusCodeCompany;
                    }
                    else
                    {
                        $statusCompany = $packageHistory->status;
                    }

                    $data = [

                        "status" => $packageHistory->status,
                        "city" => $packageHistory->Dropoff_City,
                        "state" => $packageHistory->Dropoff_Province,
                        "country" => "US",
                        "datetime" => date('Y-m-d H:i:s', strtotime($packageHistory->created_at)),
                    ];

                    array_push($packagesHistories, $data);
                }

                return response()->json(

                    [
                        'reference' => $Reference_Number_1,
                        'created_at' => date('Y-m-d H:i:s', strtotime($packageManifest->created_at)),
                        'history' => $packagesHistories,
                    ]
                , 200);
            }

            return response()->json(

                [
                    'Reference' => $Reference_Number_1,
                    'message' => 'The reference sent as parameter does not exist.',
                ]
            , 400);
        }
        else
        {
            return response()->json(

                [
                    'message' => 'Missing validation header (Authorization)',
                ]
            , 401);
        }
    }
}