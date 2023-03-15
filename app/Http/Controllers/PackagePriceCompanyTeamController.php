<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{ Configuration, HistoryDiesel, PackagePriceCompanyTeam, PeakeSeasonCompany };

use App\Http\Controllers\{ CompanyController, RangePriceCompanyController };

use Illuminate\Support\Facades\Validator;

use DB;
use Log;
use Session;

class PackagePriceCompanyTeamController extends Controller
{
    public function Insert($packageDispatch)
    {
        Log::info("==== REGISTER - PRICE COMPANY TEAM");
        $packagePriceCompanyTeam = PackagePriceCompanyTeam::where('Reference_Number_1', $packageDispatch->Reference_Number_1)->first();

        if($packagePriceCompanyTeam == null)
        {
            $packagePriceCompanyTeam = new PackagePriceCompanyTeam();

            $packagePriceCompanyTeam->id =  date('YmdHis') .'-'. $packageDispatch->Reference_Number_1;
        }

        $Reference_Number_1    = $packageDispatch->Reference_Number_1;
        $weight                = $packageDispatch->Weight;

        $historyDieselList = HistoryDiesel::orderBy('changeDate', 'asc')->get();

        $dieselPriceCompany = 0;
        $getDiesel          = 0;

        foreach($historyDieselList as $historyDiesel)
        {
            if($getDiesel == 0)
            {
                if(date('Y-m-d', strtotime($historyDiesel)) >= date('Y-m-d', strtotime($packageDispatch->Date_Delivery)))
                {
                    $dieselPriceCompany = $historyDiesel->price;
                    $getDiesel          = 1;
                }
            }
        }

        $dimWeightCompanyRound = ceil($packageDispatch->Weight);

        $priceWeightCompany = new RangePriceCompanyController();
        $priceWeightCompany = $priceWeightCompany->GetPriceCompany($packageDispatch->idCompany, $dimWeightCompanyRound);

        $peakeSeasonPriceCompany = PeakeSeasonCompany::where('idCompany', $packageDispatch->idCompany)->first();

        $date = strtotime(date('Y-m-d'));

        if($date >= strtotime($peakeSeasonPriceCompany->start_date)  && $date <= strtotime($peakeSeasonPriceCompany->end_date))
        {
            if($dimWeightCompanyRound <= $peakeSeasonPriceCompany->lb1_weight)
            {
                $peakeSeasonPriceCompany = $peakeSeasonPriceCompany->lb1_weight_price;
            }
            else if($dimWeightCompanyRound > $peakeSeasonPriceCompany->lb1_weight)
            {
                $peakeSeasonPriceCompany = $peakeSeasonPriceCompany->lb2_weight_price;
            }
        }
        else
        {
            $peakeSeasonPriceCompany = 0;
        }

        $priceBaseCompany = number_format($priceWeightCompany + $peakeSeasonPriceCompany, 2);

        $companyController          = new CompanyController();
        $surchargePercentageCompany = $companyController->GetPercentage($packageDispatch->idCompany, $dieselPriceCompany);
        $surchargePriceCompany      = number_format(($priceBaseCompany * $surchargePercentageCompany) / 100, 4);
        $totalPriceCompany          = number_format($priceBaseCompany + $surchargePriceCompany, 4);

        $packagePriceCompanyTeam->idCompany                  = $packageDispatch->idCompany;
        $packagePriceCompanyTeam->company                    = $packageDispatch->company;
        $packagePriceCompanyTeam->Reference_Number_1         = $packageDispatch->Reference_Number_1;
        $packagePriceCompanyTeam->weight                     = $weight;
        $packagePriceCompanyTeam->dieselPriceCompany         = $dieselPriceCompany;
        $packagePriceCompanyTeam->dimWeightCompanyRound      = $dimWeightCompanyRound;
        $packagePriceCompanyTeam->priceWeightCompany         = $priceWeightCompany;
        $packagePriceCompanyTeam->peakeSeasonPriceCompany    = $peakeSeasonPriceCompany;
        $packagePriceCompanyTeam->priceBaseCompany           = $priceBaseCompany;
        $packagePriceCompanyTeam->surchargePercentageCompany = $surchargePercentageCompany;
        $packagePriceCompanyTeam->surchargePriceCompany      = $surchargePriceCompany;
        $packagePriceCompanyTeam->totalPriceCompany          = $totalPriceCompany;
        $packagePriceCompanyTeam->Date_Delivery              = $packageDispatch->Date_Delivery;

        $packagePriceCompanyTeam->save();

        Log::info("==== CORRECT - PRICE COMPANY TEAM");
    }
}