<?php

namespace App\Http\Resources;

use App\Models\SprintTaskHistory;
use App\Models\StatusMap;
use App\Models\OptimizeItinerary;
use App\Models\Vendor;
use App\Models\Hub;
use App\Models\Sprint;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class BundleHubOrderResource extends JsonResource
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
        $sprint = Sprint::with('sprintTask', 'sprintTask.merchantIds')
                    ->whereHas('sprintTask', function ($query){
                        $query->where('type', 'dropoff');
                    })->whereNull('deleted_at')->find($this->sprint_id);

        $hub = Hub::whereNull('deleted_at')->find($this->hub_id);

        $trackingId='';
        $merchantOrderNo='';
        if (isset($sprint->sprintTask)) {
            foreach($sprint->sprintTask as $task){
                if($task->type == 'dropoff'){
                    if(isset($task->merchantIds)){
                        $trackingId = ($task->merchantIds->tracking_id) ? $task->merchantIds->tracking_id : '';
                        $merchantOrderNo = ($task->merchantIds->merchant_order_num) ? $task->merchantIds->merchant_order_num : '';
                    }
                }
            }
        }

        $data = [
            'id' => $this->id,
            'bundle_id' => 'MMB-'.$hub->id,
            'hub_name' => $hub->title,
            'hub_address' => $hub->address,
            'sprint_id' => 'CR-'.$this->sprint_id,
            'tracking_id' => $trackingId,
            'merchant_order_no' => $merchantOrderNo,
        ];

        return $data;
    }


}
