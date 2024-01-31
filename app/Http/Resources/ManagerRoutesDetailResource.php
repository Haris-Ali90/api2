<?php

namespace App\Http\Resources;

use App\Http\Traits\BasicModelFunctions;
use App\Models\SprintTaskHistory;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class ManagerRoutesDetailResource extends JsonResource
{
    use BasicModelFunctions;
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
        $customer_name = '';
        $customer_phone = '';
        $customer_id = '';
        $tracking_id = '';
        $delivery = '';
        $distance = '';
        $address = '';
        $status = '';
        $status_code = '';
        if (isset($this->managerSprintTask->managerMerchantIds))
        {
            $tracking_id = $this->managerSprintTask->managerMerchantIds->tracking_id;
        }

        if (isset($this->managerSprintTask->managerSprintContact))
        {
            $customer_name = $this->managerSprintTask->managerSprintContact->name;
            $customer_phone = $this->managerSprintTask->managerSprintContact->phone;
            $customer_id = $this->managerSprintTask->managerSprintContact->id;
        }

        if (isset($this->managerSprintTask->managerSprintsSprints))
        {
            $sprint_id = $this->managerSprintTask->managerSprintsSprints->id;
            $delivery_time = SprintTaskHistory::where('sprint_id',$sprint_id)
                ->select((\Illuminate\Support\Facades\DB::raw('MAX(CASE WHEN status_id IN (17,113,114,116,117,118,132,138,139,144,101,102,103,104,105,106,107,108,109,110,111,112,131,135,136) THEN CONVERT_TZ(created_at,"UTC","America/Toronto") ELSE NULL END) as delivery_time')))->first();
            $delivery  = $delivery_time?$delivery_time->delivery_time:'';
        }

        if (isset($this->distance))
        {
            $distance = round($this->distance/1000,2);
        }

        if (isset($this->managerSprintTask->managerLocation))
        {
            $address = $this->managerSprintTask->managerLocation->address;
        }

        if (isset($this->managerSprintTask->managerSprintsSprints))
        {
            $status_code = $this->managerSprintTask->managerSprintsSprints->status_id;

//            dd($this->getStatusCodesWithKey('status_labels.'.$status_code));
            $status = $this->getStatusCodesWithKey('status_labels.'.$status_code);
        }
        return [
            'tracking_id' => $tracking_id??'N/A',
            'route_label' => 'R-'.$this->route_id.'-'.$this->ordinal??'N/A',
            'ordinal' => $this->ordinal??'N/A',
            'customer_id' => $customer_id??'N/A',
            'customer_name' => $customer_name??'N/A',
            'customer_phone' => $customer_phone??'N/A',
            'delivery' => $delivery??'N/A',
            'distance' => $distance.'0 km' ??'N/A',
            'address' => $address??'N/A',
            'status' => $status??'N/A',
            'status_code' => $status_code??'N/A',


        ];
    }
}
