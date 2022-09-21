<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Models\{ Company, CompanyStatus, PackageHistory };

class TaskAmericanE extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:task-ae';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualización estados AE send csv';

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
        $company = Company::find(10);

        if($company->startDateCSV == null)
        {
            $company->endDateCSV   = date('Y-m-d H:i:00');
            $company->startDateCSV = date('Y-m-d H:i:01', strtotime('-30 minute', strtotime($company->endDateCSV)));
        }
        else
        {
            $company->startDateCSV = date('Y-m-d H:i:s', strtotime('+ 1 second', strtotime($company->endDateCSV)));
            $company->endDateCSV   = date('Y-m-d H:i:00', strtotime('+30 minute', strtotime($company->endDateCSV)));
        }

        $filename = "Report-" . date('m-d-H-i-s', strtotime($company->startDateCSV)) .'-'. date('m-d-H-i-s', strtotime($company->endDateCSV)) . ".csv";
        $contents = public_path($filename);

        $this->ReportStatusHistory($company->startDateCSV, $company->endDateCSV, $contents);

        Storage::disk('sftp')->putFileAs('tracking_in', $contents, $filename);

        $company->save();
    }

    public function ReportStatusHistory($dateInit, $dateEnd, $contents)
    { 
        $filename  = "Report-" . date('m-d-H-i-s', strtotime($dateInit)) .'-'. date('m-d-H-i-s', strtotime($dateEnd)) . ".csv";
        $delimiter = ",";

        $file   = fopen($contents, 'w');
        $fields = array('shipment_id', 'date', 'hour', 'status');

        fputcsv($file, $fields, $delimiter);
        
        $packageListHisotry = PackageHistory::whereBetween('created_at', [$dateInit, $dateEnd])->get();

        foreach($packageListHisotry as $packageHistory)
        {
            $companyStatus = CompanyStatus::where('idCompany', 10)
                                            ->where('status', $packageHistory->status)
                                            ->first();

            if($companyStatus)
            {
                $lineData = array(
                                $packageHistory->Reference_Number_1,
                                date('m-d-Y', strtotime($packageHistory->created_at)),
                                date('H:i:s', strtotime($packageHistory->created_at)),
                                $companyStatus->statusCodeCompany,
                            );

                fputcsv($file, $lineData, $delimiter);
            }
        }
        
        rewind($file);
        fclose($file);
    }
}
