<?php

namespace App\Http\Resources;

use App\Models\StatusMap;
use Illuminate\Http\Resources\Json\JsonResource;

class ManagerSprintTaskHistoryResource extends JsonResource
{
    public $resource;
    public $status;
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
        $status = StatusMap::getDescription($this->status_id);
        $breakStatus = wordwrap($status,20,"\n");

    return [
            'id' => $this->status_id??'',
            'description' => $breakStatus??'',
            'created_at' => isset($this->date) ? ConvertTimeZone($this->date,'UTC','America/Toronto') : '',
        ];
    }
}
