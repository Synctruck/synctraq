<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Models\{ PackageManifest, Route4me };

use Log;

class TaskGetGeocode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:get-geocode-route4me';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validar confidence geocode';

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
        Log::info("========== SCHEDULE TASK GET GEOCODE ==========");

        try
        {
            DB::beginTransaction();

            $listPackageManifest = PackageManifest::where('confidenceAddress', '')->get();

            if($listPackageManifest->count() >= 180)
            {
                $listPackageManifest = $listPackageManifest->take(180);
            }
            else if($listPackageManifest->count() < 180)
            {
                $listPackageManifest = $listPackageManifest->take($listPackageManifest->count());
            }

            foreach($listPackageManifest as $packageManifest)
            {
                $fullAddress = $packageManifest->Dropoff_Address_Line_1 .', '. $packageManifest->Dropoff_City .', '. $packageManifest->Dropoff_Province .' '. $packageManifest->Dropoff_Postal_Code;

                $route4me = Route4me::where('fullAddress', $fullAddress)->first();

                if($route4me)
                {
                    $packageManifest = PackageManifest::find($packageManifest->Reference_Number_1);

                    $packageManifest->confidenceAddress = $route4me->confidenceAddress;

                    $packageManifest->save();
                }
                else
                {
                    $urlRoute4me = 'https://api.route4me.com/api/address.php?address='. $packageManifest->Dropoff_Address_Line_1 .', '. $packageManifest->Dropoff_City .', '. $packageManifest->Dropoff_Province .' '. $packageManifest->Dropoff_Postal_Code .', USA&format=json&detailed=true&api_key=73D4A484115AEFA26C7E3CB5D2350BA0';

                    $urlRoute4me = str_replace(' ', '%20', $urlRoute4me);

                    $curl = curl_init($urlRoute4me);

                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

                    $output      = json_decode(curl_exec($curl), 1);
                    $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

                    curl_close($curl);

                    if($http_status == 200)
                    {
                        if(isset($output[0]))
                        {
                            $dataGeocode = $output[0];

                            $route4me = new Route4me();
                            $route4me->fullAddress       = $fullAddress;
                            $route4me->confidenceAddress = $dataGeocode['confidence'];
                            $route4me->latitute          = $dataGeocode['coordinates']['lat'];
                            $route4me->longitude         = $dataGeocode['coordinates']['lng'];
                            $route4me->save();

                            $packageManifest = PackageManifest::find($packageManifest->Reference_Number_1);
                            $packageManifest->confidenceAddress = $dataGeocode['confidence'];
                            $packageManifest->save();
                        }
                    }
                }
            }

            Log::info("==================== CORRECT SCHEDULE TASK GET GEOCODE ");
            Log::info("============================================================");

            DB::commit();
        }
        catch(Exception $e)
        {
            DB::rollback();

            Log::info("==================== ROLLBACK SCHEDULE TASK GET GEOCODE ");
            Log::info("============================================================");
        }
    }
}