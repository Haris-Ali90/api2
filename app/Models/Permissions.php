<?php

namespace App\Models;


use App\Models\Interfaces\PermissionsInterface;
use Illuminate\Database\Eloquent\Model;

class Permissions extends Model implements PermissionsInterface
{
    const SUPER_ADMIN_ROLE_ID  = 1;

    /**
     * Table name.
     *
     * @var array
     */
    public $table = 'permissions';

    /**
     * The attributes that are guarded.
     *
     * @var array
     */
    protected $guarded = [
    ];


    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [];


    /**
     * Get all the permissions list from config file.
     *
     * @var array
     */

    function scopeGetAllPermissions()
    {
        return config('permissions');
    }

    /**
     * Get all the permissions list from config file.
     *
     * @var array
     */

    function scopeGetAllDashboardCardPermissions()
    {
        return config('dashboard-cards-permissions');
    }


    /**
     * Get all the permissions Super Admin role id from config file.
     *
     * @var array
     */

    function scopeGetSuperAdminRole()
    {
        return config('app.super_admin_role_id');
    }


}
