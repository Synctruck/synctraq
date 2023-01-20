<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{PackageNotExists};

use Illuminate\Support\Facades\Validator;

use DB;
use Session;

class PackageNotExistsController extends Controller
{
    public function Index()
    {        
        return view('packagenotexists.index');
    }

    public function List(Request $request)
    {
        $packageListNotExists = PackageNotExists::orderBy('created_at', 'desc')->get();
        
        return ['packageListNotExists' => $packageListNotExists];
    }

    public function ExportExcel()
    {
        $filename = "Paquetes No Existentes " . date('Y-m-d H:i:s') . ".xls";

        
        $listPackageBotExists = PackageNotExists::orderBy('created_at', 'desc')->get();

        $exportData = "";
        $exportData .= "<table>";
        $exportData .= "<thead> <th>DATE</th><th>PACKAGE ID</th></thead>";

        foreach($listPackageBotExists as $packageNotExists)
        {
            $exportData .= "<tr> <td>". $packageNotExists->created_at ."</td> <td>". $packageNotExists->Reference_Number_1 ."</td></tr>";
        }

        $exportData .= "</table>";

    
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '";');
        header("Pragma: no-cache");
        header("Expires: 0");

        echo $exportData;
    }
}