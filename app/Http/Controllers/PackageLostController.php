<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\CustomEmail;

use App\Models\{ Company, CompanyStatus, Configuration, DimFactorCompany, PackageBlocked, PackageDispatch, PackageFailed, PackageHistory, PackageInbound, PackageLost,  PackageManifest, PackageNotExists, PackagePreDispatch, PackageWarehouse, PackagePriceCompanyTeam, PackageReturnCompany, States };

use App\Service\ServicePackageLost;

use Illuminate\Support\Facades\Validator;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

use Barryvdh\DomPDF\Facade\PDF;

use Picqer\Barcode\BarcodeGeneratorPNG;

use App\Http\Controllers\{ CompanyController, RangePriceCompanyController };
use App\Http\Controllers\Api\PackageController;

use DB;
use Log;
use Session;

class PackageLostController extends Controller
{
    private $servicePackageLost;

    public function __construct()
    {
        $this->servicePackageLost = new ServicePackageLost();
    }

    public function Index()
    {
        return view('package.lost');
    }

    public function List($idCompany, $dateStart,$dateEnd, $route, $state)
    {
        $packageListInbound    = $this->getDataLost($idCompany, $dateStart,$dateEnd, $route, $state);
        $quantityInbound       = $packageListInbound->total();

        $packageHistoryListNew = [];

        foreach($packageListInbound as $packageLost)
        {
            $packageHistory = PackageHistory::where('Reference_Number_1', $packageLost->Reference_Number_1)
                                            ->orderBy('actualDate', 'desc')
                                            ->get();

            $package = [

                "created_at" => $packageLost->created_at,
                "company" => $packageLost->company,
                "Reference_Number_1" => $packageLost->Reference_Number_1,
                "Dropoff_Contact_Name" => $packageLost->Dropoff_Contact_Name,
                "Dropoff_Contact_Phone_Number" => $packageLost->Dropoff_Contact_Phone_Number,
                "Dropoff_Address_Line_1" => $packageLost->Dropoff_Address_Line_1,
                "Dropoff_City" => $packageLost->Dropoff_City,
                "Dropoff_Province" => $packageLost->Dropoff_Province,
                "Dropoff_Postal_Code" => $packageLost->Dropoff_Postal_Code,
                "Route" => $packageLost->Route,
                "Weight" => $packageLost->Weight,
                "comment" => $packageLost->comment,
                "Last_Status" => (count($packageHistory) > 1 ? $packageHistory[1]->status : $packageHistory[0]->status),
                "Last_Description" => (count($packageHistory) > 1 ? $packageHistory[1]->Description : $packageHistory[0]->Description)
            ];

            array_push($packageHistoryListNew, $package);
        }

        return ['packageList' => $packageHistoryListNew, 'quantityInbound' => $quantityInbound];
    }

    private function getDataLost($idCompany, $dateStart,$dateEnd, $route, $state,$type='list'){

        $dateStart = $dateStart .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);

        $packageListLost = PackageLost::whereBetween('created_at', [$dateStart, $dateEnd]);

        if($route != 'all')
        {
            $packageListLost = $packageListLost->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $packageListLost = $packageListLost->whereIn('Dropoff_Province', $states);
        }

        if($idCompany)
        {
            $packageListLost = $packageListLost->where('idCompany', $idCompany);
        }

        if($type =='list')
        {
            $packageListLost = $packageListLost->orderBy('created_at', 'desc')
                                                ->select('company', 'Reference_Number_1', 'Dropoff_Contact_Name', 'Dropoff_Contact_Phone_Number', 'Dropoff_Address_Line_1', 'Dropoff_City', 'Dropoff_Province', 'Dropoff_Postal_Code', 'Weight', 'Route', 'comment', 'created_at')
                                                ->paginate(50);
        }
        else{
            
            $packageListLost = $packageListLost->orderBy('created_at', 'desc')->get(); 
        }

