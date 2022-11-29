<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;

use App\Models\{PackageHistory, PackageDelivery, PackageDispatch, PackageInbound, PackageManifest, PackageNotExists, PackageReturn, PackageWarehouse, User};

use Illuminate\Support\Facades\Validator;

use DB;
use Log;
use Session;

class IndexController extends Controller
{
    public $paginate = 50;

    public function Index()
    {
        /*if(Auth::user()->role->name == 'Administrador')
        {
            return redirect('dashboard');
        }
        elseif(Auth::user()->role->name == 'Validador')
        {
            return redirect('package-inbound');
        }
        elseif(Auth::user()->role->name == 'Team')
        {
            return redirect('package-dispatch');
        }
        elseif(Auth::user()->role->name == 'Driver')
        {
            return redirect('package-dispatch');
        }
        elseif(Auth::user()->role->name == 'View')
        {
            return redirect('package-manifest');
        }*/

        return redirect('dashboard');
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

        $quantityManifest = PackageHistory::select(DB::raw('DISTINCT Reference_Number_1'))->whereBetween('created_at', [$leastOneDayDateStart, $leastOneDayDateEnd])
                                                ->where('status', 'On hold')
                                                ->get()
                                                ->count();

        // $quantityManifestByRoutes =  PackageHistory::whereBetween('created_at', [$leastOneDayDateStart, $leastOneDayDateEnd])
        //                                             ->select('Route', DB::raw('COUNT(id) AS total'))
        //                                             ->where('status', 'On hold')
        //                                             ->groupBy('Route')
        //                                             ->get();

        $quantityInbound = PackageHistory::select(DB::raw('DISTINCT Reference_Number_1'))->whereBetween('created_at', [$leastOneDayDateStart, $leastOneDayDateEnd])
                                            ->where('status', 'Inbound')
                                            ->get()
                                            ->count();


        $quantityReInbound = PackageHistory::select(DB::raw('DISTINCT Reference_Number_1'))->whereBetween('created_at', [$initDate, $endDate])
                                                ->where('status', 'ReInbound')
                                                ->get()
                                                ->count();

        $quantityDispatch = PackageDispatch::whereBetween('created_at', [$initDate, $endDate])
                                            ->get()
                                            ->count();

        $quantityDelivery = PackageDispatch::whereBetween('Date_Delivery', [$initDate, $endDate])
                                            ->where('status', 'Delivery')
                                            ->get()
                                            ->count();

        $quantityFailed = PackageHistory::select(DB::raw('DISTINCT Reference_Number_1'))->whereBetween('created_at', [$initDate, $endDate])
                                                ->where('status', 'Failed')
                                                ->get()
                                                ->count();


        $quantityWarehouse = PackageWarehouse::select(DB::raw('DISTINCT Reference_Number_1'))->where('status', 'Warehouse')
                                                ->get()
                                                ->count();


        return [
            'quantityManifest' => $quantityManifest,
            'quantityInbound' => $quantityInbound,
            'quantityReInbound' => $quantityReInbound,
            'quantityDispatch' => $quantityDispatch,
            'quantityDelivery' => $quantityDelivery,
            'quantityWarehouse' => $quantityWarehouse,
            'quantityFailed' => $quantityFailed,
        ];
    }

