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

    public function GetAllQuantity()
    {
        $quantityManifest  = PackageManifest::get()->count();
        $quantityInbound   = PackageInbound::get()->count();
        $quantityNotExists = PackageNotExists::get()->count();
        $quantityDispatch  = PackageDispatch::where('status', 'Dispatch')->get()->count();
        $quantityReturn    = PackageReturn::get()->count();
        $quantityDelivery  = PackageDelivery::get()->count();

        $todayInitDate = date('Y-m-d') .' 00-00-00';
        $todayEndDate  = date('Y-m-d') .' 23-59-59';

        $quantityManifestToday = PackageHistory::whereBetween('created_at', [$todayInitDate, $todayEndDate])
                                                ->where('status', 'On hold')
                                                ->get()
                                                ->count();

        $quantityInboundToday = PackageHistory::whereBetween('created_at', [$todayInitDate, $todayEndDate])
                                                ->where('status', 'Inbound')
                                                ->where('inbound', 1)
                                                ->get()
                                                ->count();

        $quantityDispatchToday = PackageHistory::whereBetween('created_at', [$todayInitDate, $todayEndDate])
                                                ->where('status', 'Dispatch')
                                                ->where('dispatch', 1)
                                                ->get()
                                                ->count();

        $quantityNotExistsToday = PackageNotExists::whereBetween('created_at', [$todayInitDate, $todayEndDate])
                                                ->get()
                                                ->count();

        $quantityReturnToday = PackageHistory::whereBetween('created_at', [$todayInitDate, $todayEndDate])
                                                ->where('status', 'Return')
                                                ->where('dispatch', 0)
                                                ->get()
                                                ->count();

        $quantityDeliveryToday = PackageHistory::whereBetween('created_at', [$todayInitDate, $todayEndDate])
                                                ->where('status', 'Delivery')
                                                ->get()
                                                ->count();

        return [

            'quantityManifest' => $quantityManifest,
            'quantityInbound' => $quantityInbound,
            'quantityNotExists' => $quantityNotExists,
            'quantityDispatch' => $quantityDispatch,
            'quantityReturn' => $quantityReturn,
            'quantityDelivery' => $quantityDelivery,
            'quantityManifestToday' => $quantityManifestToday,
            'quantityInboundToday' => $quantityInboundToday,
            'quantityNotExistsToday' => $quantityNotExistsToday,
            'quantityDispatchToday' => $quantityDispatchToday,
            'quantityReturnToday' => $quantityReturnToday,
            'quantityDeliveryToday' => $quantityDeliveryToday,
        ];
    }
}