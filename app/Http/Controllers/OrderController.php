<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\{ Company, PackageHistory, PackageManifest, PackageNotExists, Routes, Stores };

use Auth;
use DB;
use PDF;

class OrderController extends Controller
{
    public function Index()
    {
        return view('order.index');
    }

    public function List(Request $request, $route, $state)
    {
        $routes = explode(',', $route);
        $states = explode(',', $state);

        $packageList = PackageManifest::where('idStore', '!=', null);

        if($route != 'all')
        {
            $packageList = $packageList->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $packageList = $packageList->whereIn('Dropoff_Province', $states);
        }

        if($request->get('textSearch'))
        {
            $packageList = $packageList->where('Reference_Number_1', 'like', '%'. $request->get('textSearch') .'%')
                                        ->orderBy('created_at', 'desc');
        }
        else
        {
            $packageList = $packageList->orderBy('created_at', 'desc');
        }

        $packageList = $packageList->paginate(50);

        $quantityPackage = $packageList->total();

        $listState  = PackageManifest::select('Dropoff_Province')
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        return ['packageList' => $packageList, 'listState' => $listState, 'quantityPackage' => $quantityPackage];
    }

    public function SearchOrderNumber(Request $request)
    {
        $packageList = PackageManifest::where('idStore', '!=', 'NULL')
                                    ->where('Reference_Number_1', $request->get('Reference_Number_1'))
                                    ->paginate(50); 
        
        return ['packageList' => $packageList];
    }

