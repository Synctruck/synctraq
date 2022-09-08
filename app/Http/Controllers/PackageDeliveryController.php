<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{Configuration, PackageHistory, PackageDelivery, PackageDispatch, PackageInbound, PackageManifest, TeamRoute, User};

use Illuminate\Support\Facades\Validator;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PhpOffice\PhpOfficePhpSpreadsheetSpreadsheet;
use PhpOffice\PhpOfficePhpSpreadsheetReaderCsv;
use PhpOffice\PhpOfficePhpSpreadsheetReaderXlsx;

use DB;
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

        $listAll = PackageDispatch::whereBetween('Date_Delivery', [$dateInit, $dateEnd])
                                ->where('confirmCheckPayment', 0)
                                ->where('photoUrl', 'like' , '%,%')
                                ->where('status', 'Delivery');

        if(Session::get('user')->role->name == 'Team')
        {
            $idsUser = User::where('idTeam', $idTeam)->get('id');

            $listAll = $listAll->whereIn('idUserDispatch', $idsUser);
        }
        elseif(Session::get('user')->role->name == 'Driver')
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

        $roleUser = Session::get('user')->role->name;

        $listState = PackageHistory::select('Dropoff_Province')
                                    ->where('dispatch', 1)
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        return ['reportList' => $listAll, 'listDeliveries' => $listDeliveries, 'listState' => $listState, 'roleUser' => $roleUser];
    }

    public function InsertForCheck(Request $request)
    {
        $packageDelivery = PackageDispatch::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();

        $packageDelivery->idUserCheckPayment = $packageDelivery->checkPayment ? 0 : Session::get('user')->id;
        $packageDelivery->checkPayment       = $packageDelivery->checkPayment ? false : true;

        $packageDelivery->save();

        return ['stateAction' => true];
    }

    public function ConfirmationCheck()
    {
        $listPackageDelivery = PackageDispatch::where('checkPayment', 1)
                                            ->where('confirmCheckPayment', 0)
                                            ->where('idUserCheckPayment', Session::get('user')->id)
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

        $listAll = PackageDispatch::whereBetween('Date_Delivery', [$dateInit, $dateEnd])
                                    ->where('status', 'Delivery');

        if($checked == 1)
        {
            $listAll = $listAll->where('checkPayment', 1)->where('confirmCheckPayment', 1);
        }
        elseif($checked != 'all')
        {
            $listAll = $listAll->where('confirmCheckPayment', 0);
        }

        if(Session::get('user')->role->name == 'Team')
        {
            $idsUser = User::where('idTeam', $idTeam)->get('id');

            $listAll = $listAll->whereIn('idUserDispatch', $idsUser);
        }
        elseif(Session::get('user')->role->name == 'Driver')
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

        $roleUser = Session::get('user')->role->name;

        $listState = PackageHistory::select('Dropoff_Province')
                                    ->where('dispatch', 1)
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        return ['reportList' => $listAll, 'listDeliveries' => $listDeliveries, 'listState' => $listState, 'roleUser' => $roleUser];
    }

    public function Import(Request $request)
    {
        $file = $request->file('file');

        $file->move(public_path() .'/file-import', 'delivery.csv');

        $handle = fopen(public_path('file-import/delivery.csv'), "r");

        $lineNumber = 1;

        $countSave = 0;

        try
        {
            DB::beginTransaction();

            while (($raw_string = fgets($handle)) !== false)
            {
                if($lineNumber > 1)
                {
                    $row = str_getcsv($raw_string);

                    if(isset($row[0]))
                    {
                        $package = [];

                        $packageDispatch = PackageDispatch::where('Reference_Number_1', $row[0])
                                                            ->where('status', 'Dispatch')
                                                            ->first();

                        $packageDelivery = PackageDelivery::where('taskDetails', $row)->first();
                        
                        if($packageDispatch && $packageDelivery == null)
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

                            if($user)
                            {
                                $packageHistory = new PackageHistory();

                                $packageHistory->id                           = uniqid();
                                $packageHistory->Reference_Number_1           = $packageDispatch->Reference_Number_1;
                                $packageHistory->idCompany                    = $packageDispatch->idCompany;
                                $packageHistory->company                      = $packageDispatch->company;
                                $packageHistory->Reference_Number_2           = $packageDispatch->Reference_Number_2;
                                $packageHistory->Reference_Number_3           = $packageDispatch->Reference_Number_3;
                                $packageHistory->Ready_At                     = $packageDispatch->Ready_At;
                                $packageHistory->Del_Date                     = $packageDispatch->Del_Date;
                                $packageHistory->Del_no_earlier_than          = $packageDispatch->Del_no_earlier_than;
                                $packageHistory->Del_no_later_than            = $packageDispatch->Del_no_later_than;
                                $packageHistory->Pickup_Contact_Name          = $packageDispatch->Pickup_Contact_Name;
                                $packageHistory->Pickup_Company               = $packageDispatch->Pickup_Company;
                                $packageHistory->Pickup_Contact_Phone_Number  = $packageDispatch->Pickup_Contact_Phone_Number;
                                $packageHistory->Pickup_Contact_Email         = $packageDispatch->Pickup_Contact_Email;
                                $packageHistory->Pickup_Address_Line_1        = $packageDispatch->Pickup_Address_Line_1;
                                $packageHistory->Pickup_Address_Line_2        = $packageDispatch->Pickup_Address_Line_2;
                                $packageHistory->Pickup_City                  = $packageDispatch->Pickup_City;
                                $packageHistory->Pickup_Province              = $packageDispatch->Pickup_Province;
                                $packageHistory->Pickup_Postal_Code           = $packageDispatch->Pickup_Postal_Code;
                                $packageHistory->Dropoff_Contact_Name         = $packageDispatch->Dropoff_Contact_Name;
                                $packageHistory->Dropoff_Company              = $packageDispatch->Dropoff_Company;
                                $packageHistory->Dropoff_Contact_Phone_Number = $packageDispatch->Dropoff_Contact_Phone_Number;
                                $packageHistory->Dropoff_Contact_Email        = $packageDispatch->Dropoff_Contact_Email;
                                $packageHistory->Dropoff_Address_Line_1       = $packageDispatch->Dropoff_Address_Line_1;
                                $packageHistory->Dropoff_Address_Line_2       = $packageDispatch->Dropoff_Address_Line_2;
                                $packageHistory->Dropoff_City                 = $packageDispatch->Dropoff_City;
                                $packageHistory->Dropoff_Province             = $packageDispatch->Dropoff_Province;
                                $packageHistory->Dropoff_Postal_Code          = $packageDispatch->Dropoff_Postal_Code;
                                $packageHistory->Service_Level                = $packageDispatch->Service_Level;
                                $packageHistory->Carrier_Name                 = $packageDispatch->Carrier_Name;
                                $packageHistory->Vehicle_Type_Id              = $packageDispatch->Vehicle_Type_Id;
                                $packageHistory->Notes                        = $packageDispatch->Notes;
                                $packageHistory->Number_Of_Pieces             = $packageDispatch->Number_Of_Pieces;
                                $packageHistory->Weight                       = $packageDispatch->Weight;
                                $packageHistory->Route                        = $packageDispatch->Route;
                                $packageHistory->Name                         = $packageDispatch->Name;
                                $packageHistory->idTeam                       = $packageDispatch->idTeam;
                                $packageHistory->idUserDispatch               = $packageDispatch->idUserDispatch;
                                $packageHistory->idUser                       = Session::get('user')->id;
                                $packageHistory->idUserDelivery               = Session::get('user')->id;
                                $packageHistory->Date_Delivery                = date('Y-m-d H:s:i');
                                $packageHistory->Description                  = $description;
                                $packageHistory->status                       = 'Delivery';

                                $packageHistory->save();

                                //Register delivery
                                $packageDelivery = new PackageDelivery();

                                $packageDelivery->taskDetails    = $row[0];
                                $packageDelivery->workerName     = $user->name .' '. $user->nameOfOwner;
                                $packageDelivery->recipientNotes = $user->nameTeam;
                                $packageDelivery->photoUrl       = $row[1];
                                $packageDelivery->arrivalTime    = $row[2];
                                $packageDelivery->arrivalLonLat  = $row[3];
                                $packageDelivery->status         = 'completed';

                                $packageDelivery->save();

                                $packageDispatch->Date_Delivery  = date('Y-m-d H:i:s');
                                $packageDispatch->status         = 'Delivery';

                                $packageDispatch->save();
                            }
                        }
                    }
                }

                $lineNumber++;
            }

            fclose($handle);

            DB::commit();

            return ['stateAction' => true];
        }
        catch(Exception $e)
        {
            DB::rollback();

            return ['stateAction' => false];
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
        if(env('APP_ENV') == 'local')
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
                                    $packageHistory->Reference_Number_2           = $packageDispatch->Reference_Number_2;
                                    $packageHistory->Reference_Number_3           = $packageDispatch->Reference_Number_3;
                                    $packageHistory->Ready_At                     = $packageDispatch->Ready_At;
                                    $packageHistory->Del_Date                     = $packageDispatch->Del_Date;
                                    $packageHistory->Del_no_earlier_than          = $packageDispatch->Del_no_earlier_than;
                                    $packageHistory->Del_no_later_than            = $packageDispatch->Del_no_later_than;
                                    $packageHistory->Pickup_Contact_Name          = $packageDispatch->Pickup_Contact_Name;
                                    $packageHistory->Pickup_Company               = $packageDispatch->Pickup_Company;
                                    $packageHistory->Pickup_Contact_Phone_Number  = $packageDispatch->Pickup_Contact_Phone_Number;
                                    $packageHistory->Pickup_Contact_Email         = $packageDispatch->Pickup_Contact_Email;
                                    $packageHistory->Pickup_Address_Line_1        = $packageDispatch->Pickup_Address_Line_1;
                                    $packageHistory->Pickup_Address_Line_2        = $packageDispatch->Pickup_Address_Line_2;
                                    $packageHistory->Pickup_City                  = $packageDispatch->Pickup_City;
                                    $packageHistory->Pickup_Province              = $packageDispatch->Pickup_Province;
                                    $packageHistory->Pickup_Postal_Code           = $packageDispatch->Pickup_Postal_Code;
                                    $packageHistory->Dropoff_Contact_Name         = $packageDispatch->Dropoff_Contact_Name;
                                    $packageHistory->Dropoff_Company              = $packageDispatch->Dropoff_Company;
                                    $packageHistory->Dropoff_Contact_Phone_Number = $packageDispatch->Dropoff_Contact_Phone_Number;
                                    $packageHistory->Dropoff_Contact_Email        = $packageDispatch->Dropoff_Contact_Email;
                                    $packageHistory->Dropoff_Address_Line_1       = $packageDispatch->Dropoff_Address_Line_1;
                                    $packageHistory->Dropoff_Address_Line_2       = $packageDispatch->Dropoff_Address_Line_2;
                                    $packageHistory->Dropoff_City                 = $packageDispatch->Dropoff_City;
                                    $packageHistory->Dropoff_Province             = $packageDispatch->Dropoff_Province;
                                    $packageHistory->Dropoff_Postal_Code          = $packageDispatch->Dropoff_Postal_Code;
                                    $packageHistory->Service_Level                = $packageDispatch->Service_Level;
                                    $packageHistory->Carrier_Name                 = $packageDispatch->Carrier_Name;
                                    $packageHistory->Vehicle_Type_Id              = $packageDispatch->Vehicle_Type_Id;
                                    $packageHistory->Notes                        = $packageDispatch->Notes;
                                    $packageHistory->Number_Of_Pieces             = $packageDispatch->Number_Of_Pieces;
                                    $packageHistory->Weight                       = $packageDispatch->Weight;
                                    $packageHistory->Route                        = $packageDispatch->Route;
                                    $packageHistory->Name                         = $packageDispatch->Name;
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
