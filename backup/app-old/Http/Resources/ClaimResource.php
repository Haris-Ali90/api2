<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ClaimResource extends JsonResource
{


    public function __construct($resource, $status)
    {

        parent::__construct($resource, $status);

    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        if($this->status == 1)
            $status = 'Approved';

        if($this->status == 2)
            $status = 'Not Approved';

        if($this->status == 3)
            $status = 'Re Submit';
        return [
            'claim_type' => ($this->type == 'tracking_id') ? 'Ecommerce' : 'Grocery',
            'tracking_id' => ($this->tasks != null)?$this->tasks->merchantIds->tracking_id:'',
            'merchant_order_no' => ($this->tasks != null)?$this->tasks->merchantIds->merchant_order_num:'',
            'route_no' => 'R-'.$this->route_id,
            'value' => $this->amount,
            'date' => date('Y-m-d H:i:s', strtotime($this->created_at)),
            'status' => $status,
            'reason' => $this->reason?$this->reason->title:'',
        ];
    }
}
