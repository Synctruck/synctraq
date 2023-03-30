<?php
namespace App\Service;

use App\Models\{ PackageBlocked };

class ServicePackageBlocked{

    public function List()
    {
        return PackageBlocked::orderBy('created_at', 'desc')->paginate(500);
    }

	public function Insert($request)
    {
        $package = new PackageBlocked();

        $package->id                 = uniqid();
        $package->Reference_Number_1 = $request->get('Reference_Number_1');
        $package->comment            = $request->get('comment');

        $package->save();
    }

    public function Delete($Reference_Number_1)
    {    
        return PackageBlocked::find($Reference_Number_1)->delete();
    }
}