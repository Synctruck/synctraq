<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Models\PackageHistory;

class TaskAmericanE extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:task-ae';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualización estados AE send csv';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    { 
        $dateEnd  = date('Y-m-d H:i:s');
        $dateInit = date('Y-m-01 H:i:s', strtotime('-2 minute', strtotime($dateEnd)));

        $filename = "Report-" . date('m-d-H-i-s', strtotime($dateInit)) .'-'. date('m-d-H-i-s', strtotime($dateEnd)) . ".csv";
        $contents = public_path($filename);

        $this->ReportStatusHistory($dateInit, $dateEnd, $contents);

        Storage::disk('sftp')->put('tracking_in/'. $filename, $contents);

        Storage::append("schedule.txt", $dateEnd);
    }

    public function ReportStatusHistory($dateInit, $dateEnd, $contents)
    {        
        $filename  = "Report-" . date('m-d-H-i-s', strtotime($dateInit)) .'-'. date('m-d-H-i-s', strtotime($dateEnd)) . ".csv";
        $delimiter = ",";

        $file   = fopen($contents, 'w');
        $fields = array('FECHA', 'HORA', 'PACKAGE ID', 'CLIENT', 'CONTACT', 'ADDREESS', 'CITY', 'STATE', 'ZIP CODE', 'WEIGHT', 'ROUTE');

        fputcsv($file, $fields, $delimiter);
        
        $ListAssigns = PackageHistory::whereBetween('created_at', [$dateInit, $dateEnd])->get();

        foreach($ListAssigns as $assign)
        {

            $lineData = array(
                                date('m-d-Y', strtotime($assign->Date_manifest)),
                                date('H:i:s', strtotime($assign->Date_manifest)),
                                $assign->Reference_Number_1,
                                $assign->Dropoff_Contact_Name,
                                $assign->Dropoff_Contact_Phone_Number,
                                $assign->Dropoff_Address_Line_1,
                                $assign->Dropoff_City,
                                $assign->Dropoff_Province,
                                $assign->Dropoff_Postal_Code,
                                $assign->Weight,
                                $assign->Route
                            );

            fputcsv($file, $lineData, $delimiter);
        }
        
        rewind($file);
        fclose($file);
    }
}
