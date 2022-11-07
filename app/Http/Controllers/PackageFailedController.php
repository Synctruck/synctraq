<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\{ Assigned, AuxDispatchUser, Comment, Configuration, Driver, PackageHistory, PackageBlocked, PackageDispatch, PackageFailed, PackageInbound, PackageManifest, PackageNotExists, PackageReturn, PackageReturnCompany, PackageWarehouse, TeamRoute, User };

use DB;
use Log;
use Session;

class PackageFailedController extends Controller
{
    public function Index()
    {
        return view('package.failed');
    }

    public function List(Request $request, $dateStart,$dateEnd, $idTeam, $idDriver, $state, $routes)
    {
        $packageFailedList = $this->getDataDispatch($dateStart,$dateEnd, $idTeam, $idDriver, $state, $routes);
        $quantityFailed    = $packageFailedList->total();

        $roleUser = Auth::user()->role->name;

        return ['packageFailedList' => $packageFailedList, 'quantityFailed' => $quantityFailed, 'roleUser' => $roleUser]; 
    }

    private function getDataDispatch($dateStart,$dateEnd, $idTeam, $idDriver, $state, $routes,$type='list')
    {
        $dateStart = $dateStart .' 00:00:00';
        $dateEnd   = $dateEnd .' 23:59:59';

        $packageDispatchList = PackageFailed::whereBetween('created_at', [$dateStart, $dateEnd])
                                                ->where('status', 'Failed');

        if($idTeam && $idDriver)
        {
            $packageDispatchList = $packageDispatchList->where('idUserDispatch', $idDriver);
        }
        elseif($idTeam)
        {
            $packageDispatchList = $packageDispatchList->where('idTeam', $idTeam);
        }
        elseif($idDriver)
        {
            $packageDispatchList = $packageDispatchList->where('idUserDispatch', $idDriver);
        }

        if($state != 'all')
        {
            $state = explode(',', $state);

            $packageDispatchList = $packageDispatchList->whereIn('Dropoff_Province', $state);
        }

        if($routes != 'all')
        {
            $routes = explode(',', $routes);

            $packageDispatchList = $packageDispatchList->whereIn('Route', $routes);
        }

        if($type == 'list'){
            $packageDispatchList = $packageDispatchList->with(['team', 'driver'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);
        }else{
            $packageDispatchList = $packageDispatchList->orderBy('created_at', 'desc')->get();
        }

        return  $packageDispatchList;

    }

    public function Export(Request $request, $dateStart,$dateEnd, $idTeam, $idDriver, $state, $routes)
    {
        $delimiter = ",";
        $filename = "PACKAGES - DISPATCH " . date('Y-m-d H:i:s') . ".csv";

        //create a file pointer
        $file = fopen('php://memory', 'w');

        //set column headers
        $fields = array('DATE' ,'HOUR', 'TEAM', 'DRIVER', 'PACKAGE ID', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'WEIGHT', 'ROUTE','TASK ONFLEET');

        fputcsv($file, $fields, $delimiter);


        $packageDispatchList = $this->getDataDispatch($dateStart,$dateEnd, $idTeam, $idDriver, $state, $routes,$type = 'export');

       foreach($packageDispatchList as $packageDispatch)
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
                $packageDispatch->Route,
                $packageDispatch->packageDispatch
            );

            fputcsv($file, $lineData, $delimiter);
        }

        fseek($file, 0);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '";');

        fpassthru($file);
    }
}