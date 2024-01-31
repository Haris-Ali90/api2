<?php

namespace App\Http\Resources;

use App\Models\ExclusiveOrderJoeys;
use App\Models\Interfaces\ExclusiveOrderJoeysInterface;
use App\Models\Sprint;
use App\Models\SprintConfirmation;
use App\Models\SprintTaskHistory;
use App\Models\SprintTasks;
use App\Models\StatusMap;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class GrocerySprintResource extends JsonResource
{

    private $duration;

    public function __construct($resource)
    {
        parent::__construct($resource);


    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
       
        $status = StatusMap::getDescription($this->status_id);
       
            /*
             * for duration
             * */

        if(isset($this->sprintPickupTask->due_time)){
            $startTime=$this->sprintPickupTask->due_time;
        }else{
            $startTime=null;
        }

        if(isset($this->sprintLastDropOffTask->etc_time)){
            $endTimeArray=$this->sprintLastDropOffTask->etc_time;
        }else{
            $endTimeArray=null;
        }


        /* difference for duration calculation  */
        $differenceTimeConversion;
        if(isset($startTime) && isset($endTimeArray)  ){

            $endTime=$endTimeArray;
            $difference= $endTime - $startTime;
            $differenceTimeConversion=date('H:i:s', $difference);
        }else{

            $differenceTimeConversion='0';
        }


        if(isset($differenceTimeConversion)) {
            $valueBrakeDown = explode(':', $differenceTimeConversion);
            $duration = $valueBrakeDown[0] . ' Hrs ' . $valueBrakeDown[1] . ' Min ' . $valueBrakeDown[2] . ' Sec';
        }else{
            $duration='0';
        }

        if ($this->optimize_route == 1) {
            $tasks = $this->sprintTask;
        } else {
            $tasks = $this->sprintTaskAscId;
        }

        return [
            'id' => $this->id,
            'status' => [
                'id' => $this->status_id,
                'description' => $status
            ],
            'distance' => $this->distance/1000,
            'duration'=>$duration,
            'task'=>SprintTaskResource::Collection($tasks,$this->status)

        ];
    }
}
