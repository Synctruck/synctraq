<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{
            Configuration, HistoryDiesel, PaymentTeam, PaymentTeamDetail, PaymentTeamAdjustment, PaymentTeamDetailReturn,
            PackageDispatch, PackageHistory, PeakeSeasonTeam, RangePriceBaseTeam, RangeDieselTeam,
            RangePriceTeamByRoute, RangePriceTeamByCompany, User };

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;


use Auth;
use DateTime;
use DB;
use Log;
use Session;

class PaymentTeamController extends Controller
{
    public function Index()
    {
        return view('payment.payment');
    }

    public function List($dateStart, $initDate, $dateEnd, $idTeam, $status)
    {
        $data = $this->GetDataListExport($dateStart, $initDate, $dateEnd, $idTeam, $status, 'list');

        $paymentList   = $data['paymentList'];
        $totalPayments = $data['totalPayments'];

        return ['paymentList' => $paymentList, 'totalPayments' => number_format($totalPayments, 4)];
    }

    public function Edit($idPayment)
    {
        $payment = PaymentTeam::with('team')->find($idPayment);

        return view('payment.edit', compact('payment'));
    }

    public function Recalculate($idPayment)
    {
        try
        {
            DB::beginTransaction();

            $payment           = PaymentTeam::with(['team'])->find($idPayment);
            $paymentDetailList = PaymentTeamDetail::where('idPaymentTeam', $idPayment)->get();
            $totalPieces = 0;
            $totalTeam   = 0;
            $totalDeduction = 0;
            $team = User::find($payment->idTeam);

            foreach($paymentDetailList as $paymentDetail)
            {
                $dimFactor   = 200;
                $weight      = $paymentDetail->weight;
                $weightRound = ceil($weight);

                $dieselPrice = $this->GetDieselPrice($paymentDetail->Date_Delivery);

                $range = RangePriceBaseTeam::where('idTeam', $payment->idTeam)
                                                ->where('minWeight', '<=', $weightRound)
                                                ->where('maxWeight', '>=', $weightRound)
                                                ->first();


                $priceWeight         = $range->price;
                $peakeSeasonPrice    = $this->GetPeakeSeasonTeam($payment);
                $priceBase           = number_format($priceWeight + $peakeSeasonPrice, 2);

                if($team->surcharge)
                {
                    $surchargePercentage = $this->GetSurchargePercentage($payment->idTeam, $dieselPrice);
                    $surchargePrice      = number_format(($priceBase * $surchargePercentage) / 100, 4);
                }
                else
                {
                    $surchargePercentage = 0;
                    $surchargePrice      = 0;
                }

                $packageHistory = PackageHistory::where('Reference_Number_1', $paymentDetail->Reference_Number_1)->first();

                $priceByCompany      = $this->GetPriceTeamByCompany($payment->idTeam, $packageHistory->idCompany, $packageHistory->Route, $range->id);
                $totalPrice          = number_format($priceBase + $surchargePrice + $priceByCompany, 4);

                $paymentDetail = PaymentTeamDetail::where('Reference_Number_1', $paymentDetail->Reference_Number_1)->first();
                $paymentDetail->Route               = $packageHistory->Route;
                $paymentDetail->dimFactor           = $dimFactor;
                $paymentDetail->weight              = $weight;
                $paymentDetail->weightRound         = $weightRound;
                $paymentDetail->priceWeight         = $priceWeight;
                $paymentDetail->peakeSeasonPrice    = $peakeSeasonPrice;
                $paymentDetail->priceBase           = $priceBase;
                $paymentDetail->dieselPrice         = $dieselPrice;
                $paymentDetail->surchargePercentage = $surchargePercentage;
                $paymentDetail->surchargePrice      = $surchargePrice;
                $paymentDetail->priceByRoute        = 0;
                $paymentDetail->priceByCompany      = $priceByCompany;
                $paymentDetail->totalPrice          = $totalPrice;

                $Date_Dispatch = '';

                $packageDelivery = PackageDispatch::where('Reference_Number_1', $paymentDetail->Reference_Number_1)
                                                    ->where('status', 'Delivery')
                                                    ->first();

                $deduction = 0;

                if($team->sla)
                {
                    if($packageDelivery)
                    {
                        if($packageDelivery->Date_Dispatch)
                            $Date_Dispatch = $packageDelivery->Date_Dispatch;
                            $deduction = $this->CalculateDeduction($packageDelivery->Date_Dispatch, $packageDelivery->Date_Delivery, $packageDelivery->Route, $team->slaRoutes, $team->slaDeduction);
                    }
                }

                $paymentDetail->Date_Dispatch = $Date_Dispatch ? $Date_Dispatch : $paymentDetail->Date_Delivery;
                $paymentDetail->Date_Delivery = $paymentDetail->Date_Delivery;
                $paymentDetail->priceDeduction = -$deduction;
                $paymentDetail->save();

                $totalPieces = $totalPieces + 1;
                $totalTeam   = $totalTeam + $totalPrice;
                $totalDeduction = $totalDeduction - $deduction;
            }

            $totalAdjustment = PaymentTeamAdjustment::where('idPaymentTeam', $idPayment)
                                                    ->get('amount')
                                                    ->sum('amount');

            $payment->totalPieces     = $totalPieces;
            $payment->totalDelivery   = $totalTeam;
            $payment->totalAdjustment = $totalAdjustment;
            $payment->totalDeduction  = $totalDeduction;
            $payment->total           = $totalTeam + $totalAdjustment + $totalDeduction;
            $payment->averagePrice    = $totalPieces > 0 ? $totalTeam / $totalPieces : 0;
            $payment->surcharge       = $team->surcharge;
            $payment->save();

            DB::commit();

            return ['statusCode' => true];
        }
        catch(Exception $e)
        {
            DB::rollback();

            return ['statusCode' => false];
        }
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

        $rangeByCompanyRate = RangePriceTeamByCompany::where('idTeam', $idTeam)
                                    ->where('idCompany', $idCompany)
                                    ->where('idRangeRate', $idRangeRate)
                                    ->where('route', '')
                                    ->first();

        $rangeByCompanyRoute = RangePriceTeamByCompany::where('idTeam', $idTeam)
                                    ->where('idCompany', $idCompany)
                                    ->where('idRangeRate', 0)
                                    ->where('route', $route)
                                    ->first();

        $rangeByRateRoute = RangePriceTeamByCompany::where('idTeam', $idTeam)
                                    ->where('idCompany', 0)
                                    ->where('idRangeRate', $idRangeRate)
                                    ->where('route', $route)
                                    ->first();

        $priceCompanyTeam  = $rangeByCompanyTeam ? $rangeByCompanyTeam->price : 0;
        $priceRate         = $rangeByRate ? $rangeByRate->price : 0;
        $priceCompany      = $rangeByCompany ? $rangeByCompany->price : 0;
        $priceTeam         = $rangeByRoute ? $rangeByRoute->price : 0;
        $priceCompanyRate  = $rangeByCompanyRate ? $rangeByCompanyRate->price : 0;
        $priceCompanyRoute = $rangeByCompanyRoute ? $rangeByCompanyRoute->price : 0;
        $priceRateRoute    = $rangeByRateRoute ? $rangeByRateRoute->price : 0;
        $totalPrices       = $priceCompanyTeam + $priceRate + $priceCompany + $priceTeam + $priceCompanyRate + $priceCompanyRoute + $priceRateRoute;

        return $totalPrices;
    }

