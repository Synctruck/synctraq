<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\RangePriceTeam;

use Illuminate\Support\Facades\Validator;

class RangePriceTeamRouteCompanyController extends Controller
{
    public function List($idTeam, $idCompany, $route)
    {
        $rangeList = RangePriceTeam::where('idTeam', $idTeam)
                                    ->where('idCompany', $idCompany)
                                    ->where('route', $route)
                                    ->orderBy('minWeight', 'asc')
                                    ->get();

        return ['rangeList' => $rangeList];
    }

    public function Insert(Request $request)
    {
        $validator = Validator::make($request->all(),

            [
                "idTeam" => ["required"],
                "idCompany" => ["required"],
                "route" => ["required"],
                "minWeight" => ["required", "min:1", "max:126", "numeric"],
                "maxWeight" => ["required", "min:1", "max:126", "numeric"],
                "price" => ["required", "max:999", "numeric"],
            ],
            [
                "idCompany.required" => "Select item",

                "route.required" => "Select item",

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

        $range = new RangePriceTeam();

        $range->idTeam    = $request->get('idTeam');
        $range->idCompany = $request->get('idCompany');
        $range->route     = $request->get('route');
        $range->minWeight = $request->get('minWeight');
        $range->maxWeight = $request->get('maxWeight');
        $range->price     = $request->get('price');

        $range->save();

        return ['stateAction' => true];
    }

    public function Get($idRange)
    {
        $range = RangePriceTeam::find($idRange);

        return ['range' => $range];
    }

    public function Update(Request $request, $idRange)
    {
        $validator = Validator::make($request->all(),

            [
                "idTeam" => ["required"],
                "idCompany" => ["required"],
                "route" => ["required"],
                "minWeight" => ["required", "min:1", "max:126", "numeric"],
                "maxWeight" => ["required", "min:1", "max:126", "numeric"],
                "price" => ["required", "max:999", "numeric"],
            ],
            [
                "idCompany.required" => "Select item",

                "route.required" => "Select item",

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

        $range = RangePriceTeam::find($idRange);

        $range->idTeam    = $request->get('idTeam');
        $range->idCompany = $request->get('idCompany');
        $range->route     = $request->get('route');
        $range->minWeight = $request->get('minWeight');
        $range->maxWeight = $request->get('maxWeight');
        $range->price     = $request->get('price');

        $range->save();

        return ['stateAction' => true];
    }

    public function Delete($idRange)
    {
        $range = RangePriceTeam::find($idRange);

        $range->delete();

        return ['stateAction' => true];
    }
}