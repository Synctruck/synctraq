<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use \App\Service\ServicePackageTerminal;

use App\Models\{ Configuration, PackageBlocked, PackageHistory, PackageInbound, PackageDispatch, PackageLost, PackageManifest, PackagePreDispatch, PackageReturn, PackageReturnCompany, PackageWarehouse, States, User };

use Illuminate\Support\Facades\Validator;

use App\Http\Controllers\Api\PackageController;

use Barryvdh\DomPDF\Facade\PDF;

use Picqer\Barcode\BarcodeGeneratorPNG;

use DB;

use Session;

class PackageMiddleMileScanController extends Controller
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
        return view('package.middlemilescan');
    }

    public function List($idCompany, $idValidator, $dateStart,$dateEnd, $route, $state)
    {
        $packageListWarehouse = $this->getDataWarehouse($idCompany, $idValidator, $dateStart,$dateEnd, $route, $state);

        $quantityWarehouse      = $packageListWarehouse->total();

        $listState  = PackageWarehouse::select('Dropoff_Province')
                                            ->groupBy('Dropoff_Province')
                                            ->get();

        $listStateValidate  = States::orderBy('name', 'asc')->get();                                    

        return ['packageList' => $packageListWarehouse, 'listState' => $listState, 'listStateValidate' => $listStateValidate, 'quantityWarehouse' => $quantityWarehouse];
    }

    private function getDataWarehouse($idCompany, $idValidator, $dateStart,$dateEnd, $route, $state,$type='list'){

        $dateStart = $dateStart .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);

        if(Auth::user()->role->name == 'Administrador')
        {
            $packageListWarehouse = PackageWarehouse::with('user');
        }
        else
        {
            $packageListWarehouse = PackageWarehouse::with('user')
                                                    ->where('idUser', Auth::user()->id);
        }

        $packageListWarehouse = $packageListWarehouse->where('status', 'Middle Mile Scan')
                                                    ->whereBetween('created_at', [$dateStart, $dateEnd]);

        if($idCompany != 0)
        {
            $packageListWarehouse = $packageListWarehouse->where('idCompany', $idCompany);
        }
        
        if($idValidator)
        {
            $packageListWarehouse = $packageListWarehouse->where('idUser', $idValidator);
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
                                                            'idUser',
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

    public function Insert(Request $request)
    {
        $packageBlocked = PackageBlocked::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();

        if($packageBlocked)
        {
            return ['stateAction' => 'validatedFilterPackage', 'packageBlocked' => $packageBlocked, 'packageManifest' => null];
        }
        
        $package = PackagePreDispatch::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();

        if($package)
        {
            return ['stateAction' => 'packageInPreDispatch'];
        }
        
        $servicePackageTerminal = new ServicePackageTerminal();
        $package                = $servicePackageTerminal->Get($request->get('Reference_Number_1'));

        if($package)
        {
            return ['stateAction' => 'packageTerminal'];
        }
        
        $packageLost = PackageLost::find($request->get('Reference_Number_1'));

        if($packageLost)
        {
            return ['stateAction' => 'validatedLost'];
        }

        $packageWarehouse = PackageWarehouse::find($request->get('Reference_Number_1'));

        $stateValidate = $request->get('StateValidate');
        //$stateValidate = $stateValidate != '' ? explode(',', $stateValidate) : [];
        $stateValidate = ['MD', 'DC', 'DE', 'VA', 'PA', 'NJ'];

        if($packageWarehouse)
        {
            if($packageWarehouse->status == 'Warehouse')
            {
                if(count($stateValidate) > 0)
                {
                    if(!in_array($packageWarehouse->Dropoff_Province, $stateValidate))
                    {
                        return ['stateAction' => 'nonValidatedState', 'packageWarehouse' => $packageWarehouse];
                    }
                }

                try
                {
                    DB::beginTransaction();

                    $created_at = date('Y-m-d H:i:s');

                    // update warehouse
                    $packageWarehouse->status     = 'Middle Mile Scan';
                    $packageWarehouse->idUser     = Auth::user()->id;
                    $packageWarehouse->created_at = $created_at;

                    $packageWarehouse->save();

                    $packageHistory = new PackageHistory();

                    $packageHistory->id                           = uniqid();
                    $packageHistory->Reference_Number_1           = $packageWarehouse->Reference_Number_1;
                    $packageHistory->idCompany                    = $packageWarehouse->idCompany;
                    $packageHistory->company                      = $packageWarehouse->company;
                    $packageHistory->idStore                      = $packageWarehouse->idStore;
                    $packageHistory->store                        = $packageWarehouse->store;
                    $packageHistory->Dropoff_Contact_Name         = $packageWarehouse->Dropoff_Contact_Name;
                    $packageHistory->Dropoff_Company              = $packageWarehouse->Dropoff_Company;
                    $packageHistory->Dropoff_Contact_Phone_Number = $packageWarehouse->Dropoff_Contact_Phone_Number;
                    $packageHistory->Dropoff_Contact_Email        = $packageWarehouse->Dropoff_Contact_Email;
                    $packageHistory->Dropoff_Address_Line_1       = $packageWarehouse->Dropoff_Address_Line_1;
                    $packageHistory->Dropoff_Address_Line_2       = $packageWarehouse->Dropoff_Address_Line_2;
                    $packageHistory->Dropoff_City                 = $packageWarehouse->Dropoff_City;
                    $packageHistory->Dropoff_Province             = $packageWarehouse->Dropoff_Province;
                    $packageHistory->Dropoff_Postal_Code          = $packageWarehouse->Dropoff_Postal_Code;
                    $packageHistory->Notes                        = $packageWarehouse->Notes;
                    $packageHistory->Weight                       = $packageWarehouse->Weight;
                    $packageHistory->Route                        = $packageWarehouse->Route;
                    $packageHistory->idUser                       = Auth::user()->id;
                    $packageHistory->Description                  = 'For: '. Auth::user()->name .' '. Auth::user()->nameOfOwner;
                    $packageHistory->quantity                     = $packageWarehouse->quantity;
                    $packageHistory->status                       = 'Middle Mile Scan';
                    $packageHistory->actualDate                   = $created_at;
                    $packageHistory->created_at                   = $created_at;
                    $packageHistory->updated_at                   = $created_at;

                    $packageHistory->save();

                    //data for INLAND
                    $packageController = new PackageController();
                    $packageController->SendStatusToInland($packageWarehouse, 'Middle Mile Scan', null, date('Y-m-d H:i:s'));
                    //end data for inland

                    DB::commit();

                    return ['stateAction' => true, 'packageWarehouse' => $packageWarehouse];
                }
                catch(Exception $e)
                {
                    DB::rollback();

                    return ['stateAction' => true];
                }
            }

            return ['stateAction' => 'packageInWarehouse'];
        }

        $packageInbound = PackageInbound::find($request->get('Reference_Number_1'));

        if($packageInbound)
        {
            if(count($stateValidate) > 0)
            {
                if(!in_array($packageInbound->Dropoff_Province, $stateValidate))
                {
                    return ['stateAction' => 'nonValidatedState', 'packageWarehouse' => $packageInbound];
                }
            }
                
            try
            {
                DB::beginTransaction();

                $created_at = date('Y-m-d H:i:s');

                $packageWarehouse = new PackageWarehouse();

                $packageWarehouse->Reference_Number_1           = $packageInbound->Reference_Number_1;
                $packageWarehouse->idCompany                    = $packageInbound->idCompany;
                $packageWarehouse->company                      = $packageInbound->company;
                $packageWarehouse->idStore                      = $packageInbound->idStore;
                $packageWarehouse->store                        = $packageInbound->store;
                $packageWarehouse->Dropoff_Contact_Name         = $packageInbound->Dropoff_Contact_Name;
                $packageWarehouse->Dropoff_Company              = $packageInbound->Dropoff_Company;
                $packageWarehouse->Dropoff_Contact_Phone_Number = $packageInbound->Dropoff_Contact_Phone_Number;
                $packageWarehouse->Dropoff_Contact_Email        = $packageInbound->Dropoff_Contact_Email;
                $packageWarehouse->Dropoff_Address_Line_1       = $packageInbound->Dropoff_Address_Line_1;
                $packageWarehouse->Dropoff_Address_Line_2       = $packageInbound->Dropoff_Address_Line_2;
                $packageWarehouse->Dropoff_City                 = $packageInbound->Dropoff_City;
                $packageWarehouse->Dropoff_Province             = $packageInbound->Dropoff_Province;
                $packageWarehouse->Dropoff_Postal_Code          = $packageInbound->Dropoff_Postal_Code;
                $packageWarehouse->Notes                        = $packageInbound->Notes;
                $packageWarehouse->Weight                       = $packageInbound->Weight;
                $packageWarehouse->Route                        = $packageInbound->Route;
                $packageWarehouse->idUser                       = Auth::user()->id;
                $packageWarehouse->quantity                     = $packageInbound->quantity;
                $packageWarehouse->status                       = 'Middle Mile Scan';

                $packageWarehouse->save();

                $packageHistory = new PackageHistory();

                $packageHistory->id                           = uniqid();
                $packageHistory->Reference_Number_1           = $packageInbound->Reference_Number_1;
                $packageHistory->idCompany                    = $packageInbound->idCompany;
                $packageHistory->company                      = $packageInbound->company;
                $packageHistory->idStore                      = $packageInbound->idStore;
                $packageHistory->store                        = $packageInbound->store;
                $packageHistory->Dropoff_Contact_Name         = $packageInbound->Dropoff_Contact_Name;
                $packageHistory->Dropoff_Company              = $packageInbound->Dropoff_Company;
                $packageHistory->Dropoff_Contact_Phone_Number = $packageInbound->Dropoff_Contact_Phone_Number;
                $packageHistory->Dropoff_Contact_Email        = $packageInbound->Dropoff_Contact_Email;
                $packageHistory->Dropoff_Address_Line_1       = $packageInbound->Dropoff_Address_Line_1;
                $packageHistory->Dropoff_Address_Line_2       = $packageInbound->Dropoff_Address_Line_2;
                $packageHistory->Dropoff_City                 = $packageInbound->Dropoff_City;
                $packageHistory->Dropoff_Province             = $packageInbound->Dropoff_Province;
                $packageHistory->Dropoff_Postal_Code          = $packageInbound->Dropoff_Postal_Code;
                $packageHistory->Notes                        = $packageInbound->Notes;
                $packageHistory->Weight                       = $packageInbound->Weight;
                $packageHistory->Route                        = $packageInbound->Route;
                $packageHistory->idUser                       = Auth::user()->id;
                $packageHistory->Description                  = 'For: '. Auth::user()->name .' '. Auth::user()->nameOfOwner;
                $packageHistory->quantity                     = $packageInbound->quantity;
                $packageHistory->status                       = 'Middle Mile Scan';
                $packageHistory->actualDate                   = $created_at;
                $packageHistory->created_at                   = $created_at;
                $packageHistory->updated_at                   = $created_at;

                $packageHistory->save();

                $packageInbound->delete();

                //data for INLAND
                $packageController = new PackageController();
                $packageController->SendStatusToInland($packageWarehouse, 'Middle Mile Scan', null, date('Y-m-d H:i:s'));
                //end data for inland
                    
                DB::commit();

                return ['stateAction' => true, 'packageWarehouse' => $packageWarehouse];
            }
            catch(Exception $e)
            {
                DB::rollback();

                return ['stateAction' => true];
            }
        }

        return ['stateAction' => 'notExists'];
    }

    public function ListInDelivery()
    {
        $listPackageMMS = PackageWarehouse::where('status', 'Middle Mile Scan')->get();

        $packagesInDelivery = [];

        foreach($listPackageMMS as $packageMMS)
        {
            $packageDelivery = PackageDispatch::find($packageMMS->Reference_Number_1);

            if($packageDelivery)
            {
                array_push($packagesInDelivery, $packageMMS->Reference_Number_1);
            }

            $packageInbound = PackageInbound::find($packageMMS->Reference_Number_1);

            if($packageInbound)
            {
                array_push($packagesInDelivery, $packageMMS->Reference_Number_1);
            }
        }

        return $packagesInDelivery;
    }

    public function DeleteInDelivery()
    {
        $packagesListInDelivery = $this->ListInDelivery();

        try
        {
            DB::beginTransaction();

            foreach($packagesListInDelivery as $Reference_Number_1)
            {
                $packageMMS = PackageWarehouse::where('status', 'Middle Mile Scan')
                                                    ->where('Reference_Number_1', $Reference_Number_1)
                                                    ->first();

                if($packageMMS)
                {
                    $packageMMS->delete();
                }
            }

            DB::commit();

            return ['message' => "packages deleted"];
        }
        catch(Exception $e)
        {
            DB::rollback();
        }
    }

    public function GetOnfleet($idOnfleet)
    {
        $curl = curl_init("https://onfleet.com/api/v2/tasks/". $idOnfleet);

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);

        $output = json_decode(curl_exec($curl), 1);

        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if($http_status == 200)
        {
            return $output;
        }
        else
        {
            return false;
        }
    }

    public function DeleteOnfleet($idOnfleet)
    {
        $curl = curl_init("https://onfleet.com/api/v2/tasks/". $idOnfleet);

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);

        $output = json_decode(curl_exec($curl), 1);

        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if($http_status == 200)
        {
            return true;
        }
        else
        {
            return false;
        }
    }
}
