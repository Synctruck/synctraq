<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{Assigned, Configuration, Driver, PackageHistory, PackageDispatch, PackageInbound, PackageManifest, PackageNotExists, PackageReturn, PackageReturnCompany, TeamRoute, User};

use Illuminate\Support\Facades\Validator;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PhpOffice\PhpOfficePhpSpreadsheetSpreadsheet;
use PhpOffice\PhpOfficePhpSpreadsheetReaderCsv;
use PhpOffice\PhpOfficePhpSpreadsheetReaderXlsx;

use DB;
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

    public function List(Request $request, $dateStart,$dateEnd, $idTeam, $idDriver, $state, $routes)
    {

        $dateStart = $dateStart .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $roleUser = '';

        if(Session::get('user')->role->name == 'Driver')
        {
            $packageDispatchList = PackageDispatch::with(['driver.role', 'driver'])
                                                    ->where('idUserDispatch', Session::get('user')->id)
                                                    ->where('status', 'Dispatch');

            $roleUser = 'Driver';
        }
        elseif(Session::get('user')->role->name == 'Team')
        {
            $drivers = Driver::where('idTeam', Session::get('user')->id)->get('id');

            $idUsers = [];

            foreach($drivers as $driver)
            {
                array_push($idUsers, $driver->id);
            }

            array_push($idUsers, Session::get('user')->id);

            $packageDispatchList = PackageDispatch::whereBetween('created_at', [$dateStart, $dateEnd]);

            $roleUser = 'Team';
        }
        else
        {
            $packageDispatchList = PackageDispatch::with(['driver.role', 'driver'])
                                                ->where('status', 'Dispatch');

            $roleUser = 'Administrador';
        }

        $packageDispatchList = $packageDispatchList->whereBetween('created_at', [$dateStart, $dateEnd]);

        if($idTeam && $idDriver)
        {
            $packageDispatchList = $packageDispatchList->where('idUserDispatch', $idDriver);
        }
        elseif($idTeam)
        {
            $idsUser = User::where('idTeam', $idTeam)->orWhere('id', $idTeam)->get('id');

            $packageDispatchList = $packageDispatchList->whereIn('idUserDispatch', $idsUser);
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

        $packageDispatchList = $packageDispatchList->orderBy('Date_Dispatch', 'desc')
                                                   ->paginate(50);

        $quantityDispatch = $packageDispatchList->total();

        $listState = PackageDispatch::select('Dropoff_Province')
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        return ['packageDispatchList' => $packageDispatchList, 'quantityDispatch' => $quantityDispatch, 'roleUser' => $roleUser, 'listState' => $listState];
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

        $dateStart = $dateStart .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        if(Session::get('user')->role->name == 'Driver')
        {
            $packageDispatchList = PackageDispatch::with(['driver.role', 'driver'])
                                                    ->where('idUserDispatch', Session::get('user')->id)
                                                    ->where('status', 'Dispatch');
        }
        elseif(Session::get('user')->role->name == 'Team')
        {
            $drivers = Driver::where('idTeam', Session::get('user')->id)->get('id');

            $idUsers = [];

            foreach($drivers as $driver)
            {
                array_push($idUsers, $driver->id);
            }

            array_push($idUsers, Session::get('user')->id);

            $packageDispatchList = PackageDispatch::whereBetween('created_at', [$dateStart, $dateEnd]);
        }
        else
        {
            $packageDispatchList = PackageDispatch::with(['driver.role', 'driver'])
                                                ->where('status', 'Dispatch');
        }

        $packageDispatchList = $packageDispatchList->whereBetween('created_at', [$dateStart, $dateEnd]);

        if($idTeam && $idDriver)
        {
            $packageDispatchList = $packageDispatchList->where('idUserDispatch', $idDriver);
        }
        elseif($idTeam)
        {
            $idsUser = User::where('idTeam', $idTeam)->orWhere('id', $idTeam)->get('id');

            $packageDispatchList = $packageDispatchList->whereIn('idUserDispatch', $idsUser);
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

        $packageDispatchList = $packageDispatchList->orderBy('Date_Dispatch', 'desc')->get();

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

        if(!$package)
        {
           $package = PackageManifest::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();
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

            $idUserDispatch = 0;

            if($request->get('idTeam') && $request->get('idDriver'))
            {
                $idUserDispatch = $request->get('idDriver');

                $user = User::find($idUserDispatch);

                $team   = User::find($request->get('idTeam'));
                $driver = $user;

                $description = 'Dispatch - for: '. Session::get('user')->name .' '. Session::get('user')->nameOfOwner .' to '. $user->nameTeam .' / '. $user->name .' '. $user->nameOfOwner;
            }
            elseif($request->get('idTeam'))
            {
                $idUserDispatch = $request->get('idTeam');

                $user = User::find($idUserDispatch);

                $description = 'Dispatch - for: '. Session::get('user')->name .' '. Session::get('user')->nameOfOwner .' to '. $user->name;
            }

            try
            {
                DB::beginTransaction();

                if(env('APP_ENV') == 'local')
                {
                    $registerTask = $this->RegisterOnfleet($package, $team, $driver);

                    if($registerTask['status'] == 200)
                    {
                        $idOnfleet   = explode('"', explode('"', explode('":', $registerTask['response'])[1])[1])[0];
                        $taskOnfleet = explode('"', explode('"', explode('":', $registerTask['response'])[5])[1])[0];

                        $registerTask = 200;
                    }
                    else
                    {
                        return ['stateAction' => 'repairPackage'];
                    }
                }
                else
                {
                    $idOnfleet   = '';
                    $taskOnfleet = '';

                    $registerTask = 200;
                }

                if($package->status == 'On hold' && $registerTask == 200)
                {
                    $packageHistory = new PackageHistory();

                    $packageHistory->id                           = uniqid();
                    $packageHistory->Reference_Number_1           = $package->Reference_Number_1;
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
                    $packageHistory->idUser                       = Session::get('user')->id;
                    $packageHistory->idUserInbound                = Session::get('user')->id;
                    $packageHistory->Date_Inbound                 = date('Y-m-d H:s:i');
                    $packageHistory->Description                  = 'Inbound - for: '. Session::get('user')->name .' '. Session::get('user')->nameOfOwner;
                    $packageHistory->inbound                      = 1;
                    $packageHistory->status                       = 'Inbound';

                    $packageHistory->save();
                }

                $packageDispatch = new PackageDispatch();

                $packageDispatch->Reference_Number_1           = $package->Reference_Number_1;
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
                $packageDispatch->idUser                       = Session::get('user')->id;
                $packageDispatch->idUserDispatch               = $idUserDispatch;
                $packageDispatch->Date_Dispatch                = date('Y-m-d H:i:s');
                $packageDispatch->status                       = 'Dispatch';
                $packageDispatch->idOnfleet                    = $idOnfleet;
                $packageDispatch->taskOnfleet                  = $taskOnfleet;

                $packageDispatch->save();

                $packageHistory = new PackageHistory();

                $packageHistory->id                           = uniqid();
                $packageHistory->Reference_Number_1           = $package->Reference_Number_1;
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
                $packageHistory->idUser                       = Session::get('user')->id;
                $packageHistory->idUserDispatch               = $idUserDispatch;
                $packageHistory->Date_Dispatch                = date('Y-m-d H:s:i');
                $packageHistory->dispatch                     = 1;
                $packageHistory->Description                  = $description;
                $packageHistory->status                       = 'Dispatch';

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
                    $packageNotExists->idUser             = Session::get('user')->id;
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
            $packageHistory->idUser                       = Session::get('user')->id;
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

                    $package         = packageInbound::find($row[0]);
                    $packageDispatch = PackageDispatch::find($row[0]);

                    if($package && $packageDispatch == null)
                    {
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

                                $description = 'Dispatch - for: '. Session::get('user')->name .' '. Session::get('user')->nameOfOwner .' to '. $user->nameTeam .' / '. $user->name .' '. $user->nameOfOwner;
                            }
                            elseif($request->get('idTeam'))
                            {
                                $idUserDispatch = $request->get('idTeam');

                                $user = User::find($idUserDispatch);

                                $description = 'Dispatch - for: '. Session::get('user')->name .' '. Session::get('user')->nameOfOwner .' to '. $user->name;
                            }

                            $packageDispatch = new PackageDispatch();

                            $packageDispatch->Reference_Number_1           = $package->Reference_Number_1;
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
                            $packageDispatch->idUser                       = Session::get('user')->id;
                            $packageDispatch->idUserDispatch               = $idUserDispatch;
                            $packageDispatch->Date_Dispatch                = date('Y-m-d H:i:s');
                            $packageDispatch->status                       = 'Dispatch';

                            $packageDispatch->save();

                            $package->delete();

                            $packageHistory = new PackageHistory();

                            $packageHistory->id                           = uniqid();
                            $packageHistory->Reference_Number_1           = $package->Reference_Number_1;
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
                            $packageHistory->idUser                       = Session::get('user')->id;
                            $packageHistory->idUserDispatch               = $idUserDispatch;
                            $packageHistory->Date_Dispatch                = date('Y-m-d H:s:i');
                            $packageHistory->dispatch                     = 1;
                            $packageHistory->Description                  = $description;
                            $packageHistory->status                       = 'Dispatch';

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
        $packageDispatch = PackageDispatch::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();

        if($packageDispatch)
        {
            if($packageDispatch->idUserDispatch == Session::get('user')->id || Session::get('user')->role->name == 'Administrador')
            {
                $user = User::find($packageDispatch->idUserDispatch);

                if($user->nameTeam)
                {
                    $description = 'Return - for: '. Session::get('user')->name .' '. Session::get('user')->nameOfOwner .' to '. $user->nameTeam .' / '. $user->name .' '. $user->nameOfOwner;
                }
                else
                {
                    $description = 'Return - for: '. Session::get('user')->name .' '. Session::get('user')->nameOfOwner .' to '. $user->name;
                }

                try
                {
                    DB::beginTransaction();

                    $returnPackage = false;

                    $idOnfleet     = '';
                    $taskOnfleet   = '';
                    $team          = '';
                    $workerName    = '';
                    $photoUrl      = '';
                    $statusOnfleet = '';
                    $onfleet       = '';

                    $team       = $user->nameTeam;
                    $workerName = $user->name .' '. $user->nameOfOwner;

                    $Date_Return         = date('Y-m-d H:i:s');
                    $Description_Return  = $request->get('Description_Return');
                    $Description_Onfleet = '';

                    if(env('APP_ENV') == 'local' && $packageDispatch->idOnfleet)
                    {
                        $onfleet = $this->GetOnfleet($packageDispatch->idOnfleet);

                        if($onfleet)
                        {
                            $idOnfleet           = $packageDispatch->idOnfleet;
                            $taskOnfleet         = $packageDispatch->taskOnfleet;
                            $Description_Onfleet = $onfleet['completionDetails']['failureReason'] .': '. $onfleet['completionDetails']['failureNotes'];
                            $Date_Return         = date('Y-m-d H:i:s');

                            if($onfleet['state'] == 3)
                            {
                                $statusOnfleet = $onfleet['completionDetails']['success'] == true ? $onfleet['state'] .' (error success)' : $onfleet['state'];
                                $returnPackage = true;
                                $Date_Return   = date('Y-m-d H:i:s', $onfleet['completionDetails']['time'] / 1000);

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
                    }
                    else
                    {
                        $taskOnfleet        = '';
                    }

                    $packageReturn = new PackageReturn();

                    $packageReturn->id                           = uniqid();
                    $packageReturn->Reference_Number_1           = $packageDispatch->Reference_Number_1;
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
                    $packageReturn->idUser                       = Session::get('user')->id;
                    $packageReturn->idUserReturn                 = $packageDispatch->idUserDispatch;
                    $packageReturn->Date_Return                  = $Date_Return;
                    $packageReturn->Description_Return           = $Description_Return;
                    $packageReturn->Description_Onfleet          = $Description_Onfleet;
                    $packageReturn->idOnfleet                    = $idOnfleet;
                    $packageReturn->taskOnfleet                  = $taskOnfleet;
                    $packageReturn->team                         = $team;
                    $packageReturn->workerName                   = $workerName;
                    $packageReturn->photoUrl                     = $photoUrl;
                    $packageReturn->statusOnfleet                = $statusOnfleet;

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

                    $packageInbound = new PackageInbound();

                    $packageInbound->Reference_Number_1           = $packageDispatch->Reference_Number_1;
                    $packageInbound->Reference_Number_2           = $packageDispatch->Reference_Number_2;
                    $packageInbound->Reference_Number_3           = $packageDispatch->Reference_Number_3;
                    $packageInbound->Ready_At                     = $packageDispatch->Ready_At;
                    $packageInbound->Del_Date                     = $packageDispatch->Del_Date;
                    $packageInbound->Del_no_earlier_than          = $packageDispatch->Del_no_earlier_than;
                    $packageInbound->Del_no_later_than            = $packageDispatch->Del_no_later_than;
                    $packageInbound->Pickup_Contact_Name          = $packageDispatch->Pickup_Contact_Name;
                    $packageInbound->Pickup_Company               = $packageDispatch->Pickup_Company;
                    $packageInbound->Pickup_Contact_Phone_Number  = $packageDispatch->Pickup_Contact_Phone_Number;
                    $packageInbound->Pickup_Contact_Email         = $packageDispatch->Pickup_Contact_Email;
                    $packageInbound->Pickup_Address_Line_1        = $packageDispatch->Pickup_Address_Line_1;
                    $packageInbound->Pickup_Address_Line_2        = $packageDispatch->Pickup_Address_Line_2;
                    $packageInbound->Pickup_City                  = $packageDispatch->Pickup_City;
                    $packageInbound->Pickup_Province              = $packageDispatch->Pickup_Province;
                    $packageInbound->Pickup_Postal_Code           = $packageDispatch->Pickup_Postal_Code;
                    $packageInbound->Dropoff_Contact_Name         = $packageDispatch->Dropoff_Contact_Name;
                    $packageInbound->Dropoff_Company              = $packageDispatch->Dropoff_Company;
                    $packageInbound->Dropoff_Contact_Phone_Number = $packageDispatch->Dropoff_Contact_Phone_Number;
                    $packageInbound->Dropoff_Contact_Email        = $packageDispatch->Dropoff_Contact_Email;
                    $packageInbound->Dropoff_Address_Line_1       = $packageDispatch->Dropoff_Address_Line_1;
                    $packageInbound->Dropoff_Address_Line_2       = $packageDispatch->Dropoff_Address_Line_2;
                    $packageInbound->Dropoff_City                 = $packageDispatch->Dropoff_City;
                    $packageInbound->Dropoff_Province             = $packageDispatch->Dropoff_Province;
                    $packageInbound->Dropoff_Postal_Code          = $packageDispatch->Dropoff_Postal_Code;
                    $packageInbound->Service_Level                = $packageDispatch->Service_Level;
                    $packageInbound->Carrier_Name                 = $packageDispatch->Carrier_Name;
                    $packageInbound->Vehicle_Type_Id              = $packageDispatch->Vehicle_Type_Id;
                    $packageInbound->Notes                        = $packageDispatch->Notes;
                    $packageInbound->Number_Of_Pieces             = $packageDispatch->Number_Of_Pieces;
                    $packageInbound->Weight                       = $packageDispatch->Weight;
                    $packageInbound->Route                        = $packageDispatch->Route;
                    $packageInbound->Name                         = $packageDispatch->Name;
                    $packageInbound->idUser                       = Session::get('user')->id;
                    $packageInbound->reInbound                    = 1;
                    $packageInbound->status                       = 'Inbound';

                    $packageInbound->save();

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
                    $packageHistory->idUser                       = Session::get('user')->id;
                    $packageHistory->idUserInbound                = Session::get('user')->id;
                    $packageHistory->Date_Inbound                 = date('Y-m-d H:s:i');
                    $packageHistory->Description                  = 'Re-Inbound - for: '. Session::get('user')->name .' '. Session::get('user')->nameOfOwner;
                    $packageHistory->Description_Return           = $Description_Return;
                    $packageHistory->Description_Onfleet          = $Description_Onfleet;
                    $packageHistory->inbound                      = 1;
                    $packageHistory->status                       = 'ReInbound';

                    $packageHistory->save();

                    $packageDispatch->delete();

                    if($onfleet)
                    {
                        if($onfleet['state'] == 1)
                        {
                            $statusOnfleet = 1;

                            $onfleet = $this->DeleteOnfleet($packageDispatch->idOnfleet);
                        }
                    }

                    DB::commit();

                    return ['stateAction' => true];
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
        $data = [   "destination" =>  [
                        "address" =>  [
                            "unparsed" =>  $package->Dropoff_Address_Line_1 .', '. $package->Dropoff_City .', '. $package->Dropoff_Province .' '. $package->Dropoff_Postal_Code .', USA',
                        ] ,
                        "notes" => "",
                    ],
                    "recipients" =>  [
                        [
                            "name"  => $package->Dropoff_Contact_Name,
                            "phone" => $package->Dropoff_Contact_Phone_Number,
                            "notes" => "",
                        ]
                    ],
                    "notes" => $package->Reference_Number_1,
                    "container" =>  [
                        "type"   =>  "WORKER",
                        "team"   =>  $team->idOnfleet,
                        "worker" =>  $driver->idOnfleet
                    ]
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
