<?php

namespace App\Http\Resources;

use App\Http\Traits\BasicModelFunctions;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class ManagerAllCountResource extends JsonResource
{
    use BasicModelFunctions;
    public function __construct($resource)
    {
        parent::__construct($resource);
        $this->resource=$resource;;

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
            'total_counts' => $this['total']??'',
            'sorted_counts' => $this['sorted']??'',
            'pickup_counts' => $this['pickup']??'',
            'delivered_order_counts' => $this['delivered_order']??'',
            'return_orders_counts' => $this['return_orders']??'',
            'hub_return_scan_counts' => $this['hub_return_scan']??'',
            'hub_not_return_scan_counts' => $this['hub_not_return_scan']??'',
            'not_scan_counts' => $this['notscan']??''

        ];
    }
}