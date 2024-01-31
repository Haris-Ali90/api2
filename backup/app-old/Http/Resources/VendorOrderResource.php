<?php

namespace App\Http\Resources;

use App\Models\SprintTaskHistory;
use App\Models\StatusMap;
use App\Models\OptimizeItinerary;
use App\Models\Vendor;
use App\Models\Location;
use App\Models\Hub;
use App\Models\Sprint;
use App\Models\MicroHubOrder;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class VendorOrderResource extends JsonResource
{
    public function __construct($resource)
    {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $microHubBundle = MicroHubOrder::where('sprint_id',$this->id)->first();
        $vendor = Vendor::whereNull('deleted_at')->find($this->creator_id);

        $location = Location::find($vendor['location_id']);

        $trackingId='';
        $merchantOrderNo='';
        if (isset($this->sprintTask)) {
            foreach($this->sprintTask as $task){
                if($task->type == 'dropoff'){
                    if(isset($task->merchantIds)){
                        $trackingId = ($task->merchantIds->tracking_id) ? $task->merchantIds->tracking_id : '';
                        $merchantOrderNo = ($task->merchantIds->merchant_order_num) ? $task->merchantIds->merchant_order_num : '';
                    }
                }
            }
        }


        $data = [
            'id' => $vendor['id'],
            'vendor_name' => $vendor['name'],
            'vendor_address' => (isset($location)) ? $location->address : '',
            'sprint_id' => 'CR-'.$this->id,
            'tracking_id' => $trackingId,
            'merchant_order_no' => $merchantOrderNo,
        ];

        return $data;



    }


}
