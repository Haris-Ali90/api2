<?php

namespace App\Models;

use App\Models\Interfaces\RoleInterface;
use App\Models\Scopes\RoleTypeScope;
use Illuminate\Database\Eloquent\Model;
use App\Models\Permissions;

class Roles extends Model implements RoleInterface
{
    const ROLE_TYPE_NAME = 'customer_dashboard';

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new RoleTypeScope);
    }


    /**
     * Table name.
     *
     * @var array
     */



    public $table = 'roles';

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
     * Get the users by roles.
     *
     * @return array
     */

    public function User()
    {
        return $this->hasMany(User::class, 'role_type','id');
    }

    /**
     * Get the users by roles.
     *
     * @return array
     */

    public function Permissions()
    {
        return $this->hasMany(Permissions::class, 'role_id','id');
    }

    /**
     * Get the dashboard all rights as array.
     *
     * @return string
     */
    public function getDashboardCardRightAttribute()
    {
        dd('adssa');
        return explode(',',$this->dashbaord_cards_rights);
    }

    /**
     * Scope a query to only include not super admin role
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotAdminRole($query)
    {
        $admin_role_id = Permissions::SUPER_ADMIN_ROLE_ID; //config('app.super_admin_role_id');
        return $query->where('id', '!=' ,$admin_role_id);
        /*$admin_role_id =  config('app.super_admin_role_id');
        return $query->where('id', '!=' ,$admin_role_id);*/
    }


}
