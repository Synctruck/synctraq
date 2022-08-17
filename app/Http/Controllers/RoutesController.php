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

    public function List(Request $request)
    {
        $routeList = Routes::orderBy('name', 'asc')
                                ->where('name', 'like', '%'. $request->get('textSearch') .'%')
                                ->paginate($this->paginate);
        
        return ['routeList' => $routeList];
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

                    $route = Routes::where('zipCode', $row[0])->first();

                    if($route)
                    {
                        if($route->name != $row[5])
                        {
                            $route->name = $row[5];

                            $route->save();
                        }
                    }
                    else
                    {
                        $package = new Routes();

                        $package->zipCode = $row[0];
                        $package->name    = $row[5];

                        $package->save();
                    }
                }
                
                $lineNumber++;
            }

            fclose($handle);

            DB::commit();

            return ['stateAction' => true];    
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