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

    public function List($idCompany, $dateInit, $dateEnd, $idTeam, $idDriver, $route, $state)
    {
        $chargeCompany = ChargeCompany::where('startDate', $dateInit)
                                        ->where('endDate', $dateEnd);

        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);

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

    public function Export($idCompany, $dateInit, $dateEnd)
    {
        $delimiter = ",";
        $filename = "Report Charge Company " . date('Y-m-d H:i:s') . ".csv";

        //create a file pointer
        $file = fopen('php://memory', 'w');

        //set column headers
        $fields = array('DATE', 'COMPANY', 'PACKAGE ID', 'WEIGHT', 'WEIGHT PRICE', 'PEAKE SEASON PRICE', 'DIESEL PRICE', 'SURCHAGE PERCENTAGE', 'BASE PRICE', 'SURCHAGE PRICE', 'TOTAL');

        fputcsv($file, $fields, $delimiter);

        $chargeCompany = ChargeCompany::where('startDate', $dateInit)
                                        ->where('endDate', $dateEnd)
                                        ->where('idCompany', $idCompany)
                                        ->first();



        $listAll = PackageDispatch::with(['driver.role', 'driver', 'package_histories'])
                                ->whereBetween('created_at', [$dateInit, $dateEnd])
                                ->where('status', 'Delivery')
                                ->get();

        foreach($listAll as $packageDispatch)
        {
            $peakeSeason = PeakeSeasonCompany::where('idCompany', $packageDispatch->idCompany)->first();

            if($peakeSeason->start_date != null && $peakeSeason->end_date != null)
            {
                $deliveryDate        = strtotime(date('Y-m-d', strtotime($packageDispatch->updated_at)));
                $peakeSeasonDateInit = strtotime($peakeSeason->start_date);
                $peakeSeasonDateEnd  = strtotime($peakeSeason->end_date);

                if($deliveryDate >= $peakeSeasonDateInit && $deliveryDate <= $peakeSeasonDateEnd)
                {
                    if($packageDispatch->Weight <= $peakeSeason->lb1_weight)
                    {
                        $peakeSeason = $peakeSeason->lb1_weight_price;
                    }
                    else
                    {
                        $peakeSeason = $peakeSeason->lb2_weight_price;
                    }
                }
                else
                {
                    $peakeSeason = 0.00;
                }
            }
            else
            {
                $peakeSeason = 0.00;
            }

            $dieselPrice    = $chargeCompany->fuelPrice;
            $fuelPercentage = $chargeCompany->fuelPercentage;
            $basePrice      = number_format($packageDispatch->pricePaymentCompany + $peakeSeason, 4);
            $surchargePrice = number_format(($basePrice * $fuelPercentage) / 100, 4);
            $total          = number_format($basePrice + $surchargePrice, 4);

            $lineData = array(
                                date('m-d-Y', strtotime($packageDispatch->updated_at)),
                                $packageDispatch->company,
                                $packageDispatch->Reference_Number_1,
                                $packageDispatch->Weight,
                                $packageDispatch->pricePaymentCompany,
                                $peakeSeason,
                                $dieselPrice,
                                $fuelPercentage,
                                $basePrice,
                                $surchargePrice,
                                $total
                            );

            fputcsv($file, $lineData, $delimiter);
        }

        fseek($file, 0);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '";');

        fpassthru($file);
    }
}