    public function ListByRoute($idPayment)
    {
        $paymentTeamDetailRouteList = PaymentTeamDetail::where('idPaymentTeam', $idPayment)
                                                ->where('podFailed', 0)
                                                ->select('Route', DB::raw('COUNT(Route) as totalPieces'),  DB::raw('SUM(totalPrice) as totalRoute'))
                                                ->groupBy('Route', 'totalPrice')
                                                ->get();

        $totalDeduction = PaymentTeamDetail::where('idPaymentTeam', $idPayment)
                                        ->select(DB::raw('SUM(priceDeduction) as totalDeduction'))
                                        ->first();

        return ['paymentTeamDetailRouteList' => $paymentTeamDetailRouteList, 'totalDeduction' => $totalDeduction];
    }

    public function InserPODFailed(Request $request)
    {
        try
        {
            DB::beginTransaction();

            $paymentDetail = PaymentTeamDetail::find($request->get('Reference_Number_1'));

            $payment = PaymentTeam::find($paymentDetail->idPaymentTeam);
            $payment->totalDelivery = $payment->totalDelivery - $paymentDetail->totalPrice;
            $payment->total = $payment->totalDelivery + $payment->totalAdjustment;
            $payment->save();

            $paymentDetail->podFailed = 1;
            $paymentDetail->totalPricePODFailed = $paymentDetail->totalPrice;
            $paymentDetail->totalPrice = 0.00;
            $paymentDetail->save();

            DB::commit();

            return ['statusCode' => true];
        }
        catch(Exception $e)
        {
            DB::rollback();

            return ['statusCode' => true];
        }
    }

