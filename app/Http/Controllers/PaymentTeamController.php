<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{ 
            Configuration, HistoryDiesel, PaymentTeam, PaymentTeamDetail, 
            PackageDispatch, PeakeSeasonTeam, RangePriceBaseTeam, RangeDieselTeam,  
            RangePriceTeamByRoute, RangePriceTeamByCompany, User };

use Illuminate\Support\Facades\Validator;

use Auth;
use DateTime;
use DB;
use Session;

class PaymentTeamController extends Controller
{
    public function Index()
    {
        $files     = [];
        $nowDate   = date('Y-07-02');
        $startDate = date('Y-06-01');
        $endDate   = date('Y-m-d', strtotime($nowDate .' -2 day'));

        $teamsList = User::with(['drivers', 'role', 'routes_team'])
                            ->where('idRole', 3)
                            ->orderBy('name', 'asc')
                            ->get();

        foreach($teamsList as $team)
        {
            $listPackageDelivery = PackageDispatch::whereBetween('Date_Delivery', [$startDate, $endDate])
                                                    ->where('idTeam', $team->id)
                                                    ->where('status', 'Delivery')
                                                    ->get();

            if($listPackageDelivery)
            {
                foreach($listPackageDelivery as $packageDelivery)
                {
                    $dimFactor = 200;
                    $weight    = $packageDelivery->Weight;
                    $weightRound = ceil($weight);


                    $dieselPrice = $this->GetDieselPrice('today', $packageDelivery);

                    if($dieselPrice)
                    {
                        $range = RangePriceBaseTeam::where('idTeam', $packageDelivery->idTeam)
                                                    ->where('minWeight', '<=', $weight)
                                                    ->where('maxWeight', '>=', $weight)
                                                    ->first();

                        if($range)
                        {
                            $priceWeight = $range->price;
                            $peakeSeasonPrice = $this->GetPeakeSeasonTeam($packageDelivery);
                            $priceBase = number_format($priceWeight + $peakeSeasonPrice, 2);

                            $surchargePercentage = $this->GetSurchargePercentage($packageDelivery->idTeam, $dieselPrice);

                            $surchargePrice = number_format(($priceBase * $surchargePercentage) / 100, 4);
                            $priceByRoute = $this->GetPriceTeamByRoute($packageDelivery->idTeam, $packageDelivery->Route);
                            $priceByCompany = $this->GetPriceTeamByCompany($packageDelivery->idTeam, $packageDelivery->idCompany);

                            //$totalPrice     = number_format($priceBase + $surchargePrice, 4);

                            //dd($totalPrice);
                            

                            $totalPrice     = number_format($priceBase + $surchargePrice + $priceByRoute + $priceByCompany, 4);

                            dd($totalPrice);

                        }


                        dd($range);


                    }


                    dd($dieselPrice);
                }
                dd($listPackageDelivery);
            }
        }

        

        dd($endDate);
        dd(2);
        return view('charge.company');
    }
    
    public function GetDieselPrice($from, $packageDelivery)
    {
        $historyDieselList = HistoryDiesel::orderBy('changeDate', 'asc')->get();

        $dieselPriceCompany = 0;

        if($from == 'today')
        {
            $dieselPriceCompany = Configuration::first()->diesel_price;
        }
        else
        {
            $historyDieselList = HistoryDiesel::orderBy('changeDate', 'asc')->get();

            foreach($historyDieselList as $historyDiesel)
            {
                $nowDate             = date('Y-m-d', strtotime($historyDiesel->changeDate));
                $timeChangeDateStart = strtotime($nowDate);
                $timeChangeDateEnd   = strtotime(date('Y-m-d', strtotime($nowDate .' +6 day')));
                $timeDeliveryDate    = strtotime(date('Y-m-d', strtotime($packageDelivery->Date_Delivery)));

                if($timeChangeDateStart <= $timeDeliveryDate && $timeDeliveryDate <= $timeChangeDateEnd)
                {
                    $dieselPriceCompany = $historyDiesel->roundPrice;
                }
            }
        }

        return $dieselPriceCompany;
    }

    public function GetPeakeSeasonTeam($packageDelivery)
    {
        $peakeSeasonPriceTeam = PeakeSeasonTeam::where('idTeam', $packageDelivery->idTeam)->first();

        $date = strtotime(date('Y-m-d'));

        if($date >= strtotime($peakeSeasonPriceTeam->start_date)  && $date <= strtotime($peakeSeasonPriceTeam->end_date))
        {
            if($dimWeightCompanyRound <= $peakeSeasonPriceTeam->lb1_weight)
            {
                $peakeSeasonPriceTeam = $peakeSeasonPriceTeam->lb1_weight_price;
            }
            else if($dimWeightCompanyRound > $peakeSeasonPriceTeam->lb1_weight)
            {
                $peakeSeasonPriceTeam = $peakeSeasonPriceTeam->lb2_weight_price;
            }
        }
        else
        {
            $peakeSeasonPriceTeam = 0;
        }

        return $peakeSeasonPriceTeam;
    }

