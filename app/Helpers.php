<?php

use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

//funcion que evalua si el usuario tiene permiso para una acción
function hasPermission($slug)
{
    $permission  = Permission::where('slug', $slug)->first();

    if ($permission) {

        $permissionUser = User::allPermisions(Auth::user()->id,Auth::user()->idRole);
        $permissionExists = $permissionUser->where('id',$permission->id)->first();
        if ($permissionExists)
            return true;
    }
    return false;
}

function getPermissions()
{
    $permissions = User::allPermisions(Auth::user()->id,Auth::user()->idRole);

    return $permissions;
}
