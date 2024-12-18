<?php

namespace App\Http\Controllers;

use App\Models\PackageHistory;
use Illuminate\Http\Request;

class Trackcontroller extends Controller
{
    public function Index(Request $request)
    {
        $textSearch = $request->get('textSearch');

        return view('track.index', compact('textSearch'));
    }

    public function trackDetail(Request $request,$package_id){

        $packageHistoryList = PackageHistory::where('Reference_Number_1', $package_id)
                                            ->whereIn('status',['Manifest','Inbound','Dispatch','Delivery'])
                                            ->groupBy('status')
                                            ->orderBy('created_at','DESC')->get();

        return [
            "details" =>$packageHistoryList
        ];
    }
}
