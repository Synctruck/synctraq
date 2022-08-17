<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{Assigned, Driver, PackageHistory, PackageDispatch, PackageInbound, PackageManifest, PackageNotExists, Routes, TeamRoute, User};

use Illuminate\Support\Facades\Validator;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PhpOffice\PhpOfficePhpSpreadsheetSpreadsheet;
use PhpOffice\PhpOfficePhpSpreadsheetReaderCsv;
use PhpOffice\PhpOfficePhpSpreadsheetReaderXlsx;

use DB;
use Session;

class AssignedController extends Controller
{
    public function Index()
    {
        return view('package.assigned');
    }

    public function List(Request $request, $dataView, $idTeam)
    {        
        $roleUser = '';

        if($dataView == 'today')
        {
            $dateInit  = date('Y-m-d') .' 00:00:00';
            $dateEnd   = date('Y-m-d', strtotime('+1 day')) .' 03:00:00';

            $assignedList = Assigned::whereBetween('created_at', [$dateInit, $dateEnd]);
        }

        if($idTeam)
        {
            $assignedList = Assigned::whereBetween('created_at', [$dateInit, $dateEnd])->orderBy('created_at', 'desc');
        }
        else
        {
            $assignedList = Assigned::orderBy('created_at', 'desc');
        }

        $assignedList = $assignedList->with(['driver', 'team'])->paginate(20);

        $quantityAssigned = $assignedList->total();

        $listState  = PackageInbound::select('Dropoff_Province')
                                            ->groupBy('Dropoff_Province')
                                            ->get();

        return ['assignedList' => $assignedList, 'listState' => $listState, 'quantityAssigned' => $quantityAssigned, 'roleUser' => $roleUser];
    }

