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
                    $cuIn       = $length * $height * $width;

                    ////////// COMPANY ///////////////////////////////////////////////////
                    //calculando dimensiones y precios para company
                    $dimFactorCompany = DimFactorCompany::where('idCompany', $packageManifest->idCompany)->first();
                    $dimFactorCompany = $dimFactorCompany->factor;

                    $dimWeightCompany      = number_format($cuIn / $dimFactorCompany, 2);
                    $dimWeightCompanyRound = ceil($dimWeightCompany);

                    $weightCompany = $weight > $dimWeightCompanyRound ? $weight : $dimWeightCompanyRound;

                    //return "weight: ". $weight .' > dimWeightRoundCompany:'. $dimWeightRoundCompany .' idCompany:'. $packageManifest->idCompany;

                    //precio base de cobro a compañia
                    $priceCompany = new RangePriceCompanyController();
                    $priceCompany = $priceCompany->GetPriceCompany($packageManifest->idCompany, $weightCompany);

                    //precio peakeseason
                    $companyController       = new CompanyController();
                    $peakeSeasonPriceCompany = $companyController->GetPeakeSeason($packageManifest->idCompany, $weightCompany);
                    
                    //precio base
                    $priceBaseCompany = number_format($priceCompany + $peakeSeasonPriceCompany, 2);

                    $dieselPrice = Configuration::first()->diesel_price;

                    $surchargePercentageCompany = $companyController->GetPercentage($packageManifest->idCompany, $dieselPrice);
                    $surchargePriceCompany      = number_format(($priceBaseCompany * $surchargePercentageCompany) / 100, 4);
                    $totalPriceCompany          = number_format($priceBaseCompany + $surchargePriceCompany, 2);
                    ///////// END COMPANY

                    try
                    {
                        DB::beginTransaction();

                        $packagePriceCompanyTeam = new PackagePriceCompanyTeam();

                        $packagePriceCompanyTeam->id                         = date('YmdHis');
                        $packagePriceCompanyTeam->Reference_Number_1         = $packageManifest->Reference_Number_1;
                        $packagePriceCompanyTeam->weight                     = $weight;
                        $packagePriceCompanyTeam->length                     = $length;
                        $packagePriceCompanyTeam->height                     = $height;
                        $packagePriceCompanyTeam->width                      = $width;
                        $packagePriceCompanyTeam->cuIn                       = $cuIn;
                        $packagePriceCompanyTeam->dimFactorCompany           = $dimFactorCompany;
                        $packagePriceCompanyTeam->dimWeightCompany           = $dimWeightCompany;
                        $packagePriceCompanyTeam->dimWeightCompanyRound      = $dimWeightCompanyRound;
                        $packagePriceCompanyTeam->priceWeightCompany         = $priceCompany;
                        $packagePriceCompanyTeam->peakeSeasonPriceCompany    = $peakeSeasonPriceCompany;
                        $packagePriceCompanyTeam->priceBaseCompany           = $priceBaseCompany;
                        $packagePriceCompanyTeam->surchargePercentageCompany = $surchargePercentageCompany;
                        $packagePriceCompanyTeam->surchargePriceCompany      = $surchargePriceCompany;
                        $packagePriceCompanyTeam->totalPriceCompany          = $totalPriceCompany;

                        $packagePriceCompanyTeam->save();

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
                        $packageInbound->Notes                        = $packageManifest->Notes;
                        $packageInbound->Weight                       = $packageManifest->Weight;
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
                        $packageHistory->Notes                        = $packageManifest->Notes;
                        $packageHistory->Weight                       = $packageManifest->Weight;
                        $packageHistory->Route                        = $packageManifest->Route;
                        $packageHistory->Date_Inbound                 = date('Y-m-d H:s:i');
                        $packageHistory->Description                  = 'Inbound - for: API CARGO';
                        $packageHistory->inbound                      = 1;
                        $packageHistory->quantity                     = $packageManifest->quantity;
                        $packageHistory->status                       = 'Inbound';
                        $packageHistory->created_at                   = date('Y-m-d H:i:s');
                        $packageHistory->updated_at                   = date('Y-m-d H:i:s');

                        $packageHistory->save();

                        $packageManifest->delete();

                        DB::commit();

                        //data for INLAND
                        $packageController = new PackageController();
                        $packageController->SendStatusToInland($packageManifest, 'Inbound', null);
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
}
