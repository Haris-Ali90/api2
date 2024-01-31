<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FlaggedOrder extends Model
{
    protected $table = 'flagged_orders';

    use SoftDeletes;
    /**
     * The attributes that are guarded.
     *
     * @var array
     */
    protected $guarded = [
    ];

    public function Sprint()
    {
        return $this->belongsTo(Sprint::class,'sprint_id','id');
    }

}

