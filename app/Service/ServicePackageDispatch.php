<?php
namespace App\Service;

use App\Models\{ PackageDispatch, PackageHistory, PackageLost, PackageNeedMoreInformation, PackageWarehouse };

use Auth;
use DB;

class ServicePackageDispatch{

    public function GetIdDriverPackageDebrief($idTeam)
    {
        $packageDispatchList = PackageDispatch::whereIn('status', ['Dispatch', 'Delete'])->where('idUserDispatch', '!=', 0);

        if($idTeam != 0)
        {
            $packageDispatchList = $packageDispatchList->where('idTeam', $idTeam);
        }

        return $packageDispatchList->groupBy('idUserDispatch')
                                    ->select('idUserDispatch', DB::raw('COUNT(idUserDispatch) as quantityOfPackages'))
                                    ->orderBy('idTeam', 'asc')
                                    ->get('idUserDispatch');
    }

	public function QuantityPackageDebrief($idDriver)
    {
        return PackageDispatch::where('idUserDispatch', $idDriver)
                            ->where('status', 'Dispatch')
                            ->get()
                            ->count();
    }

    public function ListPackagesDebrief($idDriver)
    {
        return PackageDispatch::where('idUserDispatch', $idDriver)
                            ->whereIn('status', ['Dispatch', 'Delete'])
                            ->orderBy('created_at', 'asc')
                            ->get();
    }

    public function MoveToOtherStatus($Reference_Number_1, $status, $comment)
    {
        $packageDispatch = PackageDispatch::where('status', 'Dispatch')
                                            ->orWhere('status', 'Delete')
                                            ->find($Reference_Number_1);

        dd($packageDispatch);
        if($packageDispatch)
        {
            try
            {
                DB::beginTransaction();

                $description =  '(Drebrief) for: '. Auth::user()->name .' '. Auth::user()->nameOfOwner;

                if($status == 'Lost')
                {
                    $package = new PackageLost();
                }
                else if($status == 'NMI')
                {
                    $package = new PackageNeedMoreInformation();

                    $description = $comment;
                }
                else if($status == 'Warehouse')
                {
                    $package = new PackageWarehouse();
                }

                $created_at = date('Y-m-d H:i:s');

                $package->Reference_Number_1           = $packageDispatch->Reference_Number_1;
                $package->idCompany                    = $packageDispatch->idCompany;
                $package->company                      = $packageDispatch->company;
                $package->Dropoff_Contact_Name         = $packageDispatch->Dropoff_Contact_Name;
                $package->Dropoff_Company              = $packageDispatch->Dropoff_Company;
                $package->Dropoff_Contact_Phone_Number = $packageDispatch->Dropoff_Contact_Phone_Number;
                $package->Dropoff_Contact_Email        = $packageDispatch->Dropoff_Contact_Email;
                $package->Dropoff_Address_Line_1       = $packageDispatch->Dropoff_Address_Line_1;
                $package->Dropoff_Address_Line_2       = $packageDispatch->Dropoff_Address_Line_2;
                $package->Dropoff_City                 = $packageDispatch->Dropoff_City;
                $package->Dropoff_Province             = $packageDispatch->Dropoff_Province;
                $package->Dropoff_Postal_Code          = $packageDispatch->Dropoff_Postal_Code;
                $package->Notes                        = $packageDispatch->Notes;
                $package->Route                        = $packageDispatch->Route;
                $package->Weight                       = $packageDispatch->Weight;
                $package->idUser                       = Auth::user()->id;
                $package->status                       = $status;
                $package->save();

                $packageHistory = new PackageHistory();
                $packageHistory->id                           = uniqid();
                $packageHistory->Reference_Number_1           = $packageDispatch->Reference_Number_1;
                $packageHistory->idCompany                    = $packageDispatch->idCompany;
                $packageHistory->company                      = $packageDispatch->company;
                $packageHistory->Dropoff_Contact_Name         = $packageDispatch->Dropoff_Contact_Name;
                $packageHistory->Dropoff_Company              = $packageDispatch->Dropoff_Company;
                $packageHistory->Dropoff_Contact_Phone_Number = $packageDispatch->Dropoff_Contact_Phone_Number;
                $packageHistory->Dropoff_Contact_Email        = $packageDispatch->Dropoff_Contact_Email;
                $packageHistory->Dropoff_Address_Line_1       = $packageDispatch->Dropoff_Address_Line_1;
                $packageHistory->Dropoff_Address_Line_2       = $packageDispatch->Dropoff_Address_Line_2;
                $packageHistory->Dropoff_City                 = $packageDispatch->Dropoff_City;
                $packageHistory->Dropoff_Province             = $packageDispatch->Dropoff_Province;
                $packageHistory->Dropoff_Postal_Code          = $packageDispatch->Dropoff_Postal_Code;
                $packageHistory->Notes                        = $packageDispatch->Notes;
                $packageHistory->Weight                       = $packageDispatch->Weight;
                $packageHistory->Route                        = $packageDispatch->Route;
                $packageHistory->idUser                       = Auth::user()->id;
                $packageHistory->description                  = $description;
                $packageHistory->Date_Inbound                 = $created_at;
                $packageHistory->status                       = $status;
                $packageHistory->actualDate                   = $created_at;
                $packageHistory->created_at                   = $created_at;
                $packageHistory->updated_at                   = $created_at;
                $packageHistory->save();

                $packageDispatch->delete();

                DB::commit();

                return true;
            }
            catch(Exception $e)
            {
                DB::rollback();

                return false;
            }
        }
        else
        {
            return 'packageNotExists';
        }
    }
}