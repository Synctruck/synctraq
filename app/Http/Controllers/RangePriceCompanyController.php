<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\{ Company, PackageDispatch, PackageHistory, RangePriceCompany, RangePriceCompanyZipCode };

use Illuminate\Support\Facades\Validator;

use Log;

class RangePriceCompanyController extends Controller
{
    public function List($idCompany)
    {
        $rangeList = RangePriceCompany::where('idCompany', $idCompany)
                            ->orderBy('minWeight', 'asc')
                            ->get();

        return ['rangeList' => $rangeList];
    }

    public function Insert(Request $request)
    {
        $validator = Validator::make($request->all(),

            [
                "idCompany" => ["required"],
                "minWeight" => ["required", "min:1", "max:126", "numeric"],
                "maxWeight" => ["required", "min:1", "max:126", "numeric"],
                "price" => ["required", "max:999", "numeric"],
            ],
            [
                "minWeight.required" => "The field is required",
                "minWeight.min"  => "Enter minimum 1",
                "minWeight.max"  => "Enter maximum 126",
                "minWeight.numeric"  => "Enter only numbers",

                "maxWeight.required" => "The field is required",
                "maxWeight.min"  => "Enter minimum 1",
                "maxWeight.max"  => "Enter maximum 126",
                "maxWeight.numeric"  => "Enter only numbers",

                "price.required" => "The field is required",
                "price.max"  => "Enter maximum 999",
                "price.numeric"  => "Enter only numbers",
            ]
        );

        if($validator->fails())
        {
            return response()->json(["status" => 422, "errors" => $validator->errors()], 422);
        }

        //$pricePecercentaje = $this->CalculatePricePecercentaje($request->get('price'), $request->get('fuelPercentage'));

        $range = new RangePriceCompany();

        $range->idCompany       = $request->get('idCompany');
        $range->minWeight       = $request->get('minWeight');
        $range->maxWeight       = $request->get('maxWeight');
        $range->price           = $request->get('price');
        /*$range->fuelPercentage  = $request->get('fuelPercentage');
        $range->pricePercentage = $pricePecercentaje['pricePercentage'];
        $range->total           = $pricePecercentaje['total'];*/

        $range->save();

        return ['stateAction' => true];
    }

    public function Get($idRange)
    {
        $range = RangePriceCompany::find($idRange);

        return ['range' => $range];
    }

    public function Update(Request $request, $idRange)
    {
        $validator = Validator::make($request->all(),

            [
                "idCompany" => ["required"],
                "minWeight" => ["required", "min:0", "max:126", "numeric"],
                "maxWeight" => ["required", "min:0", "max:126", "numeric"],
                "price" => ["required", "max:999", "numeric"],
            ],
            [
                "minWeight.required" => "The field is required",
                "minWeight.min"  => "Enter minimum 0",
                "minWeight.max"  => "Enter maximum 126",
                "minWeight.numeric"  => "Enter only numbers",

                "maxWeight.required" => "The field is required",
                "maxWeight.min"  => "Enter minimum 0",
                "maxWeight.max"  => "Enter maximum 126",
                "maxWeight.numeric"  => "Enter only numbers",

                "price.required" => "The field is required",
                "price.max"  => "Enter maximum 999",
                "price.numeric"  => "Enter only numbers",
            ]
        );

        if($validator->fails())
        {
            return response()->json(["status" => 422, "errors" => $validator->errors()], 422);
        }

        //$pricePecercentaje = $this->CalculatePricePecercentaje($request->get('price'), $request->get('fuelPercentage'));

        $range = RangePriceCompany::find($idRange);

        $range->idCompany       = $request->get('idCompany');
        $range->minWeight       = $request->get('minWeight');
        $range->maxWeight       = $request->get('maxWeight');
        $range->price           = $request->get('price');
        /*$range->fuelPercentage  = $request->get('fuelPercentage');
        $range->pricePercentage = $pricePecercentaje['pricePercentage'];
        $range->total           = $pricePecercentaje['total'];*/

        $range->save();

        return ['stateAction' => true];
    }

