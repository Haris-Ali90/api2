<?php

namespace App\Http\Resources;


use App\Models\ZoneSchedule;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class JoeyScheduleDetailResource extends JsonResource
{



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


       // $zoneSchedule=ZoneSchedule::where('id',$this->zone_schedule_id)->first();

        $zoneSchedule= $this->schedulesAccepted;

        $shiftStartTime = $zoneSchedule->start_time ??  '';
    //    $shiftStartTime= Carbon::parse($shiftStartTime)??'';

        $shiftEndTime = $zoneSchedule->end_time ?? '';
      //  $shiftEndTime= Carbon::parse($shiftEndTime)??'';

        if(!empty($this->start_time)){
            $joeyStartTime = $this->start_time;
        }

        if(!empty($this->end_time)){
            $joeyEndTime = $this->end_time;
        }

        if(!empty($this->schedulesAccepted)){

            if($this->schedulesAccepted->zone_id==71){
                $shiftStartTime= Carbon::parse($this->schedulesAccepted->start_time)->subHour(1);
                $shiftStartTime=  $shiftStartTime->format('Y-m-d H:i:s');

                $shiftEndTime= Carbon::parse($this->schedulesAccepted->end_time)->subHour(1);
                $shiftEndTime=  $shiftEndTime->format('Y-m-d H:i:s');


            }
        }


        if(!empty($this->schedulesAccepted)){
            $zones = new ZonesResource($this->schedulesAccepted->zones);
        }
        else{
            $zones = '';
        }

        return [
            'id' => $this->zone_schedule_id??'',
            'num' => 'SH-'.$this->zone_schedule_id??'',
            'zone_id' =>$zoneSchedule->zone_id??'',
            'shift_start_time' => $shiftStartTime,
            'shift_end_time' => $shiftEndTime,
            'joey_start_time' => $joeyStartTime ?? '',
            'joey_end_time' => $joeyEndTime ?? '',
            'zones' => $zones,
        ];
    }
}
