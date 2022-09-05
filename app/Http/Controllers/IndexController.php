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
        if(Session::get('user')->role->name == 'Administrador')
        {
            return redirect('dashboard');
        }
        elseif(Session::get('user')->role->name == 'Validador')
        {
            return redirect('package-inbound');
        }
        elseif(Session::get('user')->role->name == 'Team')
        {
            return redirect('package-dispatch');
        }
        elseif(Session::get('user')->role->name == 'Driver')
        {
            return redirect('package-dispatch');
        }
        elseif(Session::get('user')->role->name == 'View')
        {
            return redirect('package-manifest');
        }

        dd(Session::get('user'));
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

        $quantityManifestByRoutes =  PackageHistory::whereBetween('created_at', [$leastOneDayDateStart, $leastOneDayDateEnd])
                                                    ->select('Route', DB::raw('COUNT(id) AS total'))
                                                    ->where('status', 'On hold')
                                                    ->groupBy('Route')
                                                    ->get();

        $quantityInbound = PackageHistory::whereBetween('created_at', [$leastOneDayDateStart, $leastOneDayDateEnd])
                                                ->where('status', 'Inbound')
                                                // ->where('inbound', 1)
                                                ->get()
                                                ->count();

        $quantityInboundByRoutes = PackageHistory::whereBetween('created_at', [$leastOneDayDateStart, $leastOneDayDateEnd])
                                                ->select('Route', DB::raw('COUNT(id) AS total'))
                                                ->where('status', 'Inbound')
                                                ->groupBy('Route')
                                                ->get();


        $quantityDispatch = PackageHistory::whereBetween('created_at', [$initDate, $endDate])
                                                ->where('status', 'Dispatch')
                                                // ->where('dispatch', 1)
                                                ->get()
                                                ->count();

        $quantityDispatchByRoutes = PackageHistory::whereBetween('created_at', [$initDate, $endDate])
                                                ->select('Route', DB::raw('COUNT(id) AS total'))
                                                ->where('status', 'Dispatch')
                                                ->groupBy('Route')
                                                ->get();

        $quantityDelivery = PackageHistory::whereBetween('created_at', [$initDate, $endDate])
                                                ->where('status', 'Delivery')
                                                ->get()
                                                ->count();

        $quantityDeliveryByRoutes = PackageHistory::whereBetween('created_at', [$initDate, $endDate])
                                                ->select('Route', DB::raw('COUNT(id) AS total'))
                                                ->where('status', 'Delivery')
                                                ->groupBy('Route')
                                                ->get();

        $quantityFailed = PackageHistory::whereBetween('created_at', [$initDate, $endDate])
                                                ->where('status', 'Failed')
                                                ->get()
                                                ->count();

        $quantityFailedByRoutes = PackageHistory::whereBetween('created_at', [$initDate, $endDate])
                                                ->select('Route', DB::raw('COUNT(id) AS total'))
                                                ->where('status', 'Failed')
                                                ->groupBy('Route')
                                                ->get();

        $quantityWarehouse = PackageHistory::whereBetween('created_at', [$initDate, $endDate])
                                                ->where('status', 'Warehouse')
                                                ->get()
                                                ->count();

        $quantityWarehouseByRoutes = PackageHistory::whereBetween('created_at', [$initDate, $endDate])
                                                ->select('Route', DB::raw('COUNT(id) AS total'))
                                                ->where('status', 'Warehouse')
                                                ->groupBy('Route')
                                                ->get();

        return [
            'quantityManifest' => $quantityManifest,
            'quantityInbound' => $quantityInbound,
            'quantityDispatch' => $quantityDispatch,
            'quantityDelivery' => $quantityDelivery,
            'quantityWarehouse' => $quantityWarehouse,
            'quantityFailed' => $quantityFailed,

            'quantityManifestByRoutes' => $quantityManifestByRoutes,
            'quantityInboundByRoutes' => $quantityInboundByRoutes,
            'quantityDispatchByRoutes' => $quantityDispatchByRoutes,
            'quantityDeliveryByRoutes' => $quantityDeliveryByRoutes,
            'quantityWarehouseByRoutes' => $quantityWarehouseByRoutes,
            'quantityFailedByRoutes' => $quantityFailedByRoutes
        ];
    }
}
