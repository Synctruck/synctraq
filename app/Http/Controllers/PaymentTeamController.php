<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{ 
            Configuration, HistoryDiesel, PaymentTeam, PaymentTeamDetail, PaymentTeamAdjustment, PaymentTeamDetailReturn, 
            PackageDispatch, PeakeSeasonTeam, RangePriceBaseTeam, RangeDieselTeam,  
            RangePriceTeamByRoute, RangePriceTeamByCompany, User };

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;


use Auth;
use DateTime;
use DB;
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
                
                SendToTeam('Packages Warehouse', $filename,  $idPayment);
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
        
        fputcsv($file, array('DATE', 'ID INVOICE', 'TEAM', 'START DATE', 'END DATE', 'PIECES', 'TOTAL DELIVERY', 'TOTAL', 'AVERAGE PRICE', 'STATUS'), $delimiter);

        foreach($paymentList as $payment)
        {
            $lineData = array(

                date('m-d-Y', strtotime($payment->created_at)) .' '. date('H:i:s', strtotime($payment->created_at)),
                $payment->id,
                $payment->team->name,
                date('m-d-Y', strtotime($payment->startDate)),
                date('m-d-Y', strtotime($payment->endDate)),
                $payment->totalPieces,
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
}