<?php
namespace App\Service;

use App\Models\{ PackageLmCarrier };

use Auth;
use Log;

class ServicePackageLmCarrier{

    public function List()
    {
        return PackageBlocked::with('user')->orderBy('created_at', 'desc')->paginate(500);
    }

	public function Insert($Reference_Number_1, $package, $currentStatus)
    {
        $packageLmCarrier = PackageLmCarrier::find($Reference_Number_1);

        if($packageLmCarrier)
        {
            $this->Update($Reference_Number_1, $currentStatus);
        }
        else
        {
            Log::info($package);

            $packageLmCarrier = new PackageLmCarrier();
            $packageLmCarrier->Reference_Number_1           = $Reference_Number_1;
            $packageLmCarrier->idCompany                    = $package->idCompany;
            $packageLmCarrier->company                      = $package->company;
            $packageLmCarrier->Dropoff_Contact_Name         = $package->Dropoff_Contact_Name;
            $packageLmCarrier->Dropoff_Company              = $package->Dropoff_Company;
            $packageLmCarrier->Dropoff_Contact_Phone_Number = $package->Dropoff_Contact_Phone_Number;
            $packageLmCarrier->Dropoff_Contact_Email        = $package->Dropoff_Contact_Email;
            $packageLmCarrier->Dropoff_Address_Line_1       = $package->Dropoff_Address_Line_1;
            $packageLmCarrier->Dropoff_City                 = $package->Dropoff_City;
            $packageLmCarrier->Dropoff_Province             = $package->Dropoff_Province;
            $packageLmCarrier->Dropoff_Postal_Code          = $package->Dropoff_Postal_Code;
            $packageLmCarrier->Weight                       = $package->Weight;
            $packageLmCarrier->Route                        = $package->Route;
            $packageLmCarrier->currentStatus                = $currentStatus;
            $packageLmCarrier->status                       = 'Scan in LM Carrier';
            $packageLmCarrier->save();
        }
    }

    public function Get($Reference_Number_1)
    {
        return PackageLmCarrier::find($Reference_Number_1);
    }

    public function Update($Reference_Number_1, $currentStatus)
    { 
        $packageLmCarrier = PackageLmCarrier::find($Reference_Number_1);
        $packageLmCarrier->currentStatus = $currentStatus;
        $packageLmCarrier->save();
    }

    public function Delete($Reference_Number_1)
    {
        $packageLmCarrier = PackageLmCarrier::find($Reference_Number_1);

        if($packageLmCarrier)
        {
            return $packageLmCarrier->delete();
        }
    }
}