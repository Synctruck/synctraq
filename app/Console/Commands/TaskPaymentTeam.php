<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Models\{
            Configuration, HistoryDiesel, PaymentTeam, PaymentTeamAdjustment, PaymentTeamDetail,
            PackageDispatch, PackageReturnCompany, PeakeSeasonTeam, RangePriceBaseTeam, RangeDieselTeam,
            RangePriceTeamByRoute, RangePriceTeamByCompany, ToReversePackages, User, ToDeductLostPackages };

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

        if($dayName == 'Tuesday')
        {
            $files     = [];
            $nowDate   = date('Y-m-d');
            //$startDate = date('2023-11-01');
            //$endDate   = date('Y-m-d', strtotime($nowDate .' -2 day'));
            $initDate   = date('Y-m-d', strtotime($nowDate .' -8 day'));

            $startDate = date('2024-01-01 00:00:00');
            $endDate = date('2024-12-31 23:59:59');

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
                    $paymentTeam->initDate    = $initDate;
                    $paymentTeam->endDate     = $endDate;

                    $startDate = $startDate .' 00:00:00';
                    $endDate   = $endDate .' 23:59:59';

                    if($team->configurationPay == 'Package')
                    {
                        $listPackageDelivery = PackageDispatch::whereBetween('Date_Delivery', [$startDate, $endDate])
                                                                ->where('idTeam', $team->id)
                                                                ->where('paid', 0)
                                                                ->where('status', 'Delivery')
                                                                ->get();
                    }
                    else
                    {
                        $listPackageDelivery = PackageDispatch::whereBetween('Date_Delivery', [$startDate, $endDate])
                                            ->where('status', 'Delivery')
                                            ->where('paid', 0)
                                            ->where('idTeam', $team->id)
                                            ->selectRaw('Reference_Number_1, DATE(Date_Delivery) as DATE_DELIVERY, Dropoff_Address_Line_1, idTeam, company')
                                            ->orderBy('Date_Delivery', 'asc')
                                            ->orderBy('Dropoff_Address_Line_1', 'asc')
                                            ->get();
                    }

                    $listPackageReturnCompany = PackageReturnCompany::where('idTeam', $team->id)
                                                                    ->where('paid', 1)
                                                                    ->get();

                    $totalPieces = 0;
                    $totalTeam   = 0;
                    $totalDeduction = 0;

                    if(count($listPackageDelivery) > 0 || count($listPackageReturnCompany) > 0)
                    {
                        $toReversePackagesList = ToReversePackages::where('idTeam', $team->id)->get();
                        $totalAdjustment       = $toReversePackagesList->sum('priceToRevert');

                        foreach($toReversePackagesList as $revert)
                        {
                            $toReversePackages = ToReversePackages::find($revert->shipmentId);
                            $toReversePackages->delete();
                        }

                        $shipmentIds = '';
                        $totalAdjustmentToDeduct = 0;

                        $toDeductLostPackagesList = ToDeductLostPackages::where('idTeam', $team->id)->get();

                        foreach($toDeductLostPackagesList as $toDeductLostPackages)
                        {
                            $totalAdjustmentToDeduct = $totalAdjustmentToDeduct + $toDeductLostPackages->priceToDeduct;

                            $shipmentIds = $shipmentIds == '' ? $toDeductLostPackages->shipmentId : $shipmentIds .','. $toDeductLostPackages->shipmentId;

                            $toDeductLostPackages = ToDeductLostPackages::find($toDeductLostPackages->shipmentId);
                            $toDeductLostPackages->delete();
                        }

                        if($team->configurationPay == 'Package')
                        {
                            $dataPrices = $this->SaveDetailPaymentForPackage($team, $listPackageDelivery, $paymentTeam->id);
                        }
                        else
                        {
                            $dataPrices = $this->SaveDetailPaymentForRoute($team, $listPackageDelivery, $paymentTeam->id);
                        }
                        
                        $totalPieces = $dataPrices['totalPieces'];
                        $totalTeam = $dataPrices['totalTeam'];
                        $totalDeduction = $dataPrices['totalDeduction'];

                        foreach($listPackageReturnCompany as $packageReturnCompany)
                        {
                            $dimFactor   = 200;
                            $weight      = $packageReturnCompany->Weight;
                            $weightRound = ceil($weight);

                            if($team->configurationPay == 'Package')
                            {
                                $dieselPrice = $this->GetDieselPrice($packageReturnCompany->created_at);

                                if($dieselPrice)
                                {
                                    $range = RangePriceBaseTeam::where('idTeam', $packageReturnCompany->idTeam)
                                                                ->where('minWeight', '<=', $weightRound)
                                                                ->where('maxWeight', '>=', $weightRound)
                                                                ->first();

                                    if($range)
                                    {
                                        $priceWeight         = $range->price;
                                        $peakeSeasonPrice    = $this->GetPeakeSeasonTeam($packageReturnCompany);
                                        $priceBase           = number_format($priceWeight + $peakeSeasonPrice, 2);

                                        if($team->surcharge)
                                        {
                                            $surchargePercentage = $this->GetSurchargePercentage($packageReturnCompany->idTeam, $dieselPrice);
                                            $surchargePrice      = number_format(($priceBase * $surchargePercentage) / 100, 4);
                                        }
                                        else
                                        {
                                            $surchargePercentage = 0;
                                            $surchargePrice      = 0;
                                        }

                                        $priceByCompany      = $this->GetPriceTeamByCompany($packageReturnCompany->idTeam, $packageReturnCompany->idCompany, $packageReturnCompany->Route, $range->id);
                                        $totalPrice          = number_format($priceBase + $surchargePrice + $priceByCompany, 4);

                                        $paymentTeamDetail = PaymentTeamDetail::find($packageReturnCompany->Reference_Number_1);

                                        if(!$paymentTeamDetail)
                                        {
                                            $paymentTeamDetail = new PaymentTeamDetail();
                                            $paymentTeamDetail->Reference_Number_1  = $packageReturnCompany->Reference_Number_1;
                                            $paymentTeamDetail->Route               = $packageReturnCompany->Route;
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
                                            $paymentTeamDetail->Date_Delivery       = $packageReturnCompany->created_at;
                                            $paymentTeamDetail->save();

                                            $totalPieces = $totalPieces + 1;
                                            $totalTeam   = $totalTeam + $totalPrice;
                                        }

                                        $packageReturnCompany = PackageReturnCompany::find($packageReturnCompany->Reference_Number_1);
                                        $packageReturnCompany->paid = 2;
                                        $packageReturnCompany->save();
                                    }
                                }
                            }
                        }

                        if($totalTeam > 0)
                        {
                            if($totalAdjustment != 0)
                            {
                                $paymentTeamAdjustment = new PaymentTeamAdjustment();
                                $paymentTeamAdjustment->id            = uniqid();
                                $paymentTeamAdjustment->idPaymentTeam = $paymentTeam->id;
                                $paymentTeamAdjustment->amount        = $totalAdjustment;
                                $paymentTeamAdjustment->description   = 'Reverts';
                                $paymentTeamAdjustment->save();
                            }

                            if($totalAdjustmentToDeduct != 0)
                            {
                                $paymentTeamAdjustment = new PaymentTeamAdjustment();
                                $paymentTeamAdjustment->id            = uniqid();
                                $paymentTeamAdjustment->idPaymentTeam = $paymentTeam->id;
                                $paymentTeamAdjustment->amount        = -$totalAdjustmentToDeduct;
                                $paymentTeamAdjustment->description   = 'Lost Packages: '. $shipmentIds;
                                $paymentTeamAdjustment->save();
                            }

                            $paymentTeam->totalPieces    = $totalPieces;
                            $paymentTeam->totalDelivery  = $totalTeam;
                            $paymentTeam->totalDeduction = $totalDeduction;
                            $paymentTeam->totalAdjustment = $totalAdjustment - $totalAdjustmentToDeduct;
                            $paymentTeam->total          = $totalTeam + $totalAdjustment - $totalAdjustmentToDeduct + $totalDeduction;
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

    public function SaveDetailPaymentForPackage($team, $listPackageDelivery, $idPaymentTeam)
    {
        $totalPieces = 0;
        $totalTeam   = 0;
        $totalDeduction = 0;

        foreach($listPackageDelivery as $packageDelivery)
        {
            $dimFactor   = 200;
            $weight      = $packageDelivery->Weight;
            $weightRound = ceil($weight);
            $dieselPrice = $this->GetDieselPrice($packageDelivery->Date_Delivery);

            if($dieselPrice)
            {
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

                    $priceByCompany      = $this->GetPriceTeamByCompany($packageDelivery->idTeam, $packageDelivery->idCompany, $packageDelivery->Route, $range->id);
                    $totalPrice          = number_format($priceBase + $surchargePrice + $priceByCompany, 4);

                    $paymentTeamDetail = PaymentTeamDetail::find($packageDelivery->Reference_Number_1);

                    if(!$paymentTeamDetail)
                    {
                        $packageDelivery = PackageDispatch::find($packageDelivery->Reference_Number_1);
                        $packageDelivery->paid = 1;
                        $packageDelivery->save();

                        if($team->sla)
                        {
                            if($packageDelivery->Date_Dispatch)
                            $hours = $this->CalculateHours($packageDelivery->Date_Dispatch, $packageDelivery->Date_Delivery);
                            else
                                $hours = 0;

                            if($hours <= 24)
                                $deduction = 0.00;
                            elseif($hours > 24 && $hours <= 48)
                                $deduction = 1.00;
                            elseif($hours > 48 && $hours <= 72)
                                $deduction = 2.00;
                            elseif($hours > 72)
                                $deduction = 2.50;
                        }
                        else
                            $deduction = 0;

                        $paymentTeamDetail = new PaymentTeamDetail();
                        $paymentTeamDetail->Reference_Number_1  = $packageDelivery->Reference_Number_1;
                        $paymentTeamDetail->Route               = $packageDelivery->Route;
                        $paymentTeamDetail->idPaymentTeam       = $idPaymentTeam;
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
                        $paymentTeamDetail->priceDeduction      = -$deduction;
                        $paymentTeamDetail->totalPrice          = $totalPrice + $paymentTeamDetail->priceDeduction;
                        $paymentTeamDetail->Date_Delivery       = $packageDelivery->Date_Delivery;
                        $paymentTeamDetail->Date_Dispatch       = $packageDelivery->Date_Dispatch ? $packageDelivery->Date_Dispatch : $packageDelivery->Date_Delivery;
                        $paymentTeamDetail->save();

                        $totalPieces = $totalPieces + 1;
                        $totalTeam   = $totalTeam + $totalPrice;
                        $totalDeduction = $totalDeduction - $deduction;
                    }
                }
            }
        }

        return ['totalPieces' => $totalPieces, 'totalTeam' => $totalTeam, 'totalDeduction' => $totalDeduction];
    }

    public function SaveDetailPaymentForRoute($team, $listPackageDelivery, $idPaymentTeam)
    {
        $totalPieces = 0;
        $totalTeam   = 0;
        $totalDeduction = 0;

        $stopsQuantity = [];
        $addressPackages = [];
        $pricePerStop = 0;

        foreach($listPackageDelivery as $packageDelivery)
        {
            $stringSearch = $packageDelivery->DATE_DELIVERY . $packageDelivery->Dropoff_Address_Line_1;

            array_push($stopsQuantity, $stringSearch);
        }

        $stopsQuantity = array_count_values($stopsQuantity);
        $quantity = 0;

        foreach($listPackageDelivery as $packageDelivery)
        {
            $signature = $packageDelivery->company == 'EIGHTVAPE' ? $team->signature : 0;
            $priceBase = 0;
            $stringSearch = $packageDelivery->DATE_DELIVERY . $packageDelivery->Dropoff_Address_Line_1;

            if(in_array($stringSearch, $addressPackages))
            {
                array_push($addressPackages, $stringSearch);

                $priceBase = ($team->priceByPackage / $team->splitForAddPc);
                $quantity = $quantity + 1;
            }
            else
            {
                array_push($addressPackages, $stringSearch);

                $quantityPackages = $stopsQuantity[$stringSearch];
                $discountGap = $this->GetDiscountGapBetweenTiers($quantityPackages, $team->gapBetweenTiers);
                $priceBase = ($team->baseRate - $discountGap) + $team->priceByPackage;
                $quantity = 1;
            }

            $paymentTeamDetail = new PaymentTeamDetail();
            $paymentTeamDetail->Reference_Number_1  = $packageDelivery->Reference_Number_1;
            $paymentTeamDetail->Route               = $packageDelivery->Route ? $packageDelivery->Route : '---';
            $paymentTeamDetail->idPaymentTeam       = $idPaymentTeam;
            $paymentTeamDetail->dimFactor           = 0;
            $paymentTeamDetail->weight              = $packageDelivery->Weight ? $packageDelivery->Weight : 0;
            $paymentTeamDetail->weightRound         = ceil($paymentTeamDetail->weight);
            $paymentTeamDetail->priceWeight         = 0;
            $paymentTeamDetail->peakeSeasonPrice    = $signature;
            $paymentTeamDetail->priceBase           = $priceBase;
            $paymentTeamDetail->dieselPrice         = 0;
            $paymentTeamDetail->surchargePercentage = 0;
            $paymentTeamDetail->surchargePrice      = 0;
            $paymentTeamDetail->priceByRoute        = 0;
            $paymentTeamDetail->priceByCompany      = 0;
            $paymentTeamDetail->priceDeduction      = 0;
            $paymentTeamDetail->totalPrice          = $priceBase + $signature;
            $paymentTeamDetail->Date_Delivery       = $packageDelivery->Date_Delivery ? $packageDelivery->Date_Delivery : date('Y-m-d H:i:s');
            $paymentTeamDetail->Date_Dispatch       = $packageDelivery->Date_Dispatch ? $packageDelivery->Date_Dispatch : $packageDelivery->Date_Delivery;
            $paymentTeamDetail->save();

            $totalPieces = $totalPieces + 1;
            $totalTeam   = $totalTeam + ($priceBase + $signature);
        }

        return ['totalPieces' => $totalPieces, 'totalTeam' => $totalTeam, 'totalDeduction' => $totalDeduction];
    }

    public function CalculateHours($Date_Dispatch, $Date_Delivery)
    {
        $dateInit = strtotime($Date_Dispatch);
        $dateEnd = strtotime($Date_Delivery);

        $diff = abs($dateEnd - $dateInit) / 3600;

        return (int)$diff;
    }

    public function GetDieselPrice($Date_Delivery)
    {
        $dieselPriceCompany = 0;

        $historyDieselList = HistoryDiesel::orderBy('changeDate', 'asc')->get();

        foreach($historyDieselList as $historyDiesel)
        {
            $nowDate             = date('Y-m-d', strtotime($historyDiesel->changeDate));
            $timeChangeDateStart = strtotime($nowDate);
            $timeChangeDateEnd   = strtotime(date('Y-m-d', strtotime($nowDate .' +6 day')));
            $timeDeliveryDate    = strtotime(date('Y-m-d', strtotime($Date_Delivery)));

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

    public function GetPriceTeamByCompany($idTeam, $idCompany, $route, $idRangeRate)
    {
        $rangeByCompanyTeam = RangePriceTeamByCompany::where('idTeam', $idTeam)
                                                        ->where('idCompany', $idCompany)
                                                        ->where('idRangeRate', $idRangeRate)
                                                        ->where('route', $route)
                                                        ->first();

        $rangeByCompany = RangePriceTeamByCompany::where('idTeam', $idTeam)
                                    ->where('idCompany', $idCompany)
                                    ->where('idRangeRate', 0)
                                    ->where('route', '')
                                    ->first();

        $rangeByRate = RangePriceTeamByCompany::where('idTeam', $idTeam)
                                    ->where('idCompany', 0)
                                    ->where('idRangeRate', $idRangeRate)
                                    ->where('route', '')
                                    ->first();

        $rangeByRoute = RangePriceTeamByCompany::where('idTeam', $idTeam)
                                    ->where('idCompany', 0)
                                    ->where('idRangeRate', 0)
                                    ->where('route', $route)
                                    ->first();

        $priceCompanyTeam = $rangeByCompanyTeam ? $rangeByCompanyTeam->price : 0;
        $priceRate        = $rangeByRate ? $rangeByRate->price : 0;
        $priceCompany     = $rangeByCompany ? $rangeByCompany->price : 0;
        $priceTeam        = $rangeByRoute ? $rangeByRoute->price : 0;
        $totalPrices      = $priceCompanyTeam + $priceRate + $priceCompany + $priceTeam;

        return $totalPrices;
    }

    public function GetDiscountGapBetweenTiers($quantity, $gapBetweenTiers)
    {
        if($quantity >= 0 && $quantity <= 79)
            $discount = 0;
        else if($quantity >= 80 && $quantity <= 99)
            $discount = $gapBetweenTiers * 1;
        else if($quantity >= 100 && $quantity <= 119)
            $discount = $gapBetweenTiers * 2;
        else if($quantity >= 120)
            $discount = $gapBetweenTiers * 3;

        return $discount;
    }
}
