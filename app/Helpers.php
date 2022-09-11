<?php

use App\Models\Permission;
use App\Models\PermissionRole;
use App\Models\PermissionUser;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

//funcion que evalua si el usuario tiene permiso para una acción
function hasPermission($slug)
{
    $permission  = Permission::where('slug', $slug)->first();

    if ($permission) {


        $permissionRole = PermissionRole::where('permission_id',$permission->id)
                                        ->where('role_id',Auth::user()->idRole)->first();
        $permissionUser = PermissionUser::where('permission_id',$permission->id)
                                        ->where('user_id',Auth::user()->id)->first();
        // $permissionUser = User::allPermisions(Auth::user()->id,Auth::user()->idRole);
        if ($permissionRole || $permissionUser)
            return true;
    }
    return false;
}

function getPermissions()
{
    $permissions = User::allPermisions(Auth::user()->id,Auth::user()->idRole);

    return $permissions;
}
