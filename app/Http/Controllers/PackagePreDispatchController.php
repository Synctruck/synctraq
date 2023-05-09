<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{ AuxDispatchUser, Comment, Company, Configuration, DimFactorTeam, Driver, PackageHistory, PackageHighPriority, PackageBlocked, PackageDispatch,  PackageFailed, PackageInbound, PackageLost, PackageManifest, PackageNotExists, PackagePreDispatch, PackagePriceCompanyTeam, PackageReturn, PackageReturnCompany, PackageWarehouse, PalletDispatch, PaymentTeamReturn, TeamRoute, User };

use Illuminate\Support\Facades\Validator;

use App\Http\Controllers\Api\PackageController;
use App\Http\Controllers\{ RangePriceTeamRouteCompanyController, PackageDispatchController, TeamController };

use DB;
use Illuminate\Support\Facades\Auth;
use Log;
use Session;

class PackagePreDispatchController extends Controller
{
    private $apiKey;

    private $base64;

    private $headers;

    public function __construct()
    {
        $this->apiKey = Configuration::first()->key_onfleet;

        $this->base64 = base64_encode($this->apiKey .':');

        $this->headers = [
                        'Authorization: Basic '. $this->base64,
                    ];
    }

    public function Index()
    {
        return view('package.predispatch');
    }

    public function List($numberPallet)
    {
        $palletDispatch = PalletDispatch::find($numberPallet);

        if($palletDispatch->status == 'Closed')
        {
            $packagePreDispatchList = PackageHistory::where('numberPallet', $numberPallet)
                                                ->orderBy('created_at', 'desc');
        }
        else
        {
            $packagePreDispatchList = PackagePreDispatch::where('numberPallet', $numberPallet)
                                                ->orderBy('created_at', 'desc');
        }
        
        $packagePreDispatchList = $packagePreDispatchList->select(
                                                            'created_at',
                                                            'numberPallet',
                                                            'Reference_Number_1',
                                                            'Dropoff_Contact_Name',
                                                            'Dropoff_Contact_Phone_Number',
                                                            'Dropoff_Address_Line_1',
                                                            'Dropoff_City',
                                                            'Dropoff_Province',
                                                            'Dropoff_Postal_Code',
                                                            'Weight',
                                                            'Route'
                                                        )
                                                        ->get();

        return ['packagePreDispatchList' => $packagePreDispatchList, 'palletDispatch' => $palletDispatch]; 
    }

