<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FaqsResource extends JsonResource
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
        
            'Vendor_id'=> $this->vendor_id??'',
            'Faq_title'=> $this->faq_title??'',
            'Faq_description'=> $this->faq_description??'',
     

        ];
    }
}
