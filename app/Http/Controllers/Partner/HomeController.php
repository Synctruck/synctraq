<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\PackageHistory;
use App\Models\PackageWarehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index(){
        return view('partner.home');
    }

    public function Dashboard()
    {
        return view('partner.dashboard');
    }

    public function GetAllQuantity(Request $request,$startDate,$endDate)
    {
        $leastOneDayDateStart =date("Y-m-d",strtotime($startDate."- 1 days")).' 00:00:00';
        $leastOneDayDateEnd =date("Y-m-d",strtotime($endDate."- 1 days")).' 23:59:59';
        $initDate = $startDate.' 00:00:00';
        $endDate  = $endDate.' 23:59:59';

        $quantityManifest = PackageHistory::select(DB::raw('DISTINCT Reference_Number_1'))->whereBetween('created_at', [$leastOneDayDateStart, $leastOneDayDateEnd])
                                                ->where('status', 'On hold')
                                                ->where('idCompany',Auth::guard('partner')->user()->id)
                                                ->get()
                                                ->count();

        // $quantityManifestByRoutes =  PackageHistory::whereBetween('created_at', [$leastOneDayDateStart, $leastOneDayDateEnd])
        //                                             ->select('Route', DB::raw('COUNT(id) AS total'))
        //                                             ->where('status', 'On hold')
        //                                             ->groupBy('Route')
        //                                             ->get();

        $quantityInbound = PackageHistory::select(DB::raw('DISTINCT Reference_Number_1'))->whereBetween('created_at', [$leastOneDayDateStart, $leastOneDayDateEnd])
                                                ->where('status', 'Inbound')
                                                ->where('idCompany',Auth::guard('partner')->user()->id)
                                                ->get()
                                                ->count();


        $quantityDispatch = PackageHistory::select(DB::raw('DISTINCT Reference_Number_1'))->whereBetween('created_at', [$initDate, $endDate])
                                                ->where('status', 'Dispatch')
                                                ->where('idCompany',Auth::guard('partner')->user()->id)
                                                // ->where('dispatch', 1)
                                                ->get()
                                                ->count();

        $quantityDelivery = PackageHistory::select(DB::raw('DISTINCT Reference_Number_1'))->whereBetween('created_at', [$initDate, $endDate])
                                                ->where('status', 'Delivery')
                                                ->where('idCompany',Auth::guard('partner')->user()->id)
                                                ->get()
                                                ->count();



        $quantityFailed = PackageHistory::select(DB::raw('DISTINCT Reference_Number_1'))->whereBetween('created_at', [$initDate, $endDate])
                                                ->where('status', 'Failed')
                                                ->where('idCompany',Auth::guard('partner')->user()->id)
                                                ->get()
                                                ->count();


        $quantityWarehouse = PackageWarehouse::select(DB::raw('DISTINCT Reference_Number_1'))
                                                ->where('status', 'Warehouse')
                                                ->where('idCompany',Auth::guard('partner')->user()->id)
                                                ->get()
                                                ->count();


        return [
            'quantityManifest' => $quantityManifest,
            'quantityInbound' => $quantityInbound,
            'quantityDispatch' => $quantityDispatch,
            'quantityDelivery' => $quantityDelivery,
            'quantityWarehouse' => $quantityWarehouse,
            'quantityFailed' => $quantityFailed,
        ];
    }
}
