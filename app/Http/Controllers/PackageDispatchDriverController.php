<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use \App\Service\{ ServicePackageTerminal, ServicePackageNeedMoreInformation };

use App\Models\{ AuxDispatchUser, Comment, Company, Configuration, DimFactorTeam, Driver, PackageHistory, PackageHighPriority, PackageBlocked, PackageDispatchDriver,  PackageFailed, PackageInbound, PackageLost, PackageManifest, PackageNotExists, PackagePreDispatch, PackagePriceCompanyTeam, PackageReturn, PackageReturnCompany, PackageWarehouse, PaymentTeamReturn, TeamRoute, User };

use App\Http\Controllers\Api\PackageController;
use App\Http\Controllers\{ RangePriceTeamRouteCompanyController, TeamController };

use DB;
use Log;
use Session;

class PackageDispatchDriverController extends Controller
{
    public function Index()
    {
        return view('package.dispatchdriver');
    }

    public function List(Request $request, $idCompany, $dateStart,$dateEnd, $idTeam, $idDriver, $state, $routes)
    {
        $packageDispatchList = $this->getDataDispatch($idCompany, $dateStart,$dateEnd, $idTeam, $idDriver, $state, $routes);
        $getDataDispatchAll  = $this->getDataDispatchAll($idCompany, $idTeam, $idDriver);

        $quantityDispatch     = $packageDispatchList->total();
        $quantityDispatchAll  = $getDataDispatchAll->count();
        $quantityHighPriority = PackageHighPriority::get()->count();

        if($idTeam != 0)
        {
            $quantityFailed = PackageFailed::where('idTeam', $idTeam)->get()->count();
        }
        else
        {
            $quantityFailed = PackageFailed::get()->count();
        }

        $roleUser = Auth::user()->role->name;

        $listState = PackageDispatch::select('Dropoff_Province')
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        return [
            'packageDispatchList' => $packageDispatchList,
            'quantityDispatch' => $quantityDispatch,
            'quantityDispatchAll' => $quantityDispatchAll,
            'quantityFailed' => $quantityFailed,
            'quantityHighPriority' => $quantityHighPriority,
            'roleUser' => $roleUser,
            'listState' => $listState
        ]; 
    }

    private function getDataDispatch($idCompany, $dateStart,$dateEnd, $idTeam, $idDriver, $state, $routes,$type='list')
    {
        $dateStart = $dateStart .' 00:00:00';
        $dateEnd   = $dateEnd .' 23:59:59';

        $packageDispatchList = PackageDispatchDriver::whereBetween('created_at', [$dateStart, $dateEnd])
                                                ->where('status', 'DispatchDriver');

        if($idCompany != 0)
        {
            $packageDispatchList = $packageDispatchList->where('idCompany', $idCompany);
        }

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

        if($type == 'list')
        {
            $packageDispatchList = $packageDispatchList->with(['team', 'driver'])
                                                    ->orderBy('created_at', 'desc')
                                                    ->select(
                                                        'created_at',
                                                        'idTeam',
                                                        'idUserDispatch',
                                                        'company',
                                                        'Reference_Number_1',
                                                        'Dropoff_Contact_Name',
                                                        'Dropoff_Contact_Phone_Number',
                                                        'Dropoff_Address_Line_1',
                                                        'Dropoff_City',
                                                        'Dropoff_Province',
                                                        'Dropoff_Postal_Code',
                                                        'Weight',
                                                        'Route',
                                                        'taskOnfleet'
                                                    )
                                                    ->paginate(50);
        }
        else
        {
            $packageDispatchList = $packageDispatchList->orderBy('created_at', 'desc')->get();
        }

        return  $packageDispatchList;

    }

    private function getDataDispatchAll($idCompany, $idTeam, $idDriver)
    {
        $startDate = date('Y-m-d') .' 00:00:00';
        $endDate   = date('Y-m-d') .' 23:59:59';

        $packageDispatchList = PackageDispatchDriver::where('status', 'DispatchDriver')
                                                ->whereNotBetween('created_at', [$startDate, $endDate]);

        if($idCompany != 0)
        {
            $packageDispatchList = $packageDispatchList->where('idCompany', $idCompany);
        }

        if($idTeam && $idDriver)
        {
            $packageDispatchList = $packageDispatchList->where('idTeam', $idTeam)
                                                        ->where('idUserDispatch', $idDriver);
        }
        elseif($idTeam)
        {
            $packageDispatchList = $packageDispatchList->where('idTeam', $idTeam);
        }
        elseif($idDriver)
        {
            $packageDispatchList = $packageDispatchList->where('idUserDispatch', $idDriver);
        }

        $packageDispatchList = $packageDispatchList->orderBy('created_at', 'desc')->get();

        return  $packageDispatchList;
    }