    public function Insert(Request $request)
    {
        if(substr($request->get('Reference_Number_1'), 0, 6) == 'INLAND' || substr($request->get('Reference_Number_1'), 0, 5) == '67660')
        {
            $validateDispatch = false;

            $package = packageInbound::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();

            if($package)
            {
                $listTeamStates = explode(',', $request->get('listTeamStates'));

                $validationState = false;

                foreach($listTeamStates as $teamStates)
                {
                    $states = explode(';', explode('=>', $teamStates)[1]);

                    if(in_array($package->Dropoff_Province, $states))
                    {
                        $team = User::where('name', explode(';', explode('=>', $teamStates)[0]))->first();

                        $validationState = true;
                    }
                }

                if(!$validationState)
                {
                    return ['stateAction' => 'notState'];
                }

                /*$teamRoutes = TeamRoute::where('idTeam', $request->get('idTeam'))->get();

                if(count($teamRoutes) > 0)
                {
                    $route = Routes::where('name', $package->Route)->first();

                    $teamRoute = TeamRoute::where('idTeam', $request->get('idTeam'))
                                            ->where('idRoute', $route->id)
                                            ->first();
 
                    if(!$teamRoute)
                    {
                        return ['stateAction' => 'notRoute'];
                    }
                }*/

                $idTeam = $team->id;

                $description = 'Assign - for: '. Session::get('user')->name .' '. Session::get('user')->nameOfOwner;

                try
                {
                    DB::beginTransaction();

                    $assigned = new Assigned();

                    $assigned->Reference_Number_1           = $package->Reference_Number_1;
                    $assigned->Reference_Number_2           = $package->Reference_Number_2;
                    $assigned->Reference_Number_3           = $package->Reference_Number_3;
                    $assigned->Ready_At                     = $package->Ready_At;
                    $assigned->Del_Date                     = $package->Del_Date;
                    $assigned->Del_no_earlier_than          = $package->Del_no_earlier_than;
                    $assigned->Del_no_later_than            = $package->Del_no_later_than;
                    $assigned->Pickup_Contact_Name          = $package->Pickup_Contact_Name;
                    $assigned->Pickup_Company               = $package->Pickup_Company;
                    $assigned->Pickup_Contact_Phone_Number  = $package->Pickup_Contact_Phone_Number;
                    $assigned->Pickup_Contact_Email         = $package->Pickup_Contact_Email;
                    $assigned->Pickup_Address_Line_1        = $package->Pickup_Address_Line_1;
                    $assigned->Pickup_Address_Line_2        = $package->Pickup_Address_Line_2;
                    $assigned->Pickup_City                  = $package->Pickup_City;
                    $assigned->Pickup_Province              = $package->Pickup_Province;
                    $assigned->Pickup_Postal_Code           = $package->Pickup_Postal_Code;
                    $assigned->Dropoff_Contact_Name         = $package->Dropoff_Contact_Name;
                    $assigned->Dropoff_Company              = $package->Dropoff_Company;
                    $assigned->Dropoff_Contact_Phone_Number = $package->Dropoff_Contact_Phone_Number;
                    $assigned->Dropoff_Contact_Email        = $package->Dropoff_Contact_Email;
                    $assigned->Dropoff_Address_Line_1       = $package->Dropoff_Address_Line_1;
                    $assigned->Dropoff_Address_Line_2       = $package->Dropoff_Address_Line_2;
                    $assigned->Dropoff_City                 = $package->Dropoff_City;
                    $assigned->Dropoff_Province             = $package->Dropoff_Province;
                    $assigned->Dropoff_Postal_Code          = $package->Dropoff_Postal_Code;
                    $assigned->Service_Level                = $package->Service_Level;
                    $assigned->Carrier_Name                 = $package->Carrier_Name;
                    $assigned->Vehicle_Type_Id              = $package->Vehicle_Type_Id;
                    $assigned->Notes                        = $package->Notes;
                    $assigned->Number_Of_Pieces             = $package->Number_Of_Pieces;
                    $assigned->Weight                       = $package->Weight;
                    $assigned->Route                        = $package->Route;
                    $assigned->Name                         = $package->Name;
                    $assigned->idUser                       = Session::get('user')->id;
                    $assigned->idTeam                       = $idTeam;
                    $assigned->idDriver                     = 0;
                    $assigned->assignedDate                 = date('Y-m-d H:i:s');
                    $assigned->status                       = 'Assigned';

                    $assigned->save();

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
                    $packageHistory->idUserDispatch               = 0;
                    $packageHistory->Date_Dispatch                = date('Y-m-d H:s:i');
                    $packageHistory->Description                  = $description;
                    $packageHistory->status                       = 'Assigned';

                    $packageHistory->save();
                        
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
                $packageManifest = PackageManifest::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();

                if($packageManifest)
                {
                    return ['stateAction' => 'notInbound'];
                }

                $assigned = Assigned::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();

                if($assigned)
                {
                    return ['stateAction' => 'validated'];
                }
                else
                {
                    $packageHistory = PackageHistory::where('Reference_Number_1', $request->get('Reference_Number_1'))
                                                    ->where('status', 'Dispatch')
                                                    ->where('dispatch', 1)
                                                    ->first();

                    if($packageHistory)
                    {
                        return ['stateAction' => 'dispatch'];
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

    public function IndexTeam()
    {
        return view('package.assignedteam');
    }

    public function ListAssignedTeam($dataView, $idTeam)
    {
        $roleUser = Session::get('user')->role->name;

        $assignedList = Assigned::with('driver')
                                ->where('idTeam', $idTeam)
                                ->orderBy('created_at', 'desc')
                                ->paginate(20);

        return ['assignedList' => $assignedList, 'roleUser' => $roleUser];
    }

    public function InsertDriver(Request $request)
    {
        if(substr($request->get('Reference_Number_1'), 0, 6) == 'INLAND' || substr($request->get('Reference_Number_1'), 0, 5) == '67660')
        {
            $assigned = Assigned::where('Reference_Number_1', $request->get('Reference_Number_1'))
                                ->where('idTeam', Session::get('user')->id)
                                ->first();

            if($assigned)
            {
                $user = User::find($request->get('idDriver'));

                $description = 'El Team ('. Session::get('user')->name .') asignó desde oficina virtual al Driver ('. $user->email .')';

                try
                {
                    DB::beginTransaction();

                    $assigned->idDriver = $user->id;

                    $assigned->save();

                    $packageDispatch = new PackageDispatch();

                    $packageDispatch->Reference_Number_1           = $assigned->Reference_Number_1;
                    $packageDispatch->Reference_Number_2           = $assigned->Reference_Number_2;
                    $packageDispatch->Reference_Number_3           = $assigned->Reference_Number_3;
                    $packageDispatch->Ready_At                     = $assigned->Ready_At;
                    $packageDispatch->Del_Date                     = $assigned->Del_Date;
                    $packageDispatch->Del_no_earlier_than          = $assigned->Del_no_earlier_than;
                    $packageDispatch->Del_no_later_than            = $assigned->Del_no_later_than;
                    $packageDispatch->Pickup_Contact_Name          = $assigned->Pickup_Contact_Name;
                    $packageDispatch->Pickup_Company               = $assigned->Pickup_Company;
                    $packageDispatch->Pickup_Contact_Phone_Number  = $assigned->Pickup_Contact_Phone_Number;
                    $packageDispatch->Pickup_Contact_Email         = $assigned->Pickup_Contact_Email;
                    $packageDispatch->Pickup_Address_Line_1        = $assigned->Pickup_Address_Line_1;
                    $packageDispatch->Pickup_Address_Line_2        = $assigned->Pickup_Address_Line_2;
                    $packageDispatch->Pickup_City                  = $assigned->Pickup_City;
                    $packageDispatch->Pickup_Province              = $assigned->Pickup_Province;
                    $packageDispatch->Pickup_Postal_Code           = $assigned->Pickup_Postal_Code;
                    $packageDispatch->Dropoff_Contact_Name         = $assigned->Dropoff_Contact_Name;
                    $packageDispatch->Dropoff_Company              = $assigned->Dropoff_Company;
                    $packageDispatch->Dropoff_Contact_Phone_Number = $assigned->Dropoff_Contact_Phone_Number;
                    $packageDispatch->Dropoff_Contact_Email        = $assigned->Dropoff_Contact_Email;
                    $packageDispatch->Dropoff_Address_Line_1       = $assigned->Dropoff_Address_Line_1;
                    $packageDispatch->Dropoff_Address_Line_2       = $assigned->Dropoff_Address_Line_2;
                    $packageDispatch->Dropoff_City                 = $assigned->Dropoff_City;
                    $packageDispatch->Dropoff_Province             = $assigned->Dropoff_Province;
                    $packageDispatch->Dropoff_Postal_Code          = $assigned->Dropoff_Postal_Code;
                    $packageDispatch->Service_Level                = $assigned->Service_Level;
                    $packageDispatch->Carrier_Name                 = $assigned->Carrier_Name;
                    $packageDispatch->Vehicle_Type_Id              = $assigned->Vehicle_Type_Id;
                    $packageDispatch->Notes                        = $assigned->Notes;
                    $packageDispatch->Number_Of_Pieces             = $assigned->Number_Of_Pieces;
                    $packageDispatch->Weight                       = $assigned->Weight;
                    $packageDispatch->Route                        = $assigned->Route;
                    $packageDispatch->Name                         = $assigned->Name;
                    $packageDispatch->idUser                       = Session::get('user')->id;
                    $packageDispatch->idUserDispatch               = $user->id;
                    $packageDispatch->Date_Dispatch                = date('Y-m-d H:i:s');
                    $packageDispatch->status                       = 'Dispatch';

                    $packageDispatch->save();

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
                    $packageHistory->idUserDispatch               = $user->id;
                    $packageHistory->Date_Dispatch                = date('Y-m-d H:s:i');
                    $packageHistory->dispatch                     = 1;
                    $packageHistory->Description                  = $description;
                    $packageHistory->status                       = 'Dispatch';

                    $packageHistory->save();

                    /*$packageHistory = new PackageHistory();

                    $packageHistory->id             = date('Y-m-d H:i:s');
                    $packageHistory->idPackage      = $request->get('Reference_Number_1');
                    $packageHistory->description    = $user->idTeam ?  'Validación Dispatch asignado al driver: '. $user->name .' '. $user->nameOfOwner : 'Validación Dispatch asignado al equipo: '. $user->name;
                    $packageHistory->user           = Session::get('user')->email;
                    $packageHistory->status         = 'Dispatch';

                    $packageHistory->save();*/
                        
                    DB::commit();

                    return ['stateAction' => true];
                }
                catch(Exception $e)
                {
                    DB::rollback();

                    return ['stateAction' => false];
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