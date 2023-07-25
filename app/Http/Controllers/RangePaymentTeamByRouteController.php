<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\{ RangePriceTeamByRoute  };

use Illuminate\Support\Facades\Validator;

use Log;

class RangePaymentTeamByRouteController extends Controller
{
    public function List($idTeam)
    {
        $rangeList = RangePriceTeamByRoute::where('idTeam', $idTeam)
                                        ->orderBy('price', 'asc')
                                        ->get();

        return ['rangeList' => $rangeList];
    }

    public function Insert(Request $request)
    {
        $validator = Validator::make($request->all(),

            [
                "idTeam" => ["required"],
                "route" => ["required", "min:3", "max:20"],
                "price" => ["required", "max:999", "numeric"],
            ],
            [
                "idTeam.required" => "Seleccione un team",

                "route.required" => "The field is required",
                "route.min"  => "Enter minimum 3 digits",
                "route.max"  => "Enter maximum 20 digits",

                "price.required" => "The field is required",
                "price.numeric"  => "Enter only numbers",
            ]
        );

        if($validator->fails())
        {
            return response()->json(["status" => 422, "errors" => $validator->errors()], 422);
        }

        //$pricePecercentaje = $this->CalculatePricePecercentaje($request->get('price'), $request->get('fuelPercentage'));

        $range = new RangePriceTeamByRoute();
        $range->idTeam = $request->get('idTeam');
        $range->route  = $request->get('route');
        $range->price  = $request->get('price');
        $range->save();

        return ['stateAction' => true];
    }

    public function Get($idRange)
    {
        $range = RangePriceTeamByRoute::find($idRange);

        return ['range' => $range];
    }

    public function Update(Request $request, $idRange)
    {
        $validator = Validator::make($request->all(),

            [
                "idTeam" => ["required"],
                "route" => ["required", "min:3", "max:20"],
                "price" => ["required", "max:999", "numeric"],
            ],
            [
                "idTeam.required" => "Seleccione un team",

                "route.required" => "The field is required",
                "route.min"  => "Enter minimum 3 digits",
                "route.max"  => "Enter maximum 20 digits",

                "price.required" => "The field is required",
                "price.numeric"  => "Enter only numbers",
            ]
        );

        if($validator->fails())
        {
            return response()->json(["status" => 422, "errors" => $validator->errors()], 422);
        }

        $range = RangePriceTeamByRoute::find($idRange);
        $range->route = $request->get('route');
        $range->price = $request->get('price');
        $range->save();

        return ['stateAction' => true];
    }

    public function Delete($idRange)
    {
        $range = RangePriceTeamByRoute::find($idRange);

        $range->delete();

        return ['stateAction' => true];
    }
}