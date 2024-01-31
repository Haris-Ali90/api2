<?php

namespace App\Models;
use App\Models\Interfaces\SprintZoneScheduleInterface;
use Illuminate\Database\Eloquent\Model;


class SprintZoneSchedule extends Model implements SprintZoneScheduleInterface
{

    public $table = 'sprint_zone_schedule';


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sprint_id', 'zone_schedule_id'
    ];

    public function sprint()
    {
        return $this->belongsTo(Sprint::class);
    }

    public function zone()
    {
        return $this->belongsTo(ZoneSchedule::class);
    }
}
