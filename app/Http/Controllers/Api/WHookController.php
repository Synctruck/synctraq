<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{ AuxDispatchUser, Configuration, Driver, Package, PackageDelivery, PackageDispatch, PackageFailed, PackagePreFailed, PackageHistory, PackageInbound, PackageNeedMoreInformation, PackageManifest, PackageNotExists, PackageReturn, PackageWarehouse, PackageReturnCompany, PackagelmCarrier, PackageTerminal, PackageLost, PackageDispatchToMiddleMile, TeamRoute, User };

use App\Http\Controllers\{ PackageDispatchController, PackagePriceCompanyTeamController };

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

            $taskOnfleet             = $request['data']['task']['shortId'];
            $Reference_Number_1      = $request['data']['task']['notes'];
            $completionDetailsStatus = $request['data']['task']['completionDetails']['success'];
            $Date_Delivery           = $request['data']['task']['completionDetails']['time'];
            $photoUploadIds          = $request['data']['task']['completionDetails']['unavailableAttachments'];

            Log::info("==== TASK COMPLETED");
            Log::info("==== Reference_Number_1: ". $Reference_Number_1);

            if($completionDetailsStatus == true)
            {
                $packageDispatch = PackageDispatch::where('status', 'Dispatch')->find($Reference_Number_1);

                if($packageDispatch == null)
                {
                    $packageDispatch = PackageManifest::find($Reference_Number_1);
        
                    $packageDispatch = $packageDispatch != null ? $packageDispatch : PackageInbound::find($Reference_Number_1);
                    $packageDispatch = $packageDispatch != null ? $packageDispatch : PackageNeedMoreInformation::find($Reference_Number_1);
                    $packageDispatch = $packageDispatch != null ? $packageDispatch : PackageWarehouse::find($Reference_Number_1);
                    $packageDispatch = $packageDispatch != null ? $packageDispatch : PackageReturnCompany::find($Reference_Number_1);
                    $packageDispatch = $packageDispatch != null ? $packageDispatch : PackagelmCarrier::find($Reference_Number_1);
                    $packageDispatch = $packageDispatch != null ? $packageDispatch : PackageTerminal::find($Reference_Number_1);
                    $packageDispatch = $packageDispatch != null ? $packageDispatch : PackageLost::find($Reference_Number_1);
                    $packageDispatch = $packageDispatch != null ? $packageDispatch : PackageDispatchToMiddleMile::find($Reference_Number_1);
                }
                
                if($packageDispatch) 
                {
                    $user = User::find(($packageDispatch->status == 'Dispatch' ? $packageDispatch->idUserDispatch : null));
                    
                    if($user)
                    {
                        if($user->nameTeam)
                        {
                            $description = 'For: '. $user->nameTeam .' / '. $user->name .' '. $user->nameOfOwner;
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

                    $created_at = date('Y-m-d H:i:s');

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
                    $packageHistory->Date_Delivery                = date('Y-m-d H:i:s', $Date_Delivery / 1000);
                    $packageHistory->Description                  = $description;
                    $packageHistory->status                       = 'Delivery';
                    $packageHistory->actualDate                   = $created_at;
                    $packageHistory->created_at                   = $created_at;
                    $packageHistory->updated_at                   = $created_at;

                    $packageHistory->save();
 
                    if($packageDispatch->status != 'Dispatch' && $packageDispatch->status != 'Delivery')
                    {
                        $packageOther = $packageDispatch;

                        $packageDispatch = new PackageDispatch();

                        $packageDispatch->Reference_Number_1           = $packageOther->Reference_Number_1;
                        $packageDispatch->idCompany                    = $packageOther->idCompany;
                        $packageDispatch->company                      = $packageOther->company;
                        $packageDispatch->idStore                      = $packageOther->idStore;
                        $packageDispatch->store                        = $packageOther->store;
                        $packageDispatch->Dropoff_Contact_Name         = $packageOther->Dropoff_Contact_Name;
                        $packageDispatch->Dropoff_Company              = $packageOther->Dropoff_Company;
                        $packageDispatch->Dropoff_Contact_Phone_Number = $packageOther->Dropoff_Contact_Phone_Number;
                        $packageDispatch->Dropoff_Contact_Email        = $packageOther->Dropoff_Contact_Email;
                        $packageDispatch->Dropoff_Address_Line_1       = $packageOther->Dropoff_Address_Line_1;
                        $packageDispatch->Dropoff_Address_Line_2       = $packageOther->Dropoff_Address_Line_2;
                        $packageDispatch->Dropoff_City                 = $packageOther->Dropoff_City;
                        $packageDispatch->Dropoff_Province             = $packageOther->Dropoff_Province;
                        $packageDispatch->Dropoff_Postal_Code          = $packageOther->Dropoff_Postal_Code;
                        $packageDispatch->Notes                        = $packageOther->Notes;
                        $packageDispatch->Weight                       = $packageOther->Weight;
                        $packageDispatch->Route                        = $packageOther->Route;
                        $packageDispatch->Date_Dispatch                = $created_at;
                        $packageDispatch->quantity                     = $packageOther->quantity;
                        $packageDispatch->idPaymentTeam                = '';
                        $packageDispatch->created_at                   = $created_at;
                        $packageDispatch->updated_at                   = $created_at;
                    }

                    $packageDispatch->taskDetails        = $packageDispatch->Reference_Number_1;
                    $packageDispatch->workerName         = $user ? $user->name .' '. $user->nameOfOwner : '';
                    $packageDispatch->destinationAddress = $packageDispatch->Dropoff_Address_Line_1;
                    $packageDispatch->recipientNotes     = $user ? $user->nameTeam : '';

                    $photoUrl = '';

                    foreach($photoUploadIds as $idPhoto)
                    {
                        $photoUrl = $photoUrl == '' ? $idPhoto['attachmentId'] : $photoUrl .','. $idPhoto['attachmentId'];
                    }

                    Log::info($photoUrl);

                    $packageDispatch->photoUrl      = $photoUrl;
                    $packageDispatch->Date_Delivery = date('Y-m-d H:i:s', $Date_Delivery / 1000);
                    $packageDispatch->status        = 'Delivery';
                    $packageDispatch->updated_at    = $created_at;

                    $packageDispatch->save();

                    if(isset($packageOther))
                    {
                        $packageOther->delete();
                    }

                    if($packageDispatch->company == 'INLAND LOGISTICS' || $packageDispatch->company == 'AMERICAN EAGLE' || $packageDispatch->company == 'EIGHTVAPE' || $packageDispatch->company == 'Smart Kargo')
                    {
                        //create or update price company team
                        $packagePriceCompanyTeamController = new PackagePriceCompanyTeamController();
                        $packagePriceCompanyTeamController->Insert($packageDispatch, 'today');
                    }

                    if($packageDispatch->company != 'Smart Kargo')
                    {
                        Log::info($packageDispatch->company);

                        //data for INLAND
                        $packageController = new PackageController();
                        $packageController->SendStatusToInland($packageDispatch, 'Delivery', explode(',', $photoUrl), date('Y-m-d H:i:s'));
                        //end data for inland

                        $packageHistory = PackageHistory::where('Reference_Number_1', $packageDispatch->Reference_Number_1)
                                                ->where('sendToInland', 1)
                                                ->where('status', 'Manifest')
                                                ->first();

                        if($packageHistory)
                        {
                            $packageController->SendStatusToOtherCompany($packageDispatch, 'Delivery', explode(',', $photoUrl), date('Y-m-d H:i:s'));
                        }
                    }
                    else
                    {
                        Log::info('Does not send status to '. $packageDispatch->company);
                    }
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
        $idOnfleet               = $request['taskId'];
        $taskOnfleet             = $request['data']['task']['shortId'];
        $completionDetailsStatus = $request['data']['task']['completionDetails']['success'];
        $Description_Onfleet     = $request['data']['task']['completionDetails']['failureReason'] .': ['. $request['data']['task']['completionDetails']['failureNotes'] .', '. $request['data']['task']['completionDetails']['notes'] .']';
        $photoUploadIds          = $request['data']['task']['completionDetails']['unavailableAttachments'];
        Log::info('================================================');
        Log::info('============ START TASK FAILED ================');
        Log::info('TASK ONFLEET FAILED: '. $taskOnfleet);


        if($completionDetailsStatus == false)
        {
            try
            {
                DB::beginTransaction();

                $packageDispatch = PackageDispatch::find($Reference_Number_1);

                Log::info('Reference_Number_1: '. $Reference_Number_1);

                if($packageDispatch)
                {
                    $created_at          = date('Y-m-d H:i:s');
                    $Description_Onfleet = $Description_Onfleet;

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
                    $photoUrl = '';

                    foreach($photoUploadIds as $idPhoto)
                    {
                        $photoUrl = $photoUrl == '' ? $idPhoto['attachmentId'] : $photoUrl .','. $idPhoto['attachmentId'];
                    }

                    Log::info($photoUrl);

                    $packageFailed->photoUrl                     = $photoUrl;
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
                    $packageHistory->photoUrl                     = $photoUrl;
                    $packageHistory->actualDate                   = $created_at;
                    $packageHistory->created_at                   = $created_at;
                    $packageHistory->updated_at                   = $created_at;

                    $packageHistory->save();

                    $packageDispatch->delete();

                    $packageController = new PackageController();

                    if($packageDispatch->idCompany == 1)
                    {
                        //data for INLAND
                        $packageController->SendStatusToInland($packageDispatch, 'Failed', null, date('Y-m-d H:i:s'));
                        //end data for inland
                    }

                    $packageHistory = PackageHistory::where('Reference_Number_1', $packageDispatch->Reference_Number_1)
                                                ->where('sendToInland', 1)
                                                ->where('status', 'Manifest')
                                                ->first();

                    if($packageHistory)
                    {
                        $packageController->SendStatusToOtherCompany($packageDispatch, 'Failed', null, date('Y-m-d H:i:s'));
                    }
                }

                DB::commit();

                Log::info("==================== CORRECT TASK - FAILED". $request);
            }
            catch(Exception $e)
            {
                DB::rollback();

                Log::info("==================== ROLLBACK TASK - FAILED");
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
        $userCreatorOnfleet = $request['actionContext']['type'];
        $idOnfleet          = $request['taskId'];
        $taskOnfleet        = $request['data']['task']['shortId'];
        $idWorkerOnfleet    = $request['data']['task']['worker'];
        $Reference_Number_1 = $request['data']['task']['notes'];

        if($userCreatorOnfleet == 'ADMIN')
        {
            Log::info('====================================================================');
            Log::info('============ START TASK CREATED - ONFLEET DASHBOARD ================');
            Log::info('Reference_Number_1: '. $Reference_Number_1);

            $driver = User::where('idOnfleet', $idWorkerOnfleet)->first();
            $team   = User::find($driver->idTeam);

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

                if(!$package)
                {
                   $package = PackageFailed::where('Reference_Number_1', $Reference_Number_1)->first();
                }

                if(!$package)
                {
                    $package = PackageDispatch::where('Reference_Number_1', $Reference_Number_1)->where('status', 'Delete')->first();
                }

                $descriptionDispatch = 'To: '. $team->name .' / '. $driver->name .' '. $driver->nameOfOwner;

                if($package)
                {
                    try
                    {
                        DB::beginTransaction();

                        if($package->status == 'Manifest')
                        {
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
                            $packageHistory->Description                  = 'For Onfleet[ '. $userCreatorOnfleet .' ] ';
                            $packageHistory->inbound                      = 1;
                            $packageHistory->status                       = 'Inbound';
                            $packageHistory->actualDate                   = date('Y-m-d H:i:s');
                            $packageHistory->created_at                   = date('Y-m-d H:i:s');
                            $packageHistory->updated_at                   = date('Y-m-d H:i:s');

                            $packageHistory->save();
                        }

                        if($package->status != 'Delete')
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
                            $packageDispatch->idOnfleet                    = $idOnfleet;
                            $packageDispatch->taskOnfleet                  = $taskOnfleet;
                            $packageDispatch->Date_Dispatch                = date('Y-m-d H:i:s');
                            $packageDispatch->status                       = 'Dispatch';
                            $packageDispatch->created_at                   = date('Y-m-d H:i:s');
                            $packageDispatch->updated_at                   = date('Y-m-d H:i:s');

                            $packageDispatch->save();
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
                        $packageHistory->Date_Dispatch                = date('Y-m-d H:s:i');
                        $packageHistory->dispatch                     = 1;
                        $packageHistory->Description                  = 'For Onfleet[ '. $userCreatorOnfleet .' ] '. $descriptionDispatch;
                        $packageHistory->status                       = 'Dispatch';
                        $packageHistory->actualDate                   = date('Y-m-d H:i:s');
                        $packageHistory->created_at                   = date('Y-m-d H:i:s');
                        $packageHistory->updated_at                   = date('Y-m-d H:i:s');

                        $packageHistory->save();

                        if($package->status == 'Delete')
                        {
                            $package->Date_Dispatch = date('Y-m-d H:i:s');
                            $package->status        = 'Dispatch';
                            $package->idOnfleet     = $idOnfleet;
                            $package->taskOnfleet   = $taskOnfleet;
                            $package->created_at    = date('Y-m-d H:i:s');
                            $package->updated_at    = date('Y-m-d H:i:s');

                            $package->save();
                        }
                        else
                        {
                            $package->delete();
                        }

                        DB::commit();

                        Log::info('============ END TASK CREATED - CORRECT ================');
                        Log::info('====================================================================');
                        Log::info('====================================================================');
                    }
                    catch(Exception $e)
                    {
                        DB::rollback();

                        Log::info('============ END TASK CREATED - FAILED - ROLLBACK ================');
                        Log::info('====================================================================');
                        Log::info('====================================================================');
                    }
                }

                Log::info('========= PACKAGE NOT EXISTS OR PACKAGE IS IN DISPATCH =========');
                Log::info('============ END TASK CREATED - FAILED ================');
                Log::info('====================================================================');
                Log::info('====================================================================');
            }
            else
            {
                Log::info('========= NO EXISTS DRIVER OR TEAM - TASK CREATED');
                Log::info('============ END TASK CREATED - FAILED ================');
                Log::info('====================================================================');
                Log::info('====================================================================');
            }
        }
    }

    //WEBHOOK - TASK - DELETE
    public function EndPointTaskDelete(Request $request)
    {
        return response($request->check, 200)
            ->header('Content-Type', 'text/plain');
    }

    public function TaskDelete(Request $request)
    {
        $Reference_Number_1 = $request['data']['task']['notes'];
        $userCreatorOnfleet = $request['actionContext']['type'];

        Log::info('Reference_Number_1:'. $Reference_Number_1);

        $package = PackageDispatch::where('Reference_Number_1', $Reference_Number_1)
                                    ->where('status', 'Dispatch')
                                    ->first();

        if($package)
        {
            Log::info('================================================');
            Log::info('================================================');
            Log::info('============ TASK DELETE - ONFLEET ================');

            try
            {
                DB::beginTransaction();

                $descriptionHistory = 'For: Onfleet[ '. $userCreatorOnfleet .' ]';

                $nowDate    = date('Y-m-d H:i:s', strtotime('+2 second', strtotime(date('Y-m-d H:i:s'))));
                $created_at = date('Y-m-d H:i:s', strtotime('+2 second', strtotime(date('Y-m-d H:i:s'))));

                if(date('H:i:s') > date('20:00:00'))
                {
                    $created_at = date('Y-m-d 03:00:15', strtotime($nowDate .'+1 day'));
                }
                elseif(date('H:i:s') < date('03:00:00'))
                {
                    $created_at = date('Y-m-d 03:00:15');
                }
                else
                {
                    $created_at = date('Y-m-d H:i:s', strtotime('+2 second', strtotime(date('Y-m-d H:i:s'))));
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
                $packageHistory->Description                  = $descriptionHistory;
                $packageHistory->status                       = 'Delete';
                $packageHistory->actualDate                   = $nowDate;
                $packageHistory->created_at                   = $created_at;
                $packageHistory->updated_at                   = $created_at;

                $packageHistory->save();

                //update status Dispatch
                $package->status     = 'Delete';
                $package->updated_at = date('Y-m-d H:i:s');

                $package->save();

                DB::commit();

                Log::info('============ END TASK DELETE - ONFLEET ================');
                Log::info('====================================================');
                Log::info('====================================================');
            }
            catch(Exception $e)
            {
                Log::info('========= ERROR PROCESS - TASK DELETE');
                Log::info('====================================================');
                Log::info('====================================================');

                DB::rollback();
            }
        }
    }

    public function EndPointTaskXcelerator(Request $request)
    {
        return response($request->check, 200)
            ->header('Content-Type', 'text/plain');
    }

    public function TaskXcelerator(Request $request)
    {
        Log::info('=========== START - TASK Acelerator ===========');
        Log::info($request);
        Log::info('=========== END - TASK Acelerator ===========');
    }
}