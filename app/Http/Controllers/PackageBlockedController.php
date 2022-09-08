<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{ PackageBlocked };

use Illuminate\Support\Facades\Validator;

use DB;
use Session;

class PackageBlockedController extends Controller
{
    public function Index()
    {        
        return view('package.blocked');
    }

    public function List(Request $request)
    {
        $listPackageBlocked = PackageBlocked::orderBy('created_at', 'desc')->paginate(500);
            
        return ['listPackageBlocked' => $listPackageBlocked];
    }

    public function Insert(Request $request)
    {
        $validator = Validator::make($request->all(),

            [
                "Reference_Number_1" => ["required", "unique:packageblocked"],
                "comment" => ["required"],
            ],
            [
                "Reference_Number_1.required" => "The field is required",
                "Reference_Number_1.unique" => "The Package ID exists",

                "comment.required" => "The field is required",
            ]
        );

        if($validator->fails())
        {
            return response()->json(["status" => 422, "errors" => $validator->errors()], 422);
        }

        $package = new PackageBlocked();

        $package->id                 = uniqid();
        $package->Reference_Number_1 = $request->get('Reference_Number_1');
        $package->comment            = $request->get('comment');

        $package->save();

        return response()->json(["stateAction" => true], 200);
    }

    public function Get($Reference_Number_1)
    {
        $package = Package::find($Reference_Number_1);

        return ['package' => $package];
    }

    public function Delete($Reference_Number_1)
    {
        $package = PackageBlocked::find($Reference_Number_1);

        $package->delete();

        return response()->json(["stateAction" => true], 200);
    }
}