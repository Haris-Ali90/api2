<?php

namespace App\Models;

use App\Models\Interfaces\ManagerBrokerUsersInterface;
use Illuminate\Database\Eloquent\Model;


class ManagerBrokerUsers extends Model implements ManagerBrokerUsersInterface
{

    public $table = 'brookers_users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [
    ];


    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [

    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
    ];

}


