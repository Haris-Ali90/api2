<?php

namespace App\Http\Resources;

use App\Models\SprintTaskHistory;
use App\Models\StatusMap;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class JoeyPayoutReportResource extends JsonResource
{
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
        $actual_duration = "start date not available due to you did not pickup any order";
        if(isset($this->actual_duration))
        {   $match = trim(str_replace(' ','_',$this->actual_duration));
            if($match != 'start_date_not_set' && $match != 'end_date_not_set')
            {
                $actual_duration = $this->actual_duration;
            }
        }

        return [

            'route_id' => $this->route_id ?? '',
            'completed_drops' => $this->total_completed_orders??'',
            'amount' => $this->final_payout ?? '',
            'city' => $this->city_name??'',
            'date' => $this->created_at->toDateTimeString(),

            'route_detail' =>
                [
            'order_detail' =>
                [
                    'route_id' => $this->route_id ?? '',
                    'broker_name' => $this->broker_name ?? '',
                    'zone_name' => $this->zone_name ?? '',
                    'city_name' => $this->city_name ?? '',
                    'total_completed_orders' => $this->total_completed_orders ?? '',
                    'total_return_orders' => $this->total_return_orders ?? '',
                ],
            'distance_and_time' =>
                [
                    'plan_estimated_duration' => $this->plan_estimated_duration ?? '',
                    'total_km_by_routific' => $this->total_km_by_routific ?? '',
                    'actual_km' => $this->actual_km ?? '',
                    'actual_duration' => $actual_duration,
                ],
            'finance' =>
                [
                    'manual_adjustment' => $this->manual_adjustment ?? '',
                    'payout_without_tax' => $this->payout_without_tax ?? '',
                    'flag_total_bonus' => $this->flag_total_bonus ?? '',
                    'flag_total_deduction' => $this->flag_total_deduction ?? '',
                    'tax_amount' => $this->tax_amount ?? '',
                    'tax_percentage' => $this->tax_percentage ?? '',
                ],
                    ],
            ];
    }
}
