<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{ 
            Configuration, HistoryDiesel, PaymentTeam, PaymentTeamDetail, PaymentTeamAdjustment, PaymentTeamDetailReturn, 
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
        return view('payment.payment');
    }
    
    public function List($dateStart, $dateEnd, $idTeam, $status)
    {
        $data = $this->GetDataListExport($dateStart, $dateEnd, $idTeam, $status, 'list');

        $paymentList   = $data['paymentList'];
        $totalPayments = $data['totalPayments'];

        return ['paymentList' => $paymentList, 'totalPayments' => number_format($totalPayments, 4)];
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
        
        $fieldDate      = array('DATE', date('m/d/Y H:i:s'));
        $fieldIdPayment = array('ID PAYMENT', $idPayment);
        $fieldTeam      = array('TEAM', $payment->team->name);
        $fieldSurcharge = array('SURCHARGE', ($payment->surcharge ? 'YES' : 'NO'));
        $fielBlank      = array('');

        fputcsv($file, $fieldDate, $delimiter);
        fputcsv($file, $fieldIdPayment, $delimiter);
        fputcsv($file, $fieldTeam, $delimiter);
        fputcsv($file, $fieldSurcharge, $delimiter);
        fputcsv($file, $fielBlank, $delimiter);

        $paymentTeamAdjustmentList = PaymentTeamAdjustment::where('idPaymentTeam', $idPayment)
                                                                ->orderBy('created_at', 'asc')
                                                                ->get();

        if(count($paymentTeamAdjustmentList) > 0)
        {
            fputcsv($file, array('ADJUSTMENT'), $delimiter);
            fputcsv($file, array('TOTAL ADJUSTMENT', $payment->totalRevert .' $'), $delimiter);
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

        fputcsv($file, array('DATE', 'DATE DELIVERY', 'PACKAGE ID', 'ROUTE', 'DIM FACTOR', 'WEIGHT', 'DIM WEIGHT ROUND', 'PRICE WEIGHT', 'PEAKE SEASON PRICE', 'PRICE BASE', 'DIESEL PRICE', 'SURCHARGE PERCENTAGE', 'SURCHAGE PRICE', 'PRICE BY ROUTE', 'PRICE BY COMPANY', 'TOTAL PRICE'), $delimiter);

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
                $paymentDetail->totalPrice,
            );

            $totalDelivery = $totalDelivery + $paymentDetail->totalPrice;

            fputcsv($file, $lineData, $delimiter);
        }
        
        $paymentTeamDetailReturnList = PaymentTeamDetailReturn::where('idPaymentTeam', $idPayment)->get();

        if(count($paymentTeamDetailReturnList) > 0)
        {
            fputcsv($file, array('', '', '', '', '', '', '', '', '', '', '', '', '', '', 'TOTAL DELIVERY', $totalDelivery), $delimiter);
            fputcsv($file, [], $delimiter);
            fputcsv($file, [], $delimiter);
            fputcsv($file, ['REVERTS'], $delimiter);

            fputcsv($file, array('DATE', 'DATE DELIVERY', 'PACKAGE ID', 'ROUTE', 'DIM FACTOR', 'WEIGHT', 'DIM WEIGHT ROUND', 'PRICE WEIGHT', 'PEAKE SEASON PRICE', 'PRICE BASE', 'DIESEL PRICE', 'SURCHARGE PERCENTAGE', 'SURCHAGE PRICE', 'PRICE BY ROUTE', 'PRICE BY COMPANY', 'TOTAL PRICE'), $delimiter);
            
            $totalRevert = 0;

            foreach($paymentTeamDetailReturnList as $paymentDetailReturn)
            {
                $date         = date('m-d-Y', strtotime($paymentDetailReturn->created_at)) .' '. date('H:i:s', strtotime($paymentDetailReturn->created_at));
                $dateDelivery = date('m-d-Y', strtotime($paymentDetailReturn->Date_Delivery)) .' '. date('H:i:s', strtotime($paymentDetailReturn->Date_Delivery));

                $lineData = array(

                    $date,
                    $dateDelivery,
                    $paymentDetailReturn->Reference_Number_1,
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

                $totalRevert = $totalRevert + $paymentDetail->totalPrice;

                fputcsv($file, $lineData, $delimiter);
            }

            fputcsv($file, array('', '', '', '', '', '', '', '', '', '', '', '', '', '', 'TOTAL REVERT', $totalRevert), $delimiter);
        }

        fseek($file, 0);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '";');

        fpassthru($file);
    }

    public function StatusChange($idPayment, $status)
    {
        $paymentTeam = PaymentTeam::find($idPayment);

        if($status == 'PAYABLE')
        {
            $paymentTeam->idUserPayable = Auth::user()->id;
            $paymentTeam->status        = 'PAYABLE';
        }
        else if($status == 'PAID')
        {
            $paymentTeam->idUserPaid = Auth::user()->id;
            $paymentTeam->status     = 'PAID';
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
        fputcsv($file, $fietotalCharges, $delimiter);
        fputcsv($file, $fielBlank, $delimiter);
        
        fputcsv($file, array('DATE', 'ID INVOICE', 'TEAM', 'START DATE', 'END DATE', 'TOTAL DELIVERY', 'TOTAL REVERT', 'TOTAL', 'STATUS'), $delimiter);

        foreach($paymentList as $payment)
        {
            $lineData = array(

                date('m-d-Y', strtotime($payment->created_at)) .' '. date('H:i:s', strtotime($payment->created_at)),
                $payment->id,
                $payment->team->name,
                date('m-d-Y', strtotime($payment->startDate)),
                date('m-d-Y', strtotime($payment->endDate)),
                $payment->totalDelivery,
                $payment->totalRevert,
                $payment->total,
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