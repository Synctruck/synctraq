<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Models\{ Company, CompanyStatus, FileSend, PackageDispatch, PackageHistory };

use Log;

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
            $company->startDateCSV = date('Y-m-d H:i:01', strtotime('-60 minute', strtotime($company->endDateCSV)));
        }
        else
        {
            $company->startDateCSV = date('Y-m-d H:i:s', strtotime('+ 1 second', strtotime($company->endDateCSV)));
            $company->endDateCSV   = date('Y-m-d H:i:00', strtotime('+60 minute', strtotime($company->endDateCSV)));
        }

        $filename = "Report-" . date('m-d-H-i-s', strtotime($company->startDateCSV)) .'-'. date('m-d-H-i-s', strtotime($company->endDateCSV)) . ".csv";
        $contents = public_path($filename);

        $this->ReportStatusHistory($company->startDateCSV, $company->endDateCSV, $contents);

        Storage::disk('sftp')->putFileAs('tracking_in', $contents, $filename);

        $company->save();
    }

    public function ReportStatusHistory($dateInit, $dateEnd, $contents)
    {
        Log::info('================');
        Log::info('SEND STATUS - AE');

        $filename  = "Report-" . date('m-d-H-i-s', strtotime($dateInit)) .'-'. date('m-d-H-i-s', strtotime($dateEnd)) . ".csv";
        $delimiter = ",";

        $file   = fopen($contents, 'w');
        $fields = array('shipment_id', 'status', 'date', 'hour', 'timezone', 'city_locality', 'state', 'lat', 'lon', 'pod_url');

        fputcsv($file, $fields, $delimiter);
        
        $packageListHisotry = PackageHistory::whereBetween('created_at', [$dateInit, $dateEnd])
                                                ->where('idCompany', 10)
                                                ->where('status', '!=', 'Manifest')
                                                ->get();

        foreach($packageListHisotry as $packageHistory)
        {
            $companyStatus = CompanyStatus::where('idCompany', 10)
                                            ->where('status', $packageHistory->status)
                                            ->first();

            $shipment_id  = $packageHistory->Reference_Number_1;
            $status       = $packageHistory->status;
            $date         = date('m-d-Y', strtotime($packageHistory->created_at));
            $hour         = date('H:i:s', strtotime($packageHistory->created_at));
            $timeZone     = 'America/New_York';
            $cityLocality = 'Carlstadt';
            $state        = 'NJ';
            $lat          = '';
            $lon          = '';
            $podUrl       = '';

            if($packageHistory->status == 'ReInbound')
            { 
                if($packageHistory->Description_Return == 'NOT DELIVERED ADDRESS NOT FOUND' || $packageHistory->Description_Return == 'NOT DELIVERED DAMAGED' || $packageHistory->Description_Return == 'NOT DELIVERED LOST' || $packageHistory->Description_Return == 'NOT DELIVERED OTHER' || $packageHistory->Description_Return == 'NOT DELIVERED REFUSED')
                {
                    $status = str_replace(' ', '_', $packageHistory->Description_Return);
                }
                else
                {
                    $status = 'MISS_SORT';
                }
            }
            elseif($packageHistory->status == 'Delivery')
            {
                $cityLocality = $packageHistory->Dropoff_City;
                $state        = $packageHistory->Dropoff_Province;

                $packageDelivery = PackageDispatch::where('Reference_Number_1', $packageHistory->Reference_Number_1)->first();

                Log::info('Reference_Number_1:'. $packageHistory->Reference_Number_1);
                Log::info($packageDelivery);
                Log::info('PHOTO URL: '. $packageDelivery->photoUrl);

                if($packageDelivery->photoUrl != '')
                {
                    if(count(explode(',', $packageDelivery->photoUrl)) > 1)
                    {
                        $podUrl = 'https://d15p8tr8p0vffz.cloudfront.net/'. explode(',', $packageDelivery->photoUrl)[0] .'/800x.png';
                    }
                }
            }

            if($packageHistory->status == 'Inbound' || $packageHistory->status == 'Dispatch' || $packageHistory->status == 'ReInbound' || $packageHistory->status == 'Delivery')
            {
                $lineData = array(

                                $shipment_id,
                                strtoupper($status),
                                $date,
                                $hour,
                                $timeZone,
                                $cityLocality,
                                $state,
                                $lat,
                                $lon,
                                $podUrl,
                            );

                fputcsv($file, $lineData, $delimiter);
            }
            
        }
        
        $fileSend = new FileSend();

        $fileSend->id              = uniqid();
        $fileSend->idCompany       = 10;
        $fileSend->fileName        = $filename;
        $fileSend->numberOfRecords = count($packageListHisotry);
        $fileSend->start_date      = $dateInit;
        $fileSend->end_date        = $dateEnd;

        $fileSend->save();

        Log::info('SEND STATUS - AE - END');
        Log::info('================');

        rewind($file);
        fclose($file);
    }
}