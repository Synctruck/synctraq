<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use \App\Service\ServicePackageTerminal;

use App\Models\{ Company, CompanyStatus, Configuration, DimFactorCompany, PackageBlocked, PackageHistory, PackageInbound, PackageLost, PackageManifest, PackageNotExists, PackagePreDispatch, PackageWarehouse, PackagePriceCompanyTeam, PackageReturnCompany, States, LiveRoute };


use Illuminate\Support\Facades\Validator;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

use Barryvdh\DomPDF\Facade\PDF;

use Picqer\Barcode\BarcodeGeneratorPNG;

use App\Http\Controllers\{ CompanyController, RangePriceCompanyController };
use App\Http\Controllers\Api\PackageController;


use DB;
use Log;
use Session;

class PackageInboundController extends Controller
{
    public function Index()
    {
        return view('package.inbound');
    }

    public function findRoute($barCode)
    {  
        $packageRouteName = 'N/A';
        $packageZipCode   = 'N/A';

        $packageInbound = PackageInbound::select('Dropoff_Postal_Code as packageZipCode','Route as packageRouteName')->where('Reference_Number_1', $barCode)->where('status', 'Inbound')->get();
       
        if ($packageInbound->count()) {
            
            $packageZipCode     = $packageInbound[0]->packageZipCode;
            $packageRouteName   = $packageInbound[0]->packageRouteName;

             $liveRoute = LiveRoute::select('route_name as routeName')->where('zip_code',$packageZipCode)->get();
        
             if($liveRoute->count())
            {   
              $packageRouteName =  $liveRoute->pluck('routeName')->implode(', ');     
            }
        } 

        $html = '
        <table cellspacing="0" cellpadding="0">
                   
                   <tr>
                     <td width="1px"></td>
                     <td><span style="font-size:17px;">Route: '.$packageRouteName.'</span></td>
                   </tr>
                   <tr>
                   <tr>
                     <td width="1px"></td>
                     <td><span style="font-size:17px;">Zip Code: '.$packageZipCode.'</h1></td>
                   </tr>
                     <td width="1px"></td>
                     <td><span style="font-size:15px;">Package: '.$barCode.'<span></td>
                   </tr>
                </table>';
           
                echo $html;
    }

    public function List($idCompany, $dateStart,$dateEnd, $route, $state)
    {
        $packageListInbound = $this->getDataInbound($idCompany, $dateStart,$dateEnd, $route, $state);
        $quantityInbound = $packageListInbound->total();

        return ['packageList' => $packageListInbound, 'quantityInbound' => $quantityInbound];
    }

    private function getDataInbound($idCompany, $dateStart,$dateEnd, $route, $state,$type='list'){

        $dateStart = $dateStart .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);

        if(Auth::user()->role->name == 'Validador')
        {
            $packageListInbound = PackageInbound::with('user')->where('idUser', Auth::user()->id)
                                                ->where('status', 'Inbound');
        }
        else if(Auth::user()->role->name == 'Administrador')
        {
            $packageListInbound = PackageInbound::with('user')->where('status', 'Inbound');
        }

        $packageListInbound = $packageListInbound->whereBetween('created_at', [$dateStart, $dateEnd]);

        if($route != 'all')
        {
            $packageListInbound = $packageListInbound->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $packageListInbound = $packageListInbound->whereIn('Dropoff_Province', $states);
        }

        if($idCompany)
        {
            $packageListInbound = $packageListInbound->where('idCompany', $idCompany);
        }

        if($type =='list')
        {
            $packageListInbound = $packageListInbound->where('reInbound', 0)
                                                ->orderBy('created_at', 'desc')
                                                ->select('company', 'TRUCK', 'Reference_Number_1', 'Dropoff_Contact_Name', 'Dropoff_Contact_Phone_Number', 'Dropoff_Address_Line_1', 'Dropoff_City', 'Dropoff_Province', 'Dropoff_Postal_Code', 'Weight', 'Route', 'created_at')
                                                ->paginate(50);
        }
        else{
            
            $packageListInbound = $packageListInbound->where('reInbound', 0)->orderBy('created_at', 'desc')->get(); 
        }