    public function Insert(Request $request)
    {
        $packageBlocked = PackageBlocked::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();

        if($packageBlocked)
        {
            return ['stateAction' => 'validatedFilterPackage', 'packageBlocked' => $packageBlocked, 'packageManifest' => null];
        }
        
        $package = PackagePreDispatch::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();

        if($package)
        {
            return ['stateAction' => 'packageInPreDispatch'];
        }

        $packageLost = PackageLost::find($request->get('Reference_Number_1'));

        if($packageLost)
        {
            return ['stateAction' => 'validatedLost'];
        }

        $package = PackageManifest::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();

        if($package == null)
        {
           $package = PackageInbound::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();
        }

        if($package == null)
        {
           $package = PackageWarehouse::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();
        }

        if($package == null)
        {
            $package = PackageDispatch::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();

            if($package)
            {
                return ['stateAction' => 'packageInDispatch'];
            }
        }

        if($package)
        {
            $palletDispatch = PalletDispatch::find($request->get('numberPallet'));

            if($package->Route == '')
            {
                return ['stateAction' => 'notRoutePackage'];
            }

            if(strpos($palletDispatch->Route, $package->Route) === false)
            {
                return ['stateAction' => 'notRoute'];
            }

            try
            {
                DB::beginTransaction();

                $nowDate    = date('Y-m-d H:i:s');
                $created_at = date('Y-m-d H:i:s');

                if($package->status == 'Manifest')
                {
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
                    $packageHistory->idUserInbound                = Auth::user()->id;
                    $packageHistory->Date_Inbound                 = $created_at;
                    $packageHistory->Description                  = 'For: '. Auth::user()->name .' '. Auth::user()->nameOfOwner;
                    $packageHistory->inbound                      = 1;
                    $packageHistory->quantity                     = $package->quantity;
                    $packageHistory->status                       = 'Inbound';
                    $packageHistory->actualDate                   = $nowDate;
                    $packageHistory->created_at                   = $nowDate;
                    $packageHistory->updated_at                   = $nowDate;

                    $packageHistory->save();
                }

                $packagePreDispatch = new PackagePreDispatch();

                $packagePreDispatch->Reference_Number_1           = $package->Reference_Number_1;
                $packagePreDispatch->numberPallet                 = $request->get('numberPallet');
                $packagePreDispatch->idCompany                    = $package->idCompany;
                $packagePreDispatch->company                      = $package->company;
                $packagePreDispatch->idStore                      = $package->idStore;
                $packagePreDispatch->store                        = $package->store;
                $packagePreDispatch->Dropoff_Contact_Name         = $package->Dropoff_Contact_Name;
                $packagePreDispatch->Dropoff_Company              = $package->Dropoff_Company;
                $packagePreDispatch->Dropoff_Contact_Phone_Number = $package->Dropoff_Contact_Phone_Number;
                $packagePreDispatch->Dropoff_Contact_Email        = $package->Dropoff_Contact_Email;
                $packagePreDispatch->Dropoff_Address_Line_1       = $package->Dropoff_Address_Line_1;
                $packagePreDispatch->Dropoff_Address_Line_2       = $package->Dropoff_Address_Line_2;
                $packagePreDispatch->Dropoff_City                 = $package->Dropoff_City;
                $packagePreDispatch->Dropoff_Province             = $package->Dropoff_Province;
                $packagePreDispatch->Dropoff_Postal_Code          = $package->Dropoff_Postal_Code;
                $packagePreDispatch->Notes                        = $package->Notes;
                $packagePreDispatch->Weight                       = $package->Weight;
                $packagePreDispatch->Route                        = $package->Route;
                $packagePreDispatch->idUser                       = Auth::user()->id;
                $packagePreDispatch->quantity                     = $package->quantity;
                $packagePreDispatch->status                       = 'PreDispatch';
                $packagePreDispatch->created_at                   = $created_at;
                $packagePreDispatch->updated_at                   = $created_at;

                $packagePreDispatch->save();

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
                $packageHistory->status                       = 'PreDispatch';
                $packageHistory->actualDate                   = $nowDate;
                $packageHistory->created_at                   = $created_at;
                $packageHistory->updated_at                   = $created_at;

                $packageHistory->save();

                $palletDispatch = PalletDispatch::find($request->get('numberPallet'));

                $palletDispatch->quantityPackage = $palletDispatch->quantityPackage + 1;

                $palletDispatch->save();

                $package->delete();

                DB::commit();

                return ['stateAction' => true];
            }
            catch(Exception $e)
            {
                DB::rollback();

                return ['stateAction' => false];
            }
        }
        else
        {
            return ['stateAction' => 'notExists'];
        }
    }

