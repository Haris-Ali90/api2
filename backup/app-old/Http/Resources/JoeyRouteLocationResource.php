<?php

namespace App\Http\Resources;

use App\Models\SprintTaskHistory;
use App\Models\StatusMap;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class JoeyRouteLocationResource extends JsonResource
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

    //dd( $this->route_id);
        return [
            'route_id' => $this->route_id??'',
            'ordinal' => $this->ordinal??'',
        ];

    }
}
