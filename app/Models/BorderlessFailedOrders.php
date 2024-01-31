<?php

namespace App\Models;

use App\Models\Interfaces\BorderlessFailedInterface;
use Illuminate\Database\Eloquent\Model;


class BorderlessFailedOrders extends Model implements BorderlessFailedInterface
{

    /**
     * Table name.
     *
     * @var array
     */
    public $table = 'boradless_failed_orders';

    // Protected $primaryKey = "id";

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

    // hubs  function relation
    public function hub()
    {
        return $this->belongsTo(Hub::class,'hub_id','id');
    }



}