    public function Insert(Request $request)
    {        
        $validator = Validator::make($request->all(),

            [ 
                "idCompany" => ["required"],
                "idStore" => ["required"],
                "Dropoff_Contact_Name" => ["required"],
                "Dropoff_Contact_Phone_Number" => ["required"],
                "Dropoff_Address_Line_1" => ["required"],
                "Dropoff_City" => ["required"],
                "Dropoff_Province" => ["required"],
                "Dropoff_Postal_Code" => ["required"],
                "quantity" => ["required", "numeric", "min:1", "max:127"],
            ],
            [
                "idCompany.required" => "Select an item",

                "idStore.required" => "Select an item",

                "Dropoff_Contact_Name.required" => "The field is required.",
                "Dropoff_Contact_Phone_Number.required" => "The field is required.",

                "Dropoff_Address_Line_1.required" => "The field is required.",

                "Dropoff_City.required" => "The field is required.",

                "Dropoff_Province.required" => "The field is required.",

                "Dropoff_Postal_Code.required" => "The field is required.",

                "quantity.required" => "The field is required.",
                "quantity.numeric" => "Enter a numeric value",
            ]
        );

        if($validator->fails())
        {
            return response()->json(["status" => 422, "errors" => $validator->errors()], 422);
        }

        try
        {

            $route    = Routes::where('zipCode', $request->get('Dropoff_Postal_Code'))->first();
            $company  = Company::find($request->get('idCompany'));
            $store    = Stores::find($request->get('idStore'));
            $quantity = $request->get('quantity');
            $Weight   = 10;
            $time     = strtotime(date('Y-m-d H:i:s'));

            $Reference_Number_1_PCC = 'PCC'. $time;
            $Reference_Number_1DCC  = 'DCC'. $time;

            //*******************************************
            //REGISTER STORE
            $package = new PackageManifest();

            $package->idCompany                    = $request->get('idCompany');
            $package->company                      = $company->name;
            $package->idStore                      = $request->get('idStore');
            $package->idUser                       = Auth::user()->id;
            $package->store                        = $store->name;
            $package->Reference_Number_1           = $Reference_Number_1_PCC;
            $package->Dropoff_Contact_Name         = $store->name;
            $package->Dropoff_Contact_Phone_Number = $store->phoneNumber;
            $package->Dropoff_Address_Line_1       = $store->address;
            $package->Dropoff_City                 = $store->city;
            $package->Dropoff_Province             = $store->state;
            $package->Dropoff_Postal_Code          = $store->zipCode;
            $package->Weight                       = $Weight;
            $package->quantity                     = $quantity;
            $package->Route                        = $store->route;
            $package->status                       = 'On hold';

            $package->save();

            $created_at = date('Y-m-d H:i:s');

            $packageHistory = new PackageHistory();

            $packageHistory->id                           = uniqid();
            $packageHistory->idCompany                    = $request->get('idCompany');
            $packageHistory->company                      = $company->name;
            $packageHistory->idStore                      = $request->get('idStore');
            $packageHistory->store                        = $store->name;
            $packageHistory->Reference_Number_1           = $Reference_Number_1_PCC;
            $packageHistory->Dropoff_Contact_Name         = $store->name;
            $packageHistory->Dropoff_Contact_Phone_Number = $store->phoneNumber;
            $packageHistory->Dropoff_Address_Line_1       = $store->address;
            $packageHistory->Dropoff_City                 = $store->city;
            $packageHistory->Dropoff_Province             = $store->state;
            $packageHistory->Dropoff_Postal_Code          = $store->zipCode;
            $packageHistory->Weight                       = $Weight;
            $packageHistory->Route                        = $store->Route;
            $packageHistory->idUser                       = Auth::user()->id;
            $packageHistory->idUserManifest               = Auth::user()->id;
            $packageHistory->Date_manifest                = date('Y-m-d H:s:i');
            $packageHistory->Description                  = 'On hold - for: '. Auth::user()->name .' '. Auth::user()->nameOfOwner;
            $packageHistory->Route                        = $route ? $route->name : '';
            $packageHistory->status                       = 'On hold';
            $packageHistory->quantity                     = $quantity;
            $packageHistory->created_at                   = $created_at;
            $packageHistory->updated_at                   = $created_at;

            $packageHistory->save();

            //*******************************************
            //REGISTER CLIENT
            $package = new PackageManifest();

            $package->idCompany                    = $request->get('idCompany');
            $package->company                      = $company->name;
            $package->idStore                      = $request->get('idStore');
            $package->idUser                       = Auth::user()->id;
            $package->store                        = $store->name;
            $package->Reference_Number_1           = $Reference_Number_1DCC;
            $package->Dropoff_Contact_Name         = $request->get('Dropoff_Contact_Name');
            $package->Dropoff_Contact_Phone_Number = $request->get('Dropoff_Contact_Phone_Number');
            $package->Dropoff_Address_Line_1       = $request->get('Dropoff_Address_Line_1');
            $package->Dropoff_City                 = $request->get('Dropoff_City');
            $package->Dropoff_Province             = $request->get('Dropoff_Province');
            $package->Dropoff_Postal_Code          = $request->get('Dropoff_Postal_Code');
            $package->Weight                       = $Weight;
            $package->quantity                     = $quantity;
            $package->Route                        = $route ? $route->name : '';
            $package->status                       = 'On hold';

            $package->save();

            $created_at = date('Y-m-d H:i:s');

            $packageHistory = new PackageHistory();

            $packageHistory->id                           = uniqid();
            $packageHistory->idCompany                    = $request->get('idCompany');
            $packageHistory->company                      = $company->name;
            $packageHistory->idStore                      = $request->get('idStore');
            $packageHistory->store                        = $store->name;
            $packageHistory->Reference_Number_1           = $Reference_Number_1DCC;
            $packageHistory->Dropoff_Contact_Name         = $request->get('Dropoff_Contact_Name');
            $packageHistory->Dropoff_Contact_Phone_Number = $request->get('Dropoff_Contact_Phone_Number');
            $packageHistory->Dropoff_Address_Line_1       = $request->get('Dropoff_Address_Line_1');
            $packageHistory->Dropoff_City                 = $request->get('Dropoff_City');
            $packageHistory->Dropoff_Province             = $request->get('Dropoff_Province');
            $packageHistory->Dropoff_Postal_Code          = $request->get('Dropoff_Postal_Code');
            $packageHistory->Weight                       = $Weight;
            $packageHistory->Route                        = $request->get('Route');
            $packageHistory->idUser                       = Auth::user()->id;
            $packageHistory->idUserManifest               = Auth::user()->id;
            $packageHistory->Date_manifest                = date('Y-m-d H:s:i');
            $packageHistory->Description                  = 'On hold - for: '.Auth::user()->name .' '. Auth::user()->nameOfOwner;
            $packageHistory->Route                        = $route ? $route->name : '';
            $packageHistory->status                       = 'On hold';
            $packageHistory->quantity                     = $quantity;
            $packageHistory->created_at                   = $created_at;
            $packageHistory->updated_at                   = $created_at;

            $packageHistory->save();


            $store->delete = 1;

            $store->save();

            DB::commit();

            return response()->json(["stateAction" => true], 200);

        }
        catch(\Exception $e)
        {
            DB::rollback();

            return response()->json(["stateAction" => false], 200);
        }
    }

    public function Print($Reference_Number_1)
    {
        $Reference_Number_1 = substr($Reference_Number_1, 3, 13);

        $orderList = PackageManifest::where('Reference_Number_1', 'like', '%'. $Reference_Number_1 .'%')
                                    ->orderBy('Reference_Number_1', 'desc')
                                    ->get();

        $pdf = PDF::loadView('pdf.order', ['orderList' => $orderList]);
                    
        $pdf->setPaper('A4');

        return $pdf->stream('Reporte Mis Notas.pdf');
    }

    public function Delete($Reference_Number_1)
    {
        $packageManifest = PackageManifest::find($Reference_Number_1);
        $packageHistory  = PackageHistory::where('Reference_Number_1', $Reference_Number_1);

        try
        {
            DB::beginTransaction();

            $packageManifest->delete();
            $packageHistory->delete();

            DB::commit();

            return response()->json(["stateAction" => true], 200);            
        } 
        catch(Exception $e)
        {
            DB::rollback();

            return response()->json(["stateAction" => false], 200);
        }
    }
}