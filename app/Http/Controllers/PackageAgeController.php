<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{ Assigned, AuxDispatchUser, Comment, Configuration, Driver, PackageHistory, PackageBlocked, PackageDispatch, PackageInbound, PackageManifest, PackageNotExists, PackageReturn, PackageReturnCompany, PackageWarehouse, TeamRoute, User };

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

    public function List($states, $routes)
    {
        $data = $this->GetData($states, $routes, 'paginate');
        
        $packageHistoryList    = $data['packageHistoryList'];
        $packageHistoryListNew = $data['listAll'];

        return [

            'packageHistoryList' => $packageHistoryList,
            'listAll' => $packageHistoryListNew,
        ];
    }

    public function Export($states, $routes)
    {
        $data           = $this->GetData($states, $routes, 'all');
        $packageListOld = $data['listAll'];

        $delimiter = ",";
        $filename  = "PACKAGE - AGE " . date('Y-m-d H:i:s') . ".csv";

        //create a file pointer
        $file = fopen('php://memory', 'w');

        //set column headers
        $fields = array('DATE', 'LATE DAYS', 'PACKAGE ID', 'ACTUAL STATUS', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'ROUTE');

        fputcsv($file, $fields, $delimiter);

        foreach($packageListOld as $package)
        {
            $lineData = array(
                                date('m-d-Y', strtotime($package['created_at'])),
                                $package['lateDays'],
                                $package['Reference_Number_1'],
                                $package['status'],
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

    public function GetData($states, $routes, $typeData)
    {
        $idsPackageInbound   = PackageInbound::get('Reference_Number_1');
        $idsPackageWarehouse = PackageWarehouse::get('Reference_Number_1');
        $idsPackageDispatch  = PackageDispatch::where('status', '!=', 'Delivery')->get('Reference_Number_1');

        $idsAll = $idsPackageInbound->merge($idsPackageWarehouse)->merge($idsPackageDispatch);

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
                                               
        if(count($states) != 0)
        {
            $packageHistoryList = $packageHistoryList->whereIn('Dropoff_Province', $states);
        }

        if(count($routes) != 0)
        {
            $packageHistoryList = $packageHistoryList->whereIn('Route', $routes);
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

                $lateDays = $this->CalculateDaysLate($initDate, $endDate);
                $status   = $this->GetStatus($packageHistory->Reference_Number_1);

                $package = [

                    "created_at" => $packageHistory->created_at,
                    "lateDays" => $lateDays,
                    "company" => $packageHistory->company,
                    "Reference_Number_1" => $packageHistory->Reference_Number_1,
                    "status" => $status['status'],
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

        $package = $package != null ? $package : PackageWarehouse::find($Reference_Number_1);

        $package = $package != null ? $package : PackageDispatch::where('status', '!=', 'Delivery')->find($Reference_Number_1);

        return ['status' => $package->status];
    }

    public function CalculateDaysLate($initDate, $endDate)
    {
        $initDate = new DateTime($initDate);
        $endDate  = new DateTime($endDate);

        $lateDays = $initDate->diff($endDate)->days;

        return $lateDays;
    }
}