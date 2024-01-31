<?php

namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;

class JoeyVehicleResource extends JsonResource
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

        return [
            'id' => $this->id,
            'vehicle_id'=>$this->vehicle_id??'',
            'vehicle_name'=>$this->vehicle->name??'',
            'make'=>$this->make??'',
            'color' => $this->color??'',
            'model'=>$this->model??'',
            'license_plate' => $this->license_plate??'',

        ];
    }
}
