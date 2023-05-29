<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Service\ServicePackageNeedMoreInformation;
use App\External\ExternalServiceInland;

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

    public function List(Request $request, $idCompany, $dateStart,$dateEnd, $route, $state)
    {
        $servicePackageNeedMoreInformation = new ServicePackageNeedMoreInformation();
    
        return ['packageList' => $servicePackageNeedMoreInformation->List($request, $idCompany, $dateStart,$dateEnd, $route, $state)];
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

    public function Get($Reference_Number_1)
    {
        $servicePackageNeedMoreInformation = new ServicePackageNeedMoreInformation();
        
        return ['package' => $servicePackageNeedMoreInformation->Get($Reference_Number_1)];
    }

    public function Update(Request $request)
    {        
        if($request->get('passwordUpdate') != ENV('PASSWORD_UPDATE_PACKAGE'))
        {
            return ['statusAction' => 'passwordIncorrect'];
        }

        try
        {
            DB::beginTransaction();

            $servicePackageNeedMoreInformation = new ServicePackageNeedMoreInformation();
            $servicePackageNeedMoreInformation = $servicePackageNeedMoreInformation->Update($request);    
            
            if($package->company == 'INLAND LOGISTICS')
            {
                $externalServiceInland = new ExternalServiceInland();
                $externalServiceInland = $externalServiceInland->PackageUpdate($request);

                if($externalServiceInland['status'] != 200)
                {
                    return response()->json(["stateAction" => 'notUpdated', 'response' => $externalServiceInland['response']]);
                }
            }
            
            DB::commit();

            return ['statusAction' => true];
        }
        catch(Exception $e)
        {
            DB::rollback();

            return ['statusAction' => false];
        }
    }

    public function Export(Request $request, $idCompany, $dateStart,$dateEnd, $route, $state, $type)
    {
        $servicePackageNeedMoreInformation = new ServicePackageNeedMoreInformation();

        return $servicePackageNeedMoreInformation->Export($request, $idCompany, $dateStart,$dateEnd, $route, $state, $type);
    }

    public function MoveToWarehouse($Reference_Number_1)
    {
        $servicePackageNeedMoreInformation = new ServicePackageNeedMoreInformation();

        return $servicePackageNeedMoreInformation->MoveToWarehouse($Reference_Number_1);
    }
}