<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{ Company, Configuration, DimFactorCompany, DimFactorTeam, PackageHistory, PackageInbound, PackageManifest, PackageWarehouse, PackagePriceCompanyTeam, PeakeSeasonCompany, RangePriceCompany, States };

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

    public function InsertForInland(Request $request, $keyApi)
    {
        //$Reference_Number_1 = $request['responses']['dimension']['info']['barcode'];
        //$packageManifest    = PackageManifest::with('blockeds')->find($Reference_Number_1);

        Log::info("===================================");
        Log::info("============== API - FOR - INBOUND ========");
        Log::info($request);

        /*try
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
        }*/
    }
}