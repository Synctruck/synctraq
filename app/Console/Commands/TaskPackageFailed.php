<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Models\{ PackageDispatch, PackagePriceCompanyTeam };

use Log;
use Mail;

class TaskPackageFailed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:send-pre-factura';

    /**
     * The console command description.
     *
     * @var string
     */ 
    protected $description = 'Enviar correo con paquetes para facturar(Pre factura)';

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
        $filename  = 'PRE-FACTURA-'. date('m-d-H-i-s') .'.csv';
        $contents  = public_path($filename);
        $startDate = '2023-02-26 00:00:00';
        $endDate   = '2023-03-04 23:59:59';

        $this->GetReportCharge($startDate, $endDate, $filename, $contents);
    }

    public function GetReportCharge($dateInit, $dateEnd, $filename, $contents)
    {
        Log::info('================');
        Log::info('SEND PRE FACTURA - EMAIL');

        $delimiter = ",";
        $file      = fopen($contents, 'w');
        $fields    = array('DATE', 'HOUR', 'COMPANY', 'TEAM', 'PACKAGE ID', 'WEIGHT', 'LENGTH', 'HEIGHT', 'WIDTH', 'CUIN', 'DIESEL PRICE C', 'DIESEL PRICE T', 'DIM FACTOR C', 'DIM WEIGHT C', 'DIM WEIGHT ROUND C', 'PRICE WEIGHT C', 'PEAKE SEASON PRICE C', 'PRICE BASE C', 'SURCHARGE PERCENTAGE C', 'SURCHAGE PRICE C', 'TOTAL PRICE C', 'DIM FACTOR T', 'DIM WEIGHT T', 'DIM WEIGHT ROUND T', 'PRICE WEIGHT T', 'PEAKE SEASON PRICE C', 'PRICE BASE T', 'SURCHARGE PERCENTAGE T', 'SURCHAGE PRICE T', 'TOTAL PRICE T');

        fputcsv($file, $fields, $delimiter);
        
        $listPackageDelivery = PackageDispatch::whereBetween('created_at', [$dateInit, $dateEnd])
                                                ->where('status', 'Delivery')
                                                ->get();

        foreach($listPackageDelivery as $packageDelivery)
        {
            $packagePriceCompanyTeam = PackagePriceCompanyTeam::where('Reference_Number_1', $packageDelivery->Reference_Number_1)
                                                                ->first();

            if($packagePriceCompanyTeam)
            {
                $team = $packageDelivery->team  ? $packageDelivery->team->name : '';

                $lineData = array(
                                date('m-d-Y', strtotime($packageDelivery->updated_at)),
                                date('H:i:s', strtotime($packageDelivery->updated_at)),
                                $packageDelivery->company,
                                $team,
                                $packageDelivery->Reference_Number_1,
                                $packagePriceCompanyTeam->weight,
                                $packagePriceCompanyTeam->length,
                                $packagePriceCompanyTeam->height,
                                $packagePriceCompanyTeam->width,
                                $packagePriceCompanyTeam->cuIn,
                                $packagePriceCompanyTeam->dieselPriceCompany,
                                $packagePriceCompanyTeam->dieselPriceTeam,
                                $packagePriceCompanyTeam->dimFactorCompany,
                                $packagePriceCompanyTeam->dimWeightCompany,
                                $packagePriceCompanyTeam->dimWeightCompanyRound,
                                $packagePriceCompanyTeam->priceWeightCompany,
                                $packagePriceCompanyTeam->peakeSeasonPriceCompany,
                                $packagePriceCompanyTeam->priceBaseCompany,
                                $packagePriceCompanyTeam->surchargePercentageCompany,
                                $packagePriceCompanyTeam->surchargePriceCompany,
                                $packagePriceCompanyTeam->totalPriceCompany,
                                $packagePriceCompanyTeam->dimFactorTeam,
                                $packagePriceCompanyTeam->dimWeightTeam,
                                $packagePriceCompanyTeam->dimWeightTeamRound,
                                $packagePriceCompanyTeam->priceWeightTeam,
                                $packagePriceCompanyTeam->peakeSeasonPriceTeam,
                                $packagePriceCompanyTeam->priceBaseTeam,
                                $packagePriceCompanyTeam->surchargePercentageTeam,
                                $packagePriceCompanyTeam->surchargePriceTeam,
                                $packagePriceCompanyTeam->totalPriceTeam,
                            );

                fputcsv($file, $lineData, $delimiter);
            }
        }

        Log::info('SEND PRE FACTURA - EMAIL');
        Log::info('================');

        rewind($file);
        fclose($file);

        $this->SendPreFactura($filename);
    }

    public function SendPreFactura($filename)
    {
        $file = public_path($filename);
        $data = [];

        Mail::send('mail.prefactura', $data, function($message) use($file) {

            $message->to('wilcm123@gmail.com', 'WILBER CM')
            ->subject('pre-factura');

            $message->attach($file);
        });
    }
}