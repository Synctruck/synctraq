<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Models\{ Configuration, HistoryDiesel };

use Log;

class TaskGetDieselPrice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:task-get-diesel-price';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Obtener precio del diesel desde el api de EIA';

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
        Log::info("============================================================");
        Log::info("========== GET DIESEL PRICE - EIA ==========");

        try
        {
            DB::beginTransaction();

            $curl = curl_init();

            $dateStart = '2023-01-09';
            $dateEnd   = date('Y-m-d');

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.eia.gov/v2/petroleum/pri/gnd/data/?api_key=9JuR94UbaxHq9clF039qVxKTeNobIGQENAXUwVf6&frequency=weekly&data%5B0%5D=value&facets%5Bseries%5D%5B%5D=EMD_EPD2D_PTE_NUS_DPG&start='. $dateStart .'&end='. $dateEnd .'&sort%5B0%5D%5Bcolumn%5D=period&sort%5B0%5D%5Bdirection%5D=asc&offset=0&length=5000',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
            ));

            $response    = curl_exec($curl);
            $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            
            curl_close($curl);
            
            if($http_status == 200)
            {
                $data       = end(json_decode($response)->response->data);
                $changeDate = $data->period;
                $price      = $data->value;

                $historyDiesel = HistoryDiesel::where('changeDate', $changeDate)->first();

                if($historyDiesel == null)
                {
                    $historyDiesel = new historyDiesel();

                    $historyDiesel->changeDate = $changeDate;
                    $historyDiesel->price = $price;

                    $historyDiesel->save();

                    $configuration = Configuration::first();

                    $configuration->diesel_price = $price;

                    $configuration->save();
                }
            }

            Log::info("==================== CORRECT GET DIESEL PRICE ==============");
            Log::info("============================================================");

            DB::commit();
        }
        catch(Exception $e)
        {
            DB::rollback();

            Log::info("==================== ERROR GET DIESEL PRICE ==============");
            Log::info("============================================================");
        }
    }
}