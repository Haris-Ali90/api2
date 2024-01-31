<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Config;

class CheckToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next , $guard = null)
    {
        if ($request->header('Cross-origin-token') != Config::get('app.api_token')) {
            return \Response::json(['error' => 'Invalid or missing cross origin api token.']);
        }
        return $next($request)->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
    }
}
