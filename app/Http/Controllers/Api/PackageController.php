<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;

use App\Models\{ Comment, Company, CompanyStatus, PackageDispatch, PackageHistory, PackageInbound, PackageManifest, PackageNotExists, PackageReturn, PackageWarehouse, Routes };

use DateTime;
use DB;
use Log;
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

    public function Index(Request $request)
    {
        return response($request->check, 200)
            ->header('Content-Type', 'text/plain');
    }

    public function List(Request $request)
    {
        $packageList = Package::where('status', 'Manifest')
                                ->orderBy('created_at', 'desc')
                                ->paginate(2000);

        $quantityPackage = Package::where('status', 'Manifest')->get()->count();

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
            $data['contains_alcohol']      = $request->get('shipment')['shipment_details']['contains_alcohol'];
            $data['insured_value']         = $request->get('shipment')['shipment_details']['insured_value'];
            $data['service_code']          = $request->get('shipment')['shipment_details']['service_code'];
            $data['Route']                 = isset($request->get('shipment')['shipment_details']['route_name']) ? $request->get('shipment')['shipment_details']['route_name'] : '';

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

                    "ship_date.required" => "The field is required",

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
                                                    ->where('status', 'Manifest')
                                                    ->first();

            if(!$packageHistory)
            {
                try
                {
                    DB::beginTransaction();

                    $route = Routes::where('zipCode', $data['Dropoff_Postal_Code'])->first();

                    $routeName = $route ? $route->name : $data['Route'];

                    $package = new PackageManifest();

                    $package->idCompany                     = $company->id;
                    $package->company                       = $company->name;
                    $package->Reference_Number_1            = $data['Reference_Number_1'];
                    $package->Dropoff_Contact_Name          = $data['Dropoff_Contact_Name'];
                    $package->Dropoff_Contact_Phone_Number  = $data['Dropoff_Contact_Phone_Number'];
                    $package->Dropoff_Address_Line_1        = $data['Dropoff_Address_Line_1'];
                    $package->Dropoff_City                  = $data['Dropoff_City'];
                    $package->Dropoff_Province              = $data['Dropoff_Province'];
                    $package->Dropoff_Postal_Code           = $data['Dropoff_Postal_Code'];
                    $package->Weight                        = $data['Weight'];
                    $package->Route                         = $routeName;
                    $package->status                        = 'Manifest';
                    $package->manifest_id                   = $data['manifest_id'];
                    $package->mixing_center_shortcode       = $data['mixing_center_shortcode'];
                    $package->address_type                  = $data['address_type'];
                    $package->Dropoff_Address_Line_2        = $data['Dropoff_Address_Line_2'];
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
                    $package->contains_alcohol              = $data['contains_alcohol'];
                    $package->insured_value                 = $data['insured_value'];
                    $package->service_code                  = $data['service_code'];
                    $package->created_at                    = date('Y-m-d H:i:s');
                    $package->updated_at                    = date('Y-m-d H:i:s');

                    $package->save();

                    $packageHistory = new PackageHistory();

                    $packageHistory->id                            = uniqid();
                    $packageHistory->idCompany                     = $company->id;
                    $packageHistory->company                       = $company->name;
                    $packageHistory->Reference_Number_1            = $data['Reference_Number_1'];
                    $packageHistory->Dropoff_Contact_Name          = $data['Dropoff_Contact_Name'];
                    $packageHistory->Dropoff_Contact_Phone_Number  = $data['Dropoff_Contact_Phone_Number'];
                    $packageHistory->Dropoff_Address_Line_1        = $data['Dropoff_Address_Line_1'];
                    $packageHistory->Dropoff_City                  = $data['Dropoff_City'];
                    $packageHistory->Dropoff_Province              = $data['Dropoff_Province'];
                    $packageHistory->Dropoff_Postal_Code           = $data['Dropoff_Postal_Code'];
                    $packageHistory->Weight                        = $data['Weight'];
                    $packageHistory->Route                         = $routeName;
                    $packageHistory->status                        = 'Manifest';
                    $packageHistory->manifest_id                   = $data['manifest_id'];
                    $packageHistory->mixing_center_shortcode       = $data['mixing_center_shortcode'];
                    $packageHistory->address_type                  = $data['address_type'];
                    $packageHistory->Dropoff_Address_Line_2        = $data['Dropoff_Address_Line_2'];
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
                    $packageHistory->contains_alcohol              = $data['contains_alcohol'];
                    $packageHistory->insured_value                 = $data['insured_value'];
                    $packageHistory->service_code                  = $data['service_code'];
                    $packageHistory->Description                   = 'Not yet received: '. $company->name;
                    $packageHistory->created_at                    = date('Y-m-d H:i:s');
                    $packageHistory->updated_at                    = date('Y-m-d H:i:s');

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
                                            ->where('status', 'Manifest')
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
                        "datetime" => $packageManifest->created_at,
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

    public function SendStatusToInland($package, $status, $idPhoto = null, $created_at)
    {
        $statusCodeCompany = '';
        $key_webhook       = '';
        $url_webhook       = '';
        $pod_url           = "";
        $package_id        = "";
        $header_curl       = "";

        if($status == 'Return' || $status == 'ReInbound' || $status == 'Lost')
        {
            $company = Company::find($package->idCompany);

            $statusCodeCompany = $idPhoto;
            $key_webhook       = $company->key_webhook;
            $url_webhook       = $company->url_webhook;
            $typeServices      = $company->typeServices;
        }
        else
        {
            $companyStatus = CompanyStatus::with('company')
                                                ->where('idCompany', $package->idCompany)
                                                ->where('status', $status)
                                                ->first();

            Log::info('companyStatus');
            Log::info('===========');
            $statusCodeCompany = $companyStatus->statusCodeCompany;
            $key_webhook       = $companyStatus->company->key_webhook;
            $url_webhook       = $companyStatus->company->url_webhook;
            $typeServices      = $companyStatus->company->typeServices;
        }

        if($typeServices == 'API')
        {
            if($status == 'ReturnCompany')
            {
                $statusCodeCompany = $idPhoto;
            }
            elseif($status == 'Lost')
            {
                $statusCodeCompany = 'not_delivered_lost';
            }

            if($status == 'Delivery')
            {
                Log::info('idPhoto');
                Log::info($idPhoto);
                if(count($idPhoto) == 1)
                {
                    $pod_url = '"pod_url": "'. 'https://d15p8tr8p0vffz.cloudfront.net/'. $idPhoto[0] .'/800x.png' .'",';
                }
                else
                {
                    $photo1 = 'https://d15p8tr8p0vffz.cloudfront.net/'. $idPhoto[0] .'/800x.png';
                    $photo2 = 'https://d15p8tr8p0vffz.cloudfront.net/'. $idPhoto[1] .'/800x.png';

                    $pod_url = '"pod_url": "'. $photo1 .','. $photo2 .'" ,';
                }
            }

            Log::info($url_webhook . $package->Reference_Number_1 .'/update-status');
            Log::info($pod_url);

            $created_at_temp = DateTime::createFromFormat('Y-m-d H:i:s', $created_at);
            $created_at      = $created_at_temp->format(DateTime::ATOM);

            $curl = curl_init();

            if($package->idCompany == 1)
            {
                $header_curl = array(
                    'Authorization: '. $key_webhook,
                    'Content-Type: application/json'
                );

                $urlWebhook  = $url_webhook . $package->Reference_Number_1 .'/update-status';

                $dataSend = '{
                    "status": "'. $statusCodeCompany .'",
                    '. $pod_url .'
                    "metadata": [
                        {
                            "label": "",
                            "value": ""
                        }
                    ],
                    "datetime" : "'. $created_at .'"
                }';
            }
            else
            {
                $header_curl = array(
                    'code: '. $key_webhook,
                    'Content-Type: application/json'
                );

                $companyStatus = CompanyStatus::with('company')
                                                ->where('idCompany', $package->idCompany)
                                                ->where('status', $status)
                                                ->first();


                $statusCodeCompany = $companyStatus->statusCodeCompany;
                $dataSend          = $this->GetDataSmartKargo($package, $status, $statusCodeCompany, $created_at, $idPhoto);
                $urlWebhook        = $url_webhook;
            }

            Log::info('DATA SEND WEBHOOK- COMPANY');
            Log::info($dataSend);

            curl_setopt_array($curl, array(
                CURLOPT_URL => $urlWebhook,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $dataSend,
                CURLOPT_HTTPHEADER => $header_curl,
            ));

            $response = curl_exec($curl);
            $response = json_decode($response, true);

            curl_close($curl);

            Log::info($response);

            Log::info('===========  INLAND - STATUS UPDATE');
            Log::info('PACKAGE ID: '. $package->Reference_Number_1);
            Log::info('UPDATED STATUS: '. $statusCodeCompany .'[ '. $status .' ]');
            Log::info('REPONSE STATUS: '. $response['status']);
            Log::info('============INLAND - END STATUS UPDATE');
        }
    }

    public function GetDataSmartKargo($package, $status, $statusCodeCompany, $created_at, $idPhoto = null)
    {
        $created_at_now = $created_at;
        $created_at_rfc = $created_at;
        $created_at_gdl = $created_at;
        $created_at_adl = $created_at;
        $created_at_rts = $created_at;

        if($statusCodeCompany == 'RCF')
        {
            $dataStructure = '{
                "shipment_number": "'. $package->Reference_Number_1 .'",
                "tracking": {
                    "events": [
                        {
                            "name": "'. $statusCodeCompany .'",
                            "date": "'. $created_at .'",
                            "comment": "'. $status .'"
                        }
                    ],
                    "status": {
                        "to": "'. $statusCodeCompany .'",
                        "latitude": "'. $package->latitude .'",
                        "longitude": "'. $package->longitude .'"
                    }
                }
            }';
        }
        elseif($statusCodeCompany == 'GDL')
        {
            $packageHistory = PackageHistory::where('Reference_Number_1', $package->Reference_Number_1)
                                            ->where('status', 'Inbound')
                                            ->first();

            if($packageHistory)
            {
                $created_at_temp = DateTime::createFromFormat('Y-m-d H:i:s', $packageHistory->created_at);
                $created_at      = $created_at_temp->format(DateTime::ATOM);
            }

            $dataStructure = '{
                "shipment_number": "'. $package->Reference_Number_1 .'",
                "tracking": {
                    "events": [
                        {
                            "name": "RCF",
                            "date": "'. $created_at .'",
                            "comment": "Inbound"
                        },
                        {
                            "name": "'. $statusCodeCompany .'",
                            "date": "'. $created_at_now .'",
                            "comment": "'. $status .'"
                        }
                    ],
                    "status": {
                        "to": "'. $statusCodeCompany .'",
                        "latitude": "'. $package->latitude .'",
                        "longitude": "'. $package->longitude .'",
                    }
                }
            }';
        }
        elseif($statusCodeCompany == 'DDL' || $statusCodeCompany == 'ADL')
        {
            $packageInbound = PackageHistory::where('Reference_Number_1', $package->Reference_Number_1)
                                            ->where('status', 'Inbound')
                                            ->first();

            $packageDispatch = PackageDispatch::find($package->Reference_Number_1);

            if($packageInbound)
            {
                $created_at_temp = DateTime::createFromFormat('Y-m-d H:i:s', $packageInbound->created_at);
                $created_at_rfc      = $created_at_temp->format(DateTime::ATOM);
            }

            if($packageDispatch)
            {
                $created_at_temp = DateTime::createFromFormat('Y-m-d H:i:s', $packageDispatch->created_at);
                $created_at_gdl  = $created_at_temp->format(DateTime::ATOM);
            }

            $contentPhoto = '';

            if($statusCodeCompany == 'DDL')
            {
                if(count($idPhoto) == 1)
                {
                    $photo1 = 'https://d15p8tr8p0vffz.cloudfront.net/'. $idPhoto[0] .'/800x.png';

                    $contentPhoto = '{
                                        "mimeType": "url",
                                        "content": "'. $photo1 .'"
                                    }';
                }
                else
                {
                    $photo1 = 'https://d15p8tr8p0vffz.cloudfront.net/'. $idPhoto[0] .'/800x.png';
                    $photo2 = 'https://d15p8tr8p0vffz.cloudfront.net/'. $idPhoto[1] .'/800x.png';

                    $contentPhoto = '{
                                        "mimeType": "url",
                                        "content": "'. $photo1 .'"
                                    },
                                    {
                                        "mimeType": "url",
                                        "content": "'. $photo2 .'"
                                    }';
                }
            }

            $dataStructure = '{
                "shipment_number": "'. $package->Reference_Number_1 .'",
                "tracking": {
                    "events": [
                        {
                            "name": "RCF",
                            "date": "'. $created_at_rfc .'",
                            "comment": "Inbound"
                        },
                        {
                            "name": "GDL",
                            "date": "'. $created_at_gdl .'",
                            "comment": "Dispatch"
                        },
                        {
                            "name": "'. $statusCodeCompany .'",
                            "date": "'. $created_at_now .'",
                            "comment": "'. (isset($package->Description_Return) ? $package->Description_Return : $status) .'"
                        }
                    ],
                    "Pods":[
                        '. $contentPhoto .'
                    ],
                    "status": {
                        "to": "'. $statusCodeCompany .'",
                        "latitude":"",
                        "longitude":""
                    }
                }
            }';
        }
        elseif($statusCodeCompany == 'RTS')
        {
            $packageInbound = PackageHistory::where('Reference_Number_1', $package->Reference_Number_1)
                                            ->where('status', 'Inbound')
                                            ->first();

            $packageDispatch = PackageHistory::where('Reference_Number_1', $package->Reference_Number_1)->where('status', 'Dispatch')->get();
            $packageReturn   = PackageReturn::where('Reference_Number_1', $package->Reference_Number_1)->get();

            if($packageInbound)
            {
                $created_at_temp = DateTime::createFromFormat('Y-m-d H:i:s', $packageInbound->created_at);
                $created_at_rfc      = $created_at_temp->format(DateTime::ATOM);
            }

            if(count($packageDispatch) > 0)
            {
                $created_at_temp = DateTime::createFromFormat('Y-m-d H:i:s', $packageDispatch->last()->created_at);
                $created_at_gdl  = $created_at_temp->format(DateTime::ATOM);
            }

            if(count($packageReturn) > 0)
            {
                $created_at_temp = DateTime::createFromFormat('Y-m-d H:i:s', $packageReturn->last()->created_at);
                $created_at_adl  = $created_at_temp->format(DateTime::ATOM);
            }

            $dataStructure = '{
                "shipment_number": "'. $package->Reference_Number_1 .'",
                "tracking": {
                    "events": [
                        {
                            "name": "RCF",
                            "date": "'. $created_at_rfc .'",
                            "comment": "Inbound"
                        },
                        {
                            "name": "GDL",
                            "date": "'. $created_at_gdl .'",
                            "comment": "Dispatch"
                        },
                        {
                            "name": "ADL",
                            "date": "'. $created_at_adl .'",
                            "comment": "'. ( $packageReturn ? $packageReturn->Description_Return : 'ReInbound' ).'"
                        },
                        {
                            "name": "'. $statusCodeCompany .'",
                            "date": "'. $created_at_now .'",
                            "comment": "'. $package->Description_Return .'"
                        }
                    ],
                    "Pods":[
                        {
                            "mimeType": "url",
                            "content": ""
                        }
                    ],
                    "status": {
                        "to": "'. $statusCodeCompany .'",
                        "latitude": "'. $package->latitude .'",
                        "longitude": "'. $package->longitude .'",
                    }
                }
            }';
        }


        return $dataStructure;
    }

    public function UpdateManifestRouteByZipCode()
    {
        $listPackageManifest = PackageManifest::all();

        foreach($listPackageManifest as $packageManifest)
        {
            $route = Routes::where('zipCode', $packageManifest->Dropoff_Postal_Code)->first();

            if($route)
            {
                $packageManifest = PackageManifest::find($packageManifest->Reference_Number_1);

                $packageManifest->Route = $route->name;

                $packageManifest->save();
            }
        }

        echo "updated manifest";
    }

    public function UpdateInboundRouteByZipCode()
    {
        $listPackageInbound = PackageInbound::all();

        foreach($listPackageInbound as $packageInbound)
        {
            $route = Routes::where('zipCode', $packageInbound->Dropoff_Postal_Code)->first();

            if($route)
            {
                $packageInbound = PackageInbound::find($packageInbound->Reference_Number_1);

                $packageInbound->Route = $route->name;

                $packageInbound->save();
            }
        }

        echo "updated inbound";
    }

    public function UpdateWarehouseRouteByZipCode()
    {
        $listPackageWarehouse = PackageWarehouse::all();

        foreach($listPackageWarehouse as $packageWarehouse)
        {
            $route = Routes::where('zipCode', $packageWarehouse->Dropoff_Postal_Code)->first();

            if($route)
            {
                $packageWarehouse = PackageWarehouse::find($packageWarehouse->Reference_Number_1);

                $packageWarehouse->Route = $route->name;

                $packageWarehouse->save();
            }
        }

        echo "updated warehouse";
    }
}
