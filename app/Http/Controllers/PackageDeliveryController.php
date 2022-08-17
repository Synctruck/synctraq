<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{PackageHistory, PackageDelivery, PackageDispatch, PackageInbound, PackageManifest, TeamRoute, User};

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
        $this->apiKey = '4c52f49c1db8d158f7ff1ace1722f341';

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
                if($lineNumber > 2)
                {
                    $row = str_getcsv($raw_string);

                    if(isset($row[20]))
                    {
                        $package = [];

                        $packageDispatch = PackageDispatch::where('Reference_Number_1', $row[20])->first();
                        $packageDelivery = PackageDelivery::where('taskDetails', $row[20])->first();

                        if($packageDispatch)
                        {
                            $package = $packageDispatch;
                        }

                        if($packageDelivery == null)
                        {
                            if($packageDispatch)
                            {
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
                                }
                                else
                                {
                                    $description = 'Manifest or Inbound';
                                }

                                $packageHistory = new PackageHistory();

                                $packageHistory->id                           = uniqid();
                                $packageHistory->Reference_Number_1           = $package->Reference_Number_1;
                                $packageHistory->Reference_Number_2           = $package->Reference_Number_2;
                                $packageHistory->Reference_Number_3           = $package->Reference_Number_3;
                                $packageHistory->Ready_At                     = $package->Ready_At;
                                $packageHistory->Del_Date                     = $package->Del_Date;
                                $packageHistory->Del_no_earlier_than          = $package->Del_no_earlier_than;
                                $packageHistory->Del_no_later_than            = $package->Del_no_later_than;
                                $packageHistory->Pickup_Contact_Name          = $package->Pickup_Contact_Name;
                                $packageHistory->Pickup_Company               = $package->Pickup_Company;
                                $packageHistory->Pickup_Contact_Phone_Number  = $package->Pickup_Contact_Phone_Number;
                                $packageHistory->Pickup_Contact_Email         = $package->Pickup_Contact_Email;
                                $packageHistory->Pickup_Address_Line_1        = $package->Pickup_Address_Line_1;
                                $packageHistory->Pickup_Address_Line_2        = $package->Pickup_Address_Line_2;
                                $packageHistory->Pickup_City                  = $package->Pickup_City;
                                $packageHistory->Pickup_Province              = $package->Pickup_Province;
                                $packageHistory->Pickup_Postal_Code           = $package->Pickup_Postal_Code;
                                $packageHistory->Dropoff_Contact_Name         = $package->Dropoff_Contact_Name;
                                $packageHistory->Dropoff_Company              = $package->Dropoff_Company;
                                $packageHistory->Dropoff_Contact_Phone_Number = $package->Dropoff_Contact_Phone_Number;
                                $packageHistory->Dropoff_Contact_Email        = $package->Dropoff_Contact_Email;
                                $packageHistory->Dropoff_Address_Line_1       = $package->Dropoff_Address_Line_1;
                                $packageHistory->Dropoff_Address_Line_2       = $package->Dropoff_Address_Line_2;
                                $packageHistory->Dropoff_City                 = $package->Dropoff_City;
                                $packageHistory->Dropoff_Province             = $package->Dropoff_Province;
                                $packageHistory->Dropoff_Postal_Code          = $package->Dropoff_Postal_Code;
                                $packageHistory->Service_Level                = $package->Service_Level;
                                $packageHistory->Carrier_Name                 = $package->Carrier_Name;
                                $packageHistory->Vehicle_Type_Id              = $package->Vehicle_Type_Id;
                                $packageHistory->Notes                        = $package->Notes;
                                $packageHistory->Number_Of_Pieces             = $package->Number_Of_Pieces;
                                $packageHistory->Weight                       = $package->Weight;
                                $packageHistory->Route                        = $package->Route;
                                $packageHistory->Name                         = $package->Name;
                                $packageHistory->idUserDispatch              = $packageDispatch->idUserDispatch;
                                $packageHistory->idUser                       = Session::get('user')->id;

                                $saveFailed = false;

                                if($row[16] == 'TRUE')
                                {
                                    //Register delivery
                                    $packageDelivery = new PackageDelivery();

                                    $packageDelivery->taskDetails        = $row[20];
                                    $packageDelivery->status             = $row[3];
                                    $packageDelivery->workerName         = $row[6];
                                    $packageDelivery->destinationAddress = $row[7];
                                    $packageDelivery->recipientNotes     = $row[21];
                                    $packageDelivery->photoUrl           = $row[23];
                                    $packageDelivery->forceCompletedBy   = $row[26];
                                    $packageDelivery->arrivalTime        = $row[30];
                                    $packageDelivery->arrivalLonLat      = $row[8];

                                    $packageDelivery->save();

                                    //Updated dispatch to delivery, for data delivery
                                    $packageDispatch->taskDetails        = $row[20];
                                    $packageDispatch->workerName         = $row[6];
                                    $packageDispatch->destinationAddress = $row[7];
                                    $packageDispatch->recipientNotes     = $row[21];
                                    $packageDispatch->photoUrl           = $row[23];
                                    $packageDispatch->forceCompletedBy   = $row[26];
                                    $packageDispatch->arrivalTime        = $row[30];
                                    $packageDispatch->arrivalLonLat      = $row[8];
                                    $packageDispatch->Date_Delivery      = date('Y-m-d H:i:s');

                                    $packageDispatch->status = 'Delivery';

                                    $packageDispatch->save();

                                    $packageHistory->idUserDelivery = Session::get('user')->id;
                                    $packageHistory->Date_Delivery  = date('Y-m-d H:s:i');

                                    $packageHistory->Description = $description;
                                    $packageHistory->status      = 'Delivery';

                                    $packageHistory->save();
                                }
                                else
                                {
                                    $dateInit = date('Y-m-d') .' 00:00:00';
                                    $dateEnd  = date('Y-m-d') .' 23:59:59';

                                    $packageFailed = PackageHistory::where('Reference_Number_1', $row[20])
                                                                    ->where('status', 'Failed')
                                                                    ->whereIn('created_at', [$dateInit, $dateEnd])
                                                                    ->first();
                                    if(!$packageFailed)
                                    {
                                        $packageHistory->idUserFailed = Session::get('user')->id;
                                        $packageHistory->Date_Failed  = date('Y-m-d H:s:i');

                                        $packageHistory->Description = 'Delivery - failed';

                                        $packageHistory->status      = 'Failed';

                                        $packageHistory->save();
                                    }
                                }
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
                                                ->inRandomOrder()
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
                                $packageDispatch = PackageDispatch::find($packageDispatch->Reference_Number_1);

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