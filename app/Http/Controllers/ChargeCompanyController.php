<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{ ChargeCompany, ChargeCompanyDetail, ChargeCompanyAdjustment, PackageDelivery, 
                PackageDispatch, PackageHistory, PackagePriceCompanyTeam, 
                PeakeSeasonCompany, RangeDieselCompany, PackageReturnCompany };

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
 
    public function List($dateStart, $dateEnd, $idCompany, $status)
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

    public function Confirm($idCharge, $status)
    {
        $charge = ChargeCompany::find($idCharge);

        if($status == 'APPROVED')
        {
            $charge->idReceivable  = Auth::user()->id;
            $charge->status        = 'APPROVED';
        }
        else if($status == 'PAID')
        {
            $charge->idUserInvoiced = Auth::user()->id;
            $charge->status         = 'PAID';
        }

        $charge->save();

        return ['stateAction' => true];
    }

    public function Import(Request $request)
    {
        /*$handle     = fopen(public_path('file-import/HISTORY INVOICE 2023.csv'), "r");
        $lineNumber = 1;*/
        $countSave  = 0;
        $package_notexist= [];

        try
        {
            DB::beginTransaction();

            $dateInit = '2022-01-01 00:00:00';
            $dateEnd  = '2023-05-15 23:59:59';

            $chargeCompanyDetailList = ChargeCompanyDetail::whereBetween('created_at', [$dateInit, $dateEnd])->get();

            foreach($chargeCompanyDetailList as $chargeDetail)
            {
                $packageDispatch = PackageDispatch::where('Reference_Number_1', $chargeDetail->Reference_Number_1)
                                                    ->where('invoiced', 0)
                                                    ->where('status', 'Delivery')
                                                    ->first();

                if($packageDispatch)
                {
                    $packageDispatch->invoiced = 1;
                    $packageDispatch->save();

                    $countSave++;
                }
            }
            
            /*$package_notexist= [];
            $packages_inland = [];
            $package_ae      = [];
            $package_eight   = [];
            $package_chip    = [];
            $package_sm      = [];

            while (($raw_string = fgets($handle)) !== false)
            {
                if($lineNumber > 1)
                {
                    $row      = str_getcsv($raw_string);
                    $dateInit = '2022-01-01 00:00:00';
                    $dateEnd  = '2023-05-31 23:59:59';

                    $packageDispatch = PackageDispatch::where('Reference_Number_1', $row[0])
                                                    ->first();

                    if($packageDispatch)
                    {
                        $countSave++;
                    }
                    else
                    {
                        array_push($package_notexist, $row[0]);
                    }
                }

                $lineNumber++;
            }

            fclose($handle);*/

            DB::commit();

            return ['stateAction' => true, 'month' => 'mayo', 'countSave' => $countSave, 'package_notexist' => $package_notexist];
        }
        catch(Exception $e)
        {
            DB::rollback();

            return ['stateAction' => false];
        }
    }

    public function Export($idCharge)
    {
        $charge = ChargeCompany::with('company')->find($idCharge);

        $delimiter = ",";
        $filename  = "CHARGE - COMPANIES  " . date('Y-m-d H:i:s') . ".csv";
        $file      = fopen('php://memory', 'w');

        $fieldDate        = array('DATE', date('m/d/Y H:i:s'));
        $fieldIdPayment   = array('ID CHARGE', $idCharge);
        $fieldCompany     = array('COMPANY', $charge->company->name);
        $fietotalDelivery = array('TOTAL DELIVERY', $charge->totalDelivery .' $');
        $fielBlank        = array('');

        fputcsv($file, $fieldDate, $delimiter);
        fputcsv($file, $fieldIdPayment, $delimiter);
        fputcsv($file, $fieldCompany, $delimiter);
        fputcsv($file, $fietotalDelivery, $delimiter);
        fputcsv($file, $fielBlank, $delimiter);

        $chargeCompanyAdjustmentList = ChargeCompanyAdjustment::where('idCharge', $idCharge)
                                                                ->orderBy('created_at', 'asc')
                                                                ->get();

        if(count($chargeCompanyAdjustmentList) > 0)
        {
            fputcsv($file, array('ADJUSTMENT'), $delimiter);
            fputcsv($file, array('TOTAL ADJUSTMENT', $charge->totalRevert .' $'), $delimiter);
            fputcsv($file, array('DATE', 'DESCRIPTION', 'AMOUNT'), $delimiter);

            foreach($chargeCompanyAdjustmentList as $chargeAdjustment)
            {
                $lineDataAdjustment = array(
                    date('m/d/y H:i:s', strtotime($chargeAdjustment->created_at)),
                    $chargeAdjustment->description,
                    $chargeAdjustment->amount
                );

                fputcsv($file, $lineDataAdjustment, $delimiter);
            }

            fputcsv($file, $fielBlank, $delimiter);
            fputcsv($file, $fielBlank, $delimiter);
        }
        
        fputcsv($file, array('DATE', 'COMPANY', 'TEAM', 'PACKAGE ID', 'PRICE FUEL', 'WEIGHT COMPANY', 'DIM WEIGHT ROUND COMPANY', 'PRICE WEIGHT COMPANY', 'PEAKE SEASON PRICE COMPANY', 'PRICE BASE COMPANY', 'SURCHARGE PERCENTAGE COMPANY', 'SURCHAGE PRICE COMPANY', 'TOTAL PRICE COMPANY'), $delimiter);

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
                $packageDelivery = PackageReturnCompany::find($chargeDetail->Reference_Number_1);

                $date = date('m-d-Y', strtotime($packageDelivery->created_at)) .' '. date('H:i:s', strtotime($packageDelivery->created_at));
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
    }
}