<?php

namespace App\Models;


use App\Models\Interfaces\SprintTaskHistoryInterface;
use Illuminate\Database\Eloquent\Model;


class SprintTaskHistory extends Model implements SprintTaskHistoryInterface
{
    /**
     * Table name.
     *
     * @var array
     */
    public $table = 'sprint__tasks_history';

    /**
     * The attributes that are guarded.
     *
     * @var array
     */
    protected $guarded = [
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "id",
        "sprint__tasks_id",
        "sprint_id",
        "status_id",
        "active",
        "resolve_time",
        "date",
    ];

    public $timestamps = false;
 // new work
    #get sprint task for location id where type =fropoff
    public function sprintTaskDropoffLocationId(){
        return $this->hasOne(SprintTasks::class,'id','sprint__tasks_id')->where('type','dropoff');
    }
     // new work
    #get sprint task for location id where type =pickup
    public function sprintTaskPickupLocationId(){
        return $this->hasOne(SprintTasks::class,'id','sprint__tasks_id')->where('type','pickup');
    }

}
