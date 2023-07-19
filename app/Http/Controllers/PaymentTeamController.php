<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{ 
            Configuration, HistoryDiesel, PaymentTeam, PaymentTeamDetail, 
            PackageDispatch, PeakeSeasonTeam, RangePriceBaseTeam, RangeDieselTeam,  
            RangePriceTeamByRoute, RangePriceTeamByCompany, User };

use Illuminate\Support\Facades\Validator;

use Auth;
use DateTime;
use DB;
use Session;

class PaymentTeamController extends Controller
{
    public function Index()
    {
        $files     = [];
        $nowDate   = date('Y-07-02');
        $startDate = date('Y-06-01');
        $endDate   = date('Y-m-d', strtotime($nowDate .' -2 day'));

        /*try
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

                $listPackageDelivery = PackageDispatch::whereBetween('Date_Delivery', [$startDate, $endDate])
                                                        ->where('idTeam', $team->id)
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

                    if($totalTeam > 0)
                    {
                        $paymentTeam->total  = $totalTeam;
                        $paymentTeam->status = 'Payable';
                        $paymentTeam->save();
                    }
                }
            }

            DB::commit();

            dd("correct");
        }
        catch(Exception $e)
        {
            DB::rollback();

            dd("error");
        }*/

        return view('payment.payment');
    }
    
    public function List($dateInit, $endDate, $idTeam, $status)
    {
        $dateInit = $dateInit .' 00:00:00';
        $endDate  = $endDate .' 23:59:59';

        $paymentList = PaymentTeam::with('team')->whereBetween('created_at', [$dateInit, $endDate]);

        if($idTeam)
        {
            $paymentList = $paymentList->where('idTeam', $idTeam);
        }

        if($status != 'all')
        {
            $paymentList = $paymentList->where('status', $status);
        }

        $totalPayments = $paymentList->get()->sum('total');
        $paymentList   = $paymentList->orderBy('total', 'desc')->paginate(100);

        return [
            'totalPayments' => number_format($totalPayments, 4),
            'paymentList' => $paymentList,
        ];
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

    public function Export($idPayment)
    {
        $payment = PaymentTeam::with('team')->find($idPayment);

        $delimiter = ",";
        $filename  = "PAYMENT - TEAM  " . $payment->id . ".csv";
        $file      = fopen('php://memory', 'w');
        
        $fields = array('DATE', 'DATE DELIVERY', 'PACKAGE ID', 'TEAM', 'DIM FACTOR', 'WEIGHT', 'DIM WEIGHT ROUND', 'PRICE WEIGHT', 'PEAKE SEASON PRICE', 'PRICE BASE', 'DIESEL PRICE', 'SURCHARGE PERCENTAGE', 'SURCHAGE PRICE', 'PRICE BY ROUTE', 'PRICE BY COMPANY', 'TOTAL PRICE');

        fputcsv($file, $fields, $delimiter);

        $paymentTeamDetailList = PaymentTeamDetail::where('idPaymentTeam', $idPayment)->get();

        foreach($paymentTeamDetailList as $paymentDetail)
        {
            $date         = date('m-d-Y', strtotime($paymentDetail->created_at)) .' '. date('H:i:s', strtotime($paymentDetail->created_at));
            $dateDelivery = date('m-d-Y', strtotime($paymentDetail->Date_Delivery)) .' '. date('H:i:s', strtotime($paymentDetail->Date_Delivery));

            $lineData = array(

                $date,
                $dateDelivery,
                $paymentDetail->Reference_Number_1,
                $payment->team->name,
                $paymentDetail->dimFactor,
                $paymentDetail->weight,
                $paymentDetail->weightRound,
                $paymentDetail->priceWeight,
                $paymentDetail->peakeSeasonPrice,
                $paymentDetail->priceBase,
                $paymentDetail->dieselPrice,
                $paymentDetail->surchargePercentage,
                $paymentDetail->surchargePrice,
                $paymentDetail->priceByRoute,
                $paymentDetail->priceByCompany,
                $paymentDetail->totalPrice,
            );

            fputcsv($file, $lineData, $delimiter);
        }
 
        fseek($file, 0);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '";');

        fpassthru($file);
    }

    public function Confirm($idPayment)
    {
        $paymentTeam = PaymentTeam::find($idPayment);
        $paymentTeam->idUser = Auth::user()->id;
        $paymentTeam->status = 'Paid';
        $paymentTeam->save();

        return ['stateAction' => true];
    }
}