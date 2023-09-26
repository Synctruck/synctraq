<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Cellar;

use DB;

class CellarController extends Controller
{
    public function GetAll(Request $request)
    {
        return ['cellarList' => Cellar::orderBy('name', 'desc')->get()];
    }
}
