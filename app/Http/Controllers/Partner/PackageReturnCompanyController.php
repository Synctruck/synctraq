<?php
namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{Company, Configuration, PackageDelivery, PackageDispatch, PackageHistory, PackageInbound, PackageManifest, PackageNotExists, PackageReturn, PackageReturnCompany, TeamRoute, User};

use Illuminate\Support\Facades\Validator;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PhpOffice\PhpOfficePhpSpreadsheetSpreadsheet;
use PhpOffice\PhpOfficePhpSpreadsheetReaderCsv;
use PhpOffice\PhpOfficePhpSpreadsheetReaderXlsx;

use DB;
use Illuminate\Support\Facades\Auth;
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

    public function Index()
    {
        return view('partner.report.returncompany');
    }

    public function List($dateInit, $dateEnd, $route, $state)
    {

        $roleUser = Auth::user()->role->name;


        $packageReturnCompanyList = $this->getDataReturn($dateInit, $dateEnd, $route, $state);

        $quantityReturn = $packageReturnCompanyList->total();

        $listState = PackageReturnCompany::select('Dropoff_Province')
                                    ->groupBy('Dropoff_Province')
                                    ->get();

        return ['packageReturnCompanyList' => $packageReturnCompanyList, 'listState' => $listState, 'quantityReturn' => $quantityReturn, 'roleUser' => $roleUser];
    }

    private function getDataReturn($dateInit, $dateEnd, $route, $state,$type='list')
    {
        $dateInit = $dateInit .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';
        $routes   = explode(',', $route);
        $states   = explode(',', $state);

        $packageReturnCompanyList = PackageReturnCompany::whereBetween('created_at', [$dateInit, $dateEnd])->where('idCompany',Auth::guard('partner')->user()->id);

        if($route != 'all')
        {
            $packageReturnCompanyList = $packageReturnCompanyList->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $packageReturnCompanyList = $packageReturnCompanyList->whereIn('Dropoff_Province', $states);
        }

        if($type=='list'){
            $packageReturnCompanyList = $packageReturnCompanyList->paginate(50);
        }
        else{
            $packageReturnCompanyList = $packageReturnCompanyList->get();
        }

        return $packageReturnCompanyList;
    }


    public function Export($dateInit, $dateEnd, $route, $state)
    {
        $delimiter = ",";
        $filename = "Report Return Company " . date('Y-m-d H:i:s') . ".csv";

        //create a file pointer
        $file = fopen('php://memory', 'w');

        //set column headers
        $fields = array('DATE', 'HOUR', 'COMPANY', 'PACKAGE ID', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'ROUTE', 'DESCRIPTION RETURN', 'CLIENT', 'WEIGHT', 'MEASURES');

        fputcsv($file, $fields, $delimiter);

        $listPackageReturnCompany = $this->getDataReturn($dateInit, $dateEnd, $route, $state,$type='export');

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

        fseek($file, 0);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '";');

        fpassthru($file);
    }



}
