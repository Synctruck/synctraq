<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{Configuration, Driver, Package, PackageDelivery, PackageDispatch, PackageHistory, PackageInbound, PackageManifest, PackageNotExists, PackageReturn, PackageWarehouse, TeamRoute, User};

use App\Http\Controllers\PackageDispatchController;

use App\Http\Controllers\Api\PackageController;

use DB;
use Log;

class WHookController extends Controller
{
    public function EndPointTaskCompleted(Request $request)
    {   
        return response($request->check, 200)
            ->header('Content-Type', 'text/plain');
    }

    public function TaskCompleted(Request $request)
    {
        try
        {
            DB::beginTransaction();

            $Reference_Number_1      = $request['data']['task']['notes'];
            $completionDetailsStatus = $request['data']['task']['completionDetails']['success'];
            $Date_Delivery           = $request['data']['task']['completionDetails']['time'];
            $photoUploadIds          = $request['data']['task']['completionDetails']['unavailableAttachments'];

            Log::info("==== TASK COMPLETED");
            Log::info($request['data']['task']['notes']);

            if($completionDetailsStatus == true)
            {
                $packageDispatch = PackageDispatch::where('status', 'Dispatch')->find($Reference_Number_1);

                if($packageDispatch)
                {
                    $user = User::find($packageDispatch->idUserDispatch);

                    if($user)
                    {
                        if($user->nameTeam)
                        {
                            $description = 'For: Team 1 to '. $user->nameTeam .' / '. $user->name .' '. $user->nameOfOwner;
                        }
                        else
                        {
                            $description = 'For: Team 1 to '. $user->name;
                        }
                    }
                    else
                    {
                        $description = 'For: Not exist Team';
                    }

                    $packageHistory = new PackageHistory();

                    $packageHistory->id                           = uniqid();
                    $packageHistory->Reference_Number_1           = $packageDispatch->Reference_Number_1;
                    $packageHistory->idCompany                    = $packageDispatch->idCompany;
                    $packageHistory->company                      = $packageDispatch->company;
                    $packageHistory->Reference_Number_2           = $packageDispatch->Reference_Number_2;
                    $packageHistory->Reference_Number_3           = $packageDispatch->Reference_Number_3;
                    $packageHistory->Ready_At                     = $packageDispatch->Ready_At;
                    $packageHistory->Del_Date                     = $packageDispatch->Del_Date;
                    $packageHistory->Del_no_earlier_than          = $packageDispatch->Del_no_earlier_than;
                    $packageHistory->Del_no_later_than            = $packageDispatch->Del_no_later_than;
                    $packageHistory->Pickup_Contact_Name          = $packageDispatch->Pickup_Contact_Name;
                    $packageHistory->Pickup_Company               = $packageDispatch->Pickup_Company;
                    $packageHistory->Pickup_Contact_Phone_Number  = $packageDispatch->Pickup_Contact_Phone_Number;
                    $packageHistory->Pickup_Contact_Email         = $packageDispatch->Pickup_Contact_Email;
                    $packageHistory->Pickup_Address_Line_1        = $packageDispatch->Pickup_Address_Line_1;
                    $packageHistory->Pickup_Address_Line_2        = $packageDispatch->Pickup_Address_Line_2;
                    $packageHistory->Pickup_City                  = $packageDispatch->Pickup_City;
                    $packageHistory->Pickup_Province              = $packageDispatch->Pickup_Province;
                    $packageHistory->Pickup_Postal_Code           = $packageDispatch->Pickup_Postal_Code;
                    $packageHistory->Dropoff_Contact_Name         = $packageDispatch->Dropoff_Contact_Name;
                    $packageHistory->Dropoff_Company              = $packageDispatch->Dropoff_Company;
                    $packageHistory->Dropoff_Contact_Phone_Number = $packageDispatch->Dropoff_Contact_Phone_Number;
                    $packageHistory->Dropoff_Contact_Email        = $packageDispatch->Dropoff_Contact_Email;
                    $packageHistory->Dropoff_Address_Line_1       = $packageDispatch->Dropoff_Address_Line_1;
                    $packageHistory->Dropoff_Address_Line_2       = $packageDispatch->Dropoff_Address_Line_2;
                    $packageHistory->Dropoff_City                 = $packageDispatch->Dropoff_City;
                    $packageHistory->Dropoff_Province             = $packageDispatch->Dropoff_Province;
                    $packageHistory->Dropoff_Postal_Code          = $packageDispatch->Dropoff_Postal_Code;
                    $packageHistory->Service_Level                = $packageDispatch->Service_Level;
                    $packageHistory->Carrier_Name                 = $packageDispatch->Carrier_Name;
                    $packageHistory->Vehicle_Type_Id              = $packageDispatch->Vehicle_Type_Id;
                    $packageHistory->Notes                        = $packageDispatch->Notes;
                    $packageHistory->Number_Of_Pieces             = $packageDispatch->Number_Of_Pieces;
                    $packageHistory->Weight                       = $packageDispatch->Weight;
                    $packageHistory->Route                        = $packageDispatch->Route;
                    $packageHistory->Name                         = $packageDispatch->Name;
                    $packageHistory->idTeam                       = $packageDispatch->idTeam;
                    $packageHistory->idUserDispatch               = $packageDispatch->idUserDispatch;
                    $packageHistory->idUser                       = $packageDispatch->idUserDispatch;
                    $packageHistory->idUserDelivery               = $packageDispatch->idUserDispatch;
                    $packageHistory->Date_Delivery                = date('Y-m-d H:i:s', $Date_Delivery / 1000);
                    $packageHistory->Description                  = $description;
                    $packageHistory->status                       = 'Delivery';

                    $packageHistory->save();

                    $packageDispatch->taskDetails        = $packageDispatch->Reference_Number_1;
                    $packageDispatch->workerName         = $user->name .' '. $user->nameOfOwner;
                    $packageDispatch->destinationAddress = $packageDispatch->Dropoff_Address_Line_1;
                    $packageDispatch->recipientNotes     = $user->nameTeam;

                    $photoUrl = '';

                    foreach($photoUploadIds as $idPhoto)
                    {
                        $photoUrl = $photoUrl == '' ? $idPhoto['attachmentId'] : $photoUrl .','. $idPhoto['attachmentId'];
                    }

                    Log::info($photoUrl);

                    $packageDispatch->photoUrl      = $photoUrl;
                    $packageDispatch->Date_Delivery = date('Y-m-d H:i:s', $Date_Delivery / 1000);
                    $packageDispatch->status        = 'Delivery';

                    $packageDispatch->save();

                    //data for INLAND
                    $packageController = new PackageController();
                    $packageController->SendStatusToInland($packageDispatch, 'Delivery', explode(',', $photoUrl)[0]);
                    //end data for inland
                }
            }

            DB::commit();
        }
        catch(Exception $e)
        {
            DB::rollback();

        }
    }

