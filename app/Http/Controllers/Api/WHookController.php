<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{Configuration, Driver, Package, PackageDelivery, PackageDispatch, PackageHistory, PackageInbound, PackageManifest, PackageNotExists, PackageReturn, TeamRoute, User};

use DB;
use Log;

class WHookController extends Controller
{
    public function Index(Request $request)
    {   
        return response($request->check, 200)
            ->header('Content-Type', 'text/plain');
    }

    public function UpdateStatusOnfleet(Request $request)
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
                            $description = 'Delivery - for: Team 1 to '. $user->nameTeam .' / '. $user->name .' '. $user->nameOfOwner;
                        }
                        else
                        {
                            $description = 'Delivery - for: Team 1 to '. $user->name;
                        }
                    }
                    else
                    {
                        $description = 'Delivery - for: Not exist Team';
                    }

                    $packageHistory = new PackageHistory();

                    $packageHistory->id                           = uniqid();
                    $packageHistory->Reference_Number_1           = $packageDispatch->Reference_Number_1;
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
                }
            }

            DB::commit();


        }
        catch(Exception $e)
        {
            DB::rollback();

        }
    }

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
                $packageHistory->idUserDispatch               = $packageDispatch->idUserDispatch;
                $packageHistory->idUser                       = $user->id;
                $packageHistory->Description_Onfleet          = $Description_Onfleet;
                $packageHistory->status                       = 'Failed';

                $packageHistory->save();

                Log::info("==================== CORRECT TASK FAILED");
            }
        }
    }
}