<?php
namespace App\Service;

use App\Models\{ PackageTerminal };

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
}