<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ManagerLocationResource extends JsonResource
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
        return [
            'address' => $this->address,
           /* 'postal_code'=> $this->postal_code,
            'latitude' => $this->latitude/1000000,
            'longitude' => $this->longitude/1000000,
            'city' => [new CityResource($this->City)],
            'state'=>[new StateResource($this->State)],
            'country' => [new CountryResource($this->Country)]*/
        ];
    }
}
