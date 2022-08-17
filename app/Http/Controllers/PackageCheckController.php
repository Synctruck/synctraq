<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{Assigned, Driver, PackageHistory, PackageDispatch, PackageInbound, PackageManifest, PackageNotExists, PackageReturn, TeamRoute, User};

use Illuminate\Support\Facades\Validator;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PhpOffice\PhpOfficePhpSpreadsheetSpreadsheet;
use PhpOffice\PhpOfficePhpSpreadsheetReaderCsv;
use PhpOffice\PhpOfficePhpSpreadsheetReaderXlsx;

use DB;
use Session;

class PackageCheckController extends Controller
{
    public function Index()
    {
        return view('package.check');
    }

    public function Import(Request $request)
    {
        $file = $request->file('file');

        $file->move(public_path() .'/file-import', 'check.csv');

        $handle = fopen(public_path('file-import/check.csv'), "r");

        $lineNumber = 1;

        $countSave = 0;

        $packageList = [];

        while (($raw_string = fgets($handle)) !== false)
        {
            if($lineNumber > 1)
            {
                $row = str_getcsv($raw_string);

                $data = [

                    'package' => $row[20],
                    'driver' => $row[6],
                    'stop' => $row[1],
                ];

                array_push($packageList, $data);
            }
            
            $lineNumber++;
        }

        return ['packageList' => $packageList];
    }

    public function Return(Request $request)
    {
        $packageDispatch = PackageDispatch::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();
        
        if($packageDispatch)
        {
            if($packageDispatch->idUserDispatch == Session::get('user')->id || Session::get('user')->role->name == 'Administrador')
            {
                $user = User::find($packageDispatch->idUserDispatch);

                if($user->nameTeam)
                {
                    $description = 'Return - for: Dispatcher 1 to '. $user->nameTeam .' / '. $user->name .' '. $user->nameOfOwner;
                }
                else
                {
                    $description = 'Return - for: Dispatcher 1 to '. $user->name;
                }

                try
                {
                    DB::beginTransaction();

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
                    $packageReturn->Date_Return                  = date('Y-m-d H:i:s'); 
                    $packageReturn->Description_Return           = $request->get('Description_Return');
                    $packageReturn->status                       = 'Return';

                    $packageReturn->save();
                    
                    //update dispatch
                    $packageHistory = PackageHistory::where('Reference_Number_1', $request->get('Reference_Number_1'))
                                            ->where('dispatch', 1)
                                            ->first();

                    $packageHistory->dispatch = 0;

                    $packageHistory->save();

                    //update inbound
                    $packageHistory = PackageHistory::where('Reference_Number_1', $request->get('Reference_Number_1'))
                                            ->where('inbound', 1)
                                            ->first();

                    $packageHistory->inbound  = 0;

                    $packageHistory->save();


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
                    $packageHistory->idUserReturn                 = Session::get('user')->id;
                    $packageHistory->Date_Return                  = date('Y-m-d H:s:i');
                    $packageHistory->Description                  = $description;
                    $packageHistory->status                       = 'Return';

                    $packageHistory->save();

                    $packageInbound = new PackageInbound();

                    $packageInbound->Reference_Number_1           = $packageDispatch->Reference_Number_1;
                    $packageInbound->Reference_Number_2           = $packageDispatch->Reference_Number_2;
                    $packageInbound->Reference_Number_3           = $packageDispatch->Reference_Number_3;
                    $packageInbound->Ready_At                     = $packageDispatch->Ready_At;
                    $packageInbound->Del_Date                     = $packageDispatch->Del_Date;
                    $packageInbound->Del_no_earlier_than          = $packageDispatch->Del_no_earlier_than;
                    $packageInbound->Del_no_later_than            = $packageDispatch->Del_no_later_than;
                    $packageInbound->Pickup_Contact_Name          = $packageDispatch->Pickup_Contact_Name;
                    $packageInbound->Pickup_Company               = $packageDispatch->Pickup_Company;
                    $packageInbound->Pickup_Contact_Phone_Number  = $packageDispatch->Pickup_Contact_Phone_Number;
                    $packageInbound->Pickup_Contact_Email         = $packageDispatch->Pickup_Contact_Email;
                    $packageInbound->Pickup_Address_Line_1        = $packageDispatch->Pickup_Address_Line_1;
                    $packageInbound->Pickup_Address_Line_2        = $packageDispatch->Pickup_Address_Line_2;
                    $packageInbound->Pickup_City                  = $packageDispatch->Pickup_City;
                    $packageInbound->Pickup_Province              = $packageDispatch->Pickup_Province;
                    $packageInbound->Pickup_Postal_Code           = $packageDispatch->Pickup_Postal_Code;
                    $packageInbound->Dropoff_Contact_Name         = $packageDispatch->Dropoff_Contact_Name;
                    $packageInbound->Dropoff_Company              = $packageDispatch->Dropoff_Company;
                    $packageInbound->Dropoff_Contact_Phone_Number = $packageDispatch->Dropoff_Contact_Phone_Number;
                    $packageInbound->Dropoff_Contact_Email        = $packageDispatch->Dropoff_Contact_Email;
                    $packageInbound->Dropoff_Address_Line_1       = $packageDispatch->Dropoff_Address_Line_1;
                    $packageInbound->Dropoff_Address_Line_2       = $packageDispatch->Dropoff_Address_Line_2;
                    $packageInbound->Dropoff_City                 = $packageDispatch->Dropoff_City;
                    $packageInbound->Dropoff_Province             = $packageDispatch->Dropoff_Province;
                    $packageInbound->Dropoff_Postal_Code          = $packageDispatch->Dropoff_Postal_Code;
                    $packageInbound->Service_Level                = $packageDispatch->Service_Level;
                    $packageInbound->Carrier_Name                 = $packageDispatch->Carrier_Name;
                    $packageInbound->Vehicle_Type_Id              = $packageDispatch->Vehicle_Type_Id;
                    $packageInbound->Notes                        = $packageDispatch->Notes;
                    $packageInbound->Number_Of_Pieces             = $packageDispatch->Number_Of_Pieces;
                    $packageInbound->Weight                       = $packageDispatch->Weight;
                    $packageInbound->Route                        = $packageDispatch->Route;
                    $packageInbound->Name                         = $packageDispatch->Name;
                    $packageInbound->idUser                       = Session::get('user')->id;
                    $packageInbound->reInbound                    = 1; 
                    $packageInbound->status                       = 'Inbound';

                    $packageInbound->save();

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
                    $packageHistory->Description                  = 'Re-Inbound - for: '. Session::get('user')->name .' '. Session::get('user')->nameOfOwner;
                    $packageHistory->inbound                      = 1;
                    $packageHistory->status                       = 'ReInbound';

                    $packageHistory->save();

                    $packageDispatch->delete();

                    DB::commit();

                    return ['stateAction' => true];
                }
                catch(Exception $e)
                {
                    DB::rollback();

                    return ['stateAction' => false];
                }
            }

            return ['stateAction' => 'notUser'];
        }

        return ['stateAction' => 'notDispatch'];
    }
}