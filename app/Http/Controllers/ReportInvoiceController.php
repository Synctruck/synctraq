<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Controllers\PackageAgeController;

use App\Models\{ ChargeCompanyDetail, PaymentTeamDetail, PackagePriceCompanyTeam, PackageDispatch, PackageHistory };

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

use Session;

class ReportInvoiceController extends Controller
{
    public function Index()
    {
        return view('report.indexreportinvoices');
    }

    public function List($idCompany, $dateInit, $dateEnd, $idTeam, $idDriver, $route, $state)
    {
        $data                  = $this->getDataDelivery($idCompany, $dateInit, $dateEnd, $idTeam, $idDriver, $route, $state);
        $packageHistoryList    = $data['packageHistoryList'];
        $packageHistoryListNew = $data['listAll'];

        $roleUser = Auth::user()->role->name;

        $listState = PackageDispatch::select('Dropoff_Province')
                                    ->where('status', 'Delivery')
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        return [
            'packageHistoryList' => $packageHistoryList,
            'reportList' => $packageHistoryListNew,
            'listState' => $listState,
            'roleUser' => $roleUser
        ];
    }

    private function getDataDelivery($idCompany, $dateInit, $dateEnd, $idTeam, $idDriver, $route, $state,$type='list'){

        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);

        $listAll = PackageDispatch::whereBetween('Date_Delivery', [$dateInit, $dateEnd])
                                    ->where('status', 'Delivery')
                                    ->where('invoiced', 1)
                                    ->where('paid', 1);

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
            $chargeDetail  = ChargeCompanyDetail::find($packageDelivery->Reference_Number_1);
            $paymentDetail = PaymentTeamDetail::find($packageDelivery->Reference_Number_1);
            
            if($chargeDetail && $paymentDetail)
            {
                $packagePriceCompanyTeam = PackagePriceCompanyTeam::where('Reference_Number_1', $packageDelivery->Reference_Number_1)->first();

                if($packagePriceCompanyTeam)
                {
                    $priceProfit = $packagePriceCompanyTeam->totalPriceCompany - $paymentDetail->totalPrice;

                    $package = [
                        "idOnfleet" => $packageDelivery->idOnfleet,
                        "Date_Delivery" => $packageDelivery->Date_Delivery,
                        "company" => $packageDelivery->company,
                        "team" => $packageDelivery->team,
                        "driver" => $packageDelivery->driver,
                        "Reference_Number_1" => $packageDelivery->Reference_Number_1,
                        "Dropoff_City" => $packageDelivery->Dropoff_City,
                        "Dropoff_Province" => $packageDelivery->Dropoff_Province,
                        "Dropoff_Postal_Code" => $packageDelivery->Dropoff_Postal_Code,
                        "Weight" => $packageDelivery->Weight,
                        "Route" => $packageDelivery->Route,
                        "priceCompany" => $packageDelivery->pricePaymentTeam,
                        "priceTeam" => $packageDelivery->pieces,
                        "priceProfit" => $priceProfit,
                    ];

                    array_push($packageHistoryListNew, $package);
                }
            }
            
        }

        return [

            'packageHistoryList' => $listAll,
            'listAll' => $packageHistoryListNew,
        ];
    }

    public function Export($idCompany, $dateInit, $dateEnd, $idTeam, $idDriver, $route, $state, $typeExport)
    {
        $delimiter = ",";
        $filename  = $typeExport == 'download' ? "Report Invoice " . date('Y-m-d H:i:s') . ".csv" : Auth::user()->id ."- Report Invoice.csv";
        $file      = $typeExport == 'download' ? fopen('php://memory', 'w') : fopen(public_path($filename), 'w');

        //set column headers
        $fields = array('DATE', 'DELIVERY DATE', 'INBOUND DATE', 'COMPANY', 'TEAM', 'DRIVER', 'PACKAGE ID', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'WEIGHT', 'ROUTE', 'PPPC', 'PIECES', 'URL-IMAGE-1', 'URL-IMAGE-2');

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
            
            $lineData = array(
                                date('m/d/Y H:i:s', strtotime($packageDelivery['Date_Delivery'])),
                                date('m/d/Y H:i:s', strtotime($packageDelivery['inboundDate'])),
                                date('m/d/Y H:i:s', strtotime($packageDelivery['created_at'])),
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

            SendGeneralExport('Report Invoice', $filename);

            return ['stateAction' => true];
        }
    }
}