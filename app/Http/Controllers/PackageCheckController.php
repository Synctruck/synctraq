<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{Driver, PackageHistory, PackageDispatch, PackageInbound, PackageManifest, PackageNotExists, PackageReturn, TeamRoute, User};

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
}