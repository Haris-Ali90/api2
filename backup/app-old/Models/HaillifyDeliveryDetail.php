<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;


class HaillifyDeliveryDetail extends Model
{

    /**
     * Table name.
     *
     * @var array
     */
    public $table = 'haillify_delivery_details';
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

    public function booking()
    {
        return $this->belongsTo(HaillifyBooking::class);
    }


}
