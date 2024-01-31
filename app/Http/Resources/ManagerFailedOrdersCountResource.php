<?php

namespace App\Http\Resources;

use App\Http\Traits\BasicModelFunctions;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class ManagerFailedOrdersCountResource extends JsonResource
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
            'failed_order_count' => $this['failed']??'',
            'system_failed_order_count' => $this['system_failed_order']??'',
            'not_in_system_failed_order_count' => $this['not_in_system_failed_order']??''

        ];
    }
}