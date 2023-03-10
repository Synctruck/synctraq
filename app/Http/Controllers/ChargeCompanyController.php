<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{ ChargeCompany, ChargeCompanyDetail, PackageDelivery, PackageDispatch, PackageHistory, PackagePriceCompanyTeam, PeakeSeasonCompany, RangeDieselCompany };

use Illuminate\Support\Facades\Validator;

use Auth;
use DateTime;
use DB;
use Session;

class ChargeCompanyController extends Controller
{
    public function Index()
    {
        return view('charge.company');
    }
 
    public function List($dateStart, $dateEnd, $idCompany)
    {
        $dateStart = $dateStart .' 00:00:00';
        $dateEnd   = $dateEnd .' 23:59:59';

        $chargeList = ChargeCompany::whereBetween('created_at', [$dateStart, $dateEnd]);

        if($idCompany != 0)
        {
            $chargeList = $chargeList->where('idCompany', $idCompany);
        }

        $totalCharge = $chargeList->get()->sum('total');
        $chargeList  = $chargeList->with('company')
                                    ->orderBy('created_at', 'desc')
                                    ->paginate(50);

        return ['chargeList' => $chargeList, 'totalCharge' => number_format($totalCharge, 4)];
    }

    public function Export($idCharge)
    {
        $delimiter = ",";
        $filename = "CHARGE - COMPANIES  " . date('Y-m-d H:i:s') . ".csv";

        //create a file pointer
        $file    = fopen('php://memory', 'w');
        $charge = ChargeCompany::find($idCharge);

        //set column headers
        $fields = array('CHARGE', 'REGISTER DATE', date('m-d-Y H:i:s', strtotime($charge->created_at)), 'COMPANY', $charge->company->name, 'TOTAL', $charge->total);
        fputcsv($file, $fields, $delimiter);

        //set column headers
        $fields = array('', 'RANGE DATE', date('m-d-Y', strtotime($charge->startDate)) .' - '. date('m-d-Y', strtotime($charge->endDate)));
        fputcsv($file, $fields, $delimiter);

        $fields = array('');
        fputcsv($file, $fields, $delimiter);

        $fields = array('PACKAGE DELIVERIES');
        fputcsv($file, $fields, $delimiter);

        $fields    = array('DATE and HOUR', 'COMPANY', 'TEAM', 'PACKAGE ID', 'DIESEL PRICE', 'WEIGHT', 'WEIGHT ROUND', 'PRICE WEIGHT', 'PEAKE SEASON PRICE', 'PRICE BASE', 'SURCHARGE PERCENTAGE', 'SURCHAGE PRICE', 'TOTAL PRICE');

        fputcsv($file, $fields, $delimiter);

        $chargeCompanyDetailList = ChargeCompanyDetail::where('idChargeCompany', $idCharge)->get();

        foreach($chargeCompanyDetailList as $chargeDetail)
        {
            $packagePriceCompanyTeam = PackagePriceCompanyTeam::where('Reference_Number_1', $chargeDetail->Reference_Number_1)->first();
            $packageDelivery         = PackageDispatch::find($chargeDetail->Reference_Number_1);
            
            if($packageDelivery)
            {
                $team = $packageDelivery->team ? $packageDelivery->team->name : '';
                $date = date('m-d-Y', strtotime($packageDelivery->updated_at)) .' '. date('H:i:s', strtotime($packageDelivery->updated_at));
            }
            else
            {
                $team = '';
            }

            $lineData = array(

                $date,
                $packageDelivery->company,
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
    }
}