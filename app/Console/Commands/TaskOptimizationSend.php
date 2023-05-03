<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Models\{ Optimization, PackageManifest, PackageInbound, PackageWarehouse };

use App\Http\Controllers\{ PackagePriceCompanyTeamController };

use DateTime;
use Log;
use Mail;

class TaskOptimizationSend extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:optimization-send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Se envía packages para crear una nueva optimización de routes';

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
        $this->SendOptimization();
    }

    public function SendOptimization()
    {
        $curl = curl_init();

        $data      = $this->GetDataToSend();
        $addresses = $data['address'];

        $dataToSend =  '{
                            "parameters":
                            {
                                "share_route": false,
                                "route_name": "SyncTruck",
                                "route_time": 43200,
                                "optimize": "Time",
                                "distance_unit": "mi",
                                "travel_mode": "Driving",
                                "route_max_duration": 28800,
                                "store_route": false,
                                "algorithm_type": "9",
                                "device_type": "web",
                                "parts": 999,
                                "advanced_constraints":
                                [
                                    {
                                        "depot_address":
                                        {
                                            "alias": "DEPOT NJ",
                                            "address": "310 Paterson Plank Rd, Carlstadt, NJ 07072, USA",
                                            "lat": 40.82221977,
                                            "lng": -74.06843583,
                                            "time": 0
                                        },
                                        "max_cargo_weight": 1900,
                                        "members_count": 999,
                                        "available_time_windows":
                                        [
                                            [
                                                43200,
                                                72000
                                            ]
                                        ]
                                    },
                                    {
                                        "depot_address":
                                        {
                                            "alias": "DEPOT MD",
                                            "address": "2600 Cabover Dr, Hanover, MD 21076, USA",
                                            "lat": 39.15846976,
                                            "lng": -76.68194087,
                                            "time": 0
                                        },
                                        "max_cargo_weight": 1900,
                                        "members_count": 999,
                                        "available_time_windows":
                                        [
                                            [
                                                43200,
                                                72000
                                            ]
                                        ]
                                    },
                                    {
                                        "depot_address":
                                        {
                                            "alias": "DEPOT DE",
                                            "address": "4200 Governor Printz Blvd, Wilmington, DE 19802, USA",
                                            "lat": 39.7523966,
                                            "lng": -75.5166413,
                                            "time": 0
                                        },
                                        "max_cargo_weight": 1900,
                                        "members_count": 999,
                                        "available_time_windows":
                                        [
                                            [
                                                43200,
                                                72000
                                            ]
                                        ]
                                    }
                                ],
                                "use_mixed_pickup_delivery_demands": false
                            },
                            "addresses":
                            [
                                '. $addresses .'
                            ]
                        }';


        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.route4me.com/api.v4/optimization_problem.php?redirect=0&api_key=73D4A484115AEFA26C7E3CB5D2350BA0',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $dataToSend,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
          ),
        ));
    
        $output      = json_decode(curl_exec($curl), 1);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);


        Log::info('Task send optimization');
        Log::info('http_status:'. $http_status);
        Log::info($dataToSend);

        if($http_status == 200)
        {
            $optimization = new Optimization();
            $optimization->optimization_problem_id = $output['optimization_problem_id'];
            $optimization->state                   = $output['state'];
            $optimization->quantityPackage         = $data['quantityPackage'];
            $optimization->status                  = 'Open';
            $optimization->save();
        }
    }

    public function GetDataToSend()
    {
        $listPackageManifest = PackageManifest::where('confidenceAddress', 'high')->get()->take(900);
        $address             = '';
        $todayDate           = date('m/d/Y');

        foreach($listPackageManifest as $packageManifest) 
        {
            $newAddress =   '{
                                "address": "'. $packageManifest->Dropoff_Address_Line_1 .', '. $packageManifest->Dropoff_City .', '. $packageManifest->Dropoff_Province .' '. $packageManifest->Dropoff_Postal_Code .', USA",
                                "lat": '. $packageManifest->latitude .',
                                "lng": '. $packageManifest->longitude .',
                                "time": 120,
                                "custom_fields":
                                {
                                    "DATE": "'. $todayDate .'",
                                    "COMPANY": "'. $packageManifest->company .'",
                                    "PACKAGE ID": "'. $packageManifest->Reference_Number_1 .'",
                                    "CLIENT": "'. $packageManifest->Dropoff_Contact_Name .'",
                                    "CONTACT": "'. $packageManifest->Dropoff_Contact_Phone_Number .'"
                                },
                                "weight": "'. $packageManifest->Weight .'"
                            }';

            $address = $address == '' ? $newAddress : $address .','. $newAddress;
        }

        $quantityPackage = count($listPackageManifest);

        return ['address' => $address, 'quantityPackage' => $quantityPackage];
    }
}