    public function ListByPODFailed($idPayment)
    {
        $paymentTeamDetailPODFailedList = PaymentTeamDetail::where('idPaymentTeam', $idPayment)
                                                ->where('podFailed', 1)
                                                ->select('Reference_Number_1')
                                                ->get();

        return ['paymentTeamDetailPODFailedList' => $paymentTeamDetailPODFailedList];
    }

    public function ListRevertShipments($idPayment)
    {
        $paymentTeamDetailRevertShipmentsList = PaymentTeamDetailReturn::where('idPaymentTeam', $idPayment)->get();

        return ['paymentTeamDetailRevertShipmentsList' => $paymentTeamDetailRevertShipmentsList];
    }

    public function GetDataListExport($dateStart, $initDate, $dateEnd, $idTeam, $status, $typeAction)
    {
        $dateStart = $dateStart .' 00:00:00';
        $dateEnd   = $dateEnd .' 23:59:59';

        $paymentList = PaymentTeam::with('team')->whereBetween('created_at', [$dateStart, $dateEnd]);

        if($idTeam != 0)
        {
            $paymentList = $paymentList->where('idTeam', $idTeam);
        }

        if($status != 'all')
        {
            $paymentList = $paymentList->where('status', $status);
        }

        $totalPayments = $paymentList->get()->sum('total');
        $paymentList   = $paymentList->orderBy('created_at', 'asc');

        if($typeAction == 'list')
        {
            $paymentList = $paymentList->paginate(50);
        }
        else
        {
            $paymentList = $paymentList->get();
        }

        return ['totalPayments' => $totalPayments, 'paymentList' => $paymentList];
    }

