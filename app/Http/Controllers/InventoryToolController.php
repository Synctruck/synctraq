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

    public function List(Request $request, $dateStart, $dateEnd)
    {
        $dateStart = $dateStart .' 00:00:00';
        $dateEnd   = $dateEnd .' 23:59:59';

        $listInventoryTool = InventoryTool::orderBy('created_at', 'desc')
                                        ->whereBetween('created_at', [$dateStart, $dateEnd])
                                        ->paginate(50);

        $newInventory = InventoryTool::where('status', 'New')->first() ? 'none' : 'block';

        return ['inventoryToolList' => $listInventoryTool, 'newInventory' => $newInventory];
    }

    public function Insert(Request $request)
    {
        try
        {
            DB::beginTransaction();

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
            
            $packageInboundList = PackageInbound::select('Reference_Number_1')->get();
            $packageWarehouseList = PackageWarehouse::select('Reference_Number_1')->get();

            $packageList = $packageInboundList->merge($packageWarehouseList);

            foreach($packageList as $package)
            {
                $inventoryToolDetail = new InventoryToolDetail();
                $inventoryToolDetail->id                 = uniqid();
                $inventoryToolDetail->idInventoryTool    = $idInventory;
                $inventoryToolDetail->Reference_Number_1 = $package->Reference_Number_1;
                $inventoryToolDetail->status             = 'Pending';
                $inventoryToolDetail->save();
            }
            
            $inventoryTool->nf = count($packageList);
            $inventoryTool->save();

            DB::commit();

            return ['statusCode' => true, 'idInventory' => $idInventory];
        }
        catch(Exception $e)
        {
            DB::rollback();

            return ['statusCode' => false];
        }
    }

    public function Finish($idInventoryTool)
    {
        $inventoryTool = InventoryTool::find($idInventoryTool);
        $inventoryTool->status = 'Finalized';
        $inventoryTool->save();

        return ['statusCode' => true];
    }

    public function Download($idInventoryTool)
    {
        $delimiter = ',';
        $filename  = 'INVENTORY TOOL.csv';
        $file      = fopen('php://memory', 'w');

        $inventoryTool = InventoryTool::find($idInventoryTool);

        fputcsv($file, array('DATE', date('m/d/Y H:i:s', strtotime($inventoryTool->created_at))), $delimiter);
        fputcsv($file, array('USER', $inventoryTool->userName), $delimiter);
        fputcsv($file, array('NOT FOUND ', $inventoryTool->nf), $delimiter);
        fputcsv($file, array('OVERAGE', $inventoryTool->ov), $delimiter);

        fputcsv($file, array(''), $delimiter);
        //set column headers

        $fields = array('PACKAGE_ID' ,'STATUS');

        fputcsv($file, $fields, $delimiter);

        $inventoryToolList = InventoryToolDetail::where('idInventoryTool', $idInventoryTool)->get();

        foreach($inventoryToolList as $inventoryToolDetail)
        {
            $lineData = array(
                $inventoryToolDetail->Reference_Number_1,
                $inventoryToolDetail->status,
            );

            fputcsv($file, $lineData, $delimiter);
        }

        fseek($file, 0);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '";');

        fpassthru($file);
    }

    public function ListInventoryDetail($idInventoryTool)
    {
        $listInventoryToolDetailPending = InventoryToolDetail::where('idInventoryTool', $idInventoryTool)
                                                    ->where('status', 'Pending')
                                                    ->orderBy('created_at', 'desc')
                                                    ->get();

        $listInventoryToolDetailOverage = InventoryToolDetail::where('idInventoryTool', $idInventoryTool)
                                                    ->where('status', 'Overage')
                                                    ->orderBy('created_at', 'desc')
                                                    ->get();

        return [
            'listInventoryToolDetailPending' => $listInventoryToolDetailPending,
            'listInventoryToolDetailOverage' => $listInventoryToolDetailOverage
        ];
    }

    public function InsertPackage(Request $request)
    {
        $packageController = new PackageController();
        $statusActual = $packageController->GetStatus($request->Reference_Number_1);

        if($statusActual['status'] == "")
            return ['statusCode' => 'notExists'];

        try
        {
            DB::beginTransaction();

            $inventoryToolDetail = InventoryToolDetail::where('idInventoryTool', $request->idInventoryTool)
                                                ->where('Reference_Number_1', $request->Reference_Number_1)
                                                ->where('status', 'Pending')
                                                ->first();

            $inventoryTool = InventoryTool::find($request->idInventoryTool);

            if($inventoryToolDetail)
            {
                $inventoryTool->nf = $inventoryTool->nf - 1;

                $inventoryToolDetail->delete();
            }
            else
            {
                $inventoryTool->ov = $inventoryTool->ov + 1;

                $inventoryToolDetail = new InventoryToolDetail();
                $inventoryToolDetail->id                 = uniqid();
                $inventoryToolDetail->idInventoryTool    = $request->idInventoryTool;
                $inventoryToolDetail->Reference_Number_1 = $request->Reference_Number_1;
                $inventoryToolDetail->status             = 'Overage';
                $inventoryToolDetail->save();
            }

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