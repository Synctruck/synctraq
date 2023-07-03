<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{ ChargeCompanyDetail, Company, Configuration, DimFactorCompany, DimFactorTeam, PackageDispatch, PackageHistory, PackageInbound, PackageManifest, PackageWarehouse, PackagePriceCompanyTeam, PackageReturnCompany, PeakeSeasonCompany, RangePriceCompany, States };

use Illuminate\Support\Facades\Validator;

use App\Http\Controllers\{ CompanyController, RangePriceCompanyController };
use App\Http\Controllers\Api\PackageController;

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
                    $dimensions = $length .'|'. $height .'|'. $width;

                    try
                    {
                        DB::beginTransaction();

                        $packageInbound = new PackageInbound();

                        $packageInbound->Reference_Number_1           = $packageManifest->Reference_Number_1;
                        $packageInbound->idCompany                    = $packageManifest->idCompany;
                        $packageInbound->company                      = $packageManifest->company;
                        $packageInbound->idStore                      = $packageManifest->idStore;
                        $packageInbound->store                        = $packageManifest->store;
                        $packageInbound->TRUCK                        = $request->get('TRUCK') ? $request->get('TRUCK') : '';
                        $packageInbound->CLIENT                       = $packageManifest->company;
                        $packageInbound->Dropoff_Contact_Name         = $packageManifest->Dropoff_Contact_Name;
                        $packageInbound->Dropoff_Company              = $packageManifest->Dropoff_Company;
                        $packageInbound->Dropoff_Contact_Phone_Number = $packageManifest->Dropoff_Contact_Phone_Number;
                        $packageInbound->Dropoff_Contact_Email        = $packageManifest->Dropoff_Contact_Email;
                        $packageInbound->Dropoff_Address_Line_1       = $packageManifest->Dropoff_Address_Line_1;
                        $packageInbound->Dropoff_Address_Line_2       = $packageManifest->Dropoff_Address_Line_2;
                        $packageInbound->Dropoff_City                 = $packageManifest->Dropoff_City;
                        $packageInbound->Dropoff_Province             = $packageManifest->Dropoff_Province;
                        $packageInbound->Dropoff_Postal_Code          = $packageManifest->Dropoff_Postal_Code;
                        $packageInbound->Notes                        = $dimensions;
                        $packageInbound->Weight                       = $weight;
                        $packageInbound->Route                        = $packageManifest->Route;
                        $packageInbound->quantity                     = $packageManifest->quantity;
                        $packageInbound->status                       = 'Inbound';

                        $packageInbound->save();

                        $packageHistory = new PackageHistory();

                        $packageHistory->id                           = uniqid();
                        $packageHistory->Reference_Number_1           = $packageManifest->Reference_Number_1;
                        $packageHistory->idCompany                    = $packageManifest->idCompany;
                        $packageHistory->company                      = $packageManifest->company;
                        $packageHistory->idStore                      = $packageManifest->idStore;
                        $packageHistory->store                        = $packageManifest->store;
                        $packageHistory->TRUCK                        = $request->get('TRUCK') ? $request->get('TRUCK') : '';
                        $packageHistory->CLIENT                       = $request->get('CLIENT') ? $request->get('CLIENT') : '';
                        $packageHistory->Dropoff_Contact_Name         = $packageManifest->Dropoff_Contact_Name;
                        $packageHistory->Dropoff_Company              = $packageManifest->Dropoff_Company;
                        $packageHistory->Dropoff_Contact_Phone_Number = $packageManifest->Dropoff_Contact_Phone_Number;
                        $packageHistory->Dropoff_Contact_Email        = $packageManifest->Dropoff_Contact_Email;
                        $packageHistory->Dropoff_Address_Line_1       = $packageManifest->Dropoff_Address_Line_1;
                        $packageHistory->Dropoff_Address_Line_2       = $packageManifest->Dropoff_Address_Line_2;
                        $packageHistory->Dropoff_City                 = $packageManifest->Dropoff_City;
                        $packageHistory->Dropoff_Province             = $packageManifest->Dropoff_Province;
                        $packageHistory->Dropoff_Postal_Code          = $packageManifest->Dropoff_Postal_Code;
                        $packageHistory->Notes                        = $dimensions;
                        $packageHistory->Weight                       = $weight;
                        $packageHistory->Route                        = $packageManifest->Route;
                        $packageHistory->Date_Inbound                 = date('Y-m-d H:s:i');
                        $packageHistory->Description                  = 'Inbound - for: API CARGO';
                        $packageHistory->inbound                      = 1;
                        $packageHistory->quantity                     = $packageManifest->quantity;
                        $packageHistory->status                       = 'Inbound';
                        $packageHistory->actualDate                   = date('Y-m-d H:i:s');
                        $packageHistory->created_at                   = date('Y-m-d H:i:s');
                        $packageHistory->updated_at                   = date('Y-m-d H:i:s');

                        $packageHistory->save();

                        $packageManifest->delete();

                        DB::commit();

                        //data for INLAND
                        $packageController = new PackageController();
                        $packageController->SendStatusToInland($packageManifest, 'Inbound', null, date('Y-m-d H:i:s'));
                        //end data for inland

                        Log::info("============== CORRECT - INBOUND  ========");
                        Log::info("===================================");
                    }
                    catch(Exception $e)
                    {
                        DB::rollback();

                        Log::info("============== ERROR - ROLLBACK  ========");
                        Log::info("===================================");
                    }
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

        Log::info($request);

        $package = PackageManifest::find($Reference_Number_1);

        $package = $package != null ? $package : PackageInbound::find($Reference_Number_1);
        $package = $package != null ? $package : PackageDispatch::find($Reference_Number_1);
        $package = $package != null ? $package : PackageWarehouse::find($Reference_Number_1);
        $package = $package != null ? $package : PackageReturnCompany::find($Reference_Number_1);

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
                    }
                }
                else if($status == 'ReInbound')
                {
                    if($package->status == 'Inbound')
                    {
                        return response()->json(
                            [
                                'status' => 400,
                                'error' => 'PACKAGE_ID '. $package->Reference_Number_1 .' is already taken in ReInbound.'
                            ]
                        , 400);
                    }
                    else
                    {
                        $packageCreate = new PackageInbound();
                    }
                }
                else if($status == 'ReturnCompany')
                {
                    $packageCreate = new PackageReturnCompany();
                }

                $packageCreate->Reference_Number_1           = $package->Reference_Number_1;
                $packageCreate->idCompany                    = $package->idCompany;
                $packageCreate->company                      = $package->company;

                if($status != 'ReturnCompany')
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

                if($packageCreate->status == 'Delivery') 
                {
                    $packageCharge = ChargeCompanyDetail::where('Reference_Number_1', $package->Reference_Number_1)->first();

                    if($packageCharge)
                    {
                        $packageCreate->invoiced = 1;
                    }
                    
                    $packageCreate->require_invoice = $require_invoice ? 1 : 0;
                }

                $packageCreate->save();

                $packageHistory = new PackageHistory();
                $packageHistory->id                           = uniqid();
                $packageHistory->Reference_Number_1           = $package->Reference_Number_1;
                $packageHistory->idCompany                    = $package->idCompany;
                $packageHistory->company                      = $package->company;

                if($status != 'ReturnCompany')
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
                $packageHistory->save();

                if($package->status == 'Manifest' || $package->status == 'Inbound' || $package->status == 'ReInbound' || $package->status == 'ReturnCompany')
                {
                    $package->delete();
                }
                else if($package->status == 'Dispatch' || $package->status == 'Delivery')
                {
                    if($status == 'Inbound' || $status == 'ReInbound' || $package->status == 'ReturnCompany')
                    {
                        $package->delete();
                    }
                }

                //$packageController = new PackageController();
                //$packageController->SendStatusToInland($package, $status, null, date('Y-m-d H:i:s'));

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