    public function Export($idPayment)
    {
        $payment = PaymentTeam::with('team')->find($idPayment);

        $delimiter = ",";
        $filename  = "PAYMENT - TEAM  " . $payment->id . ".csv";
        $file      = fopen('php://memory', 'w');

        $fieldStartDate = array('START DATE', date('m/d/Y', strtotime($payment->startDate)));
        $fieldEndDate   = array('END DATE', date('m/d/Y', strtotime($payment->endDate)));
        $fieldIdPayment = array('ID PAYMENT', $idPayment);
        $fieldTeam      = array('TEAM', $payment->team->name);
        $fieldTeamTotal = array('INVOICE TOTAL', $payment->total);
        $fielBlank      = array('');

        fputcsv($file, $fieldStartDate, $delimiter);
        fputcsv($file, $fieldEndDate, $delimiter);
        fputcsv($file, $fieldIdPayment, $delimiter);
        fputcsv($file, $fieldTeam, $delimiter);

        if($payment->surcharge)
        {
            fputcsv($file, array('SURCHARGE', 'YES'), $delimiter);
        }

        fputcsv($file, $fieldTeamTotal, $delimiter);
        fputcsv($file, $fielBlank, $delimiter);
        fputcsv($file, array('DEDUCTIONS'), $delimiter);
        fputcsv($file, array('TOTAL DEDUCTIONS', $payment->totalDeduction), $delimiter);
        fputcsv($file, $fielBlank, $delimiter);

        $paymentTeamAdjustmentList = PaymentTeamAdjustment::where('idPaymentTeam', $idPayment)
                                                                ->orderBy('created_at', 'asc')
                                                                ->get();

        if(count($paymentTeamAdjustmentList) > 0)
        {
            fputcsv($file, array('ADJUSTMENT'), $delimiter);
            fputcsv($file, array('TOTAL ADJUSTMENT', $payment->totalAdjustment .' $'), $delimiter);
            fputcsv($file, array('DATE', 'DESCRIPTION', 'AMOUNT'), $delimiter);

            foreach($paymentTeamAdjustmentList as $chargeAdjustment)
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

        fputcsv($file, array('DATE', 'DATE DISPATCH', 'DATE DELIVERY', 'PACKAGE ID', 'INVALID POD', 'REVERTED', 'ROUTE', 'DIM FACTOR', 'WEIGHT', 'DIM WEIGHT ROUND', 'PRICE WEIGHT', 'PEAKE SEASON PRICE', 'PRICE BASE', 'DIESEL PRICE', 'SURCHARGE PERCENTAGE', 'SURCHAGE PRICE', 'PRICE BY ROUTE', 'PRICE BY COMPANY', 'PRICE DEDUCTION', 'TOTAL PRICE'), $delimiter);

        $paymentTeamDetailList = PaymentTeamDetail::where('idPaymentTeam', $idPayment)
                                                    ->orderBy('Date_Dispatch', 'asc')
                                                    ->get();

        $totalDelivery = 0;

        foreach($paymentTeamDetailList as $paymentDetail)
        {
            $date         = date('m-d-Y', strtotime($paymentDetail->created_at)) .' '. date('H:i:s', strtotime($paymentDetail->created_at));
            $dateDispatch = date('m-d-Y', strtotime($paymentDetail->Date_Dispatch)) .' '. date('H:i:s', strtotime($paymentDetail->Date_Dispatch));
            $dateDelivery = date('m-d-Y', strtotime($paymentDetail->Date_Delivery)) .' '. date('H:i:s', strtotime($paymentDetail->Date_Delivery));

            $lineData = array(

                $date,
                $dateDispatch,
                $dateDelivery,
                $paymentDetail->Reference_Number_1,
                ($paymentDetail->podFailed ? 'TRUE' : 'FALSE'),
                'FALSE',
                $paymentDetail->Route,
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
                $paymentDetail->priceDeduction,
                ($paymentDetail->podFailed ? 0.00 : $paymentDetail->totalPrice),
            );

            $totalDelivery = $totalDelivery + ($paymentDetail->podFailed ? 0.00 : $paymentDetail->totalPrice);

            fputcsv($file, $lineData, $delimiter);
        }

        $paymentTeamDetailReturnList = PaymentTeamDetailReturn::where('idPaymentTeam', $idPayment)->get();

        $totalRevert = 0;

        if(count($paymentTeamDetailReturnList) > 0)
        {
            foreach($paymentTeamDetailReturnList as $paymentDetailReturn)
            {
                $date         = date('m-d-Y', strtotime($paymentDetailReturn->created_at)) .' '. date('H:i:s', strtotime($paymentDetailReturn->created_at));
                $dateDelivery = date('m-d-Y', strtotime($paymentDetailReturn->Date_Delivery)) .' '. date('H:i:s', strtotime($paymentDetailReturn->Date_Delivery));

                $lineData = array(

                    $date,
                    $dateDelivery,
                    $paymentDetailReturn->Reference_Number_1,
                    'FALSE',
                    'TRUE',
                    $paymentDetailReturn->Route,
                    $paymentDetailReturn->dimFactor,
                    $paymentDetailReturn->weight,
                    $paymentDetailReturn->weightRound,
                    $paymentDetailReturn->priceWeight,
                    $paymentDetailReturn->peakeSeasonPrice,
                    $paymentDetailReturn->priceBase,
                    $paymentDetailReturn->dieselPrice,
                    $paymentDetailReturn->surchargePercentage,
                    $paymentDetailReturn->surchargePrice,
                    $paymentDetailReturn->priceByRoute,
                    $paymentDetailReturn->priceByCompany,
                    $paymentDetailReturn->totalPrice,
                );

                $totalRevert = $totalRevert + $paymentDetailReturn->totalPrice;

                fputcsv($file, $lineData, $delimiter);
            }
        }

        $totalDeliveryRevert = $totalDelivery + $totalRevert;

        fputcsv($file, array('', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'TOTAL DELIVERY', $totalDeliveryRevert), $delimiter);

        fseek($file, 0);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '";');

        fpassthru($file);
    }

