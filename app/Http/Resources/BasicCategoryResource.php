<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BasicCategoryResource extends JsonResource
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
            'question_id' => $this->id??'',
            'category_name'=>$this->name??'',
            'type'=>$this->type??'',
            'is_passed'=>joeyBasicSeenCategoryResource::collection($this->quizQuestion),

        ];
    }
}
