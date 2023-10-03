<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Controllers\PackageController;

use App\Models\{ Cellar, InventoryTool, InventoryToolDetail, PackageBlocked, PackageTerminal, 
        PackageDelete, PackageDelivery, PackageDispatch, PackageLmCarrier, PackagePreDispatch, 
        PackageFailed, PackagePreFailed, PackageHistory, PackageHistoryNeeMoreInformation, 
        PackageHighPriority, PackageInbound, PackageManifest, PackageNeedMoreInformation, 
        PackageNotExists, PackageReturn, PackageReturnCompany, PackageLost,
        PackageWarehouse, TeamRoute, User, PackageDispatchToMiddleMile };

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

    public function Insert(Request $request)
    {
        $idInventory = uniqid();

        $inventoryTool = new InventoryTool();
        $inventoryTool->id = $idInventory;

        $cellar = Cellar::find(Auth::user()->idCellar);

        if($cellar)
        {    
            $inventoryTool->idCellar    = $cellar->id;
            $inventoryTool->nameCellar  = $cellar->name;
            $inventoryTool->stateCellar = $cellar->state;
            $inventoryTool->cityCellar  = $cellar->city;
        }

        $inventoryTool->idUser   = Auth::user()->id;
        $inventoryTool->userName = Auth::user()->name .' '. Auth::user()->nameOfOwner;
        $inventoryTool->status   = 'New';
        $inventoryTool->save();
        
        return ['idInventory' => $idInventory];
    }

    public function Finish($idInventoryTool)
    {
        $inventoryTool = InventoryTool::find($idInventoryTool);
        $inventoryTool->status = 'Finalized';
        $inventoryTool->save();

        return ['statusCode' => true];
    }

    public function ListInventoryDetail($idInventoryTool)
    {
        $listInventoryToolDetailPending = InventoryToolDetail::where('idInventoryTool', $idInventoryTool)
                                                    ->where('status', 'Pending')
                                                    ->get();

        $listInventoryToolDetailOverage = InventoryToolDetail::where('idInventoryTool', $idInventoryTool)
                                                    ->where('status', 'Overage')
                                                    ->get();

        return [
            'listInventoryToolDetailPending' => $listInventoryToolDetailPending,
            'listInventoryToolDetailOverage' => $listInventoryToolDetailOverage
        ];
    }

    public function InsertPackage(Request $request)
    {
        $inventoryToolDetail = InventoryToolDetail::where('idInventoryTool', $request->idInventoryTool)
                                                 ->where('Reference_Number_1', $request->Reference_Number_1)
                                                 ->first();

        if($inventoryToolDetail)
            return ['statusCode' => 'exists'];

        $packageController = new PackageController();
        $statusActual = $packageController->GetStatus($request->Reference_Number_1);

        if($statusActual['status'] == "")
            return ['statusCode' => 'notExists'];

        try
        {
            DB::beginTransaction();

            
            $inventoryTool = InventoryTool::find($request->idInventoryTool);

            $inventoryToolDetail = new InventoryToolDetail();
            $inventoryToolDetail->id = uniqid();
            $inventoryToolDetail->idInventoryTool = $inventoryTool->id;
            $inventoryToolDetail->Reference_Number_1 = $request->Reference_Number_1;

            if($statusActual['status'] == 'Inbound' || $statusActual['status'] == 'Warehouse')
            {
                $inventoryTool->nf           = $inventoryTool->nf + 1;
                $inventoryToolDetail->status = 'Pending';
            }
            else
            {
                $inventoryTool->ov           = $inventoryTool->ov + 1;
                $inventoryToolDetail->status = 'Overage';
            }

            $inventoryToolDetail->save();
            $inventoryTool->save();

            if($statusActual['status'] != 'Warehouse')
            {
                $this->MoveToWarhouse($request->Reference_Number_1);
            }
            
            DB::commit();

            return ['statusCode' => true];
        }
        catch(Exception $e)
        {
            DB::rollback();

            return ['statusCode' => false];
        }
    }

    public function MoveToWarhouse($Reference_Number_1)
    {
        $package = PackageManifest::find($Reference_Number_1);

        $package = $package != null ? $package : PackageInbound::find($Reference_Number_1);
        $package = $package != null ? $package : PackageNeedMoreInformation::find($Reference_Number_1);
        $package = $package != null ? $package : PackageDispatch::find($Reference_Number_1);
        $package = $package != null ? $package : PackagePreDispatch::find($Reference_Number_1);
        $package = $package != null ? $package : PackageFailed::find($Reference_Number_1);
        $package = $package != null ? $package : PackageReturnCompany::find($Reference_Number_1);
        $package = $package != null ? $package : PackageLost::find($Reference_Number_1);
        $package = $package != null ? $package : PackageLmCarrier::find($Reference_Number_1);
        $package = $package != null ? $package : PackageTerminal::find($Reference_Number_1);
        $package = $package != null ? $package : PackageDispatchToMiddleMile::find($Reference_Number_1);

        $packageWarehouse = new PackageWarehouse();

        $packageWarehouse->Reference_Number_1           = $package->Reference_Number_1;
        $packageWarehouse->idCompany                    = $package->idCompany;
        $packageWarehouse->company                      = $package->company;
        $packageWarehouse->idStore                      = $package->idStore;
        $packageWarehouse->store                        = $package->store;
        $packageWarehouse->Dropoff_Contact_Name         = $package->Dropoff_Contact_Name;
        $packageWarehouse->Dropoff_Company              = $package->Dropoff_Company;
        $packageWarehouse->Dropoff_Contact_Phone_Number = $package->Dropoff_Contact_Phone_Number;
        $packageWarehouse->Dropoff_Contact_Email        = $package->Dropoff_Contact_Email;
        $packageWarehouse->Dropoff_Address_Line_1       = $package->Dropoff_Address_Line_1;
        $packageWarehouse->Dropoff_Address_Line_2       = $package->Dropoff_Address_Line_2;
        $packageWarehouse->Dropoff_City                 = $package->Dropoff_City;
        $packageWarehouse->Dropoff_Province             = $package->Dropoff_Province;
        $packageWarehouse->Dropoff_Postal_Code          = $package->Dropoff_Postal_Code;
        $packageWarehouse->Notes                        = $package->Notes;
        $packageWarehouse->Weight                       = $package->Weight;
        $packageWarehouse->Route                        = $package->Route;
        $packageWarehouse->idUser                       = Auth::user()->id;
        $packageWarehouse->quantity                     = $package->quantity;
        $packageWarehouse->status                       = 'Warehouse';

        $cellar = Cellar::find(Auth::user()->idCellar);

        if($cellar)
        {
            $packageWarehouse->idCellar    = $cellar->id;
            $packageWarehouse->nameCellar  = $cellar->name;
            $packageWarehouse->stateCellar = $cellar->state;
            $packageWarehouse->cityCellar  = $cellar->city;
        }

        $packageWarehouse->save();

        $packageHistory = new PackageHistory();

        $packageHistory->id                           = uniqid();
        $packageHistory->Reference_Number_1           = $package->Reference_Number_1;
        $packageHistory->idCompany                    = $package->idCompany;
        $packageHistory->company                      = $package->company;
        $packageHistory->idStore                      = $package->idStore;
        $packageHistory->store                        = $package->store;
        $packageHistory->Dropoff_Contact_Name         = $package->Dropoff_Contact_Name;
        $packageHistory->Dropoff_Company              = $package->Dropoff_Company;
        $packageHistory->Dropoff_Contact_Phone_Number = $package->Dropoff_Contact_Phone_Number;
        $packageHistory->Dropoff_Contact_Email        = $package->Dropoff_Contact_Email;
        $packageHistory->Dropoff_Address_Line_1       = $package->Dropoff_Address_Line_1;
        $packageHistory->Dropoff_Address_Line_2       = $package->Dropoff_Address_Line_2;
        $packageHistory->Dropoff_City                 = $package->Dropoff_City;
        $packageHistory->Dropoff_Province             = $package->Dropoff_Province;
        $packageHistory->Dropoff_Postal_Code          = $package->Dropoff_Postal_Code;
        $packageHistory->Notes                        = $package->Notes;
        $packageHistory->Weight                       = $package->Weight;
        $packageHistory->Route                        = $package->Route;
        $packageHistory->idUser                       = Auth::user()->id;
        $packageHistory->Description                  = 'For: '. Auth::user()->name .' '. Auth::user()->nameOfOwner;
        $packageHistory->quantity                     = $package->quantity;
        $packageHistory->status                       = 'Warehouse';
        $packageHistory->actualDate                   = date('Y-m-d H:i:s');
        $packageHistory->created_at                   = date('Y-m-d H:i:s');
        $packageHistory->updated_at                   = date('Y-m-d H:i:s');

        if($cellar)
        {
            $packageHistory->idCellar    = $cellar->id;
            $packageHistory->nameCellar  = $cellar->name;
            $packageHistory->stateCellar = $cellar->state;
            $packageHistory->cityCellar  = $cellar->city;
        }

        $packageHistory->save();

        $package->delete();
    }
}