<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MidMilePickDrop extends Model
{
    public $table = 'mid_mile_pick_drop';

    protected $fillable = [
        'bundle_id', 'joey_id', 'route_id', 'status_id', 'pickup_hub_id'
    ];

//    protected $hidden = ['created_at','updated_at'];

    /**
     * The attributes that should be append to toArray.
     *
     * @var array
     */
    protected $appends = [];

    public function joey()
    {
        return $this->belongsTo(Joey::class, 'joey_id', 'id');
    }

    public function route()
    {
        return $this->belongsTo(JoeyRoutes::class, 'route_id', 'id');
    }

}

