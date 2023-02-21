<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{ Configuration, PackagePriceCompanyTeam, PeakeSeasonCompany };

use App\Http\Controllers\{ CompanyController, RangePriceCompanyController };

use Illuminate\Support\Facades\Validator;

use DB;
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

        $Reference_Number_1    = $packageDispatch->Reference_Number_1
        $weight                = $packageDispatch->Weight;
        $dieselPriceCompany    = Configuration::first()->diesel_price;
        $dimWeightCompanyRound = ceil($packageDispatch->Weight);

        $priceWeightCompany = new RangePriceCompanyController();
        $priceWeightCompany = $priceWeightCompany->GetPriceCompany($packageDispatch->idCompany, $weight);

        $peakeSeasonPriceCompany = PeakeSeasonCompany::where('idCompany', $packageDispatch->idCompany)
                                                    ->first();

        $date = strtotime(date('Y-m-d'));

        if($start_date >= $peakeSeasonPriceCompany->startDate  && $peakeSeasonPriceCompany->end_date <= $date)
        {
            if($weight <= $peakeSeasonPriceCompany->lb1_weight)
            {
                $peakeSeasonPriceCompany = $peakeSeasonPriceCompany->lb1_weight_price;
            }
            else if($weight > $peakeSeasonPriceCompany->lb1_weight)
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

        $packagePriceCompanyTeam = new PackagePriceCompanyTeam();

        $packagePriceCompanyTeam->id                         = date('YmdHis');
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

        $packagePriceCompanyTeam->save();

        Log::info("==== CORRECT - PRICE COMPANY TEAM");
    }
}