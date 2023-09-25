<?php
namespace App\Service;

use App\Models\{ PackageLost, PackageWarehouse, PackageHistory };

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use Log;
use Session;
use DateTime;

class ServicePackageLost{

    public function MoveToWarehouse($Reference_Number_1)
    {
        $packageLost = $this->Get($Reference_Number_1);

        if(!$packageLost) return false;

        try
        {
            DB::beginTransaction();

            $packageLost = $this->Get($Reference_Number_1);

            $created_at = date('Y-m-d H:i:s');

            $packageWarehouse = new PackageWarehouse();

            $packageWarehouse->Reference_Number_1           = $packageLost->Reference_Number_1;
            $packageWarehouse->idCompany                    = $packageLost->idCompany;
            $packageWarehouse->company                      = $packageLost->company;
            $packageWarehouse->idStore                      = $packageLost->idStore;
            $packageWarehouse->store                        = $packageLost->store;
            $packageWarehouse->Dropoff_Contact_Name         = $packageLost->Dropoff_Contact_Name;
            $packageWarehouse->Dropoff_Company              = $packageLost->Dropoff_Company;
            $packageWarehouse->Dropoff_Contact_Phone_Number = $packageLost->Dropoff_Contact_Phone_Number;
            $packageWarehouse->Dropoff_Contact_Email        = $packageLost->Dropoff_Contact_Email;
            $packageWarehouse->Dropoff_Address_Line_1       = $packageLost->Dropoff_Address_Line_1;
            $packageWarehouse->Dropoff_Address_Line_2       = $packageLost->Dropoff_Address_Line_2;
            $packageWarehouse->Dropoff_City                 = $packageLost->Dropoff_City;
            $packageWarehouse->Dropoff_Province             = $packageLost->Dropoff_Province;
            $packageWarehouse->Dropoff_Postal_Code          = $packageLost->Dropoff_Postal_Code;
            $packageWarehouse->Notes                        = $packageLost->Notes;
            $packageWarehouse->Weight                       = $packageLost->Weight;
            $packageWarehouse->Route                        = $packageLost->Route;
            $packageWarehouse->idUser                       = Auth::user()->id;
            $packageWarehouse->quantity                     = $packageLost->quantity;
            $packageWarehouse->status                       = 'Warehouse';
            $packageWarehouse->save();

            $packageHistory = new PackageHistory();
            $packageHistory->id                           = uniqid();
            $packageHistory->Reference_Number_1           = $packageLost->Reference_Number_1;
            $packageHistory->idCompany                    = $packageLost->idCompany;
            $packageHistory->company                      = $packageLost->company;
            $packageHistory->idStore                      = $packageLost->idStore;
            $packageHistory->store                        = $packageLost->store;
            $packageHistory->Dropoff_Contact_Name         = $packageLost->Dropoff_Contact_Name;
            $packageHistory->Dropoff_Company              = $packageLost->Dropoff_Company;
            $packageHistory->Dropoff_Contact_Phone_Number = $packageLost->Dropoff_Contact_Phone_Number;
            $packageHistory->Dropoff_Contact_Email        = $packageLost->Dropoff_Contact_Email;
            $packageHistory->Dropoff_Address_Line_1       = $packageLost->Dropoff_Address_Line_1;
            $packageHistory->Dropoff_Address_Line_2       = $packageLost->Dropoff_Address_Line_2;
            $packageHistory->Dropoff_City                 = $packageLost->Dropoff_City;
            $packageHistory->Dropoff_Province             = $packageLost->Dropoff_Province;
            $packageHistory->Dropoff_Postal_Code          = $packageLost->Dropoff_Postal_Code;
            $packageHistory->Notes                        = $packageLost->Notes;
            $packageHistory->Weight                       = $packageLost->Weight;
            $packageHistory->Route                        = $packageLost->Route;
            $packageHistory->idUser                       = Auth::user()->id;
            $packageHistory->Description                  = '( LOST REMOVAL) For: '. Auth::user()->name .' '. Auth::user()->nameOfOwner;
            $packageHistory->quantity                     = $packageLost->quantity;
            $packageHistory->status                       = 'Warehouse';
            $packageHistory->actualDate                   = $created_at;
            $packageHistory->created_at                   = $created_at;
            $packageHistory->updated_at                   = $created_at;
            $packageHistory->save();

            $packageLost->delete();

            DB::commit();

            return ['stateAction' => true];
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            return response()->json(['message' => 'Error']);
        }
    }

    public function Get($Reference_Number_1)
    {
        return PackageLost::find($Reference_Number_1);
    }
}