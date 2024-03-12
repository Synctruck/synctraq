<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Controllers\{ PackageDispatchController, PackagePriceCompanyTeamController };

use App\Http\Controllers\Api\{ PackageController };

use App\Service\ServicePackageTerminal;

use App\Models\{
                    Comment, Company, Configuration, PackageBlocked, PackageDelivery, PackageNeedMoreInformation,
                    PackageDispatch, PackageHistory, PackageInbound, PalletRts, PackageLost,
                    PackageManifest, PackageNotExists, PackageFailed, PackagePreDispatch, PackageReturn, PalletPreRtsDispatch, 
                    PackageReturnCompany, PackageWarehouse, TeamRoute, User };

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use DateTime;
use DB;
use Log;
use Session;

class PackageReturnCompanyController extends Controller
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

    public function Index(Request $request)
    {
        $Reference_Number = $request->get('Reference_Number');

        return view('report.indexreturncompany', compact('Reference_Number'));
    }

    public function List($dateInit, $dateEnd, $idCompany, $route, $state)
    {
        $roleUser = Auth::user()->role->name;

        $packageReturnCompanyList = $this->getDataReturn($dateInit, $dateEnd, $idCompany, $route, $state, 'ReturnCompany');

        $quantityReturn = $packageReturnCompanyList->total();

        $listState = PackageReturnCompany::select('Dropoff_Province')
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        return ['packageReturnCompanyList' => $packageReturnCompanyList, 'listState' => $listState, 'quantityReturn' => $quantityReturn, 'roleUser' => $roleUser];
    }

    private function getDataReturn($dateInit, $dateEnd, $idCompany, $route, $state, $status, $type = 'list')
    {
        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';
        $routes   = explode(',', $route);
        $states   = explode(',', $state);

        $packageReturnCompanyList = PackageReturnCompany::whereBetween('created_at', [$dateInit, $dateEnd]);

        if($route != 'all')
        {
            $packageReturnCompanyList = $packageReturnCompanyList->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $packageReturnCompanyList = $packageReturnCompanyList->whereIn('Dropoff_Province', $states);
        }

        if($idCompany)
        {
            $packageReturnCompanyList = $packageReturnCompanyList->where('idCompany', $idCompany);
        }

        if($type=='list')
        {
            $packageReturnCompanyList = $packageReturnCompanyList->select(
                                                                    'created_at',
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
                                                                    'Description_Return',
                                                                    'client',
                                                                    'Weight',
                                                                    'Width',
                                                                    'Length',
                                                                    'Height'
                                                                )
                                                                ->orderBy('created_at', 'desc')
                                                                ->paginate(50);
        }
        else
        {
            $packageReturnCompanyList = $packageReturnCompanyList->get();
        }

        return $packageReturnCompanyList;
    }

    public function Insert(Request $request)
    {
        $servicePackageTerminal = new ServicePackageTerminal();

        /*$comment = Comment::where('description', $request->get('Description_Return'))->first();

        if(!$comment)
        {
            return ['stateAction' => 'commentNotExists'];
        }*/

        $packageBlocked = PackageBlocked::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();

        if($packageBlocked)
        {
            return ['stateAction' => 'validatedFilterPackage', 'packageBlocked' => $packageBlocked, 'packageManifest' => null];
        }

        $packageLost = PackageLost::find($request->get('Reference_Number_1'));

        if($packageLost)
        {
            return ['stateAction' => 'validatedLost'];
        }

        $packageInbound = PackageInbound::find($request->get('Reference_Number_1'));

        if($packageInbound == null)
        {
            $packageInbound = PackageWarehouse::find($request->get('Reference_Number_1'));
        }

        if($packageInbound == null)
        {
            $packageInbound = PackageDispatch::find($request->get('Reference_Number_1'));
        }

        if($packageInbound == null)
        {
            $packageInbound = PackageNeedMoreInformation::find($request->get('Reference_Number_1'));
        }

        if(!$packageInbound)
        {
            $packageInbound = $servicePackageTerminal->Get($request->get('Reference_Number_1'));
        }

        if($packageInbound)
        {
            try
            {
                DB::beginTransaction();

                $packageHistory = PackageHistory::where('Reference_Number_1', $packageInbound->Reference_Number_1)
                                                ->where('status', 'Manifest')
                                                ->first();

                $company = Company::find($packageHistory->idCompany);

                $packageReturnCompany = new PackageReturnCompany();

                $packageReturnCompany->idCompany                    = $packageInbound->idCompany;
                $packageReturnCompany->company                      = $packageInbound->company;
                $packageReturnCompany->Reference_Number_1           = $packageInbound->Reference_Number_1;
                $packageReturnCompany->Dropoff_Contact_Name         = $packageInbound->Dropoff_Contact_Name;
                $packageReturnCompany->Dropoff_Company              = $packageInbound->Dropoff_Company;
                $packageReturnCompany->Dropoff_Contact_Phone_Number = $packageInbound->Dropoff_Contact_Phone_Number;
                $packageReturnCompany->Dropoff_Contact_Email        = $packageInbound->Dropoff_Contact_Email;
                $packageReturnCompany->Dropoff_Address_Line_1       = $packageInbound->Dropoff_Address_Line_1;
                $packageReturnCompany->Dropoff_Address_Line_2       = $packageInbound->Dropoff_Address_Line_2;
                $packageReturnCompany->Dropoff_City                 = $packageInbound->Dropoff_City;
                $packageReturnCompany->Dropoff_Province             = $packageInbound->Dropoff_Province;
                $packageReturnCompany->Dropoff_Postal_Code          = $packageInbound->Dropoff_Postal_Code;
                $packageReturnCompany->Notes                        = $packageInbound->Notes;
                $packageReturnCompany->Weight                       = $packageHistory->Weight;
                $packageReturnCompany->Width                        = $packageHistory->width;
                $packageReturnCompany->Length                       = $packageHistory->length;
                $packageReturnCompany->Height                       = $packageHistory->height;
                $packageReturnCompany->Route                        = $packageInbound->Route;
                $packageReturnCompany->idUser                       = Auth::user()->id;
                $packageReturnCompany->Date_Return                  = date('Y-m-d H:i:s');
                $packageReturnCompany->Description_Return           = $request->get('Description_Return');
                $packageReturnCompany->client                       = $packageInbound->Dropoff_Contact_Name;
                $packageReturnCompany->statusSending                = 'scan_in_for_return';

                if($company->twoAttempts)
                {
                    $packageHistoryDispatchList = PackageHistory::where('Reference_Number_1', $request->Reference_Number_1)
                                                    ->where('status', 'Dispatch')
                                                    ->where('idCompany', $company->id)
                                                    ->orderBy('created_at', 'asc')
                                                    ->get();

                    if(count($packageHistoryDispatchList) > 1)
                    {
                        $hourDifference = $this->CalculateHourDifferenceDispatch($packageHistoryDispatchList);

                        if($hourDifference >= 6)
                        {
                            $packageReturnCompany->invoice = 1;
                        }
                    }
                }

                $packageHistory = PackageHistory::where('Reference_Number_1', $request->Reference_Number_1)
                                                        ->where('status', 'Dispatch')
                                                        ->orderBy('created_at', 'asc')
                                                        ->get()
                                                        ->last();
                                                        
                if($packageHistory)
                {
                    $team = User::find($packageHistory->idTeam);

                    if($team && $team->twoAttempts)
                    {
                        Log::info('packageHistory');
                        Log::info($packageHistory->idTeam);
                        $packageHistoryDispatchListTeam = PackageHistory::where('Reference_Number_1', $request->Reference_Number_1)
                                                                        ->where('status', 'Dispatch')
                                                                        ->where('idTeam', $team->id)
                                                                        ->orderBy('created_at', 'asc')
                                                                        ->get();

                        if(count($packageHistoryDispatchListTeam) > 1)
                        {
                            $hourDifference = $this->CalculateHourDifferenceDispatch($packageHistoryDispatchListTeam);

                            if($hourDifference >= 6)
                            {
                                $packageReturnCompany->paid   = 1;
                                $packageReturnCompany->idTeam = $team->id; 
                            }
                        }
                    }
                }

                $packageReturnCompany->status     = 'PreRts';
                $packageReturnCompany->created_at = date('Y-m-d H:i:s');
                $packageReturnCompany->updated_at = date('Y-m-d H:i:s');

                $packageReturnCompany->save(); 

                //regsister history

                $packageHistory = new PackageHistory(); 

                $packageHistory->id                           = uniqid();
                $packageHistory->Reference_Number_1           = $packageInbound->Reference_Number_1;
                $packageHistory->idCompany                    = $packageInbound->idCompany;
                $packageHistory->company                      = $packageInbound->company;
                $packageHistory->Dropoff_Contact_Name         = $packageInbound->Dropoff_Contact_Name;
                $packageHistory->Dropoff_Company              = $packageInbound->Dropoff_Company;
                $packageHistory->Dropoff_Contact_Phone_Number = $packageInbound->Dropoff_Contact_Phone_Number;
                $packageHistory->Dropoff_Contact_Email        = $packageInbound->Dropoff_Contact_Email;
                $packageHistory->Dropoff_Address_Line_1       = $packageInbound->Dropoff_Address_Line_1;
                $packageHistory->Dropoff_Address_Line_2       = $packageInbound->Dropoff_Address_Line_2;
                $packageHistory->Dropoff_City                 = $packageInbound->Dropoff_City;
                $packageHistory->Dropoff_Province             = $packageInbound->Dropoff_Province;
                $packageHistory->Dropoff_Postal_Code          = $packageInbound->Dropoff_Postal_Code;
                $packageHistory->Notes                        = $packageInbound->Notes;
                $packageHistory->Weight                       = $packageInbound->Weight;
                $packageHistory->Route                        = $packageInbound->Route;
                $packageHistory->idUser                       = Auth::user()->id;
                $packageHistory->idUserInbound                = Auth::user()->id;
                $packageHistory->Date_Inbound                 = date('Y-m-d H:s:i');
                $packageHistory->Description                  = 'SCAN IN FOR RETURN - for: user ('. Auth::user()->email .')';
                $packageHistory->Description_Return           = $request->get('Description_Return');
                $packageHistory->status                       = 'PreRts';
                $packageHistory->actualDate                   = date('Y-m-d H:i:s');
                $packageHistory->created_at                   = date('Y-m-d H:i:s');
                $packageHistory->updated_at                   = date('Y-m-d H:i:s');

                $packageHistory->save();

                Log::info('PreRts: send status: scan_in_for_return');

                $packageInbound['latitude']            = $request->get('latitude');
                $packageInbound['longitude']           = $request->get('longitude');
                $packageInbound['Description_Return']  = $request->get('Description_Return');

                $packageController = new PackageController();
                $packageController->SendStatusToInland($packageInbound, 'ReturnCompany', 'scan_in_for_return', date('Y-m-d H:i:s'));

                $packageHistory = PackageHistory::where('Reference_Number_1', $packageInbound->Reference_Number_1)
                                                ->where('sendToInland', 1)
                                                ->where('status', 'Manifest')
                                                ->first();

                if($packageHistory)
                {
                    $packageController->SendStatusToOtherCompany($packageInbound, 'ReturnCompany', 'scan_in_for_return', date('Y-m-d H:i:s'));
                }

                $packageInbound->delete();

                DB::commit();

                return ['stateAction' => true];
            }
            catch(Exception $e)
            {
                DB::rollback();

                return ['stateAction' => false];
            }
        }

        return ['stateAction' => 'notExists'];
    }

    public function CalculateHourDifferenceDispatch($packageHistoryDispatchList)
    {
        $oneDispatch = $packageHistoryDispatchList[0];
        $twoDispatch = $packageHistoryDispatchList[count($packageHistoryDispatchList) - 1];

        $dateInit = new DateTime($oneDispatch->created_at);
        $dateEnd  = new DateTime($twoDispatch->created_at);

        $interval = $dateInit->diff($dateEnd);

        return (int)$interval->format('%H');
    }

    public function Import(Request $request)
    {
        $file = $request->file('file');

        $file->move(public_path() .'/file-import', 'returncompany.csv');

        $handle = fopen(public_path('file-import/returncompany.csv'), "r");

        try
        {
            DB::beginTransaction();

            while (($raw_string = fgets($handle)) !== false)
            {
                $row = str_getcsv($raw_string);

                $Reference_Number_1 = $row[0];
                $packageHistory     = PackageHistory::where('Reference_Number_1', $Reference_Number_1)->get();

                if(count($packageHistory) > 0)
                {
                    $packageReturnCompany = PackageReturnCompany::where('status', 'ReturnCompany')
                                                                ->where('Reference_Number_1', $Reference_Number_1)
                                                                ->first();

                    if(1)
                    {
                        $packageInbound = PackageManifest::find($Reference_Number_1);

                        if($packageInbound == null)
                        {
                            $packageInbound = PackageInbound::find($Reference_Number_1);
                        }

                        if($packageInbound == null)
                        {
                            $packageInbound = PackageWarehouse::find($Reference_Number_1);
                        }

                        if($packageInbound == null)
                        {
                            $packageInbound = PackageDispatch::find($Reference_Number_1);
                        }

                        if($packageInbound == null)
                        {
                            $packageInbound = PackageFailed::find($Reference_Number_1);
                        }

                        if($packageInbound == null)
                        {
                            $packageInbound = PackageLost::find($Reference_Number_1);
                        }

                        if($packageInbound == null)
                        {
                            $packageInbound = PackagePreDispatch::find($Reference_Number_1);
                        }

                        if($packageInbound == null)
                        {
                            $packageInbound = PackageReturnCompany::where('Reference_Number_1', $Reference_Number_1)->first();
                        }

                        if($packageInbound)
                        {
                            if($packageInbound->status == 'PreRts')
                            {
                                $packageInbound->status = 'ReturnCompany';

                                $packageInbound->save();
                            }
                            else if($packageInbound->status != 'ReturnCompany')
                            {
                                $description = $row[1];
                                $Weight      = 0;
                                $Width       = 0;
                                $Length      = 0;
                                $Height      = 0;
                                $created_at  = date('Y-m-d H:i:s', strtotime($row[2]));

                                $company = Company::find($packageInbound->idCompany);

                                $packageReturnCompany = new PackageReturnCompany();

                                $packageReturnCompany->idCompany                    = $packageInbound->idCompany;
                                $packageReturnCompany->company                      = $packageInbound->company;
                                $packageReturnCompany->Reference_Number_1           = $packageInbound->Reference_Number_1;
                                $packageReturnCompany->Dropoff_Contact_Name         = $packageInbound->Dropoff_Contact_Name;
                                $packageReturnCompany->Dropoff_Company              = $packageInbound->Dropoff_Company;
                                $packageReturnCompany->Dropoff_Contact_Phone_Number = $packageInbound->Dropoff_Contact_Phone_Number;
                                $packageReturnCompany->Dropoff_Contact_Email        = $packageInbound->Dropoff_Contact_Email;
                                $packageReturnCompany->Dropoff_Address_Line_1       = $packageInbound->Dropoff_Address_Line_1;
                                $packageReturnCompany->Dropoff_Address_Line_2       = $packageInbound->Dropoff_Address_Line_2;
                                $packageReturnCompany->Dropoff_City                 = $packageInbound->Dropoff_City;
                                $packageReturnCompany->Dropoff_Province             = $packageInbound->Dropoff_Province;
                                $packageReturnCompany->Dropoff_Postal_Code          = $packageInbound->Dropoff_Postal_Code;
                                $packageReturnCompany->Notes                        = $packageInbound->Notes;
                                $packageReturnCompany->Weight                       = $Weight;
                                $packageReturnCompany->Width                        = $Width;
                                $packageReturnCompany->Length                       = $Length;
                                $packageReturnCompany->Height                       = $Height;
                                $packageReturnCompany->Route                        = $packageInbound->Route;
                                $packageReturnCompany->idUser                       = Auth::user()->id;
                                $packageReturnCompany->Date_Return                  = $created_at;
                                $packageReturnCompany->Description_Return           = $description;
                                $packageReturnCompany->surcharge                    = $row[3] == 'YES' ? 1 : 0;
                                $packageReturnCompany->status                       = 'ReturnCompany';
                                $packageReturnCompany->created_at                   = $created_at;
                                $packageReturnCompany->updated_at                   = $created_at;

                                $packageReturnCompany->save();

                                $packageHistory = new PackageHistory();

                                $packageHistory->id                           = uniqid();
                                $packageHistory->Reference_Number_1           = $packageInbound->Reference_Number_1;
                                $packageHistory->idCompany                    = $packageInbound->idCompany;
                                $packageHistory->company                      = $packageInbound->company;
                                $packageHistory->Dropoff_Contact_Name         = $packageInbound->Dropoff_Contact_Name;
                                $packageHistory->Dropoff_Company              = $packageInbound->Dropoff_Company;
                                $packageHistory->Dropoff_Contact_Phone_Number = $packageInbound->Dropoff_Contact_Phone_Number;
                                $packageHistory->Dropoff_Contact_Email        = $packageInbound->Dropoff_Contact_Email;
                                $packageHistory->Dropoff_Address_Line_1       = $packageInbound->Dropoff_Address_Line_1;
                                $packageHistory->Dropoff_Address_Line_2       = $packageInbound->Dropoff_Address_Line_2;
                                $packageHistory->Dropoff_City                 = $packageInbound->Dropoff_City;
                                $packageHistory->Dropoff_Province             = $packageInbound->Dropoff_Province;
                                $packageHistory->Dropoff_Postal_Code          = $packageInbound->Dropoff_Postal_Code;
                                $packageHistory->Notes                        = $packageInbound->Notes;
                                $packageHistory->Weight                       = $packageInbound->Weight;
                                $packageHistory->Route                        = $packageInbound->Route;
                                $packageHistory->idUser                       = Auth::user()->id;
                                $packageHistory->idUserInbound                = Auth::user()->id;
                                $packageHistory->Date_Inbound                 = date('Y-m-d H:s:i');
                                $packageHistory->Description                  = 'Return Company - Import - for: user ('. Auth::user()->email .')';
                                $packageHistory->Description_Return           = $description;
                                $packageHistory->status                       = 'ReturnCompany';
                                $packageHistory->actualDate                   = $created_at;
                                $packageHistory->created_at                   = $created_at;
                                $packageHistory->updated_at                   = $created_at;

                                $packageHistory->save();

                                $packageController = new PackageController();
                                $packageController->SendStatusToInland($packageInbound, 'ReturnCompany', null, date('Y-m-d H:i:s'));

                                $packageHistory = PackageHistory::where('Reference_Number_1', $packageInbound->Reference_Number_1)
                                                ->where('sendToInland', 1)
                                                ->where('status', 'Manifest')
                                                ->first();

                                if($packageHistory)
                                {
                                    $packageController->SendStatusToOtherCompany($packageInbound, 'ReturnCompany', null, date('Y-m-d H:i:s'));
                                }
                
                                $packageInbound->delete();
                            }

                            if($row[3] == 'YES')
                            {
                                //create or update price company
                                $packagePriceCompanyTeamController = new PackagePriceCompanyTeamController();
                                $packagePriceCompanyTeamController->Insert($packageInbound, 'old');
                            }
                        }
                    }
                }
            }

            DB::commit();

            return ['stateAction' => true];
        }
        catch(Exception $e)
        {
            DB::rollback();
        }
    }

    public function Export($dateInit, $dateEnd, $idCompany, $route, $state, $typeExport)
    {
        $delimiter = ",";
        $filename  = $typeExport == 'download' ? "Report Return Company " . date('Y-m-d H:i:s') . ".csv" : Auth::user()->id ."- Report Return Company.csv";
        $file      = $typeExport == 'download' ? fopen('php://memory', 'w') : fopen(public_path($filename), 'w');

        //set column headers
        $fields = array('DATE', 'HOUR', 'COMPANY', 'PACKAGE ID', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'ROUTE', 'DESCRIPTION RETURN', 'CLIENT', 'WEIGHT', 'MEASURES');

        fputcsv($file, $fields, $delimiter);

        $listPackageReturnCompany = $this->getDataReturn($dateInit, $dateEnd, $idCompany, $route, $state, 'ReturnCompany', $type = 'export');

        foreach($listPackageReturnCompany as $packageReturnCompany)
        {
            $lineData = array(
                                date('m-d-Y', strtotime($packageReturnCompany->created_at)),
                                date('H:i:s', strtotime($packageReturnCompany->created_at)),
                                $packageReturnCompany->company,
                                $packageReturnCompany->Reference_Number_1,
                                $packageReturnCompany->Dropoff_Contact_Name,
                                $packageReturnCompany->Dropoff_Contact_Phone_Number,
                                $packageReturnCompany->Dropoff_Address_Line_1,
                                $packageReturnCompany->Dropoff_City,
                                $packageReturnCompany->Dropoff_Province,
                                $packageReturnCompany->Dropoff_Postal_Code,
                                $packageReturnCompany->Route,
                                $packageReturnCompany->Description_Return,
                                $packageReturnCompany->client,
                                $packageReturnCompany->Weight,
                                $packageReturnCompany->measures,
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

            SendGeneralExport('Report Return Company', $filename);

            return ['stateAction' => true];
        }
    }

    public function IndexPreRts()
    {
        return view('package.prerts');
    }

    public function ListPreRts($numberPallet)
    {
        $palletRts         = PalletRts::find($numberPallet);
        $packagePreRtsList = PackageReturnCompany::where('numberPallet', $numberPallet);

        $packagePreRtsList = $packagePreRtsList->select(
                                                    'created_at',
                                                    'company',
                                                    'Reference_Number_1',
                                                    'Description_Return',
                                                    'client',
                                                    'Weight',
                                                    'Width',
                                                    'Length',
                                                    'Height',
                                                    'Route',
                                                    'Reference_Number_1_Duplicate',
                                                )
                                                ->orderBy('updated_at', 'desc')
                                                ->get();


        return ['packagePreRtsList' => $packagePreRtsList, 'palletRts' => $palletRts];
    }

    public function InsertPreRts(Request $request)
    {
        $servicePackageTerminal = new ServicePackageTerminal();

        $packageBlocked = PackageBlocked::where('Reference_Number_1', $request->get('Reference_Number_1'))->first();

        if($packageBlocked)
        {
            return ['stateAction' => 'validatedFilterPackage', 'packageBlocked' => $packageBlocked, 'packageManifest' => null];
        }

        $packageLost = PackageLost::find($request->get('Reference_Number_1'));

        if($packageLost)
        {
            return ['stateAction' => 'validatedLost'];
        }

        /*$packageInbound = PackageInbound::find($request->get('Reference_Number_1'));

        if($packageInbound == null)
        {
            $packageInbound = PackageWarehouse::find($request->get('Reference_Number_1'));
        }

        if($packageInbound == null)
        {
            $packageInbound = PackageDispatch::find($request->get('Reference_Number_1'));
        }

        if($packageInbound == null)
        {
            $packageInbound = PackageReturnCompany::find($request->get('Reference_Number_1'));

            if($packageInbound)
            {
                return ['stateAction' => ($packageInbound->status == 'PreRts' ? 'packageInRts' : 'packageReturnCompany')];
            }
        }

        if(!$packageInbound)
        {
            $packageInbound = $servicePackageTerminal->Get($request->get('Reference_Number_1'));
        }*/

        $packageReturnCompany = PackageReturnCompany::find($request->get('Reference_Number_1'));

        if($packageReturnCompany)
        {
            if($packageReturnCompany->statusSending == 'scan_in_for_return' && $packageReturnCompany->status == 'PreRts')
            {
                $palletRts = PalletRts::find($request->get('numberPallet'));

                if($palletRts->idCompany != $packageReturnCompany->idCompany)
                {
                    return ['stateAction' => 'notCompany'];
                }

                try
                {
                    DB::beginTransaction();

                    $palletRts->quantityPackage = $packageReturnCompany->numberPallet == '' ? $palletRts->quantityPackage + 1 : $palletRts->quantityPackage;
                    $palletRts->save();

                    $packageReturnCompany->numberPallet = $request->get('numberPallet');
                    $packageReturnCompany->updated_at = date('Y-m-d H:i:s');
                    $packageReturnCompany->save();

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

        $packageHistory = PackageHistory::where('Reference_Number_1', $request->get('Reference_Number_1'))
                                        ->where('status', 'Manifest')
                                        ->first();

        if(!$packageHistory){
            return ['stateAction' => 'notExistsManifest'];
        }
        /*else
        {
            $packageReturnCompany = $packageInbound = PackageReturnCompany::where('Reference_Number_1', $request->get('Reference_Number_1'))
                                            ->where('statusSending', 'scan_in_for_return')
                                            ->first();

            if($packageReturnCompany)
            {
                return ['stateAction' => 'validatedReturnCompany', 'packageInbound' => $packageReturnCompany];
            }
        }*/

        return ['stateAction' => 'notExists'];
    }

    public function InsertPreRtsExtra(Request $request)
    {
        $packageReturnCompany = PackageReturnCompany::where('Reference_Number_1_Duplicate', $request->Reference_Number_1)->get();

        $Reference_Number_1 = '';

        if(count($packageReturnCompany) == 0)
            $Reference_Number_1 = $request->Reference_Number_1 .'-1';
        else
        {
            $nextReference = count($packageReturnCompany) + 1;
            $Reference_Number_1 = $request->Reference_Number_1 .'-'. $nextReference; 
        }

        $packageReturnCompany = new PackageReturnCompany();
        $packageReturnCompany->Reference_Number_1 = $Reference_Number_1;
        $packageReturnCompany->numberPallet = $request->get('numberPallet');
        $packageReturnCompany->created_at = date('Y-m-d H:i:s');
        $packageReturnCompany->updated_at = date('Y-m-d H:i:s');
        $packageReturnCompany->Description_Return = 'Out of range';
        $packageReturnCompany->idUser = Auth::user()->id;
        $packageReturnCompany->Reference_Number_1_Duplicate = $request->Reference_Number_1;
        $packageReturnCompany->statusSending = 'scan_in_for_return';
        $packageReturnCompany->status = 'PreRts';
        $packageReturnCompany->save();

        return ['stateAction' => true];
    }

    public function ClosePallet(Request $request)
    {
        $palletRts = PalletRts::find($request->get('numberPallet'));
        $palletRts->status = 'Closed';
        $palletRts->save();

        return ['stateAction' => true];
    }

    public function UpdateCreatedAt()
    {
        try
        {
            DB::beginTransaction();

            $listPackageReturnCompany = PackageReturnCompany::all();

            foreach($listPackageReturnCompany as $packageReturnCompany)
            {
                $packageHistory = PackageHistory::where('Reference_Number_1', $packageReturnCompany->Reference_Number_1)
                                                ->where('status', 'ReturnCompany')
                                                ->first();


                $packageHistory->created_at = $packageReturnCompany->created_at;
                $packageHistory->updated_at = $packageReturnCompany->created_at;

                $packageHistory->save();
            }

            DB::commit();

            return ['stateAction' => true];
        }
        catch(Exception $e)
        {
            DB::rollback();

            return ['stateAction' => false];
        }
    }

    public function MoveToWarehouse($Reference_Number_1)
    {
        $packageReturnCompany = PackageReturnCompany::find($Reference_Number_1);

        if(!$packageReturnCompany) return false;

        try
        {
            DB::beginTransaction();

            $created_at = date('Y-m-d H:i:s');

            $packageWarehouse = new PackageWarehouse();

            $packageWarehouse->Reference_Number_1           = $packageReturnCompany->Reference_Number_1;
            $packageWarehouse->idCompany                    = $packageReturnCompany->idCompany;
            $packageWarehouse->company                      = $packageReturnCompany->company;
            $packageWarehouse->idStore                      = $packageReturnCompany->idStore;
            $packageWarehouse->store                        = $packageReturnCompany->store;
            $packageWarehouse->Dropoff_Contact_Name         = $packageReturnCompany->Dropoff_Contact_Name;
            $packageWarehouse->Dropoff_Company              = $packageReturnCompany->Dropoff_Company;
            $packageWarehouse->Dropoff_Contact_Phone_Number = $packageReturnCompany->Dropoff_Contact_Phone_Number;
            $packageWarehouse->Dropoff_Contact_Email        = $packageReturnCompany->Dropoff_Contact_Email;
            $packageWarehouse->Dropoff_Address_Line_1       = $packageReturnCompany->Dropoff_Address_Line_1;
            $packageWarehouse->Dropoff_Address_Line_2       = $packageReturnCompany->Dropoff_Address_Line_2;
            $packageWarehouse->Dropoff_City                 = $packageReturnCompany->Dropoff_City;
            $packageWarehouse->Dropoff_Province             = $packageReturnCompany->Dropoff_Province;
            $packageWarehouse->Dropoff_Postal_Code          = $packageReturnCompany->Dropoff_Postal_Code;
            $packageWarehouse->Notes                        = $packageReturnCompany->Notes;
            $packageWarehouse->Weight                       = $packageReturnCompany->Weight;
            $packageWarehouse->Route                        = $packageReturnCompany->Route;
            $packageWarehouse->idUser                       = Auth::user()->id;
            $packageWarehouse->quantity                     = $packageReturnCompany->quantity;
            $packageWarehouse->status                       = 'Warehouse';
            $packageWarehouse->save();

            $packageHistory = new PackageHistory();
            $packageHistory->id                           = uniqid();
            $packageHistory->Reference_Number_1           = $packageReturnCompany->Reference_Number_1;
            $packageHistory->idCompany                    = $packageReturnCompany->idCompany;
            $packageHistory->company                      = $packageReturnCompany->company;
            $packageHistory->idStore                      = $packageReturnCompany->idStore;
            $packageHistory->store                        = $packageReturnCompany->store;
            $packageHistory->Dropoff_Contact_Name         = $packageReturnCompany->Dropoff_Contact_Name;
            $packageHistory->Dropoff_Company              = $packageReturnCompany->Dropoff_Company;
            $packageHistory->Dropoff_Contact_Phone_Number = $packageReturnCompany->Dropoff_Contact_Phone_Number;
            $packageHistory->Dropoff_Contact_Email        = $packageReturnCompany->Dropoff_Contact_Email;
            $packageHistory->Dropoff_Address_Line_1       = $packageReturnCompany->Dropoff_Address_Line_1;
            $packageHistory->Dropoff_Address_Line_2       = $packageReturnCompany->Dropoff_Address_Line_2;
            $packageHistory->Dropoff_City                 = $packageReturnCompany->Dropoff_City;
            $packageHistory->Dropoff_Province             = $packageReturnCompany->Dropoff_Province;
            $packageHistory->Dropoff_Postal_Code          = $packageReturnCompany->Dropoff_Postal_Code;
            $packageHistory->Notes                        = $packageReturnCompany->Notes;
            $packageHistory->Weight                       = $packageReturnCompany->Weight;
            $packageHistory->Route                        = $packageReturnCompany->Route;
            $packageHistory->idUser                       = Auth::user()->id;
            $packageHistory->Description                  = '( RTS REMOVAL) For: '. Auth::user()->name .' '. Auth::user()->nameOfOwner;
            $packageHistory->quantity                     = $packageReturnCompany->quantity;
            $packageHistory->status                       = 'Warehouse';
            $packageHistory->actualDate                   = $created_at;
            $packageHistory->created_at                   = $created_at;
            $packageHistory->updated_at                   = $created_at;
            $packageHistory->save();

            $packageReturnCompany->delete();

            DB::commit();

            return ['stateAction' => true];
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            return response()->json(['message' => 'Error']);
        }
    }

    public function IndexDispatch()
    {
        $Reference_Number = '';

        return view('package.prerts-dispatch', compact('Reference_Number'));
    }

    public function ListTruck($startDate, $endDate)
    {
        $truckList = PalletPreRtsDispatch::whereBetween('created_at', [$startDate, $endDate])
                                        ->orderBy('created_at', 'desc')
                                        ->paginate(20);

        return ['truckList' => $truckList];
    }

    public function SearchTruck(Request $request)
    {
        $truckList = PalletPreRtsDispatch::where('bolNumber', 'like', '%'. $request->bolNumber .'%')->paginate(20);

        return ['truckList' => $truckList];
    }

    public function CreateTruck(Request $request)
    {
        $palletPreRtsDispatch = PalletPreRtsDispatch::find($request->bolNumber);

        if(!$palletPreRtsDispatch)
        {
            $palletPreRtsDispatch = new PalletPreRtsDispatch();
            $palletPreRtsDispatch->bolNumber = $request->bolNumber;
            $palletPreRtsDispatch->carrier = $request->carrier;
            $palletPreRtsDispatch->driver = $request->driver;
            $palletPreRtsDispatch->status = 'Pending';
            $palletPreRtsDispatch->idUser = Auth::user()->id;
            $palletPreRtsDispatch->save();

            return ['stateAction' => true, 'palletPreRtsDispatch' => $palletPreRtsDispatch];
        }
        
        return ['stateAction' => 'bolExists']; 
    }

    public function GetTruck($bolNumber)
    {
        $truck = PalletPreRtsDispatch::find($bolNumber);
        $palletList = PalletRts::where('bolNumber', $bolNumber)->get();

        return ['truck' => $truck, 'palletList' => $palletList];
    }

    public function InsertPalletToTruck(Request $request)
    {
        $palletRts = PalletRts::find($request->numberPallet);

        if($palletRts)
        {
            if($palletRts->bolNumber == '')
            {
                $palletRts->bolNumber = $request->bolNumber;
                $palletRts->idUserDispatch = Auth::user()->id;
                $palletRts->dispatchDate = date('Y-m-d H:i:s');
                $palletRts->save();

                return ['stateAction' => true];
            }

            return ['stateAction' => 'palletIdExistsOtherTruck', 'bolNumber' => $palletRts->bolNumber]; 
        }

        return ['stateAction' => 'notExists'];
    }

    public function CloseTruck(Request $request)
    {
        try
        {
            DB::beginTransaction();

            $truck = PalletPreRtsDispatch::find($request->bolNumber);
            $truck->DispatchDate = date('Y-m-d H:i:s');
            $truck->idUserDispatch = Auth::user()->id;
            $truck->status = 'Dispatched';
            $truck->save();

            $palletList = PalletRts::where('bolNumber', $request->bolNumber)->get();

            foreach($palletList as $pallet)
            {
                $pallet->dispatchDate = date('Y-m-d H:i:s');
                $pallet->idUserDispatch = Auth::user()->id;
                $pallet->status = 'Closed';
                $pallet->statusDispatch = 'Dispatched';
                $pallet->save();

                $packageRtsCompanyList = PackageReturnCompany::where('numberPallet', $pallet->number)->get();

                foreach($packageRtsCompanyList as $packagePreRts)
                {
                    $packagePreRts->statusSending = 'scan_out_for_return';
                    $packagePreRts->status = 'ReturnCompany';
                    $packagePreRts->save();

                    $packageHistory = new PackageHistory(); 
                    $packageHistory->id                           = uniqid();
                    $packageHistory->Reference_Number_1           = $packagePreRts->Reference_Number_1;
                    $packageHistory->idCompany                    = $packagePreRts->idCompany;
                    $packageHistory->company                      = $packagePreRts->company;
                    $packageHistory->Dropoff_Contact_Name         = $packagePreRts->Dropoff_Contact_Name;
                    $packageHistory->Dropoff_Company              = $packagePreRts->Dropoff_Company;
                    $packageHistory->Dropoff_Contact_Phone_Number = $packagePreRts->Dropoff_Contact_Phone_Number;
                    $packageHistory->Dropoff_Contact_Email        = $packagePreRts->Dropoff_Contact_Email;
                    $packageHistory->Dropoff_Address_Line_1       = $packagePreRts->Dropoff_Address_Line_1;
                    $packageHistory->Dropoff_Address_Line_2       = $packagePreRts->Dropoff_Address_Line_2;
                    $packageHistory->Dropoff_City                 = $packagePreRts->Dropoff_City;
                    $packageHistory->Dropoff_Province             = $packagePreRts->Dropoff_Province;
                    $packageHistory->Dropoff_Postal_Code          = $packagePreRts->Dropoff_Postal_Code;
                    $packageHistory->Notes                        = $packagePreRts->Notes;
                    $packageHistory->Weight                       = $packagePreRts->Weight;
                    $packageHistory->Route                        = $packagePreRts->Route;
                    $packageHistory->idUser                       = Auth::user()->id;
                    $packageHistory->idUserInbound                = Auth::user()->id;
                    $packageHistory->Date_Inbound                 = date('Y-m-d H:s:i');
                    $packageHistory->Description                  = 'SCAN OUT FOR RETURN - for: user ('. Auth::user()->email .')';
                    $packageHistory->Description_Return           = $pallet->number .'( '. $request->bolNumber .' )';
                    $packageHistory->status                       = 'ReturnCompany';
                    $packageHistory->actualDate                   = date('Y-m-d H:i:s');
                    $packageHistory->created_at                   = date('Y-m-d H:i:s');
                    $packageHistory->updated_at                   = date('Y-m-d H:i:s');
                    $packageHistory->save();

                    $packageController = new PackageController();
                    $packageController->SendStatusToInland($packagePreRts, 'ReturnCompany', 'scan_out_for_return', date('Y-m-d H:i:s'));

                    $packageHistory = PackageHistory::where('Reference_Number_1', $packagePreRts->Reference_Number_1)
                                                    ->where('sendToInland', 1)
                                                    ->where('status', 'Manifest')
                                                    ->first();

                    if($packageHistory)
                    {
                        $packageController->SendStatusToOtherCompany($packagePreRts, 'ReturnCompany', 'scan_out_for_return', date('Y-m-d H:i:s'));
                    }
                }
            }

            DB::commit();

            return ['stateAction' => true];
        }
        catch(Exception $e)
        {
            DB::rollback();

            return ['stateAction' => false];
        }
    }
}
