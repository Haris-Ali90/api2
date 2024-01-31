<?php

namespace App\Http\Resources;

use App\Models\JoeyRouteLocation;
use App\Models\Location;
use App\Models\MerchantsIds;
use App\Models\RouteHistory;
use App\Models\SprintTaskHistory;
use App\Models\SprintTasks;
use App\Models\StatusMap;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class JoeyRouteDetailsResource extends JsonResource
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


        //        $taskId=JoeyRouteLocation::where('id',$this->route_location_id)->first();
        //       $taskId=$this->taskIdAgainstRouteLocationId;
         if(!empty($this->taskIdAgainstRouteLocationId)){
          //  $sprintTask=SprintTasks::where('id',$this->taskIdAgainstRouteLocationId->sprintTaskAgainstRouteLocationId)->first();
            $sprintTask =  $this->taskIdAgainstRouteLocationId->sprintTaskAgainstRouteLocationId;
            //$merchant=MerchantsIds::where('task_id',$taskId->task_id)->first();
            $merchant=$this->taskIdAgainstRouteLocationId->merchant;

        }
        else{
            $sprintTask='';
            $merchant='';
        }

        if(!empty($sprintTask)){
           // $location=Location::where('id',$sprintTask->location_id)->first();
            $location=$this->taskIdAgainstRouteLocationId->sprintTaskAgainstRouteLocationId->Location;

        }
        else{
            $location='';
        }

        return [

             'id' => $this->id ,
            'route_id' => 'R-'.$this->route_id.'-'.$this->ordinal??'',
            'address' =>$location->address??'',
            'latitude' =>$location->latitude??'',
            'longitude' =>$location->longitude??'',
            'merchant_order_num'=>$merchant->merchant_order_num??'',
            'tracking_id'=>$merchant->tracking_id??''




        ];
    }
}
