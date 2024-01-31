<?php

namespace App\Http\Resources;

use App\Models\Location;
use App\Models\SprintTasks;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderListResource extends JsonResource
{

    public function __construct($resource)
    {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {

        $data=[];
        $task = SprintTasks::where('id', $this->task_id)->first();
        $location = Location::where('id', $task->location_id)->first();
        $data = [
            'id' => $this->route_id,
            'address' => ($location->address) ?? 'N/A',
            'latitude' => ($location->latitude) ? $location->latitude/1000000 : 0,
            'longitude' => ($location->longitude) ? $location->longitude/1000000 : 0,
            'ordinal' => $this->ordinal
        ];
        return $data;
    }


}
