<?php

namespace App\Models;

use App\Models\Interfaces\ExclusiveOrderJoeysInterface;
use App\Models\Interfaces\ZonesInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExclusiveOrderJoeys extends Model implements ExclusiveOrderJoeysInterface
{

    public $table = 'exclusive_order_joeys';
    use SoftDeletes;


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','order_id','joey_id'
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


