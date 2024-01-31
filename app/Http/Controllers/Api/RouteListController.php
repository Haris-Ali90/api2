<?php

namespace App\Http\Controllers\Api;

use App\Classes\RestAPI;
use App\Events\Api\SocketNotificationEvent;
use App\Http\Resources\JoeyApplyJobFilterResource;
use App\Http\Resources\NewJoeyRouteResource;
use App\Http\Resources\NewZoneListResource;
use App\Http\Resources\OrderListResource;
use App\Models\HubZones;
use App\Models\JoeyJobFilter;
use App\Models\JoeyRouteLocation;
use App\Models\JoeyRoutes;
use App\Models\Sprint;
use App\Models\SprintTasks;
use App\Models\ZoneRouting;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RouteListController extends ApiBaseController
{
    private $userRepository;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(UserRepositoryInterface $userRepository)
    {

        $this->userRepository = $userRepository;

    }

    public function index()
    {
        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);
            if (empty($joey)) {
                return RestAPI::response('Joey record not found', false);
            }

            $hubZone = JoeyJobFilter::whereNull('deleted_at')->where('joey_id', $joey->id)->first();
            $currentDate = date('Y-m-d H:i:s');
            $previousDate = date('Y-m-d H:i:s', strtotime('-7 days', strtotime($currentDate)));

            $joeyRoutes = JoeyRoutes::join('joey_route_locations','joey_route_locations.route_id','=','joey_routes.id')
                ->join('sprint__tasks','sprint__tasks.id','=','joey_route_locations.task_id')
                ->whereNull('joey_routes.joey_id')
                ->whereNull('joey_routes.deleted_at')
                ->whereNull('joey_route_locations.deleted_at')
                ->whereNull('sprint__tasks.deleted_at')
                ->whereNotIn('sprint__tasks.status_id', [36,0])
                ->whereBetween('joey_routes.date', [$previousDate,$currentDate])
                ->whereIn('joey_routes.mile_type', [3,5]);

            if(!empty($hubZone)){

                $hubIds = HubZones::whereNull('deleted_at')->where('zone_id', $joey->preferred_zone)->pluck('hub_id');
                $zoneIdArray = ZoneRouting::whereNull('deleted_at')
//                    ->whereNull('is_custom_routing')
//                    ->where('title','NOT LIKE','%test%')
                    ->whereIn('hub_id', $hubIds)->pluck('id')
                    ->toArray();

                if(in_array($hubZone->zone_id, $zoneIdArray)){
                    $joeyRoutes = $joeyRoutes->where('joey_routes.zone', $hubZone->zone_id);

                }else{
                    $joeyRoutes = $joeyRoutes->whereIn('joey_routes.zone', $zoneIdArray);
                }

                if($hubZone->distance_min != null && $hubZone->distance_min != 0 && $hubZone->distance_max != null && $hubZone->distance_max != 0){
                    $joeyRoutes = $joeyRoutes->whereBetween('joey_routes.total_distance', [$hubZone->distance_min, $hubZone->distance_max]);
                }

            }else{
                $hubIds = HubZones::whereNull('deleted_at')->where('zone_id', $joey->preferred_zone)->pluck('hub_id');
                $zoneId = ZoneRouting::whereNull('deleted_at')
//                    ->whereNull('is_custom_routing')
//                    ->where('title','NOT LIKE','%test%')
                    ->whereIn('hub_id', $hubIds)->pluck('id');
                $joeyRoutes = $joeyRoutes->whereIn('joey_routes.zone', $zoneId);
           }

            $joeyRoutes = $joeyRoutes->groupBy('joey_route_locations.route_id')
                ->get(['joey_routes.id','joey_routes.date','joey_routes.total_distance','joey_routes.hub', 'joey_routes.zone','joey_routes.mile_type']);

            if (empty($joeyRoutes)) {
                return RestAPI::response('route record not found', false);
            }

            $routeList = NewJoeyRouteResource::collection($joeyRoutes);
            return RestAPI::response($routeList, true, 'joey route zone wise');
            DB::commit();

        }catch(\Exception $e){
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
    }

    public function accept(Request $request)
    {
        $validate = Validator::make($request->all(),[
            'route_id' => 'required|exists:joey_routes,id'
        ]);
        $data = $request->all();
        $joey = $this->userRepository->find(auth()->user()->id);

        if (empty($joey)) {
            return RestAPI::response('Joey record not found', false);
        }

        if($validate->fails() == true){
            return RestAPI::response('Invalid route id', false);
        }

        $acceptRoute = JoeyRoutes::whereNotNull('joey_id')->where('id', $data['route_id'])->first();

        if(!empty($acceptRoute)){
            return RestAPI::response('This route is already accepted', false);
        }

//        $taskIds = JoeyRouteLocation::where('route_id',$data['route_id'])->pluck('task_id');
//        $sprintIds = SprintTasks::whereIn('id',$taskIds)->groupBy('sprint_id')->pluck('sprint_id');
//        Sprint::whereIn('id', $sprintIds)->update(['joey_id' => auth()->user()->id]);

        $route = JoeyRoutes::where('id', $data['route_id'])->update(['joey_id'=>auth()->user()->id]);

        return RestAPI::response(new \stdClass(), true, 'accept route successfully');
    }

    public function zoneList()
    {
        DB::beginTransaction();
        try {

            $response = [];

            $joey = $this->userRepository->find(auth()->user()->id);
            if (empty($joey)) {
                return RestAPI::response('Joey record not found', false);
            }

            $hubIds = HubZones::whereNull('deleted_at')->where('zone_id', $joey->preferred_zone)->pluck('hub_id');


            if(count($hubIds) == 0){
                return RestAPI::response('JoeyCo is not doing deliveries in your preferred zone', false);
            }
//            ->where('title','NOT LIKE','%test%')
            $zoneList = ZoneRouting::whereNull('deleted_at')
//                ->whereNull('is_custom_routing')
//                ->where('title','NOT LIKE','%test%')
                ->whereIn('hub_id', $hubIds)->get();

            if (count($zoneList) == 0) {
                return RestAPI::response('JoeyCo is not doing deliveries in your preferred zone', false);
            }

            $zoneLists = NewZoneListResource::collection($zoneList);

            return RestAPI::response($zoneLists, true, 'zone list fetch successfully');
            DB::commit();

        }catch(\Exception $e){
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
    }

    public function orderList(Request $request)
    {
        $validate = Validator::make($request->all(),[
            'route_id' => 'required|exists:joey_routes,id'
        ]);

        if($validate->fails() == true){
            return RestAPI::response('Invalid route id', false);
        }

        $data = $request->all();
        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);
            if (empty($joey)) {
                return RestAPI::response('Joey record not found', false);
            }

            $hubZone = JoeyJobFilter::whereNull('deleted_at')->where('joey_id', $joey->id)->first();
            $currentDate = date('Y-m-d H:i:s');
            $previousDate = date('Y-m-d H:i:s', strtotime('-7 days', strtotime($currentDate)));
//            if(empty($hubZone)){
//                return RestAPI::response('Orders not found', false);
//            }

            $routeLocations = JoeyRouteLocation::join('sprint__tasks','sprint__tasks.id','=','joey_route_locations.task_id')
                ->join('joey_routes', 'joey_routes.id', '=', 'joey_route_locations.route_id')
                ->whereNull('joey_route_locations.deleted_at')
                ->whereNull('joey_routes.deleted_at')
                ->whereNull('sprint__tasks.deleted_at')
                ->where('joey_route_locations.route_id',$data['route_id'])
                ->whereBetween('joey_routes.date', [$previousDate,$currentDate])
                ->whereIn('joey_routes.mile_type', [3,5]);


            if(!empty($hubZone)){

                $hubIds = HubZones::whereNull('deleted_at')->where('zone_id', $joey->preferred_zone)->pluck('hub_id');
                $zoneIdArray = ZoneRouting::whereNull('deleted_at')
//                    ->whereNull('is_custom_routing')
//                    ->where('title','NOT LIKE','%test%')
                    ->whereIn('hub_id', $hubIds)->pluck('id')
                    ->toArray();

                if(in_array($hubZone->zone_id, $zoneIdArray)){
                    $routeLocations = $routeLocations->where('joey_routes.zone', $hubZone->zone_id);

                }else{
                    $routeLocations = $routeLocations->whereIn('joey_routes.zone', $zoneIdArray);
                }

//                $routeLocations = $routeLocations->where('joey_routes.zone', $hubZone->zone_id);

                if($hubZone->distance_min != null && $hubZone->distance_min != 0 && $hubZone->distance_max != null && $hubZone->distance_max != 0){
                    $routeLocations = $routeLocations->whereBetween('joey_routes.total_distance', [$hubZone->distance_min, $hubZone->distance_max]);
                }

            }else{
                $hubIds = HubZones::whereNull('deleted_at')->where('zone_id', $joey->preferred_zone)->pluck('hub_id');
                $zoneId = ZoneRouting::whereNull('deleted_at')
//                    ->whereNull('is_custom_routing')
//                    ->where('title','NOT LIKE','%test%')
                    ->whereIn('hub_id', $hubIds)->pluck('id');
                $routeLocations = $routeLocations->whereIn('joey_routes.zone', $zoneId);
            }

            $routeLocations = $routeLocations->get(['sprint__tasks.id', 'joey_route_locations.ordinal', 'joey_route_locations.task_id', 'joey_route_locations.route_id']);

            if (count($routeLocations) < 1) {
                return RestAPI::response('Orders not found', false);
            }

            $OrderList = OrderListResource::collection($routeLocations);
            return RestAPI::response($OrderList, true, 'order list fetch successfully');
            DB::commit();

        }catch(\Exception $e){
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
    }

    public function routeApplyFilter(Request $request)
    {

        $validate = Validator::make($request->all(),[
            'zone_id' => 'required'
        ]);

        if($validate->fails() == true){
            return RestAPI::response('zone id required', false);
        }

        $data = $request->all();

//        $startTime = ConvertTimeZone($data['start_time'], $data['timezone'], 'UTC', 'H:i:s');
//        $endTime = ConvertTimeZone($data['end_time'], $data['timezone'], 'UTC', 'H:i:s');
        $joey = $this->userRepository->find(auth()->user()->id);

        if (empty($joey)) {
            return RestAPI::response('Joey record not found', false);
        }

        if($request->has('distance_min') && $request->has('distance_max')){
            if($data['distance_min'] > $data['distance_max']){
                return RestAPI::response('your min distance should be less than max distance value', false);
            }
        }

        if($request->has('price_min') && $request->has('price_max')){
            if($data['price_min'] > $data['price_max']){
                return RestAPI::response('your min price should be less than max price', false);
            }
        }

        if($request->has('start_time') && $request->has('end_time')){
            if($data['start_time'] > $data['end_time']){
                return RestAPI::response('your start time should be less than end time', false);
            }
        }

        $zoneRouting = ZoneRouting::whereNull('deleted_at')->where('id',$data['zone_id'])->first();

        if($zoneRouting == null){
            return RestAPI::response('this zone is not exists', false);
        }

        $joeyJobFilters = JoeyJobFilter::whereNull('deleted_at')->where('joey_id',$joey->id)->exists();

        if($joeyJobFilters == false){
            $data = [
                'joey_id' => $joey->id,
                'zone_id' => $data['zone_id'],
                'distance_min' => ($request->has('distance_min')) ? $data['distance_min'] : null,
                'distance_max' => ($request->has('distance_max')) ? $data['distance_max'] : null,
                'duration_min' => ($request->has('start_time')) ? $data['start_time'] : null,
                'duration_max' => ($request->has('end_time')) ? $data['end_time'] : null,
                'price_min' => ($request->has('price_min')) ? $data['price_min'] : null,
                'price_max' => ($request->has('price_max')) ? $data['price_max'] : null
            ];
            JoeyJobFilter::create($data);
        }else{
            $data = [
                'joey_id' => $joey->id,
                'zone_id' => $data['zone_id'],
                'distance_min' => ($request->has('distance_min')) ? $data['distance_min'] : null,
                'distance_max' => ($request->has('distance_max')) ? $data['distance_max'] : null,
                'duration_min' => ($request->has('start_time')) ? $data['start_time'] : null,
                'duration_max' => ($request->has('end_time')) ? $data['end_time'] : null,
                'price_min' => ($request->has('price_min')) ? $data['price_min'] : null,
                'price_max' => ($request->has('price_max')) ? $data['price_max'] : null
            ];
            JoeyJobFilter::whereNull('deleted_at')->where('joey_id',$joey->id)->update($data);
        }

        return RestAPI::response(new \stdClass(), true, 'filter apply successfully');
    }

    public function getApplyFilter()
    {
        DB::beginTransaction();
        try {
            $response=[];
            $joey = $this->userRepository->find(auth()->user()->id);
            if (empty($joey)) {
                return RestAPI::response('Joey record not found', false);
            }

            $joeyApplyFilter = JoeyJobFilter::whereNull('deleted_at')->where('joey_id',$joey->id)->first();

            $jobFilter = new JoeyApplyJobFilterResource($joeyApplyFilter);

            $response['filter'] = (!empty($joeyApplyFilter)) ? $jobFilter : null;
            $response['distance'] = ['min'=>0, 'max'=>150];
            $response['duration'] = ['min'=>1, 'max'=>9];

            return RestAPI::response($response, true, 'filter fetch successfully');
            DB::commit();

        }catch(\Exception $e){
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
    }
}
