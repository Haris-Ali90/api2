<?php

namespace App\Http\Resources;

use App\Models\ExclusiveOrderJoeys;
use App\Models\Interfaces\ExclusiveOrderJoeysInterface;
use App\Models\Sprint;
use App\Models\SprintTaskHistory;
use App\Models\StatusMap;
use App\Models\ZoneSchedule;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class JoeyScheduleResource extends JsonResource
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


        $zoneSchedule=ZoneSchedule::where('id',$this->zone_schedule_id)->first();

        $startDate =  $zoneSchedule->start_time;
       // $startDate= Carbon::parse($startDate)??'';
        $date_time=convertTimeZone($startDate,'UTC',$this->convert_to_timezone,'d/m/Y');
     
        return [
            'id' => $this->id,
            'num' =>'SH-'.$this->zone_schedule_id,
            'schedule_id'=>$this->zone_schedule_id??'',
            'start_date' => $date_time,
            'converted_date_time' => $zoneSchedule->start_time??'',
        ];
    }
}
