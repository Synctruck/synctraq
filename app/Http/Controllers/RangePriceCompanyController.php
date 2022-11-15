<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\RangePriceCompany;

use Illuminate\Support\Facades\Validator;

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

        $store = new RangePriceCompany();

        $store->idCompany = $request->get('idCompany');
        $store->minWeight = $request->get('minWeight');
        $store->maxWeight = $request->get('maxWeight');
        $store->price     = $request->get('price');

        $store->save();

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

        $range = RangePriceCompany::find($idRange);

        $range->idCompany = $request->get('idCompany');
        $range->minWeight = $request->get('minWeight');
        $range->maxWeight = $request->get('maxWeight');
        $range->price     = $request->get('price');

        $range->save();

        return ['stateAction' => true];
    }

    public function Delete($idRange)
    {
        $range = RangePriceCompany::find($idRange);

        $range->delete();

        return ['stateAction' => true];
    }
}