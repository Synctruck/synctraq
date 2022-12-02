<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{ ChargeCompany, ChargeCompanyDetail, PackageDelivery, PackageDispatch, PackageHistory, PeakeSeasonCompany, RangeDieselCompany };

use Illuminate\Support\Facades\Validator;

use Auth;
use DateTime;
use DB;
use Session;

class ChargeCompanyController extends Controller
{
    public function Index()
    {
        return view('charge.index');
    }

    public function List($idCompany, $dateInit, $dateEnd)
    {
        $chargeCompany = ChargeCompany::where('startDate', $dateInit)
                                        ->where('endDate', $dateEnd);

        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        //$routes = explode(',', $route);
        //$states = explode(',', $state);

        $listAll = PackageDispatch::with(['driver.role', 'driver', 'package_histories'])
                                ->whereBetween('updated_at', [$dateInit, $dateEnd])
                                ->where('status', 'Delivery');

        if($idCompany != 0) 
        {
            $chargeCompany = $chargeCompany->where('idCompany', $idCompany)->first();
            $listAll       = $listAll->where('idCompany', $idCompany);
        }
        else
        {
            $chargeCompany = $chargeCompany->first();
        }

        /*if($idTeam != 0)
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
        }*/

        $totalPriceCompany = $listAll->get()->sum('pricePaymentCompany');

        $listAll = $listAll->with(['team', 'driver'])
                            ->orderBy('created_at', 'desc')
                            ->paginate(50);

        $roleUser = Auth::user()->role->name;

        $listState = PackageHistory::select('Dropoff_Province')
                                    ->where('dispatch', 1)
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        return ['chargeCompany' => $chargeCompany, 'reportList' => $listAll, 'totalPriceCompany' => $totalPriceCompany, 'listState' => $listState, 'roleUser' => $roleUser];
    }

    public function Insert(Request $request)
    {
        $monday = date("w", strtotime($request->get('startDate')));
        $sunday = date("w", strtotime($request->get('endDate')));

        if($monday != 1 || $sunday != 0)
        {
            return ['stateAction' => 'incorrectDate'];
        }

        $date1 = new DateTime($request->get('startDate'));
        $date2 = new DateTime($request->get('endDate'));
        $diff  = $date1->diff($date2);

        if($diff->days != 6) return ['stateAction' => 'daysDifferenceIncorrect'];

        $chargeCompany = ChargeCompany::where('startDate', $request->get('startDate'))
                                        ->where('endDate', $request->get('endDate'))
                                        ->where('idCompany', $request->get('idCompany'))
                                        ->first();

        if($chargeCompany != null)
        {
            return ['stateAction' => 'chargeExists']; 
        }

        try
        {
            DB::beginTransaction();

            $idChargeCompany = date('Y-m-d-H-i-s');
            $dateInit        = $request->get('startDate') .' 00:00:00';
            $dateEnd         = $request->get('endDate') .' 23:59:59';

            $listPackageDelivery = PackageDispatch::whereBetween('updated_at', [$dateInit, $dateEnd])
                                                    ->where('status', 'Delivery')
                                                    ->get();

            $total = 0;

            foreach($listPackageDelivery as $packageDelivery)
            {
                $chargeCompanyDetail = ChargeCompanyDetail::where('Reference_Number_1', $packageDelivery->Reference_Number_1)->first();

                if($chargeCompanyDetail == null)
                {
                    $chargeCompanyDetail = new ChargeCompanyDetail();

                    $chargeCompanyDetail->Reference_Number_1 = $packageDelivery->Reference_Number_1;
                    $chargeCompanyDetail->idChargeCompany    = $idChargeCompany;

                    $chargeCompanyDetail->save();

                    $total = number_format($total + $packageDelivery->pricePaymentCompany, 4);
                }
            }

            $chargeCompany = new ChargeCompany(); 

            $chargeCompany->id             = $idChargeCompany;
            $chargeCompany->idCompany      = $request->get('idCompany');
            $chargeCompany->idUser         = Auth::user()->id;
            $chargeCompany->startDate      = $request->get('startDate');
            $chargeCompany->endDate        = $request->get('endDate');
            $chargeCompany->total          = $total;

            $chargeCompany->save();

            DB::commit();

            return ['stateAction' => true];
        }
        catch(Exception $e)
        {
            DB::rollback();

            return ['stateAction' => false];
        }
    }

    public function Export($dateInit, $dateEnd, $idCompany)
    {
        $delimiter = ",";
        $filename = "CHARGE - DELIVERY - COMPANY" . date('Y-m-d H:i:s') . ".csv";

        //create a file pointer
        $file = fopen('php://memory', 'w');

        //set column headers
        $fields = array('DATE', 'HOUR', 'COMPANY', 'TEAM', 'PACKAGE ID', 'WEIGHT', 'LENGTH', 'HEIGHT', 'WIDTH', 'CUIN', 'DIESEL PRICE C', 'DIESEL PRICE T', 'DIM FACTOR C', 'DIM WEIGHT C', 'DIM WEIGHT ROUND C', 'PRICE WEIGHT C', 'PEAKE SEASON PRICE C', 'PRICE BASE C', 'SURCHARGE PERCENTAGE C', 'SURCHAGE PRICE C', 'TOTAL PRICE C', 'DIM FACTOR T', 'DIM WEIGHT T', 'DIM WEIGHT ROUND T', 'PRICE WEIGHT T', 'PEAKE SEASON PRICE C', 'PRICE BASE T', 'SURCHARGE PERCENTAGE T', 'SURCHAGE PRICE T', 'TOTAL PRICE T');

        fputcsv($file, $fields, $delimiter);

        $listPackageDelivery = $this->GetDataDelivery($dateInit, $dateEnd, $idCompany);
        
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

    public function GetDataDelivery($dateInit, $dateEnd, $idCompany)
    {
        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        //$routes = explode(',', $route);
        //$states = explode(',', $state);

        $listAll = PackageDispatch::whereBetween('updated_at', [$dateInit, $dateEnd])->where('status', 'Delivery');

        if($idCompany != 0)
        {
            $listAll = $listAll->where('idCompany', $idCompany);
        }

        /*if($route != 'all')
        {
            $listAll = $listAll->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $listAll = $listAll->whereIn('Dropoff_Province', $states);
        }*/

        $listAll = $listAll->with(['team', 'driver', 'package_histories', 'package_price_company_team'])
                            ->orderBy('updated_at', 'asc')
                            ->get();

        return $listAll;
    }

    public function IndexCharge()
    {
        return view('charge.company');
    }

    public function ChargeList($dateStart, $dateEnd, $idCompany)
    {
        $dateStart = $dateStart .' 00:00:00';
        $dateEnd   = $dateEnd .' 23:59:59';

        $chargeList = ChargeCompany::whereBetween('created_at', [$dateStart, $dateEnd]);

        if($idCompany != 0)
        {
            $chargeList = $chargeList->where('idCompany', $idCompany);
        }

        $totalPayment = $chargeList->get()->sum('total');
        $chargeList   = $chargeList->with('company')
                                    ->orderBy('created_at', 'desc')
                                    ->paginate(50);

        return ['chargeList' => $chargeList, 'totalPayment' => number_format($totalPayment, 4)];
    }
}