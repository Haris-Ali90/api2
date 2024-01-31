<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
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
        $name='';
        if(!empty($this)) {

            if ($this->creator_type == 'vendor') {
                if(!empty($this->vendor)) {
                    $name = $this->vendor->first_name . ' ' . $this->vendor->last_name ?? '';
                }
            }
            else {
                if(!empty($this->joey)) {
                    $name = $this->joey->first_name .' ' . $this->joey->last_name ?? '';
                }
            }
        }
        return [
            'name' => $name??'',
            'rating'=>$this->rating??'',
            'notes'=>$this->notes??'',
            'created_at' => $this->created_at.""
        ];
    }
}
