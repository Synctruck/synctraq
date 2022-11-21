<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{ PackageDelivery, PackageDispatch, PackageHistory, PaymentTeamReturn };

use Illuminate\Support\Facades\Validator;

use Auth;
use DB;
use Session;

class PaymentDeliveryTeamController extends Controller
{
    public function Index()
    {
        return view('paymentcompanyteam.team');
    }

    public function List($dateInit, $dateEnd, $idTeam, $idDriver, $route, $state)
    {
        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);

        $listAll = PackageDispatch::with(['driver.role', 'driver', 'package_histories'])
                                ->where('idPaymentTeam', '')
                                ->whereBetween('created_at', [$dateInit, $dateEnd])
                                ->where('status', 'Delivery');

        if($idTeam != 0)
        {
            $listAll = $listAll->where('idTeam', $idTeam);
        }

        if($route != 'all')
        {
            $listAll = $listAll->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $listAll = $listAll->whereIn('Dropoff_Province', $states);
        }

        $totalPriceTeam = $listAll->get()->sum('pricePaymentTeam');

        $listAll = $listAll->with(['team', 'driver'])
                            ->orderBy('created_at', 'desc')
                            ->paginate(50);

        $roleUser = Auth::user()->role->name;

        $listState = PackageHistory::select('Dropoff_Province')
                                    ->where('dispatch', 1)
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        return ['reportList' => $listAll, 'totalPriceTeam' => $totalPriceTeam, 'listState' => $listState, 'roleUser' => $roleUser];
    }

    public function Insert(Request $request)
    {
        $sunday   = date("w", strtotime($request->get('startDate')));
        $saturday = date("w", strtotime($request->get('endDate')));

        if($sunday != 0 || $saturday != 6)
        {
            return ['stateAction' => 'incorrectDate'];
        }

        $paymentTeam = PaymentTeamReturn::where('startDate', $request->get('startDate'))
                                        ->where('endDate', $request->get('endDate'))
                                        ->where('idTeam', $request->get('idTeam'))
                                        ->first();

        if($paymentTeam != null)
        {
            return ['stateAction' => 'paymentExists'];
        }

        try
        {
            DB::beginTransaction();

            $idPaymentTeam = date('Y-m-d-H-i-s');
            $dateInit      = $request->get('startDate') .' 00:00:00';
            $dateEnd       = $request->get('endDate') .' 23:59:59';


            $listPackageDelivery = PackageDispatch::where('idPaymentTeam', '')
                                                    ->where('idTeam', $request->get('idTeam'))
                                                    ->whereBetween('created_at', [$dateInit, $dateEnd])
                                                    ->where('status', 'Delivery')
                                                    ->get();

            $totalDelivery = 0;

            foreach($listPackageDelivery as $delivery)
            {
                $packageDelivery = PackageDispatch::find($delivery->Reference_Number_1);

                $packageDelivery->idPaymentTeam = $idPaymentTeam;

                $packageDelivery->save();

                $totalDelivery = $totalDelivery + $packageDelivery->pricePaymentTeam;
            }

            $paymentTeam = new PaymentTeamReturn();

            $paymentTeam->id            = $idPaymentTeam;
            $paymentTeam->idTeam        = $request->get('idTeam');
            $paymentTeam->idUser        = Auth::user()->id;
            $paymentTeam->startDate     = $request->get('startDate');
            $paymentTeam->endDate       = $request->get('endDate');
            $paymentTeam->totalDelivery = $totalDelivery;
            $paymentTeam->total         = $totalDelivery;

            $paymentTeam->save();

            DB::commit();

            return ['stateAction' => true];
        }
        catch(Exception $e)
        {
            DB::rollback();

            return ['stateAction' => false];
        }
    }
}