        return $packageListLost;
    }

    public function Export(Request $request,$idCompany, $dateStart,$dateEnd, $route, $state, $typeExport)
    {
        $delimiter = ",";
        $filename  = $typeExport == 'download' ? "PACKAGES - LOST " . date('Y-m-d H:i:s') . ".csv" : Auth::user()->id ."- PACKAGES - LOST.csv";
        $file      = $typeExport == 'download' ? fopen('php://memory', 'w') : fopen(public_path($filename), 'w');

        //set column headers
        $fields = array('DATE', 'HOUR', 'COMPANY', 'PACKAGE ID', 'LAST STATUS', 'LAST DESCRIPTION', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'WEIGHT', 'ROUTE');

        fputcsv($file, $fields, $delimiter);

        $packageListInbound = $this->getDataLost($idCompany, $dateStart,$dateEnd, $route, $state,$type='export');

        foreach($packageListInbound as $packageLost)
        {
            $packageHistory = PackageHistory::where('Reference_Number_1', $packageLost->Reference_Number_1)
                                            ->orderBy('created_at', 'desc')
                                            ->get();

            $lineData = array(
                                date('m-d-Y', strtotime($packageLost->created_at)),
                                date('H:i:s', strtotime($packageLost->created_at)),
                                $packageLost->company,
                                $packageLost->Reference_Number_1,
                                (count($packageHistory) > 1 ? $packageHistory[1]->status : $packageHistory[0]->status),
                                (count($packageHistory) > 1 ? $packageHistory[1]->Description : $packageHistory[0]->Description),
                                $packageLost->Dropoff_Contact_Name,
                                $packageLost->Dropoff_Contact_Phone_Number,
                                $packageLost->Dropoff_Address_Line_1,
                                $packageLost->Dropoff_City,
                                $packageLost->Dropoff_Province,
                                $packageLost->Dropoff_Postal_Code,
                                $packageLost->Weight,
                                $packageLost->Route
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

            SendGeneralExport('Packages Lost', $filename);

            return ['stateAction' => true];
        }
    }

    public function Insert(Request $request)
    {
        $packageBlocked = PackageBlocked::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();

        if($packageBlocked)
        {
            return ['stateAction' => 'validatedFilterPackage', 'packageBlocked' => $packageBlocked, 'packageManifest' => null];
        }

        $packageInbound = PackageManifest::find($request->get('Reference_Number_1'));

        if($packageInbound == null)
        {
            $packageInbound = PackageInbound::find($request->get('Reference_Number_1'));
        }

        if($packageInbound == null)
        {
            $packageInbound = PackageWarehouse::find($request->get('Reference_Number_1'));
        }

        if($packageInbound == null)
        {
            $packageInbound = PackagePreDispatch::find($request->get('Reference_Number_1'));

            if($packageInbound)
            {
                return ['stateAction' => 'validatedPreDispatch'];
            }
        }

        if($packageInbound == null)
        {
            $packageInbound = PackageDispatch::find($request->get('Reference_Number_1'));
        }

        if($packageInbound == null)
        {
            $packageInbound = PackageFailed::find($request->get('Reference_Number_1'));
        }

        if($packageInbound == null)
        {
            $packageInbound = PackageReturnCompany::find($request->get('Reference_Number_1'));

            if($packageInbound)
            {
                return ['stateAction' => 'validatedReturnCompany'];
            }
        }
        
        if($packageInbound == null)
        {
            $packageInbound = PackageLost::find($request->get('Reference_Number_1'));

            if($packageInbound)
            {
                return ['stateAction' => 'validatedLost'];
            }
        }

        if($packageInbound)
        {
            try
            {
                DB::beginTransaction();

                $packageLost = new PackageLost();

                $packageLost->Reference_Number_1           = $packageInbound->Reference_Number_1;
                $packageLost->idCompany                    = $packageInbound->idCompany;
                $packageLost->company                      = $packageInbound->company;
                $packageLost->Dropoff_Contact_Name         = $packageInbound->Dropoff_Contact_Name;
                $packageLost->Dropoff_Company              = $packageInbound->Dropoff_Company;
                $packageLost->Dropoff_Contact_Phone_Number = $packageInbound->Dropoff_Contact_Phone_Number;
                $packageLost->Dropoff_Contact_Email        = $packageInbound->Dropoff_Contact_Email;
                $packageLost->Dropoff_Address_Line_1       = $packageInbound->Dropoff_Address_Line_1;
                $packageLost->Dropoff_Address_Line_2       = $packageInbound->Dropoff_Address_Line_2;
                $packageLost->Dropoff_City                 = $packageInbound->Dropoff_City;
                $packageLost->Dropoff_Province             = $packageInbound->Dropoff_Province;
                $packageLost->Dropoff_Postal_Code          = $packageInbound->Dropoff_Postal_Code;
                $packageLost->Notes                        = $packageInbound->Notes;
                $packageLost->Route                        = $packageInbound->Route;
                $packageLost->comment                      = $request->get('comment') ? $request->get('comment') : '';
                $packageLost->Weight                       = $packageInbound->Weight;
                $packageLost->idUser                       = Auth::user()->id;
                $packageLost->created_at                   = date('Y-m-d H:i:s');
                $packageLost->updated_at                   = date('Y-m-d H:i:s');
                $packageLost->status                       = 'Lost';

                $packageLost->save();

                //regsister history

                $packageHistory = new PackageHistory();

                $packageHistory->id                           = uniqid();
                $packageHistory->Reference_Number_1           = $packageInbound->Reference_Number_1;
                $packageHistory->idCompany                    = $packageInbound->idCompany;
                $packageHistory->company                      = $packageInbound->company;
                $packageHistory->Dropoff_Contact_Name         = $packageInbound->Dropoff_Contact_Name;
                $packageHistory->Dropoff_Company              = $packageInbound->Dropoff_Company;
                $packageHistory->Dropoff_Contact_Phone_Number = $packageInbound->Dropoff_Contact_Phone_Number;
                $packageHistory->Dropoff_Contact_Email        = $packageInbound->Dropoff_Contact_Email;
                $packageHistory->Dropoff_Address_Line_1       = $packageInbound->Dropoff_Address_Line_1;
                $packageHistory->Dropoff_Address_Line_2       = $packageInbound->Dropoff_Address_Line_2;
                $packageHistory->Dropoff_City                 = $packageInbound->Dropoff_City;
                $packageHistory->Dropoff_Province             = $packageInbound->Dropoff_Province;
                $packageHistory->Dropoff_Postal_Code          = $packageInbound->Dropoff_Postal_Code;
                $packageHistory->Notes                        = $packageInbound->Notes;
                $packageHistory->Weight                       = $packageInbound->Weight;
                $packageHistory->Route                        = $packageInbound->Route;
                $packageHistory->idUser                       = Auth::user()->id;
                $packageHistory->Date_Inbound                 = date('Y-m-d H:s:i');
                $packageHistory->Description                  = $request->get('comment') ? $request->get('comment') : '';
                $packageHistory->status                       = 'Lost';
                $packageHistory->actualDate                   = date('Y-m-d H:i:s');
                $packageHistory->created_at                   = date('Y-m-d H:i:s');
                $packageHistory->updated_at                   = date('Y-m-d H:i:s');
                
                $packageHistory->save();

                $packageController = new PackageController();
                $packageController->SendStatusToInland($packageInbound, 'Lost', null, date('Y-m-d H:i:s'));

                $package = $packageInbound;

                $packageInbound->delete();
                
                DB::commit();
                /*$this->sendCustomEmail($packageInbound->Reference_Number_1);*/
                $this->sendCustomEmail($package->Reference_Number_1);
                if ($package->status == 'Dispatch') {
                    $this->sendCustomEmail($package->Reference_Number_1);
                }
                if ($package->company == 'EIGHTVAPE') {
                    $this->sendEmailCompany();
                }
                
                return ['stateAction' => true, 'packageInbound' => $package];
            }
            catch(Exception $e)
            {
                DB::rollback();

                return ['stateAction' => false];
            }
        }

        return ['stateAction' => 'notExists'];
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

        $file->move(public_path() .'/file-import', 'lost.csv');

        $handle = fopen(public_path('file-import/lost.csv'), "r");

        $lineNumber = 1;

        $countSave = 0;

        try
        {
            DB::beginTransaction();

            while (($raw_string = fgets($handle)) !== false)
            {
                $row = str_getcsv($raw_string);

                if(isset($row[0]))
                {
                    Log::info($row[0]);

                    $packageInbound = PackageManifest::find($row[0]);

                    if($packageInbound == null)
                    {
                        $packageInbound = PackageInbound::find($row[0]);
                    }

                    if($packageInbound == null)
                    {
                        $packageInbound = PackageWarehouse::find($row[0]);
                    }

                    if($packageInbound == null)
                    {
                        $packageInbound = PackageDispatch::find($row[0]);
                    }

                    if($packageInbound == null)
                    {
                        $packageInbound = PackageFailed::find($row[0]);
                    }

                    Log::info($packageInbound);

                    if($packageInbound)
                    {
                        $packageLost = new PackageLost();
                        $packageLost->Reference_Number_1           = $packageInbound->Reference_Number_1;
                        $packageLost->idCompany                    = $packageInbound->idCompany;
                        $packageLost->company                      = $packageInbound->company;
                        $packageLost->Dropoff_Contact_Name         = $packageInbound->Dropoff_Contact_Name;
                        $packageLost->Dropoff_Company              = $packageInbound->Dropoff_Company;
                        $packageLost->Dropoff_Contact_Phone_Number = $packageInbound->Dropoff_Contact_Phone_Number;
                        $packageLost->Dropoff_Contact_Email        = $packageInbound->Dropoff_Contact_Email;
                        $packageLost->Dropoff_Address_Line_1       = $packageInbound->Dropoff_Address_Line_1;
                        $packageLost->Dropoff_Address_Line_2       = $packageInbound->Dropoff_Address_Line_2;
                        $packageLost->Dropoff_City                 = $packageInbound->Dropoff_City;
                        $packageLost->Dropoff_Province             = $packageInbound->Dropoff_Province;
                        $packageLost->Dropoff_Postal_Code          = $packageInbound->Dropoff_Postal_Code;
                        $packageLost->Notes                        = $packageInbound->Notes;
                        $packageLost->Route                        = $packageInbound->Route;
                        $packageLost->Weight                       = $packageInbound->Weight;
                        $packageLost->idUser                       = Auth::user()->id;
                        $packageLost->status                       = 'Lost';
                        $packageLost->created_at                   = date('Y-m-d H:i:s', strtotime($row[1]));
                        $packageLost->updated_at                   = date('Y-m-d H:i:s', strtotime($row[1]));
                        $packageLost->save();

                        $packageHistory = new PackageHistory();
                        $packageHistory->id                           = uniqid();
                        $packageHistory->Reference_Number_1           = $packageInbound->Reference_Number_1;
                        $packageHistory->idCompany                    = $packageInbound->idCompany;
                        $packageHistory->company                      = $packageInbound->company;
                        $packageHistory->Dropoff_Contact_Name         = $packageInbound->Dropoff_Contact_Name;
                        $packageHistory->Dropoff_Company              = $packageInbound->Dropoff_Company;
                        $packageHistory->Dropoff_Contact_Phone_Number = $packageInbound->Dropoff_Contact_Phone_Number;
                        $packageHistory->Dropoff_Contact_Email        = $packageInbound->Dropoff_Contact_Email;
                        $packageHistory->Dropoff_Address_Line_1       = $packageInbound->Dropoff_Address_Line_1;
                        $packageHistory->Dropoff_Address_Line_2       = $packageInbound->Dropoff_Address_Line_2;
                        $packageHistory->Dropoff_City                 = $packageInbound->Dropoff_City;
                        $packageHistory->Dropoff_Province             = $packageInbound->Dropoff_Province;
                        $packageHistory->Dropoff_Postal_Code          = $packageInbound->Dropoff_Postal_Code;
                        $packageHistory->Notes                        = $packageInbound->Notes;
                        $packageHistory->Weight                       = $packageInbound->Weight;
                        $packageHistory->Route                        = $packageInbound->Route;
                        $packageHistory->idUser                       = Auth::user()->id;
                        $packageHistory->Date_Inbound                 = date('Y-m-d H:s:i');
                        $packageHistory->Description                  = 'For: user ('. Auth::user()->email .')';
                        $packageHistory->status                       = 'Lost';
                        $packageHistory->actualDate                   = date('Y-m-d H:i:s');
                        $packageHistory->created_at                   = date('Y-m-d H:i:s', strtotime($row[1]));
                        $packageHistory->updated_at                   = date('Y-m-d H:i:s', strtotime($row[1]));
                        $packageHistory->save();

                        $packageController = new PackageController();
                        $packageController->SendStatusToInland($packageInbound, 'Lost', null, date('Y-m-d H:i:s'));

                        $package = $packageInbound;

                        $packageInbound->delete();
                    }
                }
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

    public function MoveToWarehouse($Reference_Number_1)
    {
        $servicePackageLost = new ServicePackageLost();

        return $servicePackageLost->MoveToWarehouse($Reference_Number_1);
    }

    public function sendCustomEmail($Reference_Number_1)
    {
            $package = PackageDispatch::where('Reference_Number_1', $Reference_Number_1)->first();
            $teamAssociatedWithPackage = $package->team;

        if ($teamAssociatedWithPackage) {
            $teamEmail = $teamAssociatedWithPackage->email;
        } else {
            // Manejar el caso en el que no se encuentre un usuario asociado.
             $teamEmail = null; // O cualquier otro valor por defecto.
        }   
            if ($teamEmail) {
                $message = "Greetings\n\nOur team has been inquiring about the package #$Reference_Number_1, but since there have been no updates on the status of the package, it will be marked as lost, and $50.00 will be deducted from your next payment.\n\nRegards.";
    
                Mail::raw($message, function ($msg){
                    $msg->to('alvarogranillo16@gmail.com')->subject('Package Lost Notification');
                });
            }
        }

    
    }

