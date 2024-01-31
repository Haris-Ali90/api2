<?php

namespace App\Http\Resources;

use App\Models\SprintTaskHistory;
use App\Models\StatusMap;
use App\Models\OptimizeItinerary;
use App\Models\Vendor;
use App\Models\Hub;
use App\Models\MiJobDetail;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class HubBundleResource extends JsonResource
{
    private $orderCount;

    public function __construct($resource, $orderCount)
    {
        $this->orderCount = $orderCount;
        $this->resource = $resource;

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

        $hub = Hub::find($this->hub_id);
        $reference = MiJobDetail::where('locationid', $this->hub_id)->first();

        $referenceNo = 0;
        if(isset($reference)){
            $referenceNo = $reference->mi_job_id;
        }

//        $orderCount = 0;
//        if($hub->id == $this->hub_id){
//            $orderCount+=1;
//        }

        $data = [
            'id' => $this->id,
            'bundle_id' => 'MMB-'.$this->hub_id,
            'reference_no' => 'MR-'.$referenceNo,
            'hub_name' => $hub->title,
            'hub_address' => $hub->address,
            'hub_latitude' => $hub->hub_latitude,
            'hub_longitude' => $hub->hub_longitude,
            'no_of_order' => $this->orderCount,
        ];

        return $data;
    }


}
