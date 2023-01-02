<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{ AuxDispatchUser, Comment, Configuration, Driver, PackageHistory, PackageBlocked, PackageDispatch, PackageFailed, PackageHighPriority, PackageInbound, PackageManifest, PackageNotExists, PackageReturn, PackageReturnCompany, PackageWarehouse, TeamRoute, User };

use Illuminate\Support\Facades\Validator;

use DB;
use Illuminate\Support\Facades\Auth;
use Log;
use Session;
use DateTime;

class PackageHighPriorityController extends Controller
{
    public function Index()
    {
        return view('package.hihgpriority');
    }

    public function List($idCompany, $states, $routes)
    {
        $data = $this->GetData($idCompany, $states, $routes, 'paginate');
        
        $packageHistoryList    = $data['packageHistoryList'];
        $packageHistoryListNew = $data['listAll'];

        return [

            'packageHistoryList' => $packageHistoryList,
            'listAll' => $packageHistoryListNew,
        ];
    }

    public function Export($idCompany, $states, $routes)
    {
        $data           = $this->GetData($idCompany, $states, $routes, 'all');
        $packageListOld = $data['listAll'];

        $delimiter = ",";
        $filename  = "PACKAGE - HIGH - PRIORITY " . date('Y-m-d H:i:s') . ".csv";

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

    public function GetData($idCompany, $states, $routes, $typeData)
    {
        $idsAll = PackageHighPriority::get('Reference_Number_1');

        $states = $states == 'all' ? [] : explode(',', $states);
        $routes = $routes == 'all' ? [] : explode(',', $routes);

        $packageHistoryList = PackageHistory::select(

                                                'created_at',
                                                'company',
                                                'Reference_Number_1',
                                                'internal_comment',
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
                    
                if($status['status'] != 'Delivery' && $status['status'] != 'ReturnCompany' && $status['status'] != 'PreRts')
                {
                    $package = [

                        "created_at" => $packageHistory->created_at,
                        "company" => $packageHistory->company,
                        "lateDays" => $lateDays,
                        "company" => $packageHistory->company,
                        "Reference_Number_1" => $packageHistory->Reference_Number_1,
                        "internal_comment" => $packageHistory->internal_comment,
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
        }

        return [

            'packageHistoryList' => $packageHistoryList,
            'listAll' => $packageHistoryListNew,
        ];
    }

    public function GetStatus($Reference_Number_1)
    {
        $package = PackageManifest::find($Reference_Number_1);

        if($package == null)
        {
            $package = PackageInbound::find($Reference_Number_1);
        }

        if($package == null)
        {
            $package = PackageWarehouse::find($Reference_Number_1);
        }

        if($package == null)
        {
            $package = PackageDispatch::find($Reference_Number_1);

            if($package && $package->status == 'Delivery')
            {
                $packageHighPriority = PackageHighPriority::find($Reference_Number_1);

                if($packageHighPriority)
                    $packageHighPriority->delete();
            }
        }

        if($package == null)
        {
            $package = PackageFailed::find($Reference_Number_1);
        }

        if($package == null)
        {
            $package = PackageReturnCompany::find($Reference_Number_1);

            if($package)
            {
                $packageHighPriority = PackageHighPriority::find($Reference_Number_1);

                if($packageHighPriority)
                    $packageHighPriority->delete();
            }
        }

        if($package)
        {
            return ['status' => $package->status]; 
        }

        return ['status' => ''];
    }

    public function CalculateDaysLate($initDate, $endDate)
    {
        $initDate = new DateTime($initDate);
        $endDate  = new DateTime($endDate);

        $lateDays = $initDate->diff($endDate)->days;

        return $lateDays;
    }
}