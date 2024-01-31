<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SprintConfirmationResource extends JsonResource
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
            'ordinal'=> $this->ordinal,
            'task_id' => $this->task_id,
            'joey_id' => $this->joey_id,
            'name' => $this->name,
            'title'=> $this->title,
            'description' => $this->description,
            'confirmed' => $this->confirmed,
            'input_type' => $this->input_type,
            'email'=> $this->email,
            'attachment_path' => $this->attachment_path,
            'image_old' => $this->image_old,
            'action'=>[
                'url'=>'https://api2.joeyco.com/api/v1/task-confirmations/'.$this->id,
                'method'=>'POST',
            ]

        ];
    }
}
