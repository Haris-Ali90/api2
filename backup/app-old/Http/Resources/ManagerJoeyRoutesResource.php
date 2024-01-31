<?php

namespace App\Http\Resources;

use App\Models\SprintTaskHistory;
use App\Models\StatusMap;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\JoeyRouteLocation;
use Carbon\Carbon;

class ManagerJoeyRoutesResource extends JsonResource
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
        $joey_name= '';
        $joey_id = '';
        if (isset($this->joey->first_name))
        {
            $joey_name =  $this->joey->first_name.' '.$this->joey->last_name;
            $joey_id = $this->joey->id;
        }



        $broker_id = '';
        $broker_name = '';
        if (isset($this->joey)) {
            if (isset($this->joey->ManagerJoeyBrooker)){
                $broker_name = $this->joey->ManagerJoeyBrooker->managerBrokerUsers->name;
                $broker_id = $this->joey->ManagerJoeyBrooker->managerBrokerUsers->id;
            }
        }

        if (!empty($this->id) ) {
            $duration = JoeyRouteLocation::getDurationOfRoute($this->id);
        } else {
            $duration = 0;
        }

//        dd($joey_id);
        return [
            'route_id' => $this->id??'',
            'joey_id' => ($joey_id != '')?$joey_id:'',
            'joey_name' => ($joey_id != '')?$joey_name.'('.$joey_id.')':'',
            'broker_id' => ($broker_id != '')?$broker_id:'',
            'broker_name' => ($broker_id != '')?$broker_name.'('.$broker_id.')':'',
            'drops' => $this->TotalOrderDropsCount(),
            'sorted' => $this->ManagerTotalSortedOrdersCount(),
            'pick_up' => $this->ManagerTotalOrderPickedCount(),
            'complete' => $this->ManagerTotalOrderDropsCompletedCount(),
            'return' => $this->ManagerTotalOrderReturnCount(),
            'not_scan' => $this->ManagerTotalOrderNotScanCount(),
            'un_attempt' => $this->ManagerTotalOrderUnAttemptedCount(),
            'total_duration' => $duration,
            'custom_routes' => $this->managerIsCustom(),
        ];
    }
}
