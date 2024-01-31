<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use App\Models\Store;
use App\Models\SideBanner;
use App\Models\Event;
use App\Models\Offer;

/**
 * Class ViewComposerServiceProvider
 *

 */
class ViewComposerServiceProvider extends ServiceProvider
{


    public function boot()
    {
        $this->composeAdminPages();
        view()->composer('*', function ($view)
        {
            $date = date('Y-m-d');

            /*setting default variables */
            $userPermissoins = [];
            $dashbord_cards_rights = false;

            /*checking user is login or not */
            if(Auth::check())
            {
                $auth_user = Auth::user();
             //   $userPermissoins = $auth_user->getPermissions();
             //   $dashbord_cards_rights = $auth_user->DashboardCardRightsArray();
                $userPermissoins = '';
                  $dashbord_cards_rights = '';
            }

            /*composing data to all views */
            $view->with(compact(
                'userPermissoins',
                'dashbord_cards_rights'
            ));

        });
    }

    public function register()
    {
        //
    }

    /**
     * Compose the admin pages
     *
     * e-g: admin page titles etc.
     */
    private function composeAdminPages()
    {
        /*
         * Dashboard
         */
        view()->composer('admin.dashboard.index', function ($view) {
            $view->with(['pageTitle' => 'Dashboard']);
        });


        /*
       * role & permissions
       */

//        view()->composer('admin.role.index', function ($view) {
//            $view->with(['pageTitle' => 'Role List']);
//        });
//        view()->composer('admin.role.create', function ($view) {
//            $view->with(['pageTitle' => 'Add Role']);
//        });
//        view()->composer('admin.role.show', function ($view) {
//            $view->with(['pageTitle' => 'Role View']);
//        });
//        view()->composer('admin.role.edit', function ($view) {
//            $view->with(['pageTitle' => 'Role Edit']);
//        });
//        view()->composer('admin.role.set-permissions', function ($view) {
//            $view->with(['pageTitle' => 'Set Role Permissions']);
//        });




        /**
         * Sub Admin
         */
/*        view()->composer('admin.subAdmin.index', function ($view) {
            $view->with(['pageTitle' => 'Sub Admins List']);
        });
        view()->composer('admin.subAdmin.create', function ($view) {
            $view->with(['pageTitle' => 'Add Sub Admin']);
        });
        view()->composer('admin.subAdmin.show', function ($view) {
            $view->with(['pageTitle' => 'Sub Admin View']);
        });
        view()->composer('admin.subAdmin.edit', function ($view) {
            $view->with(['pageTitle' => 'Sub Admin Edit']);
        });
*/

        /*
         * Change Password
         */
        view()->composer('admin.users.changePassword', function ($view) {
            $view->with(['pageTitle' => 'Change Password']);
        });

        /*
         * Change Password
         */
        view()->composer('admin.users.profile', function ($view) {
            $view->with(['pageTitle' => 'Edit Profile']);
        });

    }
}
