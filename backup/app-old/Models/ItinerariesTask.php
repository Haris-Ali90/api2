<?php

namespace App\Models;

use App\Models\Interfaces\ItinerariesTaskInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItinerariesTask extends Model implements ItinerariesTaskInterface

{
   // use SoftDeletes;
    public $table = 'itineraries__tasks';


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
       'ordinal','itinerary_id', 'sprint_id', 'sprint_render_id', 'task_id', 'task_render_id', 'type', 'due_time', 'eta', 'etc', 'timezone', 'location_id',
        'latitude', 'longitude', 'active', 'status'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */


    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
    ];


}
