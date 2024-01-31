<?php

namespace App\Http\Resources;

use App\Models\JoeysZoneSchedule;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ZoneScheduleResource extends JsonResource
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

        $startDate= Carbon::parse($this->start_time);
        $startDate=  $startDate->timestamp;

        $endDate= Carbon::parse($this->end_time);
        $endDate=  $endDate->timestamp;

    

            if($this->zone_id==71){
                $startTime= Carbon::parse($this->start_time)->subHour(1);
                $startTime=  $startTime->format('Y-m-d H:i:s');

                $endTime= Carbon::parse($this->end_time)->subHour(1);
                $endTime=  $endTime->format('Y-m-d H:i:s');


            }else{
                $startTime= Carbon::parse($this->start_time);
                $startTime=  $startTime->format('Y-m-d H:i:s');

                $endTime= Carbon::parse($this->end_time);
                $endTime=  $endTime->format('Y-m-d H:i:s');

            }


    //$joeys=JoeysZoneSchedule::where('zone_schedule_id','=',$this->id)->whereNull('deleted_at')->get();

        return [
            'id' =>$this->id,
            'num' =>'SH-'.$this->id,
            'start_date' =>  $startDate,
            'end_date' => $endDate,
            'start_time'=>$startTime,
            'end_time' =>  $endTime,
            'zone' => new ZonesResource($this->zones),
            'occupancy'=> $this->occupancy,
            'capacity' => $this->capacity,
            'commission' => $this->commission,
            'hourly_rate' => $this->hourly_rate,
            'minimum_hourly_rate'=> $this->minimum_hourly_rate,
            'vehicle_id' => $this->vehicle_id,
            'vehicle'=> new VehicleResource($this->vehicle),
            'joeys' => JoeyResource::collection($this->joeys),
            'shift_store_type'=> $this->shift_store_type,

        ];
    }
}
