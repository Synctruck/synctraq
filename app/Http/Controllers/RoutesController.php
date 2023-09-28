<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

use App\Models\{ PackageDelivery, PackageDispatch, PackageHistory, PackageInbound, PackageManifest, PackageNotExists, PackageReturn, PackageWarehouse, Routes, TeamRoute, User, LiveRoute, RoutesAux, RoutesZipCode};

use Illuminate\Support\Facades\Validator;

use DB;
use Session;

class RoutesController extends Controller
{
    public $paginate = 50;

    public function Index()
    {        
        return view('routes.index');
    }

    public function List(Request $request, $CitySearchList, $CountySearchList, $TypeSearchList, $StateSearchList, $RouteSearchList, $LatitudeSearchList, $LongitudeSearchList)
    {
        $zipCode   = $request->get('zipCode');
        $routeList = RoutesAux::with('zip_codes')->orderBy('name', 'asc')->paginate($this->paginate);

        /*if($zipCode)
        {
            $routeList = $routeList->where('zipCode', 'like', '%'. $zipCode .'%');
        }
        else
        {
            $CitySearchList      = $CitySearchList == 'all' ? [] : explode(',', $CitySearchList);
            $CountySearchList    = $CountySearchList == 'all' ? [] : explode(',', $CountySearchList);
            $TypeSearchList      = $TypeSearchList == 'all' ? [] : explode(',', $TypeSearchList);
            $StateSearchList     = $StateSearchList == 'all' ? [] : explode(',', $StateSearchList);
            $RouteSearchList     = $RouteSearchList == 'all' ? [] : explode(',', $RouteSearchList);
            $LatitudeSearchList  = $LatitudeSearchList == 'all' ? [] : explode(',', $LatitudeSearchList);
            $LongitudeSearchList = $LongitudeSearchList == 'all' ? [] : explode(',', $LongitudeSearchList);

            if(count($CitySearchList) != 0)
            {
                $routeList = $routeList->whereIn('city', $CitySearchList);
            }

            if(count($CountySearchList) != 0)
            {
                $routeList = $routeList->whereIn('county', $CountySearchList);
            }

            if(count($TypeSearchList) != 0)
            {
                $routeList = $routeList->whereIn('type', $TypeSearchList);
            }

            if(count($StateSearchList) != 0)
            {
                $routeList = $routeList->whereIn('state', $StateSearchList);
            }

            if(count($RouteSearchList) != 0)
            {
                $routeList = $routeList->whereIn('name', $RouteSearchList);
            }

            if(count($LatitudeSearchList) != 0)
            {
                $routeList = $routeList->whereIn('latitude', $LatitudeSearchList);
            }

            if(count($LongitudeSearchList) != 0)
            {
                $routeList = $routeList->whereIn('longitude', $LongitudeSearchList);
            }
        }*/
            
        return [

            'routeList' => $routeList,
        ];
    }

    public function FilterList()
    {
        $listCity      = Routes::select('city')->groupBy('city')->get();
        $listCounty    = Routes::select('county')->groupBy('county')->get();
        $listType      = Routes::select('type')->groupBy('type')->get();
        $listState     = Routes::select('state')->groupBy('state')->get();
        $listRoute     = RoutesAux::orderBy('name', 'asc')->get();
        $listLatitude  = Routes::select('latitude')->groupBy('latitude')->get();
        $listLongitude = Routes::select('longitude')->groupBy('longitude')->get();

        return [

            'listCity' => $listCity,
            'listCounty' => $listCounty,
            'listType' => $listType,
            'listState' => $listState,
            'listRoute' => $listRoute,
            'listLatitude' => $listLatitude,
            'listLongitude' => $listLongitude,
        ];
    }

    public function AuxList()
    {
        return ['listRoute' => RoutesAux::orderBy('name', 'asc')->get()];
    }

    public function GetAll(Request $request)
    {
        $routeList = RoutesAux::orderBy('name', 'asc')->get();
        
        return ['routeList' => $routeList];
    }

    public function RoutesInbound(Request $request)
    {
        $routeList = PackageInbound::select('Route')->groupBy('Route')->get();

        return ['routeList' => $routeList];
    }

    public function RoutesReturns(Request $request)
    {
        $routeList = PackageReturn::select('Route')->groupBy('Route')->get();

        return ['routeList' => $routeList];
    }

    public function Insert(Request $request)
    {
        $validator = Validator::make($request->all(),

            [
                "zipCode" => ["required", "unique:routes", "max:100"],
                "name" => ["required", "max:100"], 
            ],
            [
                "zipCode.unique" => "Zip code already exists",
                "zipCode.required" => "El campo es requerido",
                "zipCode.max"  => "Debe ingresar máximo 50 dígitos",

                "name.required" => "El campo es requerido",
                "name.max"  => "Debe ingresar máximo 100 dígitos",
            ]
        );

        if($validator->fails())
        {
            return response()->json(["status" => 422, "errors" => $validator->errors()], 422);
        }

        Routes::create($request->all());

        return ['stateAction' => true];
    }

