<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\{ ChargeCompanyDetail, Company, Configuration, DimFactorCompany, DimFactorTeam, PackageDispatch, Cellar, PackageHistory, PackageInbound, PackageManifest, PackageWarehouse, PackagePriceCompanyTeam, PackageReturnCompany, PackageLmCarrier, PackageLost, PackageTerminal, PackageWeight, PeakeSeasonCompany, RangePriceCompany, States, PackageDispatchToMiddleMile};

use Illuminate\Support\Facades\Validator;

use App\Http\Controllers\{ CompanyController, RangePriceCompanyController };
use App\Http\Controllers\Api\PackageController;

use App\Service\ServicePackageLmCarrier;
use App\Service\ServicePackageDispatchToMiddleMile;

use DB;
use Log;
use Session;

class PackageInboundController extends Controller
{
    public function Insert(Request $request)
    {
        $Reference_Number_1 = $request['responses']['dimension']['info']['barcode'];
        $packageManifest    = PackageManifest::with('blockeds')->find($Reference_Number_1);

        Log::info("===================================");
        Log::info("============== API - INBOUND ========");
        Log::info("Reference_Number_1: ". $Reference_Number_1);
        Log::info("dimensions");
        Log::info($request['responses']['dimension']['info']['dimensions']);

        if($packageManifest)
        {
            if($packageManifest->filter || count($packageManifest->blockeds) > 0)
            {
                Log::info("============== BLOCKED ========");
                Log::info("===================================");
            }
            else
            {
                $state = States::where('name', $packageManifest->Dropoff_Province)
                            ->where('filter', 1)
                            ->first();

                if($state == null)
                {
                    $dimensions = $request['responses']['dimension']['info']['dimensions'];
                    $weight     = $dimensions['weight']['net'];
                    $width      = $dimensions['width'];
                    $height     = $dimensions['height'];
                    $length     = $dimensions['length'];

                    $dimFactorCompany = DimFactorCompany::where('idCompany', $packageManifest->idCompany)->first();
                    $packageWeight    = PackageWeight::find($Reference_Number_1);

                    if(!$packageWeight)
                    {
                        $packageWeight = new PackageWeight();
                    }
                    
                    if($dimFactorCompany)
                    {
                        $packageWeight->weight2 = ($width * $height * $length) / $dimFactorCompany->factor;
                    }

                    $packageWeight->weight4 = $weight;
                    $packageWeight->save();
                }
                else
                {
                    Log::info("============== STATE - BLOCKED ========");
                    Log::info("===================================");
                }
            }
        }
        else
        {
            Log::info("============== PACKAGE - DOES NOT EXISTS ========");
            Log::info("===================================");
        }
    }

