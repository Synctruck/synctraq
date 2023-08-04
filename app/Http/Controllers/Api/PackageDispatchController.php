<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\{ Company, PackageDispatch, PackageFailed, PackageHistory, User };

use DB;
use DateTime;
use Log;
use Session;

class PackageDispatchController extends Controller
{
    /*
        * Retorna packages que están en dispatch y  que fueron asignados a un driver
        *
        * @parametro: apiKey
        * @parametro: idDriver
        * @validation: apiKey and idDriver
    */
    public function ListByDriverInland($apiKey, $idDriver)
    {
        $company = Company::where('id', 1)
                            ->where('key_api', $apiKey)
                            ->first();

        if($company)
        {
            $driver = User::where('idRole', 4)->find($idDriver);

            if($driver)
            {
                $shipments = $this->ListPackagesInDispatch($idDriver);

                return response()->json(
                    [
                        'message' => "The driver was found",
                        'shipments' => $shipments
                    ]
                , 200);
            }
            else
            {
                return response()->json(['message' => "The driver was not found"], 404);
            }
        }
        else
        {
            return response()->json(['message' => "Authentication Failed: incorrect api-key"], 401);
        }
    }

    /*
        * Listar Packages que están en dispatch y  que fueron asignados a un driver
        * @parametro: idDriver
        * formato: usar modelo solicitado
    */
    public function ListPackagesInDispatch($idDriver)
    {
        $packageDispatchList = PackageDispatch::where('idUserDispatch', $idDriver)
                                                ->where('status', 'Dispatch')
                                                ->get();

        $packageList = [];

        foreach($packageDispatchList as $packageDispatch)
        {
            $created_at = DateTime::createFromFormat('Y-m-d H:i:s', $packageDispatch->created_at);
            $created_at = $created_at->format(DateTime::ATOM);

            $needsSignature = $packageDispatch->company == 'EIGHTVAPE' ? true : false;

            $package = [
                'barcode' => $packageDispatch->Reference_Number_1,
                'createdAt' => $packageDispatch->created_at,
                'shipToStreet1' => $packageDispatch->Dropoff_Address_Line_1,
                'shipToStreet2' => $packageDispatch->Dropoff_Address_Line_2,
                'shipToCity' => $packageDispatch->Dropoff_City,
                'shipToState' => $packageDispatch->Dropoff_Province,
                'shipToPostalCode' => $packageDispatch->Dropoff_Postal_Code,
                'shipToName' => $packageDispatch->Dropoff_Contact_Name,
                'needsSignature' => $needsSignature,
            ];

            array_push($packageList, $package);
        }

        return $packageList;
    }

    public function UpdateStatus(Request $request, $apiKey)
    {
        $company = Company::where('id', 1)
                            ->where('key_api', $apiKey)
                            ->first();

        if($company)
        {
            $validator = Validator::make($request->all(),

                [
                    "package_id" => ["required", "min:10", "max:40"],
                    "status" => ["required", "min:6", "max:8", Rule::in(['Delivery', 'Failed'])],
                    "datetime" => ["required"],
                ],
            );

            if($validator->fails())
            {
                return response()->json(["errors" => $validator->errors()], 422);
            }

            $Reference_Number_1 = $request['package_id'];
            $Date_Delivery      = $request['datetime'];

            $packageDispatch = PackageDispatch::where('status', 'Dispatch')->find($Reference_Number_1);

            if($packageDispatch)
            {                
                try
                {
                    DB::beginTransaction();

                    if($request['status'] == 'Delivery')
                    {
                        $strtotime     = strtotime($Date_Delivery);
                        $Date_Delivery = date('Y-m-d H:i:s', $strtotime);
                        $photoUrl      = $request['pod_url'];

                        $this->TaskCompleted($request, $Date_Delivery);

                        //data for INLAND
                        $packageController = new PackageController();
                        $packageController->SendStatusToInland($packageDispatch, 'Delivery', explode(',', $photoUrl), $Date_Delivery);
                        //end data for inland
                    }
                    else
                    {
                        $this->TaskFailed($request, $Date_Delivery);
                    }

                    DB::commit();

                    return response()->json(['message' => "Correct: updated status to DELIVERY"], 200);
                }
                catch(Exception $e)
                {
                    DB::rollback();

                    return response()->json([
                        'package_id' => $Reference_Number_1,
                        'message' => "A problem occurred while performing the process, please try again."
                    ], 500);
                }
            }

            return response()->json([
                'package_id' => $Reference_Number_1,
                'message' => "The package is not in Dispatch status."
            ], 400);
        }
        else
        {
            return response()->json(['message' => "Authentication Failed: incorrect api-key"], 401);
        }
    }

