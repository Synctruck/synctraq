<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Models\{ Company, ChargeCompany, ChargeCompanyDetail, PackageDispatch, PackagePriceCompanyTeam };

use App\Http\Controllers\{ PackagePriceCompanyTeamController };

use Log;
use Mail;

class TaskPackageSendPreFactura extends Command
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
        $dayName = date("l");
        $nowHour = date('H');

        Log::info('Hoy es: '. $dayName);

        if($dayName == 'Wednesday')
        {
            try
            {
                DB::beginTransaction();

                $files     = [];
                $nowDate   = date('Y-02-26');
                $startDate = date('Y-m-d', strtotime($nowDate .' -7 day'));
                $endDate   = date('Y-m-d', strtotime($nowDate .' -1 day'));

                $companyList = Company::all();

                foreach($companyList as $company)
                {
                    Log::info('IdCompany:'. $company->id);
                    if($company->id == 10 || $company->id == 11)
                    {
                        $filename  = 'DRAFT INVOICE-'. $company->name .'-'. date('m-d-H-i-s') .'.csv';
                        $contents  = public_path($filename);

                        array_push($files, $contents);
                    
                        $this->GetReportCharge($startDate, $endDate, $company->id, $filename, $contents);
                    }
                }

                $this->SendPreFactura($startDate, $endDate, $files);

                DB::commit();
            }
            catch(Exception $e)
            {
                DB::rollback();
            }
        }
    }

    public function GetReportCharge($startDate, $endDate, $idCompany, $filename, $contents)
    {
        $idCharge = date('YmdHis') .'-'. $idCompany;

        $chargeCompany = new ChargeCompany();

        $chargeCompany->id        = $idCharge;
        $chargeCompany->idCompany = $idCompany;
        $chargeCompany->startDate = $startDate;
        $chargeCompany->endDate   = $endDate;

        $startDate = $startDate .' 00:00:00';
        $endDate   = $endDate .' 23:59:59';
        $delimiter = ",";
        $file      = fopen($contents, 'w');
        $fields    = array('DELIVERY DATE', 'COMPANY', 'PACKAGE ID', 'PRICE FUEL', 'WEIGHT COMPANY', 'DIM WEIGHT ROUND COMPANY', 'PRICE WEIGHT COMPANY', 'PEAKE SEASON PRICE COMPANY', 'PRICE BASE COMPANY', 'SURCHARGE PERCENTAGE COMPANY', 'SURCHAGE PRICE COMPANY', 'TOTAL PRICE COMPANY');

        fputcsv($file, $fields, $delimiter);

        $listPackageDelivery = PackageDispatch::whereBetween('Date_Delivery', [$startDate, $endDate])
                                                ->where('idCompany', $idCompany)
                                                ->where('status', 'Delivery')
                                                ->get();

        Log::info('================');
        Log::info('SEND PRE FACTURA - EMAIL - COMPANY - '. $idCompany);
        Log::info('Quantity:'. count($listPackageDelivery));

        $totalCharge = 0;

        if(count($listPackageDelivery) > 0)
        {
            foreach($listPackageDelivery as $packageDelivery)
            {
                $packagePriceCompanyTeam = PackagePriceCompanyTeam::where('Reference_Number_1', $packageDelivery->Reference_Number_1)
                                                                    ->first();

                if($packagePriceCompanyTeam == null || date("l", strtotime($packageDelivery->Date_Delivery)) == 'Monday')
                {
                    //create or update price company team
                    $packagePriceCompanyTeamController = new PackagePriceCompanyTeamController();
                    $packagePriceCompanyTeamController->Insert($packageDelivery, 'old');

                    $packagePriceCompanyTeam = PackagePriceCompanyTeam::where('Reference_Number_1', $packageDelivery->Reference_Number_1)
                                                                    ->first();
                }

                if($packagePriceCompanyTeam)
                {
                    $chargeCompanyDetail = ChargeCompanyDetail::where('Reference_Number_1', $packageDelivery->Reference_Number_1)->first();

                    if($chargeCompanyDetail == null)
                    {
                        $totalCharge = $totalCharge + $packagePriceCompanyTeam->totalPriceCompany;

                        $chargeCompanyDetail = new ChargeCompanyDetail();

                        $chargeCompanyDetail->Reference_Number_1 = $packageDelivery->Reference_Number_1;
                        $chargeCompanyDetail->idChargeCompany    = $idCharge;

                        $chargeCompanyDetail->save();

                        $lineData = array(
                                        date('m-d-Y', strtotime($packageDelivery->Date_Delivery)),
                                        $packageDelivery->company,
                                        $packageDelivery->Reference_Number_1,
                                        '$'. $packagePriceCompanyTeam->dieselPriceCompany,
                                        $packagePriceCompanyTeam->weight,
                                        $packagePriceCompanyTeam->dimWeightCompanyRound,
                                        '$'. $packagePriceCompanyTeam->priceWeightCompany,
                                        '$'. $packagePriceCompanyTeam->peakeSeasonPriceCompany,
                                        '$'. $packagePriceCompanyTeam->priceBaseCompany,
                                        $packagePriceCompanyTeam->surchargePercentageCompany .'%',
                                        '$'. $packagePriceCompanyTeam->surchargePriceCompany,
                                        '$'. $packagePriceCompanyTeam->totalPriceCompany
                                    );

                        fputcsv($file, $lineData, $delimiter);
                    }
                }
            }

            rewind($file);
            fclose($file);
        }

        $chargeCompany->total  = $totalCharge;
        $chargeCompany->status = 'DRAFT INVOICE';

        $chargeCompany->save();

        Log::info('SEND PRE FACTURA - EMAIL - COMPANY - '. $idCompany);
        Log::info('================');
    }

    public function SendPreFactura($startDate, $endDate, $files)
    {
        $files = $files;
        $data  = ['startDate' => $startDate, 'endDate' => $endDate];

        Mail::send('mail.prefactura', ['data' => $data ], function($message) use($startDate, $endDate, $files) {

            $message->to('wilcm123@gmail.com', 'WILBER CM')
            ->subject('DRAFT INVOICE ('. $startDate .' - '. $endDate .')');

            foreach ($files as $file)
            {
                $message->attach($file);
            }
        });
    }
}
