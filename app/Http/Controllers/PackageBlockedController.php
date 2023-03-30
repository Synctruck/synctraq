<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Service\ServicePackageBlocked;
use App\Validation\ValidationPackageBlocked;

use DB;
use Session; 

class PackageBlockedController extends Controller
{
    private $servicePackageBlocked;

    public function __construct(ServicePackageBlocked $servicePackageBlocked)
    {
        $this->servicePackageBlocked = $servicePackageBlocked;
    }

    public function Index()
    {        
        return view('package.blocked');
    }

    public function List(Request $request)
    {            
        return ['listPackageBlocked' => $this->servicePackageBlocked->List()];
    }

    public function Insert(Request $request)
    {
        $validationPackageBlocked = new ValidationPackageBlocked();
        $validationPackageBlocked = $validationPackageBlocked->Insert($request);

        if($validationPackageBlocked)
        { 
            return response()->json(["status" => 422, "errors" => $validationPackageBlocked], 422);
        }
        
        $this->servicePackageBlocked->Insert($request);

        return response()->json(["stateAction" => true], 200);
    }

    public function Delete($Reference_Number_1)
    {
        return response()->json(["stateAction" => $this->servicePackageBlocked->Delete($Reference_Number_1)], 200);
    }
}