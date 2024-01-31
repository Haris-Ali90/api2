<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class JoeyLIstResource extends JsonResource
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
            'first_name' => $this->first_name,
            'last_name'=>  $this->last_name
        ];
    }
}
