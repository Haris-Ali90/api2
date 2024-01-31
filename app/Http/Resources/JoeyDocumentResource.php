<?php

namespace App\Http\Resources;


use Illuminate\Http\Resources\Json\JsonResource;

class JoeyDocumentResource extends JsonResource
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
            'document_type ' =>$this->document_type??'',
            'document_data' => $this->document_data??'',
            'exp_date'=> $this->exp_date??''

        ];
    }
}
