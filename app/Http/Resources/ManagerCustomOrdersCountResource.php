<?php

namespace App\Http\Resources;

use App\Http\Traits\BasicModelFunctions;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class ManagerCustomOrdersCountResource extends JsonResource
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
            'custom_order' => $this['custom_order']??''
        ];
    }
}