<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\{ Company, RangePriceTeam, RangePriceBaseTeam, User };

use Illuminate\Support\Facades\Validator;

use DB;

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

        $range->idTeam          = $request->get('idTeam');
        $range->idCompany       = $request->get('idCompany');
        $range->route           = $request->get('route');
        $range->minWeight       = $request->get('minWeight');
        $range->maxWeight       = $request->get('maxWeight');
        $range->price           = $request->get('price');

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

        $range->idTeam          = $request->get('idTeam');
        $range->idCompany       = $request->get('idCompany');
        $range->route           = $request->get('route');
        $range->minWeight       = $request->get('minWeight');
        $range->maxWeight       = $request->get('maxWeight');
        $range->price           = $request->get('price');

        $range->save();

        return ['stateAction' => true];
    }

    public function Delete($idRange)
    {
        $range = RangePriceTeam::find($idRange);

        $range->delete();

        return ['stateAction' => true];
    }

    public function ListConfigurationPrice($idTeam)
    {
        $listConfigurarionPrice = RangePriceTeam::where('idTeam', $idTeam)
                                                ->groupBy('route')
                                                ->get();

        return ['listConfigurarionPrice' => $listConfigurarionPrice];
    }

    public function GetPricesByIdTeam($idTeam, $routes)
    {
        $listPrices = RangePriceTeam::where('idTeam', $idTeam)
                                ->where('route', $routes)
                                ->orderBy('id', 'asc')
                                ->get();

        return ['listPrices' => $listPrices];
    }

    public function GetPriceTeam($idTeam, $idCompany, $weight, $route)
    {
        $range = RangePriceTeam::where('idTeam', $idTeam)
                                ->where('idCompany', $idCompany)
                                ->where('minWeight', '<=', $weight)
                                ->where('maxWeight', '>=', $weight)
                                ->where('route', $route)
                                ->first();

        /*if($range == null)
        {
            $range = RangePriceBaseTeam::where('idTeam', $idTeam)
                                        ->where('minWeight', '<=', $weight)
                                        ->where('maxWeight', '>=', $weight)
                                        ->first();
        }*/

        if($range)
        {
            return $range->price;
        }

        return 0;
    }

    public function CalculatePricePecercentaje($price, $fuelPercentage)
    {
        $pricePercentage = ($price * $fuelPercentage) / 100;
        $total           = $price + $pricePercentage;

        return ['pricePercentage' => $pricePercentage, 'total' => $total];
    }

    public function Import(Request $request)
    {
        try
        {
            DB::beginTransaction();

            $file = $request->file('file');

            $file->move(public_path() .'/file-import', 'rangepriceteamroutecompany.csv');

            $handle = fopen(public_path('file-import/rangepriceteamroutecompany.csv'), "r");

            $lineNumber = 1;

            $countSave = 0;

            while (($raw_string = fgets($handle)) !== false)
            {
                if($lineNumber > 1)
                {
                    $row = str_getcsv($raw_string);

                    $team    = User::where('name', $row[0])->where('idRole', 3)->first();
                    $company = Company::where('name', $row[5])->first();

                    if($team && $company)
                    {
                        $range = RangePriceTeam::where('idTeam', $team->id)
                                                ->where('route', $row[3])
                                                ->where('idCompany', $company->id)
                                                ->where('minWeight', $row[1])
                                                ->where('maxWeight', $row[2])
                                                ->first();

                        if($range)
                        {
                            if($range->price != $row[4])
                            {
                                $range->price = $row[4];
                            }
                        }
                        else
                        {
                            $range = new RangePriceTeam();

                            $range->idTeam          = $team->id;
                            $range->idCompany       = $company->id;
                            $range->route           = $row[3];
                            $range->minWeight       = $row[1];
                            $range->maxWeight       = $row[2];
                            $range->price           = $row[4];
                        }

                        $range->save();
                    }

                }

                $lineNumber++;
            }

            DB::commit();

            return ['stateAction' => true];  
        }
        catch(Exception $e)
        {
            DB::rollback();

            return ['stateAction' => false];
        }
    }
}