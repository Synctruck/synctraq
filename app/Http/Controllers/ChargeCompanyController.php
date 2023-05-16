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

    public function Confirm($idCharge)
    {
        $charge = ChargeCompany::find($idCharge);

        $charge->idUser = Auth::user()->id;
        $charge->status = 'INVOICE';

        $charge->save();

        return ['stateAction' => true];
    }

    public function Import(Request $request)
    {
        $handle     = fopen(public_path('file-import/HISTORY INVOICE 2023.csv'), "r");
        $lineNumber = 1;
        $countSave  = 0;

        try
        {
            DB::beginTransaction();

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
                    $dateInit = '2023-05-01 00:00:00';
                    $dateEnd  = '2023-05-31 23:59:59';

                    $packageDispatch = PackageDispatch::where('Reference_Number_1', $row[0])
                                                        ->whereBetween('Date_Delivery', [$dateInit, $dateEnd])
                                                        ->where('invoiced', 0)
                                                        ->first();

                    if($packageDispatch)
                    {
                        //$packageDispatch->invoiced = 1;
                        //$packageDispatch->save();

                        $countSave++;
                        /*if($packageDispatch->company == 'INLAND LOGISTICS')
                        {
                            array_push($packages_inland, $packageDispatch->Reference_Number_1);
                        }
                        else if($packageDispatch->company == 'AMERICAN EAGLE')
                        {
                            array_push($packages_inland, $packageDispatch->Reference_Number_1);
                        }
                        else if($packageDispatch->company == 'EIGHTCIG')
                        {
                            array_push($packages_inland, $packageDispatch->Reference_Number_1);
                        }
                        else if($packageDispatch->company == 'CHIP CITY COOKIES')
                        {
                            array_push($packages_inland, $packageDispatch->Reference_Number_1);
                        }
                        else if($packageDispatch->company == 'Smart Kargo')
                        {
                            array_push($packages_inland, $packageDispatch->Reference_Number_1);
                        }*/
                    }
                }

                $lineNumber++;
            }

            fclose($handle);

            DB::commit();

            return ['stateAction' => true, 'month' => 'mayo', 'countSave' => $countSave];
        }
        catch(Exception $e)
        {
            DB::rollback();

            return ['stateAction' => false];
        }
    }

    public function Export($idCharge)
    {
        $delimiter = ",";
        $filename = "CHARGE - COMPANIES  " . date('Y-m-d H:i:s') . ".csv";

        //create a file pointer
        $file   = fopen('php://memory', 'w');
        $charge = ChargeCompany::find($idCharge);
        $fields = array('DATE', 'COMPANY', 'PACKAGE ID', 'PRICE FUEL', 'WEIGHT COMPANY', 'DIM WEIGHT ROUND COMPANY', 'PRICE WEIGHT COMPANY', 'PEAKE SEASON PRICE COMPANY', 'PRICE BASE COMPANY', 'SURCHARGE PERCENTAGE COMPANY', 'SURCHAGE PRICE COMPANY', 'TOTAL PRICE COMPANY');

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