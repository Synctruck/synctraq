<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Service\ServicePackageTerminal;

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

    public function MoveToWarehouse($Reference_Number_1)
    {
        try
        {
            DB::beginTransaction();

            $serviceTerminal = new ServicePackageTerminal();
            $serviceTerminal = $serviceTerminal->MoveToWarehouse($Reference_Number_1);    
        
            DB::commit();

            return $serviceTerminal;
        }
        catch(Exception $e)
        {
            DB::rollback();

            return "ROLLBACK";
        }
    }
}