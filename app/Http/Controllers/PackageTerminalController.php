<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Service\ServicePackageTerminal;

use Auth;
use DB;
use Log;
use Session;
use DateTime;
 
class PackageTerminalController extends Controller
{
    public function Index()
    {
        return view('package.age');
    }

    public function Insert($package)
    {
        $serviceTerminal = new ServicePackageTerminal();
        $serviceTerminal->Insert($package);
    }
}