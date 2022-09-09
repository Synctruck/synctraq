<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{PackageAux, PackageHistory, PackageManifest, PackageNotExists, Routes};

use Illuminate\Support\Facades\Validator;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PhpOffice\PhpOfficePhpSpreadsheetSpreadsheet;
use PhpOffice\PhpOfficePhpSpreadsheetReaderCsv;
use PhpOffice\PhpOfficePhpSpreadsheetReaderXlsx;

use DB;
use Illuminate\Support\Facades\Auth;
use Session;

class PackageManifestController extends Controller
{
    public function Index()
    {
        return view('package.index');
    }

    public function List(Request $request, $route, $state)
    {
        $routes = explode(',', $route);
        $states = explode(',', $state);

        $packageList = PackageManifest::where('status', 'On hold');

        if($route != 'all')
        {
            $packageList = $packageList->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $packageList = $packageList->whereIn('Dropoff_Province', $states);
        }

        if($request->get('textSearch'))
        {
            $packageList = $packageList->where('Reference_Number_1', 'like', '%'. $request->get('textSearch') .'%')
                                        ->orderBy('created_at', 'desc');
        }
        else
        {
            $packageList = $packageList->orderBy('created_at', 'desc');
        }

        $packageList = $packageList->paginate(50);

        $quantityPackage = $packageList->total();

        $listState  = PackageManifest::select('Dropoff_Province')
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        return ['packageList' => $packageList, 'listState' => $listState, 'quantityPackage' => $quantityPackage];
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
            ]
        );

        if($validator->fails())
        {
            return response()->json(["status" => 422, "errors" => $validator->errors()], 422);
        }

        if(substr($request->get('Reference_Number_1'), 0, 6) == 'INLAND' || substr($request->get('Reference_Number_1'), 0, 5) == '67660')
        {
            $packageHistory = PackageHistory::where('Reference_Number_1', $request->get('Reference_Number_1'))
                                                    ->where('status', 'On hold')
                                                    ->first();

            if(!$packageHistory)
            {
                try
                {
                    DB::beginTransaction();

                    $package = new PackageManifest();

                    $package->Reference_Number_1           = $request->get('Reference_Number_1');
                    $package->Dropoff_Contact_Name         = $request->get('Dropoff_Contact_Name');
                    $package->Dropoff_Contact_Phone_Number = $request->get('Dropoff_Contact_Phone_Number');
                    $package->Dropoff_Address_Line_1       = $request->get('Dropoff_Address_Line_1');
                    $package->Dropoff_City                 = $request->get('Dropoff_City');
                    $package->Dropoff_Province             = $request->get('Dropoff_Province');
                    $package->Dropoff_Postal_Code          = $request->get('Dropoff_Postal_Code');
                    $package->Weight                       = $request->get('Weight');
                    $package->Route                        = $request->get('Route');
                    $package->status                       = 'On hold';

                    $package->save();

                    $packageHistory = new PackageHistory();

                    $packageHistory->id                           = uniqid();
                    $packageHistory->Reference_Number_1           = $request->get('Reference_Number_1');
                    $packageHistory->Dropoff_Contact_Name         = $request->get('Dropoff_Contact_Name');
                    $packageHistory->Dropoff_Contact_Phone_Number = $request->get('Dropoff_Contact_Phone_Number');
                    $packageHistory->Dropoff_Address_Line_1       = $request->get('Dropoff_Address_Line_1');
                    $packageHistory->Dropoff_City                 = $request->get('Dropoff_City');
                    $packageHistory->Dropoff_Province             = $request->get('Dropoff_Province');
                    $packageHistory->Dropoff_Postal_Code          = $request->get('Dropoff_Postal_Code');
                    $packageHistory->Weight                       = $request->get('Weight');
                    $packageHistory->Route                        = $request->get('Route');
                    $packageHistory->status                       = $request->get('status');
                    $packageHistory->idUser                       = Auth::user()->id;
                    $packageHistory->idUserManifest               = Auth::user()->id;
                    $packageHistory->Date_manifest                = date('Y-m-d H:s:i');
                    $packageHistory->Description                  = 'On hold - for: '. Auth::user()->name .' '. Auth::user()->nameOfOwner;
                    $packageHistory->status                       = 'On hold';

                    $packageHistory->save();

                    $packageNotExists = PackageNotExists::find($request->get('Reference_Number_1'));

                    if($packageNotExists)
                    {
                        $packageNotExists->delete();
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

            return ['stateAction' => 'exists'];
        }

        return ['stateAction' => 'notInland'];
    }

    public function Get($Reference_Number_1)
    {
        $package = PackageManifest::find($Reference_Number_1);

        return ['package' => $package];
    }

    public function Update(Request $request)
    {
        $package = PackageManifest::find($request->get('Reference_Number_1'));

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

        $package = PackageManifest::find($request->get('Reference_Number_1'));

        $package->Reference_Number_1           = $request->get('Reference_Number_1');
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

        return response()->json(["stateAction" => true], 200);
    }

    public function CheckFilter(Request $request)
    {
        $packageManifest = PackageManifest::find($request->get('Reference_Number_1'));

        $packageManifest->filter = $packageManifest->filter == 1 ? 0 : 1;

        $packageManifest->save();

        return ['stateAction' => true];
    }

    public function UpdateFilter(Request $request)
    {
        try
        {
            DB::beginTransaction();

            $listPackageManifest = PackageManifest::all();

            foreach($listPackageManifest as $packageManifest)
            {
                $packageManifest = PackageManifest::find($packageManifest->Reference_Number_1);

                $packageManifest->filter = 0;

                $packageManifest->save();
            }

            $valuesCheck = $request->get('valuesCheck') != '' ? explode(",", $request->get('valuesCheck')) : [];

            foreach($valuesCheck as $value)
            {
                $packageManifest = PackageManifest::find($value);

                $packageManifest->filter = 1;

                $packageManifest->save();
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

    public function Search($Reference_Number_1)
    {
        $packageHistoryList = PackageHistory::where('idPackage', $Reference_Number_1)
                                            ->orderBy('created_at', 'asc')
                                            ->get();

        return ['packageHistoryList' => $packageHistoryList];
    }

    public function Import(Request $request)
    {
        $file = $request->file('file');

        $file->move(public_path() .'/file-import', 'manifest.csv');

        $handle = fopen(public_path('file-import/manifest.csv'), "r");

        $lineNumber = 1;

        $countSave = 0;

        $packageIDs = '';

        $quantitylines = count(file(public_path('file-import/manifest.csv')));

        while (($raw_string = fgets($handle)) !== false)
        {
            if($lineNumber > 1)
            {
                $row = str_getcsv($raw_string);

                $packageIDs = $packageIDs == '' ? $row[0] : $packageIDs .','. $row[0];
            }

            $lineNumber++;
        }

        $packageIDs = explode(',', $packageIDs);

        $listPackageHistory = PackageHistory::where('status', 'On hold')
                                            ->whereIn('Reference_Number_1', $packageIDs)
                                            ->where('status', 'On hold')
                                            ->get();

        if($quantitylines > count($listPackageHistory))
        {
            $packageIDsNotValidate = [];

            foreach($listPackageHistory as $packageHistory)
            {
                array_push($packageIDsNotValidate, $packageHistory->Reference_Number_1);
            }

            $packageIDsValidate = array_diff($packageIDs, $packageIDsNotValidate);

            try
            {
                DB::beginTransaction();

                $handle = fopen(public_path('file-import/manifest.csv'), "r");

                $lineNumber = 1;

                while (($raw_string = fgets($handle)) !== false)
                {
                    if($lineNumber > 1)
                    {
                        $row = str_getcsv($raw_string);

                        if(in_array($row[0], $packageIDsValidate))
                        {
                            if(substr($row[0], 0, 6) == 'INLAND' || substr($row[0], 0, 5) == '67660')
                            {
                                $package = new PackageManifest();

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
                                $package->Name = isset($row[32]) ? $row[32] : '';
                                $package->status = 'On hold';

                                $route = Routes::where('zipCode', $row[24])->first();

                                if(!$route)
                                {
                                    $route = new Routes();

                                    $route->zipCode = $row[24];
                                    $route->name    = $row[31];

                                    $route->save();
                                }

                                $package->Route = $route->name;

                                $package->save();

                                $packageHistory = new PackageHistory();

                                $packageHistory->id = uniqid();
                                $packageHistory->Reference_Number_1 = $row[0];
                                $packageHistory->Reference_Number_2 = $row[1];
                                $packageHistory->Reference_Number_3 = $row[2];
                                $packageHistory->Ready_At = $row[3];
                                $packageHistory->Del_Date = $row[4];
                                $packageHistory->Del_no_earlier_than = $row[5];
                                $packageHistory->Del_no_later_than = $row[6];
                                $packageHistory->Pickup_Contact_Name = $row[7];
                                $packageHistory->Pickup_Company = $row[8];
                                $packageHistory->Pickup_Contact_Phone_Number = $row[9];
                                $packageHistory->Pickup_Contact_Email = $row[10];
                                $packageHistory->Pickup_Address_Line_1 = $row[11];
                                $packageHistory->Pickup_Address_Line_2 = $row[12];
                                $packageHistory->Pickup_City = $row[13];
                                $packageHistory->Pickup_Province = $row[14];
                                $packageHistory->Pickup_Postal_Code = $row[15];
                                $packageHistory->Dropoff_Contact_Name = $row[16];
                                $packageHistory->Dropoff_Company = $row[17];
                                $packageHistory->Dropoff_Contact_Phone_Number = $row[18];
                                $packageHistory->Dropoff_Contact_Email = $row[19];
                                $packageHistory->Dropoff_Address_Line_1 = $row[20];
                                $packageHistory->Dropoff_Address_Line_2 = $row[21];
                                $packageHistory->Dropoff_City = $row[22];
                                $packageHistory->Dropoff_Province = $row[23];
                                $packageHistory->Dropoff_Postal_Code = $row[24];
                                $packageHistory->Service_Level = $row[25];
                                $packageHistory->Carrier_Name = $row[26];
                                $packageHistory->Vehicle_Type_Id = $row[27];
                                $packageHistory->Notes = $row[28];
                                $packageHistory->Number_Of_Pieces = $row[29];
                                $packageHistory->Weight = $row[30];
                                $packageHistory->Route = $route->name;
                                $packageHistory->Name = isset($row[32]) ? $row[32] : '';
                                $packageHistory->idUser = Auth::user()->id;
                                $packageHistory->idUserManifest = Auth::user()->id;
                                $packageHistory->Date_manifest = date('Y-m-d H:s:i');
                                $packageHistory->Description = 'On hold - for: '. Auth::user()->name .' '. Auth::user()->nameOfOwner;
                                $packageHistory->status = 'On hold';

                                $packageHistory->save();

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
        else
        {
            return ['stateAction' => true];
        }
    }
}

