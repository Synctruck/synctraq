<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{ ToDeductLostPackages };

use Illuminate\Support\Facades\Validator;

use Auth;
use DateTime;
use DB;
use Session;

class ToDeductLostPackagesController extends Controller
{
    public function Index()
    {
        return view('to-deduct-lost.index');
    }

    public function List()
    {
        $totalDeducts             = ToDeductLostPackages::get()->sum('priceToDeduct');
        $toDeductLostPackagesList = ToDeductLostPackages::orderBy('created_at', 'desc')->paginate(50);

        return [
            'totalDeducts' => number_format($totalDeducts, 4),
            'toDeductLostPackagesList' => $toDeductLostPackagesList,
        ];
    }

    public function UpdateDeductPrice($shipmentId, $priceToDeduct)
    {
        $toDeductLostPackage = ToDeductLostPackages::find($shipmentId);
        $toDeductLostPackage->priceToDeduct = $priceToDeduct;
        $toDeductLostPackage->save();

        return ['statusCode' => true];
    }
}