<?php

namespace App\Http\Resources;


use Illuminate\Http\Resources\Json\JsonResource;

class ComplaintResource extends JsonResource
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
            'id' => $this->id??'',
            'order_id'=> $this->order_id??'',
            'joey_id'=>$this->joey_id??'',
            'type'=>$this->type??'',
            'description'=>$this->description??'',
            'status'=>$this->status??'',

        ];
    }
}
