<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ConfirmationResource extends JsonResource
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
        $data=[];
       // dd($this->description);
        if($this->name=='pin'){
            $data['pin']=$this->description??'';
        }
        if($this->name=='confirm signature'){
            $data['signature']=$this->description??'';
        }
        if($this->name=='seal'){
            $data['seal']=$this->description??'';
        }
        if($this->name=='order-number'){
            $data['order-number']=$this->description??'';
        }
        if($this->name=='cart-items'){
            $data['items']=$this->description??'';
        }


        return $data;
    }
}