    //WEBHOOK - TASK - FAILED
    public function EndPointTaskFailed(Request $request)
    {
        return response($request->check, 200)
            ->header('Content-Type', 'text/plain');
    }

    public function TaskFailed(Request $request)
    {
        $Reference_Number_1      = $request['data']['task']['notes'];
        $completionDetailsStatus = $request['data']['task']['completionDetails']['success'];
        $Description_Onfleet     = $request['data']['task']['completionDetails']['failureReason'] .': ['. $request['data']['task']['completionDetails']['failureNotes'] .', '. $request['data']['task']['completionDetails']['notes'] .']';

        Log::info('================================================');
        Log::info('============ START TASK FAILED ================');
        Log::info($Reference_Number_1);

        if($completionDetailsStatus == false)
        {
            $packageDispatch = PackageDispatch::where('status', 'Dispatch')->find($Reference_Number_1);

            $user = User::find($packageDispatch->idUserDispatch);

            if($user)
            {
                $description = 'For: Driver '. $user->name .' '. $user->nameOfOwner;

                $packageHistory = new PackageHistory();

                $packageHistory->id                           = uniqid();
                $packageHistory->Reference_Number_1           = $packageDispatch->Reference_Number_1;
                $packageHistory->idCompany                    = $packageDispatch->idCompany;
                $packageHistory->company                      = $packageDispatch->company;
                $packageHistory->Reference_Number_2           = $packageDispatch->Reference_Number_2;
                $packageHistory->Reference_Number_3           = $packageDispatch->Reference_Number_3;
                $packageHistory->Ready_At                     = $packageDispatch->Ready_At;
                $packageHistory->Del_Date                     = $packageDispatch->Del_Date;
                $packageHistory->Del_no_earlier_than          = $packageDispatch->Del_no_earlier_than;
                $packageHistory->Del_no_later_than            = $packageDispatch->Del_no_later_than;
                $packageHistory->Pickup_Contact_Name          = $packageDispatch->Pickup_Contact_Name;
                $packageHistory->Pickup_Company               = $packageDispatch->Pickup_Company;
                $packageHistory->Pickup_Contact_Phone_Number  = $packageDispatch->Pickup_Contact_Phone_Number;
                $packageHistory->Pickup_Contact_Email         = $packageDispatch->Pickup_Contact_Email;
                $packageHistory->Pickup_Address_Line_1        = $packageDispatch->Pickup_Address_Line_1;
                $packageHistory->Pickup_Address_Line_2        = $packageDispatch->Pickup_Address_Line_2;
                $packageHistory->Pickup_City                  = $packageDispatch->Pickup_City;
                $packageHistory->Pickup_Province              = $packageDispatch->Pickup_Province;
                $packageHistory->Pickup_Postal_Code           = $packageDispatch->Pickup_Postal_Code;
                $packageHistory->Dropoff_Contact_Name         = $packageDispatch->Dropoff_Contact_Name;
                $packageHistory->Dropoff_Company              = $packageDispatch->Dropoff_Company;
                $packageHistory->Dropoff_Contact_Phone_Number = $packageDispatch->Dropoff_Contact_Phone_Number;
                $packageHistory->Dropoff_Contact_Email        = $packageDispatch->Dropoff_Contact_Email;
                $packageHistory->Dropoff_Address_Line_1       = $packageDispatch->Dropoff_Address_Line_1;
                $packageHistory->Dropoff_Address_Line_2       = $packageDispatch->Dropoff_Address_Line_2;
                $packageHistory->Dropoff_City                 = $packageDispatch->Dropoff_City;
                $packageHistory->Dropoff_Province             = $packageDispatch->Dropoff_Province;
                $packageHistory->Dropoff_Postal_Code          = $packageDispatch->Dropoff_Postal_Code;
                $packageHistory->Service_Level                = $packageDispatch->Service_Level;
                $packageHistory->Carrier_Name                 = $packageDispatch->Carrier_Name;
                $packageHistory->Vehicle_Type_Id              = $packageDispatch->Vehicle_Type_Id;
                $packageHistory->Notes                        = $packageDispatch->Notes;
                $packageHistory->Number_Of_Pieces             = $packageDispatch->Number_Of_Pieces;
                $packageHistory->Weight                       = $packageDispatch->Weight;
                $packageHistory->Route                        = $packageDispatch->Route;
                $packageHistory->Name                         = $packageDispatch->Name;
                $packageHistory->idTeam                       = $packageDispatch->idTeam;
                $packageHistory->idUserDispatch               = $packageDispatch->idUserDispatch;
                $packageHistory->idUser                       = $user->id;
                $packageHistory->Description_Onfleet          = $Description_Onfleet;
                $packageHistory->status                       = 'Failed';

                $packageHistory->save();

                Log::info("==================== CORRECT TASK FAILED");
            }
        }
    }

