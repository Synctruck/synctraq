<?php
namespace App\External;

use App\Models\{ Company, PackageBlocked };

use Auth;
use DateTime;
use Log;

class ExternalServiceInland{

    public function GetPackage($Reference_Number_1)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.staging.inlandlogistics.co/api/v6/shipments/shipment-info/'. $Reference_Number_1,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $output      = json_decode(curl_exec($curl), 1);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if($http_status >= 200 && $http_status <= 299)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function RegisterPackage($package)
    {
        $company         = Company::find(1);
        $label_message   = $package->company == 'EIGHTVAPE' ? '21+/VPOD' : 'Deliver behind planter at the front door.';
        $created_at_temp = DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s'));
        $created_at      = $created_at_temp->format(DateTime::ATOM);

        $data = '{
                    "shipment_type": "drop_off",
                    "created_at": "",
                    "ship_date": "2021-10-14T16:46:53-0600",
                    "shipment": {
                        "shipper_package_id": "'. $package->Reference_Number_1 .'",
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
                            "name": "'. $package->Dropoff_Contact_Name .'",
                            "company": "",
                            "phone": "'. $package->Dropoff_Contact_Phone_Number .'",
                            "address_line1": "'. $package->Dropoff_Address_Line_1 .'",
                            "address_line2": "'. $package->Dropoff_Address_Line_2 .'",
                            "address_line3": "",
                            "city_locality": "'. $package->Dropoff_City .'",
                            "state_province": "'. $package->Dropoff_Province .'",
                            "postal_code": "'. $package->Dropoff_Postal_Code .'",
                            "address_residential_indicator": true
                        },
                        "shipment_details": { 
                            "ship_date": "'. $created_at .'",
                            "weight": '. $package->Weight .',
                            "weight_unit": "lb",
                            "length": 0,
                            "shipper_notes_1": "Notes",
                            "width": 0,
                            "height": 0,
                            "signature_on_delivery": false,
                            "hazardous_goods": false,
                            "hazardous_goods_type": null,
                            "label_message": "'. $label_message .'",
                            "contains_alcohol": false,
                            "insured_value": null,
                            "service_code": null,
                            "goods_type": null
                        }
                    }
                }';

        $curl = curl_init();
        
        curl_setopt_array($curl, array(
            CURLOPT_URL => ENV('URL_INLAND_CREATE') .'api/v6/add-to-manifest',
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

        return ['status' => $http_status, 'output' => $output];
    }

    public function PackageUpdate($request)
    {
        $company = Company::find(1);

        $curl = curl_init();
        
        curl_setopt_array($curl, array(
            CURLOPT_URL => $company->url_webhook . $request->Reference_Number_1 .'/update-details',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10, 
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => '{
                "address_line1": "'. $request->Dropoff_Address_Line_1 .'",
                "address_line2": "'. $request->Dropoff_Address_Line_2 .'",
                "city_locality": "'. $request->Dropoff_City .'",
                "state_province": "'. $request->Dropoff_Province .'",
                "postal_code": "'. $request->Dropoff_Postal_Code .'"
            }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'authorization: '. $company->key_webhook
            ),
        ));

        $output      = json_decode(curl_exec($curl), 1);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if($http_status == 200)
        {
            return ['status' => $http_status, 'response' => $output];
        }
        else
        {
            return ['status' => $http_status, 'response' => $output];
        }
    }

    public function SendToTakeOver($Reference_Number_1)
    {
        $curl = curl_init();
        Log::info(ENV('URL_SYNC_WEB') .'api/v6/shipments/take-over/'. $Reference_Number_1);
        curl_setopt_array($curl, array(
            CURLOPT_URL => ENV('URL_SYNC_WEB') .'api/v6/shipments/take-over/'. $Reference_Number_1,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10, 
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => array(
                'token: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJib2R5Ijp7ImlkIjoiNjU4MWI4MTM2OGU5NTk5YTdjODhkMzVhIiwiZW1haWwiOiJ3aWxjbTEyM0BnbWFpbC5jb20iLCJuYW1lIjoid2lsYmVyIGNhaHVhbmEiLCJsYXN0T3JnIjoiNjU3MjA4NWEyOTE1MzMzNjFjNGEwYWI4IiwicGVybWlzc2lvbnMiOiJlZGl1c2UsbGlzdXNlLHZpZXVzZSx2aWVyb2wsbGlzcm9sLGNyZXJvbCxsaWZycGUsZGVscm9sLGVkaXJvbCx2aWVzaGksbGlzc2hpcCx2aWVvcmcsY3Jlc2hpLGVkaXNoaSxsaXNvcmcsY3Jlb3JnLGNyZXVzZSxsaWFub20sc29hbm9tLGNyZWpvYixsaXNqb2IsdmVuZGFzLGxpc3JvdSxsaXNkcmlsb2MiLCJvcmdzIjpbeyJpZCI6IjY1NzIwODVhMjkxNTMzMzYxYzRhMGFiOCIsIm5hbWUiOiJTeW5jdHJ1Y2sifV0sImV4cCI6IjIwMjQtMDMtMjRUMDU6MTg6MzguMjY4WiJ9LCJpYXQiOjE3MTEyMTQzMTgsImV4cCI6MTcxMTI1NzUxOCwiYXVkIjoic3luYy1zeXN0ZW0iLCJzdWIiOiJ3aWxjbTEyM0BnbWFpbC5jb20ifQ.F2NcheTpI0sOD_BBKYSpPglge2gQ-SY24mBfWFwdPKs'
            ),
        ));

        $response    = json_decode(curl_exec($curl), 1);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        return $response;
    }
}
