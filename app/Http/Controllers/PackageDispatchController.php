<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{ Assigned, AuxDispatchUser, Comment, Company, Configuration, Driver, PackageHistory, PackageBlocked, PackageDispatch, PackageFailed, PackageInbound, PackageManifest, PackageNotExists, PackageReturn, PackageReturnCompany, PackageWarehouse, TeamRoute, User };

use Illuminate\Support\Facades\Validator;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PhpOffice\PhpOfficePhpSpreadsheetSpreadsheet;
use PhpOffice\PhpOfficePhpSpreadsheetReaderCsv;
use PhpOffice\PhpOfficePhpSpreadsheetReaderXlsx;

use App\Http\Controllers\Api\PackageController;

use DB;
use Illuminate\Support\Facades\Auth;
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

    public function List(Request $request, $idCompany, $dateStart,$dateEnd, $idTeam, $idDriver, $state, $routes)
    {
        $packageDispatchList = $this->getDataDispatch($idCompany, $dateStart,$dateEnd, $idTeam, $idDriver, $state, $routes);
        $getDataDispatchAll  = $this->getDataDispatchAll($idCompany, $idTeam, $idDriver);

        $quantityDispatch    = $packageDispatchList->total();
        $quantityDispatchAll = $getDataDispatchAll->count();

        $roleUser = Auth::user()->role->name;

        $listState = PackageDispatch::select('Dropoff_Province')
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        return ['packageDispatchList' => $packageDispatchList, 'quantityDispatch' => $quantityDispatch, 'quantityDispatchAll' => $quantityDispatchAll, 'roleUser' => $roleUser, 'listState' => $listState]; 
    }

    private function getDataDispatch($idCompany, $dateStart,$dateEnd, $idTeam, $idDriver, $state, $routes,$type='list')
    {
        $dateStart = $dateStart .' 00:00:00';
        $dateEnd   = $dateEnd .' 23:59:59';

        $packageDispatchList = PackageDispatch::whereBetween('created_at', [$dateStart, $dateEnd])
                                                ->where('status', 'Dispatch');

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

        if($type == 'list'){
            $packageDispatchList = $packageDispatchList->with(['team', 'driver'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);
        }else{
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

        $packageDispatchList = $packageDispatchList->orderBy('created_at', 'desc')->get();

        return  $packageDispatchList;
    }

    public function Export(Request $request, $idCompany, $dateStart,$dateEnd, $idTeam, $idDriver, $state, $routes)
    {
        $delimiter = ",";
        $filename = "PACKAGES - DISPATCH " . date('Y-m-d H:i:s') . ".csv";

        //create a file pointer
        $file = fopen('php://memory', 'w');

        //set column headers
        $fields = array('DATE' ,'HOUR', 'TEAM', 'DRIVER', 'PACKAGE ID', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'WEIGHT', 'ROUTE','TASK ONFLEET');

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

    public function GetAll()
    {
        $listPackageDispatch = PackageDispatch::where('status', 'Dispatch')
                                                ->where('idOnfleet', '!=', '')
                                                ->inRandomOrder()
                                                ->get()
                                                ->take(300);

        return ['listPackageDispatch' => $listPackageDispatch];
    }

    public function Insert(Request $request)
    {
        $validateDispatch = false;

        $package = PackageInbound::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();

        if($package)
        {
            $packageBlocked = PackageBlocked::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();

            if($packageBlocked)
            {
                return ['stateAction' => 'validatedFilterPackage', 'packageBlocked' => $packageBlocked, 'packageManifest' => null];
            }
        }

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
            $package = PackageDispatch::where('Reference_Number_1', $request->get('Reference_Number_1'))
                                                ->where('status', 'Delete')
                                                ->first();
        } 

        if($package)
        {
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

                if($package->status != 'Delete')
                {
                    try
                    {
                        DB::beginTransaction();

                        $nowDate    = date('Y-m-d H:i:s');
                        $created_at = date('H:i:s') > date('20:00:00') ? date('Y-m-d 04:00:00', strtotime($nowDate .'+1 day') ) : date('Y-m-d H:i:s');

                        if($package->status == 'On hold')
                        {
                            $packageHistory = new PackageHistory();
 
                            $packageHistory->id                           = uniqid();
                            $packageHistory->Reference_Number_1           = $package->Reference_Number_1;
                            $packageHistory->idCompany                    = $package->idCompany;
                            $packageHistory->company                      = $package->company;
                            $packageHistory->idStore                      = $package->idStore;
                            $packageHistory->store                        = $package->store;
                            $packageHistory->Reference_Number_2           = $package->Reference_Number_2;
                            $packageHistory->Reference_Number_3           = $package->Reference_Number_3;
                            $packageHistory->Ready_At                     = $package->Ready_At;
                            $packageHistory->Del_Date                     = $package->Del_Date;
                            $packageHistory->Del_no_earlier_than          = $package->Del_no_earlier_than;
                            $packageHistory->Del_no_later_than            = $package->Del_no_later_than;
                            $packageHistory->Pickup_Contact_Name          = $package->Pickup_Contact_Name;
                            $packageHistory->Pickup_Company               = $package->Pickup_Company;
                            $packageHistory->Pickup_Contact_Phone_Number  = $package->Pickup_Contact_Phone_Number;
                            $packageHistory->Pickup_Contact_Email         = $package->Pickup_Contact_Email;
                            $packageHistory->Pickup_Address_Line_1        = $package->Pickup_Address_Line_1;
                            $packageHistory->Pickup_Address_Line_2        = $package->Pickup_Address_Line_2;
                            $packageHistory->Pickup_City                  = $package->Pickup_City;
                            $packageHistory->Pickup_Province              = $package->Pickup_Province;
                            $packageHistory->Pickup_Postal_Code           = $package->Pickup_Postal_Code;
                            $packageHistory->Dropoff_Contact_Name         = $package->Dropoff_Contact_Name;
                            $packageHistory->Dropoff_Company              = $package->Dropoff_Company;
                            $packageHistory->Dropoff_Contact_Phone_Number = $package->Dropoff_Contact_Phone_Number;
                            $packageHistory->Dropoff_Contact_Email        = $package->Dropoff_Contact_Email;
                            $packageHistory->Dropoff_Address_Line_1       = $package->Dropoff_Address_Line_1;
                            $packageHistory->Dropoff_Address_Line_2       = $package->Dropoff_Address_Line_2;
                            $packageHistory->Dropoff_City                 = $package->Dropoff_City;
                            $packageHistory->Dropoff_Province             = $package->Dropoff_Province;
                            $packageHistory->Dropoff_Postal_Code          = $package->Dropoff_Postal_Code;
                            $packageHistory->Service_Level                = $package->Service_Level;
                            $packageHistory->Carrier_Name                 = $package->Carrier_Name;
                            $packageHistory->Vehicle_Type_Id              = $package->Vehicle_Type_Id;
                            $packageHistory->Notes                        = $package->Notes;
                            $packageHistory->Number_Of_Pieces             = $package->Number_Of_Pieces;
                            $packageHistory->Weight                       = $package->Weight;
                            $packageHistory->Route                        = $package->Route;
                            $packageHistory->Name                         = $package->Name;
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

                        $packageDispatch = new PackageDispatch();

                        $packageDispatch->Reference_Number_1           = $package->Reference_Number_1;
                        $packageDispatch->idCompany                    = $package->idCompany;
                        $packageDispatch->company                      = $package->company;
                        $packageDispatch->idStore                      = $package->idStore;
                        $packageDispatch->store                        = $package->store;
                        $packageDispatch->Reference_Number_2           = $package->Reference_Number_2;
                        $packageDispatch->Reference_Number_3           = $package->Reference_Number_3;
                        $packageDispatch->Ready_At                     = $package->Ready_At;
                        $packageDispatch->Del_Date                     = $package->Del_Date;
                        $packageDispatch->Del_no_earlier_than          = $package->Del_no_earlier_than;
                        $packageDispatch->Del_no_later_than            = $package->Del_no_later_than;
                        $packageDispatch->Pickup_Contact_Name          = $package->Pickup_Contact_Name;
                        $packageDispatch->Pickup_Company               = $package->Pickup_Company;
                        $packageDispatch->Pickup_Contact_Phone_Number  = $package->Pickup_Contact_Phone_Number;
                        $packageDispatch->Pickup_Contact_Email         = $package->Pickup_Contact_Email;
                        $packageDispatch->Pickup_Address_Line_1        = $package->Pickup_Address_Line_1;
                        $packageDispatch->Pickup_Address_Line_2        = $package->Pickup_Address_Line_2;
                        $packageDispatch->Pickup_City                  = $package->Pickup_City;
                        $packageDispatch->Pickup_Province              = $package->Pickup_Province;
                        $packageDispatch->Pickup_Postal_Code           = $package->Pickup_Postal_Code;
                        $packageDispatch->Dropoff_Contact_Name         = $package->Dropoff_Contact_Name;
                        $packageDispatch->Dropoff_Company              = $package->Dropoff_Company;
                        $packageDispatch->Dropoff_Contact_Phone_Number = $package->Dropoff_Contact_Phone_Number;
                        $packageDispatch->Dropoff_Contact_Email        = $package->Dropoff_Contact_Email;
                        $packageDispatch->Dropoff_Address_Line_1       = $package->Dropoff_Address_Line_1;
                        $packageDispatch->Dropoff_Address_Line_2       = $package->Dropoff_Address_Line_2;
                        $packageDispatch->Dropoff_City                 = $package->Dropoff_City;
                        $packageDispatch->Dropoff_Province             = $package->Dropoff_Province;
                        $packageDispatch->Dropoff_Postal_Code          = $package->Dropoff_Postal_Code;
                        $packageDispatch->Service_Level                = $package->Service_Level;
                        $packageDispatch->Carrier_Name                 = $package->Carrier_Name;
                        $packageDispatch->Vehicle_Type_Id              = $package->Vehicle_Type_Id;
                        $packageDispatch->Notes                        = $package->Notes;
                        $packageDispatch->Number_Of_Pieces             = $package->Number_Of_Pieces;
                        $packageDispatch->Weight                       = $package->Weight;
                        $packageDispatch->Route                        = $package->Route;
                        $packageDispatch->Name                         = $package->Name;
                        $packageDispatch->idUser                       = Auth::user()->id;
                        $packageDispatch->idTeam                       = $request->get('idTeam');
                        $packageDispatch->idUserDispatch               = $idUserDispatch;
                        $packageDispatch->Date_Dispatch                = $created_at;
                        $packageDispatch->quantity                     = $package->quantity;
                        $packageDispatch->status                       = 'Dispatch';
                        $packageDispatch->created_at                   = $created_at;
                        $packageDispatch->updated_at                   = $created_at;

                        $packageHistory = new PackageHistory();

                        $packageHistory->id                           = uniqid();
                        $packageHistory->Reference_Number_1           = $package->Reference_Number_1;
                        $packageHistory->idCompany                    = $package->idCompany;
                        $packageHistory->company                      = $package->company;
                        $packageHistory->idStore                      = $package->idStore;
                        $packageHistory->store                        = $package->store;
                        $packageHistory->Reference_Number_2           = $package->Reference_Number_2;
                        $packageHistory->Reference_Number_3           = $package->Reference_Number_3;
                        $packageHistory->Ready_At                     = $package->Ready_At;
                        $packageHistory->Del_Date                     = $package->Del_Date;
                        $packageHistory->Del_no_earlier_than          = $package->Del_no_earlier_than;
                        $packageHistory->Del_no_later_than            = $package->Del_no_later_than;
                        $packageHistory->Pickup_Contact_Name          = $package->Pickup_Contact_Name;
                        $packageHistory->Pickup_Company               = $package->Pickup_Company;
                        $packageHistory->Pickup_Contact_Phone_Number  = $package->Pickup_Contact_Phone_Number;
                        $packageHistory->Pickup_Contact_Email         = $package->Pickup_Contact_Email;
                        $packageHistory->Pickup_Address_Line_1        = $package->Pickup_Address_Line_1;
                        $packageHistory->Pickup_Address_Line_2        = $package->Pickup_Address_Line_2;
                        $packageHistory->Pickup_City                  = $package->Pickup_City;
                        $packageHistory->Pickup_Province              = $package->Pickup_Province;
                        $packageHistory->Pickup_Postal_Code           = $package->Pickup_Postal_Code;
                        $packageHistory->Dropoff_Contact_Name         = $package->Dropoff_Contact_Name;
                        $packageHistory->Dropoff_Company              = $package->Dropoff_Company;
                        $packageHistory->Dropoff_Contact_Phone_Number = $package->Dropoff_Contact_Phone_Number;
                        $packageHistory->Dropoff_Contact_Email        = $package->Dropoff_Contact_Email;
                        $packageHistory->Dropoff_Address_Line_1       = $package->Dropoff_Address_Line_1;
                        $packageHistory->Dropoff_Address_Line_2       = $package->Dropoff_Address_Line_2;
                        $packageHistory->Dropoff_City                 = $package->Dropoff_City;
                        $packageHistory->Dropoff_Province             = $package->Dropoff_Province;
                        $packageHistory->Dropoff_Postal_Code          = $package->Dropoff_Postal_Code;
                        $packageHistory->Service_Level                = $package->Service_Level;
                        $packageHistory->Carrier_Name                 = $package->Carrier_Name;
                        $packageHistory->Vehicle_Type_Id              = $package->Vehicle_Type_Id;
                        $packageHistory->Notes                        = $package->Notes;
                        $packageHistory->Number_Of_Pieces             = $package->Number_Of_Pieces;
                        $packageHistory->Weight                       = $package->Weight;
                        $packageHistory->Route                        = $package->Route;
                        $packageHistory->Name                         = $package->Name;
                        $packageHistory->idUser                       = Auth::user()->id;
                        $packageHistory->idTeam                       = $request->get('idTeam');
                        $packageHistory->idUserDispatch               = $idUserDispatch;
                        $packageHistory->Date_Dispatch                = $created_at;
                        $packageHistory->dispatch                     = 1;
                        $packageHistory->Description                  = $description;
                        $packageHistory->quantity                     = $package->quantity;
                        $packageHistory->status                       = 'Dispatch';
                        $packageHistory->created_at                   = $created_at;
                        $packageHistory->updated_at                   = $created_at;

                        $registerTask = $this->RegisterOnfleet($package, $team, $driver);

                        if($registerTask['status'] == 200)
                        {
                            $idOnfleet   = explode('"', explode('"', explode('":', $registerTask['response'])[1])[1])[0];
                            $taskOnfleet = explode('"', explode('"', explode('":', $registerTask['response'])[5])[1])[0];

                            $packageDispatch->idOnfleet   = $idOnfleet;
                            $packageDispatch->taskOnfleet = $taskOnfleet;

                            $packageDispatch->save();
                            $packageHistory->save();
                            $package->delete();

                            $dataTaskOnfleet = $this->GetOnfleet($idOnfleet);

                            $warnings = $dataTaskOnfleet['destination']['warnings'];

                            Log::info('============ START TASK CREATED ================');
                            Log::info("Reference_Number_1 :". $package->Reference_Number_1);
                            Log::info("Warnings: ". count($warnings));
                            Log::info($warnings);

                            if(count($warnings) >= 0)
                            {
                                DB::commit();

                                //data for INLAND
                                $packageController = new PackageController();
                                $packageController->SendStatusToInland($package, 'Dispatch', null);
                                //end data for inland

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
                    catch(Exception $e)
                    {
                        DB::rollback();

                        return ['stateAction' => true];
                    }
                }
                elseif($package->status == 'Delete')
                {
                    $nowDate    = date('Y-m-d H:i:s');
                    $created_at = date('H:i:s') > date('20:00:00') ? date('Y-m-d 04:00:00', strtotime($nowDate .'+1 day') ) : date('Y-m-d H:i:s');

                    $packageHistory = new PackageHistory();

                    $packageHistory->id                           = uniqid();
                    $packageHistory->Reference_Number_1           = $package->Reference_Number_1;
                    $packageHistory->idCompany                    = $package->idCompany;
                    $packageHistory->company                      = $package->company;
                    $packageHistory->idStore                      = $package->idStore;
                    $packageHistory->store                        = $package->store;
                    $packageHistory->Reference_Number_2           = $package->Reference_Number_2;
                    $packageHistory->Reference_Number_3           = $package->Reference_Number_3;
                    $packageHistory->Ready_At                     = $package->Ready_At;
                    $packageHistory->Del_Date                     = $package->Del_Date;
                    $packageHistory->Del_no_earlier_than          = $package->Del_no_earlier_than;
                    $packageHistory->Del_no_later_than            = $package->Del_no_later_than;
                    $packageHistory->Pickup_Contact_Name          = $package->Pickup_Contact_Name;
                    $packageHistory->Pickup_Company               = $package->Pickup_Company;
                    $packageHistory->Pickup_Contact_Phone_Number  = $package->Pickup_Contact_Phone_Number;
                    $packageHistory->Pickup_Contact_Email         = $package->Pickup_Contact_Email;
                    $packageHistory->Pickup_Address_Line_1        = $package->Pickup_Address_Line_1;
                    $packageHistory->Pickup_Address_Line_2        = $package->Pickup_Address_Line_2;
                    $packageHistory->Pickup_City                  = $package->Pickup_City;
                    $packageHistory->Pickup_Province              = $package->Pickup_Province;
                    $packageHistory->Pickup_Postal_Code           = $package->Pickup_Postal_Code;
                    $packageHistory->Dropoff_Contact_Name         = $package->Dropoff_Contact_Name;
                    $packageHistory->Dropoff_Company              = $package->Dropoff_Company;
                    $packageHistory->Dropoff_Contact_Phone_Number = $package->Dropoff_Contact_Phone_Number;
                    $packageHistory->Dropoff_Contact_Email        = $package->Dropoff_Contact_Email;
                    $packageHistory->Dropoff_Address_Line_1       = $package->Dropoff_Address_Line_1;
                    $packageHistory->Dropoff_Address_Line_2       = $package->Dropoff_Address_Line_2;
                    $packageHistory->Dropoff_City                 = $package->Dropoff_City;
                    $packageHistory->Dropoff_Province             = $package->Dropoff_Province;
                    $packageHistory->Dropoff_Postal_Code          = $package->Dropoff_Postal_Code;
                    $packageHistory->Service_Level                = $package->Service_Level;
                    $packageHistory->Carrier_Name                 = $package->Carrier_Name;
                    $packageHistory->Vehicle_Type_Id              = $package->Vehicle_Type_Id;
                    $packageHistory->Notes                        = $package->Notes;
                    $packageHistory->Number_Of_Pieces             = $package->Number_Of_Pieces;
                    $packageHistory->Weight                       = $package->Weight;
                    $packageHistory->Route                        = $package->Route;
                    $packageHistory->Name                         = $package->Name;
                    $packageHistory->idUser                       = Auth::user()->id;
                    $packageHistory->idTeam                       = $team->id;
                    $packageHistory->idUserDispatch               = $driver->id;
                    $packageHistory->Date_Dispatch                = date('Y-m-d H:s:i');
                    $packageHistory->dispatch                     = 1;
                    $packageHistory->Description                  = $description;
                    $packageHistory->quantity                     = $package->quantity;
                    $packageHistory->status                       = 'Dispatch';
                    $packageHistory->created_at                   = $created_at;
                    $packageHistory->updated_at                   = $created_at;
                    
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
                            $packageController->SendStatusToInland($package, 'Dispatch', null);
                            //end data for inland

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

            $assigned = Assigned::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();

            if($assigned)
            {
                return ['stateAction' => 'assigned'];
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

                $packageHistory = PackageHistory::where('Reference_Number_1', $request->get('Reference_Number_1'))
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
            $packageHistory->Reference_Number_2           = $packageDispatch->Reference_Number_2;
            $packageHistory->Reference_Number_3           = $packageDispatch->Reference_Number_3;
            $packageHistory->Ready_At                     = $packageDispatch->Ready_At;
            $packageHistory->Del_Date                     = $packageDispatch->Del_Date;
            $packageHistory->Del_no_earlier_than          = $packageDispatch->Del_no_earlier_than;
            $packageHistory->Del_no_later_than            = $packageDispatch->Del_no_later_than;
            $packageHistory->Pickup_Contact_Name          = $packageDispatch->Pickup_Contact_Name;
            $packageHistory->Pickup_Company               = $packageDispatch->Pickup_Company;
            $packageHistory->Pickup_Contact_Phone_Number  = $packageDispatch->Pickup_Contact_Phone_Number;
            $packageHistory->Pickup_Contact_Email         = $packageDispatch->Pickup_Contact_Email;
            $packageHistory->Pickup_Address_Line_1        = $packageDispatch->Pickup_Address_Line_1;
            $packageHistory->Pickup_Address_Line_2        = $packageDispatch->Pickup_Address_Line_2;
            $packageHistory->Pickup_City                  = $packageDispatch->Pickup_City;
            $packageHistory->Pickup_Province              = $packageDispatch->Pickup_Province;
            $packageHistory->Pickup_Postal_Code           = $packageDispatch->Pickup_Postal_Code;
            $packageHistory->Dropoff_Contact_Name         = $packageDispatch->Dropoff_Contact_Name;
            $packageHistory->Dropoff_Company              = $packageDispatch->Dropoff_Company;
            $packageHistory->Dropoff_Contact_Phone_Number = $packageDispatch->Dropoff_Contact_Phone_Number;
            $packageHistory->Dropoff_Contact_Email        = $packageDispatch->Dropoff_Contact_Email;
            $packageHistory->Dropoff_Address_Line_1       = $packageDispatch->Dropoff_Address_Line_1;
            $packageHistory->Dropoff_Address_Line_2       = $packageDispatch->Dropoff_Address_Line_2;
            $packageHistory->Dropoff_City                 = $packageDispatch->Dropoff_City;
            $packageHistory->Dropoff_Province             = $packageDispatch->Dropoff_Province;
            $packageHistory->Dropoff_Postal_Code          = $packageDispatch->Dropoff_Postal_Code;
            $packageHistory->Service_Level                = $packageDispatch->Service_Level;
            $packageHistory->Carrier_Name                 = $packageDispatch->Carrier_Name;
            $packageHistory->Vehicle_Type_Id              = $packageDispatch->Vehicle_Type_Id;
            $packageHistory->Notes                        = $packageDispatch->Notes;
            $packageHistory->Number_Of_Pieces             = $packageDispatch->Number_Of_Pieces;
            $packageHistory->Weight                       = $packageDispatch->Weight;
            $packageHistory->Route                        = $packageDispatch->Route;
            $packageHistory->Name                         = $packageDispatch->Name;
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
                                $idUserDispatch = $request->get('idDriver');

                                $user = User::find($idUserDispatch);

                                $description = 'Dispatch - for: '. Auth::user()->name .' '. Auth::user()->nameOfOwner .' to '. $user->nameTeam .' / '. $user->name .' '. $user->nameOfOwner;
                            }
                            elseif($request->get('idTeam'))
                            {
                                $idUserDispatch = $request->get('idTeam');

                                $user = User::find($idUserDispatch);

                                $description = 'Dispatch - for: '. Auth::user()->name .' '. Auth::user()->nameOfOwner .' to '. $user->name;
                            }

                            $packageDispatch = new PackageDispatch();

                            $packageDispatch->Reference_Number_1           = $package->Reference_Number_1;
                            $packageDispatch->idCompany                    = $package->idCompany;
                            $packageDispatch->company                      = $package->company;
                            $packageDispatch->idStore                      = $package->idStore;
                            $packageDispatch->store                        = $package->store;
                            $packageDispatch->Reference_Number_2           = $package->Reference_Number_2;
                            $packageDispatch->Reference_Number_3           = $package->Reference_Number_3;
                            $packageDispatch->Ready_At                     = $package->Ready_At;
                            $packageDispatch->Del_Date                     = $package->Del_Date;
                            $packageDispatch->Del_no_earlier_than          = $package->Del_no_earlier_than;
                            $packageDispatch->Del_no_later_than            = $package->Del_no_later_than;
                            $packageDispatch->Pickup_Contact_Name          = $package->Pickup_Contact_Name;
                            $packageDispatch->Pickup_Company               = $package->Pickup_Company;
                            $packageDispatch->Pickup_Contact_Phone_Number  = $package->Pickup_Contact_Phone_Number;
                            $packageDispatch->Pickup_Contact_Email         = $package->Pickup_Contact_Email;
                            $packageDispatch->Pickup_Address_Line_1        = $package->Pickup_Address_Line_1;
                            $packageDispatch->Pickup_Address_Line_2        = $package->Pickup_Address_Line_2;
                            $packageDispatch->Pickup_City                  = $package->Pickup_City;
                            $packageDispatch->Pickup_Province              = $package->Pickup_Province;
                            $packageDispatch->Pickup_Postal_Code           = $package->Pickup_Postal_Code;
                            $packageDispatch->Dropoff_Contact_Name         = $package->Dropoff_Contact_Name;
                            $packageDispatch->Dropoff_Company              = $package->Dropoff_Company;
                            $packageDispatch->Dropoff_Contact_Phone_Number = $package->Dropoff_Contact_Phone_Number;
                            $packageDispatch->Dropoff_Contact_Email        = $package->Dropoff_Contact_Email;
                            $packageDispatch->Dropoff_Address_Line_1       = $package->Dropoff_Address_Line_1;
                            $packageDispatch->Dropoff_Address_Line_2       = $package->Dropoff_Address_Line_2;
                            $packageDispatch->Dropoff_City                 = $package->Dropoff_City;
                            $packageDispatch->Dropoff_Province             = $package->Dropoff_Province;
                            $packageDispatch->Dropoff_Postal_Code          = $package->Dropoff_Postal_Code;
                            $packageDispatch->Service_Level                = $package->Service_Level;
                            $packageDispatch->Carrier_Name                 = $package->Carrier_Name;
                            $packageDispatch->Vehicle_Type_Id              = $package->Vehicle_Type_Id;
                            $packageDispatch->Notes                        = $package->Notes;
                            $packageDispatch->Number_Of_Pieces             = $package->Number_Of_Pieces;
                            $packageDispatch->Weight                       = $package->Weight;
                            $packageDispatch->Route                        = $package->Route;
                            $packageDispatch->Name                         = $package->Name;
                            $packageDispatch->idUser                       = Auth::user()->id;
                            $packageDispatch->idTeam                       = $request->get('idTeam');
                            $packageDispatch->idUserDispatch               = $idUserDispatch;
                            $packageDispatch->Date_Dispatch                = date('Y-m-d H:i:s');
                            $packageDispatch->quantity                     = $package->quantity;
                            $packageDispatch->status                       = 'Dispatch';
                            $packageDispatch->created_at                   = date('Y-m-d H:i:s');
                            $packageDispatch->updated_at                   = date('Y-m-d H:i:s');

                            $packageDispatch->save();

                            $packageHistory = new PackageHistory();

                            $packageHistory->id                           = uniqid();
                            $packageHistory->Reference_Number_1           = $package->Reference_Number_1;
                            $packageHistory->idCompany                    = $package->idCompany;
                            $packageHistory->company                      = $package->company;
                            $packageHistory->idStore                      = $package->idStore;
                            $packageHistory->store                        = $package->store;
                            $packageHistory->Reference_Number_2           = $package->Reference_Number_2;
                            $packageHistory->Reference_Number_3           = $package->Reference_Number_3;
                            $packageHistory->Ready_At                     = $package->Ready_At;
                            $packageHistory->Del_Date                     = $package->Del_Date;
                            $packageHistory->Del_no_earlier_than          = $package->Del_no_earlier_than;
                            $packageHistory->Del_no_later_than            = $package->Del_no_later_than;
                            $packageHistory->Pickup_Contact_Name          = $package->Pickup_Contact_Name;
                            $packageHistory->Pickup_Company               = $package->Pickup_Company;
                            $packageHistory->Pickup_Contact_Phone_Number  = $package->Pickup_Contact_Phone_Number;
                            $packageHistory->Pickup_Contact_Email         = $package->Pickup_Contact_Email;
                            $packageHistory->Pickup_Address_Line_1        = $package->Pickup_Address_Line_1;
                            $packageHistory->Pickup_Address_Line_2        = $package->Pickup_Address_Line_2;
                            $packageHistory->Pickup_City                  = $package->Pickup_City;
                            $packageHistory->Pickup_Province              = $package->Pickup_Province;
                            $packageHistory->Pickup_Postal_Code           = $package->Pickup_Postal_Code;
                            $packageHistory->Dropoff_Contact_Name         = $package->Dropoff_Contact_Name;
                            $packageHistory->Dropoff_Company              = $package->Dropoff_Company;
                            $packageHistory->Dropoff_Contact_Phone_Number = $package->Dropoff_Contact_Phone_Number;
                            $packageHistory->Dropoff_Contact_Email        = $package->Dropoff_Contact_Email;
                            $packageHistory->Dropoff_Address_Line_1       = $package->Dropoff_Address_Line_1;
                            $packageHistory->Dropoff_Address_Line_2       = $package->Dropoff_Address_Line_2;
                            $packageHistory->Dropoff_City                 = $package->Dropoff_City;
                            $packageHistory->Dropoff_Province             = $package->Dropoff_Province;
                            $packageHistory->Dropoff_Postal_Code          = $package->Dropoff_Postal_Code;
                            $packageHistory->Service_Level                = $package->Service_Level;
                            $packageHistory->Carrier_Name                 = $package->Carrier_Name;
                            $packageHistory->Vehicle_Type_Id              = $package->Vehicle_Type_Id;
                            $packageHistory->Notes                        = $package->Notes;
                            $packageHistory->Number_Of_Pieces             = $package->Number_Of_Pieces;
                            $packageHistory->Weight                       = $package->Weight;
                            $packageHistory->Route                        = $package->Route;
                            $packageHistory->Name                         = $package->Name;
                            $packageHistory->idUser                       = Auth::user()->id;
                            $packageHistory->idTeam                       = $request->get('idTeam');
                            $packageHistory->idUserDispatch               = $idUserDispatch;
                            $packageHistory->Date_Dispatch                = date('Y-m-d H:s:i');
                            $packageHistory->dispatch                     = 1;
                            $packageHistory->Description                  = $description;
                            $packageHistory->quantity                     = $package->quantity;
                            $packageHistory->status                       = 'Dispatch';
                            $packageHistory->created_at                   = date('Y-m-d H:i:s');
                            $packageHistory->updated_at                   = date('Y-m-d H:i:s');

                            $packageHistory->save();

                            $package->delete();
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
        $packageDispatch = PackageFailed::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();

        if($packageDispatch == null)
        {
            $packageDispatch = PackageDispatch::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();
        }

        if($packageDispatch)
        {
            if($packageDispatch->idUserDispatch == Auth::user()->id || Auth::user()->role->name == 'Administrador')
            {
                try
                {
                    DB::beginTransaction();
                    
                    $team                = User::find($packageDispatch->idTeam);
                    $driver              = User::find($packageDispatch->idUserDispatch);
                    $idOnfleet           = $packageDispatch->idOnfleet;
                    $taskOnfleet         = $packageDispatch->taskOnfleet;
                    $teamName            = $team->name;
                    $workerName          = $driver->name .' '. $driver->nameOfOwner;
                    $photoUrl            = '';
                    $statusOnfleet       = '';
                    $Date_Return         = date('Y-m-d H:i:s');
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

                    $packageReturn = new PackageReturn();

                    $packageReturn->id                           = uniqid();
                    $packageReturn->Reference_Number_1           = $packageDispatch->Reference_Number_1;
                    $packageReturn->idCompany                    = $packageDispatch->idCompany;
                    $packageReturn->company                      = $packageDispatch->company;
                    $packageReturn->idStore                      = $packageDispatch->idStore;
                    $packageReturn->store                        = $packageDispatch->store;
                    $packageReturn->Reference_Number_2           = $packageDispatch->Reference_Number_2;
                    $packageReturn->Reference_Number_3           = $packageDispatch->Reference_Number_3;
                    $packageReturn->Ready_At                     = $packageDispatch->Ready_At;
                    $packageReturn->Del_Date                     = $packageDispatch->Del_Date;
                    $packageReturn->Del_no_earlier_than          = $packageDispatch->Del_no_earlier_than;
                    $packageReturn->Del_no_later_than            = $packageDispatch->Del_no_later_than;
                    $packageReturn->Pickup_Contact_Name          = $packageDispatch->Pickup_Contact_Name;
                    $packageReturn->Pickup_Company               = $packageDispatch->Pickup_Company;
                    $packageReturn->Pickup_Contact_Phone_Number  = $packageDispatch->Pickup_Contact_Phone_Number;
                    $packageReturn->Pickup_Contact_Email         = $packageDispatch->Pickup_Contact_Email;
                    $packageReturn->Pickup_Address_Line_1        = $packageDispatch->Pickup_Address_Line_1;
                    $packageReturn->Pickup_Address_Line_2        = $packageDispatch->Pickup_Address_Line_2;
                    $packageReturn->Pickup_City                  = $packageDispatch->Pickup_City;
                    $packageReturn->Pickup_Province              = $packageDispatch->Pickup_Province;
                    $packageReturn->Pickup_Postal_Code           = $packageDispatch->Pickup_Postal_Code;
                    $packageReturn->Dropoff_Contact_Name         = $packageDispatch->Dropoff_Contact_Name;
                    $packageReturn->Dropoff_Company              = $packageDispatch->Dropoff_Company;
                    $packageReturn->Dropoff_Contact_Phone_Number = $packageDispatch->Dropoff_Contact_Phone_Number;
                    $packageReturn->Dropoff_Contact_Email        = $packageDispatch->Dropoff_Contact_Email;
                    $packageReturn->Dropoff_Address_Line_1       = $packageDispatch->Dropoff_Address_Line_1;
                    $packageReturn->Dropoff_Address_Line_2       = $packageDispatch->Dropoff_Address_Line_2;
                    $packageReturn->Dropoff_City                 = $packageDispatch->Dropoff_City;
                    $packageReturn->Dropoff_Province             = $packageDispatch->Dropoff_Province;
                    $packageReturn->Dropoff_Postal_Code          = $packageDispatch->Dropoff_Postal_Code;
                    $packageReturn->Service_Level                = $packageDispatch->Service_Level;
                    $packageReturn->Carrier_Name                 = $packageDispatch->Carrier_Name;
                    $packageReturn->Vehicle_Type_Id              = $packageDispatch->Vehicle_Type_Id;
                    $packageReturn->Notes                        = $packageDispatch->Notes;
                    $packageReturn->Number_Of_Pieces             = $packageDispatch->Number_Of_Pieces;
                    $packageReturn->Weight                       = $packageDispatch->Weight;
                    $packageReturn->Route                        = $packageDispatch->Route;
                    $packageReturn->Name                         = $packageDispatch->Name;
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
                    $packageReturn->status                       = 'Return';

                    $packageReturn->save();

                    //update dispatch
                    $packageHistory = PackageHistory::where('Reference_Number_1', $request->get('Reference_Number_1'))
                                                    ->where('dispatch', 1)
                                                    ->first();

                    $packageHistory->dispatch = 0;

                    $packageHistory->save();

                    //update inbound
                    $packageHistory = PackageHistory::where('Reference_Number_1', $request->get('Reference_Number_1'))
                                                    ->where('inbound', 1)
                                                    ->first();

                    if($packageHistory)
                    {
                        $packageHistory->inbound  = 0;

                        $packageHistory->save();
                    }

                    $comment      = Comment::where('description', $request->get('Description_Return'))->first();
                    $statusReturn = 'Final';


                    if($comment->finalStatus == 0)
                    {
                        $statusReturn = 'ReInbound';

                        $packageWarehouse = new PackageWarehouse();

                        $packageWarehouse->Reference_Number_1           = $packageDispatch->Reference_Number_1;
                        $packageWarehouse->idCompany                    = $packageDispatch->idCompany;
                        $packageWarehouse->company                      = $packageDispatch->company;
                        $packageWarehouse->idStore                      = $packageDispatch->idStore;
                        $packageWarehouse->store                        = $packageDispatch->store;
                        $packageWarehouse->Reference_Number_2           = $packageDispatch->Reference_Number_2;
                        $packageWarehouse->Reference_Number_3           = $packageDispatch->Reference_Number_3;
                        $packageWarehouse->Ready_At                     = $packageDispatch->Ready_At;
                        $packageWarehouse->Del_Date                     = $packageDispatch->Del_Date;
                        $packageWarehouse->Del_no_earlier_than          = $packageDispatch->Del_no_earlier_than;
                        $packageWarehouse->Del_no_later_than            = $packageDispatch->Del_no_later_than;
                        $packageWarehouse->Pickup_Contact_Name          = $packageDispatch->Pickup_Contact_Name;
                        $packageWarehouse->Pickup_Company               = $packageDispatch->Pickup_Company;
                        $packageWarehouse->Pickup_Contact_Phone_Number  = $packageDispatch->Pickup_Contact_Phone_Number;
                        $packageWarehouse->Pickup_Contact_Email         = $packageDispatch->Pickup_Contact_Email;
                        $packageWarehouse->Pickup_Address_Line_1        = $packageDispatch->Pickup_Address_Line_1;
                        $packageWarehouse->Pickup_Address_Line_2        = $packageDispatch->Pickup_Address_Line_2;
                        $packageWarehouse->Pickup_City                  = $packageDispatch->Pickup_City;
                        $packageWarehouse->Pickup_Province              = $packageDispatch->Pickup_Province;
                        $packageWarehouse->Pickup_Postal_Code           = $packageDispatch->Pickup_Postal_Code;
                        $packageWarehouse->Dropoff_Contact_Name         = $packageDispatch->Dropoff_Contact_Name;
                        $packageWarehouse->Dropoff_Company              = $packageDispatch->Dropoff_Company;
                        $packageWarehouse->Dropoff_Contact_Phone_Number = $packageDispatch->Dropoff_Contact_Phone_Number;
                        $packageWarehouse->Dropoff_Contact_Email        = $packageDispatch->Dropoff_Contact_Email;
                        $packageWarehouse->Dropoff_Address_Line_1       = $packageDispatch->Dropoff_Address_Line_1;
                        $packageWarehouse->Dropoff_Address_Line_2       = $packageDispatch->Dropoff_Address_Line_2;
                        $packageWarehouse->Dropoff_City                 = $packageDispatch->Dropoff_City;
                        $packageWarehouse->Dropoff_Province             = $packageDispatch->Dropoff_Province;
                        $packageWarehouse->Dropoff_Postal_Code          = $packageDispatch->Dropoff_Postal_Code;
                        $packageWarehouse->Service_Level                = $packageDispatch->Service_Level;
                        $packageWarehouse->Carrier_Name                 = $packageDispatch->Carrier_Name;
                        $packageWarehouse->Vehicle_Type_Id              = $packageDispatch->Vehicle_Type_Id;
                        $packageWarehouse->Notes                        = $packageDispatch->Notes;
                        $packageWarehouse->Number_Of_Pieces             = $packageDispatch->Number_Of_Pieces;
                        $packageWarehouse->Weight                       = $packageDispatch->Weight;
                        $packageWarehouse->Route                        = $packageDispatch->Route;
                        $packageWarehouse->Name                         = $packageDispatch->Name;
                        $packageWarehouse->idUser                       = Auth::user()->id;
                        $packageWarehouse->quantity                     = $packageDispatch->quantity;
                        $packageWarehouse->status                       = 'Warehouse';

                        $packageWarehouse->save();

                        $packageHistory = new PackageHistory();

                        $packageHistory->id                           = uniqid();
                        $packageHistory->Reference_Number_1           = $packageDispatch->Reference_Number_1;
                        $packageHistory->idCompany                    = $packageDispatch->idCompany;
                        $packageHistory->company                      = $packageDispatch->company;
                        $packageHistory->idStore                      = $packageDispatch->idStore;
                        $packageHistory->store                        = $packageDispatch->store;
                        $packageHistory->Reference_Number_2           = $packageDispatch->Reference_Number_2;
                        $packageHistory->Reference_Number_3           = $packageDispatch->Reference_Number_3;
                        $packageHistory->Ready_At                     = $packageDispatch->Ready_At;
                        $packageHistory->Del_Date                     = $packageDispatch->Del_Date;
                        $packageHistory->Del_no_earlier_than          = $packageDispatch->Del_no_earlier_than;
                        $packageHistory->Del_no_later_than            = $packageDispatch->Del_no_later_than;
                        $packageHistory->Pickup_Contact_Name          = $packageDispatch->Pickup_Contact_Name;
                        $packageHistory->Pickup_Company               = $packageDispatch->Pickup_Company;
                        $packageHistory->Pickup_Contact_Phone_Number  = $packageDispatch->Pickup_Contact_Phone_Number;
                        $packageHistory->Pickup_Contact_Email         = $packageDispatch->Pickup_Contact_Email;
                        $packageHistory->Pickup_Address_Line_1        = $packageDispatch->Pickup_Address_Line_1;
                        $packageHistory->Pickup_Address_Line_2        = $packageDispatch->Pickup_Address_Line_2;
                        $packageHistory->Pickup_City                  = $packageDispatch->Pickup_City;
                        $packageHistory->Pickup_Province              = $packageDispatch->Pickup_Province;
                        $packageHistory->Pickup_Postal_Code           = $packageDispatch->Pickup_Postal_Code;
                        $packageHistory->Dropoff_Contact_Name         = $packageDispatch->Dropoff_Contact_Name;
                        $packageHistory->Dropoff_Company              = $packageDispatch->Dropoff_Company;
                        $packageHistory->Dropoff_Contact_Phone_Number = $packageDispatch->Dropoff_Contact_Phone_Number;
                        $packageHistory->Dropoff_Contact_Email        = $packageDispatch->Dropoff_Contact_Email;
                        $packageHistory->Dropoff_Address_Line_1       = $packageDispatch->Dropoff_Address_Line_1;
                        $packageHistory->Dropoff_Address_Line_2       = $packageDispatch->Dropoff_Address_Line_2;
                        $packageHistory->Dropoff_City                 = $packageDispatch->Dropoff_City;
                        $packageHistory->Dropoff_Province             = $packageDispatch->Dropoff_Province;
                        $packageHistory->Dropoff_Postal_Code          = $packageDispatch->Dropoff_Postal_Code;
                        $packageHistory->Service_Level                = $packageDispatch->Service_Level;
                        $packageHistory->Carrier_Name                 = $packageDispatch->Carrier_Name;
                        $packageHistory->Vehicle_Type_Id              = $packageDispatch->Vehicle_Type_Id;
                        $packageHistory->Notes                        = $packageDispatch->Notes;
                        $packageHistory->Number_Of_Pieces             = $packageDispatch->Number_Of_Pieces;
                        $packageHistory->Weight                       = $packageDispatch->Weight;
                        $packageHistory->Route                        = $packageDispatch->Route;
                        $packageHistory->Name                         = $packageDispatch->Name;
                        $packageHistory->idUser                       = Auth::user()->id;
                        $packageHistory->Description                  = 'For: '. Auth::user()->name .' '. Auth::user()->nameOfOwner;
                        $packageHistory->quantity                     = $packageDispatch->quantity;
                        $packageHistory->status                       = 'Warehouse';
                        $packageHistory->created_at                   = date('Y-m-d H:i:s', strtotime('+5 second', strtotime(date('Y-m-d H:i:s'))));
                        $packageHistory->updated_at                   = date('Y-m-d H:i:s', strtotime('+5 second', strtotime(date('Y-m-d H:i:s'))));

                        $packageHistory->save();
                    }

                    $packageHistory = new PackageHistory();

                    $packageHistory->id                           = uniqid();
                    $packageHistory->Reference_Number_1           = $packageDispatch->Reference_Number_1;
                    $packageHistory->idCompany                    = $packageDispatch->idCompany;
                    $packageHistory->company                      = $packageDispatch->company;
                    $packageHistory->idStore                      = $packageDispatch->idStore;
                    $packageHistory->store                        = $packageDispatch->store;
                    $packageHistory->Reference_Number_2           = $packageDispatch->Reference_Number_2;
                    $packageHistory->Reference_Number_3           = $packageDispatch->Reference_Number_3;
                    $packageHistory->Ready_At                     = $packageDispatch->Ready_At;
                    $packageHistory->Del_Date                     = $packageDispatch->Del_Date;
                    $packageHistory->Del_no_earlier_than          = $packageDispatch->Del_no_earlier_than;
                    $packageHistory->Del_no_later_than            = $packageDispatch->Del_no_later_than;
                    $packageHistory->Pickup_Contact_Name          = $packageDispatch->Pickup_Contact_Name;
                    $packageHistory->Pickup_Company               = $packageDispatch->Pickup_Company;
                    $packageHistory->Pickup_Contact_Phone_Number  = $packageDispatch->Pickup_Contact_Phone_Number;
                    $packageHistory->Pickup_Contact_Email         = $packageDispatch->Pickup_Contact_Email;
                    $packageHistory->Pickup_Address_Line_1        = $packageDispatch->Pickup_Address_Line_1;
                    $packageHistory->Pickup_Address_Line_2        = $packageDispatch->Pickup_Address_Line_2;
                    $packageHistory->Pickup_City                  = $packageDispatch->Pickup_City;
                    $packageHistory->Pickup_Province              = $packageDispatch->Pickup_Province;
                    $packageHistory->Pickup_Postal_Code           = $packageDispatch->Pickup_Postal_Code;
                    $packageHistory->Dropoff_Contact_Name         = $packageDispatch->Dropoff_Contact_Name;
                    $packageHistory->Dropoff_Company              = $packageDispatch->Dropoff_Company;
                    $packageHistory->Dropoff_Contact_Phone_Number = $packageDispatch->Dropoff_Contact_Phone_Number;
                    $packageHistory->Dropoff_Contact_Email        = $packageDispatch->Dropoff_Contact_Email;
                    $packageHistory->Dropoff_Address_Line_1       = $packageDispatch->Dropoff_Address_Line_1;
                    $packageHistory->Dropoff_Address_Line_2       = $packageDispatch->Dropoff_Address_Line_2;
                    $packageHistory->Dropoff_City                 = $packageDispatch->Dropoff_City;
                    $packageHistory->Dropoff_Province             = $packageDispatch->Dropoff_Province;
                    $packageHistory->Dropoff_Postal_Code          = $packageDispatch->Dropoff_Postal_Code;
                    $packageHistory->Service_Level                = $packageDispatch->Service_Level;
                    $packageHistory->Carrier_Name                 = $packageDispatch->Carrier_Name;
                    $packageHistory->Vehicle_Type_Id              = $packageDispatch->Vehicle_Type_Id;
                    $packageHistory->Notes                        = $packageDispatch->Notes;
                    $packageHistory->Number_Of_Pieces             = $packageDispatch->Number_Of_Pieces;
                    $packageHistory->Weight                       = $packageDispatch->Weight;
                    $packageHistory->Route                        = $packageDispatch->Route;
                    $packageHistory->Name                         = $packageDispatch->Name;
                    $packageHistory->idUser                       = Auth::user()->id;
                    $packageHistory->idUserInbound                = Auth::user()->id;
                    $packageHistory->Date_Inbound                 = date('Y-m-d H:s:i');
                    $packageHistory->Description                  = 'For: '. Auth::user()->name .' '. Auth::user()->nameOfOwner;
                    $packageHistory->Description_Return           = $Description_Return;
                    $packageHistory->Description_Onfleet          = $Description_Onfleet;
                    $packageHistory->inbound                      = 1;
                    $packageHistory->quantity                     = $packageDispatch->quantity;
                    $packageHistory->status                       = $statusReturn;
                    $packageHistory->created_at                   = date('Y-m-d H:i:s');
                    $packageHistory->updated_at                   = date('Y-m-d H:i:s');

                    $packageHistory->save(); 

                    $deleteDispatch = true;

                    if($onfleet)
                    {
                        if($onfleet['state'] == 1)
                        {
                            $deleteOnfleet  = $this->DeleteOnfleet($packageDispatch->idOnfleet);
                            $deleteDispatch = $deleteOnfleet ? true : false;
                        }
                    }

                    if($comment->finalStatus == 0)
                    {
                        //data for INLAND
                        $packageController = new PackageController();
                        $packageController->SendStatusToInland($packageDispatch, 'ReInbound', null);
                        //end data for inland
                    }
                    else
                    {
                        //data for INLAND
                        $packageController = new PackageController();
                        $packageController->SendStatusToInland($packageDispatch, 'Return', $comment->statusCode);
                        //end data for inland
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

        return ['stateAction' => 'notDispatch'];
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

        if($http_status == 200)
        {
            return ['status' => 200, 'response' => $output];
        }
        else
        {
            return ['status' => false, $output];
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