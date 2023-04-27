<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Service\ServicePackageNeedMoreInformation;

use Auth;
use DB;
use Log; 
use Session;
use DateTime;
 
class PackageNeedMoreInformationController extends Controller
{
    public function Index()
    {
        return view('package.needmoreinformation');
    }

    public function List(Request $request)
    {
        $servicePackageNeedMoreInformation = new ServicePackageNeedMoreInformation();
    
        return ['packageList' => $servicePackageNeedMoreInformation->List($request)];
    }

    public function Insert(Request $request)
    {
        try
        {
            DB::beginTransaction();

            $servicePackageNeedMoreInformation = new ServicePackageNeedMoreInformation();
            $servicePackageNeedMoreInformation = $servicePackageNeedMoreInformation->Insert($request);    
        
            DB::commit();

            return $servicePackageNeedMoreInformation;
        }
        catch(Exception $e)
        {
            DB::rollback();

            return false;
        }
    }

    public function Export($type)
    {
        $servicePackageNeedMoreInformation = new ServicePackageNeedMoreInformation();

        return $servicePackageNeedMoreInformation->Export($type);
    }

    public function MoveToWarehouse($Reference_Number_1)
    {
        $servicePackageNeedMoreInformation = new ServicePackageNeedMoreInformation();

        return $servicePackageNeedMoreInformation->MoveToWarehouse($Reference_Number_1);
    }
}