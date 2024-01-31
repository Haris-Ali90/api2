<?php

namespace App\Models;

use App\Models\Interfaces\ZoneScheduleInterface;
use DB;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Notifications\Notifiable;


class ZoneSchedule extends Model implements ZoneScheduleInterface

{
    public $timestamps = false;
    public $table = 'zone_schedule';

    use SoftDeletes,Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','zone_id','start_time','end_time',
        'occupancy','capacity','commission','hourly_rate','is_display',
        'vehicle_id','minimum_hourly_rate','notes','type'
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





    /**
     *
     * for Zones
     *
     */
       public  function zones(){
        return $this->belongsTo(Zones::class,'zone_id','id');
    }

    /**
     *
     * for Vehicle
     *
     */
      public  function vehicle(){
        return $this->belongsTo(Vehicle::class,'vehicle_id','id');
    }

    /**
     *
     * for Joeys
     *
     */
        public  function joeys(){

            return $this->hasMany(JoeysZoneSchedule::class,'zone_schedule_id','id');
        }





}
