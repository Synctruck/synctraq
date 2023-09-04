<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Models\{ 
            Configuration, HistoryDiesel, PaymentTeam, PaymentTeamAdjustment, PaymentTeamDetail, 
            PackageDispatch, PeakeSeasonTeam, RangePriceBaseTeam, RangeDieselTeam,  
            RangePriceTeamByRoute, RangePriceTeamByCompany, ToReversePackages, User };

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
        $dayName = date("l");
        $nowHour = date('H');

        if($dayName == 'Monday' && $nowHour > 9)
        {
            $files     = [];
            $nowDate   = date('Y-m-d');
            $startDate = date('Y-m-d', strtotime($nowDate .' -10 day'));
            $endDate   = date('Y-m-d', strtotime($nowDate .' -4 day'));

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
                    $paymentTeam->id          = date('YmdHis') .'-'. $team->id;
                    $paymentTeam->idTeam      = $team->id;
                    $paymentTeam->startDate   = $startDate;
                    $paymentTeam->endDate     = $endDate;

                    $startDate = $startDate .' 00:00:00';
                    $endDate   = $endDate .' 23:59:59';

                    $listPackageDelivery = PackageDispatch::whereBetween('Date_Delivery', [$startDate, $endDate])
                                                            ->where('idTeam', $team->id)
                                                            ->where('paid', 0)
                                                            ->where('status', 'Delivery')
                                                            ->get();

                    if($team->id == 271)
                    {
                        Log::info('$listPackageDelivery => ');
                        Log::info($listPackageDelivery);
                    }

                    $totalPieces = 0;
                    $totalTeam   = 0;

                    if($listPackageDelivery)
                    {
                        $toReversePackagesList = ToReversePackages::where('idTeam', $team->id)->get();
                        $totalRevert           = $toReversePackagesList->sum('priceToRevert');

                        foreach($toReversePackagesList as $revert)
                        {
                            $toReversePackages = ToReversePackages::find($revert->shipmentId);
                            $toReversePackages->delete();
                        }

                        foreach($listPackageDelivery as $packageDelivery)
                        {
                            $dimFactor   = 200;
                            $weight      = $packageDelivery->Weight;
                            $weightRound = ceil($weight);

                            $dieselPrice = $this->GetDieselPrice($packageDelivery);

                            if($dieselPrice)
                            {
                                Log::info('dieselPrice => '. $dieselPrice);
                                
                                $range = RangePriceBaseTeam::where('idTeam', $packageDelivery->idTeam)
                                                            ->where('minWeight', '<=', $weightRound)
                                                            ->where('maxWeight', '>=', $weightRound)
                                                            ->first();

                                if($range)
                                {
                                    $priceWeight         = $range->price;
                                    $peakeSeasonPrice    = $this->GetPeakeSeasonTeam($packageDelivery);
                                    $priceBase           = number_format($priceWeight + $peakeSeasonPrice, 2);

                                    if($team->surcharge)
                                    {
                                        $surchargePercentage = $this->GetSurchargePercentage($packageDelivery->idTeam, $dieselPrice);
                                        $surchargePrice      = number_format(($priceBase * $surchargePercentage) / 100, 4);
                                    }
                                    else
                                    {
                                        $surchargePercentage = 0;
                                        $surchargePrice      = 0;
                                    }
                                    
                                    $priceByCompany      = $this->GetPriceTeamByCompany($packageDelivery->idTeam, $packageDelivery->idCompany, $packageDelivery->Route);
                                    $totalPrice          = number_format($priceBase + $surchargePrice + $priceByCompany, 4);

                                    $paymentTeamDetail = PaymentTeamDetail::find($packageDelivery->Reference_Number_1);

                                    if(!$paymentTeamDetail)
                                    {
                                        $packageDelivery = PackageDispatch::find($packageDelivery->Reference_Number_1);
                                        $packageDelivery->paid = 1;
                                        $packageDelivery->save();

                                        $paymentTeamDetail = new PaymentTeamDetail();
                                        $paymentTeamDetail->Reference_Number_1  = $packageDelivery->Reference_Number_1;
                                        $paymentTeamDetail->Route               = $packageDelivery->Route;
                                        $paymentTeamDetail->idPaymentTeam       = $paymentTeam->id;
                                        $paymentTeamDetail->dimFactor           = $dimFactor;
                                        $paymentTeamDetail->weight              = $weight;
                                        $paymentTeamDetail->weightRound         = $weightRound;
                                        $paymentTeamDetail->priceWeight         = $priceWeight;
                                        $paymentTeamDetail->peakeSeasonPrice    = $peakeSeasonPrice;
                                        $paymentTeamDetail->priceBase           = $priceBase;
                                        $paymentTeamDetail->dieselPrice         = $dieselPrice;
                                        $paymentTeamDetail->surchargePercentage = $surchargePercentage;
                                        $paymentTeamDetail->surchargePrice      = $surchargePrice;
                                        $paymentTeamDetail->priceByRoute        = 0;
                                        $paymentTeamDetail->priceByCompany      = $priceByCompany;
                                        $paymentTeamDetail->totalPrice          = $totalPrice;
                                        $paymentTeamDetail->Date_Delivery       = $packageDelivery->Date_Delivery;
                                        $paymentTeamDetail->save();

                                        $totalPieces = $totalPieces + 1;
                                        $totalTeam   = $totalTeam + $totalPrice;
                                    }
                                }
                            }
                        }

                        if($totalTeam > 0)
                        { 
                            /*if($totalRevert != 0)
                            {
                                $paymentTeamAdjustment = new PaymentTeamAdjustment();
                                $paymentTeamAdjustment->id            = uniqid();
                                $paymentTeamAdjustment->idPaymentTeam = $paymentTeam->id;
                                $paymentTeamAdjustment->amount        = $totalRevert;
                                $paymentTeamAdjustment->description   = 'Reverts';
                                $paymentTeamAdjustment->save();
                            }*/

                            $paymentTeam->totalPieces    = $totalPieces;
                            $paymentTeam->totalDelivery  = $totalTeam;
                            $paymentTeam->totalRevert    = $totalRevert;
                            $paymentTeam->totalAdjustment = 0;
                            $paymentTeam->total          = $totalTeam + $totalRevert;
                            $paymentTeam->averagePrice   = $totalTeam / $totalPieces;
                            $paymentTeam->surcharge      = $team->surcharge;
                            $paymentTeam->status         = 'TO APPROVE';
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
    }

    public function GetDieselPrice($packageDelivery)
    {
        $dieselPriceCompany = 0;

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

        return $dieselPriceCompany;
    }

    public function GetPeakeSeasonTeam($packageDelivery)
    {
        $peakeSeasonTeam = PeakeSeasonTeam::where('idTeam', $packageDelivery->idTeam)->first();
        
        $peakeSeasonPriceTeam = 0;

        if($peakeSeasonTeam)
        {
            $date = strtotime(date('Y-m-d'));

            if($date >= strtotime($peakeSeasonTeam->start_date)  && $date <= strtotime($peakeSeasonTeam->end_date))
            {
                if($dimWeightCompanyRound <= $peakeSeasonTeam->lb1_weight)
                {
                    $peakeSeasonPriceTeam = $peakeSeasonTeam->lb1_weight_price;
                }
                else if($dimWeightCompanyRound > $peakeSeasonTeam->lb1_weight)
                {
                    $peakeSeasonPriceTeam = $peakeSeasonTeam->lb2_weight_price;
                }
            }
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

    public function GetPriceTeamByCompany($idTeam, $idCompany, $route)
    {
        $rangeByCompanyTeam = RangePriceTeamByCompany::where('idTeam', $idTeam)
                                                        ->where('idCompany', $idCompany)
                                                        ->where('route', $route)
                                                        ->first();

        $rangeByCompany = RangePriceTeamByCompany::where('idTeam', $idTeam)
                                    ->where('idCompany', $idCompany)
                                    ->where('route', '')
                                    ->first();

        $rangeByRoute = RangePriceTeamByCompany::where('idTeam', $idTeam)
                                    ->where('idCompany', 0)
                                    ->where('route', $route)
                                    ->first();

        $priceCompanyTeam = $rangeByCompanyTeam ? $rangeByCompanyTeam->price : 0;
        $priceCompany     = $rangeByCompany ? $rangeByCompany->price : 0;
        $priceTeam        = $rangeByRoute ? $rangeByRoute->price : 0;
        $totalPrices      = $priceCompanyTeam + $priceCompany + $priceTeam;

        return $totalPrices;
    }
}