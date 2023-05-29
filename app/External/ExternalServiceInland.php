<?php
namespace App\External;

use App\Models\{ PackageBlocked };

use Auth;

class ExternalServiceInland{

    public function PackageUpdate($request)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://api.staging.inlandlogistics.co/api/v6/shipments/84670689577/update-details',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'PUT',
          CURLOPT_POSTFIELDS =>'{
            "address_line1": "address test",
            "address_line2": "",
            "city_locality": "new city",
            "state_province": "new state",
            "postal_code": "17321"
        }',
          CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'authorization: SHZX2ER-4YCM907-MM958YS-11GT162'
          ),
        ));

        

        $output      = json_decode(curl_exec($curl), 1);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        dd($http_status);
        if($http_status == 200)
        {
            return ['status' => $http_status, 'response' => $output];
        }
        else
        {
            return ['status' => $http_status, 'response' => $output];
        }
    }
}