    //WEBHOOK - TASK - CREATED
    public function EndPointTaskCreated(Request $request)
    {
        return response($request->check, 200)
            ->header('Content-Type', 'text/plain');
    }

    public function TaskCreated(Request $request)
    {
        Log::info('================================================');
        Log::info('============ START TASK CREATED ================');
        $userCreatorOnfleet = $request['actionContext']['type'];
        $idOnfleet          = $request['taskId'];
        $taskOnfleet        = $request['data']['task']['shortId'];
        $idWorkerOnfleet    = $request['data']['task']['worker'];
        $Reference_Number_1 = $request['data']['task']['notes'];

        Log::info("Reference_Number_1 :". $Reference_Number_1);

        $driver = User::where('idOnfleet', $idWorkerOnfleet)->first();
        $team   = null;

        if($driver)
        {
            $team = User::where('id', $driver->idTeam)->first();

            if(!$team)
            {
                Log::info('========= NO EXISTS TEAM - TASK CREATED');
            }
        }
        else
        {
            Log::info('========= NO EXISTS DRIVER - TASK CREATED');
        }

        if($driver && $team)
        {
            $package = PackageInbound::where('Reference_Number_1', $Reference_Number_1)->first();

            if(!$package)
            {
               $package = PackageManifest::where('Reference_Number_1', $Reference_Number_1)->first();
            }

            if(!$package)
            {
               $package = PackageWarehouse::where('Reference_Number_1', $Reference_Number_1)->first();
            }

            $packageDispatch = null;

            if(!$package)
            {
                $packageDispatch = PackageDispatch::where('Reference_Number_1', $Reference_Number_1)
                                            ->where('status', 'Dispatch')
                                            ->first();
            }

            $descriptionDispatch = 'For: Onfleet[ '. $userCreatorOnfleet .' ] to '. $team->name .' / '. $driver->name .' '. $driver->nameOfOwner;

            if($package && $packageDispatch == null)
            {
                try
                {
                    DB::beginTransaction();

                    $descriptionInbound = 'For: Onfleet[ '. $userCreatorOnfleet .' ] to '. $team->name;

                    if($package->status == 'On hold')
                    {
                        $packageHistory = new PackageHistory();

                        $packageHistory->id                           = uniqid();
                        $packageHistory->Reference_Number_1           = $package->Reference_Number_1;
                        $packageHistory->idCompany                    = $package->idCompany;
                        $packageHistory->company                      = $package->company;
                        $packageHistory->Reference_Number_2           = $package->Reference_Number_2;
                        $packageHistory->Reference_Number_3           = $package->Reference_Number_3;
                        $packageHistory->Ready_At                     = $package->Ready_At;
                        $packageHistory->Del_Date                     = $package->Del_Date;
                        $packageHistory->Del_no_earlier_than          = $package->Del_no_earlier_than;
                        $packageHistory->Del_no_later_than            = $package->Del_no_later_than;
                        $packageHistory->Pickup_Contact_Name          = $package->Pickup_Contact_Name;
                        $packageHistory->Pickup_Company               = $package->Pickup_Company;
                        $packageHistory->Pickup_Contact_Phone_Number  = $package->Pickup_Contact_Phone_Number;
                        $packageHistory->Pickup_Contact_Email         = $package->Pickup_Contact_Email;
                        $packageHistory->Pickup_Address_Line_1        = $package->Pickup_Address_Line_1;
                        $packageHistory->Pickup_Address_Line_2        = $package->Pickup_Address_Line_2;
                        $packageHistory->Pickup_City                  = $package->Pickup_City;
                        $packageHistory->Pickup_Province              = $package->Pickup_Province;
                        $packageHistory->Pickup_Postal_Code           = $package->Pickup_Postal_Code;
                        $packageHistory->Dropoff_Contact_Name         = $package->Dropoff_Contact_Name;
                        $packageHistory->Dropoff_Company              = $package->Dropoff_Company;
                        $packageHistory->Dropoff_Contact_Phone_Number = $package->Dropoff_Contact_Phone_Number;
                        $packageHistory->Dropoff_Contact_Email        = $package->Dropoff_Contact_Email;
                        $packageHistory->Dropoff_Address_Line_1       = $package->Dropoff_Address_Line_1;
                        $packageHistory->Dropoff_Address_Line_2       = $package->Dropoff_Address_Line_2;
                        $packageHistory->Dropoff_City                 = $package->Dropoff_City;
                        $packageHistory->Dropoff_Province             = $package->Dropoff_Province;
                        $packageHistory->Dropoff_Postal_Code          = $package->Dropoff_Postal_Code;
                        $packageHistory->Service_Level                = $package->Service_Level;
                        $packageHistory->Carrier_Name                 = $package->Carrier_Name;
                        $packageHistory->Vehicle_Type_Id              = $package->Vehicle_Type_Id;
                        $packageHistory->Notes                        = $package->Notes;
                        $packageHistory->Number_Of_Pieces             = $package->Number_Of_Pieces;
                        $packageHistory->Weight                       = $package->Weight;
                        $packageHistory->Route                        = $package->Route;
                        $packageHistory->Name                         = $package->Name;
                        $packageHistory->Description                  = $descriptionInbound;
                        $packageHistory->inbound                      = 1;
                        $packageHistory->status                       = 'Inbound';

                        $packageHistory->save();
                    }

                    $packageDispatch = new PackageDispatch();

                    $packageDispatch->Reference_Number_1           = $package->Reference_Number_1;
                    $packageDispatch->idCompany                    = $package->idCompany;
                    $packageDispatch->company                      = $package->company;
                    $packageDispatch->Reference_Number_2           = $package->Reference_Number_2;
                    $packageDispatch->Reference_Number_3           = $package->Reference_Number_3;
                    $packageDispatch->Ready_At                     = $package->Ready_At;
                    $packageDispatch->Del_Date                     = $package->Del_Date;
                    $packageDispatch->Del_no_earlier_than          = $package->Del_no_earlier_than;
                    $packageDispatch->Del_no_later_than            = $package->Del_no_later_than;
                    $packageDispatch->Pickup_Contact_Name          = $package->Pickup_Contact_Name;
                    $packageDispatch->Pickup_Company               = $package->Pickup_Company;
                    $packageDispatch->Pickup_Contact_Phone_Number  = $package->Pickup_Contact_Phone_Number;
                    $packageDispatch->Pickup_Contact_Email         = $package->Pickup_Contact_Email;
                    $packageDispatch->Pickup_Address_Line_1        = $package->Pickup_Address_Line_1;
                    $packageDispatch->Pickup_Address_Line_2        = $package->Pickup_Address_Line_2;
                    $packageDispatch->Pickup_City                  = $package->Pickup_City;
                    $packageDispatch->Pickup_Province              = $package->Pickup_Province;
                    $packageDispatch->Pickup_Postal_Code           = $package->Pickup_Postal_Code;
                    $packageDispatch->Dropoff_Contact_Name         = $package->Dropoff_Contact_Name;
                    $packageDispatch->Dropoff_Company              = $package->Dropoff_Company;
                    $packageDispatch->Dropoff_Contact_Phone_Number = $package->Dropoff_Contact_Phone_Number;
                    $packageDispatch->Dropoff_Contact_Email        = $package->Dropoff_Contact_Email;
                    $packageDispatch->Dropoff_Address_Line_1       = $package->Dropoff_Address_Line_1;
                    $packageDispatch->Dropoff_Address_Line_2       = $package->Dropoff_Address_Line_2;
                    $packageDispatch->Dropoff_City                 = $package->Dropoff_City;
                    $packageDispatch->Dropoff_Province             = $package->Dropoff_Province;
                    $packageDispatch->Dropoff_Postal_Code          = $package->Dropoff_Postal_Code;
                    $packageDispatch->Service_Level                = $package->Service_Level;
                    $packageDispatch->Carrier_Name                 = $package->Carrier_Name;
                    $packageDispatch->Vehicle_Type_Id              = $package->Vehicle_Type_Id;
                    $packageDispatch->Notes                        = $package->Notes;
                    $packageDispatch->Number_Of_Pieces             = $package->Number_Of_Pieces;
                    $packageDispatch->Weight                       = $package->Weight;
                    $packageDispatch->Route                        = $package->Route;
                    $packageDispatch->Name                         = $package->Name;
                    $packageDispatch->idUser                       = 64;
                    $packageDispatch->idTeam                       = $team->id;
                    $packageDispatch->idUserDispatch               = $driver->id;
                    $packageDispatch->Date_Dispatch                = date('Y-m-d H:i:s');
                    $packageDispatch->status                       = 'Dispatch';
                    $packageDispatch->idOnfleet                    = $idOnfleet;
                    $packageDispatch->taskOnfleet                  = $taskOnfleet;

                    $packageDispatch->save();

                    $packageHistory = new PackageHistory();

                    $packageHistory->id                           = uniqid();
                    $packageHistory->Reference_Number_1           = $package->Reference_Number_1;
                    $packageHistory->idCompany                    = $package->idCompany;
                    $packageHistory->company                      = $package->company;
                    $packageHistory->Reference_Number_2           = $package->Reference_Number_2;
                    $packageHistory->Reference_Number_3           = $package->Reference_Number_3;
                    $packageHistory->Ready_At                     = $package->Ready_At;
                    $packageHistory->Del_Date                     = $package->Del_Date;
                    $packageHistory->Del_no_earlier_than          = $package->Del_no_earlier_than;
                    $packageHistory->Del_no_later_than            = $package->Del_no_later_than;
                    $packageHistory->Pickup_Contact_Name          = $package->Pickup_Contact_Name;
                    $packageHistory->Pickup_Company               = $package->Pickup_Company;
                    $packageHistory->Pickup_Contact_Phone_Number  = $package->Pickup_Contact_Phone_Number;
                    $packageHistory->Pickup_Contact_Email         = $package->Pickup_Contact_Email;
                    $packageHistory->Pickup_Address_Line_1        = $package->Pickup_Address_Line_1;
                    $packageHistory->Pickup_Address_Line_2        = $package->Pickup_Address_Line_2;
                    $packageHistory->Pickup_City                  = $package->Pickup_City;
                    $packageHistory->Pickup_Province              = $package->Pickup_Province;
                    $packageHistory->Pickup_Postal_Code           = $package->Pickup_Postal_Code;
                    $packageHistory->Dropoff_Contact_Name         = $package->Dropoff_Contact_Name;
                    $packageHistory->Dropoff_Company              = $package->Dropoff_Company;
                    $packageHistory->Dropoff_Contact_Phone_Number = $package->Dropoff_Contact_Phone_Number;
                    $packageHistory->Dropoff_Contact_Email        = $package->Dropoff_Contact_Email;
                    $packageHistory->Dropoff_Address_Line_1       = $package->Dropoff_Address_Line_1;
                    $packageHistory->Dropoff_Address_Line_2       = $package->Dropoff_Address_Line_2;
                    $packageHistory->Dropoff_City                 = $package->Dropoff_City;
                    $packageHistory->Dropoff_Province             = $package->Dropoff_Province;
                    $packageHistory->Dropoff_Postal_Code          = $package->Dropoff_Postal_Code;
                    $packageHistory->Service_Level                = $package->Service_Level;
                    $packageHistory->Carrier_Name                 = $package->Carrier_Name;
                    $packageHistory->Vehicle_Type_Id              = $package->Vehicle_Type_Id;
                    $packageHistory->Notes                        = $package->Notes;
                    $packageHistory->Number_Of_Pieces             = $package->Number_Of_Pieces;
                    $packageHistory->Weight                       = $package->Weight;
                    $packageHistory->Route                        = $package->Route;
                    $packageHistory->Name                         = $package->Name;
                    $packageHistory->idUser                       = 64;
                    $packageHistory->idTeam                       = $team->id;
                    $packageHistory->idUserDispatch               = $driver->id;
                    $packageHistory->Date_Dispatch                = date('Y-m-d H:s:i');
                    $packageHistory->dispatch                     = 1;
                    $packageHistory->Description                  = $descriptionDispatch;
                    $packageHistory->status                       = 'Dispatch';

                    $packageHistory->save();

                    $package->delete();

                    DB::commit();

                    Log::info('==== COMPLETED TASK CREATED');
                }
                catch(Exception $e)
                {
                    Log::info('========= ERROR PROCESS - TASK CREATED');

                    $packageDispatch = new PackageDispatchController();
                    $packageDispatch->DeleteOnfleet($idOnfleet);

                    Log::info('========= DELETE - TASK CREATED');

                    DB::rollback();
                }
            }
            else
            {
                if($packageDispatch)
                {
                    Log::info('==== EJECUTION TWO TASK CREATED'); 
                }
                else
                {
                    $package = PackageDispatch::where('Reference_Number_1', $Reference_Number_1)
                                            ->where('status', 'Delete')
                                            ->first();

                    if($package)
                    {
                        try
                        {
                            DB::beginTransaction();

                            $packageHistory = new PackageHistory();

                            $packageHistory->id                           = uniqid();
                            $packageHistory->Reference_Number_1           = $package->Reference_Number_1;
                            $packageHistory->idCompany                    = $package->idCompany;
                            $packageHistory->company                      = $package->company;
                            $packageHistory->Reference_Number_2           = $package->Reference_Number_2;
                            $packageHistory->Reference_Number_3           = $package->Reference_Number_3;
                            $packageHistory->Ready_At                     = $package->Ready_At;
                            $packageHistory->Del_Date                     = $package->Del_Date;
                            $packageHistory->Del_no_earlier_than          = $package->Del_no_earlier_than;
                            $packageHistory->Del_no_later_than            = $package->Del_no_later_than;
                            $packageHistory->Pickup_Contact_Name          = $package->Pickup_Contact_Name;
                            $packageHistory->Pickup_Company               = $package->Pickup_Company;
                            $packageHistory->Pickup_Contact_Phone_Number  = $package->Pickup_Contact_Phone_Number;
                            $packageHistory->Pickup_Contact_Email         = $package->Pickup_Contact_Email;
                            $packageHistory->Pickup_Address_Line_1        = $package->Pickup_Address_Line_1;
                            $packageHistory->Pickup_Address_Line_2        = $package->Pickup_Address_Line_2;
                            $packageHistory->Pickup_City                  = $package->Pickup_City;
                            $packageHistory->Pickup_Province              = $package->Pickup_Province;
                            $packageHistory->Pickup_Postal_Code           = $package->Pickup_Postal_Code;
                            $packageHistory->Dropoff_Contact_Name         = $package->Dropoff_Contact_Name;
                            $packageHistory->Dropoff_Company              = $package->Dropoff_Company;
                            $packageHistory->Dropoff_Contact_Phone_Number = $package->Dropoff_Contact_Phone_Number;
                            $packageHistory->Dropoff_Contact_Email        = $package->Dropoff_Contact_Email;
                            $packageHistory->Dropoff_Address_Line_1       = $package->Dropoff_Address_Line_1;
                            $packageHistory->Dropoff_Address_Line_2       = $package->Dropoff_Address_Line_2;
                            $packageHistory->Dropoff_City                 = $package->Dropoff_City;
                            $packageHistory->Dropoff_Province             = $package->Dropoff_Province;
                            $packageHistory->Dropoff_Postal_Code          = $package->Dropoff_Postal_Code;
                            $packageHistory->Service_Level                = $package->Service_Level;
                            $packageHistory->Carrier_Name                 = $package->Carrier_Name;
                            $packageHistory->Vehicle_Type_Id              = $package->Vehicle_Type_Id;
                            $packageHistory->Notes                        = $package->Notes;
                            $packageHistory->Number_Of_Pieces             = $package->Number_Of_Pieces;
                            $packageHistory->Weight                       = $package->Weight;
                            $packageHistory->Route                        = $package->Route;
                            $packageHistory->Name                         = $package->Name;
                            $packageHistory->idUser                       = 64;
                            $packageHistory->idTeam                       = $team->id;
                            $packageHistory->idUserDispatch               = $driver->id;
                            $packageHistory->Date_Dispatch                = date('Y-m-d H:s:i');
                            $packageHistory->dispatch                     = 1;
                            $packageHistory->Description                  = $descriptionDispatch;
                            $packageHistory->status                       = 'Dispatch';

                            $packageHistory->save();
                            
                            $package->Date_Dispatch                = date('Y-m-d H:i:s');
                            $package->status                       = 'Dispatch';
                            $package->idOnfleet                    = $idOnfleet;
                            $package->taskOnfleet                  = $taskOnfleet;
                            $package->created_at                   = date('Y-m-d H:i:s');

                            $package->save();

                            $packageController = new PackageController();
                            $packageController->SendStatusToInland($package, 'Dispatch', null);

                            DB::commit();

                            Log::info('==== UPDATE TASK CREATED');
                        }
                        catch(Exception $e)
                        {
                            Log::info('========= ERROR PROCESS - TASK CREATED');

                            $packageDispatchController = new PackageDispatchController();
                            $packageDispatchController->DeleteOnfleet($idOnfleet);

                            Log::info('========= UPDATE - DELETE - TASK CREATED');

                            DB::rollback();
                        }
                    }
                    else
                    {
                        Log::info('========= NO EXISTS PACKAGE ID - TASK CREATED');

                        $packageDispatchController = new PackageDispatchController();
                        $packageDispatchController->DeleteOnfleet($idOnfleet);

                        Log::info('========= DELETE - TASK CREATED');
                    }
                }
            }
        }
        else
        {
            $packageDispatchController = new PackageDispatchController();
            $packageDispatchController->DeleteOnfleet($idOnfleet);

            Log::info('========= DELETE - TASK CREATED');
        }

        Log::info('============ END TASK CREATED ================');
        Log::info('==============================================');
    }

