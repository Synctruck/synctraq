<?php
namespace App\Service;

use App\Models\{ PackageTerminal, PackageWarehouse, PackageHistory };

use Illuminate\Support\Facades\Auth;

use DB;
use Log;
use Session;
use DateTime;

class ServicePackageTerminal{

	public function Insert($package)
    {
        $packageTerminal = new PackageTerminal();

        $packageTerminal->Reference_Number_1           = $package->Reference_Number_1;
        $packageTerminal->idCompany                    = $package->idCompany;
        $packageTerminal->company                      = $package->company;
        $packageTerminal->idStore                      = $package->idStore;
        $packageTerminal->store                        = $package->store;
        $packageTerminal->Dropoff_Contact_Name         = $package->Dropoff_Contact_Name;
        $packageTerminal->Dropoff_Company              = $package->Dropoff_Company;
        $packageTerminal->Dropoff_Contact_Phone_Number = $package->Dropoff_Contact_Phone_Number;
        $packageTerminal->Dropoff_Contact_Email        = $package->Dropoff_Contact_Email;
        $packageTerminal->Dropoff_Address_Line_1       = $package->Dropoff_Address_Line_1;
        $packageTerminal->Dropoff_Address_Line_2       = $package->Dropoff_Address_Line_2;
        $packageTerminal->Dropoff_City                 = $package->Dropoff_City;
        $packageTerminal->Dropoff_Province             = $package->Dropoff_Province;
        $packageTerminal->Dropoff_Postal_Code          = $package->Dropoff_Postal_Code;
        $packageTerminal->Notes                        = $package->Notes;
        $packageTerminal->Weight                       = $package->Weight;
        $packageTerminal->Route                        = $package->Route;
        $packageTerminal->idUser                       = Auth::user()->id;
        $packageTerminal->quantity                     = $package->quantity;
        $packageTerminal->status                       = 'Terminal';

        $packageTerminal->save();
    }

    public function MoveToWarehouse($Reference_Number_1)
    {
        $packageTerminal = PackageTerminal::find($Reference_Number_1);

        if($packageTerminal)
        {
            $created_at = date('Y-m-d H:i:s');

            $packageWarehouse = new PackageWarehouse();

            $packageWarehouse->Reference_Number_1           = $packageTerminal->Reference_Number_1;
            $packageWarehouse->idCompany                    = $packageTerminal->idCompany;
            $packageWarehouse->company                      = $packageTerminal->company;
            $packageWarehouse->idStore                      = $packageTerminal->idStore;
            $packageWarehouse->store                        = $packageTerminal->store;
            $packageWarehouse->Dropoff_Contact_Name         = $packageTerminal->Dropoff_Contact_Name;
            $packageWarehouse->Dropoff_Company              = $packageTerminal->Dropoff_Company;
            $packageWarehouse->Dropoff_Contact_Phone_Number = $packageTerminal->Dropoff_Contact_Phone_Number;
            $packageWarehouse->Dropoff_Contact_Email        = $packageTerminal->Dropoff_Contact_Email;
            $packageWarehouse->Dropoff_Address_Line_1       = $packageTerminal->Dropoff_Address_Line_1;
            $packageWarehouse->Dropoff_Address_Line_2       = $packageTerminal->Dropoff_Address_Line_2;
            $packageWarehouse->Dropoff_City                 = $packageTerminal->Dropoff_City;
            $packageWarehouse->Dropoff_Province             = $packageTerminal->Dropoff_Province;
            $packageWarehouse->Dropoff_Postal_Code          = $packageTerminal->Dropoff_Postal_Code;
            $packageWarehouse->Notes                        = $packageTerminal->Notes;
            $packageWarehouse->Weight                       = $packageTerminal->Weight;
            $packageWarehouse->Route                        = $packageTerminal->Route;
            $packageWarehouse->idUser                       = Auth::user()->id;
            $packageWarehouse->quantity                     = $packageTerminal->quantity;
            $packageWarehouse->status                       = 'Warehouse';

            $packageWarehouse->save();

            $packageHistory = new PackageHistory();

            $packageHistory->id                           = uniqid();
            $packageHistory->Reference_Number_1           = $packageTerminal->Reference_Number_1;
            $packageHistory->idCompany                    = $packageTerminal->idCompany;
            $packageHistory->company                      = $packageTerminal->company;
            $packageHistory->idStore                      = $packageTerminal->idStore;
            $packageHistory->store                        = $packageTerminal->store;
            $packageHistory->Dropoff_Contact_Name         = $packageTerminal->Dropoff_Contact_Name;
            $packageHistory->Dropoff_Company              = $packageTerminal->Dropoff_Company;
            $packageHistory->Dropoff_Contact_Phone_Number = $packageTerminal->Dropoff_Contact_Phone_Number;
            $packageHistory->Dropoff_Contact_Email        = $packageTerminal->Dropoff_Contact_Email;
            $packageHistory->Dropoff_Address_Line_1       = $packageTerminal->Dropoff_Address_Line_1;
            $packageHistory->Dropoff_Address_Line_2       = $packageTerminal->Dropoff_Address_Line_2;
            $packageHistory->Dropoff_City                 = $packageTerminal->Dropoff_City;
            $packageHistory->Dropoff_Province             = $packageTerminal->Dropoff_Province;
            $packageHistory->Dropoff_Postal_Code          = $packageTerminal->Dropoff_Postal_Code;
            $packageHistory->Notes                        = $packageTerminal->Notes;
            $packageHistory->Weight                       = $packageTerminal->Weight;
            $packageHistory->Route                        = $packageTerminal->Route;
            $packageHistory->idUser                       = Auth::user()->id;
            $packageHistory->Description                  = '( TERMINAL REMOVAL) For: '. Auth::user()->name .' '. Auth::user()->nameOfOwner;
            $packageHistory->quantity                     = $packageTerminal->quantity;
            $packageHistory->status                       = 'Warehouse';
            $packageHistory->actualDate                   = $created_at;
            $packageHistory->created_at                   = $created_at;
            $packageHistory->updated_at                   = $created_at;

            $packageHistory->save();

            $packageTerminal->delete();

            return "PACKAGE MOVED TO WAREHOUSE";
        }

        return "package does not exists in terminal";
    }

    public function Get($Reference_Number_1)
    {
        return PackageTerminal::find($Reference_Number_1);
    }
}