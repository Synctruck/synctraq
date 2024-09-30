<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Controllers\PackageAgeController;

use App\Models\{ AuxDispatchUser, Comment, Configuration, Driver, Package, PackageDelivery, PackageHistory, PackageBlocked, PackageDispatch,PackageFailed,  PackageInbound, PackageLmCarrier, PackageNeedMoreInformation, PackageManifest, PackageNotExists, PackagePreDispatch, PackageReturn, PackageReturnCompany, PackageWarehouse, PackageLost, TeamRoute, User };

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

use Session;

class ReportController extends Controller
{
    public function Index()
    {
        return view('report.index');
    }

    public function general()
    {
        return view('report.general');
    }

    public function IndexManifest()
    {
        return view('report.indexmanifest');
    }

    public function ListManifest($idCompany, $dateInit, $dateEnd, $route, $state)
    {
        $listAll = $this->getDataManifest($idCompany, $dateInit, $dateEnd, $route, $state);

        $listState = PackageHistory::select('Dropoff_Province')
                                    ->where('status', 'Manifest')
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        return ['listAll' => $listAll, 'listState' => $listState];
    }

    private function getDataManifest($idCompany, $dateInit, $dateEnd, $route, $state, $type = 'list')
    {
        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);

        $listAll = PackageHistory::whereBetween('created_at', [$dateInit, $dateEnd])->where('status', 'Manifest');

        if($idCompany != 0)
        {
            $listAll = $listAll->where('idCompany', $idCompany);
        }

        if($route != 'all')
        {
            $listAll = $listAll->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $listAll = $listAll->whereIn('Dropoff_Province', $states);
        }

        if($type == 'list')
        {
            $listAll = $listAll->select(
                                    'created_at',
                                    'company',
                                    'Reference_Number_1',
                                    'Dropoff_Contact_Name',
                                    'Dropoff_Contact_Phone_Number',
                                    'Dropoff_Address_Line_1',
                                    'Dropoff_City',
                                    'Dropoff_Province',
                                    'Dropoff_Postal_Code',
                                    'Weight',
                                    'Route'
                                )
                                ->orderBy('created_at', 'desc')
                                ->paginate(50);
        }
        else
        {
            $listAll = $listAll->get();
        }

