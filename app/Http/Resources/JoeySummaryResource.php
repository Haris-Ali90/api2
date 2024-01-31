<?php

namespace App\Http\Resources;


use App\Models\Joey;

use App\Models\JoeysZoneSchedule;
use App\Models\SprintTasks;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class JoeySummaryResource extends JsonResource
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
/*         $status = StatusMap::getDescription($this->status_id);*/

        $now = Carbon::now();

        $record=JoeysZoneSchedule::where('joey_id',$this->joey_id)->get();


//        $sprintTask=SprintTasks::where('sprint_id',$this->id)->first();
//
//        $dropOffEtcTime=Carbon::createFromTimestamp($sprintTask->etc_time)??'';
//
//        $pickUpEtaTime=Carbon::createFromTimestamp($sprintTask->eta_time)??'';
//
//          $interval = $dropOffEtcTime->diff($pickUpEtaTime)->format('%H:%I:%S')." Minutes";
            dd($totalDuration);
        return [
            'id' => $this->id,
            'total_time'=>''



        ];
    }
}
