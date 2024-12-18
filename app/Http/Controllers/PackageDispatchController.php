<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use \App\Service\{ ServicePackageTerminal, ServicePackageNeedMoreInformation };

use App\Models\{ AuxDispatchUser, Comment, Company, Configuration, DimFactorTeam, Driver, PackageHistory, PackageHighPriority, PackageBlocked, PackageDispatch,  PackageFailed, PackageInbound, PackageLost, PackageManifest, PackageNotExists, PackagePreDispatch, PackagePriceCompanyTeam, PackageReturn, PackageReturnCompany, PackageWarehouse, PaymentTeamReturn, TeamRoute, User, PackageLmCarrier, Cellar };

use App\Http\Controllers\Api\PackageController;
use App\Http\Controllers\{ RangePriceTeamRouteCompanyController, TeamController };

use DateTime;
use DB;
use Log;
use Session;

class PackageDispatchController extends Controller
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
        return view('package.dispatch');
    }

    public function List(Request $request, $idCompany, $dateStart,$dateEnd, $idTeam, $idDriver, $state, $routes, $idCellar)
    {
        $packageDispatchList = $this->getDataDispatch($idCompany, $dateStart,$dateEnd, $idTeam, $idDriver, $state, $routes,  $idCellar);
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

    private function getDataDispatch($idCompany, $dateStart,$dateEnd, $idTeam, $idDriver, $state, $routes,  $idCellar, $type='list')
    {
        $dateStart = $dateStart .' 00:00:00';
        $dateEnd   = $dateEnd .' 23:59:59';

        $packageDispatchList = PackageDispatch::whereBetween('created_at', [$dateStart, $dateEnd])
                                                ->where('status', 'Dispatch');

        if($idCompany != 0)
        {
            $packageDispatchList = $packageDispatchList->where('idCompany', $idCompany);
        }

        if($idCellar != 0)
        {
            $packageDispatchList = $packageDispatchList->where('idCellar', $idCellar);
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
                                                        'nameCellar',
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

        $packageDispatchList = PackageDispatch::where('status', 'Dispatch')
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

    public function Export(Request $request, $idCompany, $dateStart,$dateEnd, $idTeam, $idDriver, $state, $routes, $idCellar, $typeExport)
    {
        $delimiter = ",";
        $filename  = $typeExport == 'download' ? "PACKAGES - DISPATCH " . date('Y-m-d H:i:s') . ".csv" : Auth::user()->id ."- PACKAGES - DISPATCH.csv";
        $file      = $typeExport == 'download' ? fopen('php://memory', 'w') : fopen(public_path($filename), 'w');

        //set column headers
        $fields = array('DATE' ,'HOUR', 'COMPANY', 'TEAM', 'DRIVER', 'PACKAGE ID', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'WEIGHT', 'ROUTE','TASK ONFLEET');

        fputcsv($file, $fields, $delimiter);


        $packageDispatchList = $this->getDataDispatch($idCompany, $dateStart,$dateEnd, $idTeam, $idDriver, $state, $routes, $idCellar, $type ='export');

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
        $listPackageDispatch = PackageDispatch::where('status', 'Dispatch')
                                                ->where('idOnfleet', '!=', '')
                                                ->inRandomOrder()
                                                ->get()
                                                ->take(300);

        return ['listPackageDispatch' => $listPackageDispatch];
    }

    public function GetByTeamDriver($idTeam, $idDriver)
    {
        $listPackageInDispatch = PackageDispatch::with(['team', 'driver'])
                                                ->where('idTeam', $idTeam)
                                                ->where('status', 'Dispatch');

        if($idDriver != 0)
        {
            $listPackageInDispatch = $listPackageInDispatch->where('idUserDispatch', $idDriver);
        }

        $listPackageInDispatch = $listPackageInDispatch->get();

        return ['listPackageInDispatch' => $listPackageInDispatch];
    }

    public function Insert(Request $request)
    {
        /*if($request->get('autorizationDispatch') == false)
        {
            return ['stateAction' => 'notAutorization'];
        }*/
        $packageDispatch = PackageDispatch::with('driver')
                                        ->where('Reference_Number_1', $request->Reference_Number_1)
                                        ->where('status', '!=', 'Delete')
                                        ->first();
        if($packageDispatch)
        {
            if($packageDispatch->status == 'Delivery')
            {
                return ['stateAction' => 'delivery'];
            }

            return ['stateAction' => 'validated', 'packageDispatch' => $packageDispatch];
        }

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
           $package = PackageLmCarrier::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();
        }
        
        if(!$package)
        {
            $package = PackageDispatch::where('Reference_Number_1', $request->get('Reference_Number_1'))
                                                ->where('status', 'Delete')
                                                ->first();
        }

        if($package)
        {
            $company = Company::find($package->idCompany);
        
            if($company->twoAttempts)
            {
                $packageHistoryDispatchListCompany = PackageHistory::where('Reference_Number_1', $request->Reference_Number_1)
                                                                    ->where('status', 'Dispatch')
                                                                    ->where('idCompany', $company->id)
                                                                    ->orderBy('created_at', 'asc')
                                                                    ->get();

                if(count($packageHistoryDispatchListCompany) > 1 && $request->forcedDispatch == 'NO')
                {
                    $hourDifference = $this->CalculateHourDifferenceDispatch($packageHistoryDispatchListCompany);

                    if($hourDifference >= 6)
                    {
                        return ['stateAction' => 'dispatchedMoreThanTwice'];
                    }
                }
            }

            if($request->get('RouteSearch'))
            {
                $routes = explode(',', $request->get('RouteSearch'));

                if(strpos($request->get('RouteSearch'), $package->Route) === false)
                {
                    return ['stateAction' => 'notRoute'];
                }
            }

            if($request->get('idTeam') && $request->get('idDriver'))
            {
                $team           = User::find($request->get('idTeam'));
                $driver         = User::find($request->get('idDriver'));
                $idUserDispatch = $request->get('idDriver');

                $description = 'To: '. $team->name .' / '. $driver->name .' '. $driver->nameOfOwner;
        
                if($team && $team->twoAttempts)
                {
                    $packageHistoryDispatchListTeam = PackageHistory::where('Reference_Number_1', $request->Reference_Number_1)
                                                                    ->where('status', 'Dispatch')
                                                                    ->where('idTeam', $team->id)
                                                                    ->orderBy('created_at', 'asc')
                                                                    ->get();

                    if(count($packageHistoryDispatchListTeam) > 1 && $request->forcedDispatch == 'NO')
                    {
                        $hourDifference = $this->CalculateHourDifferenceDispatch($packageHistoryDispatchListTeam);

                        if($hourDifference >= 6)
                        {
                            return ['stateAction' => 'dispatchedMoreThanTwice'];
                        }
                    }
                }

                if($package->status != 'Delete')
                {
                    try
                    {
                        DB::beginTransaction();

                        $nowDate    = date('Y-m-d H:i:s');
                        $created_at = date('Y-m-d H:i:s');

                        if(date('H:i:s') > date('20:00:00'))
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
                        }

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
                            $packageHistory->actualDate                   = $nowDate;
                            $packageHistory->created_at                   = $nowDate;
                            $packageHistory->updated_at                   = $nowDate;
 
                            $packageHistory->save();
                        }

                        $packageDispatch = new PackageDispatch();

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
                        $packageDispatch->idPaymentTeam                = '';
                        $packageDispatch->status                       = 'Dispatch';
                        $packageDispatch->created_at                   = $created_at;
                        $packageDispatch->updated_at                   = $created_at;
                        
                        $cellar = Cellar::find(Auth::user()->idCellar);

                        if($cellar)
                        {    
                            $packageDispatch->idCellar    = $cellar->id;
                            $packageDispatch->nameCellar  = $cellar->name;
                            $packageDispatch->stateCellar = $cellar->state;
                            $packageDispatch->cityCellar  = $cellar->city;
                        }

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
                        $packageHistory->status                       = 'Dispatch';
                        $packageHistory->actualDate                   = $nowDate;
                        $packageHistory->created_at                   = $created_at;
                        $packageHistory->updated_at                   = $created_at;

                        if($cellar){
                            $packageHistory->idCellar    = $cellar->id;
                            $packageHistory->nameCellar  = $cellar->name;
                            $packageHistory->stateCellar = $cellar->state;
                            $packageHistory->cityCellar  = $cellar->city;
                        }

                        if($driver->usageApp == 'PODApp')
                        {
                            $registerTask['status'] = 200;

                            $idOnfleet   = $package->Reference_Number_1;
                            $taskOnfleet = $package->Reference_Number_1;
                        }
                        else
                        {
                            $registerTask = $this->RegisterOnfleet($package, $team, $driver);

                            if($registerTask['status'] == 200)
                            {
                                $idOnfleet   = explode('"', explode('"', explode('":', $registerTask['response'])[1])[1])[0];
                                $taskOnfleet = explode('"', explode('"', explode('":', $registerTask['response'])[5])[1])[0];
                            }
                            else
                            {
                                return ['stateAction' => 'packageErrorOnfleet'];
                            }
                        }
                        
                        if($registerTask['status'] == 200)
                        {
                            
                            $packageDispatch->idOnfleet   = $idOnfleet;
                            $packageDispatch->taskOnfleet = $taskOnfleet;

                            $packageDispatch->save();
                            $packageHistory->save();
                            $package->delete();

                           if($driver->usageApp == 'PODApp')
                            {
                                $warnings = [];
                            }
                            else
                            {
                                $dataTaskOnfleet = $this->GetOnfleet($idOnfleet);

                                $warnings = $dataTaskOnfleet['destination']['warnings'];
                            }

                            Log::info('============ START TASK CREATED ================');
                            Log::info("Reference_Number_1 :". $package->Reference_Number_1);
                            Log::info("Warnings: ". count($warnings));
                            Log::info($warnings);

                            if(count($warnings) >= 0)
                            {
                                DB::commit();

                                $package['latitude']  = $request->get('latitude');
                                $package['longitude'] = $request->get('longitude');
                                
                                //data for INLAND
                                $packageController = new PackageController();
                                $packageController->SendStatusToInland($package, 'Dispatch', null, $created_at);
                                //end data for inland

                                $packageHistory = PackageHistory::where('Reference_Number_1', $package->Reference_Number_1)
                                                ->where('sendToInland', 1)
                                                ->where('status', 'Manifest')
                                                ->first();

                                if($packageHistory)
                                {
                                    $packageController->SendStatusToOtherCompany($package, 'Dispatch', null, date('Y-m-d H:i:s'));
                                }

                                Log::info('============ CREATED TASK COMPLETED ================');
                                Log::info('====================================================');
                                Log::info('====================================================');

                                return ['stateAction' => true];
                            }
                            else
                            {
                                Log::info('============ DELETE TASK - SYNC ================');

                                $deleteTask = $driver->usageApp == 'PODApp' ? '' : $this->DeleteOnfleet($idOnfleet);

                                Log::info('============ DELETE TASK COMPLETED - SYNC ================');

                                return ['stateAction' => 'repairPackage'];
                            }
                        }
                        else
                        {
                            return ['stateAction' => 'repairPackage'];
                        }
                    }
                    catch(Exception $e)
                    {
                        DB::rollback();

                        return ['stateAction' => true];
                    }
                }
                elseif($package->status == 'Delete')
                {
                    $nowDate    = date('Y-m-d H:i:s');
                    $created_at = date('Y-m-d H:i:s');

                    if(date('H:i:s') > date('20:00:00'))
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
                    }

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
                    $packageHistory->idTeam                       = $team->id;
                    $packageHistory->idUserDispatch               = $driver->id;
                    $packageHistory->Date_Dispatch                = date('Y-m-d H:s:i');
                    $packageHistory->dispatch                     = 1;
                    $packageHistory->Description                  = $description;
                    $packageHistory->quantity                     = $package->quantity;
                    $packageHistory->status                       = 'Dispatch';
                    $packageHistory->actualDate                   = $nowDate;
                    $packageHistory->created_at                   = $created_at;
                    $packageHistory->updated_at                   = $created_at;

                   
                        if($cellar){
                            $packageHistory->idCellar    = $cellar->id;
                            $packageHistory->nameCellar  = $cellar->name;
                            $packageHistory->stateCellar = $cellar->state;
                            $packageHistory->cityCellar  = $cellar->city;
                        }
                    
                    $registerTask = $this->RegisterOnfleet($package, $team, $driver);

                    if($registerTask['status'] == 200)
                    {
                        $idOnfleet   = explode('"', explode('"', explode('":', $registerTask['response'])[1])[1])[0];
                        $taskOnfleet = explode('"', explode('"', explode('":', $registerTask['response'])[5])[1])[0];

                        $package->Date_Dispatch = date('Y-m-d H:i:s');
                        $package->status        = 'Dispatch';
                        $package->idOnfleet     = $idOnfleet;
                        $package->taskOnfleet   = $taskOnfleet;
                        $package->created_at    = $created_at;
                        $package->updated_at    = $created_at;

                        $package->save();
                        $packageHistory->save();

                        if($cellar){
                            $packageHistory->idCellar    = $cellar->id;
                            $packageHistory->nameCellar  = $cellar->name;
                            $packageHistory->stateCellar = $cellar->state;
                            $packageHistory->cityCellar  = $cellar->city;
                        }

                        $dataTaskOnfleet = $this->GetOnfleet($idOnfleet);

                        $warnings = $dataTaskOnfleet['destination']['warnings'];

                        Log::info('============ START TASK CREATED ================');
                        Log::info("Reference_Number_1 :". $package->Reference_Number_1);
                        Log::info("Warnings: ". count($warnings));
                        Log::info($warnings);

                        if(count($warnings) == 0)
                        {
                            DB::commit();

                            //data for INLAND
                            $packageController = new PackageController();
                            $packageController->SendStatusToInland($package, 'Dispatch', null, $created_at);
                            //end data for inland

                            $packageHistory = PackageHistory::where('Reference_Number_1', $package->Reference_Number_1)
                                                ->where('sendToInland', 1)
                                                ->where('status', 'Manifest')
                                                ->first();

                            if($packageHistory)
                            {
                                $packageController->SendStatusToOtherCompany($package, 'Dispatch', null, date('Y-m-d H:i:s'));
                            }

                            Log::info('============ CREATED TASK COMPLETED ================');
                            Log::info('====================================================');
                            Log::info('====================================================');

                            return ['stateAction' => true];
                        }
                        else
                        {
                            Log::info('============ DELETE TASK - SYNC ================');

                            $deleteTask = $this->DeleteOnfleet($idOnfleet);

                            Log::info('============ DELETE TASK COMPLETED - SYNC ================');

                            return ['stateAction' => 'repairPackage'];
                        }
                    }
                    else
                    {
                        return ['stateAction' => 'repairPackage'];
                    }
                }
            }
            else
            {
                return ['stateAction' => 'notSelectTeamDriver'];
            }
        }
        else
        {
            /*$packageManifest = PackageManifest::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();

            if($packageManifest)
            {
                return ['stateAction' => 'notInbound'];
            }*/

            $packageReturnCompany = PackageReturnCompany::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();

            if($packageReturnCompany)
            {
                return ['stateAction' => 'validatedReturnCompany', 'packageInbound' => $packageReturnCompany];
            }

            $packageDispatch = PackageDispatch::with('driver')
                                            ->where('Reference_Number_1', $request->get('Reference_Number_1'))
                                            ->first();

            if($packageDispatch)
            {
                if($packageDispatch->status == 'Delivery')
                {
                    return ['stateAction' => 'delivery'];
                }

                return ['stateAction' => 'validated', 'packageDispatch' => $packageDispatch];
            }
            else
            {
                $packageReturnCompany = PackageReturnCompany::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();

                if($packageReturnCompany)
                {
                    return ['stateAction' => 'returCompany', 'packageReturnCompany' => $packageReturnCompany];
                }

                $packageHistory = PackageHistory::with('driver')
                                                ->where('Reference_Number_1', $request->get('Reference_Number_1'))
                                                ->where('status', 'Dispatch')
                                                ->where('dispatch', 1)
                                                ->first();

                if($packageHistory)
                {
                    return ['stateAction' => 'validated', 'packageDispatch' => $packageHistory];
                }

                $packageNotExists = PackageNotExists::find($request->get('Reference_Number_1'));

                if(!$packageNotExists)
                {
                    $packageNotExists = new PackageNotExists();

                    $packageNotExists->Reference_Number_1 = $request->get('Reference_Number_1');
                    $packageNotExists->idUser             = Auth::user()->id;
                    $packageNotExists->Date_Inbound       = date('Y-m-d H:s:i');

                    $packageNotExists->save();
                }

                return ['stateAction' => 'notExists'];
            }

            return ['stateAction' => false];
        }
    } /*finaliza el insert*/

    public function CalculateHourDifferenceDispatch($packageHistoryDispatchList)
    {
        $oneDispatch = $packageHistoryDispatchList[0];
        $twoDispatch = $packageHistoryDispatchList[count($packageHistoryDispatchList) - 1];

        $dateInit = new DateTime($oneDispatch->created_at);
        $dateEnd  = new DateTime($twoDispatch->created_at);

        $interval = $dateInit->diff($dateEnd);

        return (int)$interval->format('%H');
    }

    public function Get($Reference_Number_1)
    {
        $packageInbound = PackageDispatch::find($Reference_Number_1);

        return ['package' => $packageInbound];
    }

    public function Update(Request $request)
    {
        $package = PackageDispatch::find($request->get('Reference_Number_1'));

        $validator = Validator::make($request->all(),

            [
                "Dropoff_Contact_Name" => ["required"],

                "Dropoff_Contact_Phone_Number" => ["required"],
                "Dropoff_Address_Line_1" => ["required"],

                "Dropoff_City" => ["required"],
                "Dropoff_Province" => ["required"],

                "Dropoff_Postal_Code" => ["required"],
                "Weight" => ["required"],
                "Route" => ["required"],
            ],
            [
                "Dropoff_Contact_Name.required" => "El campo es requerido",
                "Dropoff_Contact_Phone_Number.required" => "El campo es requerido",

                "Dropoff_Address_Line_1.required" => "El campo es requerido",

                "Dropoff_City.required" => "El campo es requerido",

                "Dropoff_Province.required" => "El campo es requerido",

                "Dropoff_Postal_Code.required" => "El campo es requerido",

                "Weight.required" => "El campo es requerido",
                "Route.required" => "El campo es requerido",
            ]
        );

        if($validator->fails())
        {
            return response()->json(["status" => 422, "errors" => $validator->errors()], 422);
        }

        $packageHistoryList = PackageHistory::where('Reference_Number_1', $request->get('Reference_Number_1'))->get();

        foreach($packageHistoryList as $packageHistory)
        {
            $packageDispatch = PackageHistory::find($packageHistory->id);

            $packageDispatch->Reference_Number_1           = $request->get('Reference_Number_1');
            $packageDispatch->Dropoff_Contact_Name         = $request->get('Dropoff_Contact_Name');
            $packageDispatch->Dropoff_Contact_Phone_Number = $request->get('Dropoff_Contact_Phone_Number');
            $packageDispatch->Dropoff_Address_Line_1       = $request->get('Dropoff_Address_Line_1');
            $packageDispatch->Dropoff_Address_Line_2       = $request->get('Dropoff_Address_Line_2');
            $packageDispatch->Dropoff_City                 = $request->get('Dropoff_City');
            $packageDispatch->Dropoff_Province             = $request->get('Dropoff_Province');
            $packageDispatch->Dropoff_Postal_Code          = $request->get('Dropoff_Postal_Code');
            $packageDispatch->Weight                       = $request->get('Weight');
            $packageDispatch->Route                        = $request->get('Route');

            $packageDispatch->save();
        }

        $packageDispatch = PackageDispatch::find($request->get('Reference_Number_1'));

        $packageDispatch->Reference_Number_1           = $request->get('Reference_Number_1');
        $packageDispatch->Dropoff_Contact_Name         = $request->get('Dropoff_Contact_Name');
        $packageDispatch->Dropoff_Contact_Phone_Number = $request->get('Dropoff_Contact_Phone_Number');
        $packageDispatch->Dropoff_Address_Line_1       = $request->get('Dropoff_Address_Line_1');
        $packageDispatch->Dropoff_Address_Line_2       = $request->get('Dropoff_Address_Line_2');
        $packageDispatch->Dropoff_City                 = $request->get('Dropoff_City');
        $packageDispatch->Dropoff_Province             = $request->get('Dropoff_Province');
        $packageDispatch->Dropoff_Postal_Code          = $request->get('Dropoff_Postal_Code');
        $packageDispatch->Weight                       = $request->get('Weight');
        $packageDispatch->Route                        = $request->get('Route');

        $packageDispatch->save();

        return response()->json(["stateAction" => true], 200);
    }

    public function Change(Request $request)
    {
        try
        {
            DB::beginTransaction();

            $packageDispatch = PackageDispatch::find($request->get('Reference_Number_1'));

            $packageHistory = PackageHistory::where('Reference_Number_1', $request->get('Reference_Number_1'))
                                            ->where('dispatch', 1)
                                            ->first();

            $packageHistory->dispatch = 0;

            $packageHistory->save();

            $packageHistory = new PackageHistory();

            $packageHistory->id                           = uniqid();
            $packageHistory->Reference_Number_1           = $packageDispatch->Reference_Number_1;
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
            $packageHistory->idUser                       = Auth::user()->id;
            $packageHistory->idUserDispatch               = $request->get('idDriver');
            $packageHistory->Date_Dispatch                = date('Y-m-d H:s:i');
            $packageHistory->dispatch                     = 1;
            $packageHistory->status                       = 'Dispatch';

            $packageHistory->save();

            $packageDispatch->idUserDispatch = $request->get('idDriver');

            $packageDispatch->save();

            DB::commit();

            return response()->json(["stateAction" => true], 200);
        }
        catch(Exception $e)
        {
            DB::rollback();

            return response()->json(["stateAction" => false], 400);
        }
    }

    public function Import(Request $request)
    {
        $file = $request->file('file');

        $file->move(public_path() .'/file-import', 'dispatch.csv');

        $handle = fopen(public_path('file-import/dispatch.csv'), "r");

        $lineNumber = 1;

        $countSave = 0;

        try
        {
            DB::beginTransaction();

            while (($raw_string = fgets($handle)) !== false)
            {
                if($lineNumber > 1)
                {
                    $row = str_getcsv($raw_string);

                    $package = PackageInbound::find($row[0]);

                    if($package == null)
                    {
                        $package = PackageWarehouse::find($row[0]);
                    }

                    $packageDispatch = PackageDispatch::find($row[0]);

                    if($package && $packageDispatch == null)
                    {
                        Log::info("=========== IMPORT DISPATCH ===========");
                        Log::info($package->Reference_Number_1);
                        $validationRoute = true;

                        if($request->get('RouteSearch'))
                        {
                            $routes = explode(',', $request->get('RouteSearch'));

                            if(strpos($request->get('RouteSearch'), $package->Route) === false)
                            {
                                $validationRoute = false;
                            }
                        }

                        if($validationRoute)
                        {
                            if($request->get('idTeam') && $request->get('idDriver'))
                            {
                                $team           = User::find($request->get('idTeam'));
                                $driver         = User::find($request->get('idDriver'));
                                $idUserDispatch = $request->get('idDriver');

                                $user = User::find($idUserDispatch);

                                $description = 'Dispatch - for: '. Auth::user()->name .' '. Auth::user()->nameOfOwner .' to '. $user->nameTeam .' / '. $user->name .' '. $user->nameOfOwner;
                            }
                            elseif($request->get('idTeam'))
                            {
                                $idUserDispatch = $request->get('idTeam');
                                $user           = User::find($idUserDispatch);
                                $team           = $user;
                                $description    = 'Dispatch - for: '. Auth::user()->name .' '. Auth::user()->nameOfOwner .' to '. $user->name;
                            }

                            $register     = true;
                            $idOnfleet    = '';
                            $taskOnfleet  = '';
                            $created_at   = date('Y-m-d H:i:s');

                            if($team->name != 'Brooks Courier')
                            {
                                $registerTask = $this->RegisterOnfleet($package, $team, $driver);

                                if($team->name != 'Brooks Courier' && $registerTask['status'] == 200)
                                {
                                    $idOnfleet   = explode('"', explode('"', explode('":', $registerTask['response'])[1])[1])[0];
                                    $taskOnfleet = explode('"', explode('"', explode('":', $registerTask['response'])[5])[1])[0];
                                }
                                else
                                {
                                    $register = false;
                                }
                            }

                            if($register)
                            {
                                $packageDispatch = new PackageDispatch();
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
                                $packageDispatch->Date_Dispatch                = date('Y-m-d H:i:s');
                                $packageDispatch->quantity                     = $package->quantity;
                                $packageDispatch->idOnfleet                    = $idOnfleet;
                                $packageDispatch->taskOnfleet                  = $taskOnfleet;
                                $packageDispatch->status                       = 'Dispatch';
                                $packageDispatch->created_at                   = $created_at;
                                $packageDispatch->updated_at                   = $created_at;

                                $cellar = Cellar::find(Auth::user()->idCellar);

                                if($cellar)
                                {    
                                    $packageDispatch->idCellar    = $cellar->id;
                                    $packageDispatch->nameCellar  = $cellar->name;
                                    $packageDispatch->stateCellar = $cellar->state;
                                    $packageDispatch->cityCellar  = $cellar->city;
                                }

                                $packageDispatch->save();

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
                                $packageHistory->Date_Dispatch                = date('Y-m-d H:s:i');
                                $packageHistory->dispatch                     = 1;
                                $packageHistory->Description                  = $description;
                                $packageHistory->quantity                     = $package->quantity;
                                $packageHistory->status                       = 'Dispatch';
                                $packageHistory->actualDate                   = $created_at;
                                $packageHistory->created_at                   = $created_at;
                                $packageHistory->updated_at                   = $created_at;
                                
                                if($cellar)
                                {
                                    $packageHistory->idCellar    = $cellar->id;
                                    $packageHistory->nameCellar  = $cellar->name;
                                    $packageHistory->stateCellar = $cellar->state;
                                    $packageHistory->cityCellar  = $cellar->city;
                                }

                                $packageHistory->save();

                                //data for INLAND
                                $packageController = new PackageController();
                                $packageController->SendStatusToInland($package, 'Dispatch', null, $created_at);
                                //end data for inland

                                $packageHistory = PackageHistory::where('Reference_Number_1', $package->Reference_Number_1)
                                                ->where('sendToInland', 1)
                                                ->where('status', 'Manifest')
                                                ->first();

                                if($packageHistory)
                                {
                                    $packageController->SendStatusToOtherCompany($package, 'Dispatch', null, date('Y-m-d H:i:s'));
                                }

                                $package->delete();
                            }
                        }
                    }
                }

                $lineNumber++;
            }

            fclose($handle);

            DB::commit();

            return ['stateAction' => true];
        }
        catch(Exception $e)
        {
            DB::rollback();

            return ['stateAction' => false];
        }
    }

    public function Return(Request $request)
    {
        Log::info('========== RE-INBOUND');
        Log::info('Reference_Number_1: '. $request->get('Reference_Number_1'));

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

        $packageLost = PackageLost::find($request->get('Reference_Number_1'));

        if($packageLost)
        {
            return ['stateAction' => 'validatedLost'];
        }
        
        $packageDispatch = PackageFailed::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();

        if($packageDispatch == null)
        {
            $packageDispatch = PackageDispatch::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();
            
            if($packageDispatch == null)
            {
                $packageDispatch = PackageLmCarrier::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();
            }
        }

        Log::info('package: '. $packageDispatch);

        if($packageDispatch)
        {
            if($packageDispatch->idUserDispatch == Auth::user()->id || Auth::user()->role->name == 'Master')
            {
                try
                {
                    DB::beginTransaction();
                    
                    $team                = User::find($packageDispatch->idTeam);
                    $driver              = User::find($packageDispatch->idUserDispatch);
                    $idOnfleet           = $packageDispatch->idOnfleet;
                    $taskOnfleet         = $packageDispatch->taskOnfleet;
                    $teamName            = $team ? $team->name : 'NOT FOUND'; 
                    $workerName          = ($driver ? $driver->name .' '. $driver->nameOfOwner : '');
                    $photoUrl            = '';
                    $statusOnfleet       = '';
                    $Date_Return         = date('Y-m-d H:i:s');
                    $Category_Return     = $request->get('CategoryReturn');
                    $Description_Return  = $request->get('Description_Return');
                    $Description_Onfleet = '';

                    $onfleet = $this->GetOnfleet($packageDispatch->idOnfleet);

                    if($onfleet)
                    {
                        $Description_Onfleet = $onfleet['completionDetails']['failureReason'] .': '. $onfleet['completionDetails']['failureNotes'];

                        if($onfleet['state'] == 3)
                        {
                            $statusOnfleet = $onfleet['completionDetails']['success'] == true ? $onfleet['state'] .' (error success)' : $onfleet['state'];

                            if(count($onfleet['completionDetails']['photoUploadIds']) > 0)
                            {
                                $photoUrl = implode(",", $onfleet['completionDetails']['photoUploadIds']);
                            }
                            else
                            {
                                $photoUrl   = $onfleet['completionDetails']['photoUploadId'];
                            }
                        }
                        elseif($onfleet['state'] == 1)
                        {
                            $statusOnfleet = 1;
                        }
                    }
                    else
                    {
                        $idOnfleet           = null;
                        $taskOnfleet         = null;
                        $Description_Onfleet = 'Task does not exist in onfleet';
                        $statusOnfleet       = 1;
                    }

                    $nowDate              = date('Y-m-d H:i:s');
                    $created_at_ReInbound = date('Y-m-d H:i:s', strtotime('+1 second', strtotime(date('Y-m-d H:i:s'))));
                    $created_at_Warehouse = date('Y-m-d H:i:s', strtotime('+6 second', strtotime(date('Y-m-d H:i:s'))));

                    if(date('H:i:s') > date('20:00:00'))
                    {
                        $created_at_ReInbound = date('Y-m-d 03:00:10', strtotime($nowDate .'+1 day'));
                        $created_at_Warehouse = date('Y-m-d 03:00:20', strtotime($nowDate .'+1 day'));
                    }
                    elseif(date('H:i:s') < date('03:00:00'))
                    {
                        $created_at_ReInbound = date('Y-m-d 03:00:10');
                        $created_at_Warehouse = date('Y-m-d 03:00:20');
                    }
                    else
                    {
                        $created_at_ReInbound = date('Y-m-d H:i:s');
                        $created_at_Warehouse = date('Y-m-d H:i:s', strtotime('+6 second', strtotime(date('Y-m-d H:i:s'))));
                    }

                    $comment = Comment::where('description', $Description_Return)
                                        ->where('category', $Category_Return)
                                        ->first();

                    if($comment && $comment->category == 'Retry')
                    {
                        $statusReturn = 'ReInbound';
                    }
                    else
                    {
                        $statusReturn = 'Terminal';
                    }
                    
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
                    $packageHistory->idUser                       = Auth::user()->id;
                    $packageHistory->idUserInbound                = Auth::user()->id;
                    $packageHistory->Date_Inbound                 = date('Y-m-d H:s:i');
                    $packageHistory->Description                  = 'For: '. Auth::user()->name .' '. Auth::user()->nameOfOwner;
                    $packageHistory->Description_Return           = $Description_Return;
                    $packageHistory->Description_Onfleet          = $Description_Onfleet;
                    $packageHistory->inbound                      = 1;
                    $packageHistory->quantity                     = $packageDispatch->quantity;
                    $packageHistory->status                       = $statusReturn;
                    $packageHistory->actualDate                   = $nowDate;
                    $packageHistory->created_at                   = $created_at_ReInbound;
                    $packageHistory->updated_at                   = $created_at_ReInbound;

                    $cellar = Cellar::find(Auth::user()->idCellar);

                    if($cellar)
                    {    
                        $packageHistory->idCellar    = $cellar->id;
                        $packageHistory->nameCellar  = $cellar->name;
                        $packageHistory->stateCellar = $cellar->state;
                        $packageHistory->cityCellar  = $cellar->city;
                    }

                    $packageHistory->save();

                    $nowDate = date('Y-m-d H:i:s', strtotime($nowDate .'+6 second'));

                    $deleteDispatch = true;

                    if($onfleet)
                    {
                        if($onfleet['state'] == 1)
                        {
                            $deleteOnfleet  = $this->DeleteOnfleet($packageDispatch->idOnfleet);
                            $deleteDispatch = $deleteOnfleet ? true : false;
                        }
                    }

                    $packageReturn = new PackageReturn();

                    $packageReturn->id                           = uniqid();
                    $packageReturn->Reference_Number_1           = $packageDispatch->Reference_Number_1;
                    $packageReturn->idCompany                    = $packageDispatch->idCompany;
                    $packageReturn->company                      = $packageDispatch->company;
                    $packageReturn->idStore                      = $packageDispatch->idStore;
                    $packageReturn->store                        = $packageDispatch->store;
                    $packageReturn->Dropoff_Contact_Name         = $packageDispatch->Dropoff_Contact_Name;
                    $packageReturn->Dropoff_Company              = $packageDispatch->Dropoff_Company;
                    $packageReturn->Dropoff_Contact_Phone_Number = $packageDispatch->Dropoff_Contact_Phone_Number;
                    $packageReturn->Dropoff_Contact_Email        = $packageDispatch->Dropoff_Contact_Email;
                    $packageReturn->Dropoff_Address_Line_1       = $packageDispatch->Dropoff_Address_Line_1;
                    $packageReturn->Dropoff_Address_Line_2       = $packageDispatch->Dropoff_Address_Line_2;
                    $packageReturn->Dropoff_City                 = $packageDispatch->Dropoff_City;
                    $packageReturn->Dropoff_Province             = $packageDispatch->Dropoff_Province;
                    $packageReturn->Dropoff_Postal_Code          = $packageDispatch->Dropoff_Postal_Code;
                    $packageReturn->Notes                        = $packageDispatch->Notes;
                    $packageReturn->Weight                       = $packageDispatch->Weight;
                    $packageReturn->Route                        = $packageDispatch->Route;
                    $packageReturn->idUser                       = Auth::user()->id;
                    $packageReturn->idTeam                       = $packageDispatch->idTeam;
                    $packageReturn->idUserReturn                 = $packageDispatch->idUserDispatch;
                    $packageReturn->Date_Return                  = $Date_Return;
                    $packageReturn->Description_Return           = $Description_Return;
                    $packageReturn->Description_Onfleet          = $Description_Onfleet;
                    $packageReturn->idOnfleet                    = $idOnfleet;
                    $packageReturn->taskOnfleet                  = $taskOnfleet;
                    $packageReturn->team                         = $teamName;
                    $packageReturn->workerName                   = $workerName;
                    $packageReturn->photoUrl                     = $photoUrl;
                    $packageReturn->statusOnfleet                = $statusOnfleet;
                    $packageReturn->quantity                     = $packageDispatch->quantity;
                    $packageReturn->idPaymentTeam                = $packageDispatch->idPaymentTeam;
                    $packageReturn->status                       = 'Return';
                    
                    $packageReturn->save(); 

                    if($packageDispatch->idPaymentTeam != '')
                    {
                        //update payment team company
                        $paymentTeam = PaymentTeamReturn::find($packageDispatch->idPaymentTeam);

                        $paymentTeam->totalReturn = $paymentTeam->totalReturn + $packageDispatch->pricePaymentTeam;

                        $paymentTeam->save();

                        //update payment and return, prices totals
                        $team = User::find($packageDispatch->idTeam);

                        $team->totalReturn = $team->totalReturn + $packageDispatch->pricePaymentTeam;

                        $team->save();
                        //===============================
                    }

                    //update dispatch
                    $packageHistory = PackageHistory::where('Reference_Number_1', 'INLAND103845989')
                                                    ->where('dispatch', 1)
                                                    ->first();

                    if($packageHistory)
                    {
                        $packageHistory->dispatch = 0;

                        $packageHistory->save();
                    }

                    //update inbound
                    $packageHistory = PackageHistory::where('Reference_Number_1', $request->get('Reference_Number_1'))
                                                    ->where('inbound', 1)
                                                    ->first();

                    if($packageHistory)
                    {
                        $packageHistory->inbound  = 0;

                        $packageHistory->save();
                    }

                    

                    $packageWarehouse = PackageWarehouse::find($packageDispatch->Reference_Number_1);

                    if($packageWarehouse)
                    {
                        $packageWarehouse->delete();
                    }

                    $comment = Comment::where('description', $Description_Return)
                                        ->where('category', $Category_Return)
                                        ->first();

                    if($comment && $comment->category == 'Retry')
                    {
                        $packageWarehouse = new PackageWarehouse();

                        $packageWarehouse->Reference_Number_1           = $packageDispatch->Reference_Number_1;
                        $packageWarehouse->idCompany                    = $packageDispatch->idCompany;
                        $packageWarehouse->company                      = $packageDispatch->company;
                        $packageWarehouse->idStore                      = $packageDispatch->idStore;
                        $packageWarehouse->store                        = $packageDispatch->store;
                        $packageWarehouse->Dropoff_Contact_Name         = $packageDispatch->Dropoff_Contact_Name;
                        $packageWarehouse->Dropoff_Company              = $packageDispatch->Dropoff_Company;
                        $packageWarehouse->Dropoff_Contact_Phone_Number = $packageDispatch->Dropoff_Contact_Phone_Number;
                        $packageWarehouse->Dropoff_Contact_Email        = $packageDispatch->Dropoff_Contact_Email;
                        $packageWarehouse->Dropoff_Address_Line_1       = $packageDispatch->Dropoff_Address_Line_1;
                        $packageWarehouse->Dropoff_Address_Line_2       = $packageDispatch->Dropoff_Address_Line_2;
                        $packageWarehouse->Dropoff_City                 = $packageDispatch->Dropoff_City;
                        $packageWarehouse->Dropoff_Province             = $packageDispatch->Dropoff_Province;
                        $packageWarehouse->Dropoff_Postal_Code          = $packageDispatch->Dropoff_Postal_Code;
                        $packageWarehouse->Notes                        = $packageDispatch->Notes;
                        $packageWarehouse->Weight                       = $packageDispatch->Weight;
                        $packageWarehouse->Route                        = $packageDispatch->Route;
                        $packageWarehouse->idUser                       = Auth::user()->id;
                        $packageWarehouse->quantity                     = $packageDispatch->quantity;
                        $packageWarehouse->status                       = 'Warehouse';

                        $cellar = Cellar::find(Auth::user()->idCellar);

                        if($cellar)
                        {    
                            $packageWarehouse->idCellar    = $cellar->id;
                            $packageWarehouse->nameCellar  = $cellar->name;
                            $packageWarehouse->stateCellar = $cellar->state;
                            $packageWarehouse->cityCellar  = $cellar->city;
                        }

                        $packageWarehouse->save();

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
                        $packageHistory->idUser                       = Auth::user()->id;
                        $packageHistory->Description                  = 'For: '. Auth::user()->name .' '. Auth::user()->nameOfOwner;
                        $packageHistory->quantity                     = $packageDispatch->quantity;
                        $packageHistory->status                       = 'Warehouse';
                        $packageHistory->actualDate                   = $nowDate;
                        $packageHistory->created_at                   = $created_at_Warehouse;
                        $packageHistory->updated_at                   = $created_at_Warehouse;

                        if($cellar)
                        {    
                            $packageHistory->idCellar    = $cellar->id;
                            $packageHistory->nameCellar  = $cellar->name;
                            $packageHistory->stateCellar = $cellar->state;
                            $packageHistory->cityCellar  = $cellar->city;
                        }

                        $packageHistory->save();
                    }
                    else if($comment && $comment->category == 'Terminal')
                    {                        
                        $servicePackageTerminal = new ServicePackageTerminal();
                        $servicePackageTerminal->Insert($packageDispatch);
                    }

                    $packageDispatch['Description_Return'] = $Description_Return;
                    
                    //data for INLAND
                    $packageController = new PackageController();
                    $packageController->SendStatusToInland($packageDispatch, 'ReInbound', $comment->statusCode, $created_at_ReInbound);

                    $packageHistory = PackageHistory::where('Reference_Number_1', $packageDispatch->Reference_Number_1)
                                                ->where('sendToInland', 1)
                                                ->where('status', 'Manifest')
                                                ->first();

                    if($packageHistory)
                    {
                        $packageController->SendStatusToOtherCompany($packageDispatch, 'ReInbound', $comment->statusCode, $created_at_ReInbound);
                    }
                            
                    if($deleteDispatch)
                    {
                        $packageDispatch->delete();

                        DB::commit();

                        return ['stateAction' => true];
                    }
                    else
                    {
                        return ['stateAction' => 'taskWasNotDelete'];
                    }
                }
                catch(Exception $e)
                {
                    DB::rollback();

                    return ['stateAction' => false];
                }
            }

            return ['stateAction' => 'notUser'];
        }
        else
        {
            $packageReturnCompany = PackageReturnCompany::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();

            if($packageReturnCompany)
            {
                return ['stateAction' => 'validatedReturnCompany', 'packageInbound' => $packageReturnCompany];
            }
        }

        return ['stateAction' => 'notDispatch'];
    }

    public function UpdatePriceTeams($dateStart, $dateEnd)
    {
        try
        {
            DB::beginTransaction();

            $dateStart = $dateStart .' 00:00:00';
            $dateEnd   = $dateEnd .' 23:59:59';

            $listPackageDispatch = PackageDispatch::whereBetween('created_at', [$dateStart, $dateEnd])->get();

            foreach($listPackageDispatch as $packageDispatch)
            {
                $packageDispatch = PackageDispatch::find($packageDispatch->Reference_Number_1);

                if($packageDispatch)
                {
                    $priceTeam = new RangePriceTeamRouteCompanyController();
                    $priceTeam = $priceTeam->GetPriceTeam($packageDispatch->idTeam, $packageDispatch->idCompany, $packageDispatch->Weight, $packageDispatch->Route);

                    if($priceTeam > 0)
                    {
                        $packageDispatch->pricePaymentTeam = $priceTeam;

                        $packageDispatch->save();
                    }
                }
            }

            DB::commit();

            return 'updated successfully';
        }
        catch(Exception $e)
        {
            return 'erros'; 
        }
    }

    public function UpdateChangeTeam(Request $request)
    {
        $References = explode(',', $request->get('References'));
        
        $packageDispatchList = PackageDispatch::whereIn('Reference_Number_1', $References)
                                            ->where('status', 'Dispatch')
                                            ->get();

        if(count($packageDispatchList) > 0)
        {
            try
            {
                DB::beginTransaction();

                $packagesMovedList    = [];
                $packagesNotMovedList = [];

                foreach($packageDispatchList as $packageDispatch)
                {
                    $moved = false;
                    
                    if($packageDispatch->taskOnfleet)
                    {
                        $onfleet = $this->GetOnfleetShorId($packageDispatch->taskOnfleet);

                        if($onfleet)
                        {
                            $teamOld     = User::find($packageDispatch->idTeam);
                            $team        = User::find($request->get('idTeamNew'));
                            $driver      = User::find($request->get('idDriverNew'));
                            $description = 'Passed from the '. $teamOld->name .' to '. $team->name .' / '. $driver->name .' '. $driver->nameOfOwner;
                            $created_at  = date('Y-m-d H:i:s');

                            $onfleetUpdate = $this->UpdateOnfleet($team, $driver, $onfleet['id']);

                            if($onfleetUpdate['status'] == 200)
                            {
                                $packageDispatch = PackageDispatch::find($packageDispatch->Reference_Number_1);
                                $packageDispatch->idTeam         = $request->get('idTeamNew');
                                $packageDispatch->idUserDispatch = $request->get('idDriverNew');
                                $packageDispatch->save();

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
                                $packageHistory->idUser                       = Auth::user()->id;
                                $packageHistory->idTeam                       = $request->get('idTeam');
                                $packageHistory->idUserDispatch               = $request->get('idDriverNew');
                                $packageHistory->Date_Dispatch                = $created_at;
                                $packageHistory->dispatch                     = 1;
                                $packageHistory->autorizationDispatch         = 1;
                                $packageHistory->Description                  = $description;
                                $packageHistory->quantity                     = $packageDispatch->quantity;
                                $packageHistory->status                       = 'Dispatch';
                                $packageHistory->actualDate                   = $created_at;
                                $packageHistory->created_at                   = $created_at;
                                $packageHistory->updated_at                   = $created_at;
                                $packageHistory->save();

                                array_push($packagesMovedList, $packageDispatch->Reference_Number_1);

                                $moved = true;
                            }
                        }
                    }

                    if($moved == false)
                    {
                        array_push($packagesNotMovedList, $packageDispatch->Reference_Number_1);
                    }
                }

                DB::commit();

                return ['statusCode' => true, 'packagesMovedList' => $packagesMovedList, 'packagesNotMovedList' => $packagesNotMovedList];
            }
            catch(Exception $e)
            {
                DB::rollback();

                return ['statusCode' => false];
            }
        }
        else
        {
            return ['statusCode' => 'notExists'];
        }     
    }
    
    public function RegisterOnfleet($package, $team, $driver)
    {
        $company = Company::select('id', 'name', 'age21')->find($package->idCompany);

        $age21     = '';
        $age21Text = '';

        if($company && $company->age21)
        {
            $age21     = 21;
            $age21Text = 'AGE VERIFICATION 21+';
        }

        //"unparsed" =>  $package->Dropoff_Address_Line_1 .', '. $package->Dropoff_City .', '. $package->Dropoff_Province .' '. $package->Dropoff_Postal_Code .', USA',

        $number = explode(' ', $package->Dropoff_Address_Line_1)[0];
        $street = str_replace($number, '', $package->Dropoff_Address_Line_1);

        Log::info('$package->Dropoff_Contact_Name: '. $package->Dropoff_Contact_Name);

        $data = [   
                    "destination" =>  [
                        "address" =>  [
                            "number" => $number,
                            "street" => $street,
                            "apartment" => $package->Dropoff_Address_Line_2,
                            "city" => $package->Dropoff_City,
                            "state" => $package->Dropoff_Province,
                            "country" => "USA",
                            "postalCode" => $package->Dropoff_Postal_Code,
                        ] ,
                        "notes" => "",
                    ],
                    "recipients" =>  [
                        [
                            "name"  => $package->Dropoff_Contact_Name,
                            "phone" => "+". $package->Dropoff_Contact_Phone_Number,
                            "notes" => $age21Text,
                        ]
                    ],
                    "notes" => $package->Reference_Number_1,
                    "container" =>  [
                        "type"   =>  "WORKER",
                        "team"   =>  $team->idOnfleet,
                        "worker" =>  $driver->idOnfleet
                    ],
                    "requirements" => [

                        "photo" => true,
                        "minimumAge" => $age21,
                    ],
                ];

        Log::info($data);
        
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, 'https://onfleet.com/api/v2/tasks');
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERPWD, '4c52f49c1db8d158f7ff1ace1722f341:');
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);

        $output = curl_exec($curl);

        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        Log::info('http_status: '. $http_status);

        if($http_status == 200)
        {
            return ['status' => 200, 'response' => $output];
        }
        else
        {
            return ['status' => false, $output];
        }
    }

    public function UpdateOnfleet($team, $driver, $idOnfleet)
    {
        $curl = curl_init();

        Log::info('{
                        "container":{
                            "type":"WORKER",
                            "team":"'. $team->idOnfleet .'",
                            "worker":"'. $driver->idOnfleet .'"
                        }
                    }');

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://onfleet.com/api/v2/tasks/'. $idOnfleet,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS =>'{
                        "container":{
                            "type":"WORKER",
                            "team":"'. $team->idOnfleet .'",
                            "worker":"'. $driver->idOnfleet .'"
                        }
                    }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Basic '. $this->base64
            ),
        ));

        $output      = curl_exec($curl);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        Log::info($output);
        if($http_status == 200)
        {
            return ['status' => 200, 'response' => $output];
        }
        else
        {
            return ['status' => false, 'response' => $output];
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
 
    public function GetOnfleetShorId($taskOnfleet)
    {
        $curl = curl_init("https://onfleet.com/api/v2/tasks/shortId/". $taskOnfleet);

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