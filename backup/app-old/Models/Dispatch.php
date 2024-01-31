<?php

namespace App\Models;

use App\Models\Interfaces\DispatchInterface;
use DB;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


use Illuminate\Notifications\Notifiable;


class Dispatch extends Model implements DispatchInterface
//extends Model implements JoeyRoutesInterface
{

    public $table = 'dispatch';
    Protected $primaryKey = "id";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','order_id','num','creator_id','sprint_id', 'status','distance',
        'active','type','slug','order_from','joey_id',
        'joey_name','joey_phone','joey_latitude',
        'joey_longitude','vehicle_id','vehicle_name',
        'pickup_location_id','pickup_contact_name','pickup_address',
        'pickup_contact_phone','pickup_eta','pickup_etc','dropoff_contact_phone','dropoff_location_id',
        'dropoff_address','dropoff_eta','dropoff_etc',
        'date','has_notes','sprint_duration',
        'zone_id','zone_name','updated_at','status_copy'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

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
  


}
