<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserWorkPreferenceResource extends JsonResource
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
        $prefferedZone = $this->prefferedZone?$this->PrefferedZone->name:'';
        $name = str_replace(array("\n", "\r"), '', $prefferedZone);
        return [
            'id' => $this->id??NULL,
            'availability'=> $this->work_type??'',
            'contact_time'=> $this->contact_time??'',
            'prefered_zone'=> $name??'',
            'shift_store_type'=> $this->shift_store_type??'',
            'preffered_zone_id'=> $this->PrefferedZone?$this->PrefferedZone->id:NULL,
        ];
    }
}
