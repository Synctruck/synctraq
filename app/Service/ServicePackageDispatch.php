<?php
namespace App\Service;

use App\Models\{ PackageDispatch };

use DB;

class ServicePackageDispatch{

    public function GetIdDriverPackageDebrief()
    {
        return PackageDispatch::where('status', 'Dispatch')
                            ->where('idUserDispatch', '!=', 0)
                            ->groupBy('idUserDispatch')
                            ->select('idUserDispatch', DB::raw('COUNT(idUserDispatch) as quantityOfPackages'))
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
                            ->where('status', 'Dispatch')
                            ->get();
    }
}