    public function Export(Request $request, $idCompany, $dateStart,$dateEnd, $idTeam, $idDriver, $state, $routes, $typeExport)
    {
        $delimiter = ",";
        $filename  = $typeExport == 'download' ? "PACKAGES - DISPATCH " . date('Y-m-d H:i:s') . ".csv" : Auth::user()->id ."- PACKAGES - DISPATCH.csv";
        $file      = $typeExport == 'download' ? fopen('php://memory', 'w') : fopen(public_path($filename), 'w');

        //set column headers
        $fields = array('DATE' ,'HOUR', 'COMPANY', 'TEAM', 'DRIVER', 'PACKAGE ID', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'WEIGHT', 'ROUTE','TASK ONFLEET');

        fputcsv($file, $fields, $delimiter);


        $packageDispatchList = $this->getDataDispatch($idCompany, $dateStart,$dateEnd, $idTeam, $idDriver, $state, $routes,$type ='export');

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

            SendGeneralExport('Packages Dispatch', $filename);

            return ['stateAction' => true];
        }
    }

    public function GetAll()
    {
        $listPackageDispatch = PackageDispatchDriver::where('status', 'DispatchDriver')
                                                ->where('idOnfleet', '!=', '')
                                                ->inRandomOrder()
                                                ->get()
                                                ->take(300);

        return ['listPackageDispatch' => $listPackageDispatch];
    }

    public function Insert(Request $request)
    {
        $validateDispatch = false;

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

        $servicePackageTerminal = new ServicePackageNeedMoreInformation();
        $package                = $servicePackageTerminal->Get($request->get('Reference_Number_1'));

        if($package)
        {
            return ['stateAction' => 'packageNMI'];
        }

        $packageLost = PackageLost::find($request->get('Reference_Number_1'));

        if($packageLost)
        {
            return ['stateAction' => 'validatedLost'];
        }

        $package = PackageInbound::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();

        if(!$package)
        {
           $package = PackageManifest::with('blockeds')
                                    ->where('Reference_Number_1', $request->get('Reference_Number_1'))
                                    ->first();

            if($package)
            {
                if($package->filter || count($package->blockeds) > 0)
                {
                    return ['stateAction' => 'validatedFilterPackage', 'packageManifest' => $package, 'packageBlocked' => null];
                }
            }
        }

        if(!$package)
        {
           $package = PackageWarehouse::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();
        }

        if(!$package)
        {
            $package = PackageDispatch::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();

            if($package)
            {
                if($package->status != 'Delete')
                {
                    return ['stateAction' => 'packageInDispatch', 'packageManifest' => $package, 'packageStatus' => $package->status];
                }
            }
        }

        if($package)
        {
            if($request->get('idTeam') && $request->get('idDriver'))
            {
                $team           = User::find($request->get('idTeam'));
                $driver         = User::find($request->get('idDriver'));
                $idUserDispatch = $request->get('idDriver');

                $description = 'To: '. $team->name .' / '. $driver->name .' '. $driver->nameOfOwner;

                try
                {
                    DB::beginTransaction();

                    $nowDate    = date('Y-m-d H:i:s');
                    $created_at = date('Y-m-d H:i:s');

                    /*if(date('H:i:s') > date('20:00:00'))
                    {
                        $created_at = date('Y-m-d 03:00:00', strtotime($nowDate .'+1 day'));
                    }
                    elseif(date('H:i:s') < date('03:00:00'))
                    {
                        $created_at = date('Y-m-d 03:00:00');
                    }
                    else
                    {
                        $created_at = date('Y-m-d H:i:s');
                    }*/

                    if($package->status == 'Manifest')
                    {
                        $packageHistory = new PackageHistory();

                        $packageHistory->id                           = uniqid();
                        $packageHistory->Reference_Number_1           = $package->Reference_Number_1;
                        $packageHistory->idCompany                    = $package->idCompany;
                        $packageHistory->company                      = $package->company;
                        $packageHistory->idStore                      = $package->idStore;
                        $packageHistory->store                        = $package->store;
                        $packageHistory->Dropoff_Contact_Name         = $package->Dropoff_Contact_Name;
                        $packageHistory->Dropoff_Company              = $package->Dropoff_Company;
                        $packageHistory->Dropoff_Contact_Phone_Number = $package->Dropoff_Contact_Phone_Number;
                        $packageHistory->Dropoff_Contact_Email        = $package->Dropoff_Contact_Email;
                        $packageHistory->Dropoff_Address_Line_1       = $package->Dropoff_Address_Line_1;
                        $packageHistory->Dropoff_Address_Line_2       = $package->Dropoff_Address_Line_2;
                        $packageHistory->Dropoff_City                 = $package->Dropoff_City;
                        $packageHistory->Dropoff_Province             = $package->Dropoff_Province;
                        $packageHistory->Dropoff_Postal_Code          = $package->Dropoff_Postal_Code;
                        $packageHistory->Notes                        = $package->Notes;
                        $packageHistory->Weight                       = $package->Weight;
                        $packageHistory->Route                        = $package->Route;
                        $packageHistory->idUser                       = Auth::user()->id;
                        $packageHistory->idUserInbound                = Auth::user()->id;
                        $packageHistory->Date_Inbound                 = $created_at;
                        $packageHistory->Description                  = 'For: '. Auth::user()->name .' '. Auth::user()->nameOfOwner;
                        $packageHistory->inbound                      = 1;
                        $packageHistory->quantity                     = $package->quantity;
                        $packageHistory->status                       = 'Inbound';
                        $packageHistory->created_at                   = $nowDate;
                        $packageHistory->updated_at                   = $nowDate;

                        $packageHistory->save();
                    }

                    $packageDispatch = new PackageDispatchDriver();

                    $packageDispatch->Reference_Number_1           = $package->Reference_Number_1;
                    $packageDispatch->idCompany                    = $package->idCompany;
                    $packageDispatch->company                      = $package->company;
                    $packageDispatch->idStore                      = $package->idStore;
                    $packageDispatch->store                        = $package->store;
                    $packageDispatch->Dropoff_Contact_Name         = $package->Dropoff_Contact_Name;
                    $packageDispatch->Dropoff_Company              = $package->Dropoff_Company;
                    $packageDispatch->Dropoff_Contact_Phone_Number = $package->Dropoff_Contact_Phone_Number;
                    $packageDispatch->Dropoff_Contact_Email        = $package->Dropoff_Contact_Email;
                    $packageDispatch->Dropoff_Address_Line_1       = $package->Dropoff_Address_Line_1;
                    $packageDispatch->Dropoff_Address_Line_2       = $package->Dropoff_Address_Line_2;
                    $packageDispatch->Dropoff_City                 = $package->Dropoff_City;
                    $packageDispatch->Dropoff_Province             = $package->Dropoff_Province;
                    $packageDispatch->Dropoff_Postal_Code          = $package->Dropoff_Postal_Code;
                    $packageDispatch->Notes                        = $package->Notes;
                    $packageDispatch->Weight                       = $package->Weight;
                    $packageDispatch->Route                        = $package->Route;
                    $packageDispatch->idUser                       = Auth::user()->id;
                    $packageDispatch->idTeam                       = $request->get('idTeam');
                    $packageDispatch->idUserDispatch               = $idUserDispatch;
                    $packageDispatch->Date_Dispatch                = $created_at;
                    $packageDispatch->quantity                     = $package->quantity;
                    $packageDispatch->pricePaymentCompany          = $packagePriceCompanyTeam->totalPriceCompany;
                    $packageDispatch->pricePaymentTeam             = $packagePriceCompanyTeam->totalPriceTeam;
                    $packageDispatch->idPaymentTeam                = '';
                    $packageDispatch->status                       = 'DispatchDriver';
                    $packageDispatch->created_at                   = $created_at;
                    $packageDispatch->updated_at                   = $created_at;

                    $packageHistory = new PackageHistory();

                    $packageHistory->id                           = uniqid();
                    $packageHistory->Reference_Number_1           = $package->Reference_Number_1;
                    $packageHistory->idCompany                    = $package->idCompany;
                    $packageHistory->company                      = $package->company;
                    $packageHistory->idStore                      = $package->idStore;
                    $packageHistory->store                        = $package->store;
                    $packageHistory->Dropoff_Contact_Name         = $package->Dropoff_Contact_Name;
                    $packageHistory->Dropoff_Company              = $package->Dropoff_Company;
                    $packageHistory->Dropoff_Contact_Phone_Number = $package->Dropoff_Contact_Phone_Number;
                    $packageHistory->Dropoff_Contact_Email        = $package->Dropoff_Contact_Email;
                    $packageHistory->Dropoff_Address_Line_1       = $package->Dropoff_Address_Line_1;
                    $packageHistory->Dropoff_Address_Line_2       = $package->Dropoff_Address_Line_2;
                    $packageHistory->Dropoff_City                 = $package->Dropoff_City;
                    $packageHistory->Dropoff_Province             = $package->Dropoff_Province;
                    $packageHistory->Dropoff_Postal_Code          = $package->Dropoff_Postal_Code;
                    $packageHistory->Notes                        = $package->Notes;
                    $packageHistory->Weight                       = $package->Weight;
                    $packageHistory->Route                        = $package->Route;
                    $packageHistory->idUser                       = Auth::user()->id;
                    $packageHistory->idTeam                       = $request->get('idTeam');
                    $packageHistory->idUserDispatch               = $idUserDispatch;
                    $packageHistory->Date_Dispatch                = $created_at;
                    $packageHistory->dispatch                     = 1;
                    $packageHistory->autorizationDispatch         = 1;
                    $packageHistory->Description                  = $description;
                    $packageHistory->quantity                     = $package->quantity;
                    $packageHistory->status                       = 'DispatchDriver';
                    $packageHistory->created_at                   = $created_at;
                    $packageHistory->updated_at                   = $created_at;

                    $packageDispatch->save();
                    $packageHistory->save();
                    $package->delete();

                    DB::commit();

                    return ['stateAction' => true];
                }
                catch(Exception $e)
                {
                    DB::rollback();

                    return ['stateAction' => true];
                }
            }
        }

        return ['stateAction' => 'notExists'];
    }
}