    //WEBHOOK - TASK - DELETE
    public function EndPointTaskDelete(Request $request)
    {
        return response($request->check, 200)
            ->header('Content-Type', 'text/plain');
    }

    public function TaskDelete(Request $request)
    {
        Log::info('================================================');
        Log::info('============ START TASK DELETE ================');

        $Reference_Number_1 = $request['data']['task']['notes'];
        $userCreatorOnfleet = $request['actionContext']['type'];

        $package = PackageDispatch::where('Reference_Number_1', $Reference_Number_1)->first();
        
        if($package)
        {
            try
            {
                DB::beginTransaction();

                $descriptionHistory = 'For: Onfleet[ '. $userCreatorOnfleet .' ]';

                $packageHistory = new PackageHistory();

                $packageHistory->id                           = uniqid();
                $packageHistory->Reference_Number_1           = $package->Reference_Number_1;
                $packageHistory->idCompany                    = $package->idCompany;
                $packageHistory->company                      = $package->company;
                $packageHistory->Reference_Number_2           = $package->Reference_Number_2;
                $packageHistory->Reference_Number_3           = $package->Reference_Number_3;
                $packageHistory->Ready_At                     = $package->Ready_At;
                $packageHistory->Del_Date                     = $package->Del_Date;
                $packageHistory->Del_no_earlier_than          = $package->Del_no_earlier_than;
                $packageHistory->Del_no_later_than            = $package->Del_no_later_than;
                $packageHistory->Pickup_Contact_Name          = $package->Pickup_Contact_Name;
                $packageHistory->Pickup_Company               = $package->Pickup_Company;
                $packageHistory->Pickup_Contact_Phone_Number  = $package->Pickup_Contact_Phone_Number;
                $packageHistory->Pickup_Contact_Email         = $package->Pickup_Contact_Email;
                $packageHistory->Pickup_Address_Line_1        = $package->Pickup_Address_Line_1;
                $packageHistory->Pickup_Address_Line_2        = $package->Pickup_Address_Line_2;
                $packageHistory->Pickup_City                  = $package->Pickup_City;
                $packageHistory->Pickup_Province              = $package->Pickup_Province;
                $packageHistory->Pickup_Postal_Code           = $package->Pickup_Postal_Code;
                $packageHistory->Dropoff_Contact_Name         = $package->Dropoff_Contact_Name;
                $packageHistory->Dropoff_Company              = $package->Dropoff_Company;
                $packageHistory->Dropoff_Contact_Phone_Number = $package->Dropoff_Contact_Phone_Number;
                $packageHistory->Dropoff_Contact_Email        = $package->Dropoff_Contact_Email;
                $packageHistory->Dropoff_Address_Line_1       = $package->Dropoff_Address_Line_1;
                $packageHistory->Dropoff_Address_Line_2       = $package->Dropoff_Address_Line_2;
                $packageHistory->Dropoff_City                 = $package->Dropoff_City;
                $packageHistory->Dropoff_Province             = $package->Dropoff_Province;
                $packageHistory->Dropoff_Postal_Code          = $package->Dropoff_Postal_Code;
                $packageHistory->Service_Level                = $package->Service_Level;
                $packageHistory->Carrier_Name                 = $package->Carrier_Name;
                $packageHistory->Vehicle_Type_Id              = $package->Vehicle_Type_Id;
                $packageHistory->Notes                        = $package->Notes;
                $packageHistory->Number_Of_Pieces             = $package->Number_Of_Pieces;
                $packageHistory->Weight                       = $package->Weight;
                $packageHistory->Route                        = $package->Route;
                $packageHistory->Name                         = $package->Name;
                $packageHistory->idUser                       = 64;
                $packageHistory->Description                  = $descriptionHistory;
                $packageHistory->status                       = 'Delete';

                $packageHistory->save();

                //update status Dispatch
                $package->status = 'Delete';

                $package->save();

                DB::commit();

                Log::info('============ END TASK DELETE ================');
                Log::info('==============================================');
            }
            catch(Exception $e)
            {
                Log::info('========= ERROR PROCESS - TASK CREATED');

                DB::rollback();
            }
        }
        else
        {
            Log::info('============ NO EXISTS PACKAGE ID - TASK DELETE ================');
            Log::info('==============================================');
        }
    }
}