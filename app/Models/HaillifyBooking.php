<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;


class HaillifyBooking extends Model
{

    /**
     * Table name.
     *
     * @var array
     */
    public $table = 'haillify_bookings';
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


    public function sprint()
    {
        return $this->belongsTo(Sprint::class);
    }

    public function delivery()
    {
        return $this->hasMany(HaillifyDeliveryDetail::class);
    }

    public function dropoff()
    {
        return $this->hasMany(HaillifyDeliveryDetail::class)->whereNotNull('dropoff_id');
    }


}