    public function ShipmentInland(Request $request, $keyApi)
    {
        $company = Company::where('key_api', $keyApi)->first();
        
        if(!$company)
        {
            return response()->json(['message' => "Authentication Failed"], 401);
        }

        $Reference_Number_1 = $request['package_id'];
        $status             = $request['status'];
        $created_at         = $request['datetime'];
        $pod_url            = $request['pod_url'];
        $description        = $request['description'];
        $require_invoice    = $request['require_invoice'];
        $idTeam             = $request['synctruck_team'];
        $idCellar           = $request['warehouse'];

        Log::info($request);

        $package = PackageManifest::find($Reference_Number_1);
        
        $package = $package != null ? $package : PackageInbound::find($Reference_Number_1);
        $package = $package != null ? $package : PackageDispatch::find($Reference_Number_1);
        $package = $package != null ? $package : PackageWarehouse::find($Reference_Number_1);
        $package = $package != null ? $package : PackageReturnCompany::find($Reference_Number_1);
        $package = $package != null ? $package : PackagelmCarrier::find($Reference_Number_1);
        $package = $package != null ? $package : PackageTerminal::find($Reference_Number_1);
        $package = $package != null ? $package : PackageLost::find($Reference_Number_1);
        $package = $package != null ? $package : PackageDispatchToMiddleMile::find($Reference_Number_1);

        try
        {
            DB::beginTransaction();

            if($package)
            {
                if($status == 'Inbound')
                {
                    if($package->status == 'Inbound')
                    {
                        return response()->json(
                            [
                                'status' => 400,
                                'error' => 'PACKAGE_ID '. $package->Reference_Number_1 .' is already taken in Inbound.'
                            ]
                        , 400);
                    }
                    else
                    {
                        $packageCreate = new PackageInbound();
                    }
                }
                if($status == 'Terminal')
                {
                    if($package->status == 'Terminal')
                    {
                        return response()->json(
                            [
                                'status' => 400,
                                'error' => 'PACKAGE_ID '. $package->Reference_Number_1 .' is already taken in Terminal.'
                            ]
                        , 400);
                    }
                    else
                    {
                        $packageCreate = new PackageTerminal();
                    }
                }
                if($status == 'Lost')
                {
                    if($package->status == 'Lost')
                    {
                        return response()->json(
                            [
                                'status' => 400,
                                'error' => 'PACKAGE_ID '. $package->Reference_Number_1 .' is already taken in Lost.'
                            ]
                        , 400);
                    }
                    else
                    {
                        $packageCreate = new PackageLost();
                    }
                }
                else if($status == 'LM Carrier')
                {
                    if($package->status == 'LM Carrier')
                    {
                        return response()->json(
                            [
                                'status' => 400,
                                'error' => 'PACKAGE_ID '. $package->Reference_Number_1 .' is already taken in Inbound.'
                            ]
                        , 400);
                    }
                    else
                    {
                        $packageCreate = new PackagelmCarrier();
                    }
                    
                }
                else if($status == 'Dispatch To MiddleMile')
                {
                    if($package->status == 'Dispatch To MiddleMile')
                    {
                        return response()->json(
                            [
                                'status' => 400,
                                'error' => 'PACKAGE_ID '. $package->Reference_Number_1 .' is already taken in Inbound.'
                            ]
                        , 400);
                    }
                    else
                    {
                        $packageCreate = new PackageDispatchToMiddleMile();
                    }
                    
                }
                else if($status == 'Middle Mile Scan' || $status == 'Warehouse')
                {
                    if($package->status == 'Middle Mile Scan' && $status == 'Middle Mile Scan')
                    {
                        return response()->json(
                            [
                                'status' => 400,
                                'error' => 'PACKAGE_ID '. $package->Reference_Number_1 .' is already taken in '. $status .'.'
                            ]
                        , 400);
                    }
                    else if($package->status == 'Warehouse' && $status == 'Warehouse')
                    {
                        return response()->json(
                            [
                                'status' => 400,
                                'error' => 'PACKAGE_ID '. $package->Reference_Number_1 .' is already taken in '. $status .'.'
                            ]
                        , 400);
                    }
                    else
                    {
                        $packageCreate = PackageWarehouse::find($package->Reference_Number_1);

                        if(!$packageCreate)
                        {
                            $packageCreate = new PackageWarehouse();
                        }
                    }
                }
                else if($status == 'Dispatch')
                {
                    if($package->status == 'Dispatch')
                    {
                        return response()->json(
                            [
                                'status' => 400,
                                'error' => 'PACKAGE_ID '. $package->Reference_Number_1 .' is already taken in Dispatch.'
                            ]
                        , 400);
                    }
                    else if($package->status == 'Delivery')
                    {
                        $packageCreate = PackageDispatch::find($package->Reference_Number_1);
                    }
                    else
                    {
                        $packageCreate = new PackageDispatch();
                    }
                }
                else if($status == 'Delivery')
                {
                    if($package->status == 'Delivery')
                    {
                        return response()->json(
                            [
                                'status' => 400,
                                'error' => 'PACKAGE_ID '. $package->Reference_Number_1 .' is already taken in Delivery.'
                            ]
                        , 400);
                    }
                    else
                    {
                        $packageCreate = PackageDispatch::find($package->Reference_Number_1);

                        if(!$packageCreate)
                        {
                            $packageCreate = new PackageDispatch();
                        }

                        $packageCreate->photoUrl        = $pod_url;
                        $packageCreate->Date_Delivery   = $created_at;
                        $packageCreate->idTeam          = $idTeam ? $idTeam : 0;
                    }
                }
                else if($status == 'ReInbound')
                {
                    $packageCreate = PackageInbound::find($package->Reference_Number_1);

                    if(!$packageCreate)
                    {
                        $packageCreate = new PackageInbound();
                    }
                }
                else if($status == 'ReturnCompany')
                {
                    if($package->status == 'ReturnCompany')
                    {
                        return response()->json(
                            [
                                'status' => 400,
                                'error' => 'PACKAGE_ID '. $package->Reference_Number_1 .' is already taken in ReturnCompany.'
                            ]
                        , 400);
                    }
                    else
                    {
                        $packageCreate = new PackageReturnCompany();
                    }
                }

                $packageCreate->Reference_Number_1 = $package->Reference_Number_1;
                $packageCreate->idCompany          = $package->idCompany;
                $packageCreate->company            = $package->company;

                if($status != 'ReturnCompany' && $status != 'LM Carrier' && $status != 'Lost' && $status != 'Dispatch To MiddleMile')
                {
                    $packageCreate->idStore  = $package->idStore;
                    $packageCreate->store    = $package->store;
                    $packageCreate->quantity = $package->quantity;
                }
                
                $packageCreate->Dropoff_Contact_Name         = $package->Dropoff_Contact_Name;
                $packageCreate->Dropoff_Company              = $package->Dropoff_Company;
                $packageCreate->Dropoff_Contact_Phone_Number = $package->Dropoff_Contact_Phone_Number;
                $packageCreate->Dropoff_Contact_Email        = $package->Dropoff_Contact_Email;
                $packageCreate->Dropoff_Address_Line_1       = $package->Dropoff_Address_Line_1;
                $packageCreate->Dropoff_Address_Line_2       = $package->Dropoff_Address_Line_2;
                $packageCreate->Dropoff_City                 = $package->Dropoff_City;
                $packageCreate->Dropoff_Province             = $package->Dropoff_Province;
                $packageCreate->Dropoff_Postal_Code          = $package->Dropoff_Postal_Code;
                $packageCreate->Weight                       = $package->Weight;
                $packageCreate->Route                        = $package->Route;
                $packageCreate->status                       = $status == 'ReInbound' ? 'Inbound': $status;
                $packageCreate->created_at                   = $created_at;
                $packageCreate->updated_at                   = $created_at;

                $cellar = Cellar::find($idCellar);

                if($cellar)
                {    
                    $packageCreate->idCellar    = $cellar->id;
                    $packageCreate->nameCellar  = $cellar->name;
                    $packageCreate->stateCellar = $cellar->state;
                    $packageCreate->cityCellar  = $cellar->city;
                }

                if($packageCreate->status == 'Delivery') 
                {
                    $packageCharge = ChargeCompanyDetail::where('Reference_Number_1', $package->Reference_Number_1)->first();

                    if($packageCharge)
                    {
                        //$packageCreate->require_invoice = 1;
                    }
                    
                    //$packageCreate->require_invoice = $require_invoice === true ? 1 : 0;
                }

                //$packageCreate->require_invoice = 1;
                $packageCreate->save();

                $packageHistory = new PackageHistory();
                $packageHistory->id                           = uniqid();
                $packageHistory->Reference_Number_1           = $package->Reference_Number_1;
                $packageHistory->idCompany                    = $package->idCompany;
                $packageHistory->company                      = $package->company;

                if($status != 'ReturnCompany' && $status != 'LM Carrier' && $status != 'Lost' && $status != 'Dispatch To MiddleMile')
                {
                    $packageHistory->idStore  = $package->idStore;
                    $packageHistory->store    = $package->store;
                    $packageHistory->quantity = $package->quantity;
                }

                $packageHistory->Dropoff_Contact_Name         = $package->Dropoff_Contact_Name;
                $packageHistory->Dropoff_Company              = $package->Dropoff_Company;
                $packageHistory->Dropoff_Contact_Phone_Number = $package->Dropoff_Contact_Phone_Number;
                $packageHistory->Dropoff_Contact_Email        = $package->Dropoff_Contact_Email;
                $packageHistory->Dropoff_Address_Line_1       = $package->Dropoff_Address_Line_1;
                $packageHistory->Dropoff_Address_Line_2       = $package->Dropoff_Address_Line_2;
                $packageHistory->Dropoff_City                 = $package->Dropoff_City;
                $packageHistory->Dropoff_Province             = $package->Dropoff_Province;
                $packageHistory->Dropoff_Postal_Code          = $package->Dropoff_Postal_Code;
                $packageHistory->Weight                       = $package->Weight;
                $packageHistory->Route                        = $package->Route;
                $packageHistory->Date_Inbound                 = $created_at;
                $packageHistory->Description                  = 'INLAND: '. $description;
                $packageHistory->inbound                      = 1;
                $packageHistory->status                       = $status;
                $packageHistory->actualDate                   = date('Y-m-d H:i:s');
                $packageHistory->created_at                   = $created_at;
                $packageHistory->updated_at                   = $created_at;

                if($cellar)
                {    
                    $packageHistory->idCellar    = $cellar->id;
                    $packageHistory->nameCellar  = $cellar->name;
                    $packageHistory->stateCellar = $cellar->state;
                    $packageHistory->cityCellar  = $cellar->city;
                }
                
                $packageHistory->save();

                Log::info('$package->status: '. $package->status);
                Log::info('$status: '. $status);

                if($package->status == 'Manifest' || $package->status == 'Inbound' || $package->status == 'ReInbound' || $package->status == 'ReturnCompany' || $package->status == 'Middle Mile Scan' || $package->status == 'Warehouse' || $package->status == 'LM Carrier' || $package->status == 'Terminal' || $package->status == 'Lost' || $package->status == 'Dispatch To MiddleMile')
                {
                    if($package->status == 'Warehouse' && $status == 'Middle Mile Scan')
                    {

                    }
                    else if($package->status == 'Middle Mile Scan' && $status == 'Warehouse')
                    {

                    }
                    else
                    {
                        if($package->status == 'Inbound' && $status == 'ReInbound')
                        {

                        }
                        else
                        {
                            $package->delete();
                        }
                    }
                }
                else if($package->status == 'Dispatch' || $package->status == 'Delivery')
                {
                    if($status == 'Inbound' || $status == 'ReInbound' || $status == 'Warehouse' || $status == 'Middle Mile Scan' || $status == 'ReturnCompany' || $status == 'Terminal' || $status == 'Lost')
                    {                        
                        $package->delete();
                    }
                }

                if($package->company != 'INLAND LOGISTICS' && $status != 'Warehouse' && $status != 'LM Carrier' && $status != 'Dispatch To MiddleMile')
                {
                    $packageController = new PackageController();
                    $packageController->SendStatusToInland($package, $status, [], date('Y-m-d H:i:s'));
                }

                DB::commit();

                return response()->json(['message' => "Shipment has been received"], 200);
            }
            else
            {
                return response()->json(
                            [
                                'status' => 400,
                                'error' => 'PACKAGE_ID does not exists'
                            ]
                        , 400);
            }
        }
        catch (Exception $e)
        {
            DB::rollback();

            return response()->json(['message' => "Unhandled error, try again"], 500);
        }
    }
}