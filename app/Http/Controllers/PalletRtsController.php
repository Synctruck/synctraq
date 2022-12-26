<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{ PalletRts };

use Illuminate\Support\Facades\Validator;

use Auth;
use DB;
use PDF;
use Session;

class PalletRtsController extends Controller
{
    public function List($dateStart, $dateEnd)
    {
        $palletList = PalletRts::orderBy('created_at', 'desc')->paginate(50);
        
        return ['palletList' => $palletList];
    }

    public function Insert(Request $request)
    {
        $validator = Validator::make($request->all(),

            [
                "Route" => ["required"],
            ],
            [
                "Route.required" => "You must select a route",
            ]
        );

        if($validator->fails())
        {
            return response()->json(["status" => 422, "errors" => $validator->errors()], 422);
        }

        $pallet = new PalletRts();

        $pallet->number = date('YmdHis') .'-'. Auth::user()->id;
        $pallet->idUser = Auth::user()->id;
        $pallet->Route  = $request->get('Route');
        $pallet->status = 'Opened';

        $pallet->save();

        return ['stateAction' => true];
    }

    public function Print($numberPallet)
    {
        $pallet = PalletRts::find($numberPallet);

        $pdf = PDF::loadView('pdf.numberpallet', ['pallet' => $pallet]);
                    
        $pdf->setPaper('A4');

        return $pdf->stream('NUMBER PALLET.pdf');
    }
}