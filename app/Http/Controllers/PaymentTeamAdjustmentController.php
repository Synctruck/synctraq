<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{ PaymentTeam, PaymentTeamAdjustment };

use Illuminate\Support\Facades\Validator;

use Auth;
use DateTime;
use DB;
use Session;

class PaymentTeamAdjustmentController extends Controller
{
    public function List($idPaymentTeam)
    {
        return [
            'listAdjustment' => PaymentTeamAdjustment::where('idPaymentTeam', $idPaymentTeam)
                                                    ->orderBy('created_at', 'asc')
                                                    ->get()
        ];
    }

    public function Insert(Request $request)
    {
        try
        {
            DB::beginTransaction();

            $paymentTeam = PaymentTeam::find($request->idPaymentTeam);
            $paymentTeam->totalAdjustment = $paymentTeam->totalAdjustment + $request->amount;
            $paymentTeam->total           = $paymentTeam->totalDelivery + $paymentTeam->totalRevert + $paymentTeam->totalAdjustment;
            $paymentTeam->save();

            $paymentTeamAdjustment = new PaymentTeamAdjustment();
            $paymentTeamAdjustment->id            = uniqid();
            $paymentTeamAdjustment->idPaymentTeam = $request->idPaymentTeam;
            $paymentTeamAdjustment->amount        = $request->amount;
            $paymentTeamAdjustment->description   = $request->description;
            $paymentTeamAdjustment->save();

            DB::commit();

            return ['statusCode' => true];
        }
        catch(Exception $e)
        {
            DB::rollback();

            return ['statusCode' => false];
        }
    }
}