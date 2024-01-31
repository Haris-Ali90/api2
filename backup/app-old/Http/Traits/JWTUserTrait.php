<?php
namespace App\Http\Traits;

use Illuminate\Support\Facades\Request;
// use JWTAuth;


trait JWTUserTrait {

    protected static $userInstance;

    /**
     * Extract token value from request
     *
     * @return string|Request
     */
    public static function extractToken($request=false) {
        if ( $request && $request instanceof Request ) {
            $token = $request->bearerToken();
        }
        else if ( is_string($request) ) {
            $token = $request;
        }
        else {
            $token = request()->bearerToken();//Request::bearerToken();
        }

        return str_replace('Bearer ', '', $token);
    }

    /**
     * Return User instance or false if not exist in DB
     *
     * @return string|Request
     */
    public static function getUserInstance($request=false) {

        if ( !self::$userInstance ) {
            $token = self::extractToken($request);

            self::$userInstance = jwt()->setToken($token)->user();
        }

        return self::$userInstance;
    }

}
