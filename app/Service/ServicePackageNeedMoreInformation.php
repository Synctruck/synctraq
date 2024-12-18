<?php
namespace App\Service;

use App\Models\{ PackageNeedMoreInformation, PackageWarehouse, PackageHistory, PackageHistoryNeeMoreInformation, PackageInbound, Cellar };

use App\Http\Controllers\Api\PackageController;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use Log;
use Session;
use DateTime;

class ServicePackageNeedMoreInformation{ 

    public function List($request, $idCompany, $dateStart,$dateEnd, $route, $state)
    {
        return $this->GetData($request, $idCompany, $dateStart,$dateEnd, $route, $state);
    }
    
    public function Insert($request)
    {
        $packageNeedMoreInformation = $this->Get($request->get('Reference_Number_1'));

        if($packageNeedMoreInformation)
        {
            return ['stateAction' => 'existsNMI', 'packageNMI' => $packageNeedMoreInformation];
        }

        $package = PackageInbound::find($request->get('Reference_Number_1'));
        $package = $package ? $package : PackageWarehouse::find($request->get('Reference_Number_1'));

        if($package)
        {
            $created_at = date('Y-m-d H:i:s');

            $packageNeedMoreInformation = new PackageNeedMoreInformation();
            $packageNeedMoreInformation->Reference_Number_1           = $package->Reference_Number_1;
            $packageNeedMoreInformation->idCompany                    = $package->idCompany;
            $packageNeedMoreInformation->company                      = $package->company;
            $packageNeedMoreInformation->idStore                      = $package->idStore;
            $packageNeedMoreInformation->store                        = $package->store;
            $packageNeedMoreInformation->Dropoff_Contact_Name         = $package->Dropoff_Contact_Name;
            $packageNeedMoreInformation->Dropoff_Company              = $package->Dropoff_Company;
            $packageNeedMoreInformation->Dropoff_Contact_Phone_Number = $package->Dropoff_Contact_Phone_Number;
            $packageNeedMoreInformation->Dropoff_Contact_Email        = $package->Dropoff_Contact_Email;
            $packageNeedMoreInformation->Dropoff_Address_Line_1       = $package->Dropoff_Address_Line_1;
            $packageNeedMoreInformation->Dropoff_Address_Line_2       = $package->Dropoff_Address_Line_2;
            $packageNeedMoreInformation->Dropoff_City                 = $package->Dropoff_City;
            $packageNeedMoreInformation->Dropoff_Province             = $package->Dropoff_Province;
            $packageNeedMoreInformation->Dropoff_Postal_Code          = $package->Dropoff_Postal_Code;
            $packageNeedMoreInformation->Notes                        = $package->Notes;
            $packageNeedMoreInformation->Weight                       = $package->Weight;
            $packageNeedMoreInformation->Route                        = $package->Route;
            $packageNeedMoreInformation->idUser                       = Auth::user()->id;
            $packageNeedMoreInformation->quantity                     = $package->quantity;
            $packageNeedMoreInformation->status                       = 'NMI';

            $cellar = Cellar::find(Auth::user()->idCellar);
            
            if($cellar)
            {    
                $packageNeedMoreInformation->idCellar    = $cellar->id;
                $packageNeedMoreInformation->nameCellar  = $cellar->name;
                $packageNeedMoreInformation->stateCellar = $cellar->state;
                $packageNeedMoreInformation->cityCellar  = $cellar->city;
            }

            $packageNeedMoreInformation->save();

            $packageHistoryNeeMoreInformation = new PackageHistoryNeeMoreInformation();
            $packageHistoryNeeMoreInformation->id                 = uniqid();
            $packageHistoryNeeMoreInformation->idUser             = Auth::user()->id;
            $packageHistoryNeeMoreInformation->Reference_Number_1 = $package->Reference_Number_1;
            $packageHistoryNeeMoreInformation->status             = 'ToCorrect';
            $packageHistoryNeeMoreInformation->save();

            $packageHistory = new PackageHistory();
            $packageHistory->id                           = uniqid();
            $packageHistory->Reference_Number_1           = $package->Reference_Number_1;
            $packageHistory->idCompany                    = $package->idCompany;
            $packageHistory->company                      = $package->company;
            $packageHistory->idStore                      = $package->idStore;
            $packageHistory->store                        = $package->store;
            $packageHistory->Dropoff_Contact_Name         = $package->Dropoff_Contact_Name;
            $packageHistory->Dropoff_Company              = $package->Dropoff_Company;
            $packageHistory->Dropoff_Contact_Phone_Number = $package->Dropoff_Contact_Phone_Number;
            $packageHistory->Dropoff_Contact_Email        = $package->Dropoff_Contact_Email;
            $packageHistory->Dropoff_Address_Line_1       = $package->Dropoff_Address_Line_1;
            $packageHistory->Dropoff_Address_Line_2       = $package->Dropoff_Address_Line_2;
            $packageHistory->Dropoff_City                 = $package->Dropoff_City;
            $packageHistory->Dropoff_Province             = $package->Dropoff_Province;
            $packageHistory->Dropoff_Postal_Code          = $package->Dropoff_Postal_Code;
            $packageHistory->Notes                        = $package->Notes;
            $packageHistory->Weight                       = $package->Weight;
            $packageHistory->Route                        = $package->Route;
            $packageHistory->idUser                       = Auth::user()->id;
            $packageHistory->Description                  = 'For: '. Auth::user()->name .' '. Auth::user()->nameOfOwner;
            $packageHistory->quantity                     = $package->quantity;
            $packageHistory->status                       = 'NMI';
            $packageHistory->actualDate                   = $created_at;
            $packageHistory->created_at                   = $created_at;
            $packageHistory->updated_at                   = $created_at;

            if($cellar)
            {    
                $packageHistory->idCellar    = $cellar->id;
                $packageHistory->nameCellar  = $cellar->name;
                $packageHistory->stateCellar = $cellar->state;
                $packageHistory->cityCellar  = $cellar->city;
            }
            $packageHistory->save();

            $package->delete(); 

            //data for INLAND
            $packageController = new PackageController();
            $packageController->SendStatusToInland($package, 'NMI', null, date('Y-m-d H:i:s'));

            $packageHistory = PackageHistory::where('Reference_Number_1', $package->Reference_Number_1)
                                                ->where('sendToInland', 1)
                                                ->where('status', 'Manifest')
                                                ->first();

            if($packageHistory)
            {
                $packageController->SendStatusToOtherCompany($package, 'NMI', null, date('Y-m-d H:i:s'));
            }
            //end data for inland

            return ['stateAction' => true, 'package' => $package];
        }

        return ['stateAction' => 'notExists'];
    }

