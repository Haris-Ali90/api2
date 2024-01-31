<?php
namespace App\Http\Controllers\Api;

use App\Http\Traits\JWTUserTrait;
use Illuminate\Support\Facades\Cache;


class ApiBaseController extends \App\Http\Controllers\Api\Controller
{

    /**
     * Extract token value from request
     *
     * @return string
     */
    protected function extractToken($request = false)
    {
        return JWTUserTrait::extractToken($request);
    }

    /**
     * Return User instance or false if not exist in DB
     *
     * @return mixed
     */
    protected function getUserInstance($request = false)
    {
        return JWTUserTrait::getUserInstance($request);
    }

    /**
     * Cache Clear
     *
     */
    protected function clearCache() : void //!important
    {
        Cache::flush();
    }

    /**
     * Reset Cache
     *
     */
    protected function resetCache($keys = NULL) : bool //!important
    {
        if(null === $keys){
            Cache::flush();
        }
        elseif(is_array($keys)){
            foreach ($keys as $key){
                Cache::forget($key);
            }
        }
        else if(is_string($keys)){
            Cache::forget($keys);
        }

        return true;

    }
	    public function timeConvert($date){
        $start_dt = new \DateTime($date." 00:00:00", new \DateTimezone('America/Toronto'));
        $start_dt->setTimeZone(new \DateTimezone('UTC'));
        $start = $start_dt->format('Y-m-d H:i:s');

        $end_dt = new \DateTime($date." 23:59:59", new \DateTimezone('America/Toronto'));
        $end_dt->setTimeZone(new \DateTimezone('UTC'));
        $end = $end_dt->format('Y-m-d H:i:s');

        $record['start'] = $start;
        $record['end'] = $end;

        return $record;
    }
}
