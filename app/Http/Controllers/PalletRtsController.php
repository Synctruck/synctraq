<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{ Company, PalletRts };

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
                "idCompany" => ["required"],
            ],
            [
                "idCompany.required" => "You must select a company",
            ]
        );

        if($validator->fails())
        {
            return response()->json(["status" => 422, "errors" => $validator->errors()], 422);
        }

        $company = Company::find($request->get('idCompany'));

        $pallet = new PalletRts();

        $pallet->number    = date('YmdHis') .'-'. Auth::user()->id;
        $pallet->idUser    = Auth::user()->id;
        $pallet->idCompany = $company->id;
        $pallet->company   = $company->name;
        $pallet->status    = 'Opened';

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