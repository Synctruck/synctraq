<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Models\{ PackageHistory, PackageManifest };

use Log;

class TaskPackageNeverReceived extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:task-never-received';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mover paquetes con 15 días de antiguedad de manifest a never_received';

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
        Log::info("==========SCHEDULE TASK MOVE NEVER - RECEIVED ==========");

        try
        {
            DB::beginTransaction();

            $listPackageManifest = PackageManifest::where('status', 'Manifest')->get();

            $nowDate = date('Y-m-d H:i:s');

            foreach($listPackageManifest as $packageManifest)
            {
                $days = (strtotime($nowDate) - strtotime($packageManifest->created_at)) / 86400;

                if($days >= 15)
                {
                    $packageManifest = PackageManifest::find($packageManifest->Reference_Number_1);

                    $packageManifest->status     = 'NeverReceived';
                    $packageManifest->updated_at = $nowDate;

                    $packageManifest->save();

                    $packageHistory = new PackageHistory();

                    $packageHistory->id = uniqid();
                    $packageHistory->Reference_Number_1           = $packageManifest->Reference_Number_1;
                    $packageHistory->idCompany                    = $packageManifest->idCompany;
                    $packageHistory->company                      = $packageManifest->company;
                    $packageHistory->Dropoff_Contact_Name         = $packageManifest->Dropoff_Contact_Name;
                    $packageHistory->Dropoff_Contact_Phone_Number = $packageManifest->Dropoff_Contact_Phone_Number;
                    $packageHistory->Dropoff_Contact_Email        = $packageManifest->Dropoff_Contact_Email;
                    $packageHistory->Dropoff_Address_Line_1       = $packageManifest->Dropoff_Address_Line_1;
                    $packageHistory->Dropoff_City                 = $packageManifest->Dropoff_City;
                    $packageHistory->Dropoff_Province             = $packageManifest->Dropoff_Province;
                    $packageHistory->Dropoff_Postal_Code          = $packageManifest->Dropoff_Postal_Code;
                    $packageHistory->Weight                       = $packageManifest->Weight;
                    $packageHistory->status                       = 'NeverReceived';
                    $packageHistory->Description                  = 'For: Schedule Taks';
                    $packageHistory->Route                        = $packageManifest->route;
                    $packageHistory->actualDate                   = $nowDate;
                    $packageHistory->created_at                   = $nowDate;
                    $packageHistory->updated_at                   = $nowDate;

                    $packageHistory->save();
                }
            }

            Log::info("==================== CORRECT SCHEDULE TASK NEVER - RECEIVED ");
            Log::info("============================================================");

            DB::commit();
        }
        catch(Exception $e)
        {
            DB::rollback();

            Log::info("==================== ROLLBACK SCHEDULE TASK NEVER - RECEIVED ");
            Log::info("============================================================");
        }
    }
}