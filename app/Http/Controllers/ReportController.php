<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;

use App\Models\{Assigned, Package, PackageDelivery, PackageHistory, PackageInbound, PackageManifest, PackageDispatch, PackageNotExists, User};

use Illuminate\Support\Facades\Validator;

use Shuchkin\SimpleXLSXGen;

use Session;

class ReportController extends Controller
{
    public function Index()
    {        
        return view('report.index');
    }

    public function IndexManifest()
    {        
        return view('report.indexmanifest');
    }

    public function ListManifest($dateInit, $dateEnd, $route, $state)
    {        
        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);

        $listAll = PackageHistory::whereBetween('created_at', [$dateInit, $dateEnd])
                                ->where('status', 'On hold');

        if($route != 'all')
        {
            $listAll = $listAll->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $listAll = $listAll->whereIn('Dropoff_Province', $states);
        }

        $listAll = $listAll->paginate(50);

        $listState = PackageHistory::select('Dropoff_Province')
                                    ->where('status', 'On hold')
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        return ['listAll' => $listAll, 'listState' => $listState];
    }

    public function IndexInbound()
    {        
        return view('report.indexinbound');
    }

    public function ListInbound($dateInit, $dateEnd, $route, $state)
    {        
        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);

        $listAll = PackageHistory::with('validator')
                                ->whereBetween('Date_Inbound', [$dateInit, $dateEnd])
                                ->where('status', 'Inbound');

        if($route != 'all')
        {
            $listAll = $listAll->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $listAll = $listAll->whereIn('Dropoff_Province', $states);
        }

        $listAll = $listAll->orderBy('Date_Inbound', 'desc')->paginate(50);

        $listState = PackageHistory::select('Dropoff_Province')
                                    ->where('status', 'Inbound')
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        return ['listAll' => $listAll, 'listState' => $listState];
    }

    public function IndexDispatch()
    {
        return view('report.indexdispatch');
    }

    public function ListDispatch($dateInit, $dateEnd, $idTeam, $idDriver, $route, $state)
    {
        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $routes = explode(',', $route); 
        $states = explode(',', $state);

        $listAll = PackageHistory::whereBetween('Date_Dispatch', [$dateInit, $dateEnd])
                                    ->where('status', 'Dispatch')
                                    ->where('dispatch', 1);

        if(Session::get('user')->role->name == 'Team')
        {
            $idsUser = User::where('idTeam', $idTeam)->get('id');

            $listAll = $listAll->whereIn('idUserDispatch', $idsUser);
        }
        elseif(Session::get('user')->role->name == 'Driver')
        {
            $idsUser = User::where('idTeam', $idTeam)->get('id');

            $listAll = $listAll->whereIn('idUserDispatch', $idsUser)->orWhere('idUserDispatch', $idTeam);
        }
        else
        {
            if($idTeam && $idDriver)
            {
                $listAll = $listAll->where('idUserDispatch', $idDriver);
            }
            elseif($idTeam)
            {
                $idsUser = User::where('idTeam', $idTeam)->orWhere('id', $idTeam)->get('id');

                $listAll = $listAll->whereIn('idUserDispatch', $idsUser);
            }

            $listAll = $listAll->orderBy('Date_Dispatch', 'desc');
        }

        if($route != 'all')
        {
            $listAll = $listAll->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $listAll = $listAll->whereIn('Dropoff_Province', $states);
        }

        $listAll = $listAll->with(['team', 'driver'])
                            ->orderBy('created_at', 'desc')
                            ->paginate(50);

        $roleUser = Session::get('user')->role->name;

        $listState = PackageHistory::select('Dropoff_Province')
                                    ->where('dispatch', 1)
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        return ['reportList' => $listAll, 'listState' => $listState, 'roleUser' => $roleUser];
    }

    public function IndexDelivery()
    {
        return view('report.indexdelivery');
    }
 
    public function ListDelivery($dateInit, $dateEnd, $idTeam, $idDriver, $route, $state)
    {
        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);

        $listAll = PackageDispatch::whereBetween('updated_at', [$dateInit, $dateEnd])
                                    ->where('status', 'Delivery');

        if(Session::get('user')->role->name == 'Team')
        {
            $idsUser = User::where('idTeam', $idTeam)->get('id');

            $listAll = $listAll->whereIn('idUserDispatch', $idsUser);
        }
        elseif(Session::get('user')->role->name == 'Driver')
        {
            $idsUser = User::where('idTeam', $idTeam)->get('id');

            $listAll = $listAll->whereIn('idUserDispatch', $idsUser)->orWhere('idUserDispatch', $idTeam);
        }
        else
        {
            if($idTeam && $idDriver)
            {
                $listAll = $listAll->where('idUserDispatch', $idDriver);
            }
            elseif($idTeam)
            {
                $idsUser = User::where('idTeam', $idTeam)->orWhere('id', $idTeam)->get('id');

                $listAll = $listAll->whereIn('idUserDispatch', $idsUser);
            }

            $listAll = $listAll->orderBy('updated_at', 'desc');
        }

        if($route != 'all') 
        {
            $listAll = $listAll->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $listAll = $listAll->whereIn('Dropoff_Province', $states);
        }

        $listAll = $listAll->with(['team', 'driver'])
                            ->orderBy('updated_at', 'desc')
                            ->paginate(50);

        $Reference_Number_1s = [];

        foreach($listAll as $delivery)
        {
            array_push($Reference_Number_1s, $delivery->Reference_Number_1);
        }

        $listDeliveries = PackageDelivery::whereIn('taskDetails', $Reference_Number_1s)
                                        ->orderBy('created_at', 'desc')
                                        ->get();

        $roleUser = Session::get('user')->role->name;

        $listState = PackageDispatch::select('Dropoff_Province')
                                    ->where('status', 'Delivery')
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        return ['reportList' => $listAll, 'listDeliveries' => $listDeliveries, 'listState' => $listState, 'roleUser' => $roleUser];
    }


    public function IndexFailed()
    {
        return view('report.indexfailed');
    }

    public function ListFailed($dateInit, $dateEnd, $idTeam, $idDriver, $route, $state)
    {
        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);

        $listAll = PackageHistory::with('driver')
                                ->whereBetween('Date_Failed', [$dateInit, $dateEnd])
                                ->where('status', 'Failed');

        if(Session::get('user')->role->name == 'Team')
        {
            $idsUser = User::where('idTeam', $idTeam)->get('id');

            $listAll = $listAll->whereIn('idUserDispatch', $idsUser);
        }
        elseif(Session::get('user')->role->name == 'Driver')
        {
            $idsUser = User::where('idTeam', $idTeam)->get('id');

            $listAll = $listAll->whereIn('idUserDispatch', $idsUser)->orWhere('idUserDispatch', $idTeam);
        }
        else
        {
            if($idTeam && $idDriver)
            {
                $listAll = $listAll->where('idUserDispatch', $idDriver);
            }
            elseif($idTeam)
            {
                $idsUser = User::where('idTeam', $idTeam)->orWhere('id', $idTeam)->get('id');

                $listAll = $listAll->whereIn('idUserDispatch', $idsUser);
            }

            $listAll = $listAll->orderBy('Date_Failed', 'desc');
        }

        if($route != 'all')
        {
            $listAll = $listAll->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $listAll = $listAll->whereIn('Dropoff_Province', $states);
        }

        $listAll = $listAll->paginate(50);

        $roleUser = Session::get('user')->role->name;

        $listState = PackageHistory::select('Dropoff_Province')
                                    ->where('status', 'Failed')
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        return ['reportList' => $listAll, 'listState' => $listState, 'roleUser' => $roleUser];
    }

    public function IndexNotExists()
    {        
        return view('report.indexnotexists');
    }

    public function ListNotExists($dateInit, $dateEnd)
    {        
        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $reportList = PackageNotExists::whereBetween('created_at', [$dateInit, $dateEnd])
                                        ->orderBy('created_at', 'desc')
                                        ->get();

        return ['reportList' => $reportList];
    }

    public function ExportInbound($dateInit, $dateEnd, $route, $state)
    {
        $delimiter = ",";
        $filename = "Report Inbound " . date('Y-m-d H:i:s') . ".csv";
        
        //create a file pointer
        $file = fopen('php://memory', 'w');
        
        //set column headers
        $fields = array('FECHA', 'HORA', 'VALIDATOR', 'TRUCK #', 'CLIENT', 'PACKAGE ID', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'WEIGHT', 'ROUTE');

        fputcsv($file, $fields, $delimiter);
        
        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';
        
        $routes = explode(',', $route);
        $states = explode(',', $state);

        $listPackageInbound = PackageHistory::with('validator')
                                ->whereBetween('Date_Inbound', [$dateInit, $dateEnd])
                                ->where('status', 'Inbound');

        if($route != 'all')
        {
            $listPackageInbound = $listPackageInbound->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $listPackageInbound = $listPackageInbound->whereIn('Dropoff_Province', $states);
        }

        $listPackageInbound = $listPackageInbound->orderBy('Date_Inbound', 'desc')->get();

        foreach($listPackageInbound as $packageInbound)
        {
            $inbound = [];

            if($packageInbound->validator)
            {
                $validator = $packageInbound->validator->name .' '. $packageInbound->validator->nameOfOwner;
            }
            else
            {
                $validator = '';
            }

            $lineData = array(
                                date('m-d-Y', strtotime($packageInbound->created_at)),
                                date('H:i:s', strtotime($packageInbound->created_at)),
                                $validator,
                                $packageInbound->TRUCK, 
                                $packageInbound->CLIENT,
                                $packageInbound->Reference_Number_1,
                                $packageInbound->Dropoff_Contact_Name,
                                $packageInbound->Dropoff_Contact_Phone_Number,
                                $packageInbound->Dropoff_Address_Line_1,
                                $packageInbound->Dropoff_City,
                                $packageInbound->Dropoff_Province,
                                $packageInbound->Dropoff_Postal_Code,
                                $packageInbound->Weight,
                                $packageInbound->Route
                            );

            fputcsv($file, $lineData, $delimiter);
        }
        
        fseek($file, 0);
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '";');
        
        fpassthru($file);
    }

    public function ExportDispatch($dateInit, $dateEnd, $idTeam, $idDriver, $route, $state)
    {
        $delimiter = ",";
        $filename = "Report Dispatch " . date('Y-m-d H:i:s') . ".csv";
        
        //create a file pointer
        $file = fopen('php://memory', 'w');
        
        //set column headers
        $fields = array('FECHA', 'HORA', 'TEAM', 'DRIVER', 'PACKAGE ID', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'WEIGHT', 'ROUTE');

        fputcsv($file, $fields, $delimiter);
        
        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);

        $listPackageDispatch = PackageHistory::with('driver')
                                ->whereBetween('Date_Dispatch', [$dateInit, $dateEnd])
                                ->where('status', 'Dispatch')
                                ->where('dispatch', 1);

        if(Session::get('user')->role->name == 'Team')
        {
            $idsUser = User::where('idTeam', $idTeam)->get('id');

            $listPackageDispatch = $listPackageDispatch->whereIn('idUserDispatch', $idsUser);
        }
        else
        {
            if($idTeam && $idDriver)
            {
                $listPackageDispatch = $listPackageDispatch->where('idUserDispatch', $idDriver);
            }
            elseif($idTeam)
            {
                $idsUser = User::where('idTeam', $idTeam)->orWhere('id', $idTeam)->get('id');

                $listPackageDispatch = $listPackageDispatch->whereIn('idUserDispatch', $idsUser);
            }

            $listPackageDispatch = $listPackageDispatch->orderBy('Date_Dispatch', 'desc');
        }

        if($route != 'all') 
        {
            $listPackageDispatch = $listPackageDispatch->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $listPackageDispatch = $listPackageDispatch->whereIn('Dropoff_Province', $states);
        }

        $listPackageDispatch = $listPackageDispatch->get();

        foreach($listPackageDispatch as $packageDispatch)
        {
            if($packageDispatch->driver && $packageDispatch->driver->idTeam)
            {
                $team   = $packageDispatch->driver->nameTeam;
                $driver = $packageDispatch->driver->name .' '. $packageDispatch->driver->nameOfOwner;
            }
            else
            {
                $team   = $packageDispatch->driver ? $packageDispatch->driver->name : '';
                $driver = '';
            }

            $lineData = array(
                                date('m-d-Y', strtotime($packageDispatch->Date_Dispatch)),
                                date('H:i:s', strtotime($packageDispatch->Date_Dispatch)),
                                $team,
                                $driver,
                                $packageDispatch->Reference_Number_1,
                                $packageDispatch->Dropoff_Contact_Name,
                                $packageDispatch->Dropoff_Contact_Phone_Number,
                                $packageDispatch->Dropoff_Address_Line_1,
                                $packageDispatch->Dropoff_City,
                                $packageDispatch->Dropoff_Province,
                                $packageDispatch->Dropoff_Postal_Code,
                                $packageDispatch->Weight,
                                $packageDispatch->Route
                            );

            fputcsv($file, $lineData, $delimiter);
        }
        
        fseek($file, 0);
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '";');
        
        fpassthru($file);
    }

    public function ExportDelivery($dateInit, $dateEnd, $idTeam, $idDriver, $route, $state)
    {
        $delimiter = ",";
        $filename = "Report Delivery " . date('Y-m-d H:i:s') . ".csv";
        
        //create a file pointer
        $file = fopen('php://memory', 'w');
        
        //set column headers
        $fields = array('FECHA', 'HORA', 'TEAM', 'DRIVER', 'PACKAGE ID', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'WEIGHT', 'ROUTE', ' URL-IMAGES');

        fputcsv($file, $fields, $delimiter);
        
        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);

        $listPackageDelivery = PackageDispatch::with('driver')
                                ->whereBetween('created_at', [$dateInit, $dateEnd])
                                ->where('status', 'Delivery');

        if(Session::get('user')->role->name == 'Team')
        {
            $idsUser = User::where('idTeam', $idTeam)->get('id');

            $listPackageDelivery = $listPackageDelivery->whereIn('idUserDispatch', $idsUser);
        }
        else
        {
            if($idTeam && $idDriver)
            {
                $listPackageDelivery = $listPackageDelivery->where('idUserDispatch', $idDriver);
            }
            elseif($idTeam)
            {
                $idsUser = User::where('idTeam', $idTeam)->orWhere('id', $idTeam)->get('id');

                $listPackageDelivery = $listPackageDelivery->whereIn('idUserDispatch', $idsUser);
            }

            $listPackageDelivery = $listPackageDelivery->orderBy('Date_Dispatch', 'desc');
        }

        if($route != 'all') 
        {
            $listPackageDelivery = $listPackageDelivery->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $listPackageDelivery = $listPackageDelivery->whereIn('Dropoff_Province', $states);
        }

        $listPackageDelivery = $listPackageDelivery->get();
        
        foreach($listPackageDelivery as $packageDelivery)
        {
            if($packageDelivery->driver && $packageDelivery->driver->idTeam)
            {
                $team   = $packageDelivery->driver->nameTeam;
                $driver = $packageDelivery->driver->name .' '. $packageDelivery->driver->nameOfOwner;
            }
            else
            {
                $team   = $packageDelivery->driver ? $packageDelivery->driver->name : '';
                $driver = '';
            }

            $lineData = array(
                                date('m-d-Y', strtotime($packageDelivery->Date_Dispatch)),
                                date('H:i:s', strtotime($packageDelivery->Date_Dispatch)),
                                $team,
                                $driver,
                                $packageDelivery->Reference_Number_1,
                                $packageDelivery->Dropoff_Contact_Name,
                                $packageDelivery->Dropoff_Contact_Phone_Number,
                                $packageDelivery->Dropoff_Address_Line_1,
                                $packageDelivery->Dropoff_City,
                                $packageDelivery->Dropoff_Province,
                                $packageDelivery->Dropoff_Postal_Code,
                                $packageDelivery->Weight,
                                $packageDelivery->Route,
                                $packageDelivery->photoUrl,
                            );

            fputcsv($file, $lineData, $delimiter);
        }
        
        fseek($file, 0);
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '";');
        
        fpassthru($file);
    }

    public function ExportFailed($dateInit, $dateEnd, $idTeam, $idDriver, $route, $state)
    {
        $delimiter = ",";
        $filename = "Report Failed " . date('Y-m-d H:i:s') . ".csv";
        
        //create a file pointer
        $file = fopen('php://memory', 'w');
        
        //set column headers
        $fields = array('FECHA', 'HORA', 'TEAM', 'DRIVER', 'PACKAGE ID', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'WEIGHT', 'ROUTE');

        fputcsv($file, $fields, $delimiter);
        
        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);

        $listPackageDispatch = PackageHistory::with('driver')
                                ->whereBetween('Date_Failed', [$dateInit, $dateEnd])
                                ->where('status', 'Failed');

        if(Session::get('user')->role->name == 'Team')
        {
            $idsUser = User::where('idTeam', $idTeam)->get('id');

            $listPackageDispatch = $listPackageDispatch->whereIn('idUserDispatch', $idsUser);
        }
        else
        {
            if($idTeam && $idDriver)
            {
                $listPackageDispatch = $listPackageDispatch->where('idUserDispatch', $idDriver);
            }
            elseif($idTeam)
            {
                $idsUser = User::where('idTeam', $idTeam)->orWhere('id', $idTeam)->get('id');

                $listPackageDispatch = $listPackageDispatch->whereIn('idUserDispatch', $idsUser);
            }

            $listPackageDispatch = $listPackageDispatch->orderBy('Date_Failed', 'desc');
        }

        if($route != 'all') 
        {
            $listPackageDispatch = $listPackageDispatch->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $listPackageDispatch = $listPackageDispatch->whereIn('Dropoff_Province', $states);
        }

        $listPackageDispatch = $listPackageDispatch->get();

        foreach($listPackageDispatch as $packageFailed)
        {
            if($packageFailed->driver && $packageFailed->driver->idTeam)
            {
                $team   = $packageFailed->driver->nameTeam;
                $driver = $packageFailed->driver->name .' '. $packageFailed->driver->nameOfOwner;
            }
            else
            {
                $team   = $packageFailed->driver ? $packageFailed->driver->name : '';
                $driver = '';
            }

            $lineData = array(
                                date('m-d-Y', strtotime($packageFailed->Date_Failed)),
                                date('H:i:s', strtotime($packageFailed->Date_Failed)),
                                $team,
                                $driver,
                                $packageFailed->Reference_Number_1,
                                $packageFailed->Dropoff_Contact_Name,
                                $packageFailed->Dropoff_Contact_Phone_Number,
                                $packageFailed->Dropoff_Address_Line_1,
                                $packageFailed->Dropoff_City,
                                $packageFailed->Dropoff_Province,
                                $packageFailed->Dropoff_Postal_Code,
                                $packageFailed->Weight,
                                $packageFailed->Route
                            );

            fputcsv($file, $lineData, $delimiter);
        }
        
        fseek($file, 0);
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '";');
        
        fpassthru($file);
    }

    public function ExportManifest($dateInit, $dateEnd, $route, $state)
    {
        $delimiter = ",";
        $filename = "Report Manifest " . date('Y-m-d H:i:s') . ".csv";
        
        //create a file pointer
        $file = fopen('php://memory', 'w');
        
        //set column headers
        $fields = array('FECHA', 'HORA', 'PACKAGE ID', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'WEIGHT', 'ROUTE');

        fputcsv($file, $fields, $delimiter);
        
        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);

        $listPackageManifest = PackageHistory::whereBetween('created_at', [$dateInit, $dateEnd])->where('status', 'On hold');

        if($route != 'all')
        {
            $listPackageManifest = $listPackageManifest->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $listPackageManifest = $listPackageManifest->whereIn('Dropoff_Province', $states);
        }

        $listPackageManifest = $listPackageManifest->get();

        foreach($listPackageManifest as $packageManifest)
        {

            $lineData = array(
                                date('m-d-Y', strtotime($packageManifest->Date_manifest)),
                                date('H:i:s', strtotime($packageManifest->Date_manifest)),
                                $packageManifest->Reference_Number_1,
                                $packageManifest->Dropoff_Contact_Name,
                                $packageManifest->Dropoff_Contact_Phone_Number,
                                $packageManifest->Dropoff_Address_Line_1,
                                $packageManifest->Dropoff_City,
                                $packageManifest->Dropoff_Province,
                                $packageManifest->Dropoff_Postal_Code,
                                $packageManifest->Weight,
                                $packageManifest->Route
                            );

            fputcsv($file, $lineData, $delimiter);
        }
        
        fseek($file, 0);
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '";');
        
        fpassthru($file);
    }

    public function ExportNotExists($dateInit, $dateEnd)
    {
        $delimiter = ",";
        $filename = "Report Not Exists " . date('Y-m-d H:i:s') . ".csv";
        
        //create a file pointer
        $file = fopen('php://memory', 'w');
        
        //set column headers
        $fields = array('FECHA', 'HORA', 'PACKAGE ID');

        fputcsv($file, $fields, $delimiter);


        $listPackageNotExists = PackageNotExists::whereBetween('Date_Inbound', [$dateInit, $dateEnd])
                                ->orderBy('Date_Inbound', 'desc')
                                ->get();

        foreach($listPackageNotExists as $packageNotExists)
        {
            $lineData = array(
                                date('m-d-Y', strtotime($packageNotExists->Date_Inbound)),
                                date('H:i:s', strtotime($packageNotExists->Date_Inbound)),
                                $packageNotExists->Reference_Number_1
                            );

            fputcsv($file, $lineData, $delimiter);
        }
        
        fseek($file, 0);
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '";');
        
        fpassthru($file);
    }

    public function IndexAssigns()
    {        
        return view('report.indexassigns');
    }

    public function ListAssigns($dateInit, $dateEnd, $route, $state)
    {        
        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);

        $listAll = Assigned::whereBetween('assignedDate', [$dateInit, $dateEnd])
                                ->where('idDriver', '!=', 0);

        if($route != 'all')
        {
            $listAll = $listAll->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $listAll = $listAll->whereIn('Dropoff_Province', $states);
        }

        $listAll = $listAll->paginate(50);

        $listState = Assigned::select('Dropoff_Province')
                                    ->where('idDriver', '!=', 0)
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        return ['listAll' => $listAll, 'listState' => $listState];
    }

    public function ExportAssigns($dateInit, $dateEnd, $route, $state)
    {
        $delimiter = ",";
        $filename = "Report Assigns " . date('Y-m-d H:i:s') . ".csv";
        
        //create a file pointer
        $file = fopen('php://memory', 'w');
        
        //set column headers
        $fields = array('FECHA', 'HORA', 'PACKAGE ID', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'WEIGHT', 'ROUTE');

        fputcsv($file, $fields, $delimiter);
        
        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);

        $ListAssigns = Assigned::whereBetween('created_at', [$dateInit, $dateEnd])->where('idDriver', '!=', 0);

        if($route != 'all')
        {
            $ListAssigns = $ListAssigns->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $ListAssigns = $ListAssigns->whereIn('Dropoff_Province', $states);
        }

        $ListAssigns = $ListAssigns->get();

        foreach($ListAssigns as $assign)
        {

            $lineData = array(
                                date('m-d-Y', strtotime($assign->Date_manifest)),
                                date('H:i:s', strtotime($assign->Date_manifest)),
                                $assign->Reference_Number_1,
                                $assign->Dropoff_Contact_Name,
                                $assign->Dropoff_Contact_Phone_Number,
                                $assign->Dropoff_Address_Line_1,
                                $assign->Dropoff_City,
                                $assign->Dropoff_Province,
                                $assign->Dropoff_Postal_Code,
                                $assign->Weight,
                                $assign->Route
                            );

            fputcsv($file, $lineData, $delimiter);
        }
        
        fseek($file, 0);
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '";');
        
        fpassthru($file);
    }
}