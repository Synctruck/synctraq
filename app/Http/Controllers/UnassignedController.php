<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{Assigned, Driver, PackageHistory, PackageDispatch, PackageInbound, PackageManifest, PackageNotExists, TeamRoute, Unassigned, User};

use Illuminate\Support\Facades\Validator;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PhpOffice\PhpOfficePhpSpreadsheetSpreadsheet;
use PhpOffice\PhpOfficePhpSpreadsheetReaderCsv;
use PhpOffice\PhpOfficePhpSpreadsheetReaderXlsx;

use DB;
use Session;

class UnassignedController extends Controller
{
    public function Index()
    {
        return view('package.unassigned');
    }

    public function List(Request $request, $dataView, $idTeam)
    {        
        $roleUser = '';

        if($dataView == 'today')
        {
            $dateInit  = date('Y-m-d') .' 00:00:00';
            $dateEnd   = date('Y-m-d', strtotime('+1 day')) .' 03:00:00';

            $unassignedList = Unassigned::whereBetween('created_at', [$dateInit, $dateEnd]);
        }

        if($idTeam)
        {
            $unassignedList = Unassigned::whereBetween('created_at', [$dateInit, $dateEnd])->orderBy('created_at', 'desc');
        }
        else
        {
            $unassignedList = Unassigned::orderBy('created_at', 'desc');
        }

        $unassignedList = $unassignedList->with('team')->paginate(20);

        $quantityUnassigned = $unassignedList->total();

        return ['unassignedList' => $unassignedList, 'quantityUnassigned' => $quantityUnassigned, 'roleUser' => $roleUser];
    }

