<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{Company, Configuration, PackageDelivery, PackageDispatch, PackageHistory, PackageInbound, PackageManifest, PackageNotExists, PackageReturn, PackageReturnCompany, TeamRoute, User};

use Illuminate\Support\Facades\Validator;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PhpOffice\PhpOfficePhpSpreadsheetSpreadsheet;
use PhpOffice\PhpOfficePhpSpreadsheetReaderCsv;
use PhpOffice\PhpOfficePhpSpreadsheetReaderXlsx;

use DB;
use Session;

class PackageReturnCompanyController extends Controller
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
        return view('report.indexreturncompany');
    }

    public function List($dateInit, $dateEnd, $route, $state)
    {
        $routes   = explode(',', $route);
        $states   = explode(',', $state);
        $roleUser = Session::get('user')->role->name;

        $packageReturnCompanyList = PackageReturnCompany::whereBetween('created_at', [$dateInit, $dateEnd]);

        if($route != 'all')
        {
            $packageReturnCompanyList = $packageReturnCompanyList->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $packageReturnCompanyList = $packageReturnCompanyList->whereIn('Dropoff_Province', $states);
        }

        $packageReturnCompanyList = $packageReturnCompanyList->paginate(50);
        
        $quantityReturn = $packageReturnCompanyList->total();

        $listState = PackageReturnCompany::select('Dropoff_Province')
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        return ['packageReturnCompanyList' => $packageReturnCompanyList, 'listState' => $listState, 'quantityReturn' => $quantityReturn, 'roleUser' => $roleUser];
    }

    public function Insert(Request $request)
    {
        $packageInbound = PackageInbound::find($request->get('Reference_Number_1'));
        
        if($packageInbound == null)
        {
            $packageInbound = PackageDispatch::find($request->get('Reference_Number_1'));
        }

        if($packageInbound)
        {
            try
            {
                DB::beginTransaction();

                $packageHistory = PackageHistory::where('Reference_Number_1', $packageInbound->Reference_Number_1)
                                                ->where('status', 'On hold')
                                                ->first();

                $company = Company::find($packageHistory->idCompany);

                $packageReturnCompany = new PackageReturnCompany();

                $packageReturnCompany->idCompany                    = $packageInbound->idCompany;
                $packageReturnCompany->company                      = $packageInbound->company;
                $packageReturnCompany->Reference_Number_1           = $packageInbound->Reference_Number_1;
                $packageReturnCompany->Reference_Number_2           = $packageInbound->Reference_Number_2;
                $packageReturnCompany->Reference_Number_3           = $packageInbound->Reference_Number_3;
                $packageReturnCompany->Ready_At                     = $packageInbound->Ready_At;
                $packageReturnCompany->Del_Date                     = $packageInbound->Del_Date;
                $packageReturnCompany->Del_no_earlier_than          = $packageInbound->Del_no_earlier_than;
                $packageReturnCompany->Del_no_later_than            = $packageInbound->Del_no_later_than;
                $packageReturnCompany->Pickup_Contact_Name          = $packageInbound->Pickup_Contact_Name;
                $packageReturnCompany->Pickup_Company               = $packageInbound->Pickup_Company;
                $packageReturnCompany->Pickup_Contact_Phone_Number  = $packageInbound->Pickup_Contact_Phone_Number;
                $packageReturnCompany->Pickup_Contact_Email         = $packageInbound->Pickup_Contact_Email;
                $packageReturnCompany->Pickup_Address_Line_1        = $packageInbound->Pickup_Address_Line_1;
                $packageReturnCompany->Pickup_Address_Line_2        = $packageInbound->Pickup_Address_Line_2;
                $packageReturnCompany->Pickup_City                  = $packageInbound->Pickup_City;
                $packageReturnCompany->Pickup_Province              = $packageInbound->Pickup_Province;
                $packageReturnCompany->Pickup_Postal_Code           = $packageInbound->Pickup_Postal_Code;
                $packageReturnCompany->Dropoff_Contact_Name         = $packageInbound->Dropoff_Contact_Name;
                $packageReturnCompany->Dropoff_Company              = $packageInbound->Dropoff_Company;
                $packageReturnCompany->Dropoff_Contact_Phone_Number = $packageInbound->Dropoff_Contact_Phone_Number;
                $packageReturnCompany->Dropoff_Contact_Email        = $packageInbound->Dropoff_Contact_Email;
                $packageReturnCompany->Dropoff_Address_Line_1       = $packageInbound->Dropoff_Address_Line_1;
                $packageReturnCompany->Dropoff_Address_Line_2       = $packageInbound->Dropoff_Address_Line_2;
                $packageReturnCompany->Dropoff_City                 = $packageInbound->Dropoff_City;
                $packageReturnCompany->Dropoff_Province             = $packageInbound->Dropoff_Province;
                $packageReturnCompany->Dropoff_Postal_Code          = $packageInbound->Dropoff_Postal_Code;
                $packageReturnCompany->Service_Level                = $packageInbound->Service_Level;
                $packageReturnCompany->Carrier_Name                 = $packageInbound->Carrier_Name;
                $packageReturnCompany->Vehicle_Type_Id              = $packageInbound->Vehicle_Type_Id;
                $packageReturnCompany->Notes                        = $packageInbound->Notes;
                $packageReturnCompany->Number_Of_Pieces             = $packageInbound->Number_Of_Pieces;
                $packageReturnCompany->Weight                       = $request->get('Description_Return');
                $packageReturnCompany->Route                        = $packageInbound->Route;
                $packageReturnCompany->Name                         = $packageInbound->Name;
                $packageReturnCompany->idUser                       = Session::get('user')->id;
                $packageReturnCompany->Date_Return                  = date('Y-m-d H:i:s'); 
                $packageReturnCompany->Description_Return           = $request->get('Description_Return');
                $packageReturnCompany->client                       = $request->get('client');
                $packageReturnCompany->measures                     = $request->get('measures');
                $packageReturnCompany->status                       = 'ReturnCompany';

                $packageReturnCompany->save();
                
                //regsister history

                $packageHistory = new PackageHistory();

                $packageHistory->id                           = uniqid();
                $packageHistory->Reference_Number_1           = $packageInbound->Reference_Number_1;
                $packageHistory->idCompany                    = $packageInbound->idCompany;
                $packageHistory->company                      = $packageInbound->company;
                $packageHistory->Reference_Number_2           = $packageInbound->Reference_Number_2;
                $packageHistory->Reference_Number_3           = $packageInbound->Reference_Number_3;
                $packageHistory->Ready_At                     = $packageInbound->Ready_At;
                $packageHistory->Del_Date                     = $packageInbound->Del_Date;
                $packageHistory->Del_no_earlier_than          = $packageInbound->Del_no_earlier_than;
                $packageHistory->Del_no_later_than            = $packageInbound->Del_no_later_than;
                $packageHistory->Pickup_Contact_Name          = $packageInbound->Pickup_Contact_Name;
                $packageHistory->Pickup_Company               = $packageInbound->Pickup_Company;
                $packageHistory->Pickup_Contact_Phone_Number  = $packageInbound->Pickup_Contact_Phone_Number;
                $packageHistory->Pickup_Contact_Email         = $packageInbound->Pickup_Contact_Email;
                $packageHistory->Pickup_Address_Line_1        = $packageInbound->Pickup_Address_Line_1;
                $packageHistory->Pickup_Address_Line_2        = $packageInbound->Pickup_Address_Line_2;
                $packageHistory->Pickup_City                  = $packageInbound->Pickup_City;
                $packageHistory->Pickup_Province              = $packageInbound->Pickup_Province;
                $packageHistory->Pickup_Postal_Code           = $packageInbound->Pickup_Postal_Code;
                $packageHistory->Dropoff_Contact_Name         = $packageInbound->Dropoff_Contact_Name;
                $packageHistory->Dropoff_Company              = $packageInbound->Dropoff_Company;
                $packageHistory->Dropoff_Contact_Phone_Number = $packageInbound->Dropoff_Contact_Phone_Number;
                $packageHistory->Dropoff_Contact_Email        = $packageInbound->Dropoff_Contact_Email;
                $packageHistory->Dropoff_Address_Line_1       = $packageInbound->Dropoff_Address_Line_1;
                $packageHistory->Dropoff_Address_Line_2       = $packageInbound->Dropoff_Address_Line_2;
                $packageHistory->Dropoff_City                 = $packageInbound->Dropoff_City;
                $packageHistory->Dropoff_Province             = $packageInbound->Dropoff_Province;
                $packageHistory->Dropoff_Postal_Code          = $packageInbound->Dropoff_Postal_Code;
                $packageHistory->Service_Level                = $packageInbound->Service_Level;
                $packageHistory->Carrier_Name                 = $packageInbound->Carrier_Name;
                $packageHistory->Vehicle_Type_Id              = $packageInbound->Vehicle_Type_Id;
                $packageHistory->Notes                        = $packageInbound->Notes;
                $packageHistory->Number_Of_Pieces             = $packageInbound->Number_Of_Pieces;
                $packageHistory->Weight                       = $packageInbound->Weight;
                $packageHistory->Route                        = $packageInbound->Route;
                $packageHistory->Name                         = $packageInbound->Name;
                $packageHistory->idUser                       = Session::get('user')->id;
                $packageHistory->idUserInbound                = Session::get('user')->id;
                $packageHistory->Date_Inbound                 = date('Y-m-d H:s:i');
                $packageHistory->Description                  = 'Return Company - for: user ('. Session::get('user')->email .')';
                $packageHistory->Description_Return           = $request->get('Description_Return');
                $packageHistory->status                       = 'ReturnCompany';

                $packageHistory->save();
                
                $packageInbound->delete();

                DB::commit();

                return ['stateAction' => true];
            }
            catch(Exception $e)
            {
                DB::rollback();

                return ['stateAction' => false];
            }
        }

        return ['stateAction' => 'notExists'];
    }

    public function Export($dateInit, $dateEnd, $route, $state)
    {
        $delimiter = ",";
        $filename = "Report Return Company " . date('Y-m-d H:i:s') . ".csv";
        
        //create a file pointer
        $file = fopen('php://memory', 'w');
        
        //set column headers
        $fields = array('FECHA', 'HORA', 'COMPANY', 'PACKAGE ID', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'ROUTE', 'Description Return', 'Client', 'Weight', 'Measures');

        fputcsv($file, $fields, $delimiter);
        
        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);

        $listPackageReturnCompany = PackageReturnCompany::with('driver')
                                                        ->whereBetween('created_at', [$dateInit, $dateEnd]);
        if($route != 'all') 
        {
            $listPackageReturnCompany = $listPackageReturnCompany->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $listPackageReturnCompany = $listPackageReturnCompany->whereIn('Dropoff_Province', $states);
        }

        $listPackageReturnCompany = $listPackageReturnCompany->get();
        
        foreach($listPackageReturnCompany as $packageReturnCompany)
        {
            $lineData = array(
                                date('m-d-Y', strtotime($packageReturnCompany->created_at)),
                                date('H:i:s', strtotime($packageReturnCompany->created_at)),
                                $packageReturnCompany->company,
                                $packageReturnCompany->Reference_Number_1,
                                $packageReturnCompany->Dropoff_Contact_Name,
                                $packageReturnCompany->Dropoff_Contact_Phone_Number,
                                $packageReturnCompany->Dropoff_Address_Line_1,
                                $packageReturnCompany->Dropoff_City,
                                $packageReturnCompany->Dropoff_Province,
                                $packageReturnCompany->Dropoff_Postal_Code,
                                $packageReturnCompany->Route,
                                $packageReturnCompany->Description_Return,
                                $packageReturnCompany->client,
                                $packageReturnCompany->Weight,
                                $packageReturnCompany->measures,
                            );

            fputcsv($file, $lineData, $delimiter);
        }
        
        fseek($file, 0);
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '";');
        
        fpassthru($file);
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
                            $package->idUserInbound = Session::get('user')->id;
                            $package->Date_Inbound  = date('Y-m-d H:i:s');
                            $package->status        = 'Inbound';

                            $package->save();

                            $packageHistory = new PackageHistory();

                            $packageHistory->id          = uniqid();
                            $packageHistory->idPackage   = $row[0];
                            $packageHistory->description = 'Validación Inbound';
                            $packageHistory->user        = Session::get('user')->email;
                            $packageHistory->status      = 'Inbound';

                            $packageHistory->save();
                        }
                    }
                    else
                    {
                        $packageNotExists = new PackageNotExists();

                        $packageNotExists->Reference_Number_1 = $row[0];
                        $packageNotExists->idUser             = Session::get('user')->id;
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

    
}