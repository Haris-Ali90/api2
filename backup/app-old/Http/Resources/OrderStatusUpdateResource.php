<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;
class OrderStatusUpdateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $copyright = "Copyright © 2022 JoeyCo Inc. All rights reserved.";
        $http = array(
            "code"=>200,
            "message"=>"OK"
        );
        $orders = array();

        return [
            'id'=> $this->id,
            'joey_id'=> $this->joey_id,
            'creator_id'=> $this->creator_id,
            'creator_type'=> $this->creator_type,
            'checked_out_at'=> $this->checked_out_at,
            'vehicle_id'=> $this->vehicle_id,
            'route_json'=> $this->route_json,
            'distance'=> $this->distance,
            'distance_allowance'=> $this->distance_allowance,
            'distance_charge'=> $this->distance_charge,
            'task_total'=> $this->task_total,
            'subtotal'=> $this->subtotal,
            'tax'=> $this->tax,
            'tip'=> $this->tip,
            'credit_amount'=> $this->credit_amount,
            'total'=> $this->total,
            'status_id'=> $this->status_id,
            'status_copy'=> $this->status_copy,
            'active'=> $this->active,
            'merchant_charge'=> $this->merchant_charge,
            'joey_pay'=> $this->joey_pay,
            'joey_tax_pay'=> $this->joey_tax_pay,
            'joeyco_pay'=> $this->joeyco_pay,
            'make_payment_total'=> $this->make_payment_total,
            'collect_payment_total'=> $this->collect_payment_total,
            'push_at'=> $this->push_at,
            'broadcast_location_id'=> $this->broadcast_location_id,
            'level'=> $this->level,
            'visibility'=> $this->visibility,
            'optimize_route'=> $this->optimize_route,
            'only_this_vehicle'=> $this->only_this_vehicle,
            'credit_card_id'=> $this->credit_card_id,
            'last_eta_update'=> $this->last_eta_update,
            'min_score'=> $this->min_score,
            'last_task_id'=> $this->last_task_id,
            'is_sameday'=> $this->is_sameday,
            'rbc_deposit_number'=> $this->rbc_deposit_number,
            'cash_on_hand'=> $this->cash_on_hand,
            'timezone'=> $this->timezone,
            'is_cc_preauthorized'=> $this->is_cc_preauthorized,
            'is_hook'=> $this->is_hook,
            'is_updated'=> $this->is_updated,
            'merchant_order_num'=> $this->merchant_order_num,
            'store_num'=> $this->store_num,
            'is_hub'=> $this->is_hub,
            'direct_pickup_from_hub'=> $this->direct_pickup_from_hub,
            'in_hub_route'=> $this->in_hub_route,
            'date_updated'=> $this->date_updated
    ];


    }
}
