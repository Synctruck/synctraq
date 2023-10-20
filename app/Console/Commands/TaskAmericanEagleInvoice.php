<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Models\{ ChargeCompany, ChargeCompanyDetail, ChargeCompanyAdjustment, PackageDispatch, PackagePriceCompanyTeam };

use Log;

class TaskAmericanEagleInvoice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:ae-invoice';

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
        $dayName = date("l");
        $nowHour = date('H');

        if($dayName == 'Monday' && $nowHour == 10)
        {
            $chargeCompany = ChargeCompany::with('company')->where('idCompany', 10)->get()->last();

            $filename = "INVOICE-" . date('m-d-H-i-s', strtotime($chargeCompany->startDate)) .'-'. date('m-d-H-i-s', strtotime($chargeCompany->endDate)) . ".csv";
            $contents = public_path($filename);

            $this->ReportInvoice($chargeCompany, $chargeCompany->startDateCSV, $chargeCompany->endDateCSV, $contents);

            Storage::disk('sftp')->putFileAs('inbox/invoice', $contents, $filename);
        }
    }

    public function ReportInvoice($charge, $dateInit, $dateEnd, $contents)
    {
        Log::info('================');
        Log::info('SEND STATUS - AE');

        $filename  = "INVOICE-" . date('m-d-H-i-s', strtotime($charge->startDate)) .'-'. date('m-d-H-i-s', strtotime($charge->endDate)) . ".csv";
        $delimiter = ",";
        $file   = fopen($contents, 'w');

        $idCharge = $charge->id;

        $fieldDate        = array('DATE', date('m/d/Y H:i:s'));
        $fieldIdPayment   = array('ID CHARGE', $idCharge);
        $fieldCompany     = array('COMPANY', $charge->company->name);
        $fietotalDelivery = array('TOTAL DELIVERY', $charge->totalDelivery .' $');
        $fielBlank        = array('');

        fputcsv($file, $fieldDate, $delimiter);
        fputcsv($file, $fieldIdPayment, $delimiter);
        fputcsv($file, $fieldCompany, $delimiter);
        fputcsv($file, $fietotalDelivery, $delimiter);
        fputcsv($file, $fielBlank, $delimiter);

        $chargeCompanyAdjustmentList = ChargeCompanyAdjustment::where('idCharge', $idCharge)
                                                                ->orderBy('created_at', 'asc')
                                                                ->get();

        if(count($chargeCompanyAdjustmentList) > 0)
        {
            fputcsv($file, array('ADJUSTMENT'), $delimiter);
            fputcsv($file, array('TOTAL ADJUSTMENT', $charge->totalRevert .' $'), $delimiter);
            fputcsv($file, array('DATE', 'DESCRIPTION', 'AMOUNT'), $delimiter);

            foreach($chargeCompanyAdjustmentList as $chargeAdjustment)
            {
                $lineDataAdjustment = array(
                    date('m/d/y H:i:s', strtotime($chargeAdjustment->created_at)),
                    $chargeAdjustment->description,
                    $chargeAdjustment->amount
                );

                fputcsv($file, $lineDataAdjustment, $delimiter);
            }

            fputcsv($file, $fielBlank, $delimiter);
            fputcsv($file, $fielBlank, $delimiter);
        }
        
        fputcsv($file, array('DATE', 'COMPANY', 'TEAM', 'PACKAGE ID', 'PRICE FUEL', 'WEIGHT COMPANY', 'DIM WEIGHT ROUND COMPANY', 'PRICE WEIGHT COMPANY', 'PEAKE SEASON PRICE COMPANY', 'PRICE BASE COMPANY', 'SURCHARGE PERCENTAGE COMPANY', 'SURCHAGE PRICE COMPANY', 'TOTAL PRICE COMPANY'), $delimiter);

        $chargeCompanyDetailList = ChargeCompanyDetail::where('idChargeCompany', $idCharge)->get();

        foreach($chargeCompanyDetailList as $chargeDetail)
        {
            $packagePriceCompanyTeam = PackagePriceCompanyTeam::where('Reference_Number_1', $chargeDetail->Reference_Number_1)->first();
            $packageDelivery         = PackageDispatch::find($chargeDetail->Reference_Number_1);

            if($packageDelivery)
            {
                $team = $packageDelivery->team ? $packageDelivery->team->name : '';
                $date = date('m-d-Y', strtotime($packageDelivery->Date_Delivery)) .' '. date('H:i:s', strtotime($packageDelivery->Date_Delivery));
            }
            else
            {
                $packageDelivery = PackageReturnCompany::find($chargeDetail->Reference_Number_1);

                if($packageDelivery)
                {
                    $date = date('m-d-Y', strtotime($packageDelivery->created_at)) .' '. date('H:i:s', strtotime($packageDelivery->created_at));
                }
                else
                {
                    $date = date('Y-m-d', strtotime($chargeDetail->created_at .' - 2day'));
                }

                $team = '';
            }

            $lineData = array(

                $date,
                ($packageDelivery ? $packageDelivery->company : ''),
                $team,
                $chargeDetail->Reference_Number_1,
                $packagePriceCompanyTeam->dieselPriceCompany,
                $packagePriceCompanyTeam->weight,
                $packagePriceCompanyTeam->dimWeightCompanyRound,
                $packagePriceCompanyTeam->priceWeightCompany,
                $packagePriceCompanyTeam->peakeSeasonPriceCompany,
                $packagePriceCompanyTeam->priceBaseCompany,
                $packagePriceCompanyTeam->surchargePercentageCompany,
                $packagePriceCompanyTeam->surchargePriceCompany,
                $packagePriceCompanyTeam->totalPriceCompany
            );

            fputcsv($file, $lineData, $delimiter);
        }

        rewind($file);
        fclose($file);
    }
}