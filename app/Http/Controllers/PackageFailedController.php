<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\{ AuxDispatchUser, Comment, Configuration, Driver, PackageHistory, PackageBlocked, PackageDispatch, PackageFailed, PackagePreFailed, PackageInbound, PackageManifest, PackageNotExists, PackageReturn, PackageReturnCompany, PackageWarehouse, TeamRoute, User };

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

    public function MovePreFailedToFailed()
    {
        /*Log::info("============================================================");
        Log::info("==========SCHEDULE TASK MOVE PRE-FAILED TO FAILED ==========");*/

        try
        {
            DB::beginTransaction();

            $listPackagePreFailed = PackagePreFailed::all();

            foreach($listPackagePreFailed as $packagePreFailed)
            {
                $packagePreFailed = PackagePreFailed::find($packagePreFailed->taskOnfleet);
                $packageDispatch  = PackageDispatch::where('taskOnfleet', $packagePreFailed->taskOnfleet)->first();
                
                if($packageDispatch)
                {
                    $Description_Onfleet = $packagePreFailed->Description_Onfleet;
                    //$user = User::find($packageDispatch->idUserDispatch);

                    //$description = $user ? 'For: Driver '. $user->name .' '. $user->nameOfOwner : 'Driver not exists';

                    $packageFailed = new PackageFailed();

                    $packageFailed->Reference_Number_1           = $packageDispatch->Reference_Number_1;
                    $packageFailed->idCompany                    = $packageDispatch->idCompany;
                    $packageFailed->company                      = $packageDispatch->company;
                    $packageFailed->idStore                      = $packageDispatch->idStore;
                    $packageFailed->store                        = $packageDispatch->store;
                    $packageFailed->Dropoff_Contact_Name         = $packageDispatch->Dropoff_Contact_Name;
                    $packageFailed->Dropoff_Company              = $packageDispatch->Dropoff_Company;
                    $packageFailed->Dropoff_Contact_Phone_Number = $packageDispatch->Dropoff_Contact_Phone_Number;
                    $packageFailed->Dropoff_Contact_Email        = $packageDispatch->Dropoff_Contact_Email;
                    $packageFailed->Dropoff_Address_Line_1       = $packageDispatch->Dropoff_Address_Line_1;
                    $packageFailed->Dropoff_Address_Line_2       = $packageDispatch->Dropoff_Address_Line_2;
                    $packageFailed->Dropoff_City                 = $packageDispatch->Dropoff_City;
                    $packageFailed->Dropoff_Province             = $packageDispatch->Dropoff_Province;
                    $packageFailed->Dropoff_Postal_Code          = $packageDispatch->Dropoff_Postal_Code;
                    $packageFailed->Notes                        = $packageDispatch->Notes;
                    $packageFailed->Weight                       = $packageDispatch->Weight;
                    $packageFailed->Route                        = $packageDispatch->Route;
                    $packageFailed->idTeam                       = $packageDispatch->idTeam;
                    $packageFailed->idUserDispatch               = $packageDispatch->idUserDispatch;
                    $packageFailed->idUser                       = $packageDispatch->idUserDispatch;
                    $packageFailed->Description_Onfleet          = $Description_Onfleet;
                    $packageFailed->idOnfleet                    = $packageDispatch->idOnfleet;
                    $packageFailed->taskOnfleet                  = $packageDispatch->taskOnfleet;
                    $packageFailed->quantity                     = $packageDispatch->quantity;
                    $packageFailed->status                       = 'Failed';
                    $packageFailed->created_at                   = $packagePreFailed->created_at;
                    $packageFailed->updated_at                   = $packagePreFailed->created_at;

                    $packageFailed->save();

                    $packageHistory = new PackageHistory();

                    $packageHistory->id                           = uniqid();
                    $packageHistory->Reference_Number_1           = $packageDispatch->Reference_Number_1;
                    $packageHistory->idCompany                    = $packageDispatch->idCompany;
                    $packageHistory->company                      = $packageDispatch->company;
                    $packageHistory->idStore                      = $packageDispatch->idStore;
                    $packageHistory->store                        = $packageDispatch->store;
                    $packageHistory->Dropoff_Contact_Name         = $packageDispatch->Dropoff_Contact_Name;
                    $packageHistory->Dropoff_Company              = $packageDispatch->Dropoff_Company;
                    $packageHistory->Dropoff_Contact_Phone_Number = $packageDispatch->Dropoff_Contact_Phone_Number;
                    $packageHistory->Dropoff_Contact_Email        = $packageDispatch->Dropoff_Contact_Email;
                    $packageHistory->Dropoff_Address_Line_1       = $packageDispatch->Dropoff_Address_Line_1;
                    $packageHistory->Dropoff_Address_Line_2       = $packageDispatch->Dropoff_Address_Line_2;
                    $packageHistory->Dropoff_City                 = $packageDispatch->Dropoff_City;
                    $packageHistory->Dropoff_Province             = $packageDispatch->Dropoff_Province;
                    $packageHistory->Dropoff_Postal_Code          = $packageDispatch->Dropoff_Postal_Code;
                    $packageHistory->Notes                        = $packageDispatch->Notes;
                    $packageHistory->Weight                       = $packageDispatch->Weight;
                    $packageHistory->Route                        = $packageDispatch->Route;
                    $packageHistory->idTeam                       = $packageDispatch->idTeam;
                    $packageHistory->idUserDispatch               = $packageDispatch->idUserDispatch;
                    $packageHistory->idUser                       = $packageDispatch->idUserDispatch;
                    $packageHistory->Description_Onfleet          = $Description_Onfleet;
                    $packageHistory->quantity                     = $packageDispatch->quantity;
                    $packageHistory->status                       = 'Failed';
                    $packageHistory->actualDate                   = $packagePreFailed->created_at;
                    $packageHistory->created_at                   = $packagePreFailed->created_at;
                    $packageHistory->updated_at                   = $packagePreFailed->created_at;

                    $packageHistory->save();
                    
                    $packagePreFailed->delete();
                    $packageDispatch->delete();
                }
            }

            /*Log::info("==================== CORRECT SCHEDULE TASK MOVE PRE-FAILED TO FAILED");
            Log::info("============================================================");*/

            DB::commit();

            return "==================== CORRECT SCHEDULE TASK MOVE PRE-FAILED TO FAILED";
        }
        catch(Exception $e)
        {
            DB::rollback();

            return "ROLLBACK SCHEDULE TASK MOVE PRE-FAILED TO FAILED";
            /*Log::info("==================== ROLLBACK SCHEDULE TASK MOVE PRE-FAILED TO FAILED");
            Log::info("============================================================");*/
        }
    }
}