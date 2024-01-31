<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class FlagResource extends JsonResource
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
            'flag_type' => Str::ucfirst($this->flagged_type),
            'category_name' => $this->flag_cat_name,
            'tracking_id' => $this->tracking_id,
            'route_no' => 'R-' . $this->route_id,
            'customer_order_no' => $this->MerchantidsByTrackingID ? $this->MerchantidsByTrackingID->merchant_order_num : '',
            'date' => date('Y-m-d H:i:s', strtotime($this->created_at)),
            'status' => ($this->is_approved == 0) ? 'not approved' : 'approved',
            'joeyco_order_number' => $this->sprint_id,
            'category_value_applied' => $this->flagDetail ? Str::ucfirst($this->flagDetail->incident_value_applied) : '',
            'detail' => $this->flagDetail ? $this->flagDetail->JosnValuesDecode() : null,
            'note' => null,
        ];
    }
}
