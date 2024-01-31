<?php

namespace App\Models;

use App\Models\Interfaces\JoeysZoneScheduleInterface;
use App\Models\Interfaces\SprintInterface;

use App\Models\Interfaces\SprintTaskInterface;
use DB;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Notifications\Notifiable;


class JoeysZoneSchedule extends Model implements JoeysZoneScheduleInterface
{

    public $table = 'joeys_zone_schedule';
    public $timestamps = false;
    use SoftDeletes,Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','joey_id','zone_schedule_id','start_time','end_time', 'wage','joeyco_notes',
        'joey_notes','bonus_type'
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
  

      ### for schedules
      public  function schedulesAccepted(){
        return $this->belongsTo(ZoneSchedule::class,'zone_schedule_id','id');
    }


    ### for schedules
    public  function ZoneSchedule(){
        return $this->belongsTo(ZoneSchedule::class,'zone_schedule_id','id');
    }





}
