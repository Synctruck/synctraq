<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Models\{ Company, CompanyStatus, FileSend, PackageDispatch, PackageHistory };

use Log;

class TaskSendDeliveryExtra extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:delivery-extra';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envío de packages con status delivery - imports';

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
        $date = date('Y-m-d H:i:s');

        $filename = "Report-Extra-" . date('m-d-H-i-s', strtotime($date)) .".csv";
        $contents = public_path($filename);

        $this->ReportStatusHistory($contents);

        Storage::disk('sftp')->putFileAs('tracking_in', $contents, $filename);
    }

    public function ReportStatusHistory($contents)
    {
        $delimiter = ",";
        $file      = fopen($contents, 'w');
        $fields    = array('shipment_id', 'status', 'date', 'hour', 'timezone', 'city_locality', 'state', 'lat', 'lon', 'pod_url');

        fputcsv($file, $fields, $delimiter);
        
        $packageListDelivery = PackageDispatch::where('idCompany', 10)
                                                ->where('send_csv', 1)
                                                ->where('status', 'Delivery')
                                                ->get();

        try
        {
            Log::info('================');
            Log::info('================');
            Log::info('START- SEND DELIVERY EXTRA - AE');

            DB::beginTransaction();

            foreach($packageListDelivery as $packageDelivery)
            {
                $shipment_id  = $packageDelivery->Reference_Number_1;
                $status       = $packageDelivery->status;
                $date         = date('m-d-Y', strtotime($packageDelivery->created_at));
                $hour         = date('H:i:s', strtotime($packageDelivery->created_at));
                $cityLocality = $packageDelivery->Dropoff_City;
                $state        = $packageDelivery->Dropoff_Province;
                $timeZone     = 'America/New_York';
                $lat          = '';
                $lon          = '';
                $podUrl       = '';

                if($packageDelivery->photoUrl != '')
                {
                    if(count(explode(',', $packageDelivery->photoUrl)) > 1)
                    {
                        $podUrl = 'https://d15p8tr8p0vffz.cloudfront.net/'. explode(',', $packageDelivery->photoUrl)[0] .'/800x.png';
                    }
                }

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

                $packageDelivery = PackageDispatch::find($packageDelivery->Reference_Number_1);

                $packageDelivery->send_csv = 2;

                $packageDelivery->save();
            }

            rewind($file);
            fclose($file);

            DB::commit();

            Log::info('END - SEND DELIVERY EXTRA - AE');
            Log::info('================');
            Log::info('================');
        }
        catch(Exception $e)
        {
            DB::rollback();

            Log::info('ROLLBACK - SEND DELIVERY EXTRA - AE');
            Log::info('================');
            Log::info('================');
        }
    }
}