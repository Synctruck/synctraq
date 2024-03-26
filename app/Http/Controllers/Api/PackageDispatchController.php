<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\{ Company, PackageDispatch, PackageFailed, PackageInbound, PackageHistory, PackageManifest, PackageWarehouse, User };

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

    public function UpdateStatusFromSyncweb(Request $request, $apiKey)
    {
        $validator = Validator::make($request->all(),
            [
                "barcode" => ["required"],
                "status" => ["required", Rule::in(['delivered', 'failed'])],
                "createdAt" => ["required", "date"],
            ],
        );

        if($validator->fails())
        {
            return response()->json(["errors" => $validator->errors()], 422);
        }

        if($request['status'] == 'delivered'){
            return $this->InsertDelivery($request, $apiKey);
        }
        else if($request['status'] == 'failed'){
            return $this->InsertFailed($request, $apiKey);
        }
    }

    public function InsertDelivery(Request $request, $apiKey)
    {
        $Reference_Number_1 = $request['barcode'];

        if(!$request['pictures']){
        $photoUrl  = $request['pictures'];
        }
        $latitude           = $request['latitude'];
        $longitude          = $request['longitude'];
        $created_at         = date('Y-m-d H:i:s', strtotime($request['createdAt']));

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

                if($packageDispatch)
                {
                    if(count($photoUrl) == 1)
                        $photoUrl = $photoUrl[0];
                    else
                        $photoUrl = $photoUrl[0] .','. $photoUrl[1];

                    $packageDispatch->photoUrl      = $photoUrl;
                    $packageDispatch->arrivalLonLat = $longitude .','. $latitude;
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
        $created_at         = date('Y-m-d H:i:s', strtotime($request['createdAt']));

        try
        {
            DB::beginTransaction();

            $company = Company::where('id', 1)
                            ->where('key_api', $apiKey)
                            ->first();

            if($company)
            {
                $packageFailed = PackageFailed::where('status', 'Failed')->find($Reference_Number_1);

                if($packageFailed){
                    return response()->json([
                        'package_id' => $Reference_Number_1,
                        'message' => "Ok."
                    ], 200);
                }

                $packageDispatch = PackageDispatch::where('status', 'Dispatch')->find($Reference_Number_1);

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

                    if($packageDispatch->idCompany == 1)
                        $packageController->SendStatusToInland($packageDispatch, 'Failed', null, $created_at);

                    $packageHistory = PackageHistory::where('Reference_Number_1', $packageDispatch->Reference_Number_1)
                                                ->where('sendToInland', 1)
                                                ->where('status', 'Manifest')
                                                ->first();

                    if($packageHistory)
                        $packageController->SendStatusToOtherCompany($packageDispatch, 'Failed', null, $created_at);

                    $packageDispatch->delete();

                    DB::commit();

                    return response()->json([
                        'package_id' => $request['package_id'],
                        'message' => "Shipment has been received."
                    ], 200);
                }
                else
                {
                    return response()->json([
                        'package_id' => $request['package_id'],
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

    public function UpdatePhotos(Request $request, $apiKey)
    {
        $Reference_Number_1 = $request['barcode'];
        $photoUrl           = $request['pictures'];

        $company = Company::where('id', 1)
                            ->where('key_api', $apiKey)
                            ->first();

        if($company)
        {
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
