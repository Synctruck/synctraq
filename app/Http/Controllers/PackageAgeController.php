<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{ AuxDispatchUser, Comment, Configuration, Driver, 
                PackageHistory, PackageBlocked, PackageDispatch, PackageFailed, PackageLost, 
                PackageInbound, PackageManifest, PackageNeedMoreInformation, PackageNotExists, 
                PackageReturn, PackageReturnCompany, PackageWarehouse, PackageLmCarrier, TeamRoute, User };

use Illuminate\Support\Facades\Validator;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PhpOffice\PhpOfficePhpSpreadsheetSpreadsheet;
use PhpOffice\PhpOfficePhpSpreadsheetReaderCsv;
use PhpOffice\PhpOfficePhpSpreadsheetReaderXlsx;

use App\Http\Controllers\Api\PackageController;

use DB;
use Illuminate\Support\Facades\Auth;
use Log;
use Session;
use DateTime;
 
class PackageAgeController extends Controller
{
    public function Index()
    {
        return view('package.age');
    }

    public function List($idCompany, $states, $routes, $status)
    {
        $data = $this->GetData($idCompany, $states, $routes, $status, 'paginate');
        
        $packageHistoryList    = $data['packageHistoryList'];
        $packageHistoryListNew = $data['listAll'];

        return [

            'packageHistoryList' => $packageHistoryList,
            'listAll' => $packageHistoryListNew,
        ];
    }

    public function Export($idCompany, $states, $routes, $status)
    {
        $data           = $this->GetData($idCompany, $states, $routes, $status, 'all');
        $packageListOld = $data['listAll'];

        $delimiter = ",";
        $filename  = "PACKAGE - AGE " . date('Y-m-d H:i:s') . ".csv";

        //create a file pointer
        $file = fopen('php://memory', 'w');

        //set column headers
        $fields = array('DATE', 'LATE DAYS', 'COMPANY', 'PACKAGE ID', 'ACTUAL STATUS', 'STATUS DATE', 'STATUS DESCRIPTION', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'ROUTE');

        fputcsv($file, $fields, $delimiter);

        foreach($packageListOld as $package)
        {
            $lineData = array(
                                date('m-d-Y', strtotime($package['created_at'])),
                                $package['lateDays'],
                                $package['company'],
                                $package['Reference_Number_1'],
                                $package['status'],
                                $package['statusDate'],
                                $package['statusDescription'],
                                $package['Dropoff_Contact_Name'],
                                $package['Dropoff_Contact_Phone_Number'],
                                $package['Dropoff_Address_Line_1'],
                                $package['Dropoff_City'],
                                $package['Dropoff_Province'],
                                $package['Dropoff_Postal_Code'],
                                $package['Route']
                            );

            fputcsv($file, $lineData, $delimiter);
        }

        fseek($file, 0);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '";');

