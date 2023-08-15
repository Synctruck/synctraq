<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{ ChargeCompany, ChargeCompanyAdjustment };

use Illuminate\Support\Facades\Validator;

use Auth;
use DateTime;
use DB;
use Session;

class ChargeCompanyAdjustmentController extends Controller
{
    public function List($idCharge)
    {
        return [
            'listAdjustment' => ChargeCompanyAdjustment::where('idCharge', $idCharge)
                                                    ->orderBy('created_at', 'asc')
                                                    ->get()
        ];
    }

    public function Insert(Request $request)
    {
        try
        {
            DB::beginTransaction();

            $chargeCompany = ChargeCompany::find($request->idCharge);
            $chargeCompany->totalRevert = $chargeCompany->totalRevert + $request->amount;
            $chargeCompany->total       = $chargeCompany->totalDelivery + $chargeCompany->totalRevert;
            $chargeCompany->save();

            $chargeCompanyAdjustment = new ChargeCompanyAdjustment();
            $chargeCompanyAdjustment->id          = uniqid();
            $chargeCompanyAdjustment->idCharge    = $request->idCharge;
            $chargeCompanyAdjustment->amount      = $request->amount;
            $chargeCompanyAdjustment->description = $request->description;
            $chargeCompanyAdjustment->save();

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