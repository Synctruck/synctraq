<?php
namespace App\Http\Middleware;

use Closure;

use Illuminate\Support\Facades\Auth;

class Permission
{
    public function handle($request, Closure $next,$permission)
    {
        if(hasPermission($permission))
        {
            if(Auth::user()->changePasswordMandatory == 1)
            {
                return $next($request);
            }
            else
            {
                return redirect('profile');
            }
        }
       //si no se encuentra el permiso se aborta la peticion
       abort(403);
    }
}
