<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ManagerListResource extends JsonResource
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
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
