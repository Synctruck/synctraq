<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\{ RangePriceBaseTeam  };

use Illuminate\Support\Facades\Validator;

use Log;

class RangePaymentTeamController extends Controller
{
    public function List($idTeam)
    {
        $rangeList = RangePriceBaseTeam::where('idTeam', $idTeam)
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

        $range = new RangePriceBaseTeam();

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
        $range = RangePriceBaseTeam::find($idRange);

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

        $range = RangePriceBaseTeam::find($idRange);

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
        $range = RangePriceBaseTeam::find($idRange);

        $range->delete();

        return ['stateAction' => true];
    }
}