<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{ Company, PalletRts, PackageReturnCompany };

use Illuminate\Support\Facades\Validator;

use Auth;
use DB;
use PDF;
use Session;

class PalletRtsController extends Controller
{
    public function List($dateStart, $dateEnd)
    {
        $palletList = $this->GetData($dateStart, $dateEnd, 'list');
        
        return ['palletList' => $palletList];
    }

    public function Export($dateStart, $dateEnd)
    {
        $delimiter = ",";
        $filename = "PALLET - PRE - DISPATCH" . date('Y-m-d H:i:s') . ".csv";

        //create a file pointer
        $file = fopen('php://memory', 'w');

        //set column headers
        $fields = array('DATE', 'HOUR', 'PALLET ID', 'COMPANY', 'QUANTIY PACKAGE', 'STATUS');

        fputcsv($file, $fields, $delimiter);

        $palletList = $this->GetData($dateStart, $dateEnd, 'export');

        foreach($palletList as $pallet)
        {
            $lineData = array(
                                date('m-d-Y', strtotime($pallet->created_at)),
                                date('H:i:s', strtotime($pallet->created_at)),
                                $pallet->number,
                                $pallet->company,
                                $pallet->quantityPackage,
                                $pallet->status,
                            );

            fputcsv($file, $lineData, $delimiter);

            $listPackage = PackageReturnCompany::where('numberpallet', $pallet->number)->get();

            if(count($listPackage) > 0)
            {
                $fields = array('', '', 'PACKAGE ID', 'CLIENTE', 'CONTACT', 'ROUTE');

                fputcsv($file, $fields, $delimiter);

                foreach($listPackage as $package)
                {
                    $lineData = array(
                                '',
                                '',
                                $package->Reference_Number_1,
                                $package->Dropoff_Contact_Name,
                                $package->Dropoff_Contact_Phone_Number,
                                $package->Route,
                            );

                    fputcsv($file, $lineData, $delimiter);
                }
            }
            
            $lineData = array('', '', '', '', '', '');

            fputcsv($file, $lineData, $delimiter);
        }

        fseek($file, 0);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '";');

        fpassthru($file);
    }

    public function GetData($dateStart, $dateEnd, $type)
    {
        $dateStart = $dateStart .' 00:00:00';
        $dateEnd   = $dateEnd .' 23:59:59';

        $palletList = PalletRts::whereBetween('created_at', [$dateStart, $dateEnd])
                            ->orderBy('created_at', 'desc');


        if($type == 'list')
        {
            $palletList = $palletList->paginate(50);
        }
        else
        {
            $palletList = $palletList->get();
        }
        
        return $palletList;
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