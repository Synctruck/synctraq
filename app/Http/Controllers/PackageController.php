<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{Configuration, Driver, Package, PackageBlocked, PackageDelivery, PackageDispatch, PackageHistory, PackageInbound, PackageManifest, PackageNotExists, PackageReturn, TeamRoute, User};

use Illuminate\Support\Facades\Validator;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PhpOffice\PhpOfficePhpSpreadsheetSpreadsheet;
use PhpOffice\PhpOfficePhpSpreadsheetReaderCsv;
use PhpOffice\PhpOfficePhpSpreadsheetReaderXlsx;

use DB;
use Illuminate\Support\Facades\Auth;
use Session;

class PackageController extends Controller
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
        return view('package.index');
    }

    public function List(Request $request)
    {
        $packageList = Package::where('status', 'On hold')
                                ->orderBy('created_at', 'desc')
                                ->paginate(2000);

        $quantityPackage = Package::where('status', 'On hold')->get()->count();

        return ['packageList' => $packageList, 'quantityPackage' => $quantityPackage];
    }

    public function Insert(Request $request)
    {
        $validator = Validator::make($request->all(),

            [
                "Reference_Number_1" => ["required", "unique:package"],
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
                "Reference_Number_1.required" => "El campo es requerido",
                "Reference_Number_1.unique" => "El Package ya existe",

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

        $package = new Package();

        $package->Reference_Number_1 = $request->get('Reference_Number_1');
        $package->Dropoff_Contact_Name = $request->get('Dropoff_Contact_Name');
        $package->Dropoff_Contact_Phone_Number = $request->get('Dropoff_Contact_Phone_Number');
        $package->Dropoff_Address_Line_1 = $request->get('Dropoff_Address_Line_1');
        $package->Dropoff_City = $request->get('Dropoff_City');
        $package->Dropoff_Province = $request->get('Dropoff_Province');
        $package->Dropoff_Postal_Code = $request->get('Dropoff_Postal_Code');
        $package->Weight = $request->get('Weight');
        $package->Route = $request->get('Route');
        $package->Inbound = $request->get('status') ? 0 : 1;
        $package->Warehouse = 0;
        $package->Dispatch = 0;
        $package->idUserInbound = Auth::user()->id;
        $package->idUserWarehouse = 0;
        $package->idUserDispatch = 0;
        $package->Date_Inbound = $request->get('status') ? null : date('Y-m-d H:i:s');
        $package->status = $request->get('status') ? 'On hold' : 'Inbound';

        $package->save();

        $packageHistory = new PackageHistory();

        $packageHistory->id          = date('Y-m-d H:i:s');
        $packageHistory->idPackage   = $request->get('Reference_Number_1');
        $packageHistory->description = 'Package agregado a través de Package Not Exists';
        $packageHistory->user        = Auth::user()->email;
        $packageHistory->status      = 'Inbound';

        $packageHistory->save();

        $packageNotExists = PackageNotExists::find($request->get('Reference_Number_1'));

        if($packageNotExists)
        {
            $packageNotExists->delete();
        }

        return response()->json(["stateAction" => true], 200);
    }

    public function Get($Reference_Number_1)
    {
        $package = Package::find($Reference_Number_1);

        return ['package' => $package];
    }

    public function Update(Request $request)
    {
        try
        {
            DB::beginTransaction();

            $package = PackageManifest::find($request->get('Reference_Number_1'));

            if($package == null)
            {
                $package = PackageInbound::find($request->get('Reference_Number_1'));
            }

            if($package)
            {
                $package->Dropoff_Contact_Name         = $request->get('Dropoff_Contact_Name');
                $package->Dropoff_Contact_Phone_Number = $request->get('Dropoff_Contact_Phone_Number');
                $package->Dropoff_Address_Line_1       = $request->get('Dropoff_Address_Line_1');
                $package->Dropoff_Address_Line_2       = $request->get('Dropoff_Address_Line_2');
                $package->Dropoff_City                 = $request->get('Dropoff_City');
                $package->Dropoff_Province             = $request->get('Dropoff_Province');
                $package->Dropoff_Postal_Code          = $request->get('Dropoff_Postal_Code');
                $package->Weight                       = $request->get('Weight');
                $package->Route                        = $request->get('Route');
                $package->save();
            }

            $packageHistoryList = PackageHistory::where('Reference_Number_1', $request->get('Reference_Number_1'))->get();

            foreach($packageHistoryList as $packageHistory)
            {
                $packageHistory = PackageHistory::find($packageHistory->id);
                
                $packageHistory->Dropoff_Contact_Name         = $request->get('Dropoff_Contact_Name');
                $packageHistory->Dropoff_Contact_Phone_Number = $request->get('Dropoff_Contact_Phone_Number');
                $packageHistory->Dropoff_Address_Line_1       = $request->get('Dropoff_Address_Line_1');
                $packageHistory->Dropoff_Address_Line_2       = $request->get('Dropoff_Address_Line_2');
                $packageHistory->Dropoff_City                 = $request->get('Dropoff_City');
                $packageHistory->Dropoff_Province             = $request->get('Dropoff_Province');
                $packageHistory->Dropoff_Postal_Code          = $request->get('Dropoff_Postal_Code');
                $packageHistory->Weight                       = $request->get('Weight');
                $packageHistory->Route                        = $request->get('Route');
                $packageHistory->internal_comment             = $request->internal_comment;

                $packageHistory->save();
            }

            DB::commit();

            return response()->json(["stateAction" => true], 200);
        }
        catch(Exception $e)
        {
            DB::rollback();

            return response()->json(["stateAction" => false], 200);
        }
    }

    public function Search($Reference_Number_1)
    {
        $packageBlocked = PackageBlocked::where('Reference_Number_1', $Reference_Number_1)->first();

        $packageHistoryList = PackageHistory::with([
                                                'user' => function($query){
                                                    $query->select('id', 'name', 'nameOfOwner');
                                                }
                                            ])
                                            ->where('Reference_Number_1', $Reference_Number_1)
                                            ->orderBy('created_at', 'asc')
                                            ->get();

        $packageDispatch = PackageDispatch::where('Reference_Number_1', $Reference_Number_1)->first();

        $packageDelivery = PackageDelivery::where('taskDetails', $Reference_Number_1)->first();

        return ['packageBlocked' => $packageBlocked, 'packageHistoryList' => $packageHistoryList, 'packageDelivery' => $packageDelivery, 'packageDispatch' => $packageDispatch];
    }

    public function SearchTask($taskOnfleet)
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
            $idOnfleet = '';

            if($output['state'] == 3)
            {
                $idOnfleet = $output['worker'];
            }
            else
            {
                $idOnfleet = $output['container']['worker'];
            }

            $driverUser = User::where('idOnfleet', $idOnfleet)->first();

            if($output['state'] == 3 && $driverUser)
            {
                $driver = $driverUser->name .' '. $driverUser->nameOfOwner;
                $team   = $driverUser->idTeam;
            }
            else
            {
                $driver = '';
                $team   = '';
            }

            return ['stateAction' => 200, 'onfleet' => $output, 'driver' => $driver, 'team' => $team];
        }
        else
        {
            return ['stateAction' => false];
        }
    }

    public function IndexInbound()
    {
        return view('package.inbound');
    }

    public function Import(Request $request)
    {
        $file = $request->file('file');

        $file->move(public_path() .'/file-import', 'manifest.csv');

        $handle = fopen(public_path('file-import/manifest.csv'), "r");

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

                    if(!Package::find($row[0]))
                    {
                        $package = new Package();

                        $package->Reference_Number_1 = $row[0];
                        $package->Reference_Number_2 = $row[1];
                        $package->Reference_Number_3 = $row[2];
                        $package->Ready_At = $row[3];
                        $package->Del_Date = $row[4];
                        $package->Del_no_earlier_than = $row[5];
                        $package->Del_no_later_than = $row[6];
                        $package->Pickup_Contact_Name = $row[7];
                        $package->Pickup_Company = $row[8];
                        $package->Pickup_Contact_Phone_Number = $row[9];
                        $package->Pickup_Contact_Email = $row[10];
                        $package->Pickup_Address_Line_1 = $row[11];
                        $package->Pickup_Address_Line_2 = $row[12];
                        $package->Pickup_City = $row[13];
                        $package->Pickup_Province = $row[14];
                        $package->Pickup_Postal_Code = $row[15];
                        $package->Dropoff_Contact_Name = $row[16];
                        $package->Dropoff_Company = $row[17];
                        $package->Dropoff_Contact_Phone_Number = $row[18];
                        $package->Dropoff_Contact_Email = $row[19];
                        $package->Dropoff_Address_Line_1 = $row[20];
                        $package->Dropoff_Address_Line_2 = $row[21];
                        $package->Dropoff_City = $row[22];
                        $package->Dropoff_Province = $row[23];
                        $package->Dropoff_Postal_Code = $row[24];
                        $package->Service_Level = $row[25];
                        $package->Carrier_Name = $row[26];
                        $package->Vehicle_Type_Id = $row[27];
                        $package->Notes = $row[28];
                        $package->Number_Of_Pieces = $row[29];
                        $package->Weight = $row[30];
                        $package->Route = $row[31];
                        $package->Name = isset($row[32]) ? $row[32] : '';
                        $package->status = 'On hold';

                        $packageHistory = new PackageHistory();

                        $packageHistory->id = uniqid();
                        $packageHistory->idPackage = $row[0];
                        $packageHistory->description = 'Importación de Package';
                        $packageHistory->user = Auth::user()->email;
                        $packageHistory->status = 'On hold';

                        $packageHistory->save();

                        if($package->save())
                        {
                            $countSave++;
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

    public function ListInbound(Request $request)
    {
        $dateInit  = date('Y-m-d') .' 00:00:00' ;
        $dateEnd   = date('Y-m-d', strtotime('+1 day')) .' 03:00:00';

        if(Auth::user()->role->name == 'Validador')
        {
            $packageList = Package::with('user_inbound')
                                ->where('idUserInbound', Auth::user()->id)
                                ->where('Inbound', 1)
                                ->where('Dispatch', 0)
                                ->orderBy('Date_Inbound', 'desc')
                                ->get();
        }
        else
        {
            $packageList = Package::with('user_inbound')
                                ->where('Inbound', 1)
                                ->where('Dispatch', 0)
                                ->orderBy('Date_Inbound', 'desc')
                                ->get();
        }


        return ['packageList' => $packageList];
    }

    public function ValidationInbound(Request $request)
    {
        $ids_package            = explode('|', $request->get('ids_package_inbound'));
        $ids_package_not_exists = explode('|', $request->get('ids_package_notexists'));

        try
        {
            DB::beginTransaction();

            for($i = 1; $i < count($ids_package); $i++)
            {
                $package = Package::find($ids_package[$i]);

                $package->Inbound       = 1;
                $package->idUserInbound = Auth::user()->id;
                $package->Date_Inbound  = date('Y-m-d H:i:s');
                $package->status        = 'Inbound';

                $package->save();

                $packageHistory = new PackageHistory();

                $packageHistory->id          = uniqid();
                $packageHistory->idPackage   = $ids_package[$i];
                $packageHistory->description = 'Validación Inbound';
                $packageHistory->user        = Auth::user()->email;
                $packageHistory->status      = 'Inbound';

                $packageHistory->save();
            }

            for($i = 1; $i < count($ids_package_not_exists); $i++)
            {
                $packageNotExists = new PackageNotExists();

                $packageNotExists->Reference_Number_1 = $ids_package_not_exists[$i];
                $packageNotExists->idUser             = Auth::user()->id;
                $packageNotExists->Date_Inbound       = date('Y-m-d H:i:s');

                $packageNotExists->save();
            }

            DB::commit();

            return ['stateAction' => true];
        }
        catch(Exception $e)
        {
            DB::rollback();

            return ['stateAction' => true];
        }

        /*if(substr($request->get('Reference_Number_1'), 0, 6) == 'INLAND' || substr($request->get('Reference_Number_1'), 0, 5) == '67660')
        {
            $package = Package::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();

            if($package)
            {
                if($package && $package->Inbound)
                {
                    return ['stateAction' => 'validated'];
                }

                try
                {
                    DB::beginTransaction();

                    $package->Inbound = 1;
                    $package->idUserInbound = Auth::user()->id;
                    $package->Date_Inbound = date('Y-m-d H:i:s');
                    $package->status = 'Inbound';

                    $package->save();

                    $packageHistory = new PackageHistory();

                    $packageHistory->id = date('Y-m-d H:i:s');
                    $packageHistory->idPackage = $request->get('Reference_Number_1');
                    $packageHistory->description = 'Validación Inbound';
                    $packageHistory->user = Auth::user()->email;
                    $packageHistory->status = 'Inbound';

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
                $packageNotExists = PackageNotExists::find($request->get('Reference_Number_1'));

                if(!$packageNotExists)
                {
                    $packageNotExists = new PackageNotExists();

                    $packageNotExists->Reference_Number_1 = $request->get('Reference_Number_1');
                    $packageNotExists->idUser             = Auth::user()->id;

                    $packageNotExists->save();
                }

                return ['stateAction' => 'notExists'];
            }
        }

        return ['stateAction' => 'notInland'];*/
    }

    public function ImportInbound(Request $request)
    {
        $file = $request->file('file');

        $file->move(public_path() .'/file-import', 'inbound.csv');

        $handle = fopen(public_path('file-import/inbound.csv'), "r");

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

                    $package = Package::find($row[0]);

                    if($package)
                    {
                        if($package->status == 'On hold' || $package->status == 'Inbound')
                        {
                            $package->Inbound       = 1;
                            $package->idUserInbound = Auth::user()->id;
                            $package->Date_Inbound  = date('Y-m-d H:i:s');
                            $package->status        = 'Inbound';

                            $package->save();

                            $packageHistory = new PackageHistory();

                            $packageHistory->id          = uniqid();
                            $packageHistory->idPackage   = $row[0];
                            $packageHistory->description = 'Validación Inbound';
                            $packageHistory->user        = Auth::user()->email;
                            $packageHistory->status      = 'Inbound';

                            $packageHistory->save();
                        }
                    }
                    else
                    {
                        $packageNotExists = new PackageNotExists();

                        $packageNotExists->Reference_Number_1 = $row[0];
                        $packageNotExists->idUser             = Auth::user()->id;
                        $packageNotExists->Date_Inbound       = date('Y-m-d H:i:s');

                        $packageNotExists->save();
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

    public function IndexDispatch()
    {
        return view('package.dispatch');
    }

    public function ListDispatch($dataView)
    {
        $roleUser = '';

        if(Auth::user()->role->name == 'Driver')
        {
            $packageDispatchList = PackageDispatch::with(['package', 'driver.role', 'driver'])
                                                    ->where('idUSer', Auth::user()->id)
                                                    ->where('status', 'Dispatch');

            $roleUser = 'Driver';
        }
        elseif(Auth::user()->role->name == 'Team')
        {
            $drivers = Driver::where('idTeam', Auth::user()->id)->get('id');

            $idUsers = [];

            foreach($drivers as $driver)
            {
                array_push($idUsers, $driver->id);
            }

            array_push($idUsers, Auth::user()->id);

            $packageDispatchList = PackageDispatch::with(['package', 'driver.role', 'driver'])
                                                ->whereIn('idUSer', $idUsers)
                                                ->where('status', 'Dispatch');

            $roleUser = 'Team';
        }
        else
        {
            $packageDispatchList = PackageDispatch::with(['package', 'driver.role', 'driver'])
                                                ->where('status', 'Dispatch');

            $roleUser = 'Administrador';
        }

        if($dataView == 'today')
        {
            $dateInit  = date('Y-m-d') .' 00:00:00';
            $dateEnd   = date('Y-m-d', strtotime('+1 day')) .' 03:00:00';

            $packageDispatchList = $packageDispatchList->whereBetween('created_at', [$dateInit, $dateEnd])
                                                        ->orderBy('created_at', 'desc')
                                                        ->get();
        }
        else
        {
            $packageDispatchList = $packageDispatchList->orderBy('created_at', 'desc')
                                                        ->get();
        }

        return ['packageDispatchList' => $packageDispatchList, 'roleUser' => $roleUser];
    }

    public function ValidationDispatch(Request $request)
    {
        if(substr($request->get('Reference_Number_1'), 0, 6) == 'INLAND' || substr($request->get('Reference_Number_1'), 0, 5) == '67660')
        {
            $package = Package::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();

            if(!$package)
            {
                $packageNotExists = PackageNotExists::find($request->get('Reference_Number_1'));

                if(!$packageNotExists)
                {
                    $packageNotExists = new PackageNotExists();

                    $packageNotExists->Reference_Number_1 = $request->get('Reference_Number_1');
                    $packageNotExists->idUser             = Auth::user()->id;

                    $packageNotExists->save();
                }

                return ['stateAction' => 'notExists'];
            }

            if($package)
            {
                if(Auth::user()->role->name == 'Administrador')
                {
                    $listTeamRoutes = TeamRoute::with('route')->where('idTeam', $request->get('idTeam'))->get();
                }
                elseif(Auth::user()->role->name == 'Driver')
                {
                    $listTeamRoutes = TeamRoute::with('route')->where('idTeam', Auth::user()->idTeam)->get();
                }
                elseif(Auth::user()->role->name == 'Team')
                {
                    $listTeamRoutes = TeamRoute::with('route')->where('idTeam', Auth::user()->id)->get();
                }

                $validationRoute = false;

                foreach($listTeamRoutes as $teamRoute)
                {
                    if($teamRoute->route->name == $package->Route)
                    {
                        $validationRoute = true;
                    }
                }

                if(!$validationRoute)
                {
                    return ['stateAction' => 'notValidatedRoute'];
                }

                if($package->Dispatch && Auth::user()->role->name == 'Administrador')
                {
                    return ['stateAction' => 'validated'];
                }

                $idUser = 0;

                if($request->get('idTeam') && $request->get('idDriver'))
                {
                    $idUser = $request->get('idDriver');
                }
                else
                {
                    $idUser = $request->get('idTeam');
                }

                try
                {
                    DB::beginTransaction();

                    $package->Dispatch = 1;
                    $package->Date_Dispatch = date('Y-m-d H:i:s');
                    $package->idUserDispatch = Auth::user()->role->name == 'Administrador' ? $idUser : Auth::user()->id;
                    $package->status = 'Dispatch';

                    $package->save();

                    if(Auth::user()->role->name == 'Administrador')
                    {
                        $packageDispatch = new PackageDispatch();

                        $packageDispatch->id            = date('YmdHis');
                        $packageDispatch->idPackage     = $package->Reference_Number_1;
                        $packageDispatch->idUser        = Auth::user()->role->name == 'Administrador' ? $idUser : Auth::user()->id;
                        $packageDispatch->Date_Dispatch = date('Y-m-d H:i:s');
                        $packageDispatch->status        = 'Dispatch';

                        $packageDispatch->save();
                    }
                    else
                    {
                        $packageDispatch = PackageDispatch::where('idUser', Auth::user()->id)
                                                        ->where('status', 'Dispatch')
                                                        ->first();

                        $packageDispatch->idPackage     = $package->Reference_Number_1;
                        $packageDispatch->idUser        = $request->get('idDriver');
                        $packageDispatch->Date_Dispatch = date('Y-m-d H:i:s');

                        $packageDispatch->save();
                    }

                    $user = User::find($idUser);

                    $packageHistory = new PackageHistory();

                    $packageHistory->id = date('Y-m-d H:i:s');
                    $packageHistory->idPackage = $request->get('Reference_Number_1');
                    $packageHistory->description = $user->idTeam ?  'Validación Dispatch asignado al driver: '. $user->name .' '. $user->nameOfOwner : 'Validación Dispatch asignado al equipo: '. $user->name;
                    $packageHistory->user = Auth::user()->email;
                    $packageHistory->status = 'Dispatch';

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
                return ['stateAction' => false];
            }
        }

        return ['stateAction' => 'notInland'];
    }

    public function ImportDispatch(Request $request)
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

                    $package = Package::find($row[0]);

                    if($package)
                    {
                        if($package->status == 'On hold' || $package->status == 'Inbound')
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
                                        $package->Dispatch = 1;
                                        $package->Date_Dispatch = date('Y-m-d H:i:s');
                                        $package->idUserDispatch = $driver->id;
                                        $package->status = 'Dispatch';

                                        $package->save();

                                        $packageDispatch = new PackageDispatch();

                                        $packageDispatch->id            = uniqid();
                                        $packageDispatch->idPackage     = $row[0];
                                        $packageDispatch->idUser        = $driver->id;
                                        $packageDispatch->Date_Dispatch = date('Y-m-d H:i:s');
                                        $packageDispatch->status        = 'Dispatch';

                                        $packageDispatch->save();

                                        $packageHistory = new PackageHistory();

                                        $packageHistory->id = uniqid();
                                        $packageHistory->idPackage = $row[0];
                                        $packageHistory->description = 'Validación Dispatch (Importación) asignado al driver: '. $driver->name .' '. $driver->nameOfOwner;
                                        $packageHistory->user = Auth::user()->email;
                                        $packageHistory->status = 'Dispatch';

                                        $packageHistory->save();
                                    }
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

    public function ListWarehouse(Request $request)
    {
        $packageList = Package::orderBy('Pickup_Province', 'asc')
                                ->where('status', 'Inbound')
                                ->get();

        return ['packageList' => $packageList];
    }

    public function IndexReturn()
    {
        return view('package.return');
    }

    public function ListReturn($dateStart,$dateEnd,$idTeam,$idDriver,$route, $state)
    {
        $dateStart =date("Y-m-d",strtotime($dateStart)).' 00:00:00';
        $dateEnd  = date("Y-m-d",strtotime($dateEnd)).' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);

        $roleUser = '';

        if(Auth::user()->role->name == 'Driver')
        {
            $packageReturnList = PackageReturn::where('idUserReturn', Auth::user()->id)
                                                ->where('status', 'Return')
                                                ->whereBetween('Date_Return', [$dateStart, $dateEnd])
                                                ->orderBy('created_at', 'desc');

            $roleUser = 'Driver';
        }
        elseif(Auth::user()->role->name == 'Team')
        {
            $drivers = Driver::where('idTeam', Auth::user()->id)->get('id');

            $packageReturnList = PackageReturn::whereIn('idUserReturn', $drivers)
                                                ->orWhere('idUserReturn', Auth::user()->id)
                                                ->where('status', 'Return')
                                                ->whereBetween('Date_Return', [$dateStart, $dateEnd])
                                                ->orderBy('created_at', 'desc');

            $roleUser = 'Team';
        }
        else
        {
            $packageReturnList = PackageReturn::where('status', 'Return')
                                                ->whereBetween('Date_Return', [$dateStart, $dateEnd])
                                                ->orderBy('created_at', 'desc');

            $roleUser = 'Administrador';
        }

        if($idTeam && $idDriver)
        {
            $packageReturnList = $packageReturnList->where('idUserReturn', $idDriver);
        }
        elseif($idTeam)
        {
            $packageReturnList = $packageReturnList->where('idTeam', $idTeam);
        }
        elseif($idDriver)
        {
            $packageReturnList = $packageReturnList->where('idUserReturn', $idDriver);
        }

        if($route != 'all')
        {
            $packageReturnList = $packageReturnList->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $packageReturnList = $packageReturnList->whereIn('Dropoff_Province', $states);
        }

        $packageReturnList = $packageReturnList->with(['team', 'driver'])->paginate(50);

        $quantityReturn = $packageReturnList->total();

        $listState = PackageReturn::select('Dropoff_Province')
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        return ['packageReturnList' => $packageReturnList, 'listState' => $listState, 'quantityReturn' => $quantityReturn, 'roleUser' => $roleUser];
    }


    public function ListReturnExport($dateStart,$dateEnd,$idTeam,$idDriver,$route, $state)
    {
        $delimiter = ",";
        $filename = "PACKAGES - RETURN " . date('Y-m-d H:i:s') . ".csv";

        //create a file pointer
        $file = fopen('php://memory', 'w');

        //set column headers
        $fields = array('DATE' ,'HOUR', 'TEAM', 'DRIVER', 'PACKAGE ID', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'WEIGHT', 'ROUTE','TASK ONFLEET');

        fputcsv($file, $fields, $delimiter);

        $dateStart =date("Y-m-d",strtotime($dateStart)).' 00:00:00';
        $dateEnd  = date("Y-m-d",strtotime($dateEnd)).' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);

        $roleUser = '';

        if(Auth::user()->role->name == 'Driver')
        {
            $packageReturnList = PackageReturn::where('idUserReturn', Auth::user()->id)
                                                ->where('status', 'Return')
                                                ->whereBetween('Date_Return', [$dateStart, $dateEnd])
                                                ->orderBy('created_at', 'ASC');

            $roleUser = 'Driver';
        }
        elseif(Auth::user()->role->name == 'Team')
        {
            $drivers = Driver::where('idTeam', Auth::user()->id)->get('id');

            $packageReturnList = PackageReturn::whereIn('idUserReturn', $drivers)
                                                ->orWhere('idUserReturn', Auth::user()->id)
                                                ->where('status', 'Return')
                                                ->whereBetween('Date_Return', [$dateStart, $dateEnd])
                                                ->orderBy('created_at', 'ASC');
        }
        else
        {
            $packageReturnList = PackageReturn::where('status', 'Return')
                                                ->whereBetween('Date_Return', [$dateStart, $dateEnd])
                                                ->orderBy('created_at', 'ASC');

        }

        if($idTeam && $idDriver)
        {
            $packageReturnList = $packageReturnList->where('idUserReturn', $idDriver);
        }
        elseif($idTeam)
        {
            $packageReturnList = $packageReturnList->where('idTeam', $idTeam);
        }
        elseif($idDriver)
        {
            $packageReturnList = $packageReturnList->where('idUserReturn', $idDriver);
        }

        if($route != 'all')
        {
            $packageReturnList = $packageReturnList->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $packageReturnList = $packageReturnList->whereIn('Dropoff_Province', $states);
        }

        $packageReturnList = $packageReturnList->with(['team', 'driver'])->orderBy('Date_Return', 'desc')->get();


       foreach($packageReturnList as $packageReturn)
        {

            if($packageReturn->driver && $packageReturn->driver->idTeam)
            {
                $team   = $packageReturn->driver->nameTeam;
                $driver = $packageReturn->driver->name .' '. $packageReturn->driver->nameOfOwner;
            }
            else
            {
                $team   = $packageReturn->driver ? $packageReturn->driver->name : '';
                $driver = '';
            }

            $lineData = array(
                date('m-d-Y', strtotime($packageReturn->Date_Return)),
                date('H:i:s', strtotime($packageReturn->Date_Return)),
                $team,
                $driver,
                $packageReturn->Reference_Number_1,
                $packageReturn->Dropoff_Contact_Name,
                $packageReturn->Dropoff_Contact_Phone_Number,
                $packageReturn->Dropoff_Address_Line_1,
                $packageReturn->Dropoff_City,
                $packageReturn->Dropoff_Province,
                $packageReturn->Dropoff_Postal_Code,
                $packageReturn->Weight,
                $packageReturn->Route,
                $packageReturn->taskOnfleet
            );

            fputcsv($file, $lineData, $delimiter);
        }

        fseek($file, 0);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '";');

        fpassthru($file);
    }

    public function ReturnDispatch(Request $request)
    {
        $packageDispatch = PackageDispatch::where('idPackage', $request->get('Reference_Number_1'))
                                            ->where('status', 'Dispatch')
                                            ->first();

        if($packageDispatch)
        {
            if($packageDispatch->idUser == Auth::user()->id || Auth::user()->role->name == 'Administrador')
            {
                try
                {
                    DB::beginTransaction();

                    $package = Package::find($request->get('Reference_Number_1'));

                    $package->Dispatch = 0;
                    $package->Date_Dispatch = null;
                    $package->idUserDispatch = 0;
                    $package->status = 'Inbound';

                    $package->save();

                    $packageHistory = new PackageHistory();

                    $packageHistory->id = date('Y-m-d H:i:s');
                    $packageHistory->idPackage = $request->get('Reference_Number_1');
                    $packageHistory->description = 'Package retornado';
                    $packageHistory->user = Auth::user()->email;
                    $packageHistory->status = 'Return';

                    $packageHistory->save();

                    $packageDispatch->Date_Return = date('Y-m-d H:i:s');
                    $packageDispatch->status      = 'Return';
                    $packageDispatch->Description_Return = $request->get('description');

                    $packageDispatch->save();

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

    public function IndexDelivery()
    {
        return view('package.delivery');
    }

    public function ListDelivery(Request $request)
    {
        $packageListDelivery = PackageDelivery::orderBy('created_at', 'desc')->get();

        return ['packageListDelivery' => $packageListDelivery];
    }

    public function DownloadOnfleet($idTeam, $idDriver, $type, $valuesCheck, $StateSearch, $dayNight, $dateInit = null, $dateEnd = null)
    {
        if($dateInit)
        {
            $initDate = $dateInit .' 00:00:00';
            $endDate  = $dateEnd .' 23:59:59';
        }
        else
        {
            if($dayNight == 'Day')
            {
                $initDate = date('Y-m-d') .' 03:00:00';
                $endDate  = date('Y-m-d') .' 14:59:59';
            }
            else
            {
                $initDate = date('Y-m-d') .' 15:00:00';
                $endDate  = date('Y-m-d 02:59:59', strtotime(date('Y-m-d') .' +1day'));
            }
        }

        $delimiter = ",";
        $filename  = "onfleet " . date('Y-m-d H:i:s') . ".csv";

        //create a file pointer
        $file = fopen('php://memory', 'w');

        //set column headers
        $fields = array('Recipient_Name', 'Recipient_Phone', 'Address_Line1', 'Address_Line2', 'City/Town', 'State/Province', 'Postal_Code', 'Country', 'Task_Details', 'Team', 'Driver', 'Pickup', 'Recipient_Notes', 'Latitude', 'Longitude', 'Notification', 'completeAfter', 'completeBefore', 'Organization', 'Quantity', 'Merchant', 'ServiceTime');

        fputcsv($file, $fields, $delimiter);

        if($valuesCheck == 'all')
        {
            $listPackageDispatch = PackageHistory::with('driver')
                                        ->whereBetween('Date_Dispatch', [$initDate, $endDate])
                                        ->where('dispatch', 1)
                                        ->where('status', 'Dispatch');
        }
        else
        {
            $values = explode(',', $valuesCheck);

            $listPackageDispatch = PackageHistory::with('driver')
                                        ->whereIn('Reference_Number_1', $values)
                                        ->where('dispatch', 1)
                                        ->where('status', 'Dispatch');
        }

        if($idTeam && $idDriver)
        {
            $listPackageDispatch = $listPackageDispatch->where('idUserDispatch', $idDriver);
        }
        elseif($idTeam)
        {
            $userIds = User::where('idTeam', $idTeam)->orWhere('id', $idTeam)->get('id');

            $listPackageDispatch = $listPackageDispatch->whereIn('idUserDispatch', $userIds);
        }

        if($StateSearch != 'all')
        {
            $StateSearch = explode(',', $StateSearch);

            $listPackageDispatch = $listPackageDispatch->whereIn('Dropoff_Province', $StateSearch);
        }

        $listPackageDispatch = $listPackageDispatch->get();

        foreach($listPackageDispatch as $packageDispatch)
        {
            if($packageDispatch->driver)
            {
                if($packageDispatch->driver->idTeam)
                {
                    $team   = User::find($packageDispatch->driver->idTeam)->name;
                    $driver = $packageDispatch->driver->name .' '. $packageDispatch->driver->nameOfOwner;
                }
                else
                {
                    $team   = $packageDispatch->driver->name;
                    $driver = '';
                }
            }
            else
            {
                $team   = '';
                $driver = '';
            }

            $lineData = array($packageDispatch->Dropoff_Contact_Name, $packageDispatch->Dropoff_Contact_Phone_Number, $packageDispatch->Dropoff_Address_Line_1, $packageDispatch->Dropoff_Address_Line_2, $packageDispatch->Dropoff_City, $packageDispatch->Dropoff_Province, $packageDispatch->Dropoff_Postal_Code, 'USA', $packageDispatch->Reference_Number_1, $team, $driver, 'TRUE', $driver, '', '', $packageDispatch->idPackage, '', '', '', '', '', '');

            fputcsv($file, $lineData, $delimiter);
        }

        fseek($file, 0);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '";');

        fpassthru($file);

    }

    public function DownloadRoadWarrior($idTeam, $idDriver, $StateSearch)
    {
        $initDate = date('Y-m-d 03:00:00');
        $endDate  = date('Y-m-d 23:59:59');

        $delimiter = ",";
        $filename = "road warrior " . date('Y-m-d H:i:s') . ".csv";

        //create a file pointer
        $file = fopen('php://memory', 'w');

        //set column headers
        $fields = array('Name', 'building/house', 'Street Name', 'City', 'State', 'Postal', 'Country', 'Color', 'Phone', 'Note', 'Latitude', 'Longitude', 'Visit Time');


        fputcsv($file, $fields, $delimiter);

        $listPackageDispatch = PackageHistory::with('driver')
                                        ->whereBetween('Date_Dispatch', [$initDate, $endDate])
                                        ->where('dispatch', 1)
                                        ->where('status', 'Dispatch');

        if($idTeam && $idDriver)
        {
            $listPackageDispatch = $listPackageDispatch->where('idTeam', $idTeam)->where('idUserDispatch', $idDriver);
        }
        elseif($idTeam)
        {
            $listPackageDispatch = $listPackageDispatch->where('idTeam', $idTeam);
        }

        if($StateSearch != 'all')
        {
            $StateSearch = explode(',', $StateSearch);

            $listPackageDispatch = $listPackageDispatch->whereIn('Dropoff_Province', $StateSearch);
        }

        $listPackageDispatch = $listPackageDispatch->with(['team', 'driver'])->get();

        foreach($listPackageDispatch as $packageDispatch)
        {
            $lineData = array(
                                $packageDispatch->Dropoff_Address_Line_1,
                                $packageDispatch->Dropoff_Address_Line_2,
                                $packageDispatch->Dropoff_Address_Line_1,
                                $packageDispatch->Dropoff_City,
                                $packageDispatch->Dropoff_Province,
                                $packageDispatch->Dropoff_Postal_Code, 
                                'USA',
                                '',
                                $packageDispatch->Dropoff_Contact_Phone_Number,
                                $packageDispatch->Reference_Number_1,
                                '',
                                '',
                                '');

            fputcsv($file, $lineData, $delimiter);
        }

        fseek($file, 0);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '";');

        fpassthru($file);
    }
}