    public function TaskCompleted($request, $Date_Delivery)
    {
        $Reference_Number_1 = $request['package_id'];
        $created_at         = date('Y-m-d H:i:s'); 
        $status             = $request['status'];
        $Date_Delivery      = $Date_Delivery;
        $photoUrl           = $request['pod_url'];
        $Description        = $request['description'];

        $packageDispatch = PackageDispatch::find($Reference_Number_1);
        $packageDispatch->photoUrl      = $photoUrl;
        $packageDispatch->Date_Delivery = $Date_Delivery;
        $packageDispatch->status        = $status;
        $packageDispatch->updated_at    = $created_at;
        $packageDispatch->save();

        $packageHistory = new PackageHistory();

        $packageHistory->id                           = uniqid();
        $packageHistory->Reference_Number_1           = $packageDispatch->Reference_Number_1;
        $packageHistory->idCompany                    = $packageDispatch->idCompany;
        $packageHistory->company                      = $packageDispatch->company;
        $packageHistory->idStore                      = $packageDispatch->idStore;
        $packageHistory->store                        = $packageDispatch->store;
        $packageHistory->Dropoff_Contact_Name         = $packageDispatch->Dropoff_Contact_Name;
        $packageHistory->Dropoff_Company              = $packageDispatch->Dropoff_Company;
        $packageHistory->Dropoff_Contact_Phone_Number = $packageDispatch->Dropoff_Contact_Phone_Number;
        $packageHistory->Dropoff_Contact_Email        = $packageDispatch->Dropoff_Contact_Email;
        $packageHistory->Dropoff_Address_Line_1       = $packageDispatch->Dropoff_Address_Line_1;
        $packageHistory->Dropoff_Address_Line_2       = $packageDispatch->Dropoff_Address_Line_2;
        $packageHistory->Dropoff_City                 = $packageDispatch->Dropoff_City;
        $packageHistory->Dropoff_Province             = $packageDispatch->Dropoff_Province;
        $packageHistory->Dropoff_Postal_Code          = $packageDispatch->Dropoff_Postal_Code;
        $packageHistory->Notes                        = $packageDispatch->Notes;
        $packageHistory->Weight                       = $packageDispatch->Weight;
        $packageHistory->Route                        = $packageDispatch->Route;
        $packageHistory->idTeam                       = $packageDispatch->idTeam;
        $packageHistory->idUserDispatch               = $packageDispatch->idUserDispatch;
        $packageHistory->idUser                       = $packageDispatch->idUser;
        $packageHistory->idUserDelivery               = $packageDispatch->idUserDispatch;
        $packageHistory->Date_Delivery                = $Date_Delivery;
        $packageHistory->Description                  = $Description;
        $packageHistory->status                       = 'Delivery';
        $packageHistory->actualDate                   = $created_at;
        $packageHistory->created_at                   = $created_at;
        $packageHistory->updated_at                   = $created_at;

        $packageHistory->save();
    }

