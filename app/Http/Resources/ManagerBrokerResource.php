<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ManagerBrokerResource extends JsonResource
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
        $broker_name= '';
        if (isset($this->managerBrokerUsers))
        {
            $broker_name =  $this->managerBrokerUsers->name;
        }

        return [
            'id' => $this->managerBrokerUsers->id??'',
            'broker_name' => $broker_name,
        ];
    }
}
