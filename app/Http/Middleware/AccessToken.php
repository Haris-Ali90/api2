<?php

namespace App\Http\Middleware;

use App\Models\GenerateToken;
use Closure;
use Illuminate\Http\Request;
use Config;
use Response;

class AccessToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {

        $token = GenerateToken::whereToken($request->header('token'))->first();

        if(empty($request->header('token'))){
            return Response::json(['error' => 'Token Is Required']);
        }

        if ($token == null) {
            return Response::json(['error' => 'Invalid Token']);
        }

        if (date('Y-m-d H:i:s') >= $token->expired_at) {
            return Response::json(['error' => 'Token Has Been Expired']);
        }

        return $next($request);
    }
}
