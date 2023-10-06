<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{PackageHistory, PackageInbound, PackageManifest, PackageNotExists, States, Cellar};

use Illuminate\Support\Facades\Validator;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PhpOffice\PhpOfficePhpSpreadsheetSpreadsheet;
use PhpOffice\PhpOfficePhpSpreadsheetReaderCsv;
use PhpOffice\PhpOfficePhpSpreadsheetReaderXlsx;

use DB;
use Illuminate\Support\Facades\Auth;
use Session;

class PackageInboundController extends Controller
{
    public function Index()
    {
        return view('package.inbound');
    }

    public function List(Request $request, $dataView, $route, $state)
    {
        $routes = explode(',', $route);
        $states = explode(',', $state);

        if(Auth::user()->role->name == 'Validador')
        {
            $packageListInbound = PackageInbound::with('user')->where('idUser', Auth::user()->id)
                                                ->where('status', 'Inbound');
        }
        else if(Auth::user()->role->name == 'Master')
        {
            $packageListInbound = PackageInbound::with('user')->where('status', 'Inbound');
        }

        if($dataView == 'today')
        {
            $dateInit  = date('Y-m-d') .' 00:00:00';
            $dateEnd   = date('Y-m-d', strtotime('+1 day')) .' 03:00:00';

            $packageListInbound = $packageListInbound->whereBetween('created_at', [$dateInit, $dateEnd]);
        }

        if($route != 'all')
        {
            $packageListInbound = $packageListInbound->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $packageListInbound = $packageListInbound->whereIn('Dropoff_Province', $states);
        }

        $packageListInbound = $packageListInbound->where('reInbound', 0)
                                                ->orderBy('created_at', 'desc')
                                                ->paginate(25);

        $quantityInbound = $packageListInbound->total();

        $listState  = PackageInbound::select('Dropoff_Province')
                                            ->groupBy('Dropoff_Province')
                                            ->get();

        return ['packageList' => $packageListInbound, 'listState' => $listState, 'quantityInbound' => $quantityInbound];
    }

