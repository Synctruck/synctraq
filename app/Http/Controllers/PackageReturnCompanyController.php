<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{Company, Configuration, PackageBlocked, PackageDelivery, PackageDispatch, PackageHistory, PackageInbound, PackageManifest, PackageNotExists, PackageReturn, PackageReturnCompany, PackageWarehouse, TeamRoute, User};

use Illuminate\Support\Facades\Validator;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PhpOffice\PhpOfficePhpSpreadsheetSpreadsheet;
use PhpOffice\PhpOfficePhpSpreadsheetReaderCsv;
use PhpOffice\PhpOfficePhpSpreadsheetReaderXlsx;

use DB;
use Illuminate\Support\Facades\Auth;
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
        $roleUser = Auth::user()->role->name;


        $packageReturnCompanyList = $this->getDataReturn($dateInit, $dateEnd, $route, $state, 'ReturnCompany');

        $quantityReturn = $packageReturnCompanyList->total();

        $listState = PackageReturnCompany::select('Dropoff_Province')
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        return ['packageReturnCompanyList' => $packageReturnCompanyList, 'listState' => $listState, 'quantityReturn' => $quantityReturn, 'roleUser' => $roleUser];
    }

    private function getDataReturn($dateInit, $dateEnd, $route, $state, $status, $type = 'list')
    {
        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';
        $routes   = explode(',', $route);
        $states   = explode(',', $state);

        $packageReturnCompanyList = PackageReturnCompany::whereBetween('created_at', [$dateInit, $dateEnd]);

        if($route != 'all')
        {
            $packageReturnCompanyList = $packageReturnCompanyList->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $packageReturnCompanyList = $packageReturnCompanyList->whereIn('Dropoff_Province', $states);
        }

        if($status == 'PreRts')
        {
            $packageReturnCompanyList = $packageReturnCompanyList->where('status', 'PreRts');
        }

        if($type=='list')
        {
            $packageReturnCompanyList = $packageReturnCompanyList->paginate(50);
        }
        else
        {
            $packageReturnCompanyList = $packageReturnCompanyList->get();
        }

        return $packageReturnCompanyList;
    }

    public function Insert(Request $request)
    {
        $packageBlocked = PackageBlocked::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();

        if($packageBlocked)
        {
            return ['stateAction' => 'validatedFilterPackage', 'packageBlocked' => $packageBlocked, 'packageManifest' => null];
        }

        $packageInbound = PackageInbound::find($request->get('Reference_Number_1'));

        if($packageInbound == null)
        {
            $packageInbound = PackageWarehouse::find($request->get('Reference_Number_1'));
        }

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
                $packageReturnCompany->Dropoff_Contact_Name         = $packageInbound->Dropoff_Contact_Name;
                $packageReturnCompany->Dropoff_Company              = $packageInbound->Dropoff_Company;
                $packageReturnCompany->Dropoff_Contact_Phone_Number = $packageInbound->Dropoff_Contact_Phone_Number;
                $packageReturnCompany->Dropoff_Contact_Email        = $packageInbound->Dropoff_Contact_Email;
                $packageReturnCompany->Dropoff_Address_Line_1       = $packageInbound->Dropoff_Address_Line_1;
                $packageReturnCompany->Dropoff_Address_Line_2       = $packageInbound->Dropoff_Address_Line_2;
                $packageReturnCompany->Dropoff_City                 = $packageInbound->Dropoff_City;
                $packageReturnCompany->Dropoff_Province             = $packageInbound->Dropoff_Province;
                $packageReturnCompany->Dropoff_Postal_Code          = $packageInbound->Dropoff_Postal_Code;
                $packageReturnCompany->Notes                        = $packageInbound->Notes;
                $packageReturnCompany->Weight                       = $request->get('Description_Return');
                $packageReturnCompany->Route                        = $packageInbound->Route;
                $packageReturnCompany->idUser                       = Auth::user()->id;
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
                $packageHistory->idUserInbound                = Auth::user()->id;
                $packageHistory->Date_Inbound                 = date('Y-m-d H:s:i');
                $packageHistory->Description                  = 'Return Company - for: user ('. Auth::user()->email .')';
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
        $fields = array('DATE', 'HOUR', 'COMPANY', 'PACKAGE ID', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'ROUTE', 'DESCRIPTION RETURN', 'CLIENT', 'WEIGHT', 'MEASURES');

        fputcsv($file, $fields, $delimiter);

        $listPackageReturnCompany = $this->getDataReturn($dateInit, $dateEnd, $route, $state, 'ReturnCompany', $type = 'export');

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

    public function IndexPreRts()
    {
        return view('package.prerts');
    }

    public function ListPreRts($dateInit, $dateEnd, $route, $state)
    {
        $roleUser = Auth::user()->role->name;

        $packageReturnCompanyList = $this->getDataReturn($dateInit, $dateEnd, $route, $state, 'PreRts');

        $quantityReturn = $packageReturnCompanyList->total();

        $listState = PackageReturnCompany::select('Dropoff_Province')
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        return ['packageReturnCompanyList' => $packageReturnCompanyList, 'listState' => $listState, 'quantityReturn' => $quantityReturn, 'roleUser' => $roleUser];
    }

    public function InsertPreRts(Request $request)
    {
        $packageBlocked = PackageBlocked::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();

        if($packageBlocked)
        {
            return ['stateAction' => 'validatedFilterPackage', 'packageBlocked' => $packageBlocked, 'packageManifest' => null];
        }

        $packageInbound = PackageInbound::find($request->get('Reference_Number_1'));

        if($packageInbound == null)
        {
            $packageInbound = PackageWarehouse::find($request->get('Reference_Number_1'));
        }

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
                $packageReturnCompany->Dropoff_Contact_Name         = $packageInbound->Dropoff_Contact_Name;
                $packageReturnCompany->Dropoff_Company              = $packageInbound->Dropoff_Company;
                $packageReturnCompany->Dropoff_Contact_Phone_Number = $packageInbound->Dropoff_Contact_Phone_Number;
                $packageReturnCompany->Dropoff_Contact_Email        = $packageInbound->Dropoff_Contact_Email;
                $packageReturnCompany->Dropoff_Address_Line_1       = $packageInbound->Dropoff_Address_Line_1;
                $packageReturnCompany->Dropoff_Address_Line_2       = $packageInbound->Dropoff_Address_Line_2;
                $packageReturnCompany->Dropoff_City                 = $packageInbound->Dropoff_City;
                $packageReturnCompany->Dropoff_Province             = $packageInbound->Dropoff_Province;
                $packageReturnCompany->Dropoff_Postal_Code          = $packageInbound->Dropoff_Postal_Code;
                $packageReturnCompany->Notes                        = $packageInbound->Notes;
                $packageReturnCompany->Weight                       = $request->get('Weight');
                $packageReturnCompany->Width                        = $request->get('Width');
                $packageReturnCompany->Length                       = $request->get('Length');
                $packageReturnCompany->Height                       = $request->get('Height');
                $packageReturnCompany->Route                        = $packageInbound->Route;
                $packageReturnCompany->idUser                       = Auth::user()->id;
                $packageReturnCompany->Date_Return                  = date('Y-m-d H:i:s');
                $packageReturnCompany->Description_Return           = $request->get('Description_Return');
                $packageReturnCompany->client                       = $request->get('client');
                $packageReturnCompany->status                       = 'PreRts';

                $packageReturnCompany->save();

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
                $packageHistory->idUserInbound                = Auth::user()->id;
                $packageHistory->Date_Inbound                 = date('Y-m-d H:s:i');
                $packageHistory->Description                  = 'Return Company - for: user ('. Auth::user()->email .')';
                $packageHistory->Description_Return           = $request->get('Description_Return');
                $packageHistory->status                       = 'PreRts';
                $packageHistory->created_at                   = date('Y-m-d H:i:s');
                $packageHistory->updated_at                   = date('Y-m-d H:i:s');

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
        else
        {
            $packageReturnCompany = PackageReturnCompany::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();

            if($packageReturnCompany)
            {
                return ['stateAction' => 'validatedReturnCompany', 'packageInbound' => $packageReturnCompany];
            }
        }

        return ['stateAction' => 'notExists'];
    }
}
