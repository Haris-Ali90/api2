<?php

namespace App\Http\Resources;

use App\Models\Sprint;
use App\Models\Vendors;
use App\Models\Location;
use Illuminate\Http\Resources\Json\JsonResource;


class JoeyStorePickupResource extends JsonResource
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
        $sprint = Sprint::whereNull('deleted_at')->where('id', $this->sprint_id)->first();
        $vendor=[];

        $merchantOrderNum = '';
        if (isset($sprint->sprintTask)) {
            foreach($sprint->sprintTask as $task){
                if($task->type == 'dropoff'){
                    if(isset($task->merchantIds)){
                        $merchantOrderNum = ($task->merchantIds->merchant_order_num) ? $task->merchantIds->merchant_order_num : '';
                    }
                }
            }
        }

        if(isset($sprint)){
            $vendor = Vendors::find($sprint->creator_id);
            $address = $vendor->business_address;
            if($vendor->business_address == null){
                $location = Location::find($vendor->location_id);
                $address = $location->address;
            }
        }



        return [
            'task_id' =>$this->task_id ,
            'sprint_id' => $this->sprint_id ,
            'tracking_id' => $this->tracking_id,
            'route_id' => $this->route_id,
            'vendor_name' => ($vendor->name) ?? '',
            'merchant_order_no' => $merchantOrderNum,
            'vendor_id' => ($vendor->id) ?? '',
            'vendor_address' => ($address) ?? '',
        ];
    }
}