        return $listAll;
    }

    public function IndexInbound()
    {
        return view('report.indexinbound');
    }

    public function IndexLost()
    {
        return view('report.indexlost');
    }

    public function ListInbound($idCompany, $dateInit, $dateEnd, $route, $state, $truck)
    {
        $data                  = $this->getDataInbound($idCompany, $dateInit, $dateEnd, $route, $state, $truck);
        $packageHistoryList    = $data['packageHistoryList'];
        $packageHistoryListNew = $data['listAll'];

        $listState = PackageHistory::select('Dropoff_Province')
                                    ->where('status', 'Inbound')
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        $listTruck = PackageHistory::select('TRUCK')
                                    ->where('status', 'Inbound')
                                    ->groupBy('TRUCK')
                                    ->get();

        return [
            'packageHistoryList' => $packageHistoryList,
            'listAll' => $packageHistoryListNew,
            'listState' => $listState,
            'listTruck'=>$listTruck
        ];
    }

    private function getDataInbound($idCompany, $dateInit, $dateEnd, $route, $state, $truck, $type = 'list')
    {
        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);
        $trucks = explode(',', $truck);

        $listAll = PackageHistory::with(
                                [
                                    'validator' => function($query){ $query->select('id', 'name', 'nameOfOwner'); },

                                ])
                                ->whereBetween('created_at', [$dateInit, $dateEnd])
                                ->where('status', 'Inbound');

        if($route != 'all')
        {
            $listAll = $listAll->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $listAll = $listAll->whereIn('Dropoff_Province', $states);
        }

        if($truck != 'all')
        {
            $listAll = $listAll->whereIn('TRUCK', $trucks);
        }

        if($idCompany && $idCompany !=0)
        {
            $listAll = $listAll->where('idCompany', $idCompany);
        }

        if($type =='list')
        {
            $listAll = $listAll->select(
                                    'created_at',
                                    'company',
                                    'idUserInbound',
                                    'Reference_Number_1',
                                    'Dropoff_Contact_Name',
                                    'Dropoff_Contact_Phone_Number',
                                    'Dropoff_Address_Line_1',
                                    'Dropoff_City',
                                    'Dropoff_Province',
                                    'Dropoff_Postal_Code',
                                    'Weight',
                                    'Route',
                                    'Weight'
                                )
                                ->orderBy('created_at', 'desc')
                                ->paginate(50);
        }
        else
        {
            $listAll = $listAll->orderBy('created_at', 'desc')->get();
        }

        $idsExists             = [];
        $packageHistoryListNew = [];

        foreach($listAll as $packageHistory)
        {
            $packageDispatch = PackageHistory::where('Reference_Number_1', $packageHistory->Reference_Number_1)
                                            ->where('status', 'Dispatch')
                                            ->first();

            $packageDelivery = PackageHistory::where('Reference_Number_1', $packageHistory->Reference_Number_1)
                                            ->where('status', 'Delivery')
                                            ->get()
                                            ->last();

            $validator = $packageHistory->validator ? $packageHistory->validator->name .' '. $packageHistory->validator->nameOfOwner : '';

            $timeDispatchDate = 0;
            $timeDeliveryDate = 0;

            if($packageDispatch)
            {
                $timeDispatchDate = (strtotime($packageDispatch->created_at) - strtotime($packageHistory->created_at)) / 86400;
                $timeDispatchDate = number_format($timeDispatchDate, 2);
            }

            if($packageDelivery)
            {
                $timeDeliveryDate = (strtotime($packageDelivery->created_at) - strtotime($packageHistory->created_at)) / 86400;
                $timeDeliveryDate = number_format($timeDeliveryDate, 2);
            }

            $status = $this->GetStatus($packageHistory->Reference_Number_1);

            $package = [

                "created_at" => $packageHistory->created_at,
                "dispatchDate" => ($packageDispatch ? $packageDispatch->created_at : ''),
                "timeDispatch" => ($timeDispatchDate >= 0 ? $timeDispatchDate : ''),
                "deliveryDate" => ($packageDelivery ? $packageDelivery->Date_Delivery : ''),
                "timeDelivery" => ($timeDeliveryDate >= 0 ? $timeDeliveryDate : ''),
                "company" => $packageHistory->company,
                "validator" => $validator,
                "status" => $status['status'],
                "statusDate" => $status['statusDate'],
                "statusDescription" => $status['statusDescription'],
                "Reference_Number_1" => $packageHistory->Reference_Number_1,
                "Dropoff_Contact_Name" => $packageHistory->Dropoff_Contact_Name,
                "Dropoff_Contact_Phone_Number" => $packageHistory->Dropoff_Contact_Phone_Number,
                "Dropoff_Address_Line_1" => $packageHistory->Dropoff_Address_Line_1,
                "Dropoff_City" => $packageHistory->Dropoff_City,
                "Dropoff_Province" => $packageHistory->Dropoff_Province,
                "Dropoff_Postal_Code" => $packageHistory->Dropoff_Postal_Code,
                "Route" => $packageHistory->Route,
                "Weight" => $packageHistory->Weight
            ];

            array_push($packageHistoryListNew, $package);
            array_push($idsExists, $packageHistory->Reference_Number_1);

        }

        return [

            'packageHistoryList' => $listAll,
            'listAll' => $packageHistoryListNew,
        ];
    }


    public function ListLost($idCompany, $idTeam, $dateInit, $dateEnd, $route, $state)
    {
        $data                  = $this->getDataLost($idCompany, $idTeam, $dateInit, $dateEnd, $route, $state);
        $packageHistoryList    = $data['packageHistoryList'];
        $packageHistoryListNew = $data['listAll'];

        $listState = PackageHistory::select('Dropoff_Province')
                                    ->where('status', 'Lost')
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        $listTruck = PackageHistory::select('TRUCK')
                                    ->where('status', 'Lost')
                                    ->groupBy('TRUCK')
                                    ->get();

        return [
            'packageHistoryList' => $packageHistoryList,
            'listAll' => $packageHistoryListNew,
            'listState' => $listState,
            'listTruck'=>$listTruck
        ];
    }

    private function getDataLost($idCompany,$idTeam, $dateInit, $dateEnd,$route, $state, $type = 'list')
    {
        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);

        $listAll = PackageHistory::with(
                                [
                                    'validator' => function($query){ $query->select('id', 'name', 'nameOfOwner'); },

                                ])
                                ->whereBetween('created_at', [$dateInit, $dateEnd])
                                ->where('status', 'Lost');

        if($route != 'all')
        {
            $listAll = $listAll->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $listAll = $listAll->whereIn('Dropoff_Province', $states);
        }

        if($idTeam != 0)
        {
            $listAll = $listAll->where('idTeam', $idTeam);
        }

        if($idCompany && $idCompany !=0)
        {
            $listAll = $listAll->where('idCompany', $idCompany);
        }

        if($type =='list')
        {
            $listAll = $listAll->select(
                                    'created_at',
                                    'company',
                                    'idUser',
                                    'idTeam',
                                    'Reference_Number_1',
                                    'Dropoff_Contact_Name',
                                    'Dropoff_Contact_Phone_Number',
                                    'Dropoff_Address_Line_1',
                                    'Dropoff_City',
                                    'Dropoff_Province',
                                    'Dropoff_Postal_Code',
                                    'Weight',
                                    'Route',
                                    'Weight'
                                )
                                ->orderBy('created_at', 'desc')
                                ->paginate(50);
        }
        else
        {
            $listAll = $listAll->orderBy('created_at', 'desc')->get();
        }

        $idsExists             = [];
        $packageHistoryListNew = [];

        foreach($listAll as $packageHistory)
        {
            $packageDispatch = PackageHistory::where('Reference_Number_1', $packageHistory->Reference_Number_1)
                                            ->where('status', 'Dispatch')
                                            ->first();

            $packageDelivery = PackageHistory::where('Reference_Number_1', $packageHistory->Reference_Number_1)
                                            ->where('status', 'Delivery')
                                            ->get()
                                            ->last();

            $validator = $packageHistory->user ? $packageHistory->user->name .' '. $packageHistory->user->nameOfOwner : '';

            $timeDispatchDate = 0;
            $timeDeliveryDate = 0;

            if($packageDispatch)
            {
                $timeDispatchDate = (strtotime($packageDispatch->created_at) - strtotime($packageHistory->created_at)) / 86400;
                $timeDispatchDate = number_format($timeDispatchDate, 2);
            }

            if($packageDelivery)
            {
                $timeDeliveryDate = (strtotime($packageDelivery->created_at) - strtotime($packageHistory->created_at)) / 86400;
                $timeDeliveryDate = number_format($timeDeliveryDate, 2);
            }

            $status = $this->GetStatus($packageHistory->Reference_Number_1);

            $package = [

                "created_at" => $packageHistory->created_at,
                "dispatchDate" => ($packageDispatch ? $packageDispatch->created_at : ''),
                "company" => $packageHistory->company,
                "team" => $packageHistory->Team,
                "validator" => $validator,
                "status" => $status['status'],
                "statusDate" => $status['statusDate'],
                "statusDescription" => $status['statusDescription'],
                "Reference_Number_1" => $packageHistory->Reference_Number_1,
                "Dropoff_Contact_Name" => $packageHistory->Dropoff_Contact_Name,
                "Dropoff_Contact_Phone_Number" => $packageHistory->Dropoff_Contact_Phone_Number,
                "Dropoff_Address_Line_1" => $packageHistory->Dropoff_Address_Line_1,
                "Dropoff_City" => $packageHistory->Dropoff_City,
                "Dropoff_Province" => $packageHistory->Dropoff_Province,
                "Dropoff_Postal_Code" => $packageHistory->Dropoff_Postal_Code,
                "Route" => $packageHistory->Route,
                "Weight" => $packageHistory->Weight
            ];

            array_push($packageHistoryListNew, $package);
            array_push($idsExists, $packageHistory->Reference_Number_1);

        }

        return [

            'packageHistoryList' => $listAll,
            'listAll' => $packageHistoryListNew,
        ];
    }

    public function IndexMMS()
    {
        return view('report.indexmiddlemilescan');
    }

    public function ListMMS($idCompany, $dateInit, $dateEnd, $route, $state)
    {
        $listAll = $this->getDataMMS($idCompany, $dateInit, $dateEnd, $route, $state);

        $listState = PackageHistory::select('Dropoff_Province')
                                    ->where('status', 'Middle Mile Scan')
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        return ['listAll' => $listAll, 'listState' => $listState];
    }

    private function getDataMMS($idCompany, $dateInit, $dateEnd, $route, $state, $type = 'list')
    {
        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);

        $listAll = PackageHistory::whereBetween('created_at', [$dateInit, $dateEnd])->where('status', 'Middle Mile Scan');

        if($idCompany != 0)
        {
            $listAll = $listAll->where('idCompany', $idCompany);
        }

        if($route != 'all')
        {
            $listAll = $listAll->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $listAll = $listAll->whereIn('Dropoff_Province', $states);
        }

        if($type == 'list')
        {
            $listAll = $listAll->select(
                                    'created_at',
                                    'company',
                                    'Reference_Number_1',
                                    'Dropoff_Contact_Name',
                                    'Dropoff_Contact_Phone_Number',
                                    'Dropoff_Address_Line_1',
                                    'Dropoff_City',
                                    'Dropoff_Province',
                                    'Dropoff_Postal_Code',
                                    'Weight',
                                    'Route',
                                    'status'
                                )
                                ->orderBy('created_at', 'desc')
                                ->paginate(50);
        }
        else
        {
            $listAll = $listAll->get();
        }

        return $listAll;
    }

    public function IndexLmCarrier()
    {
        return view('report.indexlmcarrier');
    }

    public function ListLmCarrier($idCompany, $dateInit, $dateEnd, $route, $state)
    {
        $data = $this->getDataLmCarrier($idCompany, $dateInit, $dateEnd, $route, $state);

        $packageHistoryList    = $data['packageHistoryList'];
        $packageHistoryListNew = $data['listAll'];

        $listState = PackageHistory::select('Dropoff_Province')
                                    ->where('status', 'Middle Mile Scan')
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        return [
            'packageHistoryList' => $packageHistoryList,
            'listAll' => $packageHistoryListNew,
            'listState' => $listState,
        ];
    }

    private function getDataLmCarrier($idCompany, $dateInit, $dateEnd, $route, $state, $type = 'list')
    {
        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);

        $listAll = PackageHistory::whereBetween('created_at', [$dateInit, $dateEnd])->where('status', 'LM Carrier');

        if($idCompany != 0)
        {
            $listAll = $listAll->where('idCompany', $idCompany);
        }

        if($route != 'all')
        {
            $listAll = $listAll->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $listAll = $listAll->whereIn('Dropoff_Province', $states);
        }

        if($type == 'list')
        {
            $listAll = $listAll->select(
                                    'created_at',
                                    'company',
                                    'Reference_Number_1',
                                    'Dropoff_Contact_Name',
                                    'Dropoff_Contact_Phone_Number',
                                    'Dropoff_Address_Line_1',
                                    'Dropoff_City',
                                    'Dropoff_Province',
                                    'Dropoff_Postal_Code',
                                    'Weight',
                                    'Route',
                                    'status'
                                )
                                ->orderBy('created_at', 'desc')
                                ->paginate(50);
        }
        else
        {
            $listAll = $listAll->get();
        }

        $packageHistoryListNew = [];

        foreach($listAll as $packageHistory)
        {
            $status = $this->GetStatus($packageHistory->Reference_Number_1);

            $package = [

                "created_at" => $packageHistory->created_at,
                "company" => $packageHistory->company,
                "status" => $status['status'],
                "Reference_Number_1" => $packageHistory->Reference_Number_1,
                "Dropoff_Contact_Name" => $packageHistory->Dropoff_Contact_Name,
                "Dropoff_Contact_Phone_Number" => $packageHistory->Dropoff_Contact_Phone_Number,
                "Dropoff_Address_Line_1" => $packageHistory->Dropoff_Address_Line_1,
                "Dropoff_City" => $packageHistory->Dropoff_City,
                "Dropoff_Province" => $packageHistory->Dropoff_Province,
                "Dropoff_Postal_Code" => $packageHistory->Dropoff_Postal_Code,
                "Route" => $packageHistory->Route,
                "Weight" => $packageHistory->Weight
            ];

            array_push($packageHistoryListNew, $package);
        }

        return [

            'packageHistoryList' => $listAll,
            'listAll' => $packageHistoryListNew,
        ];
    }

    public function IndexDispatch()
    {
        return view('report.indexdispatch');
    }

    public function ListDispatch($idCompany,$dateInit, $dateEnd, $idTeam, $idDriver, $route, $state)
    {
        $data                  = $this->getDataDispatch($idCompany, $dateInit, $dateEnd, $idTeam, $idDriver, $route, $state);
        $packageHistoryList    = $data['packageHistoryList'];
        $packageHistoryListNew = $data['listAll'];

        $roleUser = Auth::user()->role->name;
        $idUser   = Auth::user()->id;

        $listState = PackageHistory::select('Dropoff_Province')
                                    ->where('dispatch', 1)
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        return [
            'packageHistoryList' => $packageHistoryList,
            'reportList' => $packageHistoryListNew,
            'listState' => $listState,
            'roleUser' => $roleUser,
            'idUser'=> $idUser
        ];
    }

    private function getDataDispatch($idCompany,$dateInit, $dateEnd, $idTeam, $idDriver, $route, $state, $type = 'list')
    {
        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);

        $listPackageDispatch = PackageHistory::whereBetween('created_at', [$dateInit, $dateEnd])
                                            ->where('status', 'Dispatch');

        if($idTeam && $idDriver)
        {
            $listPackageDispatch = $listPackageDispatch->where('idTeam', $idTeam)
                                                        ->where('idUserDispatch', $idDriver);
        }
        elseif($idTeam)
        {
            $listPackageDispatch = $listPackageDispatch->where('idTeam', $idTeam);
        }
        elseif($idDriver)
        {
            $listPackageDispatch = $listPackageDispatch->where('idUserDispatch', $idDriver);
        }

        if($route != 'all')
        {
            $listPackageDispatch = $listPackageDispatch->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $listPackageDispatch = $listPackageDispatch->whereIn('Dropoff_Province', $states);
        }

        if($idCompany && $idCompany != 0)
        {
            $listPackageDispatch = $listPackageDispatch->where('idCompany', $idCompany);
        }

        if($type == 'list')
        {
            $listPackageDispatch = $listPackageDispatch->with(['team', 'driver'])
                                                        ->select(
                                                            'created_at',
                                                            'Date_Dispatch',
                                                            'company',
                                                            'idTeam',
                                                            'idUserDispatch',
                                                            'Reference_Number_1',
                                                            'Dropoff_Contact_Name',
                                                            'Dropoff_Contact_Phone_Number',
                                                            'Dropoff_Address_Line_1',
                                                            'Dropoff_City',
                                                            'Dropoff_Province',
                                                            'Dropoff_Postal_Code',
                                                            'Weight',
                                                            'Route',
                                                        )
                                                        ->orderBy('created_at', 'desc')
                                                        ->paginate(50);
        }
        else
        {
            $listPackageDispatch = $listPackageDispatch->with(['team', 'driver'])
                                                        ->orderBy('created_at', 'desc')
                                                        ->get();
        }

        $packageHistoryListNew = [];

        $packageAgeController = new PackageAgeController();

        foreach($listPackageDispatch as $packageDispatch)
        {
            $packageInbound = PackageHistory::where('Reference_Number_1', $packageDispatch->Reference_Number_1)
                                                ->where('status', 'Inbound')
                                                ->first();

            $dispatch = PackageHistory::where('Reference_Number_1', $packageDispatch->Reference_Number_1)
                                                ->where('status', 'Dispatch')
                                                ->first();

            $packageDelivery = PackageHistory::where('Reference_Number_1', $packageDispatch->Reference_Number_1)
                                                ->where('status', 'Delivery')
                                                ->get()
                                                ->last();

            $initDate = date('Y-m-d', strtotime(($packageInbound ? $packageInbound->created_at : '')));
            $endDate  = date('Y-m-d', strtotime($packageDispatch->created_at));
            $lateDays = $packageAgeController->CalculateDaysLate($initDate, $endDate);


            $timeDispatchDate = 0;
            $timeDeliveryDate = 0;

            if($dispatch)
            {
                $timeDispatchDate = (strtotime($dispatch->created_at) - strtotime($packageDispatch->created_at)) / 86400;
                $timeDispatchDate = number_format($timeDispatchDate, 2);
            }

            if($packageDelivery)
            {
                $timeDeliveryDate = (strtotime($packageDelivery->created_at) - strtotime($packageDispatch->created_at)) / 86400;
                $timeDeliveryDate = number_format($timeDeliveryDate, 2);
            }


            $package = [
                "created_at" => $packageDispatch->created_at,
                "inboundDate" => ($packageInbound ? $packageInbound->created_at : ''),
                "lateDays" => $lateDays,
                "deliveryDate" => ($packageDelivery ? $packageDelivery->Date_Delivery : ''),
                "timeDelivery" => ($timeDeliveryDate >= 0 ? $timeDeliveryDate : ''),
                "company" => $packageDispatch->company,
                "team" => $packageDispatch->team,
                "driver" => $packageDispatch->driver,
                "Reference_Number_1" => $packageDispatch->Reference_Number_1,
                "Dropoff_Contact_Name" => $packageDispatch->Dropoff_Contact_Name,
                "Dropoff_Contact_Phone_Number" => $packageDispatch->Dropoff_Contact_Phone_Number,
                "Dropoff_Address_Line_1" => $packageDispatch->Dropoff_Address_Line_1,
                "Dropoff_City" => $packageDispatch->Dropoff_City,
                "Dropoff_Province" => $packageDispatch->Dropoff_Province,
                "Dropoff_Postal_Code" => $packageDispatch->Dropoff_Postal_Code,
                "Weight" => $packageDispatch->Weight,
                "Route" => $packageDispatch->Route
            ];

            array_push($packageHistoryListNew, $package);
        }

        return [

            'packageHistoryList' => $listPackageDispatch,
            'listAll' => $packageHistoryListNew,
        ];
    }

    public function IndexDelivery(Request $request)
    {
        $Reference_Number = $request->get('Reference_Number');

        return view('report.indexdelivery', compact('Reference_Number'));
    }

    public function ListDelivery($idCompany, $dateInit, $dateEnd, $idTeam, $idDriver, $route, $state)
    {
        $Reference_Number_1s = [];
        $data                  = $this->getDataDelivery($idCompany, $dateInit, $dateEnd, $idTeam, $idDriver, $route, $state);
        $packageHistoryList    = $data['packageHistoryList'];
        $packageHistoryListNew = $data['listAll'];

        foreach($packageHistoryListNew as $delivery)
        {
            array_push($Reference_Number_1s, $delivery['Reference_Number_1']);
        }

        $listDeliveries = PackageDelivery::whereIn('taskDetails', $Reference_Number_1s)
                                        ->orderBy('created_at', 'desc')
                                        ->get();

        $roleUser = Auth::user()->role->name;

        $listState = PackageDispatch::select('Dropoff_Province')
                                    ->where('status', 'Delivery')
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        return [
            'packageHistoryList' => $packageHistoryList,
            'reportList' => $packageHistoryListNew,
            'listState' => $listState,
            'listDeliveries'=> $listDeliveries,
            'roleUser' => $roleUser
        ];
    }

    private function getDataDelivery($idCompany, $dateInit, $dateEnd, $idTeam, $idDriver, $route, $state,$type='list'){

        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);

        $listAll = PackageDispatch::whereBetween('Date_Delivery', [$dateInit, $dateEnd])->where('status', 'Delivery');

        if($idCompany && $idCompany != 0)
        {
            $listAll = $listAll->where('idCompany', $idCompany);
        }

        if($idTeam && $idDriver)
        {
            $listAll = $listAll->where('idTeam', $idTeam)->where('idUserDispatch', $idDriver);
        }
        elseif($idTeam)
        {
            $listAll = $listAll->where('idTeam', $idTeam);
        }
        elseif($idDriver)
        {
            $listAll = $listAll->where('idUserDispatch', $idDriver);
        }

        if($route != 'all')
        {
            $listAll = $listAll->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $listAll = $listAll->whereIn('Dropoff_Province', $states);
        }

        if($type == 'list')
        {
            $listAll = $listAll->with(['team', 'driver'])
                                ->select(
                                    'idOnfleet',
                                    'photoUrl',
                                    'company',
                                    'idTeam',
                                    'idUserDispatch',
                                    'Reference_Number_1',
                                    'Dropoff_Contact_Name',
                                    'Dropoff_Contact_Phone_Number',
                                    'Dropoff_Address_Line_1',
                                    'Dropoff_City',
                                    'Dropoff_Province',
                                    'Dropoff_Postal_Code',
                                    'Weight',
                                    'Route',
                                    'taskOnfleet',
                                    'arrivalLonLat',
                                    'Date_Delivery',
                                    'filePhoto1',
                                    'filePhoto2',
                                    'created_at'
                                )
                                ->orderBy('Date_Delivery', 'desc')
                                ->paginate(50);
        }
        else
        {
            $listAll = $listAll->with(['team', 'driver'])->orderBy('Date_Delivery', 'desc')->get();
        }

        $packageHistoryListNew = [];

        foreach($listAll as $packageDelivery)
        {
            $packageInbound = PackageHistory::where('Reference_Number_1', $packageDelivery->Reference_Number_1)
                                                ->whereIn("status", ["Manifest", "Inbound"])
                                                ->orderBy('created_at', 'asc')
                                                ->get();

            $manifestDate = "";
            $inboundDate = "";

            if(count($packageInbound) > 1){
                if($packageInbound[0]->status == "Manifest"){
                    $manifestDate = $packageInbound[0]->created_at ? $packageInbound[0]->created_at : "";
                }

                if($packageInbound[1]->status == "Inbound"){
                    $inboundDate = $packageInbound[1]->created_at ? $packageInbound[1]->created_at : "";
                }
            }
            else if(count($packageInbound) == 1){
                if($packageInbound[0]->status == "Manifest"){
                    $manifestDate = $packageInbound[0]->created_at ? $packageInbound[0]->created_at : "";
                }

                if($packageInbound[0]->status == "Inbound"){
                    $inboundDate = $packageInbound[0]->created_at ? $packageInbound[0]->created_at : "";
                }
            }

            $validator = $packageDelivery->validator ? $packageDelivery->validator->name .' '. $packageDelivery->validator->nameOfOwner : '';
            
            $package = [
                "idOnfleet" => $packageDelivery->idOnfleet,
                "photoUrl" => $packageDelivery->photoUrl,
                "Date_Delivery" => $packageDelivery->Date_Delivery,
                "manifestDate" => $manifestDate,
                "inboundDate" => $inboundDate,
                "dispatchDate" => $packageDelivery->Date_Dispatch > 1 ? $packageDelivery->Date_Dispatch : '',
                "company" => $packageDelivery->company,
                "team" => $packageDelivery->team,
                "driver" => $packageDelivery->driver,
                "Reference_Number_1" => $packageDelivery->Reference_Number_1,
                "Dropoff_Contact_Name" => $packageDelivery->Dropoff_Contact_Name,
                "Dropoff_Contact_Phone_Number" => $packageDelivery->Dropoff_Contact_Phone_Number,
                "Dropoff_Address_Line_1" => $packageDelivery->Dropoff_Address_Line_1,
                "Dropoff_City" => $packageDelivery->Dropoff_City,
                "Dropoff_Province" => $packageDelivery->Dropoff_Province,
                "Dropoff_Postal_Code" => $packageDelivery->Dropoff_Postal_Code,
                "Weight" => $packageDelivery->Weight,
                "Route" => $packageDelivery->Route,
                "pricePaymentTeam" => $packageDelivery->pricePaymentTeam,
                "pieces" => $packageDelivery->pieces,
                "taskOnfleet" => $packageDelivery->taskOnfleet,
                "filePhoto1" => $packageDelivery->filePhoto1,
                "filePhoto2" => $packageDelivery->filePhoto2,
                "created_at" => $packageDelivery->created_at,
            ];

            array_push($packageHistoryListNew, $package);
        }

        return [

            'packageHistoryList' => $listAll,
            'listAll' => $packageHistoryListNew,
        ];
    }

    public function IndexDelete()
    {
        return view('report.indexdelete');
    }

    public function ListDelete($idCompany, $dateInit, $dateEnd, $idTeam, $idDriver, $route, $state, $statusDescription)
    {
        $data                  = $this->getDataDelete($idCompany, $dateInit, $dateEnd, $idTeam, $idDriver, $route, $state, $statusDescription);
        $packageHistoryList    = $data['packageHistoryList'];
        $packageHistoryListNew = $data['listAll'];

        $roleUser = Auth::user()->role->name;
        $idUser   = Auth::user()->id;

        $listState = PackageHistory::select('Dropoff_Province')
                                    ->where('status', 'Failed')
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        return [
            'packageHistoryList' => $packageHistoryList,
            'reportList' => $packageHistoryListNew,
            'listState' => $listState,
            'roleUser' => $roleUser,
            'idUser' => $idUser
        ];
    }

    private function getDataDelete($idCompany, $dateInit, $dateEnd, $idTeam, $idDriver, $route, $state, $statusDescription, $type = 'list')
    {
        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);

        $listPackageFailed = PackageHistory::whereBetween('created_at', [$dateInit, $dateEnd])->where('status', 'Delete');

        if($idTeam && $idDriver)
        {
            $listPackageFailed = $listPackageFailed->where('idTeam', $idTeam)->where('idUserDispatch', $idDriver);
        }
        elseif($idTeam)
        {
            $listPackageFailed = $listPackageFailed->where('idTeam', $idTeam);
        }
        elseif($idDriver)
        {
            $listPackageFailed = $listPackageFailed->where('idUserDispatch', $idDriver);
        }

        if($route != 'all')
        {
            $listPackageFailed = $listPackageFailed->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $listPackageFailed = $listPackageFailed->whereIn('Dropoff_Province', $states);
        }

        if($idCompany && $idCompany !=0)
        {
            $listPackageFailed = $listPackageFailed->where('idCompany', $idCompany);
        }

        if($statusDescription != 'all')
        {
            $listPackageFailed = $listPackageFailed->where('Description_Onfleet', 'like', '%'. $statusDescription .':%');
        }

        if($type == 'list')
        {
            $listPackageFailed = $listPackageFailed->with(['team', 'driver'])
                                                    ->select(
                                                        'created_at',
                                                        'idTeam',
                                                        'idUserDispatch',
                                                        'company',
                                                        'Reference_Number_1',
                                                        'Dropoff_Contact_Name',
                                                        'Dropoff_Contact_Phone_Number',
                                                        'Dropoff_Address_Line_1',
                                                        'Dropoff_City',
                                                        'Dropoff_Province',
                                                        'Dropoff_Postal_Code',
                                                        'Description_Onfleet',
                                                        'Weight',
                                                        'Route'
                                                    )
                                                    ->orderBy('created_at', 'desc')
                                                    ->paginate(50);
        }
        else
        {
            $listPackageFailed = $listPackageFailed->with(['team', 'driver'])
                                                        ->orderBy('created_at', 'desc')
                                                        ->get();
        }

        $packageHistoryListNew = [];

        foreach($listPackageFailed as $packageFailed)
        {
            $status = $this->GetStatus($packageFailed->Reference_Number_1);

            $package = [
                "created_at" => $packageFailed->created_at,
                "description" => $status['description'],
                "status" => $status['status'],
                "statusDate" => $status['statusDate'],
                "statusDescription" => $status['statusDescription'],
                "company" => $packageFailed->company,
                "team" => $packageFailed->team,
                "driver" => $packageFailed->driver,
                "Reference_Number_1" => $packageFailed->Reference_Number_1,
                "Dropoff_Contact_Name" => $packageFailed->Dropoff_Contact_Name,
                "Dropoff_Contact_Phone_Number" => $packageFailed->Dropoff_Contact_Phone_Number,
                "Dropoff_Address_Line_1" => $packageFailed->Dropoff_Address_Line_1,
                "Dropoff_City" => $packageFailed->Dropoff_City,
                "Dropoff_Province" => $packageFailed->Dropoff_Province,
                "Dropoff_Postal_Code" => $packageFailed->Dropoff_Postal_Code,
                "Weight" => $packageFailed->Weight,
                "Route" => $packageFailed->Route
            ];

            array_push($packageHistoryListNew, $package);
        }

        return [

            'packageHistoryList' => $listPackageFailed,
            'listAll' => $packageHistoryListNew,
        ];
    }

    public function IndexFailed()
    {
        return view('report.indexfailed');
    }

    public function ListFailed($idCompany, $dateInit, $dateEnd, $idTeam, $idDriver, $route, $state, $statusDescription)
    {
        $data                  = $this->getDataFailed($idCompany, $dateInit, $dateEnd, $idTeam, $idDriver, $route, $state, $statusDescription);
        $packageHistoryList    = $data['packageHistoryList'];
        $packageHistoryListNew = $data['listAll'];

        $roleUser = Auth::user()->role->name;
        $idUser   = Auth::user()->id;

        $listState = PackageHistory::select('Dropoff_Province')
                                    ->where('status', 'Failed')
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        return [
            'packageHistoryList' => $packageHistoryList,
            'reportList' => $packageHistoryListNew,
            'listState' => $listState,
            'roleUser' => $roleUser,
            'idUser' => $idUser
        ];
    }

    private function getDataFailed($idCompany, $dateInit, $dateEnd, $idTeam, $idDriver, $route, $state, $statusDescription, $type = 'list')
    {
        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);

        $listPackageFailed = PackageHistory::whereBetween('created_at', [$dateInit, $dateEnd])->where('status', 'Failed');

        if($idTeam && $idDriver)
        {
            $listPackageFailed = $listPackageFailed->where('idTeam', $idTeam)->where('idUserDispatch', $idDriver);
        }
        elseif($idTeam)
        {
            $listPackageFailed = $listPackageFailed->where('idTeam', $idTeam);
        }
        elseif($idDriver)
        {
            $listPackageFailed = $listPackageFailed->where('idUserDispatch', $idDriver);
        }

        if($route != 'all')
        {
            $listPackageFailed = $listPackageFailed->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $listPackageFailed = $listPackageFailed->whereIn('Dropoff_Province', $states);
        }

        if($idCompany && $idCompany !=0)
        {
            $listPackageFailed = $listPackageFailed->where('idCompany', $idCompany);
        }

        if($statusDescription != 'all')
        {
            $listPackageFailed = $listPackageFailed->where('Description_Onfleet', 'like', '%'. $statusDescription .':%');
        }

        if($type == 'list')
        {
            $listPackageFailed = $listPackageFailed->with(['team', 'driver'])
                                                    ->select(
                                                        'created_at',
                                                        'idTeam',
                                                        'idUserDispatch',
                                                        'company',
                                                        'Reference_Number_1',
                                                        'Dropoff_Contact_Name',
                                                        'Dropoff_Contact_Phone_Number',
                                                        'Dropoff_Address_Line_1',
                                                        'Dropoff_City',
                                                        'Dropoff_Province',
                                                        'Dropoff_Postal_Code',
                                                        'Description_Onfleet',
                                                        'Weight',
                                                        'Route',
                                                        'photoUrl'
                                                    )
                                                    ->orderBy('created_at', 'desc')
                                                    ->paginate(50);
        }
        else
        {
            $listPackageFailed = $listPackageFailed->with(['team', 'driver'])
                                                        ->orderBy('created_at', 'desc')
                                                        ->get();
        }

        $packageHistoryListNew = [];

        foreach($listPackageFailed as $packageFailed)
        {
            $status = $this->GetStatus($packageFailed->Reference_Number_1);

            $package = [
                "created_at" => $packageFailed->created_at,
                "description" => $status['description'],
                "status" => $status['status'],
                "statusDate" => $status['statusDate'],
                "statusDescription" => $status['statusDescription'],
                "company" => $packageFailed->company,
                "team" => $packageFailed->team,
                "driver" => $packageFailed->driver,
                "Reference_Number_1" => $packageFailed->Reference_Number_1,
                "Dropoff_Contact_Name" => $packageFailed->Dropoff_Contact_Name,
                "Dropoff_Contact_Phone_Number" => $packageFailed->Dropoff_Contact_Phone_Number,
                "Dropoff_Address_Line_1" => $packageFailed->Dropoff_Address_Line_1,
                "Dropoff_City" => $packageFailed->Dropoff_City,
                "Dropoff_Province" => $packageFailed->Dropoff_Province,
                "Dropoff_Postal_Code" => $packageFailed->Dropoff_Postal_Code,
                "Weight" => $packageFailed->Weight,
                "Route" => $packageFailed->Route,
                "photoUrl" => $packageFailed->photoUrl
            ];

            array_push($packageHistoryListNew, $package);
        }

        return [

            'packageHistoryList' => $listPackageFailed,
            'listAll' => $packageHistoryListNew,
        ];
    }

    public function IndexAllPending()
    {
        return view('report.indexallpending');
    }

    public function ListAllPending($idCompany, $dateInit, $dateEnd, $state, $status)
    {
        $data          = $this->getDataAllPending($idCompany, $dateInit, $dateEnd, $state, $status);
        $packageList   = $data['packageList'];
        $listAll       = $data['listAll'];
        $totalQuantity = $data['totalQuantity'];

        $listState = PackageHistory::select('Dropoff_Province')
                                    ->where('status', 'Manifest')
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        return ['packageList' => $packageList, 'listAll' => $listAll, 'listState' => $listState, 'totalQuantity' => $totalQuantity];
    }

    private function getDataAllPending($idCompany, $dateInit, $dateEnd, $state, $status, $type = 'list')
    {
        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';
        $states   = explode(',', $state);

        $packageManifestList  = PackageManifest::whereBetween('created_at', [$dateInit, $dateEnd])->where('status', 'Manifest');
        $packageInboundList   = PackageInbound::whereBetween('created_at', [$dateInit, $dateEnd]);
        $packageWarehouseList = PackageWarehouse::whereBetween('created_at', [$dateInit, $dateEnd]);

        if($idCompany != 0)
        {
            $packageManifestList  = $packageManifestList->where('idCompany', $idCompany);
            $packageInboundList   = $packageInboundList->where('idCompany', $idCompany);
            $packageWarehouseList = $packageWarehouseList->where('idCompany', $idCompany);
        }

        if($state != 'all')
        {
            $packageManifestList  = $packageManifestList->whereIn('Dropoff_Province', $states);
            $packageInboundList   = $packageInboundList->whereIn('Dropoff_Province', $states);
            $packageWarehouseList = $packageWarehouseList->whereIn('Dropoff_Province', $states);
        }

        if($status != 'all')
        {
            $packageManifestList  = $packageManifestList->where('status', $status);
            $packageInboundList   = $packageInboundList->where('status', $status);
            $packageWarehouseList = $packageWarehouseList->where('status', $status);
        }

        $listAll       = [];
        $totalQuantity = 0;

        if($type == 'list')
        {
            $packageManifestList  = $packageManifestList->paginate(50);
            $packageInboundList   = $packageInboundList->paginate(50);
            $packageWarehouseList = $packageWarehouseList->paginate(50);

            $maxTotal      = max($packageManifestList->total(), $packageInboundList->total(), $packageWarehouseList->total());
            $totalQuantity = $packageManifestList->total() + $packageInboundList->total() + $packageWarehouseList->total();

            if($maxTotal == $packageManifestList->total())
            {
                $listAll = $packageManifestList;
            }
            else if($maxTotal == $packageInboundList->total())
            {
                $listAll = $packageInboundList;
            }
            else if($maxTotal == $packageWarehouseList->total())
            {
                $listAll = $packageWarehouseList;
            }

            $packageList = array_merge($packageManifestList->items(), $packageInboundList->items());
            $packageList = array_merge($packageList, $packageWarehouseList->items());
        }
        else
        {
            $packageManifestList  = $packageManifestList->get();
            $packageInboundList   = $packageInboundList->get();
            $packageWarehouseList = $packageWarehouseList->get();

            $packageList = $packageManifestList->merge($packageInboundList);
            $packageList = $packageList->merge($packageWarehouseList);
        }

        return ['packageList' => $packageList, 'listAll' => $listAll , 'totalQuantity' => $totalQuantity];
    }

    public function IndexNotExists()
    {
        return view('report.indexnotexists');
    }

    public function ListNotExists($dateInit, $dateEnd)
    {
        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $reportList = PackageNotExists::whereBetween('created_at', [$dateInit, $dateEnd])
                                        ->orderBy('created_at', 'desc')
                                        ->get();

        return ['reportList' => $reportList];
    }

    public function ExportInbound($idCompany, $dateInit, $dateEnd, $route, $state, $truck, $typeExport)
    {
        $delimiter = ",";
        $filename  = $typeExport == 'download' ? "Report Inbound " . date('Y-m-d H:i:s') . ".csv" : Auth::user()->id ."- Report Inbound.csv";
        $file      = $typeExport == 'download' ? fopen('php://memory', 'w') : fopen(public_path($filename), 'w');

        //set column headers
        $fields = array('DATE', 'HOUR', 'DISPATCH DATE', 'TIME DISPATCH - DAYS', 'DELIVERY DATE', 'TIME DELIVERY - DAYS', 'COMPANY', 'VALIDATOR', 'PACKAGE ID', 'ACTUAL STATUS', 'STATUS DATE', 'STATUS DESCRIPTION', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'ROUTE', 'WEIGHT');

        fputcsv($file, $fields, $delimiter);

        $listPackageInbound = $this->getDataInbound($idCompany, $dateInit, $dateEnd, $route, $state, $truck, $type = 'export');
        $listPackageInbound = $listPackageInbound['listAll'];

        foreach($listPackageInbound as $packageInbound)
        {
            $lineData = array(
                                date('m-d-Y', strtotime($packageInbound['created_at'])),
                                date('H:i:s', strtotime($packageInbound['created_at'])),
                                $packageInbound['dispatchDate'],
                                $packageInbound['timeDispatch'],
                                $packageInbound['deliveryDate'],
                                $packageInbound['timeDelivery'],
                                $packageInbound['company'],
                                $packageInbound['validator'],
                                $packageInbound['Reference_Number_1'],
                                $packageInbound['status'],
                                $packageInbound['statusDate'],
                                $packageInbound['statusDescription'],
                                $packageInbound['Dropoff_Contact_Name'],
                                $packageInbound['Dropoff_Contact_Phone_Number'],
                                $packageInbound['Dropoff_Address_Line_1'],
                                $packageInbound['Dropoff_City'],
                                $packageInbound['Dropoff_Province'],
                                $packageInbound['Dropoff_Postal_Code'],
                                $packageInbound['Route'],
                                $packageInbound['Weight']
                            );

            fputcsv($file, $lineData, $delimiter);
        }

        if($typeExport == 'download')
        {
            fseek($file, 0);

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '";');

            fpassthru($file);
        }
        else
        {
            rewind($file);
            fclose($file);

            SendGeneralExport('Report Inbound', $filename);

            return ['stateAction' => true];
        }
    }


    public function ExportLost($idCompany, $idTeam, $dateInit, $dateEnd, $route, $state, $typeExport)
    {
        $delimiter = ",";
        $filename  = $typeExport == 'download' ? "Report Lost " . date('Y-m-d H:i:s') . ".csv" : Auth::user()->id ."- Report Lost.csv";
        $file      = $typeExport == 'download' ? fopen('php://memory', 'w') : fopen(public_path($filename), 'w');

        //set column headers
        $fields = array('DATE', 'HOUR','COMPANY','TEAM','VALIDATOR', 'PACKAGE ID', 'ACTUAL STATUS', 'STATUS DATE', 'STATUS DESCRIPTION', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'ROUTE', 'WEIGHT');

        fputcsv($file, $fields, $delimiter);

        $listPackageLost = $this->getDataLost($idCompany, $idTeam, $dateInit, $dateEnd, $route, $state, $type = 'export');
        $listPackageLost = $listPackageLost['listAll'];

        foreach($listPackageLost as $packageLost)
        {
            $team   = isset($packageLost['team']) ? $packageLost['team']['name'] : '';
            $lineData = array(
                                date('m-d-Y', strtotime($packageLost['created_at'])),
                                date('H:i:s', strtotime($packageLost['created_at'])),
                                $packageLost['company'],
                                $team,
                                $packageLost['validator'],
                                $packageLost['Reference_Number_1'],
                                $packageLost['status'],
                                $packageLost['statusDate'],
                                $packageLost['statusDescription'],
                                $packageLost['Dropoff_Contact_Name'],
                                $packageLost['Dropoff_Contact_Phone_Number'],
                                $packageLost['Dropoff_Address_Line_1'],
                                $packageLost['Dropoff_City'],
                                $packageLost['Dropoff_Province'],
                                $packageLost['Dropoff_Postal_Code'],
                                $packageLost['Route'],
                                $packageLost['Weight']
                            );

            fputcsv($file, $lineData, $delimiter);
        }

        if($typeExport == 'download')
        {
            fseek($file, 0);

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '";');

            fpassthru($file);
        }
        else
        {
            rewind($file);
            fclose($file);

            SendGeneralExport('Report Lost', $filename);

            return ['stateAction' => true];
        }
    }

    public function ExportDispatch($idCompany, $dateInit, $dateEnd, $idTeam, $idDriver, $route, $state, $typeExport)
    {
        $delimiter = ",";
        $filename  = $typeExport == 'download' ? "Report Dispatch " . date('Y-m-d H:i:s') . ".csv" : Auth::user()->id ."- Report Dispatch.csv";
        $file      = $typeExport == 'download' ? fopen('php://memory', 'w') : fopen(public_path($filename), 'w');

        //set column headers
        $fields = array('DATE', 'HOUR', 'INBOUND DATE', 'DAYS TO DISPATCH', 'COMPANY', 'TEAM', 'DRIVER', 'PACKAGE ID', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'WEIGHT', 'ROUTE');

        fputcsv($file, $fields, $delimiter);

        $listPackageDispatch = $this->getDataDispatch($idCompany,$dateInit, $dateEnd, $idTeam, $idDriver, $route, $state, $type = 'export');
        $listPackageDispatch = $listPackageDispatch['listAll'];

        $packageAgeController = new PackageAgeController();

        foreach($listPackageDispatch as $packageDispatch)
        {
            $team   = isset($packageDispatch['team']) ? $packageDispatch['team']['name'] : '';
            $driver = isset($packageDispatch['driver']) ? $packageDispatch['driver']['name'] .' '. $packageDispatch['driver']['nameOfOwner'] : '';

            $packageInbound = PackageHistory::where('Reference_Number_1', $packageDispatch['Reference_Number_1'])
                                                ->where('status', 'Inbound')
                                                ->first();

            $initDate = date('Y-m-d', strtotime(($packageInbound ? $packageInbound->created_at : '')));
            $endDate  = date('Y-m-d', strtotime($packageDispatch['created_at']));
            $lateDays = $packageAgeController->CalculateDaysLate($initDate, $endDate);

            $lineData = array(
                                date('m-d-Y', strtotime($packageDispatch['created_at'])),
                                date('H:i:s', strtotime($packageDispatch['created_at'])),
                                date('m-d-Y H:i:s', strtotime($packageInbound->created_at)),
                                $lateDays,
                                $packageDispatch['company'],
                                $team,
                                $driver,
                                $packageDispatch['Reference_Number_1'],
                                $packageDispatch['Dropoff_Contact_Name'],
                                $packageDispatch['Dropoff_Contact_Phone_Number'],
                                $packageDispatch['Dropoff_Address_Line_1'],
                                $packageDispatch['Dropoff_City'],
                                $packageDispatch['Dropoff_Province'],
                                $packageDispatch['Dropoff_Postal_Code'],
                                $packageDispatch['Weight'],
                                $packageDispatch['Route'],
                            );

            fputcsv($file, $lineData, $delimiter);
        }

        if($typeExport == 'download')
        {
            fseek($file, 0);

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '";');

            fpassthru($file);
        }
        else
        {
            rewind($file);
            fclose($file);

            SendGeneralExport('Report Dispatch', $filename);

            return ['stateAction' => true];
        }
    }

    public function ExportDelivery($idCompany, $dateInit, $dateEnd, $idTeam, $idDriver, $route, $state, $typeExport)
    {
        $delimiter = ",";
        $filename  = $typeExport == 'download' ? "Report Delivery " . date('Y-m-d H:i:s') . ".csv" : Auth::user()->id ."- Report Delivery.csv";
        $file      = $typeExport == 'download' ? fopen('php://memory', 'w') : fopen(public_path($filename), 'w');

        //set column headers
        $fields = array('MANIFEST DATE', 'INBOUND DATE', 'DISPATCH DATE', 'COMPLETION DATE', 'TRANSIT TIME', 'COMPANY', 'TEAM', 'DRIVER', 'PACKAGE ID', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'WEIGHT', 'ROUTE', 'PPPC', 'PIECES', 'URL-IMAGE-1', 'URL-IMAGE-2');

        fputcsv($file, $fields, $delimiter);

        $listPackageDelivery = $this->getDataDelivery($idCompany, $dateInit, $dateEnd, $idTeam, $idDriver, $route, $state,$type='export');
        $listPackageDelivery = $listPackageDelivery['listAll'];

        foreach($listPackageDelivery as $packageDelivery)
        {
            $team   = isset($packageDelivery['team']) ? $packageDelivery['team']['name'] : '';
            $driver = isset($packageDelivery['driver']) ? $packageDelivery['driver']['name'] .' '. $packageDelivery['driver']['nameOfOwner'] : '';

            $urlImage1 = '';
            $urlImage2 = '';

            if($packageDelivery['photoUrl'] != '')
            {
                $imagesIds = explode(',', $packageDelivery['photoUrl']);

                $urlImage1 = count($imagesIds) > 0 ?  'https://d15p8tr8p0vffz.cloudfront.net/'. $imagesIds[0] .'/800x.png' : '';
                $urlImage2 = count($imagesIds) > 1 ?  'https://d15p8tr8p0vffz.cloudfront.net/'. $imagesIds[1] .'/800x.png' : '';
            }

            $deliveryTime = $packageDelivery['Date_Delivery'] ? strtotime($packageDelivery['Date_Delivery']) : "";
            $inboundTime = $packageDelivery['inboundDate'] ? strtotime($packageDelivery['inboundDate']) : "";
            $secondsDifference = $deliveryTime && $inboundTime ? $deliveryTime - $inboundTime : 0;
            $transitTime = $deliveryTime && $inboundTime ?  number_format($secondsDifference / 86400, 2) : "";

            $manifestDate = $packageDelivery['manifestDate'] ? date('m/d/Y H:i:s', strtotime($packageDelivery['manifestDate'])) : "";
            $inboundDate = $packageDelivery['inboundDate'] ? date('m/d/Y H:i:s', strtotime($packageDelivery['inboundDate'])) : "";
            $dispatchDate = $packageDelivery['dispatchDate'] ? date('m/d/Y H:i:s', strtotime($packageDelivery['dispatchDate'])) : "";

            $lineData = array(
                                $manifestDate,
                                $inboundDate,
                                $dispatchDate,
                                date('m/d/Y H:i:s', strtotime($packageDelivery['Date_Delivery'])),
                                $transitTime .' day(s)',
                                $packageDelivery['company'],
                                $team,
                                $driver,
                                $packageDelivery['Reference_Number_1'],
                                $packageDelivery['Dropoff_Contact_Name'],
                                $packageDelivery['Dropoff_Contact_Phone_Number'],
                                $packageDelivery['Dropoff_Address_Line_1'],
                                $packageDelivery['Dropoff_City'],
                                $packageDelivery['Dropoff_Province'],
                                $packageDelivery['Dropoff_Postal_Code'],
                                $packageDelivery['Weight'],
                                $packageDelivery['Route'],
                                $packageDelivery['pricePaymentTeam'],
                                $packageDelivery['pieces'],
                                $urlImage1,
                                $urlImage2,
                                $packageDelivery['created_at'],
                            );

            fputcsv($file, $lineData, $delimiter);
        }

        if($typeExport == 'download')
        {
            fseek($file, 0);

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '";');

            fpassthru($file);
        }
        else
        {
            rewind($file);
            fclose($file);

            SendGeneralExport('Report Delivery', $filename);

            return ['stateAction' => true];
        }
    }

    public function ExportDelete($idCompany, $dateInit, $dateEnd, $idTeam, $idDriver, $route, $state, $statusDescription, $typeExport)
    {
        $delimiter = ",";
        $filename  = $typeExport == 'download' ? "Report Delete " . date('Y-m-d H:i:s') . ".csv" : Auth::user()->id ."- Report Delete.csv";
        $file      = $typeExport == 'download' ? fopen('php://memory', 'w') : fopen(public_path($filename), 'w');

        //set column headers
        $fields = array('DATE', 'HOUR', 'COMPANY', 'TEAM', 'DRIVER', 'PACKAGE ID', 'DESCRIPTION ONFLEET', 'ACTUAL STATUS', 'STATUS DATE', 'STATUS DESCRIPTION', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'WEIGHT', 'ROUTE');

        fputcsv($file, $fields, $delimiter);

        $listPackageFailed = $this->getDataDelete($idCompany,$dateInit, $dateEnd, $idTeam, $idDriver, $route, $state, $statusDescription, $type = 'export');
        $listPackageFailed = $listPackageFailed['listAll'];

        foreach($listPackageFailed as $packageFailed)
        {
            $team   = isset($packageFailed['team']) ? $packageFailed['team']->name : '';
            $driver = isset($packageFailed['driver']) ? $packageFailed['driver']->name .' '. $packageFailed['driver']->nameOfOwner : '';

            $lineData = array(
                                date('m-d-Y', strtotime($packageFailed['created_at'])),
                                date('H:i:s', strtotime($packageFailed['created_at'])),
                                $packageFailed['company'],
                                $team,
                                $driver,
                                $packageFailed['Reference_Number_1'],
                                $packageFailed['description'],
                                $packageFailed['status'],
                                $packageFailed['statusDate'],
                                $packageFailed['statusDescription'],
                                $packageFailed['Dropoff_Contact_Name'],
                                $packageFailed['Dropoff_Contact_Phone_Number'],
                                $packageFailed['Dropoff_Address_Line_1'],
                                $packageFailed['Dropoff_City'],
                                $packageFailed['Dropoff_Province'],
                                $packageFailed['Dropoff_Postal_Code'],
                                $packageFailed['Weight'],
                                $packageFailed['Route']
                            );

            fputcsv($file, $lineData, $delimiter);
        }

        if($typeExport == 'download')
        {
            fseek($file, 0);

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '";');

            fpassthru($file);
        }
        else
        {
            rewind($file);
            fclose($file);

            SendGeneralExport('Report Delete', $filename);

            return ['stateAction' => true];
        }
    }

    public function ExportFailed($idCompany, $dateInit, $dateEnd, $idTeam, $idDriver, $route, $state, $statusDescription, $typeExport)
    {
        $delimiter = ",";
        $filename  = $typeExport == 'download' ? "Report Failed " . date('Y-m-d H:i:s') . ".csv" : Auth::user()->id ."- Report Failed.csv";
        $file      = $typeExport == 'download' ? fopen('php://memory', 'w') : fopen(public_path($filename), 'w');

        //set column headers
        $fields = array('DATE', 'HOUR', 'COMPANY', 'TEAM', 'DRIVER', 'PACKAGE ID', 'DESCRIPTION ONFLEET', 'ACTUAL STATUS', 'STATUS DATE', 'STATUS DESCRIPTION', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'WEIGHT', 'ROUTE');

        fputcsv($file, $fields, $delimiter);

        $listPackageFailed = $this->getDataFailed($idCompany,$dateInit, $dateEnd, $idTeam, $idDriver, $route, $state, $statusDescription, $type = 'export');
        $listPackageFailed = $listPackageFailed['listAll'];

        foreach($listPackageFailed as $packageFailed)
        {
            $team   = isset($packageFailed['team']) ? $packageFailed['team']->name : '';
            $driver = isset($packageFailed['driver']) ? $packageFailed['driver']->name .' '. $packageFailed['driver']->nameOfOwner : '';

            $lineData = array(
                                date('m-d-Y', strtotime($packageFailed['created_at'])),
                                date('H:i:s', strtotime($packageFailed['created_at'])),
                                $packageFailed['company'],
                                $team,
                                $driver,
                                $packageFailed['Reference_Number_1'],
                                $packageFailed['description'],
                                $packageFailed['status'],
                                $packageFailed['statusDate'],
                                $packageFailed['statusDescription'],
                                $packageFailed['Dropoff_Contact_Name'],
                                $packageFailed['Dropoff_Contact_Phone_Number'],
                                $packageFailed['Dropoff_Address_Line_1'],
                                $packageFailed['Dropoff_City'],
                                $packageFailed['Dropoff_Province'],
                                $packageFailed['Dropoff_Postal_Code'],
                                $packageFailed['Weight'],
                                $packageFailed['Route']
                            );

            fputcsv($file, $lineData, $delimiter);
        }

        if($typeExport == 'download')
        {
            fseek($file, 0);

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '";');

            fpassthru($file);
        }
        else
        {
            rewind($file);
            fclose($file);

            SendGeneralExport('Report Failed', $filename);

            return ['stateAction' => true];
        }
    }

    public function ExportManifest($idCompany, $dateInit, $dateEnd, $route, $state, $typeExport)
    {
        $delimiter = ",";
        $filename  = $typeExport == 'download' ? "Report Manifest " . date('Y-m-d H:i:s') . ".csv" : Auth::user()->id ."- Report Manifest.csv";
        $file      = $typeExport == 'download' ? fopen('php://memory', 'w') : fopen(public_path($filename), 'w');

        //set column headers
        $fields = array('DATE', 'HOUR', 'PACKAGE ID', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'WEIGHT', 'ROUTE');

        fputcsv($file, $fields, $delimiter);


        $listPackageManifest = $this->getDataManifest($idCompany, $dateInit, $dateEnd, $route, $state, $type = 'export');

        foreach($listPackageManifest as $packageManifest)
        {

            $lineData = array(
                                date('m-d-Y', strtotime($packageManifest->created_at)),
                                date('H:i:s', strtotime($packageManifest->created_at)),
                                $packageManifest->Reference_Number_1,
                                $packageManifest->Dropoff_Contact_Name,
                                $packageManifest->Dropoff_Contact_Phone_Number,
                                $packageManifest->Dropoff_Address_Line_1,
                                $packageManifest->Dropoff_City,
                                $packageManifest->Dropoff_Province,
                                $packageManifest->Dropoff_Postal_Code,
                                $packageManifest->Weight,
                                $packageManifest->Route
                            );

            fputcsv($file, $lineData, $delimiter);
        }

        if($typeExport == 'download')
        {
            fseek($file, 0);

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '";');

            fpassthru($file);
        }
        else
        {
            rewind($file);
            fclose($file);

            SendGeneralExport('Report Manifest', $filename);

            return ['stateAction' => true];
        }
    }

    public function ExportMMS($idCompany, $dateInit, $dateEnd, $route, $state, $typeExport)
    {
        $delimiter = ",";
        $filename  = $typeExport == 'download' ? "Report Middle Mile Scan " . date('Y-m-d H:i:s') . ".csv" : Auth::user()->id ."- Report Midle Mile Scan.csv";
        $file      = $typeExport == 'download' ? fopen('php://memory', 'w') : fopen(public_path($filename), 'w');

        //set column headers
        $fields = array('DATE', 'HOUR', 'PACKAGE ID', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'WEIGHT', 'ROUTE');

        fputcsv($file, $fields, $delimiter);

        $listPackageManifest = $this->getDataMMS($idCompany, $dateInit, $dateEnd, $route, $state, $type = 'export');

        foreach($listPackageManifest as $packageManifest)
        {

            $lineData = array(
                                date('m-d-Y', strtotime($packageManifest->created_at)),
                                date('H:i:s', strtotime($packageManifest->created_at)),
                                $packageManifest->Reference_Number_1,
                                $packageManifest->Dropoff_Contact_Name,
                                $packageManifest->Dropoff_Contact_Phone_Number,
                                $packageManifest->Dropoff_Address_Line_1,
                                $packageManifest->Dropoff_City,
                                $packageManifest->Dropoff_Province,
                                $packageManifest->Dropoff_Postal_Code,
                                $packageManifest->Weight,
                                $packageManifest->Route
                            );

            fputcsv($file, $lineData, $delimiter);
        }

        if($typeExport == 'download')
        {
            fseek($file, 0);

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '";');

            fpassthru($file);
        }
        else
        {
            rewind($file);
            fclose($file);

            SendGeneralExport('Report Midle Mile Scan', $filename);

            return ['stateAction' => true];
        }
    }

    public function ExportLmCarrier($idCompany, $dateInit, $dateEnd, $route, $state, $typeExport)
    {
        $delimiter = ",";
        $filename  = $typeExport == 'download' ? "Report Lm Carrier " . date('Y-m-d H:i:s') . ".csv" : Auth::user()->id ."- Report Lm Carrier.csv";
        $file      = $typeExport == 'download' ? fopen('php://memory', 'w') : fopen(public_path($filename), 'w');

        //set column headers
        $fields = array('DATE', 'HOUR', 'PACKAGE ID', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'WEIGHT', 'ROUTE');

        fputcsv($file, $fields, $delimiter);

        $listPackageManifest = $this->getDataLmCarrier($idCompany, $dateInit, $dateEnd, $route, $state, $type = 'export');

        foreach($listPackageManifest as $packageManifest)
        {

            $lineData = array(
                                date('m-d-Y', strtotime($packageManifest->created_at)),
                                date('H:i:s', strtotime($packageManifest->created_at)),
                                $packageManifest->Reference_Number_1,
                                $packageManifest->Dropoff_Contact_Name,
                                $packageManifest->Dropoff_Contact_Phone_Number,
                                $packageManifest->Dropoff_Address_Line_1,
                                $packageManifest->Dropoff_City,
                                $packageManifest->Dropoff_Province,
                                $packageManifest->Dropoff_Postal_Code,
                                $packageManifest->Weight,
                                $packageManifest->Route
                            );

            fputcsv($file, $lineData, $delimiter);
        }

        if($typeExport == 'download')
        {
            fseek($file, 0);

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '";');

            fpassthru($file);
        }
        else
        {
            rewind($file);
            fclose($file);

            SendGeneralExport('Report Lm Carrier', $filename);

            return ['stateAction' => true];
        }
    }

    public function ExportAllPending($idCompany, $dateInit, $dateEnd, $state, $status, $typeExport)
    {
        $delimiter = ",";
        $filename  = $typeExport == 'download' ? "Report All Pending " . date('Y-m-d H:i:s') . ".csv" : Auth::user()->id ."- Report All Pending.csv";
        $file      = $typeExport == 'download' ? fopen('php://memory', 'w') : fopen(public_path($filename), 'w');

        $fields = array('DATE', 'HOUR', 'COMPANY', 'PACKAGE ID', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'WEIGHT', 'STATUS');

        fputcsv($file, $fields, $delimiter);

        $data                  = $this->getDataAllPending($idCompany, $dateInit, $dateEnd, $state, $status, $type = 'export');
        $packageListAllPending = $data['packageList'];

        foreach($packageListAllPending as $packagePending)
        {
            $lineData = array(
                                date('m/d/Y', strtotime($packagePending->created_at)),
                                date('H:i:s', strtotime($packagePending->created_at)),
                                $packagePending->company,
                                $packagePending->Reference_Number_1,
                                $packagePending->Dropoff_Contact_Name,
                                $packagePending->Dropoff_Contact_Phone_Number,
                                $packagePending->Dropoff_Address_Line_1,
                                $packagePending->Dropoff_City,
                                $packagePending->Dropoff_Province,
                                $packagePending->Dropoff_Postal_Code,
                                $packagePending->Weight,
                                $packagePending->status,
                            );

            fputcsv($file, $lineData, $delimiter);
        }

        if($typeExport == 'download')
        {
            fseek($file, 0);

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '";');

            fpassthru($file);
        }
        else
        {
            rewind($file);
            fclose($file);

            SendGeneralExport('Report All Pending', $filename);

            return ['stateAction' => true];
        }
    }

    public function ExportNotExists($dateInit, $dateEnd)
    {
        $delimiter = ",";
        $filename = "Report Not Exists " . date('Y-m-d H:i:s') . ".csv";

        //create a file pointer
        $file = fopen('php://memory', 'w');

        //set column headers
        $fields = array('DATE', 'HOUR', 'PACKAGE ID');

        fputcsv($file, $fields, $delimiter);


        $listPackageNotExists = PackageNotExists::whereBetween('Date_Inbound', [$dateInit, $dateEnd])
                                ->orderBy('Date_Inbound', 'desc')
                                ->get();

        foreach($listPackageNotExists as $packageNotExists)
        {
            $lineData = array(
                                date('m-d-Y', strtotime($packageNotExists->Date_Inbound)),
                                date('H:i:s', strtotime($packageNotExists->Date_Inbound)),
                                $packageNotExists->Reference_Number_1
                            );

            fputcsv($file, $lineData, $delimiter);
        }

        fseek($file, 0);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '";');

        fpassthru($file);
    }

    public function IndexAssigns()
    {
        return view('report.indexassigns');
    }

    public function GetStatus($Reference_Number_1)
    {
        $package = PackageManifest::find($Reference_Number_1);

        $package = $package != null ? $package : PackageInbound::find($Reference_Number_1);
        $package = $package != null ? $package : PackageWarehouse::find($Reference_Number_1);
        $package = $package != null ? $package : PackageDispatch::find($Reference_Number_1);
        $package = $package != null ? $package : PackageNeedMoreInformation::find($Reference_Number_1);
        $package = $package != null ? $package : PackageFailed::find($Reference_Number_1);
        $package = $package != null ? $package : PackagePreDispatch::find($Reference_Number_1);
        $package = $package != null ? $package : PackageLost::find($Reference_Number_1);
        $package = $package != null ? $package : PackageReturnCompany::find($Reference_Number_1);
        $package = $package != null ? $package : PackageLmCarrier::find($Reference_Number_1);

        $packageLast = PackageHistory::where('Reference_Number_1', $Reference_Number_1);

        if($package)
        {
            $packageLast = $packageLast->where('status', $package->status);

            if(count($packageLast->get()) == 0)
            {
                $packageLast = PackageHistory::where('Reference_Number_1', $Reference_Number_1);
            }

        }

        $packageFailed = PackageHistory::where('Reference_Number_1', $Reference_Number_1)
                                    ->where('status', 'Failed')
                                    ->get()
                                    ->last();

        $packageLast = $packageLast->get()->last();

        return [
            'description' => ($packageFailed ? $packageFailed->Description_Onfleet : ''),
            'status' => ($package ? $package->status : ''),
            'statusDate' => $packageLast->created_at,
            'statusDescription' => ($packageLast->Description != null || $packageLast->Description != '' ? $packageLast->Description : $packageLast->Description_Onfleet)
        ];
    }
}
