<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\{ Company, RangePriceTeamByCompany  };

use Illuminate\Support\Facades\Validator;

use Log;

class RangePaymentTeamByCompanyController extends Controller
{
    public function List($idTeam)
    {
        $rangeList = RangePriceTeamByCompany::where('idTeam', $idTeam)
                                        ->orderBy('price', 'asc')
                                        ->get();

        return ['rangeList' => $rangeList];
    }

    public function Insert(Request $request)
    {
        $validator = Validator::make($request->all(),

            [
                "idTeam" => ["required"],
                "idCompany" => ["required"],
                "price" => ["required", "max:999", "numeric"],
            ],
            [
                "idTeam.required" => "Seleccione un team",

                "idCompany.required" => "Select an item",

                "price.required" => "The field is required",
                "price.numeric"  => "Enter only numbers",
            ]
        );

        if($validator->fails())
        {
            return response()->json(["status" => 422, "errors" => $validator->errors()], 422);
        }

        $company = Company::find($request->get('idCompany'));

        $range = new RangePriceTeamByCompany();
        $range->idTeam    = $request->get('idTeam');
        $range->idCompany = $request->get('idCompany');
        $range->company   = $company->name;
        $range->price     = $request->get('price');
        $range->save();

        return ['stateAction' => true];
    }

    public function Get($idRange)
    {
        $range = RangePriceTeamByCompany::find($idRange);

        return ['range' => $range];
    }

    public function Update(Request $request, $idRange)
    {
        $validator = Validator::make($request->all(),

            [
                "idTeam" => ["required"],
                "idCompany" => ["required"],
                "price" => ["required", "max:999", "numeric"],
            ],
            [
                "idTeam.required" => "Seleccione un team",

                "idCompany.required" => "Select an item",

                "price.required" => "The field is required",
                "price.numeric"  => "Enter only numbers",
            ]
        );

        if($validator->fails())
        {
            return response()->json(["status" => 422, "errors" => $validator->errors()], 422);
        }

        $range = RangePriceTeamByCompany::find($idRange);
        $range->idCompany = $request->get('idCompany');
        $range->price     = $request->get('price');
        $range->save();

        return ['stateAction' => true];
    }

    public function Delete($idRange)
    {
        $range = RangePriceTeamByCompany::find($idRange);
        $range->delete();

        return ['stateAction' => true];
    }
}