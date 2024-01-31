<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Class MenuServiceProvider
 *
 * @author Muzafar Ali Jatoi <muzfr7@gmail.com>
 * @date   23/9/18
 */
class MenuServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->make('App\Services\Menu\AdminMenu')->register();
    }

    public function register()
    {
        //
    }
}
