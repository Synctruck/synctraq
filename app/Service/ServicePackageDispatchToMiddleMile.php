<?php
namespace App\Service;

use App\Models\{ PackageDispatchToMiddleMile };

use Auth;
use Log;

class ServicePackageDispatchToMiddleMile{

    public function List()
    {
        return PackageBlocked::with('user')->orderBy('created_at', 'desc')->paginate(500);
    }

	public function Insert($Reference_Number_1, $package, $currentStatus)
    {
        $packageDispatchToMiddleMile = PackageDispatchToMiddleMile::find($Reference_Number_1);

        if($packageDispatchToMiddleMile)
        {
            $this->Update($Reference_Number_1, $currentStatus);
        }
        else
        {
            Log::info($package);

            $packageDispatchToMiddleMile = new PackageDispatchToMiddleMile();
            $packageDispatchToMiddleMile->Reference_Number_1           = $Reference_Number_1;
            $packageDispatchToMiddleMile->idCompany                    = $package->idCompany;
            $packageDispatchToMiddleMile->company                      = $package->company;
            $packageDispatchToMiddleMile->Dropoff_Contact_Name         = $package->Dropoff_Contact_Name;
            $packageDispatchToMiddleMile->Dropoff_Company              = $package->Dropoff_Company;
            $packageDispatchToMiddleMile->Dropoff_Contact_Phone_Number = $package->Dropoff_Contact_Phone_Number;
            $packageDispatchToMiddleMile->Dropoff_Contact_Email        = $package->Dropoff_Contact_Email;
            $packageDispatchToMiddleMile->Dropoff_Address_Line_1       = $package->Dropoff_Address_Line_1;
            $packageDispatchToMiddleMile->Dropoff_City                 = $package->Dropoff_City;
            $packageDispatchToMiddleMile->Dropoff_Province             = $package->Dropoff_Province;
            $packageDispatchToMiddleMile->Dropoff_Postal_Code          = $package->Dropoff_Postal_Code;
            $packageDispatchToMiddleMile->Weight                       = $package->Weight;
            $packageDispatchToMiddleMile->Route                        = $package->Route;
            $packageDispatchToMiddleMile->currentStatus                = $currentStatus;
            $packageDispatchToMiddleMile->status                       = 'Scan in Dispatch to Middle Mile';
            $packageDispatchToMiddleMile->save();
        }
    }

    public function Get($Reference_Number_1)
    {
        return PackageDispatchToMiddleMile::find($Reference_Number_1);
    }

    public function Update($Reference_Number_1, $currentStatus)
    { 
        $packageDispatchToMiddleMile = PackageDispatchToMiddleMile::find($Reference_Number_1);
        $packageDispatchToMiddleMile->currentStatus = $currentStatus;
        $packageDispatchToMiddleMile->save();
    }

    public function Delete($Reference_Number_1)
    {
        $packageDispatchToMiddleMile = PackageDispatchToMiddleMile::find($Reference_Number_1);

        if($packageDispatchToMiddleMile)
        {
            return $packageDispatchToMiddleMile->delete();
        }
    }
}