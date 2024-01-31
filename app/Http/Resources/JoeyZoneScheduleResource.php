<?php

namespace App\Http\Resources;

use App\Models\JoeysZoneSchedule;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class JoeyZoneScheduleResource extends JsonResource
{


    public function __construct($resource)
    {


        parent::__construct($resource);
        $this->resource=$resource;

    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {


        if($this->zone_id==71){
            $startTime= Carbon::parse($this->zone_start_time)->subHour(1);
            $startTime=  $startTime->format('Y-m-d H:i:s');

            $endTime= Carbon::parse($this->zone_end_time)->subHour(1);
            $endTime=  $endTime->format('Y-m-d H:i:s');


        }else{
            $startTime= Carbon::parse($this->zone_start_time);
            $startTime=  $startTime->format('Y-m-d H:i:s');

            $endTime= Carbon::parse($this->zone_end_time);
            $endTime=  $endTime->format('Y-m-d H:i:s');

        }

        $currentTime=Carbon::now()->format('Y-m-d h:m:s');

        return [
            'id' =>$this->id,
            'num' =>'SH-'.$this->zone_schedule_id,
            'joey_id' =>$this->joey_id,
            'zone_schedule_id' =>$this->zone_schedule_id,
            'joey_start_time'=>$this->joey_start_time??'',
            'joey_end_time' =>  $this->joey_end_time??'',
            'zone_start_time'=>$startTime??'',
            'zone_end_time' => $endTime??'',
            'zones' => new ZonesResource($this->schedulesAccepted->zones),

        ];

    }
}
