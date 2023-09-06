<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{ Company, PackageAux, PackageBlocked, PackageHistory, PackageManifest, PackageNotExists, Routes, RoutesAux, RoutesZipCode };

use Illuminate\Support\Facades\Validator;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PhpOffice\PhpOfficePhpSpreadsheetSpreadsheet;
use PhpOffice\PhpOfficePhpSpreadsheetReaderCsv;
use PhpOffice\PhpOfficePhpSpreadsheetReaderXlsx;

use DB;
use Auth;
use Session;
use Mail;

class PackageManifestController extends Controller
{
    public function Index()
    {
        return view('package.index');
    }

    public function List(Request $request, $status, $idCompany, $route, $state)
    {
        $data            = $this->GetData($status, $idCompany, $route, $state, 'list');
        $packageList     = $data['packageList'];
        $quantityPackage = $data['quantityPackage'];

        $listState  = PackageManifest::select('Dropoff_Province')
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        return ['packageList' => $packageList, 'listState' => $listState, 'quantityPackage' => $quantityPackage];
    }

    public function Insert(Request $request)
    {
        $validator = Validator::make($request->all(),

            [
                "Reference_Number_1" => ["required", "unique:packagemanifest"],
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

        $packageHistory = PackageHistory::where('Reference_Number_1', $request->get('Reference_Number_1'))
                                                    ->where('status', 'Manifest')
                                                    ->first();

        if(!$packageHistory)
        {
            try
            {
                DB::beginTransaction();

                $created_at = date('Y-m-d H:i:s');

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
                $package->status                       = 'Manifest';
                $package->created_at                   = $created_at;
                $package->updated_at                   = $created_at;

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
                $packageHistory->idUser                       =  Auth::user()->id;
                $packageHistory->idUserManifest               =  Auth::user()->id;
                $packageHistory->Date_manifest                = date('Y-m-d H:s:i');
                $packageHistory->Description                  = 'Manifest - for: '.Auth::user()->name .' '. Auth::user()->nameOfOwner;
                $packageHistory->status                       = 'Manifest';
                $packageHistory->actualDate                   = $created_at;
                $packageHistory->created_at                   = $created_at;
                $packageHistory->updated_at                   = $created_at;

                $packageHistory->save();

                $packageNotExists = PackageNotExists::find($request->get('Reference_Number_1'));

                if($packageNotExists)
                {
                    $packageNotExists->delete();
                }

                DB::commit();

                return response()->json(["stateAction" => true], 200);

            }
            catch(\Exception $e)
            {
                DB::rollback();

                return response()->json(["stateAction" => false], 200);
            }
        }

        return ['stateAction' => 'exists'];
    } 

    public function Export($status, $idCompany, $route, $state, $type)
    {
        $delimiter = ",";
        $filename  = $type == 'download' ? "PACKAGES - MANIFEST " . date('Y-m-d H:i:s') . ".csv" : Auth::user()->id ."- PACKAGES - MANIFEST.csv";
        $file      = $type == 'download' ? fopen('php://memory', 'w') : fopen(public_path($filename), 'w');

        //set column headers
        $fields = array('DATE', 'HOUR', 'COMPANY', 'PACKAGE ID', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'WEIGHT', 'ROUTE');

        fputcsv($file, $fields, $delimiter);

        $data        = $this->GetData($status, $idCompany, $route, $state, 'export');
        $packageList = $data['packageList'];

        foreach($packageList as $packageManifest)
        {
            $lineData = array(
                                date('m-d-Y', strtotime($packageManifest->created_at)),
                                date('H:i:s', strtotime($packageManifest->created_at)),
                                $packageManifest->company,
                                $packageManifest->Reference_Number_1,
                                $packageManifest->Dropoff_Contact_Name,
                                $packageManifest->Dropoff_Contact_Phone_Number,
                                $packageManifest->Dropoff_Address_Line_1,
                                $packageManifest->Dropoff_City,
                                $packageManifest->Dropoff_Province,
                                $packageManifest->Dropoff_Postal_Code,
                                $packageManifest->Weight,
                                $packageManifest->Route
                            );

            fputcsv($file, $lineData, $delimiter);
        }
    
        if($type == 'download')
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

            SendGeneralExport('Packages Manifest', $filename);

            return ['stateAction' => true];
        }
    }

    public function GetData($status, $idCompany, $route, $state, $type)
    {
        $routes = explode(',', $route);
        $states = explode(',', $state);

        $packageList = PackageManifest::where('idStore', 0)
                                    ->where('status', $status);

        if($idCompany != 0)
        {
            $packageList = $packageList->where('idCompany', $idCompany);
        }

        if($route != 'all')
        {
            $packageList = $packageList->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $packageList = $packageList->whereIn('Dropoff_Province', $states);
        }

        $packageList = $packageList->orderBy('created_at', 'desc');

        if($type == 'list')
        {
            $packageList = $packageList->select('company', 'Reference_Number_1', 'Dropoff_Contact_Name', 'Dropoff_Contact_Phone_Number', 'Dropoff_Address_Line_1', 'Dropoff_City', 'Dropoff_Province', 'Dropoff_Postal_Code', 'Weight', 'Route', 'created_at')
                                    ->paginate(50);

            $quantityPackage = $packageList->total();
        }
        else
        {
            $packageList     = $packageList->get();
            $quantityPackage = 0;
        }
        
        return ['packageList' => $packageList, 'quantityPackage' => $quantityPackage];
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
        $package->updated_at                   = date('Y-m-d H:i:s');

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

        $listPackageHistory = PackageHistory::where('status', 'Manifest')
                                            ->whereIn('Reference_Number_1', $packageIDs)
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
                            $packageBlocked = PackageBlocked::where('Reference_Number_1', $row[0])->first();

                            $created_at = date('Y-m-d H:i:s');
                            $company    = Company::find($row[32]);

                            $package = new PackageManifest();

                            $package->Reference_Number_1 = $row[0];
                            $package->idCompany          = $company->id;
                            $package->company            = $company->name;
                            $package->Dropoff_Contact_Name = $row[16];
                            $package->Dropoff_Company = $row[17];
                            $package->Dropoff_Contact_Phone_Number = $row[18];
                            $package->Dropoff_Contact_Email = $row[19];
                            $package->Dropoff_Address_Line_1 = $row[20];
                            $package->Dropoff_Address_Line_2 = $row[21];
                            $package->Dropoff_City = $row[22];
                            $package->Dropoff_Province = $row[23];
                            $package->Dropoff_Postal_Code = $row[24];
                            $package->Notes = $row[28];
                            $package->Weight = $row[30];
                            $package->filter = $packageBlocked ? 1 : 0;
                            $package->status = 'Manifest';
                            $package->created_at = $created_at;
                            $package->updated_at = $created_at;

                            $routesZipCode = RoutesZipCode::find($row[24]);

                            if(!$routesZipCode)
                            {
                                $routesAux = RoutesAux::where('name', $row[31])->first();

                                if(!$routesAux)
                                {
                                    $routesAux = new RoutesAux();
                                    $routesAux->name = $row[31];
                                    $routesAux->save();
                                }
                                
                                $routesZipCode = new RoutesZipCode();
                                $routesZipCode->zipCode   = $route->zipCode;
                                $routesZipCode->idRoute   = $routesAux->id;
                                $routesZipCode->routeName = $routesAux->name;
                                $routesZipCode->save();
                            }

                            $package->Route = $routesZipCode->routeName;

                            $package->save();

                            $packageHistory = new PackageHistory();

                            $packageHistory->id = uniqid();
                            $packageHistory->Reference_Number_1           = $row[0];
                            $packageHistory->idCompany                    = $company->id;
                            $packageHistory->company                      = $company->name;
                            $packageHistory->Dropoff_Contact_Name         = $row[16];
                            $packageHistory->Dropoff_Company              = $row[17];
                            $packageHistory->Dropoff_Contact_Phone_Number = $row[18];
                            $packageHistory->Dropoff_Contact_Email        = $row[19];
                            $packageHistory->Dropoff_Address_Line_1       = $row[20];
                            $packageHistory->Dropoff_Address_Line_2       = $row[21];
                            $packageHistory->Dropoff_City                 = $row[22];
                            $packageHistory->Dropoff_Province             = $row[23];
                            $packageHistory->Dropoff_Postal_Code          = $row[24];
                            $packageHistory->Notes                        = $row[28];
                            $packageHistory->Weight                       = $row[30];
                            $packageHistory->Route                        = $route->name;
                            $packageHistory->idUser                       = Auth::user()->id;
                            $packageHistory->idUserManifest               = Auth::user()->id;
                            $packageHistory->Date_manifest                = date('Y-m-d H:s:i');
                            $packageHistory->Description                  = 'Manifest: Not yet received: '. $company->name;
                            $packageHistory->status                       = 'Manifest';
                            $packageHistory->actualDate                   = date('Y-m-d H:i:s');
                            $packageHistory->created_at                   = date('Y-m-d H:i:s');
                            $packageHistory->updated_at                   = date('Y-m-d H:i:s');

                            $packageHistory->save();

                            $countSave++;
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

