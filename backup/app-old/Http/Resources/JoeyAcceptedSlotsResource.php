<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class JoeyAcceptedSlotsResource extends JsonResource
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
        $vehicle_id="";
        $vehicle_name = "";
        if(isset($this->schedulesAccepted)){
            if($this->schedulesAccepted->zone_id==71){
                $startTime= Carbon::parse($this->schedulesAccepted->start_time)->subHour(1);
                $startTime=  $startTime->format('Y-m-d H:i:s');

                $endTime= Carbon::parse($this->schedulesAccepted->end_time)->subHour(1);
                $endTime=  $endTime->format('Y-m-d H:i:s');


            }else{
                $startTime= Carbon::parse($this->schedulesAccepted->start_time);
                $startTime=  $startTime->format('Y-m-d H:i:s');

                $endTime= Carbon::parse($this->schedulesAccepted->end_time);
                $endTime=  $endTime->format('Y-m-d H:i:s');

            }

            $vehicle_id = $this->schedulesAccepted->vehicle->id;
            $vehicle_name = $this->schedulesAccepted->vehicle->name;
        }
        

        return [
            'id' => $this->id,
            'num' =>'SH-'. $this->zone_schedule_id??'',
            'joey_id'=>$this->joey_id??'',
            'zone_schedule_id' => $this->zone_schedule_id??'',
            'zone_id'=>$this->schedulesAccepted->zone_id??'',
            'start_time' => $startTime??'',
            'end_time'=> $endTime??'',
            'vehicle' => [
                'id' => $vehicle_id,
                'name' => $vehicle_name

            ]


        ];
    }
}
