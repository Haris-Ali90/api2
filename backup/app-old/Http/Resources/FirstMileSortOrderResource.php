<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FirstMileSortOrderResource extends JsonResource
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
            'my_hub_order' => ($this->status == $this->id) ? 1 : 0,
            'message' => ($this->status == $this->id) ? 'This order belongs to my hub' : 'This order belongs to other hub' ,
            'status_id' => ($this->status == $this->id) ? 127 : 128,
        ];
    }
}
