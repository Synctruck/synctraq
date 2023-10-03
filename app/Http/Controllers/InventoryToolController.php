<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\InventoryTool;

use Auth;
use DB;
use Log;
use Session;
use DateTime; 
 
class InventoryToolController extends Controller
{
    public function Index()
    {
        return view('package.inventory-tool');
    }

    public function List()
    {
        return ['inventoryToolList' => InventoryTool::orderBy('created_at', 'desc')->paginate(50)];
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