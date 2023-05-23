<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{ AuxDispatchUser, Comment, Configuration, Driver, PackageHistory, PackageBlocked, PackageDispatch,PackageFailed,  PackageInbound, PackageLost, PackageManifest, PackageNotExists, PackageNeedMoreInformation, PackagePreDispatch, PackageReturn, PackageReturnCompany, PackageWarehouse, TeamRoute, User };

use Auth;
use DB;
use Log;
use Session;
use DateTime;
 
class PackageMassQueryController extends Controller
{
    public function Index()
    {
        return view('report.indexmassquery');
    }

    public function Import(Request $request)
    {
        $file = $request->file('file');

        $file->move(public_path() .'/file-import', 'wassquery.csv');

        $handle = fopen(public_path('file-import/wassquery.csv'), "r");

        $idsAll = [];

        while (($raw_string = fgets($handle)) !== false)
        {
            $row = str_getcsv($raw_string);

            array_push($idsAll, $row[0]);
        }

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
                                            ->where('status', 'Inbound')
                                            ->get();

        $idsExists             = [];
        $packageHistoryListNew = [];

        foreach($packageHistoryList as $packageHistory)
        {
            if(in_array($packageHistory->Reference_Number_1, $idsExists) === false)
            {
                $initDate = date('Y-m-d', strtotime($packageHistory->created_at));
                $endDate  = date('Y-m-d');

                $status   = $this->GetStatus($packageHistory->Reference_Number_1);

                $package = [

                    "created_at" => $packageHistory->created_at,
                    "company" => $packageHistory->company,
                    "company" => $packageHistory->company,
                    "Reference_Number_1" => $packageHistory->Reference_Number_1,
                    "status" => $status['status'],
                    "statusDate" => $status['statusDate'],
                    "statusDescription" => $status['statusDescription'],
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

            'listAll' => $packageHistoryListNew,
        ];
    }

    public function GetStatus($Reference_Number_1)
    {
        Log::info('Reference_Number_1: '. $Reference_Number_1);

        $package = PackageManifest::find($Reference_Number_1);

        $package = $package != null ? $package : PackageInbound::find($Reference_Number_1);
        $package = $package != null ? $package : PackageWarehouse::find($Reference_Number_1);
        $package = $package != null ? $package : PackageDispatch::find($Reference_Number_1);
        $package = $package != null ? $package : PackageFailed::find($Reference_Number_1);
        $package = $package != null ? $package : PackagePreDispatch::find($Reference_Number_1);
        $package = $package != null ? $package : PackageLost::find($Reference_Number_1);
        $package = $package != null ? $package : PackageReturnCompany::find($Reference_Number_1);
        $package = $package != null ? $package : PackageNeedMoreInformation::find($Reference_Number_1);

        $packageLast = PackageHistory::where('Reference_Number_1', $Reference_Number_1);

        if($package)
        {
            $packageLast = $packageLast->where('status', $package->status);
        }

        $packageLast = $packageLast->get()->last();

        return [
            'status' => ($package ? $package->status : ''),
            'statusDate' => $packageLast->created_at,
            'statusDescription' => ($packageLast->Description != null || $packageLast->Description != '' ? $packageLast->Description : $packageLast->Description_Onfleet)
        ];
    }
}