    public function Insert(Request $request)
    {
        if(substr($request->get('Reference_Number_1'), 0, 6) == 'INLAND' || substr($request->get('Reference_Number_1'), 0, 5) == '67660')
        {
            $packageManifest = PackageManifest::find($request->get('Reference_Number_1'));

            $packageInbound = PackageInbound::find($request->get('Reference_Number_1'));

            if($packageInbound)
            {
                return ['stateAction' => 'validated', 'packageInbound' => $packageInbound];
            }

            if($packageManifest)
            {
                if($packageManifest->filter)
                {
                    return ['stateAction' => 'validatedFilterPackage'];
                }

                $state = States::where('name', $packageManifest->Dropoff_Province)
                                ->where('filter', 1)
                                ->first();

                if($state)
                {
                    return ['stateAction' => 'validatedFilterState'];
                }

                try
                {
                    DB::beginTransaction();

                    $packageInbound = new packageInbound();

                    $packageInbound->Reference_Number_1           = $packageManifest->Reference_Number_1;
                    $packageInbound->TRUCK                        = $request->get('TRUCK') ? $request->get('TRUCK') : '';
                    $packageInbound->CLIENT                       = $request->get('CLIENT') ? $request->get('CLIENT') : '';
                    $packageInbound->Dropoff_Contact_Name         = $packageManifest->Dropoff_Contact_Name;
                    $packageInbound->Dropoff_Company              = $packageManifest->Dropoff_Company;
                    $packageInbound->Dropoff_Contact_Phone_Number = $packageManifest->Dropoff_Contact_Phone_Number;
                    $packageInbound->Dropoff_Contact_Email        = $packageManifest->Dropoff_Contact_Email;
                    $packageInbound->Dropoff_Address_Line_1       = $packageManifest->Dropoff_Address_Line_1;
                    $packageInbound->Dropoff_Address_Line_2       = $packageManifest->Dropoff_Address_Line_2;
                    $packageInbound->Dropoff_City                 = $packageManifest->Dropoff_City;
                    $packageInbound->Dropoff_Province             = $packageManifest->Dropoff_Province;
                    $packageInbound->Dropoff_Postal_Code          = $packageManifest->Dropoff_Postal_Code;
                    $packageInbound->Notes                        = $packageManifest->Notes;
                    $packageInbound->Weight                       = $packageManifest->Weight;
                    $packageInbound->Route                        = $packageManifest->Route;
                    $packageInbound->idUser                       = Auth::user()->id;
                    $packageInbound->status                       = 'Inbound';

                    $cellar = Cellar::find(Auth::user()->idCellar);
                    logger("idCellar: " . $cellar->id);
                        if($cellar>0)
                        {
                            logger("idCellar: " . $cellar->id);
                           $packageInbound->idCellar    = $cellar->id;
                           $packageInbound->nameCellar  = $cellar->name;
                           $packageInbound->stateCellar = $cellar->state;
                           $packageInbound->cityCellar  = $cellar->city;
                         }

                    $packageInbound->save();

                    $packageHistory = new PackageHistory();

                    $packageHistory->id                           = uniqid();
                    $packageHistory->Reference_Number_1           = $packageManifest->Reference_Number_1;
                    $packageHistory->TRUCK                        = $request->get('TRUCK') ? $request->get('TRUCK') : '';
                    $packageHistory->CLIENT                       = $request->get('CLIENT') ? $request->get('CLIENT') : '';
                    $packageHistory->Dropoff_Contact_Name         = $packageManifest->Dropoff_Contact_Name;
                    $packageHistory->Dropoff_Company              = $packageManifest->Dropoff_Company;
                    $packageHistory->Dropoff_Contact_Phone_Number = $packageManifest->Dropoff_Contact_Phone_Number;
                    $packageHistory->Dropoff_Contact_Email        = $packageManifest->Dropoff_Contact_Email;
                    $packageHistory->Dropoff_Address_Line_1       = $packageManifest->Dropoff_Address_Line_1;
                    $packageHistory->Dropoff_Address_Line_2       = $packageManifest->Dropoff_Address_Line_2;
                    $packageHistory->Dropoff_City                 = $packageManifest->Dropoff_City;
                    $packageHistory->Dropoff_Province             = $packageManifest->Dropoff_Province;
                    $packageHistory->Dropoff_Postal_Code          = $packageManifest->Dropoff_Postal_Code;
                    $packageHistory->Notes                        = $packageManifest->Notes;
                    $packageHistory->Weight                       = $packageManifest->Weight;
                    $packageHistory->Route                        = $packageManifest->Route;
                    $packageHistory->idUser                       = Auth::user()->id;
                    $packageHistory->idUserInbound                = Auth::user()->id;
                    $packageHistory->Date_Inbound                 = date('Y-m-d H:s:i');
                    $packageHistory->Description                  = 'Inbound - for: '. Auth::user()->name .' '. Auth::user()->nameOfOwner;
                    $packageHistory->inbound                      = 1;
                    $packageHistory->status                       = 'Inbound';

                    if($cellar)
                        {
                           $packageHistory->idCellar    = $cellar->id;
                           $packageHistory->nameCellar  = $cellar->name;
                           $packageHistory->stateCellar = $cellar->state;
                           $packageHistory->cityCellar  = $cellar->city;
                         }

                    $packageHistory->save();

                    $packageManifest->delete();

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
                $packageHistory = PackageHistory::where('Reference_Number_1', $request->get('Reference_Number_1'))
                                                ->where('status', 'Inbound')
                                                ->where('inbound', 1)
                                                ->first();

                if($packageHistory)
                {
                    return ['stateAction' => 'validated', 'packageInbound' => $packageHistory];
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
        }

        return ['stateAction' => 'notInland'];
    }

    public function Get($Reference_Number_1)
    {
        $packageInbound = packageInbound::find($Reference_Number_1);

        return ['package' => $packageInbound];
    }

    public function Update(Request $request)
    {
        $package = packageInbound::find($request->get('Reference_Number_1'));

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

        $packageInbound = packageInbound::find($request->get('Reference_Number_1'));

        $packageInbound->Reference_Number_1           = $request->get('Reference_Number_1');
        $packageInbound->Dropoff_Contact_Name         = $request->get('Dropoff_Contact_Name');
        $packageInbound->Dropoff_Contact_Phone_Number = $request->get('Dropoff_Contact_Phone_Number');
        $packageInbound->Dropoff_Address_Line_1       = $request->get('Dropoff_Address_Line_1');
        $packageInbound->Dropoff_Address_Line_2       = $request->get('Dropoff_Address_Line_2');
        $packageInbound->Dropoff_City                 = $request->get('Dropoff_City');
        $packageInbound->Dropoff_Province             = $request->get('Dropoff_Province');
        $packageInbound->Dropoff_Postal_Code          = $request->get('Dropoff_Postal_Code');
        $packageInbound->Weight                       = $request->get('Weight');
        $packageInbound->Route                        = $request->get('Route');

        $cellar = Cellar::find(Auth::user()->idCellar);

        if($cellar)
       {
          $packageInbound->idCellar    = $cellar->id;
          $packageInbound->nameCellar  = $cellar->name;
          $packageInbound->stateCellar = $cellar->state;
          $packageInbound->cityCellar  = $cellar->city;
        }

        $packageInbound->save();

        return response()->json(["stateAction" => true], 200);
    }

    public function Import(Request $request)
    {
        $file = $request->file('file');

        $file->move(public_path() .'/file-import', 'inbound.csv');

        $handle = fopen(public_path('file-import/inbound.csv'), "r");

        $lineNumber = 1;

        $countSave = 0;

        $packageIDs = '';

        $quantitylines = count(file(public_path('file-import/inbound.csv')));

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

        $listPackageHistory = PackageHistory::where('status', 'Inbound')
                                            ->whereIn('Reference_Number_1', $packageIDs)
                                            ->where('inbound', 1)
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

                $handle = fopen(public_path('file-import/inbound.csv'), "r");

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
                                $packageManifest = PackageManifest::where('Reference_Number_1', $row[0])
                                                                ->where('filter', 0)
                                                                ->first();

                                if($packageManifest)
                                {
                                    $packageInbound = new PackageInbound();

                                    $packageInbound->Reference_Number_1           = $packageManifest->Reference_Number_1;
                                    $packageInbound->Dropoff_Contact_Name         = $packageManifest->Dropoff_Contact_Name;
                                    $packageInbound->Dropoff_Company              = $packageManifest->Dropoff_Company;
                                    $packageInbound->Dropoff_Contact_Phone_Number = $packageManifest->Dropoff_Contact_Phone_Number;
                                    $packageInbound->Dropoff_Contact_Email        = $packageManifest->Dropoff_Contact_Email;
                                    $packageInbound->Dropoff_Address_Line_1       = $packageManifest->Dropoff_Address_Line_1;
                                    $packageInbound->Dropoff_Address_Line_2       = $packageManifest->Dropoff_Address_Line_2;
                                    $packageInbound->Dropoff_City                 = $packageManifest->Dropoff_City;
                                    $packageInbound->Dropoff_Province             = $packageManifest->Dropoff_Province;
                                    $packageInbound->Dropoff_Postal_Code          = $packageManifest->Dropoff_Postal_Code;
                                    $packageInbound->Notes                        = $packageManifest->Notes;
                                    $packageInbound->Weight                       = $packageManifest->Weight;
                                    $packageInbound->Route                        = $packageManifest->Route;
                                    $packageInbound->idUser                       = Auth::user()->id;
                                    $packageInbound->status                       = 'Inbound';

                                    $cellar = Cellar::find(Auth::user()->idCellar);

                                     if($cellar)
                                     {
                                      $packageInbound->idCellar    = $cellar->id;
                                      $packageInbound->nameCellar  = $cellar->name;
                                      $packageInbound->stateCellar = $cellar->state;
                                      $packageInbound->cityCellar  = $cellar->city;
                                     }
                                    

                                    $packageInbound->save();

                                    $packageHistory = new PackageHistory();

                                    $packageHistory->id                           = uniqid();
                                    $packageHistory->Reference_Number_1           = $packageManifest->Reference_Number_1;
                                    $packageHistory->Dropoff_Contact_Name         = $packageManifest->Dropoff_Contact_Name;
                                    $packageHistory->Dropoff_Company              = $packageManifest->Dropoff_Company;
                                    $packageHistory->Dropoff_Contact_Phone_Number = $packageManifest->Dropoff_Contact_Phone_Number;
                                    $packageHistory->Dropoff_Contact_Email        = $packageManifest->Dropoff_Contact_Email;
                                    $packageHistory->Dropoff_Address_Line_1       = $packageManifest->Dropoff_Address_Line_1;
                                    $packageHistory->Dropoff_Address_Line_2       = $packageManifest->Dropoff_Address_Line_2;
                                    $packageHistory->Dropoff_City                 = $packageManifest->Dropoff_City;
                                    $packageHistory->Dropoff_Province             = $packageManifest->Dropoff_Province;
                                    $packageHistory->Dropoff_Postal_Code          = $packageManifest->Dropoff_Postal_Code;
                                    $packageHistory->Notes                        = $packageManifest->Notes;
                                    $packageHistory->Weight                       = $packageManifest->Weight;
                                    $packageHistory->Route                        = $packageManifest->Route;
                                    $packageHistory->idUser                       = Auth::user()->id;
                                    $packageHistory->idUserInbound                = Auth::user()->id;
                                    $packageHistory->Date_Inbound                 = date('Y-m-d H:s:i');
                                    $packageHistory->Description                  = 'Inbound - for: '. Auth::user()->name .' '. Auth::user()->nameOfOwner;
                                    $packageHistory->inbound                      = 1;
                                    $packageHistory->status                       = 'Inbound';

                                    if($cellar)
                                    {
                                     $packageHistory->idCellar    = $cellar->id;
                                     $packageHistory->nameCellar  = $cellar->name;
                                     $packageHistory->stateCellar = $cellar->state;
                                     $packageHistory->cityCellar  = $cellar->city;
                                    }
                                   

                                    $packageHistory->save();

                                    $packageManifest->delete();
                                }
                                else
                                {
                                    $packageNotExists = PackageNotExists::find($row[0]);

                                    if(!$packageNotExists)
                                    {
                                        $packageNotExists = new PackageNotExists();

                                        $packageNotExists->Reference_Number_1 = $row[0];
                                        $packageNotExists->idUser             = Auth::user()->id;
                                        $packageNotExists->Date_Inbound       = date('Y-m-d H:i:s');

                                        $packageNotExists->save();
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
        else
        {
            return ['stateAction' => true];
        }
    }
}
