<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OptimizeItinerary extends Model
{
    use SoftDeletes;
    public $table = 'optimize_itineraries';

    protected $guarded = [];

    protected $hidden = ['created_at','updated_at'];

    /**
     * The attributes that should be append to toArray.
     *
     * @var array
     */
    protected $appends = [];

    public function itinerary()
    {
        return $this->hasMany(OptimizeTask::class, 'itinerary_id', 'id');
    }
}

