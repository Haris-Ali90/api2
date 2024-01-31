<?php
namespace App\Http\Controllers\Admin;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Cache;

class Controller extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    private const DEFAULT_IMAGE = 'default.jpg';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        \View::share('siteSettings', \App\Models\SiteSetting::find(1));
    }

    /**
     * Check if the logged in user is admin and then redirect accordingly
     * @return void
     */
    public function isAdmin()
    {
        $redirect = strpos(url()->current(), '/admin') !== false ? 'admin' : '/';

        if (auth()->user()) {
            $user  = auth()->user();
            $roles = \DB::table('role_user')->where('user_id', $user->id)->pluck('role_id')->toArray();

            if (!in_array(1, $roles) && !in_array(2, $roles)) {
                header('Location: ' . \URL::to($redirect));
                die();
            }
        } else {
            header('Location: ' . \URL::to($redirect));
            die();
        }
    }

    /*
    * getRestrictedPagesIds
    *
    **/
    public function getRestrictedPagesIds()
    {
        return \DB::table('pages')
            ->whereIn('slug', ['home', 'contact'])
            ->pluck('id')
            ->toArray();
    }

    /**
     * Clear Cache
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

    /**
     * Remove previous image
     *
     */
    protected function safeRemoveImage($fileName='', $path=''){
        if (self::DEFAULT_IMAGE === $fileName){
            return true;
        }
        if (is_file(public_path($path.$fileName))) {
            unlink(public_path($path.$fileName));
        }
        return true;
    }
}