    public function GetSurchargePercentage($idTeam, $dieselPrice)
    {
        $surchargePercentage = RangeDieselTeam::where('idTeam', $idTeam)
                                            ->where('at_least', '<=', $dieselPrice)
                                            ->where('but_less', '>=',  $dieselPrice)
                                            ->first();

        if($surchargePercentage)
        {
            return $surchargePercentage->surcharge_percentage;
        }

        return 0;
    }

    public function GetPriceTeamByRoute($idTeam, $route)
    {
        $range = RangePriceTeamByRoute::where('idTeam', $idTeam)
                                    ->where('route', $route)
                                    ->first();

        if($range)
        {
            return $range->price;
        }

        return 0;
    }

    public function GetPriceTeamByCompany($idTeam, $idCompany)
    {
        $range = RangePriceTeamByCompany::where('idTeam', $idTeam)
                                    ->where('idCompany', $idCompany)
                                    ->first();

        if($range)
        {
            return $range->price;
        }

        return 0;
    }
    /*public function List($dateStart, $dateEnd, $idCompany, $status)
    {
        $dateStart = $dateStart .' 00:00:00';
        $dateEnd   = $dateEnd .' 23:59:59';

        $chargeList = ChargeCompany::whereBetween('created_at', [$dateStart, $dateEnd]);

        if($idCompany != 0)
        {
            $chargeList = $chargeList->where('idCompany', $idCompany);
        }

        if($status != 'all')
        {
            $chargeList = $chargeList->where('status', $status);
        }

        $totalCharge = $chargeList->get()->sum('total');
        $chargeList  = $chargeList->with('company')
                                    ->orderBy('created_at', 'desc')
                                    ->paginate(50);

        return ['chargeList' => $chargeList, 'totalCharge' => number_format($totalCharge, 4)];
    }

    public function Confirm($idCharge)
    {
        $charge = ChargeCompany::find($idCharge);

        $charge->idUser = Auth::user()->id;
        $charge->status = 'INVOICE';

        $charge->save();

        return ['stateAction' => true];
    }

    public function Export($idCharge)
    {
        $delimiter = ",";
        $filename = "CHARGE - COMPANIES  " . date('Y-m-d H:i:s') . ".csv";

        //create a file pointer
        $file   = fopen('php://memory', 'w');
        $charge = ChargeCompany::find($idCharge);
        $fields = array('DATE', 'COMPANY', 'TEAM', 'PACKAGE ID', 'PRICE FUEL', 'WEIGHT COMPANY', 'DIM WEIGHT ROUND COMPANY', 'PRICE WEIGHT COMPANY', 'PEAKE SEASON PRICE COMPANY', 'PRICE BASE COMPANY', 'SURCHARGE PERCENTAGE COMPANY', 'SURCHAGE PRICE COMPANY', 'TOTAL PRICE COMPANY');

        fputcsv($file, $fields, $delimiter);

        $chargeCompanyDetailList = ChargeCompanyDetail::where('idChargeCompany', $idCharge)->get();

        foreach($chargeCompanyDetailList as $chargeDetail)
        {
            $packagePriceCompanyTeam = PackagePriceCompanyTeam::where('Reference_Number_1', $chargeDetail->Reference_Number_1)->first();
            $packageDelivery         = PackageDispatch::find($chargeDetail->Reference_Number_1);
            
            if($packageDelivery)
            {
                $team = $packageDelivery->team ? $packageDelivery->team->name : '';
                $date = date('m-d-Y', strtotime($packageDelivery->Date_Delivery)) .' '. date('H:i:s', strtotime($packageDelivery->Date_Delivery));
            }
            else
            {
                $team = '';
            }

            $lineData = array(

                $date,
                ($packageDelivery ? $packageDelivery->company : ''),
                $team,
                $chargeDetail->Reference_Number_1,
                $packagePriceCompanyTeam->dieselPriceCompany,
                $packagePriceCompanyTeam->weight,
                $packagePriceCompanyTeam->dimWeightCompanyRound,
                $packagePriceCompanyTeam->priceWeightCompany,
                $packagePriceCompanyTeam->peakeSeasonPriceCompany,
                $packagePriceCompanyTeam->priceBaseCompany,
                $packagePriceCompanyTeam->surchargePercentageCompany,
                $packagePriceCompanyTeam->surchargePriceCompany,
                $packagePriceCompanyTeam->totalPriceCompany
            );

            fputcsv($file, $lineData, $delimiter);
        }
 
        fseek($file, 0);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '";');

        fpassthru($file);
    }*/
}