    public function ExportReceipt($idPayment, $type)
    {

        $payment = PaymentTeam::with('team')->find($idPayment);

        $delimiter = ",";
        $filename = $type == 'download' ? "PAYMENT - RECEIPT - TEAM " . $payment->id . ".csv" : Auth::user()->id . "- PAYMENT - RECEIPT - TEAM.csv";
        $file = $type == 'download' ? fopen('php://memory', 'w') : fopen(public_path($filename), 'w');

        $fieldStartDate = array('START DATE', date('m/d/Y', strtotime($payment->startDate)));
        $fieldEndDate   = array('END DATE', date('m/d/Y', strtotime($payment->endDate)));
        $fieldIdPayment = array('ID PAYMENT', $idPayment);
        $fieldTeam      = array('TEAM', $payment->team->name);
        $fieldSurcharge = array('SURCHARGE', ($payment->surcharge ? 'YES' : 'NO'));
        $fieldNumberTransaction = array('PAYMENT CONFIRMATION CODE', $payment->numberTransaction);
        $fieldTeamTotal = array('INVOICE TOTAL', $payment->total);
        $fielBlank      = array('');

        fputcsv($file, $fieldStartDate, $delimiter);
        fputcsv($file, $fieldEndDate, $delimiter);
        fputcsv($file, $fieldIdPayment, $delimiter);
        fputcsv($file, $fieldTeam, $delimiter);

        if($payment->surcharge)
        {
            fputcsv($file, array('SURCHARGE', 'YES'), $delimiter);
        }

        fputcsv($file, $fieldNumberTransaction, $delimiter);
        fputcsv($file, $fieldTeamTotal, $delimiter);
        fputcsv($file, $fielBlank, $delimiter);

        $paymentTeamAdjustmentList = PaymentTeamAdjustment::where('idPaymentTeam', $idPayment)
                                                                ->orderBy('created_at', 'asc')
                                                                ->get();

        if(count($paymentTeamAdjustmentList) > 0)
        {
            fputcsv($file, array('ADJUSTMENT'), $delimiter);
            fputcsv($file, array('TOTAL ADJUSTMENT', $payment->totalAdjustment .' $'), $delimiter);
            fputcsv($file, array('DATE', 'DESCRIPTION', 'AMOUNT'), $delimiter);

            foreach($paymentTeamAdjustmentList as $chargeAdjustment)
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

        //LIST DELIVERIES
        fputcsv($file, array('DATE DELIVERY', 'PACKAGE ID', 'ROUTE', 'TOTAL PRICE'), $delimiter);

        $paymentTeamDetailList = PaymentTeamDetail::where('idPaymentTeam', $idPayment)
                                                    ->where('podFailed', 0)
                                                    ->get();

        $totalDelivery = 0;

        foreach($paymentTeamDetailList as $paymentDetail)
        {
            $dateDelivery = date('m-d-Y', strtotime($paymentDetail->Date_Delivery)) .' '. date('H:i:s', strtotime($paymentDetail->Date_Delivery));

            $lineData = array(

                $dateDelivery,
                $paymentDetail->Reference_Number_1,
                $paymentDetail->Route,
                $paymentDetail->totalPrice,
            );

            $totalDelivery = $totalDelivery + $paymentDetail->totalPrice;

            fputcsv($file, $lineData, $delimiter);
        }

        //LIST REVERTS
        $paymentTeamDetailReturnList = PaymentTeamDetailReturn::where('idPaymentTeam', $idPayment)->get();

        if(count($paymentTeamDetailReturnList) > 0)
        {
            foreach($paymentTeamDetailReturnList as $paymentDetailReturn)
            {
                $dateDelivery = date('m-d-Y H:i:s', strtotime($paymentDetailReturn->Date_Delivery));

                $lineData = array(

                    $dateDelivery,
                    $paymentDetailReturn->Reference_Number_1,
                    $paymentDetailReturn->Route,
                    $paymentDetailReturn->totalPrice,
                );

                $totalDelivery = $totalDelivery + $paymentDetailReturn->totalPrice;

                fputcsv($file, $lineData, $delimiter);
            }
        }

        fputcsv($file, array('', '', 'TOTAL DELIVERY', $totalDelivery), $delimiter);
        fputcsv($file, $fielBlank, $delimiter);

        //LIST POD FAILED
        $paymentPODFailedList = PaymentTeamDetail::where('idPaymentTeam', $idPayment)
                                                    ->where('podFailed', 1)
                                                    ->get();

        if(count($paymentPODFailedList) > 0)
        {
            fputcsv($file, array('INVALID PODS'), $delimiter);

            foreach($paymentPODFailedList as $paymentPODFailed)
            {
                $dateDelivery = date('m-d-Y', strtotime($paymentPODFailed->Date_Delivery)) .' '. date('H:i:s', strtotime($paymentPODFailed->Date_Delivery));

                $lineData = array(

                    $dateDelivery,
                    $paymentPODFailed->Reference_Number_1,
                    $paymentPODFailed->Route,
                    $paymentPODFailed->totalPrice,
                );

                fputcsv($file, $lineData, $delimiter);
            }
        }
        if($type == 'download')
        {
                fseek($file, 0);

                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="' . $filename . '";');

                fpassthru($file);
        }
        else
        {
                rewind($file);
                fclose($file);

                SendToTeam('Payment Team', $filename,  $idPayment);
                return ['stateAction' => true];
        }
    }

    public function StatusChange(Request $request, $idPayment, $status)
    {
        $paymentTeam = PaymentTeam::find($idPayment);

        if($status == 'PAYABLE')
        {
            $paymentTeam->idUserPayable = Auth::user()->id;
            $paymentTeam->status        = 'PAYABLE';
        }
        else if($status == 'PAID')
        {
            $paymentTeam->idUserPaid        = Auth::user()->id;
            $paymentTeam->numberTransaction = $request->numberTransaction;
            $paymentTeam->status            = 'PAID';
        }

        $paymentTeam->save();

        return ['stateAction' => true];
    }

    public function ExportAll($dateStart, $initDate, $dateEnd, $idCompany, $status)
    {
        $data = $this->GetDataListExport($dateStart,$initDate, $dateEnd, $idCompany, $status, 'export');

        $paymentList   = $data['paymentList'];
        $totalPayments = $data['totalPayments'];

        $delimiter = ",";
        $filename  = "PAYMENTS - TEAMS  " . date('Y-m-d H:i:s') . ".csv";
        $file      = fopen('php://memory', 'w');

        $fieldDate        = array('DATE', date('m/d/Y H:i:s'));
        $fietotalPayments = array('TOTAL PAYMENTS', $totalPayments .' $');
        $fielBlank        = array('');

        fputcsv($file, $fieldDate, $delimiter);
        fputcsv($file, $fietotalPayments, $delimiter);
        fputcsv($file, $fielBlank, $delimiter);

        fputcsv($file, array('DATE', 'ID INVOICE', 'TEAM', 'START DATE', 'END DATE', 'PIECES', 'TOTAL DELIVERY','TOTAL ADJUSTMENT', 'TOTAL', 'AVERAGE PRICE', 'STATUS'), $delimiter);

        foreach($paymentList as $payment)
        {
            $lineData = array(

                date('m-d-Y', strtotime($payment->created_at)) .' '. date('H:i:s', strtotime($payment->created_at)),
                $payment->id,
                $payment->team->name,
                date('m-d-Y', strtotime($payment->initDate)),
                date('m-d-Y', strtotime($payment->endDate)),
                $payment->totalPieces,
                $payment->totalDelivery,
                $payment->totalAdjustment,
                $payment->total,
                $payment->averagePrice,
                $payment->status
            );

            fputcsv($file, $lineData, $delimiter);
        }

        fseek($file, 0);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '";');

        fpassthru($file);
    }

