<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Models\{ 
            Configuration, HistoryDiesel, PaymentTeam, PaymentTeamDetail, 
            PackageDispatch, PeakeSeasonTeam, RangePriceBaseTeam, RangeDieselTeam,  
            RangePriceTeamByRoute, RangePriceTeamByCompany, User };

use App\Http\Controllers\{ PackagePriceCompanyTeamController };

use Log;
use Mail;

class TaskPaymentTeam extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:send-payment-team';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Realizar pagos a team para pagar';

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
        $files     = [];
        $nowDate   = date('Y-05-29');
        $startDate = date('Y-05-21');
        $endDate   = date('Y-m-d', strtotime($nowDate .' -2 day'));

        try
        {
            DB::beginTransaction();

            $teamsList = User::with(['drivers', 'role', 'routes_team'])
                            ->where('idRole', 3)
                            ->where('status', 'Active')
                            ->orderBy('name', 'asc')
                            ->get();

            foreach($teamsList as $team)
            {
                $paymentTeam = new PaymentTeam();
                $paymentTeam->id        = date('YmdHis') .'-'. $team->id;
                $paymentTeam->idTeam    = $team->id;
                $paymentTeam->startDate = $startDate;
                $paymentTeam->endDate   = $endDate;            

                $startDate = $startDate .' 00:00:00';
                $endDate   = $endDate .' 23:59:59';

                $listPackageDelivery = PackageDispatch::whereBetween('Date_Delivery', [$startDate, $endDate])
                                                        ->where('idTeam', $team->id)
                                                        ->where('paid', 0)
                                                        ->where('status', 'Delivery')
                                                        ->get();

                $totalTeam = 0;

                if($listPackageDelivery)
                {
                    foreach($listPackageDelivery as $packageDelivery)
                    {
                        $dimFactor = 200;
                        $weight    = $packageDelivery->Weight;
                        $weightRound = ceil($weight);


                        $dieselPrice = $this->GetDieselPrice('today', $packageDelivery);

                        if($dieselPrice)
                        {
                            $range = RangePriceBaseTeam::where('idTeam', $packageDelivery->idTeam)
                                                        ->where('minWeight', '<=', $weight)
                                                        ->where('maxWeight', '>=', $weight)
                                                        ->first();

                            if($range)
                            {
                                $priceWeight = $range->price;
                                $peakeSeasonPrice = $this->GetPeakeSeasonTeam($packageDelivery);
                                $priceBase = number_format($priceWeight + $peakeSeasonPrice, 2);

                                $surchargePercentage = $this->GetSurchargePercentage($packageDelivery->idTeam, $dieselPrice);

                                $surchargePrice = number_format(($priceBase * $surchargePercentage) / 100, 4);
                                $priceByRoute = $this->GetPriceTeamByRoute($packageDelivery->idTeam, $packageDelivery->Route);
                                $priceByCompany = $this->GetPriceTeamByCompany($packageDelivery->idTeam, $packageDelivery->idCompany);
                                $totalPrice     = number_format($priceBase + $surchargePrice + $priceByRoute + $priceByCompany, 4);

                                $paymentTeamDetail = PaymentTeamDetail::find($packageDelivery->Reference_Number_1);

                                if(!$paymentTeamDetail)
                                {
                                    $packageDelivery = PackageDispatch::find($packageDelivery->Reference_Number_1);
                                    $packageDelivery->paid = 1;
                                    $packageDelivery->save();

                                    $paymentTeamDetail = new PaymentTeamDetail();
                                    $paymentTeamDetail->Reference_Number_1 = $packageDelivery->Reference_Number_1;
                                    $paymentTeamDetail->idPaymentTeam      = $paymentTeam->id;
                                    $paymentTeamDetail->dimFactor      = $dimFactor;
                                    $paymentTeamDetail->weight      = $weight;
                                    $paymentTeamDetail->weightRound      = $weightRound;
                                    $paymentTeamDetail->priceWeight      = $priceWeight;
                                    $paymentTeamDetail->peakeSeasonPrice      = $peakeSeasonPrice;
                                    $paymentTeamDetail->priceBase      = $priceBase;
                                    $paymentTeamDetail->dieselPrice      = $dieselPrice;
                                    $paymentTeamDetail->surchargePercentage      = $surchargePercentage;
                                    $paymentTeamDetail->surchargePrice      = $surchargePrice;
                                    $paymentTeamDetail->priceByRoute      = $priceByRoute;
                                    $paymentTeamDetail->priceByCompany      = $priceByCompany;
                                    $paymentTeamDetail->totalPrice           = $totalPrice;
                                    $paymentTeamDetail->Date_Delivery        = $packageDelivery->Date_Delivery;
                                    $paymentTeamDetail->save();

                                    $totalTeam = $totalTeam + $totalPrice;
                                }
                                
                            }
                        }
                    }

                    if($totalTeam > 0)
                    {
                        $paymentTeam->total  = $totalTeam;
                        $paymentTeam->status = 'Payable';
                        $paymentTeam->save();
                    }
                }
            }

            DB::commit();

            Log::info('correct payment team');
        }
        catch(Exception $e)
        {
            DB::rollback();

            Log::info('error payment team');
        }
    }

    public function GetDieselPrice($from, $packageDelivery)
    {
        $historyDieselList = HistoryDiesel::orderBy('changeDate', 'asc')->get();

        $dieselPriceCompany = 0;

        if($from == 'today')
        {
            $dieselPriceCompany = Configuration::first()->diesel_price;
        }
        else
        {
            $historyDieselList = HistoryDiesel::orderBy('changeDate', 'asc')->get();

            foreach($historyDieselList as $historyDiesel)
            {
                $nowDate             = date('Y-m-d', strtotime($historyDiesel->changeDate));
                $timeChangeDateStart = strtotime($nowDate);
                $timeChangeDateEnd   = strtotime(date('Y-m-d', strtotime($nowDate .' +6 day')));
                $timeDeliveryDate    = strtotime(date('Y-m-d', strtotime($packageDelivery->Date_Delivery)));

                if($timeChangeDateStart <= $timeDeliveryDate && $timeDeliveryDate <= $timeChangeDateEnd)
                {
                    $dieselPriceCompany = $historyDiesel->roundPrice;
                }
            }
        }

        return $dieselPriceCompany;
    }

    public function GetPeakeSeasonTeam($packageDelivery)
    {
        $peakeSeasonPriceTeam = PeakeSeasonTeam::where('idTeam', $packageDelivery->idTeam)->first();

        $date = strtotime(date('Y-m-d'));

        if($date >= strtotime($peakeSeasonPriceTeam->start_date)  && $date <= strtotime($peakeSeasonPriceTeam->end_date))
        {
            if($dimWeightCompanyRound <= $peakeSeasonPriceTeam->lb1_weight)
            {
                $peakeSeasonPriceTeam = $peakeSeasonPriceTeam->lb1_weight_price;
            }
            else if($dimWeightCompanyRound > $peakeSeasonPriceTeam->lb1_weight)
            {
                $peakeSeasonPriceTeam = $peakeSeasonPriceTeam->lb2_weight_price;
            }
        }
        else
        {
            $peakeSeasonPriceTeam = 0;
        }

        return $peakeSeasonPriceTeam;
    }

    public function GetSurchargePercentage($idTeam, $dieselPrice)
    {
        $surchargePercentage = RangeDieselTeam::where('idTeam', $idTeam)
                                            ->where('at_least', '<=', $dieselPrice)
                                            ->where('but_less', '>=',  $dieselPrice)
                                            ->first();

        if($surchargePercentage)
        {
            return $surchargePercentage->surcharge_percentage;
        }

        return 0;
    }

    public function GetPriceTeamByRoute($idTeam, $route)
    {
        $range = RangePriceTeamByRoute::where('idTeam', $idTeam)
                                    ->where('route', $route)
                                    ->first();

        if($range)
        {
            return $range->price;
        }

        return 0;
    }

    public function GetPriceTeamByCompany($idTeam, $idCompany)
    {
        $range = RangePriceTeamByCompany::where('idTeam', $idTeam)
                                    ->where('idCompany', $idCompany)
                                    ->first();

        if($range)
        {
            return $range->price;
        }

        return 0;
    }
}
