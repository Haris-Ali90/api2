<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MidMilePickAndDropResource extends JsonResource
{

    private $status;

    public function __construct($resource, $status)
    {

        parent::__construct($resource, $status);
        $this->status = $status;

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
            'hub_id' => $this->id,
            'hub_title' => $this->title,
            'hub_address' => $this->address,
            'hub_lat' => $this->hub_latitude,
            'hub_lng' => $this->hub_longitude,
            'type' => $this->type,
            'status_id' => $this->status,
        ];
    }
}
