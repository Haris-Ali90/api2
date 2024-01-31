<?php

namespace App\Http\Resources;

use App\Models\HubZones;
use App\Models\Joey;
use App\Models\JoeyLocations;
use App\Models\JoeyRouteLocation;
use App\Models\SprintTaskHistory;
use App\Models\StatusMap;
use App\Models\OptimizeItinerary;
use App\Models\Vendor;
use App\Models\Hub;
use App\Models\ZoneRouting;
use App\Models\Zones;
use DateTime;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class NewJoeyRouteResource extends JsonResource
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

//        $hubZone = HubZones::whereNull('deleted_at')->where('zone_id', auth()->user()->preferred_zone)->first();
        $zone = ZoneRouting::where('id',$this->zone)->first();
//        $joey = Joey::find(auth()->user()->id);
//        $hubIds = HubZones::whereNull('deleted_at')->where('zone_id', $joey->preferred_zone)->pluck('hub_id');
//        $zoneId = ZoneRouting::whereNull('deleted_at')->whereIn('hub_id', $hubIds)->pluck('id');
//        $zonePreferred = ZoneRouting::whereIn('id', $this->zone)->first();
        $hub = Hub::find($this->hub);

        $orderCount = JoeyRouteLocation::whereHas('sprintTaskAgainstRouteLocationId', function ($query){
            $query->whereNull('deleted_at');
        })->whereNull('deleted_at')->where('route_id', $this->id)->count();

        $sumDistance = JoeyRouteLocation::getDistanceSum($this->id);

        $routeDate = $this->date;
        $newDate = date("Y-m-d 09:00:00", strtotime($routeDate));

        $data = [
            'route_id' => (int)$this->id,
            'pickup_time' => $newDate,
            'distance' => (float)round($this->total_distance, 2)??0,
            'amount' => (float)123,
            'zone' => (isset($zone)) ? $zone->title : $this->zone,
            'address' => (isset($hub->address)) ? $hub->address : 'N/A',
            'order_count' => $orderCount,
            'mile_type' => $this->mile_type,
        ];

        return $data;
    }


}
