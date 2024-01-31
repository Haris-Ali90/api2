<?php

namespace App\Http\Middleware\Api;

use App\Classes\RestAPI;
use App\Models\AttendanceUser;
use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AttendanceAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $token = JWTAuth::getToken();

          if(!$token){
              return RestAPI::response('Token not provided', false,'token_absent');
          }

        return $next($request);
    }

}
