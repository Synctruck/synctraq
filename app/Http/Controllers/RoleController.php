<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Role;

use Illuminate\Support\Facades\Validator;

use Session;

class RoleController extends Controller
{
    public $paginate = 50;

    public function List(Request $request)
    {
        $roleList = Role::orderBy('name', 'asc')->get();
        
        return ['roleList' => $roleList];
    }
}