    public function Delete($idRange)
    {
        $range = RangePriceCompany::find($idRange);

        $range->delete();

        return ['stateAction' => true];
    }

    public function GetPriceCompany($idCompany, $weight, $Reference_Number_1)
    {
        $company = Company::find($idCompany);

        if($company->name == 'Smart Kargo')
        {
            $range =  $this->GetPriceCompanySmartKargo($idCompany, $weight, $Reference_Number_1);
        }
        else
        {
            $searchRangePriceCompany = false;

            Log::info('COMPANY: '. $company->name);

            if($company->name == 'EIGHTVAPE')
            {
                Log::info('new ranfe EIGHTVAPE');

                $packageHistory = PackageHistory::where('Reference_Number_1', $Reference_Number_1)
                                                ->where('status', 'Manifest')
                                                ->first();

                Log::info('postal code: '. $packageHistory->Dropoff_Postal_Code);

                $range = RangePriceCompanyZipCode::where('zipCode', $packageHistory->Dropoff_Postal_Code)->first();

                if($range == null)
                {
                    $searchRangePriceCompany = true;
                }
            }

            if($searchRangePriceCompany)
            {
                $range = RangePriceCompany::where('idCompany', $idCompany)
                                    ->where('minWeight', '<=', $weight)
                                    ->where('maxWeight', '>=', $weight)
                                    ->first();
            }
        }

        if($range == null)
        {
            $range = RangePriceCompany::orderBy('price', 'desc')->first();
        }

        return $range->price;
    }

    public function GetPriceCompanySmartKargo($idCompany, $weight, $Reference_Number_1)
    {
        $packageHistory = PackageHistory::where('Reference_Number_1', $Reference_Number_1)
                                        ->where('status', 'Manifest')
                                        ->first();

        $date      = date('Y-m-d', strtotime($packageHistory->created_at));
        $startDate = $date .' 00:00:00';
        $endDate   = $date .' 23:59:59';

        $quantityPackagesHistory = PackageHistory::whereBetween('created_at', [$startDate, $endDate])
                                                ->where('status', 'Manifest')
                                                ->where('idCompany', $idCompany)
                                                ->get()
                                                ->count();

        Log::info('QuantityPackage SM');
        Log::info($quantityPackagesHistory);
        Log::info($Reference_Number_1);
        
        $range = RangePriceCompany::where('idCompany', $idCompany)
                                ->where('minWeight', '<=', $weight)
                                ->where('maxWeight', '>=', $weight)
                                ->orderBy('price', 'desc')
                                ->get();

        Log::info($range);
        
        if($quantityPackagesHistory <= 500)
        {
            $priceBaseCompany = $range[0];
        }
        else if($quantityPackagesHistory > 500 && $quantityPackagesHistory < 1200)
        {
            $priceBaseCompany = $range[1];
        }
        else if($quantityPackagesHistory >= 1200)
        {
            $priceBaseCompany = $range[2];
        }

        return $priceBaseCompany;
    }

    public function CalculatePricePecercentaje($price, $fuelPercentage)
    {
        $pricePercentage = ($price * $fuelPercentage) / 100;
        $total           = $price + $pricePercentage;

        return ['pricePercentage' => $pricePercentage, 'total' => $total];
    }

    public function UpdatePrices()
    {
        $listAll = PackageDispatch::where('pricePaymentCompany', 0.00)->get();

        foreach($listAll as $packageDispatch)
        {
            $packageDispatch = PackageDispatch::find($packageDispatch->Reference_Number_1);

            $range = RangePriceCompany::where('idCompany', $packageDispatch->idCompany)
                                ->where('minWeight', '<=', $packageDispatch->Weight)
                                ->where('maxWeight', '>=', $packageDispatch->Weight)
                                ->first();

            if($range)
            {
                $packageDispatch->pricePaymentCompany = $range->price;

                $packageDispatch->save();
            }
        }

        return 'updated completed';
    }
}