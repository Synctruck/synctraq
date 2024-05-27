<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Models\{ Company, ChargeCompany, ChargeCompanyDetail, PackageDispatch, PackagePriceCompanyTeam, PackageReturnCompany };

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

        /*try
        {
            DB::beginTransaction();

            $files     = [];
            $nowDate   = date('Y-08-14');
            $startDate = date('Y-08-06');
            $endDate   = date('Y-m-d', strtotime($nowDate .' -2 day'));

            $companyList = Company::all();

            foreach($companyList as $company)
            {
                if($company->id == 1 || $company->id == 10 || $company->id == 11 || $company->id == 13)
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
        }*/

        if($dayName == 'Monday' && $nowHour == 9)
        {
            try
            {
                DB::beginTransaction();

                $files     = [];
                $nowDate   = date('Y-m-d');
                $startDate = date('2023-01-01');
                $endDate   = date('Y-m-d', strtotime($nowDate .' -2 day'));
                $initDate  = date('Y-m-d', strtotime($nowDate .' -8 day'));

                $companyList = Company::all();

                foreach($companyList as $company)
                {
                    if($company->id == 1 || $company->id == 10 || $company->id == 11 || $company->id == 13 || $company->id == 14)
                    {
                        $filename  = 'DRAFT INVOICE-'. $company->name .'-'. date('m-d-H-i-s') .'.csv';
                        $contents  = public_path($filename);

                        array_push($files, $contents);

                        $this->GetReportCharge($startDate, $initDate,  $endDate, $company->id, $filename, $contents);
                    }
                }

                $this->SendPreFactura($startDate, $initDate,$endDate, $files);

                DB::commit();
            }
            catch(Exception $e)
            {
                DB::rollback();
            }
        }
    }

    public function GetReportCharge($startDate, $initDate, $endDate, $idCompany, $filename, $contents)
    {
        $idCharge = date('YmdHis') .'-'. $idCompany;

        $chargeCompany = new ChargeCompany();

        $chargeCompany->id        = $idCharge;
        $chargeCompany->idCompany = $idCompany;
        $chargeCompany->startDate = $startDate;
        $chargeCompany->endDate   = $endDate;
        $chargeCompany->initDate  = $initDate;


        $startDate = $startDate .' 00:00:00';
        $endDate   = $endDate .' 23:59:59';

        $delimiter = ",";
        $file      = fopen($contents, 'w');
        $fields    = array('DELIVERY DATE', 'COMPANY', 'PACKAGE ID', 'STATUS', 'PRICE FUEL', 'WEIGHT COMPANY', 'DIM WEIGHT ROUND COMPANY', 'PRICE WEIGHT COMPANY', 'PEAKE SEASON PRICE COMPANY', 'PRICE BASE COMPANY', 'SURCHARGE PERCENTAGE COMPANY', 'SURCHAGE PRICE COMPANY', 'TOTAL PRICE COMPANY');

        fputcsv($file, $fields, $delimiter);

        $listPackageDelivery = PackageDispatch::whereBetween('Date_Delivery', [$startDate, $endDate])
                                                ->where('idCompany', $idCompany)
                                                ->where('invoiced', 0)
                                                ->where('require_invoice', 1)
                                                ->where('status', 'Delivery')
                                                ->get();

        $packageReturnCompanyList = PackageReturnCompany::where('invoice', 1)
                                                        ->where('idCompany', $idCompany)
                                                        ->get();

        Log::info('================');
        Log::info('SEND PRE FACTURA - EMAIL - COMPANY - '. $idCompany);
        Log::info('Quantity:'. count($listPackageDelivery));

        $totalCharge = 0;

        if(count($listPackageDelivery) > 0 || count($packageReturnCompanyList) > 0)
        {
            foreach($listPackageDelivery as $packageDelivery)
            {
                $packageDelivery = PackageDispatch::find($packageDelivery->Reference_Number_1);

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
                        $chargeCompanyDetail->status             = 'DELIVERY';
                        $chargeCompanyDetail->save();

                        $lineData = array(
                                        date('m-d-Y', strtotime($packageDelivery->Date_Delivery)),
                                        $packageDelivery->company,
                                        $packageDelivery->Reference_Number_1,
                                        'DELIVERY',
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

                    $packageDelivery->invoiced = 1;
                    $packageDelivery->save();
                }
            }

            foreach($packageReturnCompanyList as $packageReturnCompany)
            {
                $packageReturnCompany = PackageReturnCompany::find($packageReturnCompany->Reference_Number_1);

                $packagePriceCompanyTeam = PackagePriceCompanyTeam::where('Reference_Number_1', $packageReturnCompany->Reference_Number_1)
                                                                    ->first();

                if($packagePriceCompanyTeam == null || date("l", strtotime($packageReturnCompany->created_at)) == 'Monday')
                {
                    //create or update price company team
                    $packagePriceCompanyTeamController = new PackagePriceCompanyTeamController();
                    $packagePriceCompanyTeamController->Insert($packageReturnCompany, 'old');

                    $packagePriceCompanyTeam = PackagePriceCompanyTeam::where('Reference_Number_1', $packageReturnCompany->Reference_Number_1)
                                                                    ->first();
                }

                Log::info('$packagePriceCompanyTeam => '. $packagePriceCompanyTeam);

                if($packagePriceCompanyTeam)
                {
                    $chargeCompanyDetail = ChargeCompanyDetail::where('Reference_Number_1', $packageReturnCompany->Reference_Number_1)->first();

                    if($chargeCompanyDetail == null)
                    {
                        $totalCharge = $totalCharge + $packagePriceCompanyTeam->totalPriceCompany;

                        $chargeCompanyDetail = new ChargeCompanyDetail();
                        $chargeCompanyDetail->Reference_Number_1 = $packageReturnCompany->Reference_Number_1;
                        $chargeCompanyDetail->idChargeCompany    = $idCharge;
                        $chargeCompanyDetail->status             = 'RTS';
                        $chargeCompanyDetail->save();

                        $lineData = array(
                                        date('m-d-Y', strtotime($packageReturnCompany->created_at)),
                                        $packageReturnCompany->company,
                                        $packageReturnCompany->Reference_Number_1,
                                        'RTS',
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

                    $packageReturnCompany->invoice = 2;
                    $packageReturnCompany->save();
                }
            }

            rewind($file);
            fclose($file);
        }

        $chargeCompany->totalDelivery  = $totalCharge;
        $chargeCompany->total          = $totalCharge;
        $chargeCompany->status         = 'TO APPROVE';

        $chargeCompany->save();

        Log::info('SEND PRE FACTURA - EMAIL - COMPANY - '. $idCompany);
        Log::info('================');
    }

    public function SendPreFactura($startDate, $initDate, $endDate, $files)
    {
        $files = $files;
        $data  = ['startDate' => $initDate, 'endDate' => $endDate];

        if(ENV('APP_ENV') == 'production')
        {
            Mail::send('mail.prefactura', ['data' => $data ], function($message) use($startDate,$initDate, $endDate, $files) {

                $message->to('jm.busto@synctruck.com', 'SYNCTRUCK')
                ->subject('DRAFT INVOICE ('. $initDate .' - '. $endDate .')');

                foreach ($files as $file)
                {
                    $message->attach($file);
                }
            });
        }

        Mail::send('mail.prefactura', ['data' => $data ], function($message) use($startDate, $initDate, $endDate, $files) {

            $message->to('wilcm123@gmail.com', 'WILBER CM')
            ->subject('DRAFT INVOICE ('. $initDate .' - '. $endDate .')');

            foreach ($files as $file)
            {
                $message->attach($file);
            }
        });
    }
}
