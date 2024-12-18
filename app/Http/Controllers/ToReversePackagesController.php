<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{ PaymentTeam, PaymentTeamDetail, PaymentTeamDetailReturn, ToReversePackages };

use Illuminate\Support\Facades\Validator;

use Auth;
use DateTime;
use DB;
use Session;

class ToReversePackagesController extends Controller
{
    public function Index()
    {
        return view('revert-payment.index');
    }

    public function List($dateInit, $endDate, $idTeam, $status)
    {
        $dateInit = $dateInit .' 00:00:00';
        $endDate  = $endDate .' 23:59:59';

        $toReversePackagesList = ToReversePackages::with('team')->whereBetween('created_at', [$dateInit, $endDate]);

        if($idTeam)
        {
            $toReversePackagesList = $toReversePackagesList->where('idTeam', $idTeam);
        }

        if($status != 'all')
        {
            $toReversePackagesList = $toReversePackagesList->where('status', $status);
        }

        $totalReverts          = $toReversePackagesList->get()->sum('priceToRevert');
        $toReversePackagesList = $toReversePackagesList->orderBy('created_at', 'desc')->paginate(100);

        return [
            'totalReverts' => number_format($totalReverts, 4),
            'toReversePackagesList' => $toReversePackagesList,
        ];
    }

    public function Insert(Request $request)
    {        
        $paymentTeamDetail = PaymentTeamDetail::with('payment')->find($request->Reference_Number_1);

        if(!$paymentTeamDetail)
            return response()->json(['statusCode' => 'notExists']);

        if($paymentTeamDetail->payment->status != 'PAID')
            return response()->json(['statusCode' => 'notInPaid', 'idPaymentTeam' => $paymentTeamDetail->payment->id]);

        try
        {
            DB::commit();

            $paymentTeamDetailReturn = new PaymentTeamDetailReturn();
            $paymentTeamDetailReturn->id                  = uniqid();
            $paymentTeamDetailReturn->Reference_Number_1  = $paymentTeamDetail->Reference_Number_1;
            $paymentTeamDetailReturn->Route               = $paymentTeamDetail->Route;
            $paymentTeamDetailReturn->idPaymentTeam       = $paymentTeamDetail->idPaymentTeam;
            $paymentTeamDetailReturn->dimFactor           = $paymentTeamDetail->dimFactor;
            $paymentTeamDetailReturn->weight              = $paymentTeamDetail->weight;
            $paymentTeamDetailReturn->weightRound         = $paymentTeamDetail->weightRound;
            $paymentTeamDetailReturn->priceWeight         = $paymentTeamDetail->priceWeight;
            $paymentTeamDetailReturn->peakeSeasonPrice    = $paymentTeamDetail->peakeSeasonPrice;
            $paymentTeamDetailReturn->priceBase           = $paymentTeamDetail->priceBase;
            $paymentTeamDetailReturn->dieselPrice         = $paymentTeamDetail->dieselPrice;
            $paymentTeamDetailReturn->surchargePercentage = $paymentTeamDetail->surchargePercentage;
            $paymentTeamDetailReturn->surchargePrice      = $paymentTeamDetail->surchargePrice;
            $paymentTeamDetailReturn->priceByRoute        = $paymentTeamDetail->priceByRoute;
            $paymentTeamDetailReturn->priceByCompany      = $paymentTeamDetail->priceByCompany;
            $paymentTeamDetailReturn->totalPrice          = $paymentTeamDetail->totalPrice;
            $paymentTeamDetailReturn->Date_Delivery       = $paymentTeamDetail->Date_Delivery;
            $paymentTeamDetailReturn->Reason              = $request->Reason;
            $paymentTeamDetailReturn->created_at          = date('Y-m-d H:i:s');
            $paymentTeamDetailReturn->updated_at          = date('Y-m-d H:i:s');
            $paymentTeamDetailReturn->save();

            $paymentTeam = PaymentTeam::find($paymentTeamDetail->idPaymentTeam);
            $paymentTeam->totalDelivery = $paymentTeam->totalDelivery - $paymentTeamDetail->totalPrice;
            $paymentTeam->save();

            $toReversePackages = new ToReversePackages();
            $toReversePackages->shipmentId    = $paymentTeamDetail->Reference_Number_1;
            $toReversePackages->idPaymentTeam = $paymentTeamDetail->idPaymentTeam;
            $toReversePackages->idTeam        = $paymentTeam->idTeam;
            $toReversePackages->priceToRevert = -$paymentTeamDetail->totalPrice;
            $toReversePackages->reason        = $request->Reason;
            $toReversePackages->save();

            $paymentTeamDetail->delete();

            DB::commit();

            return response()->json(['statusCode' => true]);
        }
        catch(Exception $e)
        {
            DB::rollback();

            return response()->json(['statusCode' => 'error'], 500);
        }
    }
}