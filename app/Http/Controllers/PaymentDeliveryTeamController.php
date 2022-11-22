<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{ PackageDelivery, PackageDispatch, PackageHistory, PaymentTeamReturn, User };

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
                                                    ->whereBetween('updated_at', [$dateInit, $dateEnd])
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

            //update payment and return, prices totals
            $team = User::find($request->get('idTeam'));

            $team->totalDelivery = ($team->totalDelivery + $totalDelivery) - $team->totalReturn;

            $team->save();
            //===============================

            $paymentTeam = new PaymentTeamReturn();

            $paymentTeam->id            = $idPaymentTeam;
            $paymentTeam->idTeam        = $request->get('idTeam');
            $paymentTeam->idUser        = Auth::user()->id;
            $paymentTeam->startDate     = $request->get('startDate');
            $paymentTeam->endDate       = $request->get('endDate');
            $paymentTeam->totalDelivery = $totalDelivery;
            $paymentTeam->refund        = $team->totalReturn;
            $paymentTeam->total         = $paymentTeam->totalDelivery - $paymentTeam->refund;

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

    public function IndexPayment()
    {
        return view('paymentcompanyteam.payment');
    }

    public function PaymentList($dateStart, $dateEnd, $idTeam)
    {
        $dateStart = $dateStart .' 00:00:00';
        $dateEnd   = $dateEnd .' 23:59:59';

        $paymentList = PaymentTeamReturn::whereBetween('created_at', [$dateStart, $dateEnd]);

        if($idTeam != 0)
        {
            $paymentList = $paymentList->where('idTeam', $idTeam);
        }

        $totalPayment = $paymentList->get()->sum('total');
        $paymentList  = $paymentList->orderBy('created_at', 'desc')
                                    ->paginate(50);

        return ['paymentList' => $paymentList, 'totalPayment' => number_format($totalPayment, 4)];
    }
}