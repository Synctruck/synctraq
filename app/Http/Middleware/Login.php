<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

use Session;

class Login
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(Session::has('user'))
        {
            return $next($request);
        }

        return redirect('/');
        
        abort(401);
    }
}