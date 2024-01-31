<?php

namespace App\Http\Resources;

use App\Models\StatusMap;
use Illuminate\Http\Resources\Json\JsonResource;

class TrackingDetailsResource extends JsonResource
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
//        dd($this->taskids, $request->all());
        $status = StatusMap::getDescription($this->taskids->status_id);

//        dd($this->taskids);
        // $joey_name='';
        // if(isset($this->taskids->sprintsSprints->joey)){
        //     $joey_name = $this->taskids->sprintsSprints->joey->first_name.' '.$this->taskids->sprintsSprints->joey->last_name;
        // }

        return [
            'tracking_id' => $this->tracking_id,
            'status'=>$status,
           // 'joey' =>$joey_name,
            'address'=>new LocationResource(($this->taskids)?$this->taskids->Location:''),
            'status_history'=>SprintTaskHistoryResource::collection(($this->taskids)?$this->taskids->sprintTaskMultipleHistory:''),

        ];
    }
}
