<?php

namespace App\Models;

use App\Models\Interfaces\JoeyItinerariesLocationsInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JoeyItinerariesLocations extends Model implements JoeyItinerariesLocationsInterface

{
   // use SoftDeletes;
    public $table = 'joey_itineraries_locations';


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'joey_itineraries_id','joey_route_location_id','task_id','arrival_time','finish_time','distance','ordinal'
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


    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function joeyRouteLocation()
    {
        return $this->belongsTo(JoeyRouteLocation::class,'joey_route_location_id');
    }



}
