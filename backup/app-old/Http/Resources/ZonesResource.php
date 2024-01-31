<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ZonesResource extends JsonResource
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
        $latitude = $this->latitude/1000000;
        $longitude = $this->longitude/1000000;
        // $lat[0] = substr( $this->latitude, 0, 2);
        // $lat[1] = substr( $this->latitude, 2);
        // $latitude = $lat[0].".".$lat[1];

        // $long[0] = substr( $this->longitude, 0, 3);
        // $long[1] = substr( $this->longitude, 3);
        //$longitude = $long[0].".".$long[1];


        return [
            'id' => $this->id,
            'num'=>'ZN-'.$this->id,
            'name' => $this->name,
            'latitude'=>  $latitude,
            'longitude' => $longitude,
            'radius'=> $this->radius,

        ];
    }
}