    public function DeletePackagesDetail()
    {
        try
        {
            DB::beginTransaction();

            $startDate = date('Y-m-01 00:00:00');
            $endDate   = date('Y-m-01 23:59:59');

            $paymentDetailList = PaymentTeamDetail::whereBetween('created_at', [$startDate, $endDate])->get();

            foreach($paymentDetailList as $paymentDetail)
            {
                $packageDispatch = PackageDispatch::find($paymentDetail->Reference_Number_1);

                if($packageDispatch)
                {
                    $packageDispatch->paid = 0;
                    $packageDispatch->save();
                }

                $paymentDetail = PaymentTeamDetail::find($paymentDetail->Reference_Number_1);
                $paymentDetail->delete();
            }

            DB::commit();

            return "compleweted";
        }
        catch(Exception $e)
        {
            DB::rollback();

            return "error";
        }
    }

    public function CalculateDeduction($Date_Dispatch, $Date_Delivery, $packageRoute, $sla_Routes, $sla_Deduction)
    {
        $dateInit = strtotime($Date_Dispatch);
        $dateEnd = strtotime($Date_Delivery);
        $diff = abs($dateEnd - $dateInit) / 3600;
        $hours = (int)$diff;

        $slaRoutes = explode(',', $sla_Routes);
        $slaRoutes = array_map('trim', $slaRoutes);
        $deduction = 0.00;

        if(in_array($packageRoute, $slaRoutes))
            $deduction = $hours > 28 ? $sla_Deduction : 0.00;

        return $deduction;
    }

