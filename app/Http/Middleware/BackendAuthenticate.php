<?php

namespace App\Http\Middleware;


use Closure;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BackendAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param mixed|null $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if (Auth::guard($guard)->check()) {

            if(Auth::user()->is_enabled == 0){
                Auth::logout();
                return redirect()->guest('login')->withErrors('Your account has been made In-Active,please contact Customer admin');
            }
            return $next($request);

        }

        return redirect()->guest('login');
    }
}
