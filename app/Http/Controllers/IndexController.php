<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;

use App\Models\{PackageHistory, PackageDelivery, PackageDispatch, PackageInbound, PackageManifest, PackageNotExists, PackageReturn, User};

use Illuminate\Support\Facades\Validator;

use DB;
use Session;

class IndexController extends Controller
{
    public $paginate = 50;

    public function Index()
    {
        return view('home.index');
    }

    public function IndexPublic()
    {
        $listQuantityRoute = PackageHistory::where('status', 'Inbound')
                                            ->select(DB::raw('count(Route) as quantity, Route'))
                                            ->groupBy('Route')
                                            ->orderBy('Route', 'asc')
                                            ->get();

        return view('home.indexpublic', compact('listQuantityRoute'));
    }

    public function Dashboard()
    {
        return view('home.dashboard');
    }

    public function GetAllQuantity(Request $request,$startDate,$endDate)
    {
        $leastOneDayDateStart =date("Y-m-d",strtotime($startDate."- 1 days")).' 00:00:00';
        $leastOneDayDateEnd =date("Y-m-d",strtotime($endDate."- 1 days")).' 23:59:59';
        $initDate = $startDate.' 00:00:00';
        $endDate  = $endDate.' 23:59:59';

        $quantityManifest = PackageHistory::whereBetween('created_at', [$leastOneDayDateStart, $leastOneDayDateEnd])
                                                ->where('status', 'On hold')
                                                ->get()
                                                ->count();

        $quantityInbound = PackageHistory::whereBetween('created_at', [$leastOneDayDateStart, $leastOneDayDateEnd])
                                                ->where('status', 'Inbound')
                                                ->where('inbound', 1)
                                                ->get()
                                                ->count();

        $quantityDispatch = PackageHistory::whereBetween('created_at', [$initDate, $endDate])
                                                ->where('status', 'Dispatch')
                                                ->where('dispatch', 1)
                                                ->get()
                                                ->count();

        // $quantityNotExistsToday = PackageNotExists::whereBetween('created_at', [$initDate, $endDate])
        //                                         ->get()
        //                                         ->count();

        // $quantityReturnToday = PackageHistory::whereBetween('created_at', [$todayInitDate, $endDate])
        //                                         ->where('status', 'Return')
        //                                         ->where('dispatch', 0)
        //                                         ->get()
        //                                         ->count();

        $quantityDelivery = PackageHistory::whereBetween('created_at', [$initDate, $endDate])
                                                ->where('status', 'Delivery')
                                                ->get()
                                                ->count();

        $quantityWarehouse = PackageHistory::whereBetween('created_at', [$initDate, $endDate])
                                                ->where('status', 'Warehouse')
                                                ->get()
                                                ->count();

        return [

            'quantityManifest' => $quantityManifest,
            'quantityInbound' => $quantityInbound,
            'quantityDispatch' => $quantityDispatch,
            'quantityDelivery' => $quantityDelivery,
            'quantityWarehouse' => $quantityWarehouse,
        ];
    }
}
