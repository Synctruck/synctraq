<?php
namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;

use App\Models\{Assigned, Package, PackageDelivery, PackageHistory, PackageInbound, PackageManifest, PackageDispatch, PackageNotExists, User};
use Illuminate\Support\Facades\Auth;
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
        return view('partner.report.manifest');
    }

    public function ListManifest($dateInit, $dateEnd, $route, $state)
    {

        $listAll = $this->getDataManifest($dateInit, $dateEnd, $route, $state);

        $listState = PackageHistory::select('Dropoff_Province')
                                    ->where('status', 'On hold')
                                    ->where('idCompany',Auth::guard('partner')->user()->id)
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        return ['listAll' => $listAll, 'listState' => $listState];
    }

    private function getDataManifest($dateInit, $dateEnd, $route, $state, $type = 'list')
    {
        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);

        $listAll = PackageHistory::whereBetween('created_at', [$dateInit, $dateEnd])->where('status', 'On hold');

        if($route != 'all')
        {
            $listAll = $listAll->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $listAll = $listAll->whereIn('Dropoff_Province', $states);
        }

        if($type == 'list')
        {
            $listAll = $listAll->paginate(50);
        }
        else
        {
            $listAll = $listAll->get();
        }

        return $listAll;
    }

    public function IndexInbound()
    {
        return view('report.indexinbound');
    }

    public function ListInbound($idCompany, $dateInit, $dateEnd, $route, $state, $truck)
    {
        $listAll = $this->getDataInbound($idCompany, $dateInit, $dateEnd, $route, $state, $truck);

        $listState = PackageHistory::select('Dropoff_Province')
                                    ->where('status', 'Inbound')
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        $listTruck = PackageHistory::select('TRUCK')
                                    ->where('status', 'Inbound')
                                    ->groupBy('TRUCK')
                                    ->get();

        return ['listAll' => $listAll, 'listState' => $listState,'listTruck'=>$listTruck];
    }

    private function getDataInbound($idCompany, $dateInit, $dateEnd, $route, $state, $truck, $type = 'list')
    {
        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);
        $trucks = explode(',', $truck);

        $listAll = PackageHistory::with(
                                [
                                    'validator'=> function($query){ $query->select('id', 'name', 'nameOfOwner'); },

                                ])
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

        if($truck != 'all')
        {
            $listAll = $listAll->whereIn('TRUCK', $trucks);
        }

        if($idCompany && $idCompany !=0)
        {
            $listAll = $listAll->where('idCompany', $idCompany);
        }

        if($type =='list')
        {
            $listAll = $listAll->orderBy('Date_Inbound', 'desc')->paginate(50);
        }
        else
        {
            $listAll = $listAll->orderBy('Date_Inbound', 'desc')->get();
        }

        return $listAll;
    }

    public function IndexDispatch()
    {
        return view('report.indexdispatch');
    }

    public function ListDispatch($idCompany,$dateInit, $dateEnd, $idTeam, $idDriver, $route, $state)
    {
        $listPackageDispatch = $this->getDataDispatch($idCompany,$dateInit, $dateEnd, $idTeam, $idDriver, $route, $state);

        $roleUser = Auth::user()->role->name;
        $idUser   = Auth::user()->id;

        $listState = PackageHistory::select('Dropoff_Province')
                                    ->where('dispatch', 1)
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        return ['reportList' => $listPackageDispatch, 'listState' => $listState, 'roleUser' => $roleUser,'idUser'=>$idUser];
    }

    private function getDataDispatch($idCompany,$dateInit, $dateEnd, $idTeam, $idDriver, $route, $state, $type = 'list')
    {
        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);

        $listPackageDispatch = PackageDispatch::whereBetween('created_at', [$dateInit, $dateEnd]);

        if($idTeam && $idDriver)
        {
            $listPackageDispatch = $listPackageDispatch->where('idTeam', $idTeam)
                                                        ->where('idUserDispatch', $idDriver);
        }
        elseif($idTeam)
        {
            $listPackageDispatch = $listPackageDispatch->where('idTeam', $idTeam);
        }
        elseif($idDriver)
        {
            $listPackageDispatch = $listPackageDispatch->where('idUserDispatch', $idDriver);
        }

        if($route != 'all')
        {
            $listPackageDispatch = $listPackageDispatch->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $listPackageDispatch = $listPackageDispatch->whereIn('Dropoff_Province', $states);
        }

        if($idCompany && $idCompany != 0)
        {
            $listPackageDispatch = $listPackageDispatch->where('idCompany', $idCompany);
        }

        if($type == 'list')
        {
            $listPackageDispatch = $listPackageDispatch->with(['team', 'driver'])
                                                            ->orderBy('created_at', 'desc')
                                                            ->paginate(50);
        }
        else
        {
            $listPackageDispatch = $listPackageDispatch->with(['team', 'driver'])
                                                        ->orderBy('created_at', 'desc')
                                                        ->get();
        }

        return $listPackageDispatch;
    }

    public function IndexDelivery()
    {
        return view('report.indexdelivery');
    }

    public function ListDelivery($dateInit, $dateEnd, $idTeam, $idDriver, $route, $state)
    {
        $Reference_Number_1s = [];

        $listAll = $this->getDataDelivery($dateInit, $dateEnd, $idTeam, $idDriver, $route, $state);

        foreach($listAll as $delivery)
        {
            array_push($Reference_Number_1s, $delivery->Reference_Number_1);
        }

        $listDeliveries = PackageDelivery::whereIn('taskDetails', $Reference_Number_1s)
                                        ->orderBy('created_at', 'desc')
                                        ->get();

        $roleUser = Auth::user()->role->name;

        $listState = PackageDispatch::select('Dropoff_Province')
                                    ->where('status', 'Delivery')
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        return ['reportList' => $listAll, 'listDeliveries' => $listDeliveries, 'listState' => $listState, 'roleUser' => $roleUser];
    }

    private function getDataDelivery($dateInit, $dateEnd, $idTeam, $idDriver, $route, $state,$type='list'){

        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);

        $listAll = PackageDispatch::whereBetween('Date_Delivery', [$dateInit, $dateEnd])->where('status', 'Delivery');

        if($idTeam && $idDriver)
        {
            $listAll = $listAll->where('idTeam', $idTeam)->where('idUserDispatch', $idDriver);
        }
        elseif($idTeam)
        {
            $listAll = $listAll->where('idTeam', $idTeam);
        }
        elseif($idDriver)
        {
            $listAll = $listAll->where('idUserDispatch', $idDriver);
        }

        if($route != 'all')
        {
            $listAll = $listAll->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $listAll = $listAll->whereIn('Dropoff_Province', $states);
        }

        if($type == 'list')
        {
            $listAll = $listAll->with(['team', 'driver'])->orderBy('Date_Delivery', 'desc')->paginate(50);
        }
        else
        {
            $listAll = $listAll->with(['team', 'driver'])->orderBy('Date_Delivery', 'desc')->get();
        }

        return $listAll;
    }

    public function IndexFailed()
    {
        return view('report.indexfailed');
    }

    public function ListFailed($idCompany, $dateInit, $dateEnd, $idTeam, $idDriver, $route, $state)
    {
        $listPackageFailed = $this->getDataFailed($idCompany, $dateInit, $dateEnd, $idTeam, $idDriver, $route, $state);

        $roleUser = Auth::user()->role->name;
        $idUser   = Auth::user()->id;

        $listState = PackageHistory::select('Dropoff_Province')
                                    ->where('status', 'Failed')
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        return ['reportList' => $listPackageFailed, 'listState' => $listState, 'roleUser' => $roleUser, 'idUser' => $idUser];
    }

    private function getDataFailed($idCompany, $dateInit, $dateEnd, $idTeam, $idDriver, $route, $state, $type = 'list')
    {
        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);

        $listPackageFailed = PackageHistory::whereBetween('created_at', [$dateInit, $dateEnd])->where('status', 'Failed');

        if($idTeam && $idDriver)
        {
            $listPackageFailed = $listPackageFailed->where('idTeam', $idTeam)->where('idUserDispatch', $idDriver);
        }
        elseif($idTeam)
        {
            $listPackageFailed = $listPackageFailed->where('idTeam', $idTeam);
        }
        elseif($idDriver)
        {
            $listPackageFailed = $listPackageFailed->where('idUserDispatch', $idDriver);
        }

        if($route != 'all')
        {
            $listPackageFailed = $listPackageFailed->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $listPackageFailed = $listPackageFailed->whereIn('Dropoff_Province', $states);
        }

        if($idCompany && $idCompany !=0)
        {
            $listPackageFailed = $listPackageFailed->where('idCompany', $idCompany);
        }

        if($type == 'list')
        {
            $listPackageFailed = $listPackageFailed->with(['team', 'driver'])
                                                            ->orderBy('created_at', 'desc')
                                                            ->paginate(50);
        }
        else
        {
            $listPackageFailed = $listPackageFailed->with(['team', 'driver'])
                                                        ->orderBy('created_at', 'desc')
                                                        ->get();
        }

        return $listPackageFailed;
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

    public function ExportInbound($idCompany, $dateInit, $dateEnd, $route, $state,$truck)
    {
        $delimiter = ",";
        $filename = "Report Inbound " . date('Y-m-d H:i:s') . ".csv";

        //create a file pointer
        $file = fopen('php://memory', 'w');

        //set column headers
        $fields = array('DATE', 'HOUR', 'COMPANY', 'VALIDATOR', 'TRUCK #', 'PACKAGE ID', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'WEIGHT', 'ROUTE');

        fputcsv($file, $fields, $delimiter);


        $listPackageInbound = $this->getDataInbound($idCompany, $dateInit, $dateEnd, $route, $state, $truck, $type = 'export');

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
                                $packageInbound->company,
                                $validator,
                                $packageInbound->TRUCK,
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

    public function ExportDispatch($idCompany, $dateInit, $dateEnd, $idTeam, $idDriver, $route, $state)
    {
        $delimiter = ",";
        $filename = "Report Dispatch " . date('Y-m-d H:i:s') . ".csv";

        //create a file pointer
        $file = fopen('php://memory', 'w');

        //set column headers
        $fields = array('DATE', 'HOUR', 'COMPANY', 'TEAM', 'DRIVER', 'PACKAGE ID', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'WEIGHT', 'ROUTE', 'TASK ONFLEET');

        fputcsv($file, $fields, $delimiter);

        $listPackageDispatch = $this->getDataDispatch($idCompany,$dateInit, $dateEnd, $idTeam, $idDriver, $route, $state, $type = 'export');

        foreach($listPackageDispatch as $packageDispatch)
        {
            $team   = isset($packageDispatch->team) ? $packageDispatch->team->name : '';
            $driver = isset($packageDispatch->driver) ? $packageDispatch->driver->name .' '. $packageDispatch->driver->nameOfOwner : '';

            $lineData = array(
                                date('m-d-Y', strtotime($packageDispatch->created_at)),
                                date('H:i:s', strtotime($packageDispatch->created_at)),
                                $packageDispatch->company,
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
                                $packageDispatch->Route,
                                $packageDispatch->taskOnfleet,
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
        $fields = array('DATE', 'HOUR', 'TEAM', 'DRIVER', 'PACKAGE ID', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'WEIGHT', 'ROUTE', ' URL-IMAGES');

        fputcsv($file, $fields, $delimiter);

        $listPackageDelivery = $this->getDataDelivery($dateInit, $dateEnd, $idTeam, $idDriver, $route, $state,$type='export');


        foreach($listPackageDelivery as $packageDelivery)
        {
            $team   = isset($packageDelivery->team) ? $packageDelivery->team->name : '';
            $driver = isset($packageDelivery->driver) ? $packageDelivery->driver->name .' '. $packageDelivery->driver->nameOfOwner : '';

            $lineData = array(
                                date('m-d-Y', strtotime($packageDelivery->Date_Delivery)),
                                date('H:i:s', strtotime($packageDelivery->Date_Delivery)),
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

    public function ExportFailed($idCompany, $dateInit, $dateEnd, $idTeam, $idDriver, $route, $state)
    {
        $delimiter = ",";
        $filename = "Report Failed " . date('Y-m-d H:i:s') . ".csv";

        //create a file pointer
        $file = fopen('php://memory', 'w');

        //set column headers
        $fields = array('DATE', 'HOUR', 'COMPANY', 'TEAM', 'DRIVER', 'PACKAGE ID', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'WEIGHT', 'ROUTE');

        fputcsv($file, $fields, $delimiter);

        $listPackageFailed = $this->getDataFailed($idCompany,$dateInit, $dateEnd, $idTeam, $idDriver, $route, $state, $type = 'export');

        foreach($listPackageFailed as $packageFailed)
        {
            $team   = isset($packageFailed->team) ? $packageFailed->team->name : '';
            $driver = isset($packageFailed->driver) ? $packageFailed->driver->name .' '. $packageFailed->driver->nameOfOwner : '';

            $lineData = array(
                                date('m-d-Y', strtotime($packageFailed->created_at)),
                                date('H:i:s', strtotime($packageFailed->created_at)),
                                $packageFailed->company,
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
        $fields = array('DATE', 'HOUR', 'PACKAGE ID', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'WEIGHT', 'ROUTE');

        fputcsv($file, $fields, $delimiter);


        $listPackageManifest = $this->getDataManifest($dateInit, $dateEnd, $route, $state,$type='export');

        foreach($listPackageManifest as $packageManifest)
        {

            $lineData = array(
                                date('m-d-Y', strtotime($packageManifest->created_at)),
                                date('H:i:s', strtotime($packageManifest->created_at)),
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
        $fields = array('DATE', 'HOUR', 'PACKAGE ID');

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
        $fields = array('DATE', 'HOUR', 'PACKAGE ID', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'WEIGHT', 'ROUTE');

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