    public function Import(Request $request)
    {        
        $file = $request->file('file');
        $file->move(public_path() .'/file-import', 'routes.csv');

        $handle = fopen(public_path('file-import/routes.csv'), "r");

        $lineNumber = 1;

        $countSave = 0;

        try
        {
            DB::beginTransaction();

            while (($raw_string = fgets($handle)) !== false)
            {
                if($lineNumber > 1)
                {
                    $row = str_getcsv($raw_string);

                    if($row[0] != '')
                    {
                        $routesZipCode = RoutesZipCode::where('zipCode', $row[0])
                                                        ->where('routeName', $row[5])
                                                        ->first();

                        if($routesZipCode)
                        {
                            if($routesZipCode->city != $row[1] || $routesZipCode->county != $row[2] || $routesZipCode->type != $row[3] || $routesZipCode->state = $row[4] || $routesZipCode->name != $row[5] || $routesZipCode->latitude != $row[6] || $routesZipCode->longitude != $row[7])
                            {
                                $routesZipCode->city      = $row[1];
                                $routesZipCode->county    = $row[2];
                                $routesZipCode->type      = $row[3];
                                $routesZipCode->state     = $row[4];
                                $routesZipCode->routeName = $row[5];
                                $routesZipCode->latitude  = $row[6];
                                $routesZipCode->longitude = $row[7];
                                $routesZipCode->save();
                            }
                        }
                        else
                        {
                            $routesAux = RoutesAux::where('name', $row[5])->first();

                            if($routesAux == null)
                            {
                                $routesAux = new RoutesAux();
                                $routesAux->name = $row[5];
                                $routesAux->save();
                            }

                            $routesZipCode = RoutesZipCode::find($row[0]);

                            if($routesZipCode == null)
                            {
                                $routesZipCode = new RoutesZipCode();
                            }
                            
                            $routesZipCode->zipCode   = $row[0];
                            $routesZipCode->idRoute   = $routesAux->id;
                            $routesZipCode->city      = $row[1];
                            $routesZipCode->county    = $row[2];
                            $routesZipCode->type      = $row[3];
                            $routesZipCode->state     = $row[4];
                            $routesZipCode->routeName = $row[5];
                            $routesZipCode->latitude  = $row[6];
                            $routesZipCode->longitude = $row[7];
                            $routesZipCode->save();
                        }
                    }
                }
                
                $lineNumber++;
            }

            fclose($handle);

            DB::commit();

            return ['stateAction' => true, 'lineNumber' => $lineNumber];    
        }
        catch(Exception $e)
        {
            DB::rollback();

            return ['stateAction' => false];
        }
    }

    public function UploadLiveRoutes(Request $request){

        $file = $request->file('file');

        $file->move(public_path() .'/file-import', 'live_routes.csv');

        $handle = fopen(public_path('file-import/live_routes.csv'), "r");

        $lineNumber = 1;

        $countSave = 0;

        try
        {
            DB::beginTransaction();
            
            LiveRoute::whereNotNull('id')->delete();

            while (($raw_string = fgets($handle)) !== false)
            {
                if($lineNumber > 1)
                {
                    $route = new LiveRoute();
                   
                    $row = str_getcsv($raw_string);
                  
                    $route->zip_code    = $row[0];
                    $route->route_name  = $row[1];
                    $route->sequence_no = $row[2];
                    $route->save();
                   
                }
                
                $lineNumber++;
            }

            fclose($handle);

            DB::commit();

           return redirect('routes');
        }
        catch(Exception $e)
        {
            DB::rollback();

            return ['stateAction' => false];
        }
    }

    public function Get($id)
    {
        $route = RoutesAux::with('zip_codes')->find($id);
        
        return ['route' => $route];
    }