    public function ChangeToDispatch(Request $request)
    {
        $user = User::where('passwordDispatch', $request->get('passwordDispatch'))
                    ->select('id', 'name', 'nameOfOwner', 'passwordDispatch')
                    ->first();

        if($user)
        {
            try
            {
                DB::beginTransaction();

                $packagePreDispatchList = PackagePreDispatch::where('numberPallet', $request->get('numberPallet'))->get();

                foreach($packagePreDispatchList as $packagePreDispatch)
                {
                    $packagePreDispatch      = PackagePreDispatch::find($packagePreDispatch->Reference_Number_1);

                    $created_at  = date('Y-m-d H:i:s');
                    $team        = User::find($request->get('idTeam'));
                    $driver      = User::find($request->get('idDriver'));
                    $description = 'To: '. $team->name .' / '. $driver->name .' '. $driver->nameOfOwner;

                    $packageDispatch = new PackageDispatch();

                    $packageDispatch->Reference_Number_1           = $packagePreDispatch->Reference_Number_1;
                    $packageDispatch->idCompany                    = $packagePreDispatch->idCompany;
                    $packageDispatch->company                      = $packagePreDispatch->company;
                    $packageDispatch->idStore                      = $packagePreDispatch->idStore;
                    $packageDispatch->store                        = $packagePreDispatch->store;
                    $packageDispatch->Dropoff_Contact_Name         = $packagePreDispatch->Dropoff_Contact_Name;
                    $packageDispatch->Dropoff_Company              = $packagePreDispatch->Dropoff_Company;
                    $packageDispatch->Dropoff_Contact_Phone_Number = $packagePreDispatch->Dropoff_Contact_Phone_Number;
                    $packageDispatch->Dropoff_Contact_Email        = $packagePreDispatch->Dropoff_Contact_Email;
                    $packageDispatch->Dropoff_Address_Line_1       = $packagePreDispatch->Dropoff_Address_Line_1;
                    $packageDispatch->Dropoff_Address_Line_2       = $packagePreDispatch->Dropoff_Address_Line_2;
                    $packageDispatch->Dropoff_City                 = $packagePreDispatch->Dropoff_City;
                    $packageDispatch->Dropoff_Province             = $packagePreDispatch->Dropoff_Province;
                    $packageDispatch->Dropoff_Postal_Code          = $packagePreDispatch->Dropoff_Postal_Code;
                    $packageDispatch->Notes                        = $packagePreDispatch->Notes;
                    $packageDispatch->Weight                       = $packagePreDispatch->Weight;
                    $packageDispatch->Route                        = $packagePreDispatch->Route;
                    $packageDispatch->idUser                       = Auth::user()->id;
                    $packageDispatch->idTeam                       = $request->get('idTeam');
                    $packageDispatch->idUserDispatch               = $request->get('idDriver');
                    $packageDispatch->Date_Dispatch                = $created_at; 
                    $packageDispatch->quantity                     = $packagePreDispatch->quantity;
                    $packageDispatch->pricePaymentCompany          = $packagePriceCompanyTeam->totalPriceCompany;
                    $packageDispatch->pricePaymentTeam             = $packagePriceCompanyTeam->totalPriceTeam;
                    $packageDispatch->idPaymentTeam                = '';
                    $packageDispatch->status                       = 'Dispatch';
                    $packageDispatch->created_at                   = $created_at;
                    $packageDispatch->updated_at                   = $created_at;

                    $packageHistory = new PackageHistory();

                    $packageHistory->id                           = uniqid();
                    $packageHistory->Reference_Number_1           = $packagePreDispatch->Reference_Number_1;
                    $packageHistory->idCompany                    = $packagePreDispatch->idCompany;
                    $packageHistory->company                      = $packagePreDispatch->company;
                    $packageHistory->idStore                      = $packagePreDispatch->idStore;
                    $packageHistory->store                        = $packagePreDispatch->store;
                    $packageHistory->Dropoff_Contact_Name         = $packagePreDispatch->Dropoff_Contact_Name;
                    $packageHistory->Dropoff_Company              = $packagePreDispatch->Dropoff_Company;
                    $packageHistory->Dropoff_Contact_Phone_Number = $packagePreDispatch->Dropoff_Contact_Phone_Number;
                    $packageHistory->Dropoff_Contact_Email        = $packagePreDispatch->Dropoff_Contact_Email;
                    $packageHistory->Dropoff_Address_Line_1       = $packagePreDispatch->Dropoff_Address_Line_1;
                    $packageHistory->Dropoff_Address_Line_2       = $packagePreDispatch->Dropoff_Address_Line_2;
                    $packageHistory->Dropoff_City                 = $packagePreDispatch->Dropoff_City;
                    $packageHistory->Dropoff_Province             = $packagePreDispatch->Dropoff_Province;
                    $packageHistory->Dropoff_Postal_Code          = $packagePreDispatch->Dropoff_Postal_Code;
                    $packageHistory->Notes                        = $packagePreDispatch->Notes;
                    $packageHistory->Weight                       = $packagePreDispatch->Weight;
                    $packageHistory->Route                        = $packagePreDispatch->Route;
                    $packageHistory->idUser                       = Auth::user()->id;
                    $packageHistory->idTeam                       = $request->get('idTeam');
                    $packageHistory->idUserDispatch               = $request->get('idDriver');
                    $packageHistory->Date_Dispatch                = $created_at;
                    $packageHistory->dispatch                     = 1;
                    $packageHistory->autorizationDispatch         = 1;
                    $packageHistory->Description                  = $description;
                    $packageHistory->quantity                     = $packagePreDispatch->quantity;
                    $packageHistory->numberPallet                 = $request->get('numberPallet');
                    $packageHistory->status                       = 'Dispatch';
                    $packageHistory->actualDate                   = $created_at;
                    $packageHistory->created_at                   = $created_at;
                    $packageHistory->updated_at                   = $created_at;

                    $registerTask = new PackageDispatchController();
                    $registerTask = $registerTask->RegisterOnfleet($packagePreDispatch, $team, $driver);

                    if($registerTask['status'] == 200)
                    {
                        $idOnfleet   = explode('"', explode('"', explode('":', $registerTask['response'])[1])[1])[0];
                        $taskOnfleet = explode('"', explode('"', explode('":', $registerTask['response'])[5])[1])[0];

                        //data for INLAND
                        $packageController = new PackageController();
                        $packageController->SendStatusToInland($packagePreDispatch, 'Dispatch', null, $created_at);
                        //end data for inland

                        $packageDispatch->idOnfleet   = $idOnfleet;
                        $packageDispatch->taskOnfleet = $taskOnfleet;

                        $packageDispatch->save();
                        $packageHistory->save();
                        $packagePreDispatch->delete();
                    }
                }

                $packagePreDispatchList = PackagePreDispatch::where('numberPallet', $request->get('numberPallet'))->get();

                $closePallet = 0;

                $palletDispatch = PalletDispatch::find($request->get('numberPallet'));
                $palletDispatch->idUserDispatch = $user->id;
                $palletDispatch->dispatcher     = $user->name .' '. $user->nameOfOwner;

                if(count($packagePreDispatchList) == 0)
                {
                    $closePallet            = 1;
                    $palletDispatch->status = 'Closed';
                }

                $palletDispatch->save();

                DB::commit();

                return ['stateAction' => true, 'closePallet' => $closePallet];
            }
            catch (Exception $e)
            {
                DB::rollback();

                return ['stateAction' => false];
            }
        }
        
        return ['stateAction' => 'userNotExists'];
    }