        return $packageListInbound;
    }

    public function Export(Request $request,$idCompany, $dateStart,$dateEnd, $route, $state)
    {
        $delimiter = ",";
        $filename = "PACKAGES - INBOUND " . date('Y-m-d H:i:s') . ".csv";

        //create a file pointer
        $file = fopen('php://memory', 'w');

        //set column headers
        $fields = array('DATE', 'HOUR', 'VALIDATOR', 'TRUCK #', 'CLIENT', 'PACKAGE ID', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'WEIGHT', 'ROUTE');

        fputcsv($file, $fields, $delimiter);

        $packageListInbound = $this->getDataInbound($idCompany, $dateStart,$dateEnd, $route, $state,$type='export');

        foreach($packageListInbound as $packageInbound)
        {
            if($packageInbound->validator)
            {
                $validator = $packageInbound->validator->name .' '. $packageInbound->validator->nameOfOwner;
            }
            else
            {
                $validator = '';
            }

            $lineData = array(
                                date('m-d-Y', strtotime($packageInbound->created_at)),
                                date('H:i:s', strtotime($packageInbound->created_at)),
                                $validator,
                                $packageInbound->TRUCK,
                                $packageInbound->CLIENT,
                                $packageInbound->Reference_Number_1,
                                $packageInbound->Dropoff_Contact_Name,
                                $packageInbound->Dropoff_Contact_Phone_Number,
                                $packageInbound->Dropoff_Address_Line_1,
                                $packageInbound->Dropoff_City,
                                $packageInbound->Dropoff_Province,
                                $packageInbound->Dropoff_Postal_Code,
                                $packageInbound->Weight,
                                $packageInbound->Route
                            );

            fputcsv($file, $lineData, $delimiter);
        }

        fseek($file, 0);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '";');

        fpassthru($file);
    }

    public function Insert(Request $request)
    {
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

        $packageInbound   = PackageInbound::find($request->get('Reference_Number_1'));
        $packageWarehouse = PackageWarehouse::find($request->get('Reference_Number_1'));

        if($packageInbound)
        {
            return ['stateAction' => 'validatedInbound', 'packageInbound' => $packageInbound];
        }
        elseif($packageWarehouse)
        {
            return ['stateAction' => 'validatedWarehouse', 'packageWarehouse' => $packageWarehouse];
        }

        $packageLost = PackageLost::find($request->get('Reference_Number_1'));

        if($packageLost)
        {
            return ['stateAction' => 'validatedLost'];
        }

        $packageManifest = PackageManifest::with('blockeds')->find($request->get('Reference_Number_1'));

        if($packageManifest)
        {
            if($packageManifest->filter || count($packageManifest->blockeds) > 0)
            {
                return ['stateAction' => 'validatedFilterPackage', 'packageManifest' => $packageManifest];
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

                $packageInbound = new PackageInbound();

                $packageInbound->Reference_Number_1           = $packageManifest->Reference_Number_1;
                $packageInbound->idCompany                    = $packageManifest->idCompany;
                $packageInbound->company                      = $packageManifest->company;
                $packageInbound->idStore                      = $packageManifest->idStore;
                $packageInbound->store                        = $packageManifest->store;
                $packageInbound->TRUCK                        = $request->get('TRUCK') ? $request->get('TRUCK') : '';
                $packageInbound->CLIENT                       = $packageManifest->company;
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
                $packageInbound->quantity                     = $packageManifest->quantity;
                $packageInbound->status                       = 'Inbound';

                $packageInbound->save();

                $packageHistory = new PackageHistory();

                $packageHistory->id                           = uniqid();
                $packageHistory->Reference_Number_1           = $packageManifest->Reference_Number_1;
                $packageHistory->idCompany                    = $packageManifest->idCompany;
                $packageHistory->company                      = $packageManifest->company;
                $packageHistory->idStore                      = $packageManifest->idStore;
                $packageHistory->store                        = $packageManifest->store;
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
                $packageHistory->quantity                     = $packageManifest->quantity;
                $packageHistory->status                       = 'Inbound';
                $packageHistory->created_at                   = date('Y-m-d H:i:s');
                $packageHistory->updated_at                   = date('Y-m-d H:i:s');

                $packageHistory->save();

                $packageManifest['latitude']  = $request->get('latitude');
                $packageManifest['longitude'] = $request->get('longitude');

                //data for INLAND
                $packageController = new PackageController();
                $packageController->SendStatusToInland($packageManifest, 'Inbound', null, date('Y-m-d H:i:s'));
                //end data for inland

                $packageManifest->delete();

                DB::commit();

                return ['stateAction' => true, 'packageInbound' => $packageManifest];
            }
            catch(Exception $e)
            {
                DB::rollback();

                return ['stateAction' => true];
            }
        }
        else
        {
            $packageReturnCompany = PackageReturnCompany::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();

            if($packageReturnCompany)
            {
                return ['stateAction' => 'validatedReturnCompany', 'packageInbound' => $packageReturnCompany];
            }

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
                                    $packageInbound->idCompany                    = $packageManifest->idCompany;
                                    $packageInbound->company                      = $packageManifest->company;
                                    $packageInbound->idStore                      = $packageManifest->idStore;
                                    $packageInbound->store                        = $packageManifest->store;
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
                                    $packageInbound->quantity                     = $packageManifest->quantity;
                                    $packageInbound->status                       = 'Inbound';

                                    $packageInbound->save();

                                    $packageHistory = new PackageHistory();

                                    $packageHistory->id                           = uniqid();
                                    $packageHistory->Reference_Number_1           = $packageManifest->Reference_Number_1;
                                    $packageHistory->idCompany                    = $packageManifest->idCompany;
                                    $packageHistory->company                      = $packageManifest->company;
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
                                    $packageHistory->quantity                     = $packageManifest->quantity;
                                    $packageHistory->status                       = 'Inbound';
                                    $packageHistory->created_at                   = date('Y-m-d H:i:s');
                                    $packageHistory->updated_at                   = date('Y-m-d H:i:s');

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

    public function GenerateBarCode($Reference_Number_1)
    {
        $generador = new BarcodeGeneratorPNG();

        $texto = $Reference_Number_1;
        $tipo  = $generador::TYPE_CODE_128;

        $imagen = $generador->getBarcode($texto, $tipo);

        # Aquí se guarda la imagen
        $nombreArchivo = 'img/barcode/'. $Reference_Number_1 .'.png';

        # Escribir los datos
        $bytesEscritos = file_put_contents($nombreArchivo, $imagen);

        # Comprobar si todo fue bien
        if($bytesEscritos !== false)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function PdfLabel($Reference_Number_1)
    {
        $packageInbound = PackageInbound::find($Reference_Number_1);

        $pdf = \PDF::loadView('pdf.label', compact('packageInbound'));

        $pdf->setPaper('A5', 'portrait');

        return $pdf->stream();
    }
}
