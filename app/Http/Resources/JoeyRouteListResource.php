<?php

namespace App\Http\Resources;

use App\Models\JoeyRouteLocation;
use App\Models\RouteHistory;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class JoeyRouteListResource extends JsonResource
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


//        $routeCount=RouteHistory::where('joey_id',$this->joey_id)
//            ->where('route_id',$this->route_id)
//            ->where('status',2)
//            ->count();
        $routeCount=$this->routeHistoryForJoey->count();

        $routedistance= JoeyRouteLocation::where('route_id',$this->route_id)
            ->orderBy('created_at', 'asc')
            ->get('distance');


        $totalDistance= $routedistance->sum('distance')/1000;

        $duration =RouteHistory::where('joey_id',$this->joey_id)
            ->where('route_id',$this->route_id)
            ->orderBy('created_at', 'asc')
            ->pluck('created_at')
           ->toArray();


        $firsttime = Carbon::parse($duration[0]);
        $lasttime = Carbon::parse(end($duration));
        $totalDuration = explode(':',$lasttime->diff($firsttime)->format('%H:%I:%S'));
       // $totalDuration = $totalDuration>format('h:i:s')-;
        $date_time=convertTimeZone($this->created_at->format('Y/m/d H:i:s'),'UTC',$this->convert_to_timezone,'Y-m-d H:i:s');
        $date=convertTimeZone($this->created_at->format('Y/m/d H:i:s'),'UTC',$this->convert_to_timezone,'d M Y');


        return [
            'id' => $this->id ,
            'route_id' => $this->route_id??0,
            'dropOff'=>$routeCount??'',
            'distance' =>round($totalDistance, 2),
            'duration'=>$totalDuration[0].' Hrs'.' '.$totalDuration[1].' Mins'.' '.$totalDuration[2].' Sec'??'',
            'date'=>$date??'',
            'date_time' => $this->created_at->format('Y-m-d H:i:s')??'',
        ];
    }
}
