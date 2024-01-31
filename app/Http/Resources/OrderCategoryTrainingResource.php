<?php

namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderCategoryTrainingResource extends JsonResource
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
            'order_category_id'=> $this->order_category_id??'',
            'name'=> $this->name??'',
            'description'=> $this->description??'',
            'type'=> $this->type??'',
             'url'=> $this->url??'',
             'extension'=> $this->extension??'',
             'seen'=> $this->seen??'',
            'duration'=> $this->duration??'',
            'title'=> $this->title??'',

        ];
    }
}