    public function Export($request, $idCompany, $dateStart, $dateEnd, $route, $state, $typeExport)
    {
        $delimiter = ",";
        $filename  = $typeExport == 'download' ? "PACKAGES - NEED MORE INFORMATION " . date('Y-m-d H:i:s') . ".csv" : Auth::user()->id ."- PACKAGES - NEED MORE INFORMATION.csv";
        $file      = $typeExport == 'download' ? fopen('php://memory', 'w') : fopen(public_path($filename), 'w');

        //set column headers
        $fields = array('DATE', 'HOUR', 'COMPANY', 'PACKAGE ID', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'WEIGHT', 'ROUTE');

        fputcsv($file, $fields, $delimiter);

        $packageNeedMoreInformationList = $this->GetData($request, $idCompany, $dateStart,$dateEnd, $route, $state, 'export');

        foreach($packageNeedMoreInformationList as $packageNeedMoreInformation)
        {
            $lineData = array(
                                date('m-d-Y', strtotime($packageNeedMoreInformation->created_at)),
                                date('H:i:s', strtotime($packageNeedMoreInformation->created_at)),
                                $packageNeedMoreInformation->company,
                                $packageNeedMoreInformation->Reference_Number_1,
                                $packageNeedMoreInformation->Dropoff_Contact_Name,
                                $packageNeedMoreInformation->Dropoff_Contact_Phone_Number,
                                $packageNeedMoreInformation->Dropoff_Address_Line_1,
                                $packageNeedMoreInformation->Dropoff_City,
                                $packageNeedMoreInformation->Dropoff_Province,
                                $packageNeedMoreInformation->Dropoff_Postal_Code,
                                $packageNeedMoreInformation->Weight,
                                $packageNeedMoreInformation->Route
                            );

            fputcsv($file, $lineData, $delimiter);
        }


        if($typeExport == 'download')
        {
            fseek($file, 0);

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '";');

            fpassthru($file);
        }
        else
        {
            rewind($file);
            fclose($file);

            SendGeneralExport('Packages Need More Information', $filename);

            return ['stateAction' => true];
        }
    }

    private function GetData($request, $idCompany, $dateStart,$dateEnd, $route, $state, $type = 'list')
    {
        if($request->get('Reference_Number_1') && $type == 'list')
        {
            $packageListNMI = PackageNeedMoreInformation::where('Reference_Number_1', $request->get('Reference_Number_1'))
                                                        ->orderBy('created_at', 'desc')
                                                        ->paginate(20);
        }
        else
        {
            $dateStart = $dateStart .' 00:00:00';
            $dateEnd   = $dateEnd .' 23:59:59';

            $routes = explode(',', $route);
            $states = explode(',', $state);

            $packageListNMI = PackageNeedMoreInformation::whereBetween('created_at', [$dateStart, $dateEnd]);

            if(Auth::user()->role->name != 'Master')
            {
                $packageListNMI = $packageListNMI->where('idUser', Auth::user()->id);
            }

            if($route != 'all')
            {
                $packageListNMI = $packageListNMI->whereIn('Route', $routes);
            }

            if($state != 'all')
            {
                $packageListNMI = $packageListNMI->whereIn('Dropoff_Province', $states);
            }

            if($idCompany)
            {
                $packageListNMI = $packageListNMI->where('idCompany', $idCompany);
            }

            if($type == 'list')
            {
                $packageListNMI = $packageListNMI->orderBy('created_at', 'desc')
                                                ->select('company', 'Reference_Number_1', 'Dropoff_Contact_Name', 'Dropoff_Contact_Phone_Number', 'Dropoff_Address_Line_1', 'Dropoff_City', 'Dropoff_Province', 'Dropoff_Postal_Code', 'Weight', 'Route', 'updated','nameCellar', 'created_at')
                                                ->paginate(50);
            }
            else
            {
                $packageListNMI = $packageListNMI->orderBy('created_at', 'desc')->get();
            }
        }

        return $packageListNMI;
    }

    public function MoveToWarehouse($Reference_Number_1)
    {
        $packageNeedMoreInformation = $this->Get($Reference_Number_1);

        if(!$packageNeedMoreInformation) return false;

        try
        {
            DB::beginTransaction();

            $packageNeedMoreInformation = $this->Get($Reference_Number_1);

            $created_at = date('Y-m-d H:i:s');

            $packageWarehouse = new PackageWarehouse();

            $packageWarehouse->Reference_Number_1           = $packageNeedMoreInformation->Reference_Number_1;
            $packageWarehouse->idCompany                    = $packageNeedMoreInformation->idCompany;
            $packageWarehouse->company                      = $packageNeedMoreInformation->company;
            $packageWarehouse->idStore                      = $packageNeedMoreInformation->idStore;
            $packageWarehouse->store                        = $packageNeedMoreInformation->store;
            $packageWarehouse->Dropoff_Contact_Name         = $packageNeedMoreInformation->Dropoff_Contact_Name;
            $packageWarehouse->Dropoff_Company              = $packageNeedMoreInformation->Dropoff_Company;
            $packageWarehouse->Dropoff_Contact_Phone_Number = $packageNeedMoreInformation->Dropoff_Contact_Phone_Number;
            $packageWarehouse->Dropoff_Contact_Email        = $packageNeedMoreInformation->Dropoff_Contact_Email;
            $packageWarehouse->Dropoff_Address_Line_1       = $packageNeedMoreInformation->Dropoff_Address_Line_1;
            $packageWarehouse->Dropoff_Address_Line_2       = $packageNeedMoreInformation->Dropoff_Address_Line_2;
            $packageWarehouse->Dropoff_City                 = $packageNeedMoreInformation->Dropoff_City;
            $packageWarehouse->Dropoff_Province             = $packageNeedMoreInformation->Dropoff_Province;
            $packageWarehouse->Dropoff_Postal_Code          = $packageNeedMoreInformation->Dropoff_Postal_Code;
            $packageWarehouse->Notes                        = $packageNeedMoreInformation->Notes;
            $packageWarehouse->Weight                       = $packageNeedMoreInformation->Weight;
            $packageWarehouse->Route                        = $packageNeedMoreInformation->Route;
            $packageWarehouse->idUser                       = Auth::user()->id;
            $packageWarehouse->quantity                     = $packageNeedMoreInformation->quantity;
            $packageWarehouse->status                       = 'Warehouse';

            $cellar = Cellar::find(Auth::user()->idCellar);

            if($cellar)
            {
                $packageWarehouse->idCellar    = $cellar->id;
                $packageWarehouse->nameCellar  = $cellar->name;
                $packageWarehouse->stateCellar = $cellar->state;
                $packageWarehouse->cityCellar  = $cellar->city;
            }

            $packageWarehouse->save();

            $packageHistoryNeeMoreInformation = new PackageHistoryNeeMoreInformation();
            $packageHistoryNeeMoreInformation->id                 = uniqid();
            $packageHistoryNeeMoreInformation->idUser             = Auth::user()->id;
            $packageHistoryNeeMoreInformation->Reference_Number_1 = $packageNeedMoreInformation->Reference_Number_1;
            $packageHistoryNeeMoreInformation->status             = 'Corrected';
            $packageHistoryNeeMoreInformation->save();

            $packageHistory = new PackageHistory();
            $packageHistory->id                           = uniqid();
            $packageHistory->Reference_Number_1           = $packageNeedMoreInformation->Reference_Number_1;
            $packageHistory->idCompany                    = $packageNeedMoreInformation->idCompany;
            $packageHistory->company                      = $packageNeedMoreInformation->company;
            $packageHistory->idStore                      = $packageNeedMoreInformation->idStore;
            $packageHistory->store                        = $packageNeedMoreInformation->store;
            $packageHistory->Dropoff_Contact_Name         = $packageNeedMoreInformation->Dropoff_Contact_Name;
            $packageHistory->Dropoff_Company              = $packageNeedMoreInformation->Dropoff_Company;
            $packageHistory->Dropoff_Contact_Phone_Number = $packageNeedMoreInformation->Dropoff_Contact_Phone_Number;
            $packageHistory->Dropoff_Contact_Email        = $packageNeedMoreInformation->Dropoff_Contact_Email;
            $packageHistory->Dropoff_Address_Line_1       = $packageNeedMoreInformation->Dropoff_Address_Line_1;
            $packageHistory->Dropoff_Address_Line_2       = $packageNeedMoreInformation->Dropoff_Address_Line_2;
            $packageHistory->Dropoff_City                 = $packageNeedMoreInformation->Dropoff_City;
            $packageHistory->Dropoff_Province             = $packageNeedMoreInformation->Dropoff_Province;
            $packageHistory->Dropoff_Postal_Code          = $packageNeedMoreInformation->Dropoff_Postal_Code;
            $packageHistory->Notes                        = $packageNeedMoreInformation->Notes;
            $packageHistory->Weight                       = $packageNeedMoreInformation->Weight;
            $packageHistory->Route                        = $packageNeedMoreInformation->Route;
            $packageHistory->idUser                       = Auth::user()->id;
            $packageHistory->Description                  = '( NMI REMOVAL) For: '. Auth::user()->name .' '. Auth::user()->nameOfOwner;
            $packageHistory->quantity                     = $packageNeedMoreInformation->quantity;
            $packageHistory->status                       = 'Warehouse';
            $packageHistory->actualDate                   = $created_at;
            $packageHistory->created_at                   = $created_at;
            $packageHistory->updated_at                   = $created_at;
                
            if($cellar)
            {
                $packageHistory->idCellar    = $cellar->id;
                $packageHistory->nameCellar  = $cellar->name;
                $packageHistory->stateCellar = $cellar->state;
                $packageHistory->cityCellar  = $cellar->city;
            }

            $packageHistory->save();

            $packageNeedMoreInformation->delete();

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
        return PackageNeedMoreInformation::find($Reference_Number_1);
    }

    public function Update($request)
    {
        $packageHistoryList  = PackageHistory::where('Reference_Number_1', $request->get('Reference_Number_1'))->get();

        foreach($packageHistoryList as $packageHistory)
        {
            if($request->get('internalComment'))
            {
                $packageHistory = PackageHistory::find($packageHistory->id);
                $packageHistory->internal_comment = $request->get('internalComment');
                $packageHistory->save();
            }
        }

        $packageHistory = PackageHistory::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();
        $packageHistory->Dropoff_Contact_Name         = $request->get('Dropoff_Contact_Name');
        $packageHistory->Dropoff_Contact_Phone_Number = $request->get('Dropoff_Contact_Phone_Number');
        $packageHistory->Dropoff_Address_Line_1       = $request->get('Dropoff_Address_Line_1');
        $packageHistory->Dropoff_City                 = $request->get('Dropoff_City');
        $packageHistory->Dropoff_Province             = $request->get('Dropoff_Province');
        $packageHistory->Dropoff_Postal_Code          = $request->get('Dropoff_Postal_Code');
        $packageHistory->Weight                       = $request->get('Weight');
        $packageHistory->Route                        = $request->get('Route');
        $packageHistory->save();

        $packageNMI = PackageNeedMoreInformation::find($request->get('Reference_Number_1'));
        $packageNMI->Dropoff_Contact_Name         = $request->get('Dropoff_Contact_Name');
        $packageNMI->Dropoff_Contact_Phone_Number = $request->get('Dropoff_Contact_Phone_Number');
        $packageNMI->Dropoff_Address_Line_1       = $request->get('Dropoff_Address_Line_1');
        $packageNMI->Dropoff_City                 = $request->get('Dropoff_City');
        $packageNMI->Dropoff_Province             = $request->get('Dropoff_Province');
        $packageNMI->Dropoff_Postal_Code          = $request->get('Dropoff_Postal_Code');
        $packageNMI->Weight                       = $request->get('Weight');
        $packageNMI->Route                        = $request->get('Route');
        $packageNMI->updated                      = 1;
        $packageNMI->save();

        $packageHistoryNeeMoreInformation = new PackageHistoryNeeMoreInformation();
        $packageHistoryNeeMoreInformation->id                 = uniqid();
        $packageHistoryNeeMoreInformation->idUser             = Auth::user()->id;
        $packageHistoryNeeMoreInformation->Reference_Number_1 = $request->get('Reference_Number_1');
        $packageHistoryNeeMoreInformation->internalComment    = $request->get('internalComment');
        $packageHistoryNeeMoreInformation->status             = 'Updated';
        $packageHistoryNeeMoreInformation->save();
    }
}