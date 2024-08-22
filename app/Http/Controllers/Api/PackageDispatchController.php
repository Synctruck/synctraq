<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\{ Company, PackageDispatch, ToDeductLostPackages, PackageTerminal, PackageFailed, PackageLost, PackageLmCarrier, PackageInbound, PackageHistory, PackageManifest, PackageWarehouse, User, PackageBlocked, PackagePreDispatch, PackageReturnCompany, PackageReturn};

use App\Http\Controllers\Api\PackageController;

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

    /*
        * Devuelve la información de un determinado Package
        * @parametro: apiKey, Reference_Number_1
        * @request: request (información que manda la PODApp)
        * response: devuelve formato solicitado
    */
    public function GetPackage($apiKey, $Reference_Number_1)
    {
        $company = Company::where('id', 1)
                            ->where('key_api', $apiKey)
                            ->first();

        if($company)
        {
            $packageDispatch = PackageDispatch::find($Reference_Number_1);

            if($packageDispatch)
            {
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
                    'needsSignature' => false,
                ];

                return response()->json($package);
            }

            return response()->json([
                'package_id' => $Reference_Number_1,
                'message' => "The package is not in DISPATCH or DELIVERY status."
            ], 400);
        }
        else
        {
            return response()->json(['message' => "Authentication Failed: incorrect api-key"], 401);
        }
    }

    public function UpdateStatusFromSyncweb(Request $request, $apiKey)
    {
        $validator = Validator::make($request->all(),
            [
                "barcode" => ["required"],
                "status" => ["required", Rule::in(['delivered', 'failed', 'inbound', 'dispatch','lost','pre_rts','rts', 'scan_in_last_mile_carrier', 'not_delivered'])],
                "createdAt" => ["required", "date"],
            ],
        );

        if($validator->fails())
            return response()->json(["errors" => $validator->errors()], 422);

        if($request['status'] != "delivered" && $request['status'] != "failed" && $request['status'] != "inbound" && $request['status'] != "lost" && $request['status'] != "pre_rts" && $request['status'] != 'rts'  && $request['status'] != 'scan_in_last_mile_carrier'  && $request['status'] != 'not_delivered')
        {
            $validator = Validator::make($request->all(),
                [
                    "driverId"  => ["required"],
                ],
            );
        }

        if($validator->fails())
            return response()->json(["errors" => $validator->errors()], 422);

        $company = Company::where('id', 1)->where('key_api', $apiKey)->first();

        if($company)
        {
            try
            {
                DB::beginTransaction();

                if($request['status'] == 'delivered')
                    $this->InsertDeliveryFromSyncWeb($request, $apiKey);
                else if($request['status'] == 'failed')
                    $this->InsertFailed($request, $apiKey);
                 else if ($request['status'] == 'inbound') {
                        if (isset($request['failureReason']) && !empty($request['failureReason'])) {
                            $this->InsertReInbound($request, $apiKey);
                        } else {
                            $this->InsertInbound($request, $apiKey);
                        }
                }else if($request['status'] == 'lost')
                    $this->InsertLost($request, $apiKey);
                else if($request['status'] == 'pre_rts')
                    $this->InsertPreRts($request, $apiKey);
                else if($request['status'] == 'rts')
                    $this->InsertRts($request, $apiKey);
                else if($request['status'] == 'scan_in_last_mile_carrier')
                    $this->InsertLMCarrierFromSyncWeb($request, $apiKey);
                else if($request['status'] == 'not_delivered')
                    $this->InsertTerminalFromSyncWeb($request, $apiKey);
                else{
                    $result = $this->InsertDispatchFromSyncWeb($request, $apiKey);

                    if($result === false)
                        return response()->json(['message' => "The package does not exists"], 400);
                    else if($result === "notDriver")
                        return response()->json(['message' => "The driver does not exists"], 400);
                    else if($result === "notTeam")
                        return response()->json(['message' => "The team does not exists"], 400);
                }

                DB::commit();

                return response()->json(['message' => "shipment success"], 200);
            }
            catch(Exception $e)
            {
                DB::rollback();

                return response()->json(['message' => "There was an error while carrying out the process"], 400);
            }
        }
        else
        {
            return response()->json(['message' => "Authentication Failed: incorrect api-key"], 401);
        }
    }

    public function InsertDispatchFromSyncWeb(Request $request, $apiKey)
    {
        Log::info("InsertDispatchFromSyncWeb");
        $replicationChildOrgName    = $request['replicationChildOrgName'];
        $package = PackageManifest::find($request['barcode']);
        $package = $package ? $package : PackageInbound::find($request['barcode']);
        $package = $package ? $package : PackageWarehouse::where('status', 'Warehouse')->find($request['barcode']);
        $package = $package ? $package : PackageDispatch::where('status', 'Dispatch')->find($request['barcode']);
        $package = $package ? $package : PackageFailed::where('status', 'Failed')->find($request['barcode']);
        Log::info($package);
        if($package)
        {
            $driver = User::where('driverId', $request['driverId'])->where('idRole', 4)->first();

            if($driver)
            {
                $team = User::where('idRole', 3)->find($driver->idTeam);

                if($team)
                {
                    $created_at = date('Y-m-d H:i:s');

                    if($package->status == 'Manifest' || $package->status == 'Inbound' || $package->status == 'Warehouse' || $package->status == 'Failed')
                    {
                        Log::info('PackageDispatch: ');

                        $packageDispatch = new PackageDispatch();
                        $packageDispatch->Reference_Number_1           = $package->Reference_Number_1;
                        $packageDispatch->idCompany                    = $package->idCompany;
                        $packageDispatch->company                      = $package->company;
                        $packageDispatch->idStore                      = $package->idStore;
                        $packageDispatch->store                        = $package->store;
                        $packageDispatch->Dropoff_Contact_Name         = $package->Dropoff_Contact_Name;
                        $packageDispatch->Dropoff_Company              = $package->Dropoff_Company;
                        $packageDispatch->Dropoff_Contact_Phone_Number = $package->Dropoff_Contact_Phone_Number;
                        $packageDispatch->Dropoff_Contact_Email        = $package->Dropoff_Contact_Email;
                        $packageDispatch->Dropoff_Address_Line_1       = $package->Dropoff_Address_Line_1;
                        $packageDispatch->Dropoff_Address_Line_2       = $package->Dropoff_Address_Line_2;
                        $packageDispatch->Dropoff_City                 = $package->Dropoff_City;
                        $packageDispatch->Dropoff_Province             = $package->Dropoff_Province;
                        $packageDispatch->Dropoff_Postal_Code          = $package->Dropoff_Postal_Code;
                        $packageDispatch->Notes                        = $package->Notes;
                        $packageDispatch->Weight                       = $package->Weight;
                        $packageDispatch->Route                        = $package->Route;
                        $packageDispatch->idTeam                       = $team->id;
                        $packageDispatch->idUserDispatch               = $driver->id;
                        $packageDispatch->Date_Dispatch                = $created_at;
                        $packageDispatch->quantity                     = $package->quantity;
                        $packageDispatch->status                       = 'Dispatch';
                        $packageDispatch->created_at                   = $created_at;
                        $packageDispatch->updated_at                   = $created_at;
                        $packageDispatch->save();
                    }
                    else
                    {
                        $package->idTeam         = $team->id;
                        $package->idUserDispatch = $driver->id;
                        $package->updated_at     = $created_at;
                        $package->save();
                    }

                    $packageHistory = new PackageHistory();
                    $packageHistory->id                           = uniqid();
                    $packageHistory->Reference_Number_1           = $package->Reference_Number_1;
                    $packageHistory->idCompany                    = $package->idCompany;
                    $packageHistory->company                      = $package->company;
                    $packageHistory->idStore                      = $package->idStore;
                    $packageHistory->store                        = $package->store;
                    $packageHistory->Dropoff_Contact_Name         = $package->Dropoff_Contact_Name;
                    $packageHistory->Dropoff_Company              = $package->Dropoff_Company;
                    $packageHistory->Dropoff_Contact_Phone_Number = $package->Dropoff_Contact_Phone_Number;
                    $packageHistory->Dropoff_Contact_Email        = $package->Dropoff_Contact_Email;
                    $packageHistory->Dropoff_Address_Line_1       = $package->Dropoff_Address_Line_1;
                    $packageHistory->Dropoff_Address_Line_2       = $package->Dropoff_Address_Line_2;
                    $packageHistory->Dropoff_City                 = $package->Dropoff_City;
                    $packageHistory->Dropoff_Province             = $package->Dropoff_Province;
                    $packageHistory->Dropoff_Postal_Code          = $package->Dropoff_Postal_Code;
                    $packageHistory->Notes                        = $package->Notes;
                    $packageHistory->Weight                       = $package->Weight;
                    $packageHistory->Route                        = $package->Route;
                    $packageHistory->idTeam                       = $team->id;
                    $packageHistory->idUserDispatch               = $driver->id;
                    $packageHistory->Description                  = 'Dispatch from SyncFreight to:' . $team->name .' / '. $driver->name .' '. $driver->nameOfOwner;
                    $packageHistory->status                       = 'Dispatch';
                    $packageHistory->Date_Dispatch                = $created_at;
                    $packageHistory->actualDate                   = $created_at;
                    $packageHistory->created_at                   = $created_at;
                    $packageHistory->updated_at                   = $created_at;
                    $packageHistory->save();

                    Log::info('eliminar: '. $package->status);

                    if($package->status == 'Manifest' || $package->status == 'Inbound' || $package->status == 'Warehouse' || $package->status == 'Failed')
                    {
                        Log::info('eliminado: '. $package->status);
                        $package->delete();
                    }

                    if($request['status'] != 'dispatch')
                        $this->UpdateStatusFromSyncweb($request,$apiKey);

                    return true;
                }

                return "notTeam";
            }

            return "notDriver";
        }

        return false;
    }

    public function InsertDeliveryFromSyncWeb(Request $request, $apiKey)
    {
        $Reference_Number_1         = $request['barcode'];
        $photoUrl                   = $request['pictures'];
        $latitude                   = $request['latitude'];
        $longitude                  = $request['longitude'];
        $created_at                 = $request['createdAt'];
        $replicationChildOrgName    = $request['replicationChildOrgName'];
        $packageDelivery = PackageDispatch::where('status', 'Delivery')->find($Reference_Number_1);


        $created_at_obj = DateTime::createFromFormat('Y-m-d H:i:s', $created_at);
        if ($created_at_obj === false) {
            Log::error('Error creating DateTime from createdAt: ' . $created_at);
            return response()->json(['message' => 'Invalid date format for createdAt'], 400);
        }

        if($packageDelivery){
            return response()->json([
                'package_id' => $Reference_Number_1,
                'message' => "Ok."
            ], 200);
        }

        $packageDispatch = PackageDispatch::where('status', 'Dispatch')->find($Reference_Number_1);

        if($packageDispatch)
        {

            if(count($photoUrl) == 0)
                $photoUrl = '';
            elseif(count($photoUrl) == 1)
                $photoUrl = $photoUrl[0];
            else
                $photoUrl = $photoUrl[0] .','. $photoUrl[1];

            $packageDispatch->photoUrl      = $photoUrl;
            $packageDispatch->arrivalLonLat = $longitude .','. $latitude;
            $packageDispatch->filePhoto1    = '';
            $packageDispatch->filePhoto2    = '';
            $packageDispatch->status        = 'Delivery';
            $packageDispatch->Date_Delivery = $created_at;
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
            $packageHistory->idUser                       = $packageDispatch->idUserDispatch;
            $packageHistory->quantity                     = $packageDispatch->quantity;
            $packageHistory->Description                  = 'From Syncweb';
            $packageHistory->Date_Delivery                = $created_at;
            $packageHistory->status                       = 'Delivery';
            $packageHistory->actualDate                   = date('Y-m-d H:i:s');
            $packageHistory->created_at                   = $created_at;
            $packageHistory->updated_at                   = $created_at;
            $packageHistory->save();

            $packageController = new PackageController();

            if($replicationChildOrgName != "FALCON EXPRESS" && $replicationChildOrgName != "Brooks Courier"){
            if($packageDispatch->idCompany == 1)
                $packageController->SendStatusToInland($packageDispatch, 'Delivery', explode(',', $photoUrl), $created_at);
                LOG::INFO("STATUS SENT TO INLAND");
            $packageHistory = PackageHistory::where('Reference_Number_1', $packageDispatch->Reference_Number_1)
                                        ->where('sendToInland', 1)
                                        ->where('status', 'Manifest')
                                        ->first();

            if($packageHistory)
                $packageController->SendStatusToOtherCompany($packageDispatch, 'Delivery', explode(',', $photoUrl), $created_at);
            }
        }
        else
        {
            $this->InsertDispatchFromSyncWeb($request, $apiKey);
        }
    }

    public function InsertDispatch(Request $request, $apiKey)
    {
        try
        {
            DB::beginTransaction();

            $company = Company::where('id', 1)
                            ->where('key_api', $apiKey)
                            ->first();

            if($company)
            {
                $package = PackageManifest::find($request['package_id']);
                $package = $package ? $package : PackageInbound::find($request['package_id']);
                $package = $package ? $package : PackageWarehouse::where('status', 'Warehouse')->find($request['package_id']);
                $package = $package ? $package : PackageDispatch::where('status', 'Dispatch')->find($request['package_id']);
                $package = $package ? $package : PackageFailed::where('status', 'Failed')->find($request['package_id']);

                if($package)
                {
                    $driver = User::where('idRole', 4)->find($request['id_driver']);

                    if($driver)
                    {
                        $team = User::where('idRole', 3)->find($driver->idTeam);

                        if($team)
                        {
                            $created_at = date('Y-m-d H:i:s');

                            if($package->status == 'Manifest' || $package->status == 'Inbound' || $package->status == 'Warehouse' || $package->status == 'Failed')
                            {
                                $packageDispatch = new PackageDispatch();
                                $packageDispatch->Reference_Number_1           = $package->Reference_Number_1;
                                $packageDispatch->idCompany                    = $package->idCompany;
                                $packageDispatch->company                      = $package->company;
                                $packageDispatch->idStore                      = $package->idStore;
                                $packageDispatch->store                        = $package->store;
                                $packageDispatch->Dropoff_Contact_Name         = $package->Dropoff_Contact_Name;
                                $packageDispatch->Dropoff_Company              = $package->Dropoff_Company;
                                $packageDispatch->Dropoff_Contact_Phone_Number = $package->Dropoff_Contact_Phone_Number;
                                $packageDispatch->Dropoff_Contact_Email        = $package->Dropoff_Contact_Email;
                                $packageDispatch->Dropoff_Address_Line_1       = $package->Dropoff_Address_Line_1;
                                $packageDispatch->Dropoff_Address_Line_2       = $package->Dropoff_Address_Line_2;
                                $packageDispatch->Dropoff_City                 = $package->Dropoff_City;
                                $packageDispatch->Dropoff_Province             = $package->Dropoff_Province;
                                $packageDispatch->Dropoff_Postal_Code          = $package->Dropoff_Postal_Code;
                                $packageDispatch->Notes                        = $package->Notes;
                                $packageDispatch->Weight                       = $package->Weight;
                                $packageDispatch->Route                        = $package->Route;
                                $packageDispatch->idTeam                       = $team->id;
                                $packageDispatch->idUserDispatch               = $driver->id;
                                $packageDispatch->Date_Dispatch                = $created_at;
                                $packageDispatch->quantity                     = $package->quantity;
                                $packageDispatch->status                       = 'Dispatch';
                                $packageDispatch->created_at                   = $created_at;
                                $packageDispatch->updated_at                   = $created_at;
                                $packageDispatch->save();
                            }
                            else
                            {
                                $package->idTeam         = $team->id;
                                $package->idUserDispatch = $driver->id;
                                $package->updated_at     = $created_at;
                                $package->save();
                            }

                            $packageHistory = new PackageHistory();
                            $packageHistory->id                           = uniqid();
                            $packageHistory->Reference_Number_1           = $package->Reference_Number_1;
                            $packageHistory->idCompany                    = $package->idCompany;
                            $packageHistory->company                      = $package->company;
                            $packageHistory->idStore                      = $package->idStore;
                            $packageHistory->store                        = $package->store;
                            $packageHistory->Dropoff_Contact_Name         = $package->Dropoff_Contact_Name;
                            $packageHistory->Dropoff_Company              = $package->Dropoff_Company;
                            $packageHistory->Dropoff_Contact_Phone_Number = $package->Dropoff_Contact_Phone_Number;
                            $packageHistory->Dropoff_Contact_Email        = $package->Dropoff_Contact_Email;
                            $packageHistory->Dropoff_Address_Line_1       = $package->Dropoff_Address_Line_1;
                            $packageHistory->Dropoff_Address_Line_2       = $package->Dropoff_Address_Line_2;
                            $packageHistory->Dropoff_City                 = $package->Dropoff_City;
                            $packageHistory->Dropoff_Province             = $package->Dropoff_Province;
                            $packageHistory->Dropoff_Postal_Code          = $package->Dropoff_Postal_Code;
                            $packageHistory->Notes                        = $package->Notes;
                            $packageHistory->Weight                       = $package->Weight;
                            $packageHistory->Route                        = $package->Route;
                            $packageHistory->idTeam                       = $team->id;
                            $packageHistory->idUserDispatch               = $driver->id;
                            $packageHistory->Description                  = 'Dispatch for APP POD to:' . $team->name .' / '. $driver->name .' '. $driver->nameOfOwner;
                            $packageHistory->status                       = 'Dispatch';
                            $packageHistory->actualDate                   = $created_at;
                            $packageHistory->created_at                   = $created_at;
                            $packageHistory->updated_at                   = $created_at;
                            $packageHistory->save();

                            if($package->status == 'Manifest' || $package->status == 'Inbound' || $package->status == 'Warehouse' || $package->status == 'Failed')
                            {
                                $package->delete();
                            }

                            DB::commit();

                            return response()->json([
                                'package_id' => $request['package_id'],
                                'message' => "Shipment has been received."
                            ], 200);
                        }
                        else
                        {
                            return response()->json([
                                'id_driver' => $request['id_driver'],
                                'message' => "The Team assigned to the driver does not exist"
                            ], 400);
                        }
                    }
                    else
                    {
                        return response()->json([
                            'id_driver' => $request['id_driver'],
                            'message' => "The driver does not exist"
                        ], 400);
                    }
                }
                else
                {
                    return response()->json([
                        'package_id' => $request['package_id'],
                        'message' => "The package is not in MANIFEST or DISPATCH or WAREHOUSE status."
                    ], 400);
                }
            }
            else
            {
                return response()->json(['message' => "Authentication Failed: incorrect api-key"], 401);
            }
        }
        catch(Exception $e)
        {
            DB::rollback();

            return response()->json(['message' => "There was an error while carrying out the process"], 400);
        }
    }

    public function InsertDelivery(Request $request, $apiKey)
    {
        $Reference_Number_1 = $request['barcode'];
        $photoUrl           = $request['pictures'];
        $latitude           = $request['latitude'];
        $longitude          = $request['longitude'];
        $created_at         = date('Y-m-d H:i:s', strtotime($request['statusDate']));

        try
        {
            DB::beginTransaction();

            $company = Company::where('id', 1)
                            ->where('key_api', $apiKey)
                            ->first();

            if($company)
            {
                $packageDelivery = PackageDispatch::where('status', 'Delivery')->find($Reference_Number_1);

                if($packageDelivery){
                    return response()->json([
                        'package_id' => $Reference_Number_1,
                        'message' => "Ok."
                    ], 200);
                }

                $packageDispatch = PackageDispatch::where('status', 'Dispatch')->find($Reference_Number_1);
                $packageDispatch = $packageDispatch ? $packageDispatch : PackageWarehouse::where('status', 'Warehouse')->find($Reference_Number_1);

                if($packageDispatch)
                {
                    if($packageDispatch->status == 'Warehouse')
                    {
                        $package = $packageDispatch;

                        $packageDispatch = new PackageDispatch();
                        $packageDispatch->Reference_Number_1           = $package->Reference_Number_1;
                        $packageDispatch->idCompany                    = $package->idCompany;
                        $packageDispatch->company                      = $package->company;
                        $packageDispatch->idStore                      = $package->idStore;
                        $packageDispatch->store                        = $package->store;
                        $packageDispatch->Dropoff_Contact_Name         = $package->Dropoff_Contact_Name;
                        $packageDispatch->Dropoff_Company              = $package->Dropoff_Company;
                        $packageDispatch->Dropoff_Contact_Phone_Number = $package->Dropoff_Contact_Phone_Number;
                        $packageDispatch->Dropoff_Contact_Email        = $package->Dropoff_Contact_Email;
                        $packageDispatch->Dropoff_Address_Line_1       = $package->Dropoff_Address_Line_1;
                        $packageDispatch->Dropoff_Address_Line_2       = $package->Dropoff_Address_Line_2;
                        $packageDispatch->Dropoff_City                 = $package->Dropoff_City;
                        $packageDispatch->Dropoff_Province             = $package->Dropoff_Province;
                        $packageDispatch->Dropoff_Postal_Code          = $package->Dropoff_Postal_Code;
                        $packageDispatch->Notes                        = $package->Notes;
                        $packageDispatch->Weight                       = $package->Weight;
                        $packageDispatch->Route                        = $package->Route;
                        $packageDispatch->idTeam                       = $team->id;
                        $packageDispatch->idUserDispatch               = $driver->id;
                        $packageDispatch->Date_Dispatch                = $created_at;
                        $packageDispatch->quantity                     = $package->quantity;
                        $packageDispatch->status                       = 'Dispatch';
                        $packageDispatch->created_at                   = $created_at;
                        $packageDispatch->updated_at                   = $created_at;
                        $packageDispatch->save();
                    }
                    else
                    {
                        $packageDispatch->photoUrl      = $photoUrl;
                        $packageDispatch->arrivalLonLat = $longitude .','. $latitude;
                        $packageDispatch->status        = 'Delivery';
                        $packageDispatch->Date_Delivery = $created_at;
                        $packageDispatch->save();
                    }

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
                    $packageHistory->quantity                     = $packageDispatch->quantity;
                    $packageHistory->Description                  = 'From SyncPOD';
                    $packageHistory->Date_Delivery                = $created_at;
                    $packageHistory->status                       = 'Delivery';
                    $packageHistory->actualDate                   = date('Y-m-d H:i:s');
                    $packageHistory->created_at                   = $created_at;
                    $packageHistory->updated_at                   = $created_at;
                    $packageHistory->save();

                    $packageController = new PackageController();


                    if($packageDispatch->idCompany == 1)
                        $packageController->SendStatusToInland($packageDispatch, 'Delivery', explode(',', $photoUrl), $created_at);

                    $packageHistory = PackageHistory::where('Reference_Number_1', $packageDispatch->Reference_Number_1)
                                                ->where('sendToInland', 1)
                                                ->where('status', 'Manifest')
                                                ->first();

                    if($packageHistory)
                        $packageController->SendStatusToOtherCompany($packageDispatch, 'Delivery', explode(',', $photoUrl), $created_at);

                    DB::commit();

                    return response()->json([
                        'package_id' => $Reference_Number_1,
                        'message' => "Shipment has been received."
                    ], 200);
                }
                else
                {
                    return response()->json([
                        'package_id' => $Reference_Number_1,
                        'message' => "The package is not in DISPATCH."
                    ], 400);
                }
            }
            else
            {
                return response()->json(['message' => "Authentication Failed: incorrect api-key"], 401);
            }
        }
        catch(Exception $e)
        {
            DB::rollback();

            return response()->json(['message' => "There was an error while carrying out the process"], 400);
        }
    }

    public function InsertFailed(Request $request, $apiKey)
    {
        $Reference_Number_1 = $request['barcode'];
        $Description_POD    = '['. $request['failureReason'] .', '. $request['notes'] .']';
        $created_at         = $request['createdAt'];
        $photoUrl           = $request['pictures'];
        $replicationChildOrgName    = $request['replicationChildOrgName'];

        $packageDispatch = PackageManifest::find($request['barcode']);
        $packageDispatch = $packageDispatch ? $packageDispatch : PackageInbound::find($request['barcode']);
        $packageDispatch = $packageDispatch ? $packageDispatch : PackageWarehouse::where('status', 'Warehouse')->find($request['barcode']);
        $packageDispatch = $packageDispatch ? $packageDispatch : PackageDispatch::where('status', 'Dispatch')->find($request['barcode']);
        $packageDispatch = $packageDispatch ? $packageDispatch : PackageFailed::where('status', 'Failed')->find($request['barcode']);

        if($packageDispatch)
        {
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
            $packageFailed->Description_Onfleet          = $Description_POD;
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
            $packageHistory->quantity                     = $packageDispatch->quantity;
            $packageHistory->Description_Onfleet          = $Description_POD;
            $packageHistory->status                       = 'Failed';
            $packageHistory->actualDate                   = date('Y-m-d H:i:s');
            $packageHistory->created_at                   = $created_at;
            $packageHistory->updated_at                   = $created_at;
            $packageHistory->save();

            $packageController = new PackageController();
            if( $replicationChildOrgName  != "FALCON EXPRESS" && $replicationChildOrgName != "Brooks Courier"){
            if($packageDispatch->idCompany == 1)
                $packageController->SendStatusToInland($packageDispatch, 'Failed', null, $created_at);

            $packageHistory = PackageHistory::where('Reference_Number_1', $packageDispatch->Reference_Number_1)
                                        ->where('sendToInland', 1)
                                        ->where('status', 'Manifest')
                                        ->first();

            if($packageHistory)
                $packageController->SendStatusToOtherCompany($packageDispatch, 'Failed', null, $created_at);
            }
            $packageDispatch->delete();
        }
    }

    public function InsertInbound(Request $request, $apiKey)
    {
        $Reference_Number_1 = $request['barcode'];
        $Description_POD    = '['. $request['failureReason'] .', '. $request['notes'] .']';
        $created_at         = $request['createdAt'];
        $photoUrl           = $request['pictures'];
        $replicationChildOrgName    = $request['replicationChildOrgName'];


        // Verifica que la fecha esté en el formato correcto
            $created_at_obj = DateTime::createFromFormat('Y-m-d H:i:s', $created_at);
            if ($created_at_obj === false) {
                Log::error('Error creating DateTime from createdAt: ' . $created_at);
                return response()->json(['message' => 'Invalid date format for createdAt'], 400);
            }

        $packageManifest = PackageManifest::find($request['barcode']);

        if($packageManifest)
        {
            if(!$packageManifest->filter && count($packageManifest->blockeds) == 0)
            {
                $packageInbound = new PackageInbound();
                $packageInbound->Reference_Number_1           = $packageManifest->Reference_Number_1;
                $packageInbound->idCompany                    = $packageManifest->idCompany;
                $packageInbound->company                      = $packageManifest->company;
                $packageInbound->idStore                      = $packageManifest->idStore;
                $packageInbound->store                        = $packageManifest->store;
                $packageInbound->Dropoff_Contact_Name         = $packageManifest->Dropoff_Contact_Name;
                $packageInbound->Dropoff_Company              = $packageManifest->Dropoff_Company;
                $packageInbound->Dropoff_Contact_Phone_Number = $packageManifest->Dropoff_Contact_Phone_Number;
                $packageInbound->Dropoff_Contact_Email        = $packageManifest->Dropoff_Contact_Email;
                $packageInbound->Dropoff_Address_Line_1       = $packageManifest->Dropoff_Address_Line_1;
                $packageInbound->Dropoff_Address_Line_2       = $packageManifest->Dropoff_Address_Line_2;
                $packageInbound->Dropoff_City                 = $packageManifest->Dropoff_City;
                $packageInbound->Dropoff_Province             = $packageManifest->Dropoff_Province;
                $packageInbound->Dropoff_Postal_Code          = $packageManifest->Dropoff_Postal_Code;
                $packageInbound->Notes                        = $packageManifest->Notes;
                $packageInbound->Weight                       = $packageManifest->Weight;
                $packageInbound->Route                        = $packageManifest->Route;
                $packageInbound->quantity                     = $packageManifest->quantity;
                $packageInbound->status                       = 'Inbound';
                $packageInbound->created_at                   = $created_at;
                $packageInbound->updated_at                   = $created_at;
                $packageInbound->save();

                $packageHistory = new PackageHistory();
                $packageHistory->id                           = uniqid();
                $packageHistory->Reference_Number_1           = $packageManifest->Reference_Number_1;
                $packageHistory->idCompany                    = $packageManifest->idCompany;
                $packageHistory->company                      = $packageManifest->company;
                $packageHistory->idStore                      = $packageManifest->idStore;
                $packageHistory->store                        = $packageManifest->store;
                $packageHistory->Dropoff_Contact_Name         = $packageManifest->Dropoff_Contact_Name;
                $packageHistory->Dropoff_Company              = $packageManifest->Dropoff_Company;
                $packageHistory->Dropoff_Contact_Phone_Number = $packageManifest->Dropoff_Contact_Phone_Number;
                $packageHistory->Dropoff_Contact_Email        = $packageManifest->Dropoff_Contact_Email;
                $packageHistory->Dropoff_Address_Line_1       = $packageManifest->Dropoff_Address_Line_1;
                $packageHistory->Dropoff_Address_Line_2       = $packageManifest->Dropoff_Address_Line_2;
                $packageHistory->Dropoff_City                 = $packageManifest->Dropoff_City;
                $packageHistory->Dropoff_Province             = $packageManifest->Dropoff_Province;
                $packageHistory->Dropoff_Postal_Code          = $packageManifest->Dropoff_Postal_Code;
                $packageHistory->Notes                        = $packageManifest->Notes;
                $packageHistory->Weight                       = $packageManifest->Weight;
                $packageHistory->Route                        = $packageManifest->Route;
                $packageHistory->quantity                     = $packageManifest->quantity;
                $packageHistory->Description                  = "Inbound from Syncfreight";
                $packageHistory->status                       = 'Inbound';
                $packageHistory->actualDate                   = date('Y-m-d H:i:s');
                $packageHistory->created_at                   = $created_at;
                $packageHistory->updated_at                   = $created_at;
                $packageHistory->save();

                $packageController = new PackageController();
                if($replicationChildOrgName  != "FALCON EXPRESS" && $replicationChildOrgName != "Brooks Courier")
                {
                if($packageManifest->idCompany == 1)
                    $packageController->SendStatusToInland($packageManifest, 'Inbound', null, $created_at);

                $packageHistory = PackageHistory::where('Reference_Number_1', $packageManifest->Reference_Number_1)
                                            ->where('sendToInland', 1)
                                            ->where('status', 'Manifest')
                                            ->first();

                if($packageHistory)
                    $packageController->SendStatusToOtherCompany($packageManifest, 'Inbound', null, $created_at);
                }
                $packageManifest->delete();
            }
        }
    }

    public function InsertReInbound(Request $request, $apiKey)
    {
        Log::info('========== RE-INBOUND');
        Log::info('Reference_Number_1: '. $request->get('Reference_Number_1'));

        $Reference_Number_1 = $request['barcode'];
        $Description_POD    = '['. $request['failureReason'] .', '. $request['notes'] .']';
        $created_at         = $request['createdAt'];
        $Description_Return = $request['failureReason'];
        $photoUrl           = $request['pictures'];
        $replicationChildOrgName    = $request['replicationChildOrgName'];


        //Verifica que la fecha esté en el formato correcto
        $created_at_obj = DateTime::createFromFormat('Y-m-d H:i:s', $created_at);
        if ($created_at_obj === false) {
            Log::error('Error creating DateTime from createdAt: ' . $created_at);
            return response()->json(['message' => 'Invalid date format for createdAt'], 400);
        }


        $packageBlocked = PackageBlocked::where('Reference_Number_1', $Reference_Number_1)->first();

        if ($packageBlocked) {
            return ['stateAction' => 'validatedFilterPackage', 'packageBlocked' => $packageBlocked, 'packageManifest' => null];
        }

        $package = PackagePreDispatch::where('Reference_Number_1', $Reference_Number_1)->first();

        if ($package) {
            return ['stateAction' => 'packageInPreDispatch'];
        }

        $packageLost = PackageLost::find($Reference_Number_1);

        if ($packageLost) {
            return ['stateAction' => 'validatedLost'];
        }

        $packageDispatch = PackageFailed::where('Reference_Number_1', $Reference_Number_1)->first();

        if ($packageDispatch == null) {
            $packageDispatch = PackageDispatch::where('Reference_Number_1', $Reference_Number_1)->first();

            if ($packageDispatch == null) {
                $packageDispatch = PackageLmCarrier::where('Reference_Number_1', $Reference_Number_1)->first();
            }
        }

        Log::info('package: '. $packageDispatch);

        if ($packageDispatch) {
            $nowDate              = date('Y-m-d H:i:s');
            if ($apiKey) {
                try {
                    DB::beginTransaction();

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
                    $packageHistory->Date_Inbound                 = date('Y-m-d H:i:s');
                    $packageHistory->Description                  = 'For: REINBOUND FROM SYNCFREIGHT ';
                    $packageHistory->Description_Return           = $Description_Return;
                    $packageHistory->inbound                      = 1;
                    $packageHistory->quantity                     = $packageDispatch->quantity;
                    $packageHistory->status                       = 'Reinbound';
                    $packageHistory->actualDate                   = $nowDate;
                    $packageHistory->created_at                   = $created_at;
                    $packageHistory->updated_at                   = $created_at;

                    $packageHistory->save();

                    $nowDate = date('Y-m-d H:i:s', strtotime($nowDate .'+6 second'));

                    $deleteDispatch = true;

                    $packageReturn = new PackageReturn();

                    $packageReturn->id                           = uniqid();
                    $packageReturn->Reference_Number_1           = $packageDispatch->Reference_Number_1;
                    $packageReturn->idCompany                    = $packageDispatch->idCompany;
                    $packageReturn->company                      = $packageDispatch->company;
                    $packageReturn->idStore                      = $packageDispatch->idStore;
                    $packageReturn->store                        = $packageDispatch->store;
                    $packageReturn->Dropoff_Contact_Name         = $packageDispatch->Dropoff_Contact_Name;
                    $packageReturn->Dropoff_Company              = $packageDispatch->Dropoff_Company;
                    $packageReturn->Dropoff_Contact_Phone_Number = $packageDispatch->Dropoff_Contact_Phone_Number;
                    $packageReturn->Dropoff_Contact_Email        = $packageDispatch->Dropoff_Contact_Email;
                    $packageReturn->Dropoff_Address_Line_1       = $packageDispatch->Dropoff_Address_Line_1;
                    $packageReturn->Dropoff_Address_Line_2       = $packageDispatch->Dropoff_Address_Line_2;
                    $packageReturn->Dropoff_City                 = $packageDispatch->Dropoff_City;
                    $packageReturn->Dropoff_Province             = $packageDispatch->Dropoff_Province;
                    $packageReturn->Dropoff_Postal_Code          = $packageDispatch->Dropoff_Postal_Code;
                    $packageReturn->Notes                        = $packageDispatch->Notes;
                    $packageReturn->Weight                       = $packageDispatch->Weight;
                    $packageReturn->Route                        = $packageDispatch->Route;
                    $packageReturn->idTeam                       = $packageDispatch->idTeam;
                    $packageReturn->idUserReturn                 = $packageDispatch->idUserDispatch;
                    $packageReturn->Date_Return                  = $created_at;
                    $packageReturn->Description_Return           = $Description_Return;
                    $packageReturn->status                       = 'Return';

                    $packageReturn->save();

                    //update dispatch
                    $packageHistory = PackageHistory::where('Reference_Number_1', $Reference_Number_1)
                                                    ->where('dispatch', 1)
                                                    ->first();

                    if ($packageHistory) {
                        $packageHistory->dispatch = 0;

                        $packageHistory->save();
                    }

                    //update inbound
                    $packageHistory = PackageHistory::where('Reference_Number_1', $Reference_Number_1)
                                                    ->where('inbound', 1)
                                                    ->first();

                    if ($packageHistory) {
                        $packageHistory->inbound  = 0;

                        $packageHistory->save();
                    }

                    $packageWarehouse = PackageWarehouse::find($packageDispatch->Reference_Number_1);

                    if ($packageWarehouse) {
                        $packageWarehouse->delete();
                    }

                    $comment = 'Retry';
                    if ($comment) {
                        $packageWarehouse = new PackageWarehouse();

                        $packageWarehouse->Reference_Number_1           = $packageDispatch->Reference_Number_1;
                        $packageWarehouse->idCompany                    = $packageDispatch->idCompany;
                        $packageWarehouse->company                      = $packageDispatch->company;
                        $packageWarehouse->idStore                      = $packageDispatch->idStore;
                        $packageWarehouse->store                        = $packageDispatch->store;
                        $packageWarehouse->Dropoff_Contact_Name         = $packageDispatch->Dropoff_Contact_Name;
                        $packageWarehouse->Dropoff_Company              = $packageDispatch->Dropoff_Company;
                        $packageWarehouse->Dropoff_Contact_Phone_Number = $packageDispatch->Dropoff_Contact_Phone_Number;
                        $packageWarehouse->Dropoff_Contact_Email        = $packageDispatch->Dropoff_Contact_Email;
                        $packageWarehouse->Dropoff_Address_Line_1       = $packageDispatch->Dropoff_Address_Line_1;
                        $packageWarehouse->Dropoff_Address_Line_2       = $packageDispatch->Dropoff_Address_Line_2;
                        $packageWarehouse->Dropoff_City                 = $packageDispatch->Dropoff_City;
                        $packageWarehouse->Dropoff_Province             = $packageDispatch->Dropoff_Province;
                        $packageWarehouse->Dropoff_Postal_Code          = $packageDispatch->Dropoff_Postal_Code;
                        $packageWarehouse->Notes                        = $packageDispatch->Notes;
                        $packageWarehouse->Weight                       = $packageDispatch->Weight;
                        $packageWarehouse->Route                        = $packageDispatch->Route;
                        $packageWarehouse->quantity                     = $packageDispatch->quantity;
                        $packageWarehouse->status                       = 'Warehouse';

                        $packageWarehouse->save();

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
                        $packageHistory->Description                  = 'For: return from SyncFreight';
                        $packageHistory->quantity                     = $packageDispatch->quantity;
                        $packageHistory->status                       = 'Warehouse';
                        $packageHistory->actualDate                   = $nowDate;
                        $packageHistory->created_at                   = $created_at;
                        $packageHistory->updated_at                   = $created_at;

                        $packageHistory->save();
                    }
                    if($replicationChildOrgName  != "FALCON EXPRESS" && $replicationChildOrgName != "Brooks Courier"){
                    //data for INLAND
                    $packageController = new PackageController();
                    $packageController->SendStatusToInland($packageDispatch, 'ReInbound', null, $created_at);

                    $packageHistory = PackageHistory::where('Reference_Number_1', $packageDispatch->Reference_Number_1)
                                                ->where('sendToInland', 1)
                                                ->where('status', 'Manifest')
                                                ->first();

                    if ($packageHistory) {
                        $packageController->SendStatusToOtherCompany($packageDispatch, 'ReInbound', null, $created_at_ReInbound);
                        }
                    }

                    if ($deleteDispatch) {
                        $packageDispatch->delete();

                        DB::commit();
                    }
                } catch (Exception $e) {
                    DB::rollback();

                }
            }
        } else {
            $packageReturnCompany = PackageReturnCompany::where('Reference_Number_1', $Reference_Number_1)->first();
        }
    }

    public function InsertLMCarrierFromSyncWeb(Request $request, $apiKey){
        $Reference_Number_1 = $request->get('barcode');
        $created_at = $request->get('createdAt');
        $packageBlocked = PackageBlocked::where('Reference_Number_1', $Reference_Number_1)->first();
        $replicationChildOrgName    = $request['replicationChildOrgName'];

        if ($packageBlocked) {
            return ['stateAction' => 'validatedFilterPackage', 'packageBlocked' => $packageBlocked, 'packageManifest' => null];
        }

        $packageInbound = PackageManifest::find($Reference_Number_1)
            ?: PackageInbound::find($Reference_Number_1)
            ?: PackageWarehouse::find($Reference_Number_1)
            ?: PackagePreDispatch::find($Reference_Number_1)
            ?: PackageDispatch::find($Reference_Number_1)
            ?: PackageFailed::find($Reference_Number_1)
            ?: PackageReturnCompany::find($Reference_Number_1)
            ?: PackageLost::find($Reference_Number_1);

        if (!$packageInbound) {
            return ['stateAction' => 'notExists'];
        }

        if ($packageInbound instanceof PackagePreDispatch) {
            return ['stateAction' => 'validatedPreDispatch'];
        }

        if ($packageInbound instanceof PackageReturnCompany) {
            return ['stateAction' => 'validatedReturnCompany'];
        }

        if ($packageInbound instanceof PackageLost) {
            return ['stateAction' => 'validatedLost'];
        }

        $idTeampackage = PackageHistory::where('Reference_Number_1', $packageInbound->Reference_Number_1)
            ->where('status', $packageInbound->status)
            ->get();

        Log::info('Package From History');
        Log::info($idTeampackage);

        try {
            DB::beginTransaction();

            $packageLmCarrier = new PackageLmCarrier();

            $packageLmCarrier->Reference_Number_1 = $packageInbound->Reference_Number_1;
            $packageLmCarrier->idCompany = $packageInbound->idCompany;
            $packageLmCarrier->company = $packageInbound->company;
            $packageLmCarrier->Dropoff_Contact_Name = $packageInbound->Dropoff_Contact_Name;
            $packageLmCarrier->Dropoff_Company = $packageInbound->Dropoff_Company;
            $packageLmCarrier->Dropoff_Contact_Phone_Number = $packageInbound->Dropoff_Contact_Phone_Number;
            $packageLmCarrier->Dropoff_Contact_Email = $packageInbound->Dropoff_Contact_Email;
            $packageLmCarrier->Dropoff_Address_Line_1 = $packageInbound->Dropoff_Address_Line_1;
            $packageLmCarrier->Dropoff_Address_Line_2 = $packageInbound->Dropoff_Address_Line_2;
            $packageLmCarrier->Dropoff_City = $packageInbound->Dropoff_City;
            $packageLmCarrier->Dropoff_Province = $packageInbound->Dropoff_Province;
            $packageLmCarrier->Dropoff_Postal_Code = $packageInbound->Dropoff_Postal_Code;
            $packageLmCarrier->Route = $packageInbound->Route;
            $packageLmCarrier->Weight = $packageInbound->Weight;
            $packageLmCarrier->created_at = date('Y-m-d H:i:s');
            $packageLmCarrier->updated_at = date('Y-m-d H:i:s');
            $packageLmCarrier->status = 'LM Carrier';

            $packageLmCarrier->save();

            $packageHistory = new PackageHistory();

            $packageHistory->id = uniqid();
            $packageHistory->Reference_Number_1 = $packageInbound->Reference_Number_1;
            $packageHistory->idCompany = $packageInbound->idCompany;
            $packageHistory->company = $packageInbound->company;
            $packageHistory->Dropoff_Contact_Name = $packageInbound->Dropoff_Contact_Name;
            $packageHistory->Dropoff_Company = $packageInbound->Dropoff_Company;
            $packageHistory->Dropoff_Contact_Phone_Number = $packageInbound->Dropoff_Contact_Phone_Number;
            $packageHistory->Dropoff_Contact_Email = $packageInbound->Dropoff_Contact_Email;
            $packageHistory->Dropoff_Address_Line_1 = $packageInbound->Dropoff_Address_Line_1;
            $packageHistory->Dropoff_Address_Line_2 = $packageInbound->Dropoff_Address_Line_2;
            $packageHistory->Dropoff_City = $packageInbound->Dropoff_City;
            $packageHistory->Dropoff_Province = $packageInbound->Dropoff_Province;
            $packageHistory->Dropoff_Postal_Code = $packageInbound->Dropoff_Postal_Code;
            $packageHistory->Notes = $packageInbound->Notes;
            $idTeam = $packageInbound->idTeam;
            if ($idTeam) {
                $packageHistory->idTeam = $packageInbound->idTeam;
            }
            $packageHistory->Weight = $packageInbound->Weight;
            $packageHistory->Route = $packageInbound->Route;
            $packageHistory->idUser = "";
            $packageHistory->Date_Inbound = date('Y-m-d H:s:i');
            $packageHistory->Description = $request->get('comment') ? $request->get('comment') : 'LM Carrier from SyncFreight';
            $packageHistory->status = 'LM Carrier';
            $packageHistory->actualDate = date('Y-m-d H:i:s');
            $packageHistory->created_at = date('Y-m-d H:i:s');
            $packageHistory->updated_at = date('Y-m-d H:i:s');

            $packageHistory->save();

            $packageController = new PackageController();
            if($replicationChildOrgName  != "FALCON EXPRESS" && $replicationChildOrgName != "Brooks Courier"){
            $packageController->SendStatusToInland($packageInbound, 'scan_in_last_mile_carrier', null, date('Y-m-d H:i:s'));

            $packageHistory = PackageHistory::where('Reference_Number_1', $packageInbound->Reference_Number_1)
                ->where('sendToInland', 1)
                ->where('status', 'Manifest')
                ->first();

            if ($packageHistory) {
                $packageController->SendStatusToOtherCompany($packageInbound, 'scan_in_last_mile_carrier', null, date('Y-m-d H:i:s'));
            }
            }
            $package = $packageInbound;
            $packageInbound->delete();

            DB::commit();

            return ['stateAction' => true, 'packageInbound' => $package];
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error: ' . $e->getMessage());

            return ['stateAction' => false];
        }
    }

    public function InsertTerminalFromSyncWeb(Request $request, $apiKey){
        $Reference_Number_1 = $request->get('barcode');
        $created_at = $request->get('createdAt');
        $packageBlocked = PackageBlocked::where('Reference_Number_1', $Reference_Number_1)->first();
        $replicationChildOrgName    = $request['replicationChildOrgName'];

        if ($packageBlocked) {
            return ['stateAction' => 'validatedFilterPackage', 'packageBlocked' => $packageBlocked, 'packageManifest' => null];
        }

        $packageInbound = PackageManifest::find($Reference_Number_1)
            ?: PackageInbound::find($Reference_Number_1)
            ?: PackageWarehouse::find($Reference_Number_1)
            ?: PackagePreDispatch::find($Reference_Number_1)
            ?: PackageDispatch::find($Reference_Number_1)
            ?: PackageFailed::find($Reference_Number_1)
            ?: PackageReturnCompany::find($Reference_Number_1)
            ?: PackageLost::find($Reference_Number_1)
            ?: PackageLmCarrier::find($Reference_Number_1);

        if (!$packageInbound) {
            return ['stateAction' => 'notExists'];
        }

        if ($packageInbound instanceof PackagePreDispatch) {
            return ['stateAction' => 'validatedPreDispatch'];
        }

        if ($packageInbound instanceof PackageReturnCompany) {
            return ['stateAction' => 'validatedReturnCompany'];
        }

        if ($packageInbound instanceof PackageLost) {
            return ['stateAction' => 'validatedLost'];
        }

        $idTeampackage = PackageHistory::where('Reference_Number_1', $packageInbound->Reference_Number_1)
            ->where('status', $packageInbound->status)
            ->get();

        Log::info('Package From History');
        Log::info($idTeampackage);

        try {
            DB::beginTransaction();

            $packageTerminal = new PackageTerminal();

            $packageTerminal->Reference_Number_1 = $packageInbound->Reference_Number_1;
            $packageTerminal->idCompany = $packageInbound->idCompany;
            $packageTerminal->company = $packageInbound->company;
            $packageTerminal->Dropoff_Contact_Name = $packageInbound->Dropoff_Contact_Name;
            $packageTerminal->Dropoff_Company = $packageInbound->Dropoff_Company;
            $packageTerminal->Dropoff_Contact_Phone_Number = $packageInbound->Dropoff_Contact_Phone_Number;
            $packageTerminal->Dropoff_Contact_Email = $packageInbound->Dropoff_Contact_Email;
            $packageTerminal->Dropoff_Address_Line_1 = $packageInbound->Dropoff_Address_Line_1;
            $packageTerminal->Dropoff_Address_Line_2 = $packageInbound->Dropoff_Address_Line_2;
            $packageTerminal->Dropoff_City = $packageInbound->Dropoff_City;
            $packageTerminal->Dropoff_Province = $packageInbound->Dropoff_Province;
            $packageTerminal->Dropoff_Postal_Code = $packageInbound->Dropoff_Postal_Code;
            $packageTerminal->Route = $packageInbound->Route;
            $packageTerminal->Weight = $packageInbound->Weight;
            $packageTerminal->created_at = date('Y-m-d H:i:s');
            $packageTerminal->updated_at = date('Y-m-d H:i:s');
            $packageTerminal->status = 'Terminal';

            $packageTerminal->save();

            $packageHistory = new PackageHistory();

            $packageHistory->id = uniqid();
            $packageHistory->Reference_Number_1 = $packageInbound->Reference_Number_1;
            $packageHistory->idCompany = $packageInbound->idCompany;
            $packageHistory->company = $packageInbound->company;
            $packageHistory->Dropoff_Contact_Name = $packageInbound->Dropoff_Contact_Name;
            $packageHistory->Dropoff_Company = $packageInbound->Dropoff_Company;
            $packageHistory->Dropoff_Contact_Phone_Number = $packageInbound->Dropoff_Contact_Phone_Number;
            $packageHistory->Dropoff_Contact_Email = $packageInbound->Dropoff_Contact_Email;
            $packageHistory->Dropoff_Address_Line_1 = $packageInbound->Dropoff_Address_Line_1;
            $packageHistory->Dropoff_Address_Line_2 = $packageInbound->Dropoff_Address_Line_2;
            $packageHistory->Dropoff_City = $packageInbound->Dropoff_City;
            $packageHistory->Dropoff_Province = $packageInbound->Dropoff_Province;
            $packageHistory->Dropoff_Postal_Code = $packageInbound->Dropoff_Postal_Code;
            $packageHistory->Notes = $packageInbound->Notes;
            $idTeam = $packageInbound->idTeam;
            if ($idTeam) {
                $packageHistory->idTeam = $packageInbound->idTeam;
            }
            $packageHistory->Weight = $packageInbound->Weight;
            $packageHistory->Route = $packageInbound->Route;
            $packageHistory->idUser = "";
            $packageHistory->Date_Inbound = date('Y-m-d H:s:i');
            $packageHistory->Description = $request->get('comment') ? $request->get('comment') : 'Terminal from SyncFreight';
            $packageHistory->status = 'Terminal';
            $packageHistory->actualDate = date('Y-m-d H:i:s');
            $packageHistory->created_at = date('Y-m-d H:i:s');
            $packageHistory->updated_at = date('Y-m-d H:i:s');

            $packageHistory->save();

            $packageController = new PackageController();
            if($replicationChildOrgName  != "FALCON EXPRESS" && $replicationChildOrgName != "Brooks Courier"){
            $packageController->SendStatusToInland($packageInbound, 'not_delivered', null, date('Y-m-d H:i:s'));

            $packageHistory = PackageHistory::where('Reference_Number_1', $packageInbound->Reference_Number_1)
                ->where('sendToInland', 1)
                ->where('status', 'Manifest')
                ->first();

            if ($packageHistory) {
                $packageController->SendStatusToOtherCompany($packageInbound, 'not_delivered', null, date('Y-m-d H:i:s'));
            }
            }
            $package = $packageInbound;
            $packageInbound->delete();

            DB::commit();

            return ['stateAction' => true, 'packageInbound' => $package];
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error: ' . $e->getMessage());

            return ['stateAction' => false];
        }
    }


    public function InsertLost(Request $request, $apiKey)
        {
            $Reference_Number_1 = $request->get('barcode');
            $created_at = $request->get('createdAt');
            $replicationChildOrgName    = $request['replicationChildOrgName'];
            $packageBlocked = PackageBlocked::where('Reference_Number_1', $Reference_Number_1)->first();

            if ($packageBlocked) {
                return ['stateAction' => 'validatedFilterPackage', 'packageBlocked' => $packageBlocked, 'packageManifest' => null];
            }

            $packageInbound = PackageManifest::find($Reference_Number_1)
                ?: PackageInbound::find($Reference_Number_1)
                ?: PackageWarehouse::find($Reference_Number_1)
                ?: PackagePreDispatch::find($Reference_Number_1)
                ?: PackageDispatch::find($Reference_Number_1)
                ?: PackageFailed::find($Reference_Number_1)
                ?: PackageReturnCompany::find($Reference_Number_1)
                ?: PackageLost::find($Reference_Number_1);

            if (!$packageInbound) {
                return ['stateAction' => 'notExists'];
            }

            if ($packageInbound instanceof PackagePreDispatch) {
                return ['stateAction' => 'validatedPreDispatch'];
            }

            if ($packageInbound instanceof PackageReturnCompany) {
                return ['stateAction' => 'validatedReturnCompany'];
            }

            if ($packageInbound instanceof PackageLost) {
                return ['stateAction' => 'validatedLost'];
            }

            $idTeampackage = PackageHistory::where('Reference_Number_1', $packageInbound->Reference_Number_1)
                ->where('status', $packageInbound->status)
                ->get();

            Log::info('Package From History');
            Log::info($idTeampackage);

            try {
                DB::beginTransaction();

                $packageLost = new PackageLost();

                $packageLost->Reference_Number_1 = $packageInbound->Reference_Number_1;
                $packageLost->idCompany = $packageInbound->idCompany;
                $packageLost->company = $packageInbound->company;
                $packageLost->Dropoff_Contact_Name = $packageInbound->Dropoff_Contact_Name;
                $packageLost->Dropoff_Company = $packageInbound->Dropoff_Company;
                $packageLost->Dropoff_Contact_Phone_Number = $packageInbound->Dropoff_Contact_Phone_Number;
                $packageLost->Dropoff_Contact_Email = $packageInbound->Dropoff_Contact_Email;
                $packageLost->Dropoff_Address_Line_1 = $packageInbound->Dropoff_Address_Line_1;
                $packageLost->Dropoff_Address_Line_2 = $packageInbound->Dropoff_Address_Line_2;
                $packageLost->Dropoff_City = $packageInbound->Dropoff_City;
                $packageLost->Dropoff_Province = $packageInbound->Dropoff_Province;
                $packageLost->Dropoff_Postal_Code = $packageInbound->Dropoff_Postal_Code;
                $packageLost->Notes = $packageInbound->Notes;
                $packageLost->Route = $packageInbound->Route;
                $packageLost->comment = $request->get('comment') ? $request->get('comment') : 'Lost from SyncFreight';
                $packageLost->Weight = $packageInbound->Weight;
                $packageLost->idUser = "";
                $packageLost->created_at = date('Y-m-d H:i:s');
                $packageLost->updated_at = date('Y-m-d H:i:s');
                $packageLost->status = 'Lost';

                $packageLost->save();

                $packageHistory = new PackageHistory();

                $packageHistory->id = uniqid();
                $packageHistory->Reference_Number_1 = $packageInbound->Reference_Number_1;
                $packageHistory->idCompany = $packageInbound->idCompany;
                $packageHistory->company = $packageInbound->company;
                $packageHistory->Dropoff_Contact_Name = $packageInbound->Dropoff_Contact_Name;
                $packageHistory->Dropoff_Company = $packageInbound->Dropoff_Company;
                $packageHistory->Dropoff_Contact_Phone_Number = $packageInbound->Dropoff_Contact_Phone_Number;
                $packageHistory->Dropoff_Contact_Email = $packageInbound->Dropoff_Contact_Email;
                $packageHistory->Dropoff_Address_Line_1 = $packageInbound->Dropoff_Address_Line_1;
                $packageHistory->Dropoff_Address_Line_2 = $packageInbound->Dropoff_Address_Line_2;
                $packageHistory->Dropoff_City = $packageInbound->Dropoff_City;
                $packageHistory->Dropoff_Province = $packageInbound->Dropoff_Province;
                $packageHistory->Dropoff_Postal_Code = $packageInbound->Dropoff_Postal_Code;
                $packageHistory->Notes = $packageInbound->Notes;
                $idTeam = $packageInbound->idTeam;
                if ($idTeam) {
                    $packageHistory->idTeam = $packageInbound->idTeam;
                }
                $packageHistory->Weight = $packageInbound->Weight;
                $packageHistory->Route = $packageInbound->Route;
                $packageHistory->idUser = "";
                $packageHistory->Date_Inbound = date('Y-m-d H:s:i');
                $packageHistory->Description = $request->get('comment') ? $request->get('comment') : 'Lost from SyncFreight';
                $packageHistory->status = 'Lost';
                $packageHistory->actualDate = date('Y-m-d H:i:s');
                $packageHistory->created_at = date('Y-m-d H:i:s');
                $packageHistory->updated_at = date('Y-m-d H:i:s');

                $packageHistory->save();

                $packageController = new PackageController();
                if($replicationChildOrgName  != "FALCON EXPRESS" && $replicationChildOrgName != "Brooks Courier"){
                $packageController->SendStatusToInland($packageInbound, 'Lost', null, date('Y-m-d H:i:s'));

                $packageHistory = PackageHistory::where('Reference_Number_1', $packageInbound->Reference_Number_1)
                    ->where('sendToInland', 1)
                    ->where('status', 'Manifest')
                    ->first();

                if ($packageHistory) {
                    $packageController->SendStatusToOtherCompany($packageInbound, 'Lost', null, date('Y-m-d H:i:s'));
                }
                }
                $package = $packageInbound;
                $packageInbound->delete();

                $toDeductLostPackages = ToDeductLostPackages::find($packageInbound->Reference_Number_1);

                if (!$toDeductLostPackages) {
                    $toDeductLostPackages = new ToDeductLostPackages();
                    $toDeductLostPackages->shipmentId = $packageInbound->Reference_Number_1;
                    $toDeductLostPackages->idTeam = $packageInbound->status == 'Dispatch' || $packageInbound->status == 'Delivery' ? $packageInbound->idTeam : null;

                    if ($packageInbound->company != 'EIGHTVAPE')
                        $toDeductLostPackages->priceToDeduct = 50;

                    $toDeductLostPackages->save();
                }

                Log::info($idTeampackage);
                Log::info($packageInbound);
                if (!$idTeam) {
                    Log::info('IdTeam was not found');
                    $orgId = env('SYNC_ORG_ID');
                    Log::info($orgId);
                } else {
                    $team = User::where('id', $idTeam)->first();
                    $orgId = $team->orgId;
                    Log::info($orgId);
                }

                DB::commit();

                return ['stateAction' => true, 'packageInbound' => $package];
            } catch (Exception $e) {
                DB::rollback();
                Log::error('Error: ' . $e->getMessage());

                return ['stateAction' => false];
            }
        }

    public function UpdatePhotos(Request $request, $apiKey)
    {
        $Reference_Number_1 = $request['barcode'];
        $photoUrl           = $request['pictures'];

        $company = Company::where('id', 1)
                            ->where('key_api', $apiKey)
                            ->first();

        if($company)
        {
            $packageHistory = PackageHistory::where('Reference_Number_1', $Reference_Number_1)->get();

            if(count($packageHistory) == 0)
                return response()->json(['message' => 'success'], 200);

            $packageDispatch = PackageDispatch::find($Reference_Number_1);

            if($packageDispatch)
            {
                $packageDispatch->photoUrl = $photoUrl;
                $packageDispatch->save();

                DB::commit();

                return response()->json([
                    'package_id' => $Reference_Number_1,
                    'message' => "Package photos were updated."
                ], 200);
            }
            else
            {
                return response()->json([
                    'package_id' => $Reference_Number_1,
                    'message' => "The package is not in DISPATCH or DELIVERY."
                ], 400);
            }
        }
        else
        {
            return response()->json(['message' => "Authentication Failed: incorrect api-key"], 401);
        }
    }

    /*
        * Actualiza los status de los packages Dispatch de synctruck, que manda la PODApp y  que fueron asignados a un driver
        * @parametro: apiKey
        * @request: request (información que manda la PODApp)
        * formato: usar modelo solicitado
    */
    public function UpdateStatus(Request $request, $apiKey)
    {
        $company = Company::where('id', 1)
                            ->where('key_api', $apiKey)
                            ->first();

        if($company)
        {
            $validator = Validator::make($request->all(),

                [
                    "package_id" => ["required", "min:4", "max:40"],
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

                        $messageResponse = 'Correct: updated status to DELIVERY';
                    }
                    else
                    {
                        $this->TaskFailed($request, $Date_Delivery);

                        $messageResponse = 'Correct: updated status to FAILED';
                    }

                    DB::commit();

                    return response()->json(['message' => $messageResponse], 200);
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
        $Description        = 'PODApp: Delivery';

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

    public function InsertPreRts(Request $request, $apiKey)
    {
        $Reference_Number_1 = $request->get('barcode');
        $created_at = $request->get('createdAt');
        $replicationChildOrgName    = $request['replicationChildOrgName'];
        $packageBlocked = PackageBlocked::where('Reference_Number_1', $Reference_Number_1)->first();

    if ($packageBlocked) {
        return ['stateAction' => 'validatedFilterPackage', 'packageBlocked' => $packageBlocked, 'packageManifest' => null];
    }

    $packageInbound = PackageManifest::find($Reference_Number_1)
        ?: PackageInbound::find($Reference_Number_1)
        ?: PackageWarehouse::find($Reference_Number_1)
        ?: PackagePreDispatch::find($Reference_Number_1)
        ?: PackageDispatch::find($Reference_Number_1)
        ?: PackageFailed::find($Reference_Number_1)
        ?: PackageReturnCompany::find($Reference_Number_1)
        ?: PackageLost::find($Reference_Number_1);

    if (!$packageInbound) {
        return ['stateAction' => 'notExists'];
    }

    if ($packageInbound instanceof PackagePreDispatch) {
        return ['stateAction' => 'validatedPreDispatch'];
    }

    if ($packageInbound instanceof PackageReturnCompany) {
        return ['stateAction' => 'validatedReturnCompany'];
    }

    if ($packageInbound instanceof PackageLost) {
        return ['stateAction' => 'validatedLost'];
    }

    $idTeampackage = PackageHistory::where('Reference_Number_1', $packageInbound->Reference_Number_1)
        ->where('status', $packageInbound->status)
        ->get();

    Log::info('Package From History');
    Log::info($idTeampackage);

    try {
        DB::beginTransaction();

        $packageRts = new PackageReturnCompany();

        $packageRts->Reference_Number_1 = $packageInbound->Reference_Number_1;
        $packageRts->idCompany = $packageInbound->idCompany;
        $packageRts->company = $packageInbound->company;
        $packageRts->Dropoff_Contact_Name = $packageInbound->Dropoff_Contact_Name;
        $packageRts->Dropoff_Company = $packageInbound->Dropoff_Company;
        $packageRts->Dropoff_Contact_Phone_Number = $packageInbound->Dropoff_Contact_Phone_Number;
        $packageRts->Dropoff_Contact_Email = $packageInbound->Dropoff_Contact_Email;
        $packageRts->Dropoff_Address_Line_1 = $packageInbound->Dropoff_Address_Line_1;
        $packageRts->Dropoff_Address_Line_2 = $packageInbound->Dropoff_Address_Line_2;
        $packageRts->Dropoff_City = $packageInbound->Dropoff_City;
        $packageRts->Dropoff_Province = $packageInbound->Dropoff_Province;
        $packageRts->Dropoff_Postal_Code = $packageInbound->Dropoff_Postal_Code;
        $packageRts->Notes = $packageInbound->Notes;
        $packageRts->Route = $packageInbound->Route;
        $packageRts->Weight = $packageInbound->Weight;
        $packageRts->idUser = "";
        $packageRts->created_at = date('Y-m-d H:i:s');
        $packageRts->updated_at = date('Y-m-d H:i:s');
        $packageRts->status = 'PreRts';

        $packageRts->save();

        $packageHistory = new PackageHistory();

        $packageHistory->id = uniqid();
        $packageHistory->Reference_Number_1 = $packageInbound->Reference_Number_1;
        $packageHistory->idCompany = $packageInbound->idCompany;
        $packageHistory->company = $packageInbound->company;
        $packageHistory->Dropoff_Contact_Name = $packageInbound->Dropoff_Contact_Name;
        $packageHistory->Dropoff_Company = $packageInbound->Dropoff_Company;
        $packageHistory->Dropoff_Contact_Phone_Number = $packageInbound->Dropoff_Contact_Phone_Number;
        $packageHistory->Dropoff_Contact_Email = $packageInbound->Dropoff_Contact_Email;
        $packageHistory->Dropoff_Address_Line_1 = $packageInbound->Dropoff_Address_Line_1;
        $packageHistory->Dropoff_Address_Line_2 = $packageInbound->Dropoff_Address_Line_2;
        $packageHistory->Dropoff_City = $packageInbound->Dropoff_City;
        $packageHistory->Dropoff_Province = $packageInbound->Dropoff_Province;
        $packageHistory->Dropoff_Postal_Code = $packageInbound->Dropoff_Postal_Code;
        $packageHistory->Notes = $packageInbound->Notes;
        $idTeam = $packageInbound->idTeam;
        if ($idTeam) {
            $packageHistory->idTeam = $packageInbound->idTeam;
        }
        $packageHistory->Weight = $packageInbound->Weight;
        $packageHistory->Route = $packageInbound->Route;
        $packageHistory->idUser = "";
        $packageHistory->Date_Inbound = date('Y-m-d H:s:i');
        $packageHistory->Description = $request->get('comment') ? $request->get('comment') : 'RTS from SyncFreight';
        $packageHistory->status = 'PreRts';
        $packageHistory->actualDate = date('Y-m-d H:i:s');
        $packageHistory->created_at = date('Y-m-d H:i:s');
        $packageHistory->updated_at = date('Y-m-d H:i:s');

        $packageHistory->save();

        $packageController = new PackageController();
        if($replicationChildOrgName  != "FALCON EXPRESS" && $replicationChildOrgName != "Brooks Courier"){
        $packageController->SendStatusToInland($packageInbound, 'ReturnCompany', 'scan_out_for_return', date('Y-m-d H:i:s'));

        $packageHistory = PackageHistory::where('Reference_Number_1', $packageInbound->Reference_Number_1)
            ->where('sendToInland', 1)
            ->where('status', 'Manifest')
            ->first();

        if ($packageHistory) {
            $packageController->SendStatusToOtherCompany($packageInbound, 'ReturnCompany', 'scan_out_for_return', date('Y-m-d H:i:s'));
        }
        }
        $package = $packageInbound;
        $packageInbound->delete();

        $toDeductLostPackages = ToDeductLostPackages::find($packageInbound->Reference_Number_1);

        if (!$toDeductLostPackages) {
            $toDeductLostPackages = new ToDeductLostPackages();
            $toDeductLostPackages->shipmentId = $packageInbound->Reference_Number_1;
            $toDeductLostPackages->idTeam = $packageInbound->status == 'Dispatch' || $packageInbound->status == 'Delivery' ? $packageInbound->idTeam : null;

            if ($packageInbound->company != 'EIGHTVAPE')
                $toDeductLostPackages->priceToDeduct = 50;

            $toDeductLostPackages->save();
        }

        Log::info($idTeampackage);
        Log::info($packageInbound);
        if (!$idTeam) {
            Log::info('IdTeam was not found');
            $orgId = env('SYNC_ORG_ID');
            Log::info($orgId);
        } else {
            $team = User::where('id', $idTeam)->first();
            $orgId = $team->orgId;
            Log::info($orgId);
        }

        DB::commit();

        return ['stateAction' => true, 'packageInbound' => $package];
    } catch (Exception $e) {
        DB::rollback();
        Log::error('Error: ' . $e->getMessage());

        return ['stateAction' => false];
    }
}

public function InsertRts(Request $request, $apiKey)
{
    $Reference_Number_1 = $request->get('barcode');
    $created_at = $request->get('createdAt');
    $replicationChildOrgName    = $request['replicationChildOrgName'];
    $packageBlocked = PackageBlocked::where('Reference_Number_1', $Reference_Number_1)->first();

    if ($packageBlocked) {
        return ['stateAction' => 'validatedFilterPackage', 'packageBlocked' => $packageBlocked, 'packageManifest' => null];
    }

    $packageInbound = PackageManifest::find($Reference_Number_1)
        ?: PackageInbound::find($Reference_Number_1)
        ?: PackageWarehouse::find($Reference_Number_1)
        ?: PackagePreDispatch::find($Reference_Number_1)
        ?: PackageDispatch::find($Reference_Number_1)
        ?: PackageFailed::find($Reference_Number_1)
        ?: PackageReturnCompany::find($Reference_Number_1)
        ?: PackageLost::find($Reference_Number_1);

    if (!$packageInbound) {
        return ['stateAction' => 'notExists'];
    }

    if ($packageInbound instanceof PackagePreDispatch) {
        return ['stateAction' => 'validatedPreDispatch'];
    }

    if ($packageInbound instanceof PackageReturnCompany) {
        // Si el paquete ya existe en PackageReturnCompany, actualizamos su estado
        $packageInbound->status = 'ReturnCompany';
        $packageInbound->save();

        // Crear un nuevo registro en PackageHistory
        $packageHistory = new PackageHistory();
        $packageHistory->id = uniqid();
        $packageHistory->Reference_Number_1 = $packageInbound->Reference_Number_1;
        $packageHistory->idCompany = $packageInbound->idCompany;
        $packageHistory->company = $packageInbound->company;
        $packageHistory->Dropoff_Contact_Name = $packageInbound->Dropoff_Contact_Name;
        $packageHistory->Dropoff_Company = $packageInbound->Dropoff_Company;
        $packageHistory->Dropoff_Contact_Phone_Number = $packageInbound->Dropoff_Contact_Phone_Number;
        $packageHistory->Dropoff_Contact_Email = $packageInbound->Dropoff_Contact_Email;
        $packageHistory->Dropoff_Address_Line_1 = $packageInbound->Dropoff_Address_Line_1;
        $packageHistory->Dropoff_Address_Line_2 = $packageInbound->Dropoff_Address_Line_2;
        $packageHistory->Dropoff_City = $packageInbound->Dropoff_City;
        $packageHistory->Dropoff_Province = $packageInbound->Dropoff_Province;
        $packageHistory->Dropoff_Postal_Code = $packageInbound->Dropoff_Postal_Code;
        $packageHistory->Notes = $packageInbound->Notes;
        $packageHistory->Weight = $packageInbound->Weight;
        $packageHistory->Route = $packageInbound->Route;
        $packageHistory->idUser = "";
        $packageHistory->Date_Inbound = date('Y-m-d H:s:i');
        $packageHistory->Description = $request->get('comment') ? $request->get('comment') : 'RTS from SyncFreight';
        $packageHistory->status = 'ReturnCompany';
        $packageHistory->actualDate = date('Y-m-d H:i:s');
        $packageHistory->created_at = date('Y-m-d H:i:s');
        $packageHistory->updated_at = date('Y-m-d H:i:s');

        $packageHistory->save();

        return ['stateAction' => 'validatedReturnCompany'];
    }

    if ($packageInbound instanceof PackageLost) {
        return ['stateAction' => 'validatedLost'];
    }

    $idTeampackage = PackageHistory::where('Reference_Number_1', $packageInbound->Reference_Number_1)
        ->where('status', $packageInbound->status)
        ->get();

    Log::info('Package From History');
    Log::info($idTeampackage);

    try {
        DB::beginTransaction();

        $packageRts = new PackageReturnCompany();

        $packageRts->Reference_Number_1 = $packageInbound->Reference_Number_1;
        $packageRts->idCompany = $packageInbound->idCompany;
        $packageRts->company = $packageInbound->company;
        $packageRts->Dropoff_Contact_Name = $packageInbound->Dropoff_Contact_Name;
        $packageRts->Dropoff_Company = $packageInbound->Dropoff_Company;
        $packageRts->Dropoff_Contact_Phone_Number = $packageInbound->Dropoff_Contact_Phone_Number;
        $packageRts->Dropoff_Contact_Email = $packageInbound->Dropoff_Contact_Email;
        $packageRts->Dropoff_Address_Line_1 = $packageInbound->Dropoff_Address_Line_1;
        $packageRts->Dropoff_Address_Line_2 = $packageInbound->Dropoff_Address_Line_2;
        $packageRts->Dropoff_City = $packageInbound->Dropoff_City;
        $packageRts->Dropoff_Province = $packageInbound->Dropoff_Province;
        $packageRts->Dropoff_Postal_Code = $packageInbound->Dropoff_Postal_Code;
        $packageRts->Notes = $packageInbound->Notes;
        $packageRts->Route = $packageInbound->Route;
        $packageRts->Weight = $packageInbound->Weight;
        $packageRts->idUser = "";
        $packageRts->created_at = date('Y-m-d H:i:s');
        $packageRts->updated_at = date('Y-m-d H:i:s');
        $packageRts->status = 'ReturnCompany';

        $packageRts->save();

        $packageHistory = new PackageHistory();

        $packageHistory->id = uniqid();
        $packageHistory->Reference_Number_1 = $packageInbound->Reference_Number_1;
        $packageHistory->idCompany = $packageInbound->idCompany;
        $packageHistory->company = $packageInbound->company;
        $packageHistory->Dropoff_Contact_Name = $packageInbound->Dropoff_Contact_Name;
        $packageHistory->Dropoff_Company = $packageInbound->Dropoff_Company;
        $packageHistory->Dropoff_Contact_Phone_Number = $packageInbound->Dropoff_Contact_Phone_Number;
        $packageHistory->Dropoff_Contact_Email = $packageInbound->Dropoff_Contact_Email;
        $packageHistory->Dropoff_Address_Line_1 = $packageInbound->Dropoff_Address_Line_1;
        $packageHistory->Dropoff_Address_Line_2 = $packageInbound->Dropoff_Address_Line_2;
        $packageHistory->Dropoff_City = $packageInbound->Dropoff_City;
        $packageHistory->Dropoff_Province = $packageInbound->Dropoff_Province;
        $packageHistory->Dropoff_Postal_Code = $packageInbound->Dropoff_Postal_Code;
        $packageHistory->Notes = $packageInbound->Notes;
        $idTeam = $packageInbound->idTeam;
        if ($idTeam) {
            $packageHistory->idTeam = $packageInbound->idTeam;
        }
        $packageHistory->Weight = $packageInbound->Weight;
        $packageHistory->Route = $packageInbound->Route;
        $packageHistory->idUser = "";
        $packageHistory->Date_Inbound = date('Y-m-d H:s:i');
        $packageHistory->Description = $request->get('comment') ? $request->get('comment') : 'RTS from SyncFreight';
        $packageHistory->status = 'ReturnCompany';
        $packageHistory->actualDate = date('Y-m-d H:i:s');
        $packageHistory->created_at = date('Y-m-d H:i:s');
        $packageHistory->updated_at = date('Y-m-d H:i:s');

        $packageHistory->save();

        $packageController = new PackageController();
        if($replicationChildOrgName  != "FALCON EXPRESS" && $replicationChildOrgName != "Brooks Courier"){
        $packageController->SendStatusToInland($packageInbound, 'ReturnCompany', 'scan_in_for_return', date('Y-m-d H:i:s'));

        $packageHistory = PackageHistory::where('Reference_Number_1', $packageInbound->Reference_Number_1)
            ->where('sendToInland', 1)
            ->where('status', 'Manifest')
            ->first();

        if ($packageHistory) {
            $packageController->SendStatusToOtherCompany($packageInbound, 'ReturnCompany', 'scan_in_for_return', date('Y-m-d H:i:s'));
        }
        }
        $package = $packageInbound;
        $packageInbound->delete();

        $toDeductLostPackages = ToDeductLostPackages::find($packageInbound->Reference_Number_1);

        if (!$toDeductLostPackages) {
            $toDeductLostPackages = new ToDeductLostPackages();
            $toDeductLostPackages->shipmentId = $packageInbound->Reference_Number_1;
            $toDeductLostPackages->idTeam = $packageInbound->status == 'Dispatch' || $packageInbound->status == 'Delivery' ? $packageInbound->idTeam : null;

            if ($packageInbound->company != 'EIGHTVAPE')
                $toDeductLostPackages->priceToDeduct = 50;

            $toDeductLostPackages->save();
        }

        Log::info($idTeampackage);
        Log::info($packageInbound);
        if (!$idTeam) {
            Log::info('IdTeam was not found');
            $orgId = env('SYNC_ORG_ID');
            Log::info($orgId);
        } else {
            $team = User::where('id', $idTeam)->first();
            $orgId = $team->orgId;
            Log::info($orgId);
        }

        DB::commit();

        return ['stateAction' => true, 'packageInbound' => $package];
    } catch (Exception $e) {
        DB::rollback();
        Log::error('Error: ' . $e->getMessage());

        return ['stateAction' => false];
    }
}

    public function TaskFailed(Request $request, $Date_Failed)
    {
        $Reference_Number_1  = $request['package_id'];
        $created_at          = date('Y-m-d H:i:s');
        $status              = $request['status'];
        $photoUrl            = $request['pod_url'];
        $Description_Onfleet = $request['failure_notes'];

        $packageDispatch = PackageDispatch::find($Reference_Number_1);

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