    public function TaskFailed(Request $request, $Date_Failed)
    {
        $Reference_Number_1  = $request['package_id'];
        $created_at          = date('Y-m-d H:i:s'); 
        $status              = $request['status'];
        $photoUrl            = $request['pod_url'];
        $Description_Onfleet = $request['failure_notes'];

        $packageFailed = new PackageFailed();
        $packageFailed->Reference_Number_1           = $packageDispatch->Reference_Number_1;
        $packageFailed->idCompany                    = $packageDispatch->idCompany;
        $packageFailed->company                      = $packageDispatch->company;
        $packageFailed->idStore                      = $packageDispatch->idStore;
        $packageFailed->store                        = $packageDispatch->store;
        $packageFailed->Dropoff_Contact_Name         = $packageDispatch->Dropoff_Contact_Name;
        $packageFailed->Dropoff_Company              = $packageDispatch->Dropoff_Company;
        $packageFailed->Dropoff_Contact_Phone_Number = $packageDispatch->Dropoff_Contact_Phone_Number;
        $packageFailed->Dropoff_Contact_Email        = $packageDispatch->Dropoff_Contact_Email;
        $packageFailed->Dropoff_Address_Line_1       = $packageDispatch->Dropoff_Address_Line_1;
        $packageFailed->Dropoff_Address_Line_2       = $packageDispatch->Dropoff_Address_Line_2;
        $packageFailed->Dropoff_City                 = $packageDispatch->Dropoff_City;
        $packageFailed->Dropoff_Province             = $packageDispatch->Dropoff_Province;
        $packageFailed->Dropoff_Postal_Code          = $packageDispatch->Dropoff_Postal_Code;
        $packageFailed->Notes                        = $packageDispatch->Notes;
        $packageFailed->Weight                       = $packageDispatch->Weight;
        $packageFailed->Route                        = $packageDispatch->Route;
        $packageFailed->idTeam                       = $packageDispatch->idTeam;
        $packageFailed->idUserDispatch               = $packageDispatch->idUserDispatch;
        $packageFailed->idUser                       = $packageDispatch->idUserDispatch;
        $packageFailed->Description_Onfleet          = $Description_Onfleet;
        $packageFailed->idOnfleet                    = $packageDispatch->idOnfleet;
        $packageFailed->taskOnfleet                  = $packageDispatch->taskOnfleet;
        $packageFailed->quantity                     = $packageDispatch->quantity;
        $packageFailed->status                       = 'Failed';
        $packageFailed->created_at                   = $created_at;
        $packageFailed->updated_at                   = $created_at;

        $packageFailed->save();

        $packageHistory = new PackageHistory();

        $packageHistory->id                           = uniqid();
        $packageHistory->Reference_Number_1           = $packageDispatch->Reference_Number_1;
        $packageHistory->idCompany                    = $packageDispatch->idCompany;
        $packageHistory->company                      = $packageDispatch->company;
        $packageHistory->idStore                      = $packageDispatch->idStore;
        $packageHistory->store                        = $packageDispatch->store;
        $packageHistory->Dropoff_Contact_Name         = $packageDispatch->Dropoff_Contact_Name;
        $packageHistory->Dropoff_Company              = $packageDispatch->Dropoff_Company;
        $packageHistory->Dropoff_Contact_Phone_Number = $packageDispatch->Dropoff_Contact_Phone_Number;
        $packageHistory->Dropoff_Contact_Email        = $packageDispatch->Dropoff_Contact_Email;
        $packageHistory->Dropoff_Address_Line_1       = $packageDispatch->Dropoff_Address_Line_1;
        $packageHistory->Dropoff_Address_Line_2       = $packageDispatch->Dropoff_Address_Line_2;
        $packageHistory->Dropoff_City                 = $packageDispatch->Dropoff_City;
        $packageHistory->Dropoff_Province             = $packageDispatch->Dropoff_Province;
        $packageHistory->Dropoff_Postal_Code          = $packageDispatch->Dropoff_Postal_Code;
        $packageHistory->Notes                        = $packageDispatch->Notes;
        $packageHistory->Weight                       = $packageDispatch->Weight;
        $packageHistory->Route                        = $packageDispatch->Route;
        $packageHistory->idTeam                       = $packageDispatch->idTeam;
        $packageHistory->idUserDispatch               = $packageDispatch->idUserDispatch;
        $packageHistory->idUser                       = $packageDispatch->idUserDispatch;
        $packageHistory->Description_Onfleet          = $Description_Onfleet;
        $packageHistory->quantity                     = $packageDispatch->quantity;
        $packageHistory->status                       = 'Failed';
        $packageHistory->actualDate                   = $created_at;
        $packageHistory->created_at                   = $created_at;
        $packageHistory->updated_at                   = $created_at;

        $packageHistory->save();
        
        $packageDispatch->delete();
    }
}