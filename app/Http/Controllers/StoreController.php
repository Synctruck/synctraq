<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Stores;

use Illuminate\Support\Facades\Validator;

class StoreController extends Controller
{
    public function List($idCompany)
    {
        $storeList = Stores::where('idCompany', $idCompany)
                            ->orderBy('name', 'asc')
                            ->get();

        return ['storeList' => $storeList];
    }

    public function Insert(Request $request)
    {
        $store = Stores::where('name', $request->get('name'))
                        ->where('idCompany', $request->get('idCompany'))
                        ->first();

        if($store)
        {
            $validator = Validator::make($request->all(),

                [
                    "name" => ["required", "unique:stores", "max:50"],
                ],
                [
                    "name.unique" => "The store exists",
                    "name.required" => "The field is required",
                    "name.max"  => "You must enter a maximum of 50 digits",
                ]
            );

            if($validator->fails())
            {
                return response()->json(["status" => 422, "errors" => $validator->errors()], 422);
            }
        }

        $validator = Validator::make($request->all(),

            [
                "idCompany" => ["required"],
                "name" => ["required", "max:50"],
                "address" => ["required", "max:100"],
            ],
            [
                "name.required" => "The field is required",
                "name.max"  => "You must enter a maximum of 50 digits",

                "address.required" => "The field is required",
                "address.max"  => "You must enter a maximum of 100 digits",
            ]
        );

        if($validator->fails())
        {
            return response()->json(["status" => 422, "errors" => $validator->errors()], 422);
        }

        $store = new Stores();

        $store->idCompany = $request->get('idCompany');
        $store->name      = $request->get('name');
        $store->address   = $request->get('address');

        $store->save();

        return ['stateAction' => true];
    }

    public function Update(Request $request, $idStore)
    {
        $store = Stores::where('name', $request->get('name'))
                        ->where('idCompany', $request->get('idCompany'))
                        ->first();

        if($store && $store->id != $idStore)
        {
            $validator = Validator::make($request->all(),

                [
                    "name" => ["required", "unique:stores", "max:50"],
                ],
                [
                    "name.unique" => "The store exists",
                    "name.required" => "The field is required",
                    "name.max"  => "You must enter a maximum of 50 digits",
                ]
            );

            if($validator->fails())
            {
                return response()->json(["status" => 422, "errors" => $validator->errors()], 422);
            }
        }

        $validator = Validator::make($request->all(),

            [
                "idCompany" => ["required"],
                "name" => ["required", "max:50"],
                "address" => ["required", "max:100"],
            ],
            [
                "name.required" => "The field is required",
                "name.max"  => "You must enter a maximum of 50 digits",

                "address.required" => "The field is required",
                "address.max"  => "You must enter a maximum of 100 digits",
            ]
        );

        if($validator->fails())
        {
            return response()->json(["status" => 422, "errors" => $validator->errors()], 422);
        }

        $store = Stores::find($idStore);

        $store->name      = $request->get('name');
        $store->address   = $request->get('address');

        $store->save();

        return ['stateAction' => true];
    }

    public function Get($idStore)
    {
        $store = Stores::find($idStore);

        return ['store' => $store];
    }

    public function Delete($idStore)
    {
        $store = Stores::find($idStore);

        $store->delete();

        return ['stateAction' => true];
    }
}