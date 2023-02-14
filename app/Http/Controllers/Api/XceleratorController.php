<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{ Comment, Company, CompanyStatus, Configuration };

use App\Http\Controllers\ConfigurationController;

use DateTime;
use DB;
use Log;
use Session;

class XceleratorController extends Controller
{
    private $configuration;
    private $access_token_Xcelerator;

    public function __construct()
    {
        $this->configuration           = Configuration::first();
        $this->access_token_Xcelerator = $this->configuration->access_token_Xcelerator;
    }

    public function GetAccessToken()
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://xcel.brookscourier.net/xcelerator/AXIS/login',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'grant_type=password&username='. $this->configuration->user_Xcelerator .'&password='. $this->configuration->password_Xcelerator,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: text/plain',
                'Cookie: .AspNet.ApplicationCookie=AKWeAbM0wWWzjDuJN9uQoNKHvHrJ3lHVMBpmrLgUw3AQiuCovlkQooDZcPwnVGY8G8px8yREkI662VZz6lKzmMUApoT5j5O5KWyxnpAeY9sHU00gM99HZZh4dPrEVlgLAIeVdxK-v2l74_kkzGMKnQN8vtNetlWEHKvrBIxgFuysw0e0YHWT3TlAm30h1hIwDtpssxIWLIdaImnE5uSWakY9zkD69qDcPHCrKNd8GYonpXnIzX5LVPYAI__xkFYRdWCq21FGHGkmeUqMgG6gxw1JJ1wAg6XSguFV26UI-JOcDzxnRW5R7DzxQBRRyKcqFGKuVYTxZ98d8c2TlafILB8xwPboyhvap8vfXJQ2DyMP-EJDHQh24ewB0vcbLqKDHMW0HmtV25sEgNwDDEH79MqduKQkHMbaDF2a5G3B9T2NH3-aru9XxyMJlnmemeTjMNJk2z9PA-Bkogi8q3s7wg'
            ),
        ));
 
        $response    = curl_exec($curl);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);
        
        $response = json_decode($response);

        if($http_status == 200)
        {
            $configurationController = new ConfigurationController();
            $configurationController = $configurationController->UpdateTokenXcelerator($response);

            $this->access_token_Xcelerator = $response->access_token;

            return ['statusAction' => true];
        }
        else
        {
            return ['statusAction' => false, 'response' => $response];
        }
    }

    public function Insert($package, $driver)
    {
        $curl = curl_init();

        Log::info("access_token_Xcelerator");
        Log::info($this->access_token_Xcelerator);

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://xcel.brookscourier.net/xcelerator/Axis/v2/Order/SubmitOrders',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'[
                {
                    "AltRefId": null,
                    "RouteNo": "'. $package->Route .'",
                    "DriverId": "'. $driver->id .'",
                    "DriverFirstName": "'. $driver->name .'",
                    "DriverLastName": "'. $driver->nameOfOwner .'",
                    "AccountNo": "SYNCT01",
                    "Service": "CDL Rush",
                    "Vehicle": "Van",
                    "ClientRefNo": "'. $package->Reference_Number_1 .'",
                    "ClientRefNo2": "'. $package->Route .'",
                    "ClientRefNo3": "",
                    "ClientRefNo4": "",
                    "PickupAddress": {
                        "Street": "'. $package->Dropoff_Address_Line_1 .'",
                        "City": "'. $package->Dropoff_City .'",
                        "State": "'. $package->Dropoff_Province .'",
                        "Zip": "'. $package->Dropoff_Postal_Code .'"
                    },
                    "PLI": null,
                    "DeliveryAddress": {
                        "Name": "'. $package->Dropoff_Contact_Name .'",
                        "Contact": "'. $package->Dropoff_Contact_Phone_Number .'",
                        "Street": "'. $package->Dropoff_Address_Line_1 .'",
                        "Street2": "",
                        "City": "'. $package->Dropoff_City .'",
                        "State": "'. $package->Dropoff_Province .'",
                        "Zip": "'. $package->Dropoff_Postal_Code .'",
                        "RefNo": null,
                        "Phone": "+'. $package->Dropoff_Contact_Phone_Number .'"
                    },
                    "DLI": null,
                    "ShipmentWeight": 11.7,
                    "PickupTargetFrom": "2023-01-26T09:13:00",
                    "PickupTargetTo": "2023-01-26T12:00:00",
                    "DeliveryTargetFrom": "2023-01-26T16:00:00",
                    "DeliveryTargetTo": "2023-01-26T20:00:00",
                    "OrderPackageItems": [
                        {
                            "PackageName": "Pieces",
                            "RefNo": "'. $package->Reference_Number_1 .'",
                            "MasterPackageRefNo": "'. $package->Reference_Number_1 .'",
                            "Weight": '. $package->Weight .',
                            "Length": 0.00,
                            "Width": 0.00,
                            "Height": 0.00
                        }
                    ]
                }
            ]',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer '. $this->access_token_Xcelerator,
                'Content-Type: application/json'
            ),
        ));

        $response    = curl_exec($curl);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);
        
        $response = json_decode($response);

        Log::info("status Xcelerator: ". $http_status);

        if($http_status == 200)
        {
            return ['status' => $http_status, 'response' => $response];
        }
        else if($http_status == 401)
        {
            return ['status' => $http_status, 'Message' => $response->Message];
        }
    }
}