<?php

namespace App\Http\Resources;

use App\Models\StatusMap;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderConfirmResource extends JsonResource
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
        $status = StatusMap::getDescription($this->status_id);
        return [
            'task_id' => $this->id??'' ,
            'merchant_order_num' => $this->merchant_order_num??'',
            'tracking_id' => $this->tracking_id??'',
            'message' => 'Confirmed successfully',
         //   'status' => $status??'',
        
        ];
    }
}