    public function CalculatePaymentByRoute()
    {
        $startDate = date('2023-02-01 00:00:00');
        $endDate = date('2023-12-31 23:59:59');

        /*$packageDispatchList = PackageDispatch::whereBetween('Date_Delivery', [$startDate, $endDate])
                                            ->where('status', 'Delivery')
                                            ->orderBy('Date_Delivery', 'asc')
                                            ->selectRaw('DATE(Date_Delivery) AS Fecha, Dropoff_Address_Line_1, COUNT(Date_Delivery) as TOTAL_PIECES')
                                            ->groupBy('Fecha', 'Dropoff_Address_Line_1')
                                            ->get();

        foreach($packageDispatchList as $packageDispatch)
        {
            echo $packageDispatch->Fecha .'=>'. $packageDispatch->Dropoff_Address_Line_1 .' => '. $packageDispatch->TOTAL_PIECES .'<br>';
        }*/
        $team = User::find(46);

        if($team)
        {
            if($team->configurationPay == 'Route')
            {
                $packageDispatchList = PackageDispatch::whereBetween('Date_Delivery', [$startDate, $endDate])
                                            ->where('status', 'Delivery')
                                            ->where('paid', 1)
                                            ->where('idTeam', 46)
                                            ->where('Date_Dispatch', '!=', null)
                                            ->selectRaw('Reference_Number_1, DATE(Date_Dispatch) as DATE_DELIVERY, Dropoff_Address_Line_1, idTeam, company, Date_Dispatch, Date_Delivery')
                                            ->orderBy('Date_Dispatch', 'asc')
                                            ->orderBy('Dropoff_Address_Line_1', 'asc')
                                            ->get();

                $stopsQuantity = [];
                $addressPackages = [];
                $routesDates = [];
                $pricePerStop = 0;
                dd($packageDispatchList);
                foreach($packageDispatchList as $packageDispatch)
                {
                    $stringSearch = $packageDispatch->DATE_DELIVERY . $packageDispatch->Dropoff_Address_Line_1;

                    array_push($stopsQuantity, $stringSearch);

                    if(!in_array($packageDispatch->DATE_DELIVERY, $routesDates))
                        array_push($routesDates, $packageDispatch->DATE_DELIVERY);
                }

                $stopsQuantity = array_count_values($stopsQuantity);
                $quantity = 0;

                $quantityRoutesDates = count($routesDates);
                $positionRoutesDates = 0;

                $priceBasePay = $team->basePay;

                foreach($packageDispatchList as $packageDispatch)
                {
                    if($routesDates[$positionRoutesDates] != $packageDispatch->DATE_DELIVERY){
                        echo  '.... priceBasePay '. $priceBasePay .'<br><br>';
                        $priceBasePay = $team->basePay;
                        $positionRoutesDates = $positionRoutesDates + 1;
                    }

                    $signature = isset($packageDispatch->signature) ? $team->signature : $team->signature;
                    $price = 0;
                    $stringSearch = $packageDispatch->DATE_DELIVERY . $packageDispatch->Dropoff_Address_Line_1;

                    if(in_array($stringSearch, $addressPackages))
                    {
                        array_push($addressPackages, $stringSearch);

                        $price = ($team->priceByPackage / $team->splitForAddPc) + $signature;
                        $quantity = $quantity + 1;

                        echo $quantity .' ';
                    }
                    else
                    {
                        array_push($addressPackages, $stringSearch);

                        $quantityPackages = $stopsQuantity[$stringSearch];
                        $discountGap = $this->GetDiscountGapBetweenTiers($quantityPackages, $team->gapBetweenTiers);
                        $price = ($team->baseRate - $discountGap) + $team->priceByPackage + $signature;
                        $quantity = 1;

                        echo $quantity .' ';
                    }

                    if($routesDates[$positionRoutesDates] == $packageDispatch->DATE_DELIVERY){
                        $priceBasePay = $priceBasePay - $price;
                    }

                    echo ' ====== '. $packageDispatch->Reference_Number_1 .'=>'. $packageDispatch->DATE_DELIVERY .' => '. $packageDispatch->Dropoff_Address_Line_1 .' = $'. $price .'<br>';

                    if(count($routesDates) - 1 == $positionRoutesDates)
                            echo  '.... priceBasePay '. $priceBasePay .'<br><br>';

                }
            }
        }
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
