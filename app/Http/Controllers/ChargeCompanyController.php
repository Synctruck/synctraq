<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{ ChargeCompany, PackageDelivery, PackageDispatch, PackageHistory, PeakeSeason, RangeDiesel };

use Illuminate\Support\Facades\Validator;

use Auth;
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
                                        ->where('endDate', $dateEnd)
                                        ->where('idCompany', $idCompany)
                                        ->first();

        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);

        $listAll = PackageDispatch::with(['driver.role', 'driver', 'package_histories'])
                                ->whereBetween('created_at', [$dateInit, $dateEnd])
                                ->where('status', 'Delivery');

        if($idCompany != 0)
        {
            $listAll = $listAll->where('idCompany', $idCompany);
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

        if($request->get('fuelPrice') == null)
        {
            return ['stateAction' => 'nullFuel'];
        }

        $rangeDiesel = RangeDiesel::where('idCompany', $request->get('idCompany'))
                                    ->where('at_least', '<=', $request->get('fuelPrice'))
                                    ->where('but_less', '>=', $request->get('fuelPrice'))
                                    ->first();

        if($rangeDiesel == null)
        {
            return ['stateAction' => 'notRangeDiesel'];
        }

        try
        {
            DB::beginTransaction();

            $idCharge = date('Y-m-d-H-i-s');
            $dateInit = $request->get('startDate') .' 00:00:00';
            $dateEnd  = $request->get('endDate') .' 23:59:59';

            $chargeCompany = new ChargeCompany();

            $chargeCompany->id             = $idCharge;
            $chargeCompany->idCompany      = $request->get('idCompany');
            $chargeCompany->idUser         = Auth::user()->id;
            $chargeCompany->startDate      = $request->get('startDate');
            $chargeCompany->endDate        = $request->get('endDate');
            $chargeCompany->fuelPrice      = $request->get('fuelPrice');
            $chargeCompany->fuelPercentage = $rangeDiesel->surcharge_percentage;

            $chargeCompany->save();

            DB::commit();

            return ['stateAction' => true, 'fuelPercentage' => $rangeDiesel->surcharge_percentage];
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
            $peakeSeason = PeakeSeason::where('idCompany', $packageDispatch->idCompany)->first();

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