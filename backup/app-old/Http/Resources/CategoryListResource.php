<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryListResource extends JsonResource
{
    private $_token = '';

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
            'name'=>$this->name??'',
            'order_count'=>$this->order_count??'',
            'type'=>$this->type??'',
            'quiz_limit'=>$this->quiz_limit??'',
            'score'=>$this->score??'',
        ];
    }
}
