<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use \App\Service\ServicePackageTerminal;

use App\Models\{ Configuration, PackageBlocked, PackageHistory, PackageInbound, PackageDispatch, PackageLost, PackageManifest, PackagePreDispatch, PackageReturn, PackageReturnCompany, PackageLmCarrier, States, User };

use Illuminate\Support\Facades\Validator;

use App\Http\Controllers\Api\PackageController;

use Barryvdh\DomPDF\Facade\PDF;

use Picqer\Barcode\BarcodeGeneratorPNG;

use DB;

use Session;

class PackageLmCarrierController extends Controller
{
    private $apiKey;

    private $base64;

    private $headers;

    public function __construct()
    {
        $this->apiKey = Configuration::first()->key_onfleet;

        $this->base64 = base64_encode($this->apiKey .':');

        $this->headers = [
                        'Authorization: Basic '. $this->base64,
                    ];
    }

    public function Index()
    {
        return view('package.lmcarrier');
    }

    public function List($idCompany, $dateStart,$dateEnd, $route, $state)
    {
        $packageListWarehouse = $this->GetDataLmCarrier($idCompany, $dateStart, $dateEnd, $route, $state);
        $quantityWarehouse    = $packageListWarehouse->total();

        $listState  = PackageLmCarrier::select('Dropoff_Province')
                                        ->groupBy('Dropoff_Province')
                                        ->get();

        $listStateValidate  = States::orderBy('name', 'asc')->get();                                    

        return ['packageList' => $packageListWarehouse, 'listState' => $listState, 'listStateValidate' => $listStateValidate, 'quantityWarehouse' => $quantityWarehouse];
    }

    private function GetDataLmCarrier($idCompany, $dateStart,$dateEnd, $route, $state,$type='list')
    {
        $dateStart = $dateStart .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);

        $packageListWarehouse = PackageLmCarrier::whereBetween('created_at', [$dateStart, $dateEnd]);

        if($idCompany != 0)
        {
            $packageListWarehouse = $packageListWarehouse->where('idCompany', $idCompany);
        }
    
        if($route != 'all')
        {
            $packageListWarehouse = $packageListWarehouse->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $packageListWarehouse = $packageListWarehouse->whereIn('Dropoff_Province', $states);
        }
        if($type == 'list')
        {
            $packageListWarehouse = $packageListWarehouse->orderBy('created_at', 'desc')
                                                        ->select(
                                                            'created_at',
                                                            'company',
                                                            'Reference_Number_1',
                                                            'Dropoff_Contact_Name',
                                                            'Dropoff_Contact_Phone_Number',
                                                            'Dropoff_Address_Line_1',
                                                            'Dropoff_City',
                                                            'Dropoff_Province',
                                                            'Dropoff_Postal_Code',
                                                            'Weight',
                                                            'Route'
                                                        )
                                                        ->paginate(50); 
        }
        else
        {
            $packageListWarehouse = $packageListWarehouse->orderBy('created_at', 'desc')->get();
        }

        return $packageListWarehouse;
    }

    public function Export($idCompany, $idValidator, $dateStart,$dateEnd, $route, $state, $typeExport)
    {
        $delimiter = ",";
        $filename  = $typeExport == 'download' ? "PACKAGES - MIDLE MIAL SCAN " . date('Y-m-d H:i:s') . ".csv" : Auth::user()->id ."- PACKAGES - MIDLE MIAL SCAN.csv";
        $file      = $typeExport == 'download' ? fopen('php://memory', 'w') : fopen(public_path($filename), 'w');

        //set column headers
        $fields = array('DATE', 'HOUR', 'COMPANY', 'VALIDATOR', 'PACKAGE ID', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'WEIGHT', 'ROUTE');

        fputcsv($file, $fields, $delimiter);

        $packageListWarehouse = $this->getDataWarehouse($idCompany, $idValidator, $dateStart,$dateEnd, $route, $state,$type='export');

        foreach($packageListWarehouse as $packageWarehouse)
        {
            $lineData = array(
                                date('m-d-Y', strtotime($packageWarehouse->created_at)),
                                date('H:i:s', strtotime($packageWarehouse->created_at)),
                                $packageWarehouse->company,
                                $packageWarehouse->user->name .' '. $packageWarehouse->user->nameOfOwner,
                                $packageWarehouse->Reference_Number_1,
                                $packageWarehouse->Dropoff_Contact_Name,
                                $packageWarehouse->Dropoff_Contact_Phone_Number,
                                $packageWarehouse->Dropoff_Address_Line_1,
                                $packageWarehouse->Dropoff_City,
                                $packageWarehouse->Dropoff_Province,
                                $packageWarehouse->Dropoff_Postal_Code,
                                $packageWarehouse->Weight,
                                $packageWarehouse->Route
                            );

            fputcsv($file, $lineData, $delimiter);
        }

        if($typeExport == 'download')
        {
            fseek($file, 0);

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '";');

            fpassthru($file);
        }
        else
        {
            rewind($file);
            fclose($file);

            SendGeneralExport('Packages Middle Mile Scan', $filename);

            return ['stateAction' => true];
        }
    }
}
