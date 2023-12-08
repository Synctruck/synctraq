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
    
    public function List($dateStart, $dateEnd, $idTeam, $status)
    {
        $data = $this->GetDataListExport($dateStart, $dateEnd, $idTeam, $status, 'list');

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

                $team                = User::find($payment->idTeam);
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
                $paymentDetail->Date_Delivery       = $paymentDetail->Date_Delivery;
                $paymentDetail->save();

                $totalPieces = $totalPieces + 1;
                $totalTeam   = $totalTeam + $totalPrice;
            }

            $totalAdjustment = PaymentTeamAdjustment::where('idPaymentTeam', $idPayment)
                                                    ->get('amount')
                                                    ->sum('amount');

            $payment->totalPieces     = $totalPieces;
            $payment->totalDelivery   = $totalTeam;
            $payment->totalAdjustment = $totalAdjustment;
            $payment->total           = $totalTeam + $totalAdjustment;
            $payment->averagePrice    = $totalTeam / $totalPieces;
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

        Log::info('idTeam => '. $idTeam .' idCompany => '. $idCompany .' route => '. $route .' idRangeRate => '. $idRangeRate);
        Log::info('rangeByRoute => '. $rangeByRoute);
        $priceCompanyTeam = $rangeByCompanyTeam ? $rangeByCompanyTeam->price : 0;
        $priceRate        = $rangeByRate ? $rangeByRate->price : 0;
        $priceCompany     = $rangeByCompany ? $rangeByCompany->price : 0;
        $priceTeam        = $rangeByRoute ? $rangeByRoute->price : 0;
        $totalPrices      = $priceCompanyTeam + $priceRate + $priceCompany + $priceTeam;

        return $totalPrices;
    }
    
    public function ListByRoute($idPayment) 
    {
        $paymentTeamDetailRouteList = PaymentTeamDetail::where('idPaymentTeam', $idPayment)
                                                ->where('podFailed', 0)
                                                ->select('Route', DB::raw('COUNT(Route) as totalPieces'),  DB::raw('SUM(totalPrice) as totalRoute'))
                                                ->groupBy('Route', 'totalPrice')
                                                ->get();

        return ['paymentTeamDetailRouteList' => $paymentTeamDetailRouteList];
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

    public function GetDataListExport($dateStart, $dateEnd, $idTeam, $status, $typeAction)
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
        $paymentList   = $paymentList->orderBy('created_at', 'desc');

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

        fputcsv($file, array('DATE', 'DATE DELIVERY', 'PACKAGE ID', 'INVALID POD', 'REVERTED', 'ROUTE', 'DIM FACTOR', 'WEIGHT', 'DIM WEIGHT ROUND', 'PRICE WEIGHT', 'PEAKE SEASON PRICE', 'PRICE BASE', 'DIESEL PRICE', 'SURCHARGE PERCENTAGE', 'SURCHAGE PRICE', 'PRICE BY ROUTE', 'PRICE BY COMPANY', 'TOTAL PRICE'), $delimiter);

        $paymentTeamDetailList = PaymentTeamDetail::where('idPaymentTeam', $idPayment)->get();

        $totalDelivery = 0;

        foreach($paymentTeamDetailList as $paymentDetail)
        {
            $date         = date('m-d-Y', strtotime($paymentDetail->created_at)) .' '. date('H:i:s', strtotime($paymentDetail->created_at));
            $dateDelivery = date('m-d-Y', strtotime($paymentDetail->Date_Delivery)) .' '. date('H:i:s', strtotime($paymentDetail->Date_Delivery));

            $lineData = array(

                $date,
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

        fputcsv($file, array('', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'TOTAL DELIVERY', $totalDeliveryRevert), $delimiter);

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

    public function ExportAll($dateStart, $dateEnd, $idCompany, $status)
    {
        $data = $this->GetDataListExport($dateStart, $dateEnd, $idCompany, $status, 'export');

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
                date('m-d-Y', strtotime($payment->startDate)),
                date('m-d-Y', strtotime($payment->endDate)),
                $payment->totalPieces,
                $payment->totalAdjustment,
                $payment->totalDelivery,
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

            $startDate = date('Y-m-08 00:00:00');
            $endDate   = date('Y-m-09 23:59:59');

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
}