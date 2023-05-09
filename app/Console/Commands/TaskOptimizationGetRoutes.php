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

                $dataGetRoute = $this->GetRoutes($optimization);
                
                DB::commit();

                $http_status = $dataGetRoute['http_status'];
                $output      = $dataGetRoute['output'];

                if($http_status == 200)
                {
                    if($output['state'] == 4)
                    {
                        $output['quantityPackage'] = $optimization->quantityPackage;
                        $output['date']            = date('m/d/Y H:i:s');

                        Mail::send('mail.optimizationgetroutes', ['data' => $output ], function($message) use($output) {

                            $message->to('wilcm123@gmail.com', 'Optimization Get Routes')->subject('Optimization Get Routes Date ('. $output['date'] .')');
                        });
                    }
                }

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

        return ['http_status' => $http_status, 'output' => $output];
    }

    public function SaveOptimization($optimization)
    {
        $optimization->status = 'Close';
        $optimization->save();
    }

    public function SaveRoutes($routesList)
    {
        $status = false;

        foreach($routesList as $route)
        {
            $optimizationRoutes = new OptimizationRoutes();
            $optimizationRoutes->route_id                = $route['route_id'];
            $optimizationRoutes->optimization_problem_id = $route['optimization_problem_id'];
            $optimizationRoutes->route_pieces            = $route['route_pieces'];
            $optimizationRoutes->trip_distance           = $route['trip_distance'];
            $optimizationRoutes->route_duration_sec      = $route['route_duration_sec'];
            $optimizationRoutes->save();

            $status = $this->SaveRoutesDetail($route['route_id']);
        }

        return $status;
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

            $is_depot = false;
            $alias    = '';

            foreach($addressesList as $address)
            {
                if($address['is_depot'] == true)
                {
                    $alias = $address['alias'];
                }

                $optimizationRoutesDetail = new OptimizationRoutesDetail();
                $optimizationRoutesDetail->id                 = uniqid();
                $optimizationRoutesDetail->route_id           = $route_id;
                $optimizationRoutesDetail->alias              = $alias;
                $optimizationRoutesDetail->Reference_Number_1 = count($address['custom_fields']) > 0 ? $address['custom_fields']['PACKAGE ID'] : '';
                $optimizationRoutesDetail->sequence_no        = $address['sequence_no'];
                $optimizationRoutesDetail->save();
            }

            return true;
        }

        return false;
    }
}