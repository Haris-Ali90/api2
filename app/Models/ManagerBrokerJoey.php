<?php

namespace App\Models;

use App\Models\Interfaces\ManagerBrokerJoeyInterface;

use Illuminate\Database\Eloquent\Model;


class ManagerBrokerJoey extends Model implements ManagerBrokerJoeyInterface
{

    public $table = 'brooker_joey';

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

    public function managerBrokerUsers()
    {
        return $this->belongsTo(ManagerBrokerUsers::class,'brooker_id','id');
    }
}


