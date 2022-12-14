<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Models\{ PackageFailed, PackagePreFailed, PackageDispatch, PackageHistory };

use Log;

class TaskPackageFailed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:task-insert-package-failed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mover paquetes de pre failed a failed';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Log::info("============================================================");
        Log::info("==========SCHEDULE TASK MOVE PRE-FAILED TO FAILED ==========");

        try
        {
            DB::beginTransaction();

            $listPackagePreFailed = PackagePreFailed::all();

            foreach($listPackagePreFailed as $packagePreFailed)
            {
                $packagePreFailed = PackagePreFailed::find($packagePreFailed->taskOnfleet);
                $packageDispatch  = PackageDispatch::where('taskOnfleet', $packagePreFailed->taskOnfleet)->first();
                
                if($packageDispatch)
                {
                    $Description_Onfleet = $packagePreFailed->Description_Onfleet;
                    //$user = User::find($packageDispatch->idUserDispatch);

                    //$description = $user ? 'For: Driver '. $user->name .' '. $user->nameOfOwner : 'Driver not exists';

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
                    $packageFailed->created_at                   = $packagePreFailed->created_at;
                    $packageFailed->updated_at                   = $packagePreFailed->created_at;

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
                    $packageHistory->created_at                   = $packagePreFailed->created_at;
                    $packageHistory->updated_at                   = $packagePreFailed->created_at;

                    $packageHistory->save();
                    
                    $packageDispatch->delete();
                }

                $packagePreFailed->delete();
            }

            Log::info("==================== CORRECT SCHEDULE TASK MOVE PRE-FAILED TO FAILED");
            Log::info("============================================================");

            DB::commit();
        }
        catch(Exception $e)
        {
            DB::rollback();

            Log::info("==================== ROLLBACK SCHEDULE TASK MOVE PRE-FAILED TO FAILED");
            Log::info("============================================================");
        }
    }
}