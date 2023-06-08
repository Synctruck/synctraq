<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Models\{ Company, PackageManifest, ZipCodeInland };

use DateTime;
use Log;

class TaskCreatePackageInland extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:task-create-package-inland';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create packages in INLAND';

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
        $this->CreatePackageInland();
    }

    public function CreatePackageInland()
    {
        $packageManifestList = PackageManifest::where('company', '!=', 'INLAND LOGISTICS')
                                                ->where('sendToInland', 0)
                                                ->get()
                                                ->take(50);

        foreach($packageManifestList as $packageManifest)
        {
            $zipCode         = ZipCodeInland::where('zipCode', $packageManifest->Dropoff_Postal_Code)->first();
            $packageManifest = PackageManifest::find($packageManifest->Reference_Number_1);

            if($zipCode && $packageManifest)
            {
                $company         = Company::where('name', 'INLAND LOGISTICS')->first();
                $created_at_temp = DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s'));
                $created_at      = $created_at_temp->format(DateTime::ATOM);

                $data = '{
                            "shipment_type": "pick_up",
                            "created_at": "",
                            "ship_date": "2021-10-14T16:46:53-0600",
                            "shipment": {
                                "shipper_package_id": "'. $packageManifest->Reference_Number_1 .'",
                                "ship_from": {
                                    "facility_shortcode": "SMEWRD1",
                                    "address_type": "ship_from",
                                    "name": "Shipping",
                                    "company": "NAPERVILLE199",
                                    "phone": "2125551212",
                                    "address_line1": "80 HEATHER DR",
                                    "address_line2": "",
                                    "address_line3": "",
                                    "city_locality": "ROSLYN",
                                    "state_province": "NY",
                                    "postal_code": "11576"
                                },
                                "ship_to": {
                                    "address_type": "",
                                    "name": "'. $packageManifest->Dropoff_Contact_Name .'",
                                    "company": "",
                                    "phone": "'. $packageManifest->Dropoff_Contact_Phone_Number .'",
                                    "address_line1": "'. $packageManifest->Dropoff_Address_Line_1 .'",
                                    "address_line2": "", 
                                    "address_line3": "",
                                    "city_locality": "'. $packageManifest->Dropoff_City .'",
                                    "state_province": "'. $packageManifest->Dropoff_Province .'",
                                    "postal_code": "'. $packageManifest->Dropoff_Postal_Code .'",
                                    "address_residential_indicator": true
                                },
                                "shipment_details": { 
                                    "ship_date": "'. $created_at .'",
                                    "weight": '. $packageManifest->Weight .',
                                    "weight_unit": "lb",
                                    "length": 0,
                                    "shipper_notes_1": "Notes",
                                    "width": 0,
                                    "height": 0,
                                    "signature_on_delivery": false,
                                    "hazardous_goods": false,
                                    "hazardous_goods_type": null,
                                    "label_message": "Deliver behind planter at the front door.",
                                    "contains_alcohol": false,
                                    "insured_value": null,
                                    "service_code": null,
                                    "goods_type": null
                                }
                            }
                        }';

                $curl = curl_init();
                
                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://api.staging.inlandlogistics.co/api/v6/add-to-manifest',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10, 
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => $data,
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/json',
                        'authorization: '. $company->api_key_inland_insert
                    ),
                ));

                $output      = json_decode(curl_exec($curl), 1);
                $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

                curl_close($curl);
                
                if($http_status >= 200 && $http_status <= 299)
                {
                    $packageManifest->sendToInland = 1;
                }
                else if($http_status >= 400 && $http_status <= 499)
                {
                    Log::info($output['error']);

                    $packageManifest->sendToInland = 3;
                }
                else if($http_status >= 500)
                {
                    $packageManifest->sendToInland = 4;
                }

                $packageManifest->sendToInlandDate = date('Y-m-d H:i:s');
                $packageManifest->save();
            }
            else if($packageManifest)
            {
                $packageManifest->sendToInland     = 5;
                $packageManifest->sendToInlandDate = date('Y-m-d H:i:s');
                $packageManifest->save();
            }
        }
    }
}