<?php

namespace App\Http\Middleware\Api;

use App\Classes\RestAPI;
use App\Models\AttendanceUser;
use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class ManagerAuthenticate
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
        /*$token = JWTAuth::getToken();

          if(!$token){
              return RestAPI::response('Token not found', false,'token_absent');
          }

        $token = JWTAuth::invalidate();
        if(!$token){
            return RestAPI::response('Token Invalid', false,'token_invalid');
        }

        return $next($request);*/

        $token =  JWTAuth::getToken();
        try {
            $user = JWTAuth::authenticate($token);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return RestAPI::response('Token expired', false,'token_expired');
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return RestAPI::response('Token Invalid', false,'token_invalid');
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return RestAPI::response('Token not found', false,'token_absent');
        }
        return $next($request);

    }

}
