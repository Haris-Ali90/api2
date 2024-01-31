<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VendorFaqsResource extends JsonResource
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
            'Id' => $this->id??'',
            'Name'=> $this->name??'',
            'vendor_email'=> $this->email??'',
            'Faqs'=>FaqsResource::collection($this->Faqs),


        ];
    }
}
