<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{ Cellar, Company, Configuration, PackageLmCarrier, States, PackageDispatchToMiddleMile };

use Auth;
use DateTime;
use DB;
use Session;

class PackageDispatchToMiddleMileController extends Controller
{
    private $apiKey;

    private $base64;

    private $headers;

    public function __construct()
    {
        $this->apiKey = Configuration::first()->key_onfleet;

        $this->base64 = base64_encode($this->apiKey .':');

        $this->headers = [
                        'Authorization: Basic '. $this->base64,
                    ];
    }
    
    public function Index()
    {
        return view('package.dispatchtomiddlemile');
    }

    public function List($idCompany, $dateStart,$dateEnd, $route, $state)
    {
        $packageListWarehouse = $this->GetDataDispatchToMiddleMile($idCompany, $dateStart, $dateEnd, $route, $state);
        $quantityWarehouse    = $packageListWarehouse->total();

        $listState  = PackageLmCarrier::select('Dropoff_Province')
                                        ->groupBy('Dropoff_Province')
                                        ->get();

        $listStateValidate  = States::orderBy('name', 'asc')->get();                                    

        return ['packageList' => $packageListWarehouse, 'listState' => $listState, 'listStateValidate' => $listStateValidate, 'quantityWarehouse' => $quantityWarehouse];
    }

    private function GetDataDispatchToMiddleMile($idCompany, $dateStart, $dateEnd, $route, $state, $type='list')
    {
        $dateStart = $dateStart .' 00:00:00';
        $dateEnd  = $dateEnd .' 23:59:59';

        $routes = explode(',', $route);
        $states = explode(',', $state);

        $packageListWarehouse = PackageDispatchToMiddleMile::whereBetween('created_at', [$dateStart, $dateEnd]);

        if($idCompany != 0)
        {
            $packageListWarehouse = $packageListWarehouse->where('idCompany', $idCompany);
        }
    
        if($route != 'all')
        {
            $packageListWarehouse = $packageListWarehouse->whereIn('Route', $routes);
        }

        if($state != 'all')
        {
            $packageListWarehouse = $packageListWarehouse->whereIn('Dropoff_Province', $states);
        }
        if($type == 'list')
        {
            $packageListWarehouse = $packageListWarehouse->orderBy('created_at', 'desc')
                                                        ->select(
                                                            'created_at',
                                                            'company',
                                                            'Reference_Number_1',
                                                            'Dropoff_Contact_Name',
                                                            'Dropoff_Contact_Phone_Number',
                                                            'Dropoff_Address_Line_1',
                                                            'Dropoff_City',
                                                            'Dropoff_Province',
                                                            'Dropoff_Postal_Code',
                                                            'Weight',
                                                            'Route'
                                                        )
                                                        ->paginate(50); 
        }
        else
        {
            $packageListWarehouse = $packageListWarehouse->orderBy('created_at', 'desc')->get();
        }

        return $packageListWarehouse;
    }

}
