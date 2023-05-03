<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Models\{ Optimization, OptimizationRoutes, OptimizationRoutesDetail };

use DateTime;
use Log;
use Mail;

class TaskOptimizationGetRoutes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:optimization-get-routes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Obtiene rutas para una optimización enviada';

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
        $optimization =  Optimization::where('status', 'Open')->first();

        if($optimization)
        {
            Log::info('Optimization - START');
            Log::info($optimization);

            try
            {
                DB::beginTransaction();

                $routesList = $this->GetRoutes($optimization);
                
                DB::commit();

                Log::info('Optimization - CORRECT');
            }
            catch(Exception $e)
            {
                DB::rollback();

                Log::info('Optimization - rollback');
            }
        }
    }

    public function GetRoutes($optimization)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.route4me.com/api.v4/optimization_problem.php?api_key=73D4A484115AEFA26C7E3CB5D2350BA0&optimization_problem_id='. $optimization->optimization_problem_id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $output      = json_decode(curl_exec($curl), 1);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if($http_status == 200)
        {
            if($output['state'] == 4)
            {
                
                $this->SaveOptimization($optimization);
                $this->SaveRoutes($output['routes']);
            }
            else
            {
                Log::info('$output->state: '. $output['state']);
            }
        }
    }

    public function SaveOptimization($optimization)
    {
        $optimization->status = 'Close';
        $optimization->save();
    }

    public function SaveRoutes($routesList)
    {
        foreach($routesList as $route)
        {
            $optimizationRoutes = new OptimizationRoutes();
            $optimizationRoutes->route_id                = $route['route_id'];
            $optimizationRoutes->optimization_problem_id = $route['optimization_problem_id'];
            $optimizationRoutes->route_pieces            = $route['route_pieces'];
            $optimizationRoutes->trip_distance           = $route['trip_distance'];
            $optimizationRoutes->route_duration_sec      = $route['route_duration_sec'];
            $optimizationRoutes->save();

            $this->SaveRoutesDetail($route['route_id']);
        }
    }

    public function SaveRoutesDetail($route_id)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.route4me.com/api.v4/route.php?api_key=73D4A484115AEFA26C7E3CB5D2350BA0&route_id='. $route_id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $output      = json_decode(curl_exec($curl), 1);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if($http_status == 200)
        {
            $addressesList = $output['addresses'];

            foreach($addressesList as $address)
            {
                $optimizationRoutesDetail = new OptimizationRoutesDetail();
                $optimizationRoutesDetail->id                 = uniqid();
                $optimizationRoutesDetail->route_id           = $route_id;
                $optimizationRoutesDetail->alias              = $address['alias'];
                $optimizationRoutesDetail->Reference_Number_1 = count($address['custom_fields']) > 0 ? $address['custom_fields']['PACKAGE ID'] : '';
                $optimizationRoutesDetail->alias              = $address['alias'];
                $optimizationRoutesDetail->sequence_no        = $address['sequence_no'];
                $optimizationRoutesDetail->save();
            }
        }
    }
}