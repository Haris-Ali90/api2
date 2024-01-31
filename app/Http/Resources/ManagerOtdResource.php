<?php

namespace App\Http\Resources;

use App\Http\Traits\BasicModelFunctions;
use App\Models\SprintTaskHistory;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class ManagerOtdResource extends JsonResource
{
    protected $type;
    use BasicModelFunctions;
    public function __construct($resource, $value)
    {
        parent::__construct($resource);
        $this->resource=$resource;
        $this->type = $value;

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
            'on_time_otd_percentage' => $this['y2']??'N/A',
            'off_time_otd_percentage' => $this['y1']??'N/A',
            'on_time_otd_counts' => $this['ontime']??'N/A',
            'off_time_otd_counts' => $this['offtime']??'N/A',
            'type' => $this->type??'',

        ];
    }
}
