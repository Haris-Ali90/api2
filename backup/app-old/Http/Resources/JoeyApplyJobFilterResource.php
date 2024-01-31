<?php

namespace App\Http\Resources;

use App\Models\HubZones;
use App\Models\ZoneRouting;
use Illuminate\Http\Resources\Json\JsonResource;

class JoeyApplyJobFilterResource extends JsonResource
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
        $zone = ZoneRouting::where('id', auth()->user()->preferred_zone)->first();
        $zoneRoute = ZoneRouting::where('id', $this->zone_id)->first();


//        $hubIds = HubZones::whereNull('deleted_at')->where('zone_id', auth()->user()->preferred_zone)->pluck('hub_id');
//        $zoneIdArray = ZoneRouting::whereNull('deleted_at')->whereNull('is_custom_routing')->where('title','NOT LIKE','%test%')->whereIn('hub_id', $hubIds)->pluck('id')->toArray();

//        if(in_array($this->zone_id, $zoneIdArray)){
//           $distanceMin = (float)$this->distance_min;
//           $distanceMax = (float)$this->distance_max;
//        }else{
//            $distanceMin = null;
//            $distanceMax = null;
//        }


        $data = [
            'id' => $this->id,
            'joey_id' => $this->joey_id,
            'zone_id' => $this->zone_id,
            'preferred_zone' => ($zoneRoute) ? $zoneRoute->title : 'N/A',
            'preferred_zone_id' => auth()->user()->preferred_zone,
            'distance_min' => (float)$this->distance_min,
            'distance_max' => (float)$this->distance_max,
            'duration_min' => $this->duration_min,
            'duration_max' => $this->duration_max,
            'price_min' => $this->price_min,
            'price_max' => $this->price_max
        ];
        return $data;
    }


}
