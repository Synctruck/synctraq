<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Models\{ PackageDispatch, PackageInbound, PackageWarehouse, PackageHistory };

use App\Http\Controllers\{ PackagePriceCompanyTeamController };

use DateTime;
use Log;
use Mail;

class TaskSendAgeOfPackages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:send-age-of-packages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar por correo los packages antiguos';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */

    public function handle()
    {
        if(date('l') == 'Monday' && $nowHour == 8)
        {
            $this->ExportAgeOfPackages();
            $this->SendAgeOfPackages();
        }

        $this->ExportAgeOfPackages();
        $this->SendAgeOfPackages();
    }

    public function ExportAgeOfPackages()
    {
        $data           = $this->GetData();
        $packageListOld = $data['listAll'];

        $delimiter = ",";
        $filename  = "PACKAGE - AGE OF PACKAGES.csv";

        //create a file pointer
        $file   = fopen(public_path($filename), 'w');
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

        rewind($file);
        fclose($file);
    }

    public function GetData()
    {
        $idsPackageInbound   = PackageInbound::get('Reference_Number_1');
        $idsPackageWarehouse = PackageWarehouse::get('Reference_Number_1');
        $idsPackageDispatch  = PackageDispatch::where('status', '!=', 'Delivery')->get('Reference_Number_1');

        $idsAll = $idsPackageInbound->merge($idsPackageWarehouse)->merge($idsPackageDispatch);

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
                                            ->orderBy('created_at', 'asc')
                                            ->get();

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
                    "company" => $packageHistory->company,
                    "lateDays" => $lateDays,
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

            'packageHistoryList' => $packageHistoryList,
            'listAll' => $packageHistoryListNew,
        ];
    }

    public function GetStatus($Reference_Number_1)
    {
        $package = PackageInbound::find($Reference_Number_1);

        $package = $package != null ? $package : PackageWarehouse::find($Reference_Number_1);
        $package = $package != null ? $package : PackageDispatch::where('status', '!=', 'Delivery')->find($Reference_Number_1);

        $packageLast = PackageHistory::where('Reference_Number_1', $Reference_Number_1)->get()->last();

        if($package)
        {
            return [
                'status' => $package->status,
                'statusDate' => $packageLast->created_at,
                'statusDescription' => $packageLast->Description
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

    public function SendAgeOfPackages()
    {
        $filename  = "PACKAGE - AGE OF PACKAGES.csv";
        $files     = [public_path($filename)];
        $date      = date('Y-m-d H:i:s');
        $data      = ['date' => $date];

        Mail::send('mail.ageofpackages', ['data' => $data ], function($message) use($date, $files) {

            $message->to('wilcm123@gmail.com', 'AGE OF PACKAGES')
            ->subject('AGE OF PACKAGE ('. $date . ')');

            foreach ($files as $file)
            {
                $message->attach($file);
            }
        });
    }
}