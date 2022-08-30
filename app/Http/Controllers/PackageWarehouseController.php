<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{Configuration, PackageHistory, PackageInbound, PackageDispatch, PackageManifest, PackageReturn, PackageWarehouse, User};

use Illuminate\Support\Facades\Validator;

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

    public function List(Request $request, $filterDate, $route, $state)
    {
        $routes = explode(',', $route);
        $states = explode(',', $state);

        if(Session::get('user')->role->name == 'Administrador')
        {
            $packageListWarehouse = PackageWarehouse::with('user');
        }
            $date = explode("-",$filterDate);
            $packageListWarehouse = $packageListWarehouse->whereMonth('created_at', $date[1])
                                    ->whereYear('created_at', $date[0])
                                    ->whereDay('created_at', $date[2]);

        if($route != 'all')
        {
            $packageListWarehouse = $packageListWarehouse->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $packageListWarehouse = $packageListWarehouse->whereIn('Dropoff_Province', $states);
        }

        $packageListWarehouse = $packageListWarehouse->orderBy('created_at', 'desc')->paginate(50);
        $quantityInbound      = $packageListWarehouse->total();

        $listState  = PackageWarehouse::select('Dropoff_Province')
                                            ->groupBy('Dropoff_Province')
                                            ->get();

        return ['packageList' => $packageListWarehouse, 'listState' => $listState, 'quantityInbound' => $quantityInbound];
    }

    public function Insert(Request $request)
    {
        $packageWarehouse = PackageWarehouse::find($request->get('Reference_Number_1'));

        //VALIDATION OF PACKAGE IN WAREHOUSE AND UPDATE DATE CREATED
        if($packageWarehouse)
        {
            if(date('Y-m-d', strtotime($packageWarehouse->created_at)) == date('Y-m-d'))
            {
                return ['stateAction' => 'packageInWarehouse', 'packageWarehouse' => $packageWarehouse];
            }

            $packageWarehouse->created_at = date('Y-m-d H:i:s');

            $packageWarehouse->save();

            return ['stateAction' => 'packageUpdateCreatedAt', 'packageWarehouse' => $packageWarehouse];
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
            $packageDispatch = PackageDispatch::find($request->get('Reference_Number_1'));
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
                
                if($packageManifest)
                {
                    $packageInbound = new PackageInbound();

                    $packageInbound->Reference_Number_1           = $package->Reference_Number_1;
                    $packageInbound->Reference_Number_2           = $package->Reference_Number_2;
                    $packageInbound->Reference_Number_3           = $package->Reference_Number_3;
                    $packageInbound->TRUCK                        = $request->get('TRUCK') ? $request->get('TRUCK') : '';
                    $packageInbound->CLIENT                       = $package->company;
                    $packageInbound->Ready_At                     = $package->Ready_At;
                    $packageInbound->Del_Date                     = $package->Del_Date;
                    $packageInbound->Del_no_earlier_than          = $package->Del_no_earlier_than;
                    $packageInbound->Del_no_later_than            = $package->Del_no_later_than;
                    $packageInbound->Pickup_Contact_Name          = $package->Pickup_Contact_Name;
                    $packageInbound->Pickup_Company               = $package->Pickup_Company;
                    $packageInbound->Pickup_Contact_Phone_Number  = $package->Pickup_Contact_Phone_Number;
                    $packageInbound->Pickup_Contact_Email         = $package->Pickup_Contact_Email;
                    $packageInbound->Pickup_Address_Line_1        = $package->Pickup_Address_Line_1;
                    $packageInbound->Pickup_Address_Line_2        = $package->Pickup_Address_Line_2;
                    $packageInbound->Pickup_City                  = $package->Pickup_City;
                    $packageInbound->Pickup_Province              = $package->Pickup_Province;
                    $packageInbound->Pickup_Postal_Code           = $package->Pickup_Postal_Code;
                    $packageInbound->Dropoff_Contact_Name         = $package->Dropoff_Contact_Name;
                    $packageInbound->Dropoff_Company              = $package->Dropoff_Company;
                    $packageInbound->Dropoff_Contact_Phone_Number = $package->Dropoff_Contact_Phone_Number;
                    $packageInbound->Dropoff_Contact_Email        = $package->Dropoff_Contact_Email;
                    $packageInbound->Dropoff_Address_Line_1       = $package->Dropoff_Address_Line_1;
                    $packageInbound->Dropoff_Address_Line_2       = $package->Dropoff_Address_Line_2;
                    $packageInbound->Dropoff_City                 = $package->Dropoff_City;
                    $packageInbound->Dropoff_Province             = $package->Dropoff_Province;
                    $packageInbound->Dropoff_Postal_Code          = $package->Dropoff_Postal_Code;
                    $packageInbound->Service_Level                = $package->Service_Level;
                    $packageInbound->Carrier_Name                 = $package->Carrier_Name;
                    $packageInbound->Vehicle_Type_Id              = $package->Vehicle_Type_Id;
                    $packageInbound->Notes                        = $package->Notes;
                    $packageInbound->Number_Of_Pieces             = $package->Number_Of_Pieces;
                    $packageInbound->Weight                       = $package->Weight;
                    $packageInbound->Route                        = $package->Route;
                    $packageInbound->Name                         = $package->Name;
                    $packageInbound->idUser                       = Session::get('user')->id;
                    $packageInbound->status                       = 'Inbound';

                    $packageInbound->save();

                    $packageHistory = new PackageHistory();

                    $packageHistory->id                           = uniqid();
                    $packageHistory->Reference_Number_1           = $package->Reference_Number_1;
                    $packageHistory->Reference_Number_2           = $package->Reference_Number_2;
                    $packageHistory->Reference_Number_3           = $package->Reference_Number_3;
                    $packageHistory->TRUCK                        = $request->get('TRUCK') ? $request->get('TRUCK') : '';
                    $packageHistory->CLIENT                       = $request->get('CLIENT') ? $request->get('CLIENT') : '';
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
                    $packageHistory->idUser                       = Session::get('user')->id;
                    $packageHistory->idUserInbound                = Session::get('user')->id;
                    $packageHistory->Date_Inbound                 = date('Y-m-d H:s:i');
                    $packageHistory->Description                  = 'Inbound - for: '. Session::get('user')->name .' '. Session::get('user')->nameOfOwner;
                    $packageHistory->inbound                      = 1;
                    $packageHistory->status                       = 'Inbound';

                    $packageHistory->save();

                    $packageManifest->delete();
                }

                if($packageDispatch)
                {
                    $user = User::find($packageDispatch->idUserDispatch);

                    if($user->nameTeam)
                    {
                        $description = 'Return - for: '. Session::get('user')->name .' '. Session::get('user')->nameOfOwner .' to '. $user->nameTeam .' / '. $user->name .' '. $user->nameOfOwner;
                    }
                    else
                    {
                        $description = 'Return - for: '. Session::get('user')->name .' '. Session::get('user')->nameOfOwner .' to '. $user->name;
                    }

                    $idOnfleet     = '';
                    $taskOnfleet   = '';
                    $team          = '';
                    $workerName    = '';
                    $photoUrl      = '';
                    $statusOnfleet = '';
                    $onfleet       = '';

                    $team       = $user->nameTeam;
                    $workerName = $user->name .' '. $user->nameOfOwner;

                    $Date_Return         = date('Y-m-d H:i:s');
                    $Description_Return  = $request->get('Description_Return');
                    $Description_Onfleet = ''; 

                    if(env('APP_ENV') == 'local' && $packageDispatch->idOnfleet)
                    {
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
                    }

                    $packageReturn = new PackageReturn();

                    $packageReturn->id                           = uniqid();
                    $packageReturn->Reference_Number_1           = $packageDispatch->Reference_Number_1;
                    $packageReturn->Reference_Number_2           = $packageDispatch->Reference_Number_2;
                    $packageReturn->Reference_Number_3           = $packageDispatch->Reference_Number_3;
                    $packageReturn->Ready_At                     = $packageDispatch->Ready_At;
                    $packageReturn->Del_Date                     = $packageDispatch->Del_Date;
                    $packageReturn->Del_no_earlier_than          = $packageDispatch->Del_no_earlier_than;
                    $packageReturn->Del_no_later_than            = $packageDispatch->Del_no_later_than;
                    $packageReturn->Pickup_Contact_Name          = $packageDispatch->Pickup_Contact_Name;
                    $packageReturn->Pickup_Company               = $packageDispatch->Pickup_Company;
                    $packageReturn->Pickup_Contact_Phone_Number  = $packageDispatch->Pickup_Contact_Phone_Number;
                    $packageReturn->Pickup_Contact_Email         = $packageDispatch->Pickup_Contact_Email;
                    $packageReturn->Pickup_Address_Line_1        = $packageDispatch->Pickup_Address_Line_1;
                    $packageReturn->Pickup_Address_Line_2        = $packageDispatch->Pickup_Address_Line_2;
                    $packageReturn->Pickup_City                  = $packageDispatch->Pickup_City;
                    $packageReturn->Pickup_Province              = $packageDispatch->Pickup_Province;
                    $packageReturn->Pickup_Postal_Code           = $packageDispatch->Pickup_Postal_Code;
                    $packageReturn->Dropoff_Contact_Name         = $packageDispatch->Dropoff_Contact_Name;
                    $packageReturn->Dropoff_Company              = $packageDispatch->Dropoff_Company;
                    $packageReturn->Dropoff_Contact_Phone_Number = $packageDispatch->Dropoff_Contact_Phone_Number;
                    $packageReturn->Dropoff_Contact_Email        = $packageDispatch->Dropoff_Contact_Email;
                    $packageReturn->Dropoff_Address_Line_1       = $packageDispatch->Dropoff_Address_Line_1;
                    $packageReturn->Dropoff_Address_Line_2       = $packageDispatch->Dropoff_Address_Line_2;
                    $packageReturn->Dropoff_City                 = $packageDispatch->Dropoff_City;
                    $packageReturn->Dropoff_Province             = $packageDispatch->Dropoff_Province;
                    $packageReturn->Dropoff_Postal_Code          = $packageDispatch->Dropoff_Postal_Code;
                    $packageReturn->Service_Level                = $packageDispatch->Service_Level;
                    $packageReturn->Carrier_Name                 = $packageDispatch->Carrier_Name;
                    $packageReturn->Vehicle_Type_Id              = $packageDispatch->Vehicle_Type_Id;
                    $packageReturn->Notes                        = $packageDispatch->Notes;
                    $packageReturn->Number_Of_Pieces             = $packageDispatch->Number_Of_Pieces;
                    $packageReturn->Weight                       = $packageDispatch->Weight;
                    $packageReturn->Route                        = $packageDispatch->Route;
                    $packageReturn->Name                         = $packageDispatch->Name;
                    $packageReturn->idUser                       = Session::get('user')->id;
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
                    $packageReturn->status                       = 'Return';

                    $packageReturn->save();

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
                    $packageHistory->idUser                       = Session::get('user')->id;
                    $packageHistory->idUserInbound                = Session::get('user')->id;
                    $packageHistory->Date_Inbound                 = date('Y-m-d H:s:i');
                    $packageHistory->Description                  = 'Return - for: '. Session::get('user')->name .' '. Session::get('user')->nameOfOwner;
                    $packageHistory->Description_Return           = $Description_Return;
                    $packageHistory->Description_Onfleet          = $Description_Onfleet;
                    $packageHistory->inbound                      = 1;
                    $packageHistory->status                       = 'Return';

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
                $packageWarehouse->Reference_Number_2           = $package->Reference_Number_2;
                $packageWarehouse->Reference_Number_3           = $package->Reference_Number_3;
                $packageWarehouse->Ready_At                     = $package->Ready_At;
                $packageWarehouse->Del_Date                     = $package->Del_Date;
                $packageWarehouse->Del_no_earlier_than          = $package->Del_no_earlier_than;
                $packageWarehouse->Del_no_later_than            = $package->Del_no_later_than;
                $packageWarehouse->Pickup_Contact_Name          = $package->Pickup_Contact_Name;
                $packageWarehouse->Pickup_Company               = $package->Pickup_Company;
                $packageWarehouse->Pickup_Contact_Phone_Number  = $package->Pickup_Contact_Phone_Number;
                $packageWarehouse->Pickup_Contact_Email         = $package->Pickup_Contact_Email;
                $packageWarehouse->Pickup_Address_Line_1        = $package->Pickup_Address_Line_1;
                $packageWarehouse->Pickup_Address_Line_2        = $package->Pickup_Address_Line_2;
                $packageWarehouse->Pickup_City                  = $package->Pickup_City;
                $packageWarehouse->Pickup_Province              = $package->Pickup_Province;
                $packageWarehouse->Pickup_Postal_Code           = $package->Pickup_Postal_Code;
                $packageWarehouse->Dropoff_Contact_Name         = $package->Dropoff_Contact_Name;
                $packageWarehouse->Dropoff_Company              = $package->Dropoff_Company;
                $packageWarehouse->Dropoff_Contact_Phone_Number = $package->Dropoff_Contact_Phone_Number;
                $packageWarehouse->Dropoff_Contact_Email        = $package->Dropoff_Contact_Email;
                $packageWarehouse->Dropoff_Address_Line_1       = $package->Dropoff_Address_Line_1;
                $packageWarehouse->Dropoff_Address_Line_2       = $package->Dropoff_Address_Line_2;
                $packageWarehouse->Dropoff_City                 = $package->Dropoff_City;
                $packageWarehouse->Dropoff_Province             = $package->Dropoff_Province;
                $packageWarehouse->Dropoff_Postal_Code          = $package->Dropoff_Postal_Code;
                $packageWarehouse->Service_Level                = $package->Service_Level;
                $packageWarehouse->Carrier_Name                 = $package->Carrier_Name;
                $packageWarehouse->Vehicle_Type_Id              = $package->Vehicle_Type_Id;
                $packageWarehouse->Notes                        = $package->Notes;
                $packageWarehouse->Number_Of_Pieces             = $package->Number_Of_Pieces;
                $packageWarehouse->Weight                       = $package->Weight;
                $packageWarehouse->Route                        = $package->Route;
                $packageWarehouse->Name                         = $package->Name;
                $packageWarehouse->idUser                       = Session::get('user')->id;
                $packageWarehouse->status                       = 'Warehouse';

                $packageWarehouse->save();

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
                $packageHistory->idUser                       = Session::get('user')->id;
                $packageHistory->Description                  = 'Warehouse - for: '. Session::get('user')->name .' '. Session::get('user')->nameOfOwner;
                $packageHistory->status                       = 'Warehouse';

                $packageHistory->save();
                
                $package->delete();

                DB::commit();

                return ['stateAction' => true, 'packageWarehouse' => $packageWarehouse];
            }
            catch(Exception $e)
            {
                DB::rollback();

                return ['stateAction' => true];
            }
        }
        
        return ['stateAction' => 'notExists'];
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