    public function Export(Request $request, $idCompany, $dateStart,$dateEnd, $idTeam, $idDriver, $state, $routes)
    {
        $delimiter = ",";
        $filename = "PACKAGES - DISPATCH " . date('Y-m-d H:i:s') . ".csv";

        //create a file pointer
        $file = fopen('php://memory', 'w');

        //set column headers
        $fields = array('DATE' ,'HOUR', 'TEAM', 'DRIVER', 'PACKAGE ID', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'WEIGHT', 'ROUTE','TASK ONFLEET');

        fputcsv($file, $fields, $delimiter);


        $packageDispatchList = $this->getDataDispatch($idCompany, $dateStart,$dateEnd, $idTeam, $idDriver, $state, $routes,$type ='export');

       foreach($packageDispatchList as $packageDispatch)
        {

            if($packageDispatch->driver && $packageDispatch->driver->idTeam)
            {
                $team   = $packageDispatch->driver->nameTeam;
                $driver = $packageDispatch->driver->name .' '. $packageDispatch->driver->nameOfOwner;
            }
            else
            {
                $team   = $packageDispatch->driver ? $packageDispatch->driver->name : '';
                $driver = '';
            }

            $lineData = array(
                date('m-d-Y', strtotime($packageDispatch->Date_Dispatch)),
                date('H:i:s', strtotime($packageDispatch->Date_Dispatch)),
                $team,
                $driver,
                $packageDispatch->Reference_Number_1,
                $packageDispatch->Dropoff_Contact_Name,
                $packageDispatch->Dropoff_Contact_Phone_Number,
                $packageDispatch->Dropoff_Address_Line_1,
                $packageDispatch->Dropoff_City,
                $packageDispatch->Dropoff_Province,
                $packageDispatch->Dropoff_Postal_Code,
                $packageDispatch->Weight,
                $packageDispatch->Route,
                $packageDispatch->taskOnfleet,
            );

            fputcsv($file, $lineData, $delimiter);
        }

        fseek($file, 0);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '";');

        fpassthru($file);
    }

    public function Get($Reference_Number_1)
    {
        $packageInbound = PackageDispatch::find($Reference_Number_1);

        return ['package' => $packageInbound];
    }
}