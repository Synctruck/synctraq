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
        $paymentList   = $paymentList->orderBy('created_at', 'desc')
                                    ->orderBy('total', 'desc')
                                    ->paginate(100);

        return [
            'totalPayments' => number_format($totalPayments, 4),
            'paymentList' => $paymentList,
        ];
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
        $fielBlank      = array('');

        fputcsv($file, $fieldDate, $delimiter);
        fputcsv($file, $fieldIdPayment, $delimiter);
        fputcsv($file, $fieldTeam, $delimiter);
        fputcsv($file, $fielBlank, $delimiter);

        $fields = array('DATE', 'DATE DELIVERY', 'PACKAGE ID', 'ROUTE', 'DIM FACTOR', 'WEIGHT', 'DIM WEIGHT ROUND', 'PRICE WEIGHT', 'PEAKE SEASON PRICE', 'PRICE BASE', 'DIESEL PRICE', 'SURCHARGE PERCENTAGE', 'SURCHAGE PRICE', 'PRICE BY ROUTE', 'PRICE BY COMPANY', 'TOTAL PRICE');

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