        fpassthru($file);
    }

    public function GetData($idCompany, $states, $routes, $status, $typeData) 
    {
        if($status == 'all')
        {
            $idsPackageInbound   = PackageInbound::get('Reference_Number_1');
            $idsPackageWarehouse = PackageWarehouse::get('Reference_Number_1');
            $idsPackageDispatch  = PackageDispatch::where('status', '!=', 'Delivery')->get('Reference_Number_1');
            $idsPackageFailed    = PackageFailed::get('Reference_Number_1');
            $idsPackageNMI       = PackageNeedMoreInformation::get('Reference_Number_1');
            $idsPackageLmCarrier = PackageLmCarrier::where('status', '=', 'LM Carrier')
                             ->whereNotIn('Reference_Number_1', function($query) {
                                 $query->select('Reference_Number_1')
                                       ->from('packagehistory')
                                       ->where('status', '=', 'Delivery');
                             })
                             ->get('Reference_Number_1');

            $idsAll = $idsPackageInbound->merge($idsPackageWarehouse)->merge($idsPackageDispatch)->merge($idsPackageFailed)->merge($idsPackageNMI)->merge($idsPackageLmCarrier);
        }
        else if($status == 'Inbound')
        {
            $idsAll = PackageInbound::get('Reference_Number_1');
        }
        else if($status == 'Warehouse')
        {
            $idsAll = PackageWarehouse::where('status', '=', 'Warehouse')->get('Reference_Number_1');
        }
        else if($status == 'Dispatch')
        {
            $idsAll = PackageDispatch::where('status', '=', 'Dispatch')->get('Reference_Number_1');
        }
        else if($status == 'Delete')
        {
            $idsAll = PackageDispatch::where('status', '=', 'Delete')->get('Reference_Number_1');
        }
        else if($status == 'Failed')
        {
            $idsAll = PackageFailed::get('Reference_Number_1');
        }
        else if($status == 'NMI')
        {
            $idsAll = PackageNeedMoreInformation::get('Reference_Number_1');
        }
        /*else if($status == 'Lost')
        {
            $idsAll = PackageLost::get('Reference_Number_1');
        }*/
        else if($status == 'Middle Mile Scan')
        {
            $idsAll = PackageWarehouse::where('status', '=', 'Middle Mile Scan')->get('Reference_Number_1');
        }
        else if($status == 'LM Carrier')
        {
            $idsAll = PackageLmCarrier::where('status', '=', 'LM Carrier')
                             ->whereNotIn('Reference_Number_1', function($query) {
                                 $query->select('Reference_Number_1')
                                       ->from('PackageHistory')
                                       ->where('status', '=', 'Delivery');
                             })
                             ->get('Reference_Number_1');
        }

        $states = $states == 'all' ? [] : explode(',', $states);
        $routes = $routes == 'all' ? [] : explode(',', $routes);

        $packageHistoryList = PackageHistory::select(

                                                'created_at',
                                                'company',
                                                'Reference_Number_1',
                                                'Dropoff_Contact_Name',
                                                'Dropoff_Contact_Name',
                                                'Dropoff_Contact_Phone_Number',
                                                'Dropoff_Address_Line_1',
                                                'Dropoff_City',
                                                'Dropoff_Province',
                                                'Dropoff_Postal_Code',
                                                'Route'
                                            )
                                            ->whereIn('Reference_Number_1', $idsAll)
                                            ->where('status', 'Inbound');
        
        if($idCompany != 0)
        {
            $packageHistoryList = $packageHistoryList->where('idCompany', $idCompany);
        }

        if(count($states) != 0)
        {
            $packageHistoryList = $packageHistoryList->whereIn('Dropoff_Province', $states);
        }

        if(count($routes) != 0)
        {
            $packageHistoryList = $packageHistoryList->whereIn('Route', $routes);
        }

        if($status != 0)
        {
            $packageHistoryList = $packageHistoryList->where('status', $status);
        }

        if($typeData == 'paginate')
        {
            $packageHistoryList = $packageHistoryList->orderBy('created_at', 'asc')->paginate(50);
        }
        else
        {
            $packageHistoryList = $packageHistoryList->orderBy('created_at', 'asc')->get();
        }

        $idsExists             = [];
        $packageHistoryListNew = [];

        foreach($packageHistoryList as $packageHistory)
        {
            if(in_array($packageHistory->Reference_Number_1, $idsExists) === false)
            {
                $initDate = date('Y-m-d', strtotime($packageHistory->created_at));
                $endDate  = date('Y-m-d');

                $lateDays     = $this->CalculateDaysLate($initDate, $endDate);
                $statusActual = $this->GetStatus($packageHistory->Reference_Number_1);

                $package = [

                    "created_at" => $packageHistory->created_at,
                    "company" => $packageHistory->company,
                    "lateDays" => $lateDays,
                    "company" => $packageHistory->company,
                    "Reference_Number_1" => $packageHistory->Reference_Number_1,
                    "status" => $statusActual['status'],
                    "statusDate" => $statusActual['statusDate'],
                    "statusDescription" => $statusActual['statusDescription'],
                    "Dropoff_Contact_Name" => $packageHistory->Dropoff_Contact_Name,
                    "Dropoff_Contact_Phone_Number" => $packageHistory->Dropoff_Contact_Phone_Number,
                    "Dropoff_Address_Line_1" => $packageHistory->Dropoff_Address_Line_1,
                    "Dropoff_City" => $packageHistory->Dropoff_City,
                    "Dropoff_Province" => $packageHistory->Dropoff_Province,
                    "Dropoff_Postal_Code" => $packageHistory->Dropoff_Postal_Code,
                    "Route" => $packageHistory->Route,
                ];

                array_push($packageHistoryListNew, $package);
                array_push($idsExists, $packageHistory->Reference_Number_1);
            }
        }

        return [

            'packageHistoryList' => $packageHistoryList,
            'listAll' => $packageHistoryListNew,
        ];
    }

    public function GetStatus($Reference_Number_1)
    {
        $package = PackageInbound::find($Reference_Number_1);
        $package = $package != null ? $package : PackageWarehouse::where('status', '=', 'Warehouse')->find($Reference_Number_1);
        $package = $package != null ? $package : PackageWarehouse::where('status', '=', 'Middle Mile Scan')->find($Reference_Number_1);
        $package = $package != null ? $package : PackageDispatch::where('status', '=', 'Dispatch')->find($Reference_Number_1);
        $package = $package != null ? $package : PackageDispatch::where('status', '=', 'Delete')->find($Reference_Number_1);
        $package = $package != null ? $package : PackageFailed::find($Reference_Number_1);
        $package = $package != null ? $package : PackageNeedMoreInformation::find($Reference_Number_1);
        $package = $package != null ? $package : PackageLost::find($Reference_Number_1);
        $package = $package != null ? $package : PackageLmCarrier::where('status', '=', 'LM Carrier')->find($Reference_Number_1);

        $packageLast = PackageHistory::where('Reference_Number_1', $Reference_Number_1)->get()->last();

        if($package)
        {
            $packageLast = PackageHistory::where('Reference_Number_1', $Reference_Number_1)
                                        ->where('status', $package->status)
                                        ->orderBy('created_at', 'desc')
                                        ->first();

            return [
                'status' => $package->status,
                'statusDate' => ($packageLast ? $packageLast->created_at : ''), 
                'statusDescription' => ($packageLast ? $packageLast->Description : ''),
            ];
        }
        else
        {
            return [
                'status' => '',
                'statusDate' => $packageLast->created_at,
                'statusDescription' => $packageLast->Description
            ];
        }
    }

    public function CalculateDaysLate($initDate, $endDate)
    {
        $initDate = new DateTime($initDate);
        $endDate  = new DateTime($endDate);

        $lateDays = $initDate->diff($endDate)->days;

        return $lateDays;
    }
}