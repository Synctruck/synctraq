<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Models\{ Company, PackageDispatch };

use App\Http\Controllers\PackageDispatchController;

use Log;

class TaskSendDeliverySmartKargo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:delivery-smartkargo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envío de packages con status delivery - imports';

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
        $this->SendDelivery();
    }

    public function SendDelivery()
    {
        $packageDeliveryList = PackageDispatch::where('status', 'Delivery')
                                            ->where('idCompany', 15)
                                            ->where('arrivalLonLat', '')
                                            ->where('taskOnfleet', '!=', '')
                                            ->get();
        try
        {
            Log::info('================');
            Log::info('================');
            Log::info('START- SEND SMARTKARGO');

            DB::beginTransaction();

            foreach($packageDeliveryList as $packageDelivery)
            {
                $packageDispatchController = new PackageDispatchController();
                $dataTaskOnfleet           = $packageDispatchController->GetOnfleetShorId($packageDelivery->taskOnfleet);

                Log::info($dataTaskOnfleet);
                
                if($dataTaskOnfleet)
                {
                    Log::info($dataTaskOnfleet);
                }
                //$packageDelivery = PackageDispatch::find($packageDelivery->Reference_Number_1);


            }

            DB::commit();

            Log::info('END - SEND SMARTKARGO');
            Log::info('================');
            Log::info('================');
        }
        catch(Exception $e)
        {
            DB::rollback();

            Log::info('ROLLBACK - SEND SMARTKARGO');
            Log::info('================');
            Log::info('================');
        }
    }
}