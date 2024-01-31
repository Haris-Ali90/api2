<?php

namespace App\Http\Middleware\Api;

use App\Classes\RestAPI;
use Closure;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class Authenticate
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
         try {

            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return RestAPI::response('User not found', false,'user_not_found');
            }
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
