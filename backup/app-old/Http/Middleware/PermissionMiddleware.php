<?php
/**
 * Created by Muhammad Adnan Nadeem.
 * User: Muhammad Adnan Nadeem <adnannadeem1994@gmail.com>
 * Date: 10/24/2020
 * Time: 11:16 AM
 */

namespace App\Http\Middleware;


use Closure;
use Auth;
use Session;
use App\Models\Permissions;

class PermissionMiddleware
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // current user data
        $user_data = Auth::user();

        // checking is if the user is super admin then pass
        if($user_data->role_type == Permissions::GetSuperAdminRole())
        {
            return $next($request);
        }

        $checker_value = can_access_route($request->route()->getName(),$user_data->PermissionsExtract());

        if($checker_value)
        {
            return $next($request);
        }

        Session::put('error', "<b>Opps</b> You don't have permissoin to go there");
        return redirect('dashboard');

    }

}