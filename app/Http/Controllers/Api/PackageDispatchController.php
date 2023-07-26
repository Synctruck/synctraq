<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{ Company, PackageDispatch, User };

use Illuminate\Support\Facades\Validator;

use App\Http\Controllers\{ CompanyController, RangePriceCompanyController };
use App\Http\Controllers\Api\PackageController;

use DB;
use DateTime;
use Log;
use Session;

class PackageDispatchController extends Controller
{
    /*
        * Retorna packages de inland que están en dispatch y  que fueron asignados a un driver
        *
        * @var: apiKey
        * @var: idDriver
        * @validation: apiKey and idDriver
    */
    public function ListByDriverInland($apiKey, $idDriver)
    {
        $company = Company::where('id', 1)
                            ->where('key_api', $apiKey)
                            ->first();

        if($company)
        {
            $driver = User::where('idRole', 4)->find($idDriver);

            if($driver)
            {
                $shipments = $this->ListPackagesInDispatch($idDriver);

                return response()->json(
                    [
                        'message' => "The driver was found",
                        'shipments' => $shipments
                    ]
                , 200);
            }
            else
            {
                return response()->json(['message' => "The driver was not found"], 404);
            }
        }
        else
        {
            return response()->json(['message' => "Authentication Failed: incorrect api-key"], 401);
        }
    }

    /*
        * Listar Packages de inland que están en dispatch y  que fueron asignados a un driver
        * @var: idDriver
        * formato: usar modelo solicitado
    */
    public function ListPackagesInDispatch($idDriver)
    {
        $packageDispatchList = PackageDispatch::where('idCompany', 1)
                                                ->where('idUserDispatch', $idDriver)
                                                ->where('status', 'Dispatch')
                                                ->get();

        $packageList = [];

        foreach($packageDispatchList as $packageDispatch)
        {
            $created_at = DateTime::createFromFormat('Y-m-d H:i:s', $packageDispatch->created_at);
            $created_at = $created_at->format(DateTime::ATOM);

            $package = [

                'createdAt' => $packageDispatch->created_at,
                'shipToStreet1' => $packageDispatch->Dropoff_Address_Line_1,
                'shipToStreet2' => $packageDispatch->Dropoff_Address_Line_2,
                'shipToCity' => $packageDispatch->Dropoff_City,
                'shipToState' => $packageDispatch->Dropoff_Province,
                'shipToPostalCode' => $packageDispatch->Dropoff_Postal_Code,
                'shipToName' => $packageDispatch->Dropoff_Contact_Name,
                'needsSignature' => true,
            ];

            array_push($packageList, $package);
        }

        return $packageList;
    }
}