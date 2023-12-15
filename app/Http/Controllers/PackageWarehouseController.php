<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use \App\Service\ServicePackageTerminal;

use App\Models\{ Configuration, PackageBlocked, PackageHistory, PackageInbound, PackageDispatch, PackageLost, PackageManifest, PackagePreDispatch, PackageReturn, PackageReturnCompany, PackageWarehouse,PackageLmCarrier, States, User, Cellar};

use Illuminate\Support\Facades\Validator;

use App\Http\Controllers\Api\PackageController;

use Barryvdh\DomPDF\Facade\PDF;

use Picqer\Barcode\BarcodeGeneratorPNG;

use DB;

use Session;

class PackageWarehouseController extends Controller
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
        return view('package.warehouse');
    }

    public function List($idCompany, $idValidator, $dateStart,$dateEnd, $route, $state, $idCellar)
    {

        $packageListWarehouse = $this->getDataWarehouse($idCompany, $idValidator, $dateStart,$dateEnd, $route, $state, $idCellar);

        $quantityWarehouse      = $packageListWarehouse->total();

        $listState  = PackageWarehouse::select('Dropoff_Province')
                                            ->groupBy('Dropoff_Province')
                                            ->get();

        $listStateValidate  = States::orderBy('name', 'asc')->get();                                    

        return ['packageList' => $packageListWarehouse, 'listState' => $listState, 'listStateValidate' => $listStateValidate, 'quantityWarehouse' => $quantityWarehouse];
    }

    private function getDataWarehouse($idCompany, $idValidator, $dateStart,$dateEnd, $route, $state, $idCellar, $type='list'){

        $dateStart = $dateStart .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);

        if(Auth::user()->role->name == 'Master')
        {
            $packageListWarehouse = PackageWarehouse::with('user');
        }
        else
        {
            $packageListWarehouse = PackageWarehouse::with('user')
                                                    ->where('idUser', Auth::user()->id);
        }

        $packageListWarehouse = $packageListWarehouse->where('status', 'Warehouse')
                                                    ->whereBetween('created_at', [$dateStart, $dateEnd]);

        if($idCompany != 0)
        {
            $packageListWarehouse = $packageListWarehouse->where('idCompany', $idCompany);
        }

        if($idCellar != 0)
        {
            $packageListWarehouse = $packageListWarehouse->where('idCellar', $idCellar);
        }
        
        if($idValidator)
        {
            $packageListWarehouse = $packageListWarehouse->where('idUser', $idValidator);
        }

        if($route != 'all')
        {
            $packageListWarehouse = $packageListWarehouse->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $packageListWarehouse = $packageListWarehouse->whereIn('Dropoff_Province', $states);
        }
        
        if($type == 'list')
        {
            $packageListWarehouse = $packageListWarehouse->orderBy('created_at', 'desc')
                                                        ->select(
                                                            'created_at',
                                                            'idUser',
                                                            'company',
                                                            'Reference_Number_1',
                                                            'Dropoff_Contact_Name',
                                                            'Dropoff_Contact_Phone_Number',
                                                            'Dropoff_Address_Line_1',
                                                            'Dropoff_City',
                                                            'Dropoff_Province',
                                                            'Dropoff_Postal_Code',
                                                            'Weight',
                                                            'Route',
                                                            'nameCellar'
                                                        )
                                                        ->paginate(50); 
        }
        else
        {
            $packageListWarehouse = $packageListWarehouse->orderBy('created_at', 'desc')->get();
        }

        return $packageListWarehouse;
    }

    public function Export($idCompany, $idValidator, $dateStart,$dateEnd, $route, $state, $idCellar, $typeExport)
    {
        $delimiter = ",";
        $filename  = $typeExport == 'download' ? "PACKAGES - WAREHOUSE " . date('Y-m-d H:i:s') . ".csv" : Auth::user()->id ."- PACKAGES - WAREHOUSE.csv";
        $file      = $typeExport == 'download' ? fopen('php://memory', 'w') : fopen(public_path($filename), 'w');


        //set column headers
        $fields = array('DATE', 'HOUR', 'COMPANY', 'VALIDATOR', 'PACKAGE ID', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'WEIGHT', 'ROUTE');

        fputcsv($file, $fields, $delimiter);

        $packageListWarehouse = $this->getDataWarehouse($idCompany, $idValidator, $dateStart,$dateEnd, $route, $state, $idCellar, $type='export');

        foreach($packageListWarehouse as $packageWarehouse)
        {
            $user = $packageWarehouse->user ? $packageWarehouse->user->name .' '. $packageWarehouse->user->nameOfOwner : '';

            $lineData = array(
                                date('m-d-Y', strtotime($packageWarehouse->created_at)),
                                date('H:i:s', strtotime($packageWarehouse->created_at)),
                                $packageWarehouse->company,
                                $user,
                                $packageWarehouse->Reference_Number_1,
                                $packageWarehouse->Dropoff_Contact_Name,
                                $packageWarehouse->Dropoff_Contact_Phone_Number,
                                $packageWarehouse->Dropoff_Address_Line_1,
                                $packageWarehouse->Dropoff_City,
                                $packageWarehouse->Dropoff_Province,
                                $packageWarehouse->Dropoff_Postal_Code,
                                $packageWarehouse->Weight,
                                $packageWarehouse->Route
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

            SendGeneralExport('Packages Warehouse', $filename);

            return ['stateAction' => true];
        }
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
        
        $servicePackageTerminal = new ServicePackageTerminal();
        $package                = $servicePackageTerminal->Get($request->get('Reference_Number_1'));

        if($package)
        {
            return ['stateAction' => 'packageTerminal'];
        }
        
        $packageLost = PackageLost::find($request->get('Reference_Number_1'));

        if($packageLost)
        {
            return ['stateAction' => 'validatedLost'];
        }

        $packageWarehouse = PackageWarehouse::find($request->get('Reference_Number_1'));
        $stateValidate    = $request->get('StateValidate');
        $stateValidate    = $stateValidate != '' ? explode(',', $stateValidate) : [];

        //VALIDATION OF PACKAGE IN WAREHOUSE AND UPDATE DATE CREATED
        if($packageWarehouse != null)
        {
            if($packageWarehouse->status == 'Warehouse')
            {
                if(count($stateValidate) > 0)
                {
                    if(!in_array($packageWarehouse->Dropoff_Province, $stateValidate))
                    {
                        return ['stateAction' => 'nonValidatedState', 'packageWarehouse' => $packageWarehouse];
                    }
                }
                
                /*$initDate = date('Y-m-d 00:00:00');
                $endDate  = date('Y-m-d 23:59:59');

                $countValidations = PackageHistory::where('Reference_Number_1', $request->get('Reference_Number_1'))
                                                ->whereBetween('created_at', [$initDate, $endDate])
                                                ->where('idUser', Auth::user()->id)
                                                ->where('status', 'Warehouse')
                                                ->get()
                                                ->count();

                if($countValidations >= 2)
                {
                    return ['stateAction' => 'countValidations', 'packageWarehouse' => $packageWarehouse];
                }*/

                if(date('Y-m-d', strtotime($packageWarehouse->created_at)) == date('Y-m-d'))
                {
                    return ['stateAction' => 'packageInWarehouse', 'packageWarehouse' => $packageWarehouse];
                }
            }

            try
            {
                DB::beginTransaction();

                // history warehouse
                $packageHistory = new PackageHistory();

                $packageHistory->id                           = uniqid();
                $packageHistory->Reference_Number_1           = $packageWarehouse->Reference_Number_1;
                $packageHistory->idCompany                    = $packageWarehouse->idCompany;
                $packageHistory->company                      = $packageWarehouse->company;
                $packageHistory->idStore                      = $packageWarehouse->idStore;
                $packageHistory->store                        = $packageWarehouse->store;
                $packageHistory->Dropoff_Contact_Name         = $packageWarehouse->Dropoff_Contact_Name;
                $packageHistory->Dropoff_Company              = $packageWarehouse->Dropoff_Company;
                $packageHistory->Dropoff_Contact_Phone_Number = $packageWarehouse->Dropoff_Contact_Phone_Number;
                $packageHistory->Dropoff_Contact_Email        = $packageWarehouse->Dropoff_Contact_Email;
                $packageHistory->Dropoff_Address_Line_1       = $packageWarehouse->Dropoff_Address_Line_1;
                $packageHistory->Dropoff_Address_Line_2       = $packageWarehouse->Dropoff_Address_Line_2;
                $packageHistory->Dropoff_City                 = $packageWarehouse->Dropoff_City;
                $packageHistory->Dropoff_Province             = $packageWarehouse->Dropoff_Province;
                $packageHistory->Dropoff_Postal_Code          = $packageWarehouse->Dropoff_Postal_Code;
                $packageHistory->Notes                        = $packageWarehouse->Notes;
                $packageHistory->Weight                       = $packageWarehouse->Weight;
                $packageHistory->Route                        = $packageWarehouse->Route;
                $packageHistory->idUser                       = Auth::user()->id;
                $packageHistory->Description                  = 'For: '. Auth::user()->name .' '. Auth::user()->nameOfOwner;
                $packageHistory->quantity                     = $packageWarehouse->quantity;
                $packageHistory->status                       = 'Warehouse';
                $packageHistory->created_at                   = date('Y-m-d H:i:s');
                $packageHistory->actualDate                   = date('Y-m-d H:i:s');
                $packageHistory->updated_at                   = date('Y-m-d H:i:s');
                
                $cellar = Cellar::find(Auth::user()->idCellar);
                
                if($cellar)
                {
                    $packageHistory->idCellar    = $cellar->id;
                    $packageHistory->nameCellar  = $cellar->name;
                    $packageHistory->stateCellar = $cellar->state;
                    $packageHistory->cityCellar  = $cellar->city;
                }

                $packageHistory->save();

                // update warehouse
                $packageWarehouse->status     = 'Warehouse';
                $packageWarehouse->idUser     = Auth::user()->id;
                $packageWarehouse->created_at = date('Y-m-d H:i:s');

                $packageWarehouse->save();

                $packageController = new PackageController();

                if($packageWarehouse->idCompany == 1)
                {
                    $packageController->SendStatusToInland($packageWarehouse, 'Warehouse', null, date('Y-m-d H:i:s'));
                }

                $packageHistory = PackageHistory::where('Reference_Number_1', $packageWarehouse->Reference_Number_1)
                                                ->where('sendToInland', 1)
                                                ->where('status', 'Manifest')
                                                ->first();

                if($packageHistory)
                {
                    $packageController->SendStatusToOtherCompany($packageWarehouse, 'Warehouse', null, date('Y-m-d H:i:s'));
                }

                DB::commit();

                return ['stateAction' => 'packageUpdateCreatedAt', 'packageWarehouse' => $packageWarehouse];
            }
            catch(Exception $e)
            {
                DB::rollback();

                return ['stateAction' => false];
            }
        }

        $packageInbound  = null;
        $packageDispatch = null;

        $packageManifest = PackageManifest::find($request->get('Reference_Number_1'));

        if($packageManifest == null)
        {
            $packageInbound = PackageInbound::find($request->get('Reference_Number_1'));
        }

        if($packageInbound == null)
        {
            $packageDispatch = PackageDispatch::where('status', 'Dispatch')
                                                ->where('Reference_Number_1', $request->get('Reference_Number_1'))
                                                ->first();
        }

        if($packageManifest || $packageInbound || $packageDispatch)
        {
            try
            {
                DB::beginTransaction();

                if($packageManifest)
                {
                    $package = $packageManifest;
                }
                elseif($packageInbound)
                {
                    $package = $packageInbound;
                }
                elseif($packageDispatch)
                {
                    $package = $packageDispatch;
                }

                if(count($stateValidate) > 0)
                {
                    if(!in_array($package->Dropoff_Province, $stateValidate))
                    {
                        return ['stateAction' => 'nonValidatedState', 'packageWarehouse' => $package];
                    }
                }

                if($packageManifest)
                {
                    $packageHistory = new PackageHistory();

                    $packageHistory->id                           = uniqid();
                    $packageHistory->Reference_Number_1           = $package->Reference_Number_1;
                    $packageHistory->idCompany                    = $package->idCompany;
                    $packageHistory->company                      = $package->company;
                    $packageHistory->idStore                      = $package->idStore;
                    $packageHistory->store                        = $package->store;
                    $packageHistory->TRUCK                        = $request->get('TRUCK') ? $request->get('TRUCK') : '';
                    $packageHistory->CLIENT                       = $request->get('CLIENT') ? $request->get('CLIENT') : '';
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
                    $packageHistory->Date_Inbound                 = date('Y-m-d H:s:i');
                    $packageHistory->Description                  = 'Inbound - for: '. Auth::user()->name .' '. Auth::user()->nameOfOwner;
                    $packageHistory->inbound                      = 1;
                    $packageHistory->quantity                     = $package->quantity;
                    $packageHistory->status                       = 'Inbound';
                    $packageHistory->actualDate                   = date('Y-m-d H:i:s');
                    $packageHistory->created_at                   = date('Y-m-d H:i:s');
                    $packageHistory->updated_at                   = date('Y-m-d H:i:s');

                    $packageHistory->save();

                    //data for INLAND
                    $packageController = new PackageController();
                    $packageController->SendStatusToInland($packageManifest, 'Inbound', null, date('Y-m-d H:i:s'));
                    //end data for inland

                    $packageHistory = PackageHistory::where('Reference_Number_1', $packageManifest->Reference_Number_1)
                                                ->where('sendToInland', 1)
                                                ->where('status', 'Manifest')
                                                ->first();

                    if($packageHistory)
                    {
                        $packageController->SendStatusToOtherCompany($packageManifest, 'Inbound', null, date('Y-m-d H:i:s'));
                    }
                }

                if($packageDispatch)
                {
                    $user = User::find($packageDispatch->idUserDispatch);

                    if($user && $user->nameTeam)
                    {
                        $description = 'Return - for: '. Auth::user()->name .' '. Auth::user()->nameOfOwner .' to '. $user->nameTeam .' / '. $user->name .' '. $user->nameOfOwner;
                    }
                    else
                    {
                        $description = 'Return - for: '. Auth::user()->name .' '. Auth::user()->nameOfOwner;
                    }

                    $idOnfleet     = '';
                    $taskOnfleet   = '';
                    $team          = '';
                    $workerName    = '';
                    $photoUrl      = '';
                    $statusOnfleet = '';
                    $onfleet       = '';

                    $team       = $user ? $user->nameTeam : '';
                    $workerName = $user ? $user->name .' '. $user->nameOfOwner : '';

                    $Date_Return         = date('Y-m-d H:i:s');
                    $Description_Return  = $request->get('Description_Return');
                    $Description_Onfleet = '';

                    $onfleet = $this->GetOnfleet($packageDispatch->idOnfleet);

                    if($onfleet)
                    {
                        $idOnfleet           = $packageDispatch->idOnfleet;
                        $taskOnfleet         = $packageDispatch->taskOnfleet;
                        $Description_Onfleet = $onfleet['completionDetails']['failureReason'] .': '. $onfleet['completionDetails']['failureNotes'];
                        $Date_Return         = date('Y-m-d H:i:s');

                        if($onfleet['state'] == 3)
                        {
                            $statusOnfleet = $onfleet['completionDetails']['success'] == true ? $onfleet['state'] .' (error success)' : $onfleet['state'];
                            $Date_Return   = date('Y-m-d H:i:s', $onfleet['completionDetails']['time'] / 1000);

                            if(count($onfleet['completionDetails']['photoUploadIds']) > 0)
                            {
                                $photoUrl = implode(",", $onfleet['completionDetails']['photoUploadIds']);
                            }
                            else
                            {
                                $photoUrl   = $onfleet['completionDetails']['photoUploadId'];
                            }
                        }
                        elseif($onfleet['state'] == 1)
                        {
                            $statusOnfleet = 1;
                        }
                    }

                    $packageReturn = new PackageReturn();

                    $packageReturn->id                           = uniqid();
                    $packageReturn->Reference_Number_1           = $packageDispatch->Reference_Number_1;
                    $packageReturn->idCompany                    = $packageDispatch->idCompany;
                    $packageReturn->company                      = $packageDispatch->company;
                    $packageReturn->idStore                      = $packageDispatch->idStore;
                    $packageReturn->store                        = $packageDispatch->store;
                    $packageReturn->Dropoff_Contact_Name         = $packageDispatch->Dropoff_Contact_Name;
                    $packageReturn->Dropoff_Company              = $packageDispatch->Dropoff_Company;
                    $packageReturn->Dropoff_Contact_Phone_Number = $packageDispatch->Dropoff_Contact_Phone_Number;
                    $packageReturn->Dropoff_Contact_Email        = $packageDispatch->Dropoff_Contact_Email;
                    $packageReturn->Dropoff_Address_Line_1       = $packageDispatch->Dropoff_Address_Line_1;
                    $packageReturn->Dropoff_Address_Line_2       = $packageDispatch->Dropoff_Address_Line_2;
                    $packageReturn->Dropoff_City                 = $packageDispatch->Dropoff_City;
                    $packageReturn->Dropoff_Province             = $packageDispatch->Dropoff_Province;
                    $packageReturn->Dropoff_Postal_Code          = $packageDispatch->Dropoff_Postal_Code;
                    $packageReturn->Notes                        = $packageDispatch->Notes;
                    $packageReturn->Weight                       = $packageDispatch->Weight;
                    $packageReturn->Route                        = $packageDispatch->Route;
                    $packageReturn->idUser                       = Auth::user()->id;
                    $packageReturn->idTeam                       = $packageDispatch->idTeam;
                    $packageReturn->idUserReturn                 = $packageDispatch->idUserDispatch;
                    $packageReturn->Date_Return                  = $Date_Return;
                    $packageReturn->Description_Return           = $Description_Return;
                    $packageReturn->Description_Onfleet          = $Description_Onfleet;
                    $packageReturn->idOnfleet                    = $idOnfleet;
                    $packageReturn->taskOnfleet                  = $taskOnfleet;
                    $packageReturn->team                         = $team;
                    $packageReturn->workerName                   = $workerName;
                    $packageReturn->photoUrl                     = $photoUrl;
                    $packageReturn->statusOnfleet                = $statusOnfleet;
                    $packageReturn->quantity                     = $packageDispatch->quantity;
                    $packageReturn->status                       = 'Return';

                    $cellar = Cellar::find(Auth::user()->idCellar);

                    if($cellar)
                    {
                        $packageReturn->idCellar    = $cellar->id;
                        $packageReturn->nameCellar  = $cellar->name;
                        $packageReturn->stateCellar = $cellar->state;
                        $packageReturn->cityCellar  = $cellar->city;
                    }

                    $packageReturn->save();

                    $packageHistory = new PackageHistory();

                    $packageHistory->id                           = uniqid();
                    $packageHistory->Reference_Number_1           = $packageDispatch->Reference_Number_1;
                    $packageHistory->idCompany                    = $packageDispatch->idCompany;
                    $packageHistory->company                      = $packageDispatch->company;
                    $packageHistory->idStore                      = $packageDispatch->idStore;
                    $packageHistory->store                        = $packageDispatch->store;
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
                    $packageHistory->idTeam                       = $packageDispatch->idTeam;
                    $packageHistory->idUserReturn                 = $packageDispatch->idUserDispatch;
                    $packageHistory->idUser                       = Auth::user()->id;
                    $packageHistory->idUserInbound                = Auth::user()->id;
                    $packageHistory->Date_Inbound                 = date('Y-m-d H:s:i');
                    $packageHistory->Description                  = 'Return - for: '. Auth::user()->name .' '. Auth::user()->nameOfOwner;
                    $packageHistory->Description_Return           = $Description_Return;
                    $packageHistory->Description_Onfleet          = $Description_Onfleet;
                    $packageHistory->inbound                      = 1;
                    $packageHistory->quantity                     = $packageDispatch->quantity;
                    $packageHistory->status                       = 'Return';
                    $packageHistory->actualDate                   = date('Y-m-d H:i:s');
                    $packageHistory->created_at                   = date('Y-m-d H:i:s');
                    $packageHistory->updated_at                   = date('Y-m-d H:i:s');

                    if($cellar)
                    {
                        $packageHistory->idCellar    = $cellar->id;
                        $packageHistory->nameCellar  = $cellar->name;
                        $packageHistory->stateCellar = $cellar->state;
                        $packageHistory->cityCellar  = $cellar->city;
                    }

                    $packageHistory->save();

                    if($onfleet)
                    {
                        if($onfleet['state'] == 1)
                        {
                            $statusOnfleet = 1;

                            $onfleet = $this->DeleteOnfleet($packageDispatch->idOnfleet);
                        }
                    }
                }

                $packageWarehouse = new PackageWarehouse();

                $packageWarehouse->Reference_Number_1           = $package->Reference_Number_1;
                $packageWarehouse->idCompany                    = $package->idCompany;
                $packageWarehouse->company                      = $package->company;
                $packageWarehouse->idStore                      = $package->idStore;
                $packageWarehouse->store                        = $package->store;
                $packageWarehouse->Dropoff_Contact_Name         = $package->Dropoff_Contact_Name;
                $packageWarehouse->Dropoff_Company              = $package->Dropoff_Company;
                $packageWarehouse->Dropoff_Contact_Phone_Number = $package->Dropoff_Contact_Phone_Number;
                $packageWarehouse->Dropoff_Contact_Email        = $package->Dropoff_Contact_Email;
                $packageWarehouse->Dropoff_Address_Line_1       = $package->Dropoff_Address_Line_1;
                $packageWarehouse->Dropoff_Address_Line_2       = $package->Dropoff_Address_Line_2;
                $packageWarehouse->Dropoff_City                 = $package->Dropoff_City;
                $packageWarehouse->Dropoff_Province             = $package->Dropoff_Province;
                $packageWarehouse->Dropoff_Postal_Code          = $package->Dropoff_Postal_Code;
                $packageWarehouse->Notes                        = $package->Notes;
                $packageWarehouse->Weight                       = $package->Weight;
                $packageWarehouse->Route                        = $package->Route;
                $packageWarehouse->idUser                       = Auth::user()->id;
                $packageWarehouse->quantity                     = $package->quantity;
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
                $packageHistory->status                       = 'Warehouse';
                $packageHistory->actualDate                   = date('Y-m-d H:i:s');
                $packageHistory->created_at                   = date('Y-m-d H:i:s');
                $packageHistory->updated_at                   = date('Y-m-d H:i:s');

                if($cellar)
                {
                    $packageHistory->idCellar    = $cellar->id;
                    $packageHistory->nameCellar  = $cellar->name;
                    $packageHistory->stateCellar = $cellar->state;
                    $packageHistory->cityCellar  = $cellar->city;
                }

                $packageHistory->save();

                $package->delete();

                $packageController = new PackageController();

                if($packageWarehouse->idCompany == 1)
                {
                    $packageController->SendStatusToInland($packageWarehouse, 'Warehouse', null, date('Y-m-d H:i:s'));
                }
                
                $packageHistory = PackageHistory::where('Reference_Number_1', $packageWarehouse->Reference_Number_1)
                                                ->where('sendToInland', 1)
                                                ->where('status', 'Manifest')
                                                ->first();

                if($packageHistory)
                {
                    $packageController->SendStatusToOtherCompany($packageWarehouse, 'Warehouse', null, date('Y-m-d H:i:s'));
                }

                DB::commit();

                return ['stateAction' => true, 'packageWarehouse' => $packageWarehouse];
            }
            catch(Exception $e)
            {
                DB::rollback();

                return ['stateAction' => true];
            }
        }
        else
        {
            $packageReturnCompany = PackageReturnCompany::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();

            if($packageReturnCompany)
            {
                return ['stateAction' => 'validatedReturnCompany', 'packageInbound' => $packageReturnCompany];
            }
        }

        return ['stateAction' => 'notExists'];
    }

    public function Import(Request $request)
    {
        $file = $request->file('file');

        $file->move(public_path() .'/file-import', 'warehouse-import.csv');

        $handle = fopen(public_path('file-import/warehouse-import.csv'), "r");

        $line = 0;

        try
        {
            DB::beginTransaction();

            while (($raw_string = fgets($handle)) !== false)
            {
                if($line > 0)
                {
                    $row = str_getcsv($raw_string);

                    $request['Reference_Number_1'] = $row[0];

                    $this->Insert($request);
                }

                $line++;
            }

            DB::commit();

            return ['stateAction' => true];
        }
        catch(Exception $e)
        {
            DB::rollback();
        }
    }

    public function ListInDelivery()
    {
        $listPackageLmCarrier = PackageLmCarrier::where('status', 'LM Carrier')->get();

        $packagesInDelivery = [];

        foreach($listPackageLmCarrier as $packageLmCarrier)
        {
            $packageHistory = PackageHistory::where('Reference_Number_1',$packageLmCarrier->Reference_Number_1);

            if($packageHistory->status== 'Delivery')
            {
                array_push($packagesInDelivery, $packageHistory->Reference_Number_1);
            }
        }

        return $packagesInDelivery;
    }

    public function DeleteInDelivery()
    {
        $packagesListInDelivery = $this->ListInDelivery();

        try
        {
            DB::beginTransaction();

            foreach($packagesListInDelivery as $Reference_Number_1)
            {
                $packageLmCarrier = PackageLmCarrier::where('status', 'LM Carrier')
                                                    ->where('Reference_Number_1', $Reference_Number_1)
                                                    ->first();

                if($packageLmCarrier)
                {
                    $packageLmCarrier->delete();
                }
            }

            DB::commit();

            return ['message' => "packages deleted"];
        }
        catch(Exception $e)
        {
            DB::rollback();
        }
    }

    public function GetOnfleet($idOnfleet)
    {
        $curl = curl_init("https://onfleet.com/api/v2/tasks/". $idOnfleet);

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);

        $output = json_decode(curl_exec($curl), 1);

        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if($http_status == 200)
        {
            return $output;
        }
        else
        {
            return false;
        }
    }

    public function DeleteOnfleet($idOnfleet)
    {
        $curl = curl_init("https://onfleet.com/api/v2/tasks/". $idOnfleet);

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);

        $output = json_decode(curl_exec($curl), 1);

        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if($http_status == 200)
        {
            return true;
        }
        else
        {
            return false;
        }
    }
}
