<?php

namespace App\Http\Resources;

use App\Http\Traits\BasicModelFunctions;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class ManagerRouteOrdersCountResource extends JsonResource
{
    use BasicModelFunctions;

    public function __construct($resource)
    {
        parent::__construct($resource);
        $this->resource = $resource;;

    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'total_route_count' => $this['total_route']??'',
            'normal_route_count' => $this['normal_route']??'',
            'custom_route_count' => $this['custom_route']??'',
            'big_box_route_count' => $this['big_box_route']??''
        ];
    }
}