    public function GetDataPerDate(Request $request, $date)
    {
        $leastOneDayStartDate = date("Y-m-d",strtotime($date ."- 1 days")) .' 00:00:00';
        $leastOneDayEndDate   = date("Y-m-d",strtotime($date ."- 1 days")) .' 23:59:59';
        $startDate            = date("Y-m-d",strtotime($date)) .' 00:00:00';
        $endDate              = date("Y-m-d",strtotime($date)) .' 23:59:59';

        $packageHistoryInbound = PackageHistory::select('Reference_Number_1', 'Route', 'status')
                                                                ->whereBetween('created_at', [$leastOneDayStartDate, $leastOneDayEndDate])
                                                                ->where('status', 'Inbound')
                                                                ->groupBy('Reference_Number_1')
                                                                ->get();

        $packageDispatchList = PackageDispatch::whereBetween('created_at', [$startDate, $endDate])->get();

        $packageDeliveryList = PackageDispatch::whereBetween('Date_Delivery', [$startDate, $endDate])
                                                ->where('status', 'Delivery')
                                                ->get();

        $packageHistoryListProcess = PackageHistory::select('Reference_Number_1', 'Route', 'status')
                                            ->whereBetween('created_at', [$startDate, $endDate])
                                            ->where('status', '!=', 'On hold')
                                            ->where('status', '!=', 'Inbound')
                                            ->groupBy('Reference_Number_1', 'Route', 'status')
                                            ->get();


        $packageRouteList = PackageHistory::select(DB::raw('DISTINCT Route'))
                                            ->whereBetween('created_at', [$leastOneDayStartDate, $endDate])
                                            ->orderBy('Route', 'asc')
                                            ->get();
        
        $dataPerTeams = DB::select("SELECT
                                p.idTeam, u.name,
                                (SELECT count(DISTINCT Reference_Number_1)
                                FROM packagedispatch p2
                                where (p2.created_at  BETWEEN '$startDate' AND '$endDate') AND p2.idTeam  = p.idTeam
                                ) as total_dispatch,
                                (SELECT count(DISTINCT Reference_Number_1)
                                FROM packagereturn p3
                                where (p3.created_at  BETWEEN '$startDate' AND '$endDate')  AND p3.idTeam  = p.idTeam
                                ) as total_reinbound,
                                (SELECT count(DISTINCT Reference_Number_1)
                                FROM packagehistory p4
                                where (p4.created_at  BETWEEN '$startDate' AND '$endDate') AND p4.status ='Failed' AND p4.idTeam  = p.idTeam
                                ) as total_failed,
                                (SELECT count(DISTINCT Reference_Number_1)
                                FROM packagedispatch p5
                                where (p5.Date_Delivery BETWEEN '$startDate' AND '$endDate') AND p5.status ='Delivery' AND p5.idTeam  = p.idTeam
                                ) as total_delivery
                                FROM packagehistory p
                                JOIN `user` u ON u.id = p.idTeam
                                WHERE (p.created_at BETWEEN '$startDate' AND '$endDate' )
                                GROUP  BY p.idTeam ");

        return [

            'packageHistoryInbound' => $packageHistoryInbound,
            'packageDispatchList' => $packageDispatchList,
            'packageDeliveryList' => $packageDeliveryList,
            'packageHistoryListProcess' => $packageHistoryListProcess,
            'packageRouteList'   => $packageRouteList,
            'dataPerTeams' => $dataPerTeams,
       ];
       
        /*$dataPerRoutes = DB::select("SELECT
                            p.Route,
                            ( SELECT count(DISTINCT Reference_Number_1)
                                FROM packagehistory p2
                                WHERE (p2.created_at BETWEEN '$leastOneDayStartDate' AND '$leastOneDayEndDate') AND p2.status ='Inbound' AND p2.Route = p.Route
                            ) as total_inbound,
                            (SELECT count(DISTINCT Reference_Number_1)
                                FROM packagehistory p3
                                WHERE (p3.created_at BETWEEN '$startDate' AND '$endDate') AND p3.status ='ReInbound' AND p3.Route = p.Route
                            ) as total_reinbound,
                            (SELECT  count(DISTINCT Reference_Number_1)
                                FROM packagehistory p4
                                WHERE (p4.created_at BETWEEN '$startDate' AND '$endDate') AND p4.status ='Dispatch' AND p4.Route = p.Route
                            ) as total_dispatch,
                            (SELECT count(DISTINCT Reference_Number_1)
                                FROM packagehistory p5
                                WHERE (p5.created_at BETWEEN '$startDate' AND '$endDate') AND p5.status ='Failed' AND p5.Route = p.Route
                            ) as total_failed,
                            (SELECT count(DISTINCT Reference_Number_1)
                                FROM packagehistory p6
                                WHERE (p6.created_at BETWEEN '$startDate' AND '$endDate') AND p6.status ='Delivery' AND p6.Route = p.Route
                            ) as total_delivery
                            FROM packagehistory p
                            WHERE (created_at BETWEEN '$leastOneDayStartDate' AND '$leastOneDayEndDate') AND status IN ('Inbound','ReInbound')
                            GROUP  BY p.Route");

        $dataPerTeams = DB::select("SELECT
                                p.idTeam, u.name,
                                (SELECT count(DISTINCT Reference_Number_1)
                                FROM packagedispatch p2
                                where (p2.created_at  BETWEEN '$startDate' AND '$endDate') AND p2.status ='Dispatch' AND p2.idTeam  = p.idTeam
                                ) as total_dispatch,
                                (SELECT count(DISTINCT Reference_Number_1)
                                FROM packagereturn p3
                                where (p3.created_at  BETWEEN '$startDate' AND '$endDate')  AND p3.idTeam  = p.idTeam
                                ) as total_reinbound,
                                (SELECT count(DISTINCT Reference_Number_1)
                                FROM packagehistory p4
                                where (p4.created_at  BETWEEN '$startDate' AND '$endDate') AND p4.status ='Failed' AND p4.idTeam  = p.idTeam
                                ) as total_failed,
                                (SELECT count(DISTINCT Reference_Number_1)
                                FROM packagedispatch p5
                                where (p5.Date_Delivery BETWEEN '$startDate' AND '$endDate') AND p5.status ='Delivery' AND p5.idTeam  = p.idTeam
                                ) as total_delivery
                                FROM packagehistory p
                                JOIN `user` u ON u.id = p.idTeam
                                WHERE (p.created_at BETWEEN '$startDate' AND '$endDate' )
                                GROUP  BY p.idTeam ");

       return [
        'dataPerRoutes' => $dataPerRoutes,
        'dataPerTeams' => $dataPerTeams
       ];
        */
    }
}