    public function Insert(Request $request)
    {
        if(substr($request->get('Reference_Number_1'), 0, 6) == 'INLAND' || substr($request->get('Reference_Number_1'), 0, 5) == '67660')
        {
            $validateDispatch = false;

            $assigned = Assigned::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();

            if($assigned)
            {
                $description = 'Unassigned - for: '. Session::get('user')->name .' '. Session::get('user')->nameOfOwner;

                try
                {
                    DB::beginTransaction();

                    $unassigned = new Unassigned();

                    $unassigned->id                           = uniqid();
                    $unassigned->Reference_Number_1           = $assigned->Reference_Number_1;
                    $unassigned->Reference_Number_2           = $assigned->Reference_Number_2;
                    $unassigned->Reference_Number_3           = $assigned->Reference_Number_3;
                    $unassigned->Ready_At                     = $assigned->Ready_At;
                    $unassigned->Del_Date                     = $assigned->Del_Date;
                    $unassigned->Del_no_earlier_than          = $assigned->Del_no_earlier_than;
                    $unassigned->Del_no_later_than            = $assigned->Del_no_later_than;
                    $unassigned->Pickup_Contact_Name          = $assigned->Pickup_Contact_Name;
                    $unassigned->Pickup_Company               = $assigned->Pickup_Company;
                    $unassigned->Pickup_Contact_Phone_Number  = $assigned->Pickup_Contact_Phone_Number;
                    $unassigned->Pickup_Contact_Email         = $assigned->Pickup_Contact_Email;
                    $unassigned->Pickup_Address_Line_1        = $assigned->Pickup_Address_Line_1;
                    $unassigned->Pickup_Address_Line_2        = $assigned->Pickup_Address_Line_2;
                    $unassigned->Pickup_City                  = $assigned->Pickup_City;
                    $unassigned->Pickup_Province              = $assigned->Pickup_Province;
                    $unassigned->Pickup_Postal_Code           = $assigned->Pickup_Postal_Code;
                    $unassigned->Dropoff_Contact_Name         = $assigned->Dropoff_Contact_Name;
                    $unassigned->Dropoff_Company              = $assigned->Dropoff_Company;
                    $unassigned->Dropoff_Contact_Phone_Number = $assigned->Dropoff_Contact_Phone_Number;
                    $unassigned->Dropoff_Contact_Email        = $assigned->Dropoff_Contact_Email;
                    $unassigned->Dropoff_Address_Line_1       = $assigned->Dropoff_Address_Line_1;
                    $unassigned->Dropoff_Address_Line_2       = $assigned->Dropoff_Address_Line_2;
                    $unassigned->Dropoff_City                 = $assigned->Dropoff_City;
                    $unassigned->Dropoff_Province             = $assigned->Dropoff_Province;
                    $unassigned->Dropoff_Postal_Code          = $assigned->Dropoff_Postal_Code;
                    $unassigned->Service_Level                = $assigned->Service_Level;
                    $unassigned->Carrier_Name                 = $assigned->Carrier_Name;
                    $unassigned->Vehicle_Type_Id              = $assigned->Vehicle_Type_Id;
                    $unassigned->Notes                        = $assigned->Notes;
                    $unassigned->Number_Of_Pieces             = $assigned->Number_Of_Pieces;
                    $unassigned->Weight                       = $assigned->Weight;
                    $unassigned->Route                        = $assigned->Route;
                    $unassigned->Name                         = $assigned->Name;
                    $unassigned->idUser                       = Session::get('user')->id;
                    $unassigned->idTeam                       = $assigned->team->id;
                    $unassigned->idDriver                     = 0;
                    $unassigned->unassignedDate                 = date('Y-m-d H:i:s');
                    $unassigned->status                       = 'Unassigned';

                    $unassigned->save();

                    $packageHistory = new PackageHistory();

                    $packageHistory->id                           = uniqid();
                    $packageHistory->Reference_Number_1           = $assigned->Reference_Number_1;
                    $packageHistory->Reference_Number_2           = $assigned->Reference_Number_2;
                    $packageHistory->Reference_Number_3           = $assigned->Reference_Number_3;
                    $packageHistory->Ready_At                     = $assigned->Ready_At;
                    $packageHistory->Del_Date                     = $assigned->Del_Date;
                    $packageHistory->Del_no_earlier_than          = $assigned->Del_no_earlier_than;
                    $packageHistory->Del_no_later_than            = $assigned->Del_no_later_than;
                    $packageHistory->Pickup_Contact_Name          = $assigned->Pickup_Contact_Name;
                    $packageHistory->Pickup_Company               = $assigned->Pickup_Company;
                    $packageHistory->Pickup_Contact_Phone_Number  = $assigned->Pickup_Contact_Phone_Number;
                    $packageHistory->Pickup_Contact_Email         = $assigned->Pickup_Contact_Email;
                    $packageHistory->Pickup_Address_Line_1        = $assigned->Pickup_Address_Line_1;
                    $packageHistory->Pickup_Address_Line_2        = $assigned->Pickup_Address_Line_2;
                    $packageHistory->Pickup_City                  = $assigned->Pickup_City;
                    $packageHistory->Pickup_Province              = $assigned->Pickup_Province;
                    $packageHistory->Pickup_Postal_Code           = $assigned->Pickup_Postal_Code;
                    $packageHistory->Dropoff_Contact_Name         = $assigned->Dropoff_Contact_Name;
                    $packageHistory->Dropoff_Company              = $assigned->Dropoff_Company;
                    $packageHistory->Dropoff_Contact_Phone_Number = $assigned->Dropoff_Contact_Phone_Number;
                    $packageHistory->Dropoff_Contact_Email        = $assigned->Dropoff_Contact_Email;
                    $packageHistory->Dropoff_Address_Line_1       = $assigned->Dropoff_Address_Line_1;
                    $packageHistory->Dropoff_Address_Line_2       = $assigned->Dropoff_Address_Line_2;
                    $packageHistory->Dropoff_City                 = $assigned->Dropoff_City;
                    $packageHistory->Dropoff_Province             = $assigned->Dropoff_Province;
                    $packageHistory->Dropoff_Postal_Code          = $assigned->Dropoff_Postal_Code;
                    $packageHistory->Service_Level                = $assigned->Service_Level;
                    $packageHistory->Carrier_Name                 = $assigned->Carrier_Name;
                    $packageHistory->Vehicle_Type_Id              = $assigned->Vehicle_Type_Id;
                    $packageHistory->Notes                        = $assigned->Notes;
                    $packageHistory->Number_Of_Pieces             = $assigned->Number_Of_Pieces;
                    $packageHistory->Weight                       = $assigned->Weight;
                    $packageHistory->Route                        = $assigned->Route;
                    $packageHistory->Name                         = $assigned->Name;
                    $packageHistory->idUser                       = Session::get('user')->id;
                    $packageHistory->idUserDispatch               = 0;
                    $packageHistory->Date_Unassigned              = date('Y-m-d H:s:i');
                    $packageHistory->Description                  = $description;
                    $packageHistory->status                       = 'Unassigned';

                    $packageHistory->save();
                    
                    $packageInbound = new PackageInbound();

                    $packageInbound->Reference_Number_1           = $assigned->Reference_Number_1;
                    $packageInbound->Reference_Number_2           = $assigned->Reference_Number_2;
                    $packageInbound->Reference_Number_3           = $assigned->Reference_Number_3;
                    $packageInbound->Ready_At                     = $assigned->Ready_At;
                    $packageInbound->Del_Date                     = $assigned->Del_Date;
                    $packageInbound->Del_no_earlier_than          = $assigned->Del_no_earlier_than;
                    $packageInbound->Del_no_later_than            = $assigned->Del_no_later_than;
                    $packageInbound->Pickup_Contact_Name          = $assigned->Pickup_Contact_Name;
                    $packageInbound->Pickup_Company               = $assigned->Pickup_Company;
                    $packageInbound->Pickup_Contact_Phone_Number  = $assigned->Pickup_Contact_Phone_Number;
                    $packageInbound->Pickup_Contact_Email         = $assigned->Pickup_Contact_Email;
                    $packageInbound->Pickup_Address_Line_1        = $assigned->Pickup_Address_Line_1;
                    $packageInbound->Pickup_Address_Line_2        = $assigned->Pickup_Address_Line_2;
                    $packageInbound->Pickup_City                  = $assigned->Pickup_City;
                    $packageInbound->Pickup_Province              = $assigned->Pickup_Province;
                    $packageInbound->Pickup_Postal_Code           = $assigned->Pickup_Postal_Code;
                    $packageInbound->Dropoff_Contact_Name         = $assigned->Dropoff_Contact_Name;
                    $packageInbound->Dropoff_Company              = $assigned->Dropoff_Company;
                    $packageInbound->Dropoff_Contact_Phone_Number = $assigned->Dropoff_Contact_Phone_Number;
                    $packageInbound->Dropoff_Contact_Email        = $assigned->Dropoff_Contact_Email;
                    $packageInbound->Dropoff_Address_Line_1       = $assigned->Dropoff_Address_Line_1;
                    $packageInbound->Dropoff_Address_Line_2       = $assigned->Dropoff_Address_Line_2;
                    $packageInbound->Dropoff_City                 = $assigned->Dropoff_City;
                    $packageInbound->Dropoff_Province             = $assigned->Dropoff_Province;
                    $packageInbound->Dropoff_Postal_Code          = $assigned->Dropoff_Postal_Code;
                    $packageInbound->Service_Level                = $assigned->Service_Level;
                    $packageInbound->Carrier_Name                 = $assigned->Carrier_Name;
                    $packageInbound->Vehicle_Type_Id              = $assigned->Vehicle_Type_Id;
                    $packageInbound->Notes                        = $assigned->Notes;
                    $packageInbound->Number_Of_Pieces             = $assigned->Number_Of_Pieces;
                    $packageInbound->Weight                       = $assigned->Weight;
                    $packageInbound->Route                        = $assigned->Route;
                    $packageInbound->Name                         = $assigned->Name;
                    $packageInbound->idUser                       = Session::get('user')->id;
                    $packageInbound->status                       = 'Inbound';

                    $packageInbound->save();

                    $packageHistory = new PackageHistory();

                    $packageHistory->id                           = uniqid();
                    $packageHistory->Reference_Number_1           = $assigned->Reference_Number_1;
                    $packageHistory->Reference_Number_2           = $assigned->Reference_Number_2;
                    $packageHistory->Reference_Number_3           = $assigned->Reference_Number_3;
                    $packageHistory->Ready_At                     = $assigned->Ready_At;
                    $packageHistory->Del_Date                     = $assigned->Del_Date;
                    $packageHistory->Del_no_earlier_than          = $assigned->Del_no_earlier_than;
                    $packageHistory->Del_no_later_than            = $assigned->Del_no_later_than;
                    $packageHistory->Pickup_Contact_Name          = $assigned->Pickup_Contact_Name;
                    $packageHistory->Pickup_Company               = $assigned->Pickup_Company;
                    $packageHistory->Pickup_Contact_Phone_Number  = $assigned->Pickup_Contact_Phone_Number;
                    $packageHistory->Pickup_Contact_Email         = $assigned->Pickup_Contact_Email;
                    $packageHistory->Pickup_Address_Line_1        = $assigned->Pickup_Address_Line_1;
                    $packageHistory->Pickup_Address_Line_2        = $assigned->Pickup_Address_Line_2;
                    $packageHistory->Pickup_City                  = $assigned->Pickup_City;
                    $packageHistory->Pickup_Province              = $assigned->Pickup_Province;
                    $packageHistory->Pickup_Postal_Code           = $assigned->Pickup_Postal_Code;
                    $packageHistory->Dropoff_Contact_Name         = $assigned->Dropoff_Contact_Name;
                    $packageHistory->Dropoff_Company              = $assigned->Dropoff_Company;
                    $packageHistory->Dropoff_Contact_Phone_Number = $assigned->Dropoff_Contact_Phone_Number;
                    $packageHistory->Dropoff_Contact_Email        = $assigned->Dropoff_Contact_Email;
                    $packageHistory->Dropoff_Address_Line_1       = $assigned->Dropoff_Address_Line_1;
                    $packageHistory->Dropoff_Address_Line_2       = $assigned->Dropoff_Address_Line_2;
                    $packageHistory->Dropoff_City                 = $assigned->Dropoff_City;
                    $packageHistory->Dropoff_Province             = $assigned->Dropoff_Province;
                    $packageHistory->Dropoff_Postal_Code          = $assigned->Dropoff_Postal_Code;
                    $packageHistory->Service_Level                = $assigned->Service_Level;
                    $packageHistory->Carrier_Name                 = $assigned->Carrier_Name;
                    $packageHistory->Vehicle_Type_Id              = $assigned->Vehicle_Type_Id;
                    $packageHistory->Notes                        = $assigned->Notes;
                    $packageHistory->Number_Of_Pieces             = $assigned->Number_Of_Pieces;
                    $packageHistory->Weight                       = $assigned->Weight;
                    $packageHistory->Route                        = $assigned->Route;
                    $packageHistory->Name                         = $assigned->Name;
                    $packageHistory->idUser                       = Session::get('user')->id;
                    $packageHistory->idUserInbound                = Session::get('user')->id;
                    $packageHistory->Date_Inbound                 = date('Y-m-d H:s:i');
                    $packageHistory->Description                  = 'Inbound - for: '. Session::get('user')->name .' '. Session::get('user')->nameOfOwner;
                    $packageHistory->inbound                      = 1;
                    $packageHistory->status                       = 'Inbound';

                    $packageHistory->save();

                    $assigned->delete();

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
                return ['stateAction' => 'notAssigned'];
            }
        }
        
        return ['stateAction' => 'notInland'];
    }

    public function Get($Reference_Number_1)
    {
        $packageInbound = packageDispatch::find($Reference_Number_1);

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

                    $package = PackageManifest::find($row[0]);

                    if(!$package)
                    {
                        $package = packageInbound::find($row[0]);
                    }

                    if($package)
                    {
                        $team = User::where('name', $row[1])
                                        ->where('idRole', 3)
                                        ->first();

                        if($team)
                        {
                            $driver = User::where('email', $row[2])
                                            ->where('idTeam', $team->id)
                                            ->first();

                            if($driver)
                            {
                                $listTeamRoutes = TeamRoute::with('route')->where('idTeam', $team->id)->get();

                                $validationRoute = false;

                                foreach($listTeamRoutes as $teamRoute)
                                {
                                    if($teamRoute->route->name == $package->Route)
                                    {
                                        $validationRoute = true;
                                    }
                                }

                                if($validationRoute)
                                {
                                    $packageDispatch = new PackageDispatch();

                                    $packageDispatch->id                           = uniqid();
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
                                    $packageDispatch->idUser                       = $driver->id;
                                    $packageDispatch->Date_Dispatch                = date('Y-m-d H:i:s');
                                    $packageDispatch->status                       = 'Dispatch';

                                    $packageDispatch->save();

                                    $packageHistory = new PackageHistory();

                                    $packageHistory->id = uniqid();
                                    $packageHistory->idPackage = $package->Reference_Number_1;
                                    $packageHistory->description = 'Validación Dispatch (Importación) asignado al driver: '. $driver->name .' '. $driver->nameOfOwner;
                                    $packageHistory->user = Session::get('user')->email;
                                    $packageHistory->status = 'Dispatch';

                                    $packageHistory->save();
                                    
                                    $package->delete();
                                }
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

    public function UnassignedIndex()
    {
        return view('package.unassigned');
    }

    public function ListUnassigned()
    {
        $roleUser = Session::get('user')->role->name;

        $assignedList = Assigned::orderBy('created_at', 'desc')->paginate(20);

        return ['assignedList' => $assignedList, 'roleUser' => $roleUser];
    }

    public function IndexTeam()
    {
        return view('package.unassignedteam');
    }

    public function ListUnassignedTeam(Request $request, $dataView, $idTeam)
    {        
        $roleUser = '';


        if($dataView == 'today')
        {
            $dateInit  = date('Y-m-d') .' 00:00:00';
            $dateEnd   = date('Y-m-d', strtotime('+1 day')) .' 03:00:00';

            $unassignedList = Unassigned::whereBetween('created_at', [$dateInit, $dateEnd]);
        }

        if($idTeam)
        {
            $unassignedList = Unassigned::whereBetween('created_at', [$dateInit, $dateEnd])->orderBy('created_at', 'desc');
        }
        else
        {
            $unassignedList = Unassigned::orderBy('created_at', 'desc');
        }

        $unassignedList = $unassignedList->with('team')->paginate(20);

        $quantityUnassigned = $unassignedList->total();

        return ['unassignedList' => $unassignedList, 'quantityUnassigned' => $quantityUnassigned, 'roleUser' => $roleUser];
    }

    public function RemoveDriver(Request $request)
    {
        if(substr($request->get('Reference_Number_1'), 0, 6) == 'INLAND' || substr($request->get('Reference_Number_1'), 0, 5) == '67660')
        {
            $assigned = Assigned::where('Reference_Number_1', $request->get('Reference_Number_1'))
                                ->where('idTeam', Session::get('user')->id)
                                ->where('idDriver', '!=', 0)
                                ->first();

            if($assigned)
            {
                $driver = User::find($assigned->idDriver);

                $description = 'El Team ('. Session::get('user')->email .') quitó la asignación al Driver ('. $driver->email .')';

                try
                {
                    DB::beginTransaction();

                    $assigned->idDriver = 0;

                    $assigned->save();

                    //update dispatch
                    $packageHistory = PackageHistory::where('Reference_Number_1', $request->get('Reference_Number_1'))
                                            ->where('dispatch', 1)
                                            ->first(); 

                    $packageHistory->dispatch = 0;

                    $packageHistory->save();

                    $packageHistory = new PackageHistory();

                    $packageHistory->id                           = uniqid();
                    $packageHistory->Reference_Number_1           = $assigned->Reference_Number_1;
                    $packageHistory->Reference_Number_2           = $assigned->Reference_Number_2;
                    $packageHistory->Reference_Number_3           = $assigned->Reference_Number_3;
                    $packageHistory->Ready_At                     = $assigned->Ready_At;
                    $packageHistory->Del_Date                     = $assigned->Del_Date;
                    $packageHistory->Del_no_earlier_than          = $assigned->Del_no_earlier_than;
                    $packageHistory->Del_no_later_than            = $assigned->Del_no_later_than;
                    $packageHistory->Pickup_Contact_Name          = $assigned->Pickup_Contact_Name;
                    $packageHistory->Pickup_Company               = $assigned->Pickup_Company;
                    $packageHistory->Pickup_Contact_Phone_Number  = $assigned->Pickup_Contact_Phone_Number;
                    $packageHistory->Pickup_Contact_Email         = $assigned->Pickup_Contact_Email;
                    $packageHistory->Pickup_Address_Line_1        = $assigned->Pickup_Address_Line_1;
                    $packageHistory->Pickup_Address_Line_2        = $assigned->Pickup_Address_Line_2;
                    $packageHistory->Pickup_City                  = $assigned->Pickup_City;
                    $packageHistory->Pickup_Province              = $assigned->Pickup_Province;
                    $packageHistory->Pickup_Postal_Code           = $assigned->Pickup_Postal_Code;
                    $packageHistory->Dropoff_Contact_Name         = $assigned->Dropoff_Contact_Name;
                    $packageHistory->Dropoff_Company              = $assigned->Dropoff_Company;
                    $packageHistory->Dropoff_Contact_Phone_Number = $assigned->Dropoff_Contact_Phone_Number;
                    $packageHistory->Dropoff_Contact_Email        = $assigned->Dropoff_Contact_Email;
                    $packageHistory->Dropoff_Address_Line_1       = $assigned->Dropoff_Address_Line_1;
                    $packageHistory->Dropoff_Address_Line_2       = $assigned->Dropoff_Address_Line_2;
                    $packageHistory->Dropoff_City                 = $assigned->Dropoff_City;
                    $packageHistory->Dropoff_Province             = $assigned->Dropoff_Province;
                    $packageHistory->Dropoff_Postal_Code          = $assigned->Dropoff_Postal_Code;
                    $packageHistory->Service_Level                = $assigned->Service_Level;
                    $packageHistory->Carrier_Name                 = $assigned->Carrier_Name;
                    $packageHistory->Vehicle_Type_Id              = $assigned->Vehicle_Type_Id;
                    $packageHistory->Notes                        = $assigned->Notes;
                    $packageHistory->Number_Of_Pieces             = $assigned->Number_Of_Pieces;
                    $packageHistory->Weight                       = $assigned->Weight;
                    $packageHistory->Route                        = $assigned->Route;
                    $packageHistory->Name                         = $assigned->Name;
                    $packageHistory->idUser                       = Session::get('user')->id;
                    $packageHistory->idUserDispatch               = 0;
                    $packageHistory->Date_Unassigned              = date('Y-m-d H:s:i');
                    $packageHistory->Description                  = $description;
                    $packageHistory->status                       = 'Unassigned';

                    $packageHistory->save();
                    
                    $packageDispatch = PackageDispatch::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();

                    $packageDispatch->delete();

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
                return ['stateAction' => 'notAssigned'];
            }
        }
        
        return ['stateAction' => 'notInland'];
    }
}