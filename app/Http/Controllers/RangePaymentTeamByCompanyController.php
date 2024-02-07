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
        $rangeList = RangePriceTeamByCompany::with('range_rate_team')
                                        ->where('idTeam', $idTeam)
                                        ->orderBy('price', 'asc')
                                        ->get();

        return ['rangeList' => $rangeList];
    }

    public function Insert(Request $request)
    {
        $request['idCompany']      = $request->get('idCompany') ? $request->get('idCompany') : 0;
        $request['routeByCompany'] = $request->get('routeByCompany') ? $request->get('routeByCompany') : '';

        $range = RangePriceTeamByCompany::where('idTeam', $request->get('idTeam'))
                                        ->where('idCompany', $request['idCompany'])
                                        ->where('route', $request['routeByCompany'])
                                        ->first();

        if($range)
        {
            $request['idCompany']      = '';
            $request['routeByCompany'] = '';

            $validator = Validator::make($request->all(),

                [
                    "idCompany" => ["required"],
                    "routeByCompany" => ["required"],
                ],
                [
                    "idCompany.required" => "The configuration already exists",
                    "routeByCompany.required" => "The configuration already exists",
                ]
            );

            if($validator->fails())
            {
                return response()->json(["status" => 422, "errors" => $validator->errors()], 422);
            }
        }

        if($request->get('idCompany') || $request->get('routeByCompany'))
        {
            $validator = Validator::make($request->all(),

                [
                    "idTeam" => ["required"],
                ],
                [

                    "price.required" => "The field is required",
                    "price.numeric"  => "Enter only numbers",
                ]
            );
        }
        else
        {
            $validator = Validator::make($request->all(),

                [
                    "idTeam" => ["required"],
                    "idRangeRate" => ["idRangeRate"],
                    "idCompany" => ["required"],
                    "routeByCompany" => ["required"],
                    "price" => ["required", "max:999", "numeric"],
                ],
                [
                    "idTeam.required" => "Seleccione un team",

                    "idCompany.required" => "Select an item",

                    "idRangeRate.required" => "Select a BASE RATE RANGE",

                    "routeByCompany.required" => "The field is required",

                    "price.required" => "The field is required",
                    "price.numeric"  => "Enter only numbers",
                ]
            );
        }

        if($validator->fails())
        {
            return response()->json(["status" => 422, "errors" => $validator->errors()], 422);
        }

        $company = Company::find($request->get('idCompany'));

        $range = new RangePriceTeamByCompany();
        $range->idTeam      = $request->get('idTeam');
        $range->idCompany   = $request['idCompany'];
        $range->idRangeRate = $request['idRangeRate'] ? $request['idRangeRate'] : 0;
        $range->company     = $company ? $company->name : '';
        $range->route       = $request['routeByCompany'];
        $range->price       = $request->get('price');
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
        $request['routeByCompany'] = $request->get('routeByCompany') ? $request->get('routeByCompany') : '';

        $range = RangePriceTeamByCompany::where('idTeam', $request->get('idTeam'))
                                        ->where('idCompany', $request->get('idCompany'))
                                        ->where('route', $request['routeByCompany'])
                                        ->first();

        if($range && $range->id != $idRange)
        {
            $request['idCompany']      = '';
            $request['routeByCompany'] = '';

            $validator = Validator::make($request->all(),

                [
                    "idCompany" => ["required"],
                    "routeByCompany" => ["required"],
                ],
                [
                    "idCompany.required" => "The configuration already exists",
                    "routeByCompany.required" => "The configuration already exists",
                ]
            );

            if($validator->fails())
            {
                return response()->json(["status" => 422, "errors" => $validator->errors()], 422);
            }
        }

        if($request->get('idCompany') || $request->get('routeByCompany'))
        {
            $validator = Validator::make($request->all(),

                [
                    "idTeam" => ["required"],
                ],
                [

                    "price.required" => "The field is required",
                    "price.numeric"  => "Enter only numbers",
                ]
            );
        }
        else
        {
            $validator = Validator::make($request->all(),

                [
                    "idTeam" => ["required"],
                    "idCompany" => ["required"],
                    "idRangeRate" => ["idRangeRate"],
                    "routeByCompany" => ["required"],
                    "price" => ["required", "max:999", "numeric"],
                ],
                [
                    "idTeam.required" => "Seleccione un team",

                    "idCompany.required" => "Select an item",

                    "idRangeRate.required" => "Select a BASE RATE RANGE",
                    
                    "routeByCompany.required" => "The field is required",

                    "price.required" => "The field is required",
                    "price.numeric"  => "Enter only numbers",
                ]
            );
        }

        if($validator->fails())
        {
            return response()->json(["status" => 422, "errors" => $validator->errors()], 422);
        }

        $company = Company::find($request->get('idCompany'));

        $range = RangePriceTeamByCompany::find($idRange);
        $range->idCompany = $request->get('idCompany') ? $request->get('idCompany') : 0;
        $range->idRangeRate = $request['idRangeRate'];
        $range->company   = $company ? $company->name : '';
        $range->route     = $request['routeByCompany'];
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