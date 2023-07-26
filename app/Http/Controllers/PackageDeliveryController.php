<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{
        ChargeCompanyDetail, Configuration, PackageHistory, PackageDelivery, PackageDispatch, 
        PackageFailed, PackageInbound, PackageManifest, PackageWarehouse, 
        PackagePreDispatch, PackageNeedMoreInformation, PackageReturnCompany, TeamRoute, User};

use App\Http\Controllers\{ PackageDispatchController, PackagePriceCompanyTeamController };

use App\Http\Controllers\Api\PackageController;

use Illuminate\Support\Facades\Validator;

use Illuminate\Support\Facades\Auth;

use DB;
use Log;
use Session;

class PackageDeliveryController extends Controller
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
        return view('package.delivery');
    }

    public function List(Request $request)
    {
        $packageListDelivery = PackageDispatch::with(['package', 'driver.role', 'driver', 'package_histories'])
                                                    ->where('status', 'Delivery')
                                                    ->orderBy('created_at', 'desc')
                                                    ->paginate(25);

        $Reference_Number_1s = [];

        foreach($packageListDelivery as $delivery)
        {
            array_push($Reference_Number_1s, $delivery->Reference_Number_1);
        }

        $listDeliveries = PackageDelivery::whereIn('taskDetails', $Reference_Number_1s)->get();

        $quantityDelivery = $packageListDelivery->total();

        return ['packageListDelivery' => $packageListDelivery, 'listDeliveries' => $listDeliveries, 'quantityDelivery' => $quantityDelivery];
    }

    public function Insert(Request $request)
    {
        try
        {
            DB::beginTransaction();

            $Reference_Number_1 = $request->get('Reference_Number_1');

            $package = PackageManifest::find($Reference_Number_1);
            $package = $package != null ? $package : PackageInbound::find($Reference_Number_1);
            $package = $package != null ? $package : PackageWarehouse::find($Reference_Number_1);
            $package = $package != null ? $package : PackageNeedMoreInformation::find($Reference_Number_1);
            $package = $package != null ? $package : PackageDispatch::find($Reference_Number_1);
            $package = $package != null ? $package : PackagePreDispatch::find($Reference_Number_1);
            $package = $package != null ? $package : PackageFailed::find($Reference_Number_1);
            $package = $package != null ? $package : PackageReturnCompany::find($Reference_Number_1);

            if(!$package)
            {
                return ['stateAction' => 'notExists'];
            }

            $actualDate    = date('Y-m-d H:i:s');
            $created_at    = $request->get('DateDelivery') .' '. $request->get('HourDelivery');
            $arrivalLonLat = $request->get('arrivalLonLat');
            $team          = User::find($request->get('idTeam'));

            if($team)
            {
                $idTeam = $team->id;
            }
            else
            {
                $idTeam = isset($package->idTeam) ? $package->idTeam : 0;
            }

            $packageHistory = PackageHistory::where('Reference_Number_1', $Reference_Number_1)
                                                            ->orderBy('actualDate', 'asc')
                                                            ->get();

            if(count($packageHistory) > 0)
            {
                $packageHistory = $packageHistory->last();

                if($packageHistory->status == 'Delivery')
                {
                    $packageHistory = PackageHistory::find($packageHistory->id);
                }
                else
                {
                    $packageHistory = new PackageHistory();
                    $packageHistory->id = uniqid();
                }
            }
            else
            {
                $packageHistory = new PackageHistory();
                $packageHistory->id = uniqid();
            }

            $packageHistory->Reference_Number_1           = $package->Reference_Number_1;
            $packageHistory->idCompany                    = $package->idCompany;
            $packageHistory->company                      = $package->company;
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
            $packageHistory->idTeam                       = $idTeam;
            $packageHistory->idUserDispatch               = isset($package->idUserDispatch) ? $package->idUserDispatch : 0;
            $packageHistory->idUser                       = Auth::user()->id;
            $packageHistory->idUserDelivery               = isset($package->idUserDispatch) ? $package->idUserDispatch : 0;
            $packageHistory->Date_Delivery                = $created_at;
            $packageHistory->Description                  = 'For: '. Auth::user()->name .' (Register Forced Delivery)';
            $packageHistory->status                       = 'Delivery';
            $packageHistory->actualDate                   = $actualDate;
            $packageHistory->created_at                   = $created_at;
            $packageHistory->updated_at                   = $created_at;
            $packageHistory->save();

            $filePhoto1 = '';
            $filePhoto2 = '';

            if($request->hasFile('filePhoto1'))
            {
                $filePhoto1 = $Reference_Number_1 .'-photo1.'. $request->file('filePhoto1')->getClientOriginalExtension();

                $request->file('filePhoto1')->move(public_path('img/deliveries'), $filePhoto1);
            }

            if($request->hasFile('filePhoto2'))
            {
                $filePhoto2 = $Reference_Number_1 .'-photo2.'. $request->file('filePhoto2')->getClientOriginalExtension();
                
                $request->file('filePhoto2')->move(public_path('img/deliveries'), $filePhoto2);
            }

            if($package->status != 'Dispatch' && $package->status != 'Delivery' && $package->status != 'Delete')
            {
                $packageDispatch = new PackageDispatch();
                $packageDispatch->Reference_Number_1           = $package->Reference_Number_1;
                $packageDispatch->idCompany                    = $package->idCompany;
                $packageDispatch->company                      = $package->company;
                $packageDispatch->idStore                      = $package->idStore;
                $packageDispatch->store                        = $package->store;
                $packageDispatch->Dropoff_Contact_Name         = $package->Dropoff_Contact_Name;
                $packageDispatch->Dropoff_Company              = $package->Dropoff_Company;
                $packageDispatch->Dropoff_Contact_Phone_Number = $package->Dropoff_Contact_Phone_Number;
                $packageDispatch->Dropoff_Contact_Email        = $package->Dropoff_Contact_Email;
                $packageDispatch->Dropoff_Address_Line_1       = $package->Dropoff_Address_Line_1;
                $packageDispatch->Dropoff_Address_Line_2       = $package->Dropoff_Address_Line_2;
                $packageDispatch->Dropoff_City                 = $package->Dropoff_City;
                $packageDispatch->Dropoff_Province             = $package->Dropoff_Province;
                $packageDispatch->Dropoff_Postal_Code          = $package->Dropoff_Postal_Code;
                $packageDispatch->Notes                        = $package->Notes;
                $packageDispatch->Weight                       = $package->Weight;
                $packageDispatch->Route                        = $package->Route;
                $packageDispatch->arrivalLonLat                = $arrivalLonLat;
                $packageDispatch->idUser                       = Auth::user()->id;
                $packageDispatch->idTeam                       = $idTeam;
                $packageDispatch->Date_Dispatch                = $created_at;
                $packageDispatch->Date_Delivery                = $created_at;
                $packageDispatch->quantity                     = 0;
                $packageDispatch->filePhoto1                   = $filePhoto1;
                $packageDispatch->filePhoto2                   = $filePhoto2;
                $packageDispatch->send_csv                     = 1;
                $packageDispatch->status                       = 'Delivery';
                $packageDispatch->created_at                   = $actualDate;
                $packageDispatch->updated_at                   = $actualDate;

                $packageDispatch->save();

                $package->delete();
            }
            else if($package->status == 'Dispatch' || $package->status == 'Delivery' || $package->status == 'Delete')
            {
                $package->idTeam        = $idTeam;
                $package->arrivalLonLat = $arrivalLonLat;
                $package->Date_Delivery = $created_at;
                $package->filePhoto1    = $filePhoto1;
                $package->filePhoto2    = $filePhoto2;
                $package->send_csv      = 1;
                $package->status        = 'Delivery';
                $package->save();
            }

            DB::commit();

            return ['stateAction' => true];
        }
        catch (Exception $e)
        {
            DB::rollback();

            return ['stateAction' => true];
        }
    }

    public function IndexForCheck()
    {
        return view('package.deliverycheck');
    }

    public function ListForCheck($dateInit, $dateEnd, $idTeam, $idDriver, $route, $state)
    {
        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);

        $listAll = PackageDispatch::with(['driver.role', 'driver', 'package_histories'])
                                ->whereBetween('Date_Delivery', [$dateInit, $dateEnd])
                                ->where('status', 'Delivery');

        if(Auth::user()->role->name == 'Team')
        {
            $idsUser = User::where('idTeam', $idTeam)->get('id');

            $listAll = $listAll->whereIn('idUserDispatch', $idsUser);
        }
        elseif(Auth::user()->role->name == 'Driver')
        {
            $idsUser = User::where('idTeam', $idTeam)->get('id');

            $listAll = $listAll->whereIn('idUserDispatch', $idsUser)->orWhere('idUserDispatch', $idTeam);
        }
        else
        {
            if($idTeam && $idDriver)
            {
                $listAll = $listAll->where('idUserDispatch', $idDriver);
            }
            elseif($idTeam)
            {
                $idsUser = User::where('idTeam', $idTeam)->orWhere('id', $idTeam)->get('id');

                $listAll = $listAll->whereIn('idUserDispatch', $idsUser);
            }

            $listAll = $listAll->orderBy('Date_Delivery', 'desc');
        }

        if($route != 'all')
        {
            $listAll = $listAll->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $listAll = $listAll->whereIn('Dropoff_Province', $states);
        }

        $listAll = $listAll->with(['team', 'driver'])
                            ->orderBy('Date_Delivery', 'desc')
                            ->paginate(50);

        $Reference_Number_1s = [];

        foreach($listAll as $delivery)
        {
            array_push($Reference_Number_1s, $delivery->Reference_Number_1);
        }

        $listDeliveries = PackageDelivery::whereIn('taskDetails', $Reference_Number_1s)
                                        ->orderBy('updated_at', 'desc')
                                        ->get();

        $roleUser = Auth::user()->role->name;

        $listState = PackageHistory::select('Dropoff_Province')
                                    ->where('dispatch', 1)
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        return ['reportList' => $listAll, 'listDeliveries' => $listDeliveries, 'listState' => $listState, 'roleUser' => $roleUser];
    }

    public function InsertForCheck(Request $request)
    {
        $packageDelivery = PackageDispatch::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();


        $packageDelivery->idUserCheckPayment = $packageDelivery->checkPayment ? 0 : Auth::user()->id;
        $packageDelivery->checkPayment       = $request->get('checkPayment');


        $packageDelivery->save();

        return ['stateAction' => true];
    }

    public function ConfirmationCheck()
    {
        $listPackageDelivery = PackageDispatch::where('checkPayment', 1)
                                            ->where('confirmCheckPayment', 0)
                                            ->where('idUserCheckPayment', Auth::user()->id)
                                            ->update(['confirmCheckPayment' => 1]);

        return ['stateAction' => true];
    }

    public function IndexFinance()
    {
        return view('package.deliveryfinance');
    }

    public function ListFinance($dateInit, $dateEnd, $idTeam, $idDriver, $checked, $route, $state)
    {
        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);

        $listAll = PackageDispatch::with(['driver.role', 'driver', 'package_histories'])
                                ->whereBetween('Date_Delivery', [$dateInit, $dateEnd])
                                ->where('checkPayment', '!=', null)
                                ->where('status', 'Delivery');

        if($checked == 1)
        {
            $listAll = $listAll->where('checkPayment', 1);
        }
        elseif($checked != 'all')
        {
            $listAll = $listAll->where('checkPayment', 0);
        }

        if(Auth::user()->role->name == 'Team')
        {
            $idsUser = User::where('idTeam', $idTeam)->get('id');

            $listAll = $listAll->whereIn('idUserDispatch', $idsUser);
        }
        elseif(Auth::user()->role->name == 'Driver')
        {
            $idsUser = User::where('idTeam', $idTeam)->get('id');

            $listAll = $listAll->whereIn('idUserDispatch', $idsUser)->orWhere('idUserDispatch', $idTeam);
        }
        else
        {
            if($idTeam && $idDriver)
            {
                $listAll = $listAll->where('idUserDispatch', $idDriver);
            }
            elseif($idTeam)
            {
                $idsUser = User::where('idTeam', $idTeam)->orWhere('id', $idTeam)->get('id');

                $listAll = $listAll->whereIn('idUserDispatch', $idsUser);
            }

            $listAll = $listAll->orderBy('Date_Delivery', 'desc');
        }


        /*if($route != 'all')
        {
            $listAll = $listAll->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $listAll = $listAll->whereIn('Dropoff_Province', $states);
        }*/

        $listAll = $listAll->with(['team', 'driver'])
                            ->orderBy('Date_Delivery', 'desc')
                            ->paginate(50);

        $Reference_Number_1s = [];

        foreach($listAll as $delivery)
        {
            array_push($Reference_Number_1s, $delivery->Reference_Number_1);
        }

        $listDeliveries = PackageDelivery::whereIn('taskDetails', $Reference_Number_1s)
                                        ->orderBy('updated_at', 'desc')
                                        ->get();

        $roleUser = Auth::user()->role->name;

        $listState = PackageHistory::select('Dropoff_Province')
                                    ->where('dispatch', 1)
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        return ['reportList' => $listAll, 'listDeliveries' => $listDeliveries, 'listState' => $listState, 'roleUser' => $roleUser];
    }

    public function ListInvoiced()
    {
        $dateInit = '2023-02-01 00:00:00';
        $dateEnd  = '2023-02-28 23:59:59';

        $listAll = PackageDispatch::whereBetween('Date_Delivery', [$dateInit, $dateEnd])->get('Reference_Number_1');

        $notInvoice = [];
        $invoice    = [];

        echo $dateInit .' => '. $dateEnd .'<br>';

        foreach($listAll as $packageDelivery)
        {
            $chargeCompanyDetail = ChargeCompanyDetail::find($packageDelivery->Reference_Number_1);

            if($chargeCompanyDetail)
            {
                array_push($invoice, $packageDelivery->Reference_Number_1);
            }
            else
            {
                echo $packageDelivery->Reference_Number_1 .'<br>';

                array_push($notInvoice, $packageDelivery->Reference_Number_1);
            }
        }

        //dd($notInvoice);
    }

    public function ImportPhoto(Request $request)
    {
        $file = $request->file('file');

        $file->move(public_path() .'/file-import', 'delivery-photo.csv');

        $handle = fopen(public_path('file-import/delivery-photo.csv'), "r");

        $lineNumber = 1;
        $countSave  = 0;

        try
        {
            Log::info("================================");
            Log::info("================================");
            Log::info("===== START - FILE IMPORT DELIVERY - PHOTO");

            DB::beginTransaction();

            while (($raw_string = fgets($handle)) !== false)
            {
                $row = str_getcsv($raw_string);

                if(isset($row[0]))
                {
                    $contador        = 0;
                    $packageManifest = PackageManifest::find($row[0]);
                    $packageAux      = null;

                    if($packageManifest)
                    {
                        $packageAux = $packageManifest;

                        $contador++;
                    }

                    $packageInbound = PackageInbound::find($row[0]);

                    if($packageInbound)
                    {
                        $packageAux = $packageInbound;

                        $contador++;
                    }

                    $packageWarehouse = PackageWarehouse::find($row[0]);

                    if($packageWarehouse)
                    {
                        $packageAux = $packageWarehouse;

                        $contador++;
                    }

                    $packageFailed = PackageFailed::find($row[0]);

                    if($packageFailed)
                    {
                        $packageAux = $packageFailed;

                        $contador++;
                    }

                    $packageDispatch = PackageDispatch::find($row[0]);

                    if($packageDispatch)
                    {
                        $packageAux = $packageDispatch;

                        $contador++;
                    }

                    $packageReturnCompany = PackageReturnCompany::find($row[0]);

                    if($packageReturnCompany)
                    {
                        $packageAux = $packageReturnCompany;

                        $contador++;
                    }

                    $packageDispatch = $packageAux;

                    if($contador == 1)
                    {
                        $photoUrls = $row[1] != '' ? explode('https://', $row[1]) : explode('https://', 'https://');

                        if(count($photoUrls) == 2 && $row[1] != '')
                        {
                            $photoUrl = explode('/', $photoUrls[1])[1];
                        }
                        else if(count($photoUrls) > 2 && $row[1] != '')
                        {
                            $photoUrl1 = explode('/', $photoUrls[1])[1];
                            $photoUrl2 = explode('/', $photoUrls[2])[1];

                            $photoUrl = $photoUrl1 .','. $photoUrl2;
                        }
                        else
                        {
                            $photoUrl = '';
                        }

                        $description   = '';
                        $idTeam        = 0;
                        $arrivalLonLat = $row[3];
                        $actualDate    = date('Y-m-d H:i:s');
                        $created_at    = $row[6] == '' ? date('Y-m-d H:i:s', strtotime($row[2])) : date('Y-m-d H:i:s', strtotime($row[2] .' '. $row[6]));

                        if(isset($row[4]) && $row[4] != '')
                        {
                            $user   = User::find($row[4]);
                            $idTeam = $row[4];

                            if($user)
                            {
                                $description = 'For: '. $user->name .' (Import Report Delivery)';
                            }
                        }

                        $description = $description ? $description : 'For: Import Report Delivery (Photo)';

                        if($packageDispatch)
                        {
                            $packageHistory = PackageHistory::where('Reference_Number_1', $packageDispatch->Reference_Number_1)
                                                            ->orderBy('actualDate', 'asc')
                                                            ->get();

                            if(count($packageHistory) > 0)
                            {
                                $packageHistory = $packageHistory->last();

                                if($packageHistory->status == 'Delivery')
                                {
                                    $packageHistory = PackageHistory::find($packageHistory->id);
                                }
                                else
                                {
                                    $packageHistory = new PackageHistory();
                                    $packageHistory->id = uniqid();
                                }
                            }
                            else
                            {
                                $packageHistory = new PackageHistory();
                                $packageHistory->id = uniqid();
                            }
                            
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
                            $packageHistory->idTeam                       = $idTeam;
                            $packageHistory->idUserDispatch               = $packageDispatch->status == 'Dispatch' ? $packageDispatch->idUserDispatch : 0;
                            $packageHistory->idUser                       = Auth::user()->id;
                            $packageHistory->idUserDelivery               = $packageDispatch->status == 'Dispatch' ? $packageDispatch->idUserDispatch : 0;
                            $packageHistory->Date_Delivery                = $created_at;
                            $packageHistory->Description                  = $description;
                            $packageHistory->status                       = 'Delivery';
                            $packageHistory->actualDate                   = $actualDate;
                            $packageHistory->created_at                   = $created_at;
                            $packageHistory->updated_at                   = $created_at;

                            $packageHistory->save();

                            if($packageDispatch->status == 'Dispatch' || $packageDispatch->status == 'Delete' || $packageDispatch->status == 'Delivery')
                            {
                                $packageDispatch->photoUrl           = $photoUrl;
                                $packageDispatch->Date_Delivery      = $created_at;
                                $packageDispatch->arrivalLonLat      = $arrivalLonLat;
                                $packageDispatch->send_csv           = 1;
                                $packageDispatch->status             = 'Delivery';
                                $packageDispatch->updated_at         = $created_at;

                                if($idTeam)
                                {
                                    $packageDispatch->idTeam = $idTeam;
                                }

                                $packageDispatch->save();
                            }
                            else if($packageDispatch->status != 'Dispatch' && $packageDispatch->status != 'Delete' && $packageDispatch->status != 'Delivery')
                            {
                                $packageDis = new PackageDispatch();

                                $packageDis->Reference_Number_1           = $packageDispatch->Reference_Number_1;
                                $packageDis->idCompany                    = $packageDispatch->idCompany;
                                $packageDis->company                      = $packageDispatch->company;
                                $packageDis->idStore                      = $packageDispatch->idStore;
                                $packageDis->store                        = $packageDispatch->store;
                                $packageDis->Dropoff_Contact_Name         = $packageDispatch->Dropoff_Contact_Name;
                                $packageDis->Dropoff_Company              = $packageDispatch->Dropoff_Company;
                                $packageDis->Dropoff_Contact_Phone_Number = $packageDispatch->Dropoff_Contact_Phone_Number;
                                $packageDis->Dropoff_Contact_Email        = $packageDispatch->Dropoff_Contact_Email;
                                $packageDis->Dropoff_Address_Line_1       = $packageDispatch->Dropoff_Address_Line_1;
                                $packageDis->Dropoff_Address_Line_2       = $packageDispatch->Dropoff_Address_Line_2;
                                $packageDis->Dropoff_City                 = $packageDispatch->Dropoff_City;
                                $packageDis->Dropoff_Province             = $packageDispatch->Dropoff_Province;
                                $packageDis->Dropoff_Postal_Code          = $packageDispatch->Dropoff_Postal_Code;
                                $packageDis->Notes                        = $packageDispatch->Notes;
                                $packageDis->Weight                       = $packageDispatch->Weight;
                                $packageDis->Route                        = $packageDispatch->Route;
                                $packageDis->idUser                       = Auth::user()->id;

                                if($idTeam)
                                {
                                    $packageDis->idTeam = $idTeam;
                                }

                                $packageDis->idUserDispatch               = 0;
                                $packageDis->Date_Dispatch                = $created_at;
                                $packageDis->Date_Delivery                = $created_at;
                                $packageDis->quantity                     = 0;
                                $packageDis->pricePaymentCompany          = 0;
                                $packageDis->pricePaymentTeam             = 0;
                                $packageDis->idPaymentTeam                = '';
                                $packageDis->photoUrl                     = $photoUrl;
                                $packageDis->arrivalLonLat                = $arrivalLonLat;
                                $packageDis->send_csv                     = 1;
                                $packageDis->status                       = 'Delivery';
                                $packageDis->created_at                   = $created_at;
                                $packageDis->updated_at                   = $created_at;

                                $packageDis->save();

                                $packageDispatch->delete();
                            }

                            if(isset($row[5]) && $row[5] == 'YES')
                            {
                                //data for INLAND
                                $packageController = new PackageController();
                                $packageController->SendStatusToInland($packageDispatch, 'Delivery', explode(',', $photoUrl), $created_at);
                                //end data for inland
                            }

                            if($packageDispatch->idCompany == 10 || $packageDispatch->idCompany == 11)
                            {
                                $packageDispatch['Date_Delivery'] = $created_at;

                                //create or update price company team
                                $packagePriceCompanyTeamController = new PackagePriceCompanyTeamController();
                                $packagePriceCompanyTeamController->Insert($packageDispatch, 'old');
                            }
                        }
                    }
                    elseif($contador > 1)
                    {
                        Log::info("================================");
                        Log::info("===== PACKAGE TWO STATUS - Reference_Number_1:". $row[0]);
                    }
                }
            }

            fclose($handle);

            DB::commit();

            Log::info("===== END - FILE IMPORT DELIVERY - PHOTO");
            Log::info("================================");
            Log::info("================================");

            return ['stateAction' => true];
        }
        catch(Exception $e)
        {
            DB::rollback();

            return ['stateAction' => false];
        }
    }

    public function CreateDeliveryForImport($packageDispatch, $row)
    {
        $packageDispatchAux = PackageDispatch::find($packageDispatch->Reference_Number_1);

        if(isset($row[4]) && $row[4] != '')
        {
            $description = 'For: Brooks Courier (Import Report Delivery)';
        }
        else
        {
            $description = 'For: Import Report Delivery (Photo)';
        }

        $photoUrls = $row[1] != '' ? explode('https://', $row[1]) : 'https://';

        if(count($photoUrls) == 2)
        {
            $photoUrl = explode('/', $photoUrls[1])[1];
        }
        else if(count($photoUrls) > 2)
        {
            $photoUrl1 = explode('/', $photoUrls[1])[1];
            $photoUrl2 = explode('/', $photoUrls[2])[1];

            $photoUrl = $photoUrl1 .','. $photoUrl2;
        }
        else
        {
            $photoUrl = '';
        }

        $arrivalLonLat = $row[3];
        $created_at    = date('Y-m-d H:i:s', strtotime($row[2]));

        if($packageDispatchAux == null && $packageDispatch->status != 'Delivery')
        {
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
            $packageHistory->idTeam                       = $packageDispatch->idTeam;
            $packageHistory->idUserDispatch               = $packageDispatch->idUserDispatch;
            $packageHistory->idUser                       = $packageDispatch->idUser;
            $packageHistory->idUserDelivery               = $packageDispatch->idUserDispatch;
            $packageHistory->Date_Delivery                = $created_at;
            $packageHistory->Description                  = $description;
            $packageHistory->status                       = 'Delivery';
            $packageHistory->actualDate                   = $created_at;
            $packageHistory->created_at                   = $created_at;
            $packageHistory->updated_at                   = $created_at;

            $packageHistory->save();

            if($packageDispatch->status == 'Dispatch' || $packageDispatch->status == 'Delete')
            {
                $packageDispatch->taskDetails        = $packageDispatch->Reference_Number_1;
                $packageDispatch->workerName         = '';
                $packageDispatch->destinationAddress = $packageDispatch->Dropoff_Address_Line_1;
                $packageDispatch->recipientNotes     = '';
                $packageDispatch->photoUrl           = $photoUrl;
                $packageDispatch->Date_Delivery      = $created_at;
                $packageDispatch->arrivalLonLat      = $arrivalLonLat;
                $packageDispatch->status             = 'Delivery';

                if(isset($row[4]) && $row[4] != '')
                {
                    $packageDispatch->idTeam = $row[4];
                }

                $packageDispatch->save();
            }
            else
            {
                $packageDis = new PackageDispatch();

                $packageDis->Reference_Number_1           = $packageDispatch->Reference_Number_1;
                $packageDis->idCompany                    = $packageDispatch->idCompany;
                $packageDis->company                      = $packageDispatch->company;
                $packageDis->idStore                      = $packageDispatch->idStore;
                $packageDis->store                        = $packageDispatch->store;
                $packageDis->Dropoff_Contact_Name         = $packageDispatch->Dropoff_Contact_Name;
                $packageDis->Dropoff_Company              = $packageDispatch->Dropoff_Company;
                $packageDis->Dropoff_Contact_Phone_Number = $packageDispatch->Dropoff_Contact_Phone_Number;
                $packageDis->Dropoff_Contact_Email        = $packageDispatch->Dropoff_Contact_Email;
                $packageDis->Dropoff_Address_Line_1       = $packageDispatch->Dropoff_Address_Line_1;
                $packageDis->Dropoff_Address_Line_2       = $packageDispatch->Dropoff_Address_Line_2;
                $packageDis->Dropoff_City                 = $packageDispatch->Dropoff_City;
                $packageDis->Dropoff_Province             = $packageDispatch->Dropoff_Province;
                $packageDis->Dropoff_Postal_Code          = $packageDispatch->Dropoff_Postal_Code;
                $packageDis->Notes                        = $packageDispatch->Notes;
                $packageDis->Weight                       = $packageDispatch->Weight;
                $packageDis->Route                        = $packageDispatch->Route;
                $packageDis->idUser                       = Auth::user()->id;
                $packageDis->idTeam                       = 0;
                $packageDis->idUserDispatch               = 0;
                $packageDis->Date_Dispatch                = $created_at;
                $packageDis->Date_Delivery                = $created_at;
                $packageDis->quantity                     = 0;
                $packageDis->pricePaymentCompany          = 0;
                $packageDis->pricePaymentTeam             = 0;
                $packageDis->idPaymentTeam                = '';
                $packageDis->status                       = 'Delivery';
                $packageDis->created_at                   = $created_at;
                $packageDis->updated_at                   = $created_at;

                $packageDis->save();
            }
        }
        else if($packageDispatchAux)
        {
            if($packageDispatch->photoUrl == '')
            {
                $packageHistory = new PackageHistory();

                $packageHistory->id                           = uniqid();
                $packageHistory->Reference_Number_1           = $packageDispatchAux->Reference_Number_1;
                $packageHistory->idCompany                    = $packageDispatchAux->idCompany;
                $packageHistory->company                      = $packageDispatchAux->company;
                $packageHistory->Dropoff_Contact_Name         = $packageDispatchAux->Dropoff_Contact_Name;
                $packageHistory->Dropoff_Company              = $packageDispatchAux->Dropoff_Company;
                $packageHistory->Dropoff_Contact_Phone_Number = $packageDispatchAux->Dropoff_Contact_Phone_Number;
                $packageHistory->Dropoff_Contact_Email        = $packageDispatchAux->Dropoff_Contact_Email;
                $packageHistory->Dropoff_Address_Line_1       = $packageDispatchAux->Dropoff_Address_Line_1;
                $packageHistory->Dropoff_Address_Line_2       = $packageDispatchAux->Dropoff_Address_Line_2;
                $packageHistory->Dropoff_City                 = $packageDispatchAux->Dropoff_City;
                $packageHistory->Dropoff_Province             = $packageDispatchAux->Dropoff_Province;
                $packageHistory->Dropoff_Postal_Code          = $packageDispatchAux->Dropoff_Postal_Code;
                $packageHistory->Notes                        = $packageDispatchAux->Notes;
                $packageHistory->Weight                       = $packageDispatchAux->Weight;
                $packageHistory->Route                        = $packageDispatchAux->Route;
                $packageHistory->idTeam                       = $packageDispatchAux->idTeam;
                $packageHistory->idUserDispatch               = $packageDispatchAux->idUserDispatch;
                $packageHistory->idUser                       = $packageDispatchAux->idUser;
                $packageHistory->idUserDelivery               = $packageDispatchAux->idUserDispatch;
                $packageHistory->Date_Delivery                = $created_at;
                $packageHistory->Description                  = $description;
                $packageHistory->status                       = 'Delivery';
                $packageHistory->actualDate                   = $created_at;
                $packageHistory->created_at                   = $created_at;
                $packageHistory->updated_at                   = $created_at;

                $packageHistory->save();

                $packageDispatch->taskDetails        = $packageDispatch->Reference_Number_1;
                $packageDispatch->workerName         = '';
                $packageDispatch->destinationAddress = $packageDispatch->Dropoff_Address_Line_1;
                $packageDispatch->recipientNotes     = '';
                $packageDispatch->photoUrl           = $photoUrl;
                $packageDispatch->Date_Delivery      = $created_at;
                $packageDispatch->arrivalLonLat      = $arrivalLonLat;
                $packageDispatch->status             = 'Delivery';

                if(isset($row[4]) && $row[4] != '')
                {
                    $packageDispatch->idTeam = $row[4];
                }

                $packageDispatch->save();
            }
        }
    }

    public function UpdatedTeamOrDriverFailed()
    {
        $packageHistoryFailed = PackageHistory::where('idUserDispatch', null)
                                                ->where('status', 'Failed')
                                                ->get();

        foreach($packageHistoryFailed as $packageFailed)
        {
            $packageFailed = PackageHistory::find($packageFailed->id);

            $packageDispatch = PackageHistory::where('Reference_Number_1', $packageFailed->Reference_Number_1)
                                                ->where('status', 'Dispatch')
                                                ->first();

            if($packageDispatch)
            {
                $packageFailed->idUserDispatch = $packageDispatch->idUserDispatch;

                $packageFailed->save();
            }

        }

        return "completed";
    }

    public function UpdatedCreatedDate()
    {
        $dateInit = date('Y-m-d 00:00:00');
        $dateEnd  = date('Y-m-d 23:59:59');

        $packageDispatchList = PackageDispatch::where('status', 'Delivery')
                                                ->whereBetween('updated_at', [$dateInit, $dateEnd])
                                                ->get();

        foreach($packageDispatchList as $packageDispatch)
        {
            if($packageDispatch->Date_Delivery == $packageDispatch->created_at)
            {
                $packageHistory = PackageHistory::where('Reference_Number_1', $packageDispatch->Reference_Number_1)
                                                ->where('status', 'Delivery')
                                                ->get()
                                                ->last();

                if($packageHistory)
                {
                    $packageHistory->created_at = $packageDispatch->Date_Delivery;

                    $packageHistory->save();
                }
            }
        }

        return "completed";
    }

    ////******* APi ONFLEET
    public function UpdatedDeliverFields()
    {
        $packageDispatch = PackageDispatch::where('status', 'Delivery')->get();

        foreach($packageDispatch as $packDispatch)
        {
            if($packDispatch->taskDetails == '')
            {
                $packDelivery = packageDelivery::find($packDispatch->Reference_Number_1);

                if($packDelivery)
                {
                    $packageDispatch = PackageDispatch::find($packDispatch->Reference_Number_1);

                    $packageDispatch->taskDetails        = $packDelivery->taskDetails;
                    $packageDispatch->workerName         = $packDelivery->workerName;
                    $packageDispatch->destinationAddress = $packDelivery->destinationAddress;
                    $packageDispatch->recipientNotes     = $packDelivery->recipientNotes;
                    $packageDispatch->photoUrl           = $packDelivery->photoUrl;
                    $packageDispatch->forceCompletedBy   = $packDelivery->forceCompletedBy;
                    $packageDispatch->arrivalTime        = $packDelivery->arrivalTime;
                    $packageDispatch->arrivalLonLat      = $packDelivery->arrivalLonLat;
                    $packageDispatch->Date_Delivery      = $packDelivery->created_at;

                    $packageDispatch->save();
                }
            }
        }

        dd('completed');
    }

    public function UpdatedOnfleet()
    {
        $listPackageDispatch = PackageDispatch::where('status', 'Dispatch')
                                                ->where('idOnfleet', '!=', '')
                                                ->orderBy('created_at', 'asc')
                                                ->get()
                                                ->take(200);

        $quantityOnfleet = 0;

        try
        {
            DB::beginTransaction();

            foreach($listPackageDispatch as $packageDispatch)
            {
                $onfleet = $this->GetOnfleet($packageDispatch->taskOnfleet);

                if($onfleet)
                {
                    if($onfleet['state'] == 3 && isset($onfleet['completionDetails']['success']))
                    {
                        if($onfleet['completionDetails']['success'] == true)
                        {
                            $packageDispatch = PackageDispatch::where('status', 'Dispatch')
                                                                ->find($packageDispatch->Reference_Number_1);

                            if($packageDispatch)
                            {
                                $user = User::find($packageDispatch->idUserDispatch);

                                if($user)
                                {
                                    if($user->nameTeam)
                                    {
                                        $description = 'Delivery - for: Team 1 to '. $user->nameTeam .' / '. $user->name .' '. $user->nameOfOwner;
                                    }
                                    else
                                    {
                                        $description = 'Delivery - for: Team 1 to '. $user->name;
                                    }
                                }
                                else
                                {
                                    $description = 'Delivery - for: Not exist Team';
                                }

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
                                $packageHistory->idTeam                       = $packageDispatch->idTeam;
                                $packageHistory->idUserDispatch               = $packageDispatch->idUserDispatch;
                                $packageHistory->idUser                       = 64;
                                $packageHistory->idUserDelivery               = 64;
                                $packageHistory->Date_Delivery                = date('Y-m-d H:i:s', $onfleet['completionDetails']['time'] / 1000);
                                $packageHistory->Description                  = $description;
                                $packageHistory->status                       = 'Delivery';

                                $packageHistory->save();

                                $packageDispatch->taskDetails        = $packageDispatch->Reference_Number_1;
                                $packageDispatch->workerName         = $user->name .' '. $user->nameOfOwner;
                                $packageDispatch->destinationAddress = $packageDispatch->Dropoff_Address_Line_1;
                                $packageDispatch->recipientNotes     = $user->nameTeam;

                                if(count($onfleet['completionDetails']['photoUploadIds']) > 0)
                                {
                                    $photoUrl = implode(",", $onfleet['completionDetails']['photoUploadIds']);
                                }
                                else
                                {
                                    $photoUrl   = $onfleet['completionDetails']['photoUploadId'];
                                }

                                $packageDispatch->photoUrl           = $photoUrl;
                                $packageDispatch->Date_Delivery      = date('Y-m-d H:i:s', $onfleet['completionDetails']['time'] / 1000);

                                $packageDispatch->status = 'Delivery';

                                if($packageDispatch->save())
                                {
                                    $quantityOnfleet++;
                                }
                            }
                        }
                    }
                }
            }

            DB::commit();

            return [
                    'stateAction' => 'onfleet',
                    'quantityDispatch' => count($listPackageDispatch),
                    'quantityOnfleet' => $quantityOnfleet,
            ];
        }
        catch(Exception $e)
        {
            DB::rollback();

            return [
                    'stateAction' => 'error',
            ];
        }

        return ['stateAction' => 'notOnfleet'];
    }

    public function GetOnfleet($taskOnfleet)
    {
        $curl = curl_init("https://onfleet.com/api/v2/tasks/shortId/". $taskOnfleet);

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

    public function GetListOnfleet()
    {
        $curl = curl_init("https://onfleet.com/api/v2/tasks/all?from=1455072025000&state=3");

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
}
