<?php

namespace App\Models;


use App\Models\Interfaces\ManagerUserDeviceInterface;
use Illuminate\Database\Eloquent\Model;


class ManagerUserDevice extends Model implements ManagerUserDeviceInterface
{
    public $table = 'manager_user_devices';

    protected $guarded = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $casts = [
        'user_id'          => 'integer',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = ['created_at','updated_at'];

    /**
     * The attributes that should be append to toArray.
     *
     * @var array
     */
    protected $appends = [];


    /**
     * Get Customer Detail
     */
    public function userDetail()
    {
        return $this->belongsTo(ManagerDashboard::class);
    }


}
