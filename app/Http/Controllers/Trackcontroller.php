<?php

namespace App\Http\Controllers;

use App\Models\PackageHistory;
use Illuminate\Http\Request;

class Trackcontroller extends Controller
{
    public function  index(){
        return view('track.index');
    }

    public function trackDetail(Request $request,$package_id){

        $packageHistoryList = PackageHistory::where('Reference_Number_1', $package_id)->whereIn('status',['On Hold','Inbound','Dispatch','Delivery'])->orderBy('created_at','DESC') ->get();

        return [
            "details" =>$packageHistoryList
        ];
    }
}
