<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Models\{ FileImport, PackageWeight, PackageManifest, PackageHistory, Routes, RoutesZipCode };

use Log;

class TaskAmericanManifest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:ae-manifest';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Leer data de American Enagle';

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
        $allFilesTrackingIn = Storage::disk('sftp')->allFiles('/outbox/manifest');

        foreach($allFilesTrackingIn as $fileTracking)
        {
            $fileImport = FileImport::where('name', $fileTracking)->first();

            if(!$fileImport)
            {
                Storage::disk('public')->put(str_replace("outgoing/", "", $fileTracking), Storage::disk('sftp')->get($fileTracking));

                $handle = fopen(public_path('storage/'. $fileTracking), "r");

                $lineNumber = 1;
 
                Log::info('================');
                Log::info('Upload Manifest');
                Log::info($fileTracking);

                try
                {
                    DB::beginTransaction();

                    while (($raw_string = fgets($handle)) !== false)
                    {
                        if($lineNumber > 1)
                        {
                            $row = str_getcsv($raw_string);

                            $packageHistory  = PackageHistory::where('Reference_Number_1', $row[0])->get();
                            $packageManifest = PackageManifest::find($row[0]);

                            if(count($packageHistory) == 0 && !$packageManifest)
                            {
                                if(isset($row[21]) && isset($row[22]) && isset($row[16]) && isset($row[18]) && isset($row[19]) && isset($row[20]))
                                {
                                    $created_at = date('Y-m-d H:i:s');

                                    $packageWeight = new PackageWeight();
                                    $packageWeight->Reference_Number_1 = $row[0];
                                    $packageWeight->weight3 = $row[27];
                                    $packageWeight->save();

                                    $package = new PackageManifest();

                                    $package->Reference_Number_1           = $row[0];
                                    $package->idCompany                    = 10;
                                    $package->company                      = 'AMERICAN EAGLE';
                                    $package->Dropoff_Contact_Name         = $row[21];
                                    $package->Dropoff_Contact_Phone_Number = $row[22];
                                    $package->Dropoff_Contact_Email        = $row[23];
                                    $package->Dropoff_Address_Line_1       = $row[16];
                                    $package->Dropoff_Address_Line_2       = $row[17];
                                    $package->Dropoff_City                 = $row[18];
                                    $package->Dropoff_Province             = $row[19];
                                    $package->Dropoff_Postal_Code          = $row[20];
                                    $package->Notes                        = '';
                                    $package->weight_unit                  = $row[26];
                                    $package->Weight                       = $row[27];
                                    $package->height                       = $row[30];
                                    $package->status                       = 'Manifest';
                                    $package->created_at                   = $created_at;
                                    $package->updated_at                   = $created_at;

                                    $routesZipCode = RoutesZipCode::find($row[20]);
                                    
                                    $package->Route = $routesZipCode ? $routesZipCode->routeName : '';
                                    $package->save();

                                    $packageHistory = new PackageHistory();

                                    $packageHistory->id = uniqid();
                                    $packageHistory->Reference_Number_1           = $row[0];
                                    $packageHistory->idCompany                    = 10;
                                    $packageHistory->company                      = 'AMERICAN EAGLE';
                                    $packageHistory->Dropoff_Contact_Name         = $row[21];
                                    $packageHistory->Dropoff_Contact_Phone_Number = $row[22];
                                    $packageHistory->Dropoff_Contact_Email        = $row[23];
                                    $packageHistory->Dropoff_Address_Line_1       = $row[16];
                                    $packageHistory->Dropoff_Address_Line_2       = $row[17];
                                    $packageHistory->Dropoff_City                 = $row[18];
                                    $packageHistory->Dropoff_Province             = $row[19];
                                    $packageHistory->Dropoff_Postal_Code          = $row[20];
                                    $packageHistory->Notes                        = '';
                                    $packageHistory->weight_unit                  = $row[26];
                                    $packageHistory->Weight                       = $row[27];
                                    $packageHistory->height                       = $row[30];
                                    $packageHistory->status                       = 'Manifest';
                                    $packageHistory->Date_manifest                = $created_at;
                                    $packageHistory->Description                  = 'For: AMERICAN EAGLE (schedule task)';
                                    $packageHistory->Route                        = $route ? $route->name : '';
                                    $packageHistory->actualDate                   = $created_at;
                                    $packageHistory->created_at                   = $created_at;
                                    $packageHistory->updated_at                   = $created_at;

                                    $packageHistory->save();
                                }
                                
                            }
                        }
                        
                        $lineNumber++;
                    }

                    $fileImport = new FileImport();

                    $fileImport->id        = uniqid();
                    $fileImport->idCompany = 10;
                    $fileImport->name      = $fileTracking;

                    $fileImport->save();

                    DB::commit();

                    Log::info("Upload - Correct");
                    Log::info('================');
                }
                catch(Exception $e)
                {
                    DB::rollback();

                    Log::info("Upload - In - Correct");
                    Log::info('================');
                }
            }
            else
            {
                Log::info('================');
                Log::info('Upload Manifest - EXISTS');
                Log::info($fileTracking);
                Log::info('================');
                Log::info('================');
            }
        }
    }
}
