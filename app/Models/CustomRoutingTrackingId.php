<?php

namespace App\Models;

use App\Models\Interfaces\CustomRoutingTrackingIdInterface;
use Illuminate\Database\Eloquent\Model;

class CustomRoutingTrackingId extends Model implements CustomRoutingTrackingIdInterface
{


    /**
     * Table name.
     *
     * @var array
     */
    public $table = 'custom_routing_tracking_id';

    /**
     * remove time steps
     */
    public $timestamps = false;

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
    protected $casts = [

    ];

    /**
     * ORM Relation
     *
     * @var array
     */




}
