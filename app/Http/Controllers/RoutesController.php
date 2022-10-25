<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

use App\Models\{Assigned, PackageDelivery, PackageDispatch, PackageHistory, PackageInbound, PackageManifest, PackageNotExists, PackageReturn, Routes, TeamRoute, Unassigned, User};

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

    public function List(Request $request, $CitySearchList, $CountySearchList, $TypeSearchList, $StateSearchList, $RouteSearchList)
    {
        $zipCode   = $request->get('zipCode');
        $routeList = Routes::orderBy('zipCode', 'asc');

        if($zipCode)
        {
            $routeList = $routeList->where('zipCode', 'like', '%'. $zipCode .'%');
        }
        else
        {
            $CitySearchList   = $CitySearchList == 'all' ? [] : explode(',', $CitySearchList);
            $CountySearchList = $CountySearchList == 'all' ? [] : explode(',', $CountySearchList);
            $TypeSearchList   = $TypeSearchList == 'all' ? [] : explode(',', $TypeSearchList);
            $StateSearchList  = $StateSearchList == 'all' ? [] : explode(',', $StateSearchList);
            $RouteSearchList  = $RouteSearchList == 'all' ? [] : explode(',', $RouteSearchList);

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
        }

        $routeList = $routeList->with('teams')->paginate($this->paginate);
            
        return [

            'routeList' => $routeList,
        ];
    }

    public function FilterList()
    {
        $listCity   = Routes::select('city')->groupBy('city')->get();
        $listCounty = Routes::select('county')->groupBy('county')->get();
        $listType   = Routes::select('type')->groupBy('type')->get();
        $listState  = Routes::select('state')->groupBy('state')->get();
        $listRoute  = Routes::select('name')->groupBy('name')->get();

        return [

            'listCity' => $listCity,
            'listCounty' => $listCounty,
            'listType' => $listType,
            'listState' => $listState,
            'listRoute' => $listRoute,
        ];
    }

    public function GetAll(Request $request)
    {
        $routeList = Routes::select('name')->groupBy('name')->get();
        
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
                        $route = Routes::where('zipCode', $row[0])->first();

                        if($route)
                        {
                            $route->city   = $row[1];
                            $route->county = $row[2];
                            $route->type   = $row[3];
                            $route->state  = $row[4];
                            $route->name   = $row[5];

                            $route->save();
                        }
                        else
                        {
                            $route = new Routes();

                            $route->zipCode = $row[0];
                            $route->city    = $row[1];
                            $route->county  = $row[2];
                            $route->type    = $row[3];
                            $route->state   = $row[4];
                            $route->name    = $row[5];

                            $route->save();
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

    public function Get($id)
    {
        $route = Routes::find($id);
        
        return ['route' => $route];
    }

    public function Update(Request $request, $id)
    {
        $validator = Validator::make($request->all(),

            [
                "name" => ["required", "unique:routes,name,$id", "max:100"],
            ],
            [
                "name.unique" => "La ruta existe",
                "name.required" => "El campo es requerido",
                "name.max"  => "Debe ingresar máximo 100 dígitos",
            ]
        );

        if($validator->fails())
        {
            return response()->json(["status" => 422, "errors" => $validator->errors()], 422);
        }

        $route = Routes::find($id);
        
        $route->update($request->all()); 

        return ['stateAction' => true];
    }

    public function Delete($id)
    {
        $route = Routes::find($id);

        $route->delete();

        return ['stateAction' => true];
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
}