    public function Update(Request $request, $id)
    {
        $validator = Validator::make($request->all(),

            [
                "zipCode" => ["required", "unique:routes_zip_code", "min:5", "max:5"],
                "city" => ["required", "max:40"],
                "county" => ["required", "max:40"],
                "type" => ["required", "max:40"],
                "state" => ["required", "max:20"],
                "idRoute" => ["required"],
                "latitude" => ["required", "numeric"],
                "longitude" => ["required", "numeric"],
            ],
            [
                "zipCode.unique" => "The zip code exists",
                "zipCode.required" => "The field is required",
                "zipCode.max"  => "You must enter a minimum of 5 digits",
                "zipCode.max"  => "You must enter a maximum of 5 digits",

                "city.required" => "The field is required",
                "city.max"  => "You must enter a maximum of 40 digits",

                "county.required" => "The field is required",
                "county.max"  => "You must enter a maximum of 40 digits",

                "type.required" => "The field is required",
                "type.max"  => "You must enter a maximum of 40 digits",

                "state.required" => "The field is required",
                "state.max"  => "You must enter a maximum of 20 digits",

                "idRoute.required" => "The field is required",

                "latitude.required" => "The field is required",
                "latitude.numeric"  => "Enter a numeric value",

                "longitude.required" => "The field is required",
                "longitude.numeric"  => "Enter a numeric value",
            ]
        );

        if($validator->fails())
        {
            return response()->json(["status" => 422, "errors" => $validator->errors()], 422);
        }

        $route = RoutesAux::find($request->idRoute);

        $zipCode = new RoutesZipCode();
        $zipCode->zipCode   = $request->zipCode;
        $zipCode->idRoute   = $request->idRoute;
        $zipCode->routeName = $route->name;
        $zipCode->city      = $request->city;
        $zipCode->county    = $request->county;
        $zipCode->type      = $request->type;
        $zipCode->state     = $request->state;
        $zipCode->latitude  = $request->latitude;
        $zipCode->longitude = $request->longitude;
        $zipCode->save();

        return ['stateAction' => true];
    }

    public function Delete($id)
    {
        $route = Routes::find($id);

        $route->delete();

        return ['stateAction' => true];
    }

    public function DeleteZipCode($zipCode)
    {
        $zipCode = RoutesZipCode::find($zipCode);
        $zipCode->delete();

        return ['stateAction' => true];
    }

    public function UpdateRoutePackageManifestInboundWarehouse()
    {        
        $listPackageManifest = PackageManifest::all();

        foreach($listPackageManifest as $package)
        {
            $package = PackageManifest::find($package->Reference_Number_1);

            if($package)
            {
                $route = Routes::where('zipCode', $package->Dropoff_Postal_Code)->first();

                if($route)
                {
                    $package->Route = $route->name;

                    $package->save();
                }
            }
        }

        $listPackageInbound = PackageInbound::all();

        foreach($listPackageInbound as $package)
        {
            $package = PackageInbound::find($package->Reference_Number_1);
            
            if($package)
            {
                $route = Routes::where('zipCode', $package->Dropoff_Postal_Code)->first();

                if($route)
                {
                    $package->Route = $route->name;

                    $package->save();
                }
            }
        }

        $listPackageWarehouse = PackageWarehouse::all();

        foreach($listPackageWarehouse as $package)
        {
            $package = PackageWarehouse::find($package->Reference_Number_1);
            
            if($package)
            {
                $route = Routes::where('zipCode', $package->Dropoff_Postal_Code)->first();

                if($route)
                {
                    $package->Route = $route->name;

                    $package->save();
                }
            }
        }

        dd('updated');
    }

    public function UpdateRoutePackage()
    {        
        $listPackage = PackageHistory::where('updateRoute', 0)->get();

        foreach($listPackage as $package)
        {
            $package = PackageHistory::find($package->id);
            $route   = Routes::where('zipCode', $package->Dropoff_Postal_Code)->first();

            if($route)
            {
                $package->Route       = $route->name;
                $package->updateRoute = 1;
                    
                $package->save();
            }
        }

        dd('updated');
    }

    public function UpdatePassRouteAux()
    {
        try
        {
            DB::beginTransaction();

            $routesList = Routes::all();

            foreach($routesList as $route)
            {
                $routesAux = RoutesAux::where('name', $route->name)->first();

                if($routesAux == null)
                {
                    $routesAux = new RoutesAux();
                    $routesAux->name = $route->name;
                    $routesAux->save();
                }
            }

            DB::commit();

            echo 'update';  
        }
        catch(Exception $e)
        {
            DB::rollback();

            echo 'failed';
        }
    }

    public function UpdatePassRoutesZipCode()
    {
        try
        {
            DB::beginTransaction();

            $routesList = Routes::all();

            foreach($routesList as $route)
            {
                $routesZipCode = RoutesZipCode::find($route->zipCode);

                if($routesZipCode == null)
                {
                    $routes = Routes::where('name', $route->name)
                                    ->where('zipCode', $route->zipCode)
                                    ->first();

                    $routesAux = RoutesAux::where('name', $route->name)->first();

                    $routesZipCode = new RoutesZipCode();
                    $routesZipCode->zipCode   = $route->zipCode;
                    $routesZipCode->idRoute   = $routesAux->id;
                    $routesZipCode->routeName = $routes->name;
                    $routesZipCode->city      = $routes->city;
                    $routesZipCode->county    = $routes->county;
                    $routesZipCode->type      = $routes->type;
                    $routesZipCode->state     = $routes->state;
                    $routesZipCode->latitude  = $routes->latitude;
                    $routesZipCode->longitude = $routes->longitude;
                    $routesZipCode->save();
                }
            }

            DB::commit();

            echo 'update';  
        }
        catch(Exception $e)
        {
            DB::rollback();

            echo 'failed';
        }
    }
}