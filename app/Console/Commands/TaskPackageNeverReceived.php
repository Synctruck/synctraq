<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Models\{ PackageManifest };

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

                    $packageManifest->status     = 'Never Received';
                    $packageManifest->updated_at = $nowDate;

                    $packageManifest->save();
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