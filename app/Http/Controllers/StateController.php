<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{PackageHistory, States};

use Illuminate\Support\Facades\Validator;

use DB;
use Session;

class StateController extends Controller
{
    public $paginate = 20000;

    public function Index()
    {
        $listHistory = PackageHistory::groupBy('Dropoff_Province')->get('Dropoff_Province');

        foreach($listHistory as $history)
        {
            $state = States::where('name', $history->Dropoff_Province)->first();

            if(!$state)
            {
                if($history->Dropoff_Province)
                {
                    $state = new States();
                    $state->name = $history->Dropoff_Province;
                    $state->filter = 0;
                    $state->save();
                }
            }
        }

        return view('states.index');
    }

    public function List(Request $request)
    {
        $stateList = States::orderBy('name', 'asc')
                                ->where('name', 'like', '%'. $request->get('textSearch') .'%')
                                ->paginate($this->paginate);
        
        return ['stateList' => $stateList];
    }

    public function Insert(Request $request)
    {
        $validator = Validator::make($request->all(),

            [
                "name" => ["required", "unique:routes", "max:100"],
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

        $request["filter"] = 0;

        States::create($request->all());

        return ['stateAction' => true];
    }

    public function Get($id)
    {
        $state = States::find($id);
        
        return ['state' => $state];
    }

    public function Update(Request $request, $id)
    {
        try
        {
            DB::beginTransaction();

            $stateList = States::all();

            foreach($stateList as $state)
            {
                $state = States::find($state->id);

                $state->filter = 0;

                $state->save();
            }

            $valuesCheck = $request->get('valuesCheck') != '' ? explode(",", $request->get('valuesCheck')) : [];

            foreach($valuesCheck as $value)
            {
                $state = States::find($value);

                $state->filter = 1;

                $state->save();
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

    public function Delete($id)
    {
        $state = States::find($id);

        $state->delete();

        return ['stateAction' => true];
    }
}