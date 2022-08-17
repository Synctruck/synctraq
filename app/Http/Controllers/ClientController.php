<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{Client};

use Illuminate\Support\Facades\Validator;

use DB;
use Session;

class CompanyController extends Controller
{
    public function GetAll(Request $request)
    {
        $clientList = Client::orderBy('name', 'asc')->get();
        
        return ['clientList' => $clientList];
    }

    public function List(Request $request)
    {
        if($request->get('textSearch'))
        {
            $clientList = Client::where('name', 'like', '%'. $request->get('textSearch') .'%')->get('id');

            $clientList = Client::orderBy('name', 'asc')
                                ->whereIn('id', $clientList)
                                ->paginate(20);
        }
        else
        {
            $clientList = Client::orderBy('name', 'asc')->paginate(20);
        }
        
        return ['clientList' => $clientList];
    }

    public function Insert(Request $request)
    {
        $validator = Validator::make($request->all(),

            [
                "name" => ["required", "unique:client", "max:100"],
            ],
            [
                "name.unique" => "The client exists",
                "name.required" => "The field is required",
                "name.max"  => "You must enter a maximum of 200 digits",
            ]
        );

        if($validator->fails())
        {
            return response()->json(["status" => 422, "errors" => $validator->errors()], 422);
        }

        Client::create($request->all());

        return ['stateAction' => true];
    }

    public function Get($id)
    {
        $client = Client::find($id);
        
        return ['client' => $client];
    }

    public function Update(Request $request, $id)
    {
        $validator = Validator::make($request->all(),

            [
                "name" => ["required", "unique:client,name,". $id, "max:100"],
            ],
            [
                "name.unique" => "The client exists",
                "name.required" => "The field is required",
                "name.max"  => "You must enter a maximum of 200 digits",
            ]
        );

        if($validator->fails())
        {
            return response()->json(["status" => 422, "errors" => $validator->errors()], 422);
        }

        $client = Client::find($id);
        $client->update($request->all());

        return ['stateAction' => true];
    }

    public function Delete($id)
    {
        $client = Client::find($id);

        $client->delete();

        return ['stateAction' => true];
    }
}