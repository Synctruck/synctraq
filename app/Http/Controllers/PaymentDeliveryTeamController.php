<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{ PackageDelivery, PackageDispatch, PackageHistory, PackagePriceCompanyTeam, PackageReturn, PaymentTeamReturn, RangePriceBaseTeam, RangeDieselTeam, User };

use Illuminate\Support\Facades\Validator;

use Auth;
use DB;
use Session;
use DateTime;

class PaymentDeliveryTeamController extends Controller
{
    public function Index()
    {
        return view('paymentcompanyteam.team');
    }

    public function List($dateInit, $dateEnd, $idTeam, $idDriver, $route, $state)
    {
        $paymentTeam = PaymentTeamReturn::where('startDate', $dateInit)
                                        ->where('endDate', $dateEnd);
                                        

        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);

        $listAll = PackageDispatch::with(['driver.role', 'driver', 'package_histories', 'package_price_company_team'])
                                ->whereBetween('updated_at', [$dateInit, $dateEnd])
                                ->where('status', 'Delivery');

        if($idTeam != 0)
        {
            $paymentTeam = $paymentTeam->where('idTeam', $idTeam)->first();

            $listAll = $listAll->where('idTeam', $idTeam);
        }
        else
        {
            $paymentTeam = $paymentTeam->first();
        }

        if($route != 'all')
        {
            $listAll = $listAll->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $listAll = $listAll->whereIn('Dropoff_Province', $states);
        }

        $totalPriceCompany = $listAll->get()->sum('pricePaymentCompany');
        $totalPriceTeam    = $listAll->get()->sum('pricePaymentTeam');

        $listAll = $listAll->with(['team', 'driver'])
                            ->orderBy('created_at', 'desc')
                            ->paginate(50);

        $roleUser = Auth::user()->role->name;

        $listState = PackageHistory::select('Dropoff_Province')
                                    ->where('dispatch', 1)
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        return [
            'paymentTeam' => $paymentTeam,
            'reportList' => $listAll,
            'totalPriceCompany' => $totalPriceCompany,
            'totalPriceTeam' => $totalPriceTeam,
            'listState' => $listState,
            'roleUser' => $roleUser
        ];
    }

    public function Insert(Request $request)
    {
        $sunday   = date("w", strtotime($request->get('startDate')));
        $saturday = date("w", strtotime($request->get('endDate')));

        if($sunday != 0 || $saturday != 6)
        {
            return ['stateAction' => 'incorrectDate'];
        }

        $date1 = new DateTime($request->get('startDate'));
        $date2 = new DateTime($request->get('endDate'));
        $diff  = $date1->diff($date2);

        if($diff->days != 6) return ['stateAction' => 'daysDifferenceIncorrect'];

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

            //==============REGISTER PAYMENT=================
            $team = User::find($request->get('idTeam'));

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

            //update payment and return, prices totals
            $team->totalDelivery = ($team->totalDelivery + $totalDelivery) - $team->totalReturn;
            $team->totalReturn   = 0.00;
            $team->total         = $team->totalDelivery;

            $team->save();

            DB::commit();

            return ['stateAction' => true];
        }
        catch(Exception $e)
        {
            DB::rollback();

            return ['stateAction' => false];
        }
    }

    public function Export($dateInit, $dateEnd, $idTeam, $idDriver, $route, $state)
    {
        $delimiter = ",";
        $filename = "PAYMENT - DELIVERY - TEAM  " . date('Y-m-d H:i:s') . ".csv";

        //create a file pointer
        $file = fopen('php://memory', 'w');

        //set column headers
        $fields = array('DATE', 'HOUR', 'COMPANY', 'TEAM', 'PACKAGE ID', 'WEIGHT', 'LENGTH', 'HEIGHT', 'WIDTH', 'CUIN', 'DIESEL PRICE C', 'DIESEL PRICE T', 'DIM FACTOR C', 'DIM WEIGHT C', 'DIM WEIGHT ROUND C', 'PRICE WEIGHT C', 'PEAKE SEASON PRICE C', 'PRICE BASE C', 'SURCHARGE PERCENTAGE C', 'SURCHAGE PRICE C', 'TOTAL PRICE C', 'DIM FACTOR T', 'DIM WEIGHT T', 'DIM WEIGHT ROUND T', 'PRICE WEIGHT T', 'PEAKE SEASON PRICE C', 'PRICE BASE T', 'SURCHARGE PERCENTAGE T', 'SURCHAGE PRICE T', 'TOTAL PRICE T');

        fputcsv($file, $fields, $delimiter);

        $listPackageDelivery = $this->GetDataDelivery($dateInit, $dateEnd, $idTeam, $idDriver, $route, $state);
        
        foreach($listPackageDelivery as $packageDelivery)
        {

            $lineData = array(
                                date('m-d-Y', strtotime($packageDelivery->updated_at)),
                                date('H:i:s', strtotime($packageDelivery->updated_at)),
                                $packageDelivery->company,
                                $packageDelivery->team->name,
                                $packageDelivery->Reference_Number_1,
                                $packageDelivery->package_price_company_team[0]->weight,
                                $packageDelivery->package_price_company_team[0]->length,
                                $packageDelivery->package_price_company_team[0]->height,
                                $packageDelivery->package_price_company_team[0]->width,
                                $packageDelivery->package_price_company_team[0]->cuIn,
                                $packageDelivery->package_price_company_team[0]->dieselPriceCompany,
                                $packageDelivery->package_price_company_team[0]->dieselPriceTeam,
                                $packageDelivery->package_price_company_team[0]->dimFactorCompany,
                                $packageDelivery->package_price_company_team[0]->dimWeightCompany,
                                $packageDelivery->package_price_company_team[0]->dimWeightCompanyRound,
                                $packageDelivery->package_price_company_team[0]->priceWeightCompany,
                                $packageDelivery->package_price_company_team[0]->peakeSeasonPriceCompany,
                                $packageDelivery->package_price_company_team[0]->priceBaseCompany,
                                $packageDelivery->package_price_company_team[0]->surchargePercentageCompany,
                                $packageDelivery->package_price_company_team[0]->surchargePriceCompany,
                                $packageDelivery->package_price_company_team[0]->totalPriceCompany,
                                $packageDelivery->package_price_company_team[0]->dimFactorTeam,
                                $packageDelivery->package_price_company_team[0]->dimWeightTeam,
                                $packageDelivery->package_price_company_team[0]->dimWeightTeamRound,
                                $packageDelivery->package_price_company_team[0]->priceWeightTeam,
                                $packageDelivery->package_price_company_team[0]->peakeSeasonPriceTeam,
                                $packageDelivery->package_price_company_team[0]->priceBaseTeam,
                                $packageDelivery->package_price_company_team[0]->surchargePercentageTeam,
                                $packageDelivery->package_price_company_team[0]->surchargePriceTeam,
                                $packageDelivery->package_price_company_team[0]->totalPriceTeam,
                            );

            fputcsv($file, $lineData, $delimiter);
        }

        fseek($file, 0);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '";');

        fpassthru($file);
    }

    public function GetDataDelivery($dateInit, $dateEnd, $idTeam, $idDriver, $route, $state)
    {
        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);

        $listAll = PackageDispatch::whereBetween('updated_at', [$dateInit, $dateEnd])->where('status', 'Delivery');

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

        $listAll = $listAll->with(['team', 'driver', 'package_histories', 'package_price_company_team'])
                            ->orderBy('updated_at', 'asc')
                            ->get();

        return $listAll;
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

    public function ExportPayment($idPayment)
    {
        $delimiter = ",";
        $filename = "PAYMENT - TEAM  " . date('Y-m-d H:i:s') . ".csv";

        //create a file pointer
        $file    = fopen('php://memory', 'w');
        $payment = PaymentTeamReturn::find($idPayment);

        //set column headers
        $fields = array('PAYMENT', 'REGISTER DATE', date('m-d-Y H:i:s', strtotime($payment->created_at)), 'TEAM', $payment->team->name, 'TOTAL DELIVERY', $payment->totalDelivery, 'TOTAL RETURN', $payment->totalReturn, 'REFUND', $payment->refund, 'TOTAL', $payment->total);
        fputcsv($file, $fields, $delimiter);

        //set column headers

        $fields = array('', 'RANGE DATE', date('m-d-Y', strtotime($payment->startDate)), date('m-d-Y', strtotime($payment->endDate)));
        fputcsv($file, $fields, $delimiter);

        $fields = array('');
        fputcsv($file, $fields, $delimiter);

        $fields = array('PACKAGE RETURNS');
        fputcsv($file, $fields, $delimiter);

        //set column headers
        $fields = array('DATE', 'HOUR', 'COMPANY', 'PACKAGE ID', 'WEIGHT', 'LENGTH', 'HEIGHT', 'WIDTH', 'CUIN', 'DIESEL PRICE C', 'DIESEL PRICE T', 'DIM FACTOR C', 'DIM WEIGHT C', 'DIM WEIGHT ROUND C', 'PRICE WEIGHT C', 'PEAKE SEASON PRICE C', 'PRICE BASE C', 'SURCHARGE PERCENTAGE C', 'SURCHAGE PRICE C', 'TOTAL PRICE C', 'DIM FACTOR T', 'DIM WEIGHT T', 'DIM WEIGHT ROUND T', 'PRICE WEIGHT T', 'PEAKE SEASON PRICE C', 'PRICE BASE T', 'SURCHARGE PERCENTAGE T', 'SURCHAGE PRICE T', 'TOTAL PRICE T');

        fputcsv($file, $fields, $delimiter);

        $listPackageReturn = PackageReturn::with(['team', 'driver', 'package_price_company_team'])
                                            ->where('idPaymentTeam', $idPayment)
                                            ->get();
        
        foreach($listPackageReturn as $packageReturn)
        {
            $packagePriceCompanyTeam = PackagePriceCompanyTeam::where('Reference_Number_1', $packageReturn->Reference_Number_1)->first();

            $lineData = array(
                                date('m-d-Y', strtotime($packageReturn->created_at)),
                                date('H:i:s', strtotime($packageReturn->created_at)),
                                $packageReturn->company,
                                $packageReturn->Reference_Number_1,
                                $packagePriceCompanyTeam->weight,
                                $packagePriceCompanyTeam->length,
                                $packagePriceCompanyTeam->height,
                                $packagePriceCompanyTeam->width,
                                $packagePriceCompanyTeam->cuIn,
                                $packagePriceCompanyTeam->dieselPriceCompany,
                                $packagePriceCompanyTeam->dieselPriceTeam,
                                $packagePriceCompanyTeam->dimFactorCompany,
                                $packagePriceCompanyTeam->dimWeightCompany,
                                $packagePriceCompanyTeam->dimWeightCompanyRound,
                                $packagePriceCompanyTeam->priceWeightCompany,
                                $packagePriceCompanyTeam->peakeSeasonPriceCompany,
                                $packagePriceCompanyTeam->priceBaseCompany,
                                $packagePriceCompanyTeam->surchargePercentageCompany,
                                $packagePriceCompanyTeam->surchargePriceCompany,
                                $packagePriceCompanyTeam->totalPriceCompany,
                                $packagePriceCompanyTeam->dimFactorTeam,
                                $packagePriceCompanyTeam->dimWeightTeam,
                                $packagePriceCompanyTeam->dimWeightTeamRound,
                                $packagePriceCompanyTeam->priceWeightTeam,
                                $packagePriceCompanyTeam->peakeSeasonPriceTeam,
                                $packagePriceCompanyTeam->priceBaseTeam,
                                $packagePriceCompanyTeam->surchargePercentageTeam,
                                $packagePriceCompanyTeam->surchargePriceTeam,
                                $packagePriceCompanyTeam->totalPriceTeam,
                            );

            fputcsv($file, $lineData, $delimiter);
        }

        $fields = array('');
        fputcsv($file, $fields, $delimiter);

        $fields = array('PACKAGE DELIVERIES');
        fputcsv($file, $fields, $delimiter);

        //set column headers
        $fields = array('DATE', 'HOUR', 'COMPANY', 'PACKAGE ID', 'WEIGHT', 'LENGTH', 'HEIGHT', 'WIDTH', 'CUIN', 'DIESEL PRICE C', 'DIESEL PRICE T', 'DIM FACTOR C', 'DIM WEIGHT C', 'DIM WEIGHT ROUND C', 'PRICE WEIGHT C', 'PEAKE SEASON PRICE C', 'PRICE BASE C', 'SURCHARGE PERCENTAGE C', 'SURCHAGE PRICE C', 'TOTAL PRICE C', 'DIM FACTOR T', 'DIM WEIGHT T', 'DIM WEIGHT ROUND T', 'PRICE WEIGHT T', 'PEAKE SEASON PRICE C', 'PRICE BASE T', 'SURCHARGE PERCENTAGE T', 'SURCHAGE PRICE T', 'TOTAL PRICE T');

        fputcsv($file, $fields, $delimiter);

        $listPackageDelivery = PackageDispatch::with(['team', 'driver', 'package_price_company_team'])
                                            ->where('idPaymentTeam', $idPayment)
                                            ->get();
        
        foreach($listPackageDelivery as $packageDelivery)
        {

            $lineData = array(
                                date('m-d-Y', strtotime($packageDelivery->updated_at)),
                                date('H:i:s', strtotime($packageDelivery->updated_at)),
                                $packageDelivery->company,
                                $packageDelivery->Reference_Number_1,
                                $packageDelivery->package_price_company_team[0]->weight,
                                $packageDelivery->package_price_company_team[0]->length,
                                $packageDelivery->package_price_company_team[0]->height,
                                $packageDelivery->package_price_company_team[0]->width,
                                $packageDelivery->package_price_company_team[0]->cuIn,
                                $packageDelivery->package_price_company_team[0]->dieselPriceCompany,
                                $packageDelivery->package_price_company_team[0]->dieselPriceTeam,
                                $packageDelivery->package_price_company_team[0]->dimFactorCompany,
                                $packageDelivery->package_price_company_team[0]->dimWeightCompany,
                                $packageDelivery->package_price_company_team[0]->dimWeightCompanyRound,
                                $packageDelivery->package_price_company_team[0]->priceWeightCompany,
                                $packageDelivery->package_price_company_team[0]->peakeSeasonPriceCompany,
                                $packageDelivery->package_price_company_team[0]->priceBaseCompany,
                                $packageDelivery->package_price_company_team[0]->surchargePercentageCompany,
                                $packageDelivery->package_price_company_team[0]->surchargePriceCompany,
                                $packageDelivery->package_price_company_team[0]->totalPriceCompany,
                                $packageDelivery->package_price_company_team[0]->dimFactorTeam,
                                $packageDelivery->package_price_company_team[0]->dimWeightTeam,
                                $packageDelivery->package_price_company_team[0]->dimWeightTeamRound,
                                $packageDelivery->package_price_company_team[0]->priceWeightTeam,
                                $packageDelivery->package_price_company_team[0]->peakeSeasonPriceTeam,
                                $packageDelivery->package_price_company_team[0]->priceBaseTeam,
                                $packageDelivery->package_price_company_team[0]->surchargePercentageTeam,
                                $packageDelivery->package_price_company_team[0]->surchargePriceTeam,
                                $packageDelivery->package_price_company_team[0]->totalPriceTeam,
                            );

            fputcsv($file, $lineData, $delimiter);
        }

        fseek($file, 0);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '";');

        fpassthru($file);
    }
}