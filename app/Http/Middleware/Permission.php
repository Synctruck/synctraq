<?php
namespace App\Http\Middleware;

use Closure;


class Permission
{
    public function handle($request, Closure $next,$permission)
    {
        if (hasPermission($permission))
        {
             return $next($request);
        }
       //si no se encuentra el permiso se aborta la peticion
       abort(403);
    }
}
