<?php

namespace App\Http\Controllers\Api;

use App\Classes\RestAPI;
use App\Classes\RoutificClient;
use App\Http\Resources\JoeyBroadCastRouteResource;
use App\Http\Resources\NewJoeyRouteResource;
use App\Models\Joey;
use App\Models\Location;
use App\Models\Sprint;
use App\Models\SprintTasks;
use App\Models\SprintZone;
use App\Models\ZoneSchedule;
use App\Models\JoeyRoutes;
use App\Models\JoeyRouteLocation;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class ZoneRouteController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->all();

        $validate = $request->validate([
           'zone_id' => 'required',
            'date' => 'required'
        ]);

        DB::beginTransaction();
        try {

            $sprintIds = SprintZone::where('zone_id', $data['zone_id'])->where('created_at', 'LIKE', '%'.$data['date'].'%')->pluck('sprint_id');

            $schedules = ZoneSchedule::leftJoin('joeys_zone_schedule as jzs','jzs.zone_schedule_id','=', 'zone_schedule.id')
                ->leftJoin('joey_locations as jl','jzs.joey_id','=', 'jl.joey_id')
                ->where('zone_schedule.zone_id',$data['zone_id'])
                ->where('zone_schedule.start_time', 'LIKE', '%'.$data['date'].'%')
                ->orderBy('jl.id', 'DESC')
//                ->groupBy('jl.joey_id')
                ->get(['zone_schedule.start_time', 'zone_schedule.end_time','jzs.joey_id', 'zone_schedule.id', 'jl.latitude as lat', 'jl.longitude as lng', 'zone_schedule.capacity'])->toArray();


            $sprints = Sprint::leftJoin('sprint__tasks as task','task.sprint_id','=', 'sprint__sprints.id')
                ->leftJoin('merchantids as mrids','mrids.task_id','=', 'task.id')
                ->leftJoin('locations as loc', 'loc.id', '=', 'task.location_id')
                ->whereIn('sprint__sprints.id',$sprintIds)
                ->get(['sprint__sprints.id as order_id', 'task.type', 'mrids.start_time',
                'mrids.end_time', 'loc.address as loc_address', 'loc.suite', 'mrids.address_line2', 'task.status_id as task_status_id','loc.id as location_id', 'loc.latitude as loc_latitude',
                'loc.longitude as loc_longitude'])->toArray();

//            dd($sprintIds,$schedules);

            if(empty($sprints)){
                return RestAPI::response('No Visits in their dates', false);
            }

            if(empty($schedules)){
                return RestAPI::response('No Shifts Available of their date', false);
            }

            $payload=[];
            $visits=[];
            $fleets=[];
            foreach($sprints as $visit){
                $visits[$visit['order_id']] = [
                    "location" => [
                        "name" => $visit['loc_address'],
                        "lat" => $visit['loc_latitude']/1000000,
                        "lng" => $visit['loc_longitude']/1000000,
                    ],
                    "load" => 1,
                    "duration" => 2
                ];
            }
            foreach($schedules as $fleet){

                $startStrToTime = strtotime($fleet['start_time']);
                $startTime = date('H:i', $startStrToTime);

                $endStrToTime = strtotime($fleet['end_time']);
                $endTime = date('H:i', $endStrToTime);

                $fleets['joey_'.$fleet['joey_id']]=[
                    "start_location" => [
                        "id" => $fleet['id'],
                        'name' => $sprints[0]['loc_address'],
                        'lat' => $fleet['lat']/1000000,
                        'lng' => $fleet['lng']/1000000,
                    ],
                    "shift_start" => $startTime,
                    'shift_end' => $endTime,
                    'capacity' => $fleet['capacity'],
                    'min_visits_per_vehicle' => 1
                ];
            }

            $payload = [
                "visits" => $visits,
                "fleet" => $fleets,
                'options' => [
                    "shortest_distance" => true,
                    "polylines" => true,
                ]
            ];
//            dd($payload);

            $client = new RoutificClient( '/vrp-long' );
            $client->setData($payload);
            $apiResponse= $client->send();

//            dd($apiResponse);


            if(!empty($apiResponse->error)){
                return ['statusCode'=>400,'message'=>$apiResponse->error];
            }

            if($apiResponse->job_id){
//                dd($apiResponse->job_id);

                $client->setJobID($apiResponse->job_id);
                sleep(5);
                $apiResult = $client->getJobResults();

//                dd($apiResult);

//                dd($apiResult['status']);
                if($apiResult['status'] == 'finished'){
                    $solution = $apiResult['output']['solution'];

                    if($apiResult['output']['num_unserved'] > 0){
                        return RestAPI::response($apiResult['output']['num_unserved'] .' locations unserved', false, 'error_exception');
                    }

//                    $job=SlotJob::where('job_id','=',$id)->first();
//                    SlotJob::where('job_id','=',$job->job_id)->update(['status'=>$apiResult['status']]);

//            dd($solution);

                    if(!empty($solution)){
                        foreach ($solution as $key => $value){
                            if(count($value)>1){
                                $Route = new JoeyRoutes();
                                //$Route->joey_id = $key;
                                $Route->date =date('Y-m-d H:i:s');
//                                $Route->hub = $job->hub_id;
                                $Route->zone = $data['zone_id'];
                                // $Route->total_travel_time=$apiResult['output']['total_travel_time'];
                                if(isset($apiResult['output']['total_working_time'])){
                                    $Route->total_travel_time=$apiResult['output']['total_working_time'];
                                }
                                else{
                                    $Route->total_travel_time=0;
                                }
                                if(isset($apiResult['output']['total_distance']))
                                {
                                    $Route->total_distance=$apiResult['output']['total_distance'];
                                }
                                else
                                {
                                    $Route->total_distance=0;
                                }
                                $Route->mile_type = 4;
                                $Route->save();

                                for($i=0;$i<count($value);$i++){
                                    if($i>0){

                                        //JoeyRouteLocations::where('task_id','=',$value[$i]['location_id'])->update(['deleted_at'=>date('Y-m-d H:i:s')]);

                                        $routeLoc = new JoeyRouteLocation();
                                        $routeLoc->route_id = $Route->id;
                                        $routeLoc->ordinal = $i;
                                        $routeLoc->task_id = $value[$i]['location_id'];

                                        if(isset($value[$i]['distance']) && !empty($value[$i]['distance'])){
                                            $routeLoc->distance = $value[$i]['distance'];
                                        }

                                        if(isset($value[$i]['arrival_time']) && !empty($value[$i]['arrival_time'])){
                                            $routeLoc->arrival_time = $value[$i]['arrival_time'];
                                            if(isset($value[$i]['finish_time'])){
                                                $routeLoc->finish_time = $value[$i]['finish_time'];
                                            }
                                        }
                                        $routeLoc->save();

                                    }
                                }

                            }
                        }
                    }
                }
                else{

                    return RestAPI::response('your Request Is In '.$apiResult['status'], false, 'error_exception');
//                    $error = new LogRoutes();
//                    $error->error = $apiResponse->job_id." is in ".$apiResult['status'];
//                    $error->save();
//                    return back()->with('error','Routes creation is in process');
                }


            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response([], true, 'Route Created Successfully');
    }

    public function showBroadCastRoute(Request $request)
    {
        $data = $request->all();

        $validate = $request->validate([
            'zone_id' => 'required',
            'date' => 'required'
        ]);

        DB::beginTransaction();
        try {

            $joey = Joey::find(auth()->user()->id);


            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }

//            $schedules = ZoneSchedule::leftJoin('joeys_zone_schedule as jzs','jzs.zone_schedule_id','=', 'zone_schedule.id')
//                ->where('zone_schedule.zone_id',$data['zone_id'])
//                ->where('zone_schedule.start_time', 'LIKE', '%'.$data['date'].'%')
//                ->where('jzs.joey_id', $joey->id)
//                ->get(['zone_schedule.start_time', 'zone_schedule.end_time','jzs.joey_id', 'zone_schedule.id', 'zone_schedule.capacity'])->toArray();

//            dd($schedules);

            $joeyRoutes = JoeyRoutes::join('joey_route_locations','joey_route_locations.route_id','=','joey_routes.id')
                ->whereNull('joey_routes.joey_id')
                ->whereNull('joey_routes.deleted_at')
                ->whereNull('joey_route_locations.deleted_at')
                ->where('joey_routes.date', 'LIKE', '%'.$data['date'].'%')
                ->where('joey_routes.zone', $data['zone_id'])
                ->get();

//            dd($joeyRoutes);
            if(empty($joeyRoutes)){
                $joeyRoutes = JoeyRoutes::join('joey_route_locations','joey_route_locations.route_id','=','joey_routes.id')
                    ->whereNull('joey_routes.joey_id')
                    ->whereNull('joey_routes.deleted_at')
                    ->whereNull('joey_route_locations.deleted_at')
                    ->where('joey_routes.date', 'LIKE', '%'.$data['date'].'%')
                    ->where('joey_routes.zone', $joey->preferred_zone)
                    ->get();
            }

//            dd($joeyRoutes);
            $routeList = JoeyBroadCastRouteResource::collection($joeyRoutes);
            return RestAPI::response($routeList, true, 'joey route zone wise');




//            $response = new RouteStatusListResource($request);


            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'Routes Status List');
    }

    public function accept(Request $request)
    {
        $validate = Validator::make($request->all(),[
            'route_id' => 'required|exists:joey_routes,id'
        ]);

        $data = $request->all();
        $joey = Joey::find(auth()->user()->id);

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

    public function riderList(Request $request)
    {
        $data = $request->all();

        $validate = $request->validate([
            'zone_id' => 'required',
            'date' => 'required'
        ]);



//        $sprints = Sprint::leftJoin('sprint__tasks as task','task.sprint_id','=', 'sprint__sprints.id')
//            ->leftJoin('merchantids as mrids','mrids.task_id','=', 'task.id')
//            ->whereNull('sprint__sprints.deleted_at')
//            ->where('sprint__sprints.created_at', 'LIKE', '%'.date('Y-m-d').'%')
//            ->get(['sprint__sprints.id as order_id', 'task.type', 'mrids.start_time',
//                'mrids.end_time', 'mrids.address_line2', 'task.status_id as task_status_id','task.id as task_id'])->toArray();

        DB::beginTransaction();
        try{
            $taskIds = Sprint::leftJoin('sprint__tasks as task','task.sprint_id','=', 'sprint__sprints.id')
                ->leftJoin('merchantids as mrids','mrids.task_id','=', 'task.id')
                ->whereNull('sprint__sprints.deleted_at')
                ->where('task.type', 'dropoff')
                ->where('sprint__sprints.created_at', 'LIKE', '%'.$data['date'].'%')
                ->pluck('task.id')->toArray();

            if(empty($taskIds)){
                return RestAPI::response('No orders available in this date', false);
            }

            $routeExistsTaskIds = JoeyRouteLocation::whereIn('task_id', $taskIds)->pluck('task_id')->toArray();


            $taskIdsNotInRoute = array_values(array_diff($taskIds,$routeExistsTaskIds));

            if(empty($taskIdsNotInRoute)){
                return RestAPI::response('This orders is already in route', false);
            }

            $sprintIds = SprintTasks::whereIn('id', $taskIdsNotInRoute)->pluck('sprint_id')->toArray();

            $sprintZone = SprintZone::whereIn('sprint_id',$sprintIds)->pluck('zone_id');

            $joeyIds = JoeyRoutes::whereIn('zone',$sprintZone)->pluck('joey_id')->toArray();



            // Initialize an empty result array
            $result = [];

            // Nested loops to iterate over each element combination
            foreach ($joeyIds as $item1) {
                foreach ($sprintIds as $item2) {
                    // Combine the current elements
//                $combination = [$item1, $item2];

                    // Add the combination to the result array
                    $result[] = [
                        'joey_id' => $item1,
                        'sprint_id' => $item2,
                    ];
                }
            }

            DB::commit();
        }catch (\Exception $exception){

            DB::rollBack();
            return RestAPI::response($exception->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($result, true, 'rider List');

    }

    public function riderAcceptOrder(Request $request)
    {
        $data = $request->all();

        $validate = $request->validate([
            'order_id' => 'required',
            'date' => 'required'
        ]);

        DB::beginTransaction();
        try{
            $sprintZone = SprintZone::where('sprint_id',$data['order_id'])->first();

            $route = JoeyRoutes::where('zone',$sprintZone->zone_id)->orderBy('id', 'DESC')->first();

            $task = SprintTasks::where('sprint_id',$data['order_id'])->where('type', 'dropoff')->first();

            $joeyRouteLocation = JoeyRouteLocation::where('route_id', $route->id)->orderBy('id', 'DESC')->first();

            $ordinal = $joeyRouteLocation->ordinal+1;
            $arrivalTime = $joeyRouteLocation->finish_time;

            $data = [
                'route_id' => $route->id,
                'ordinal' => $ordinal,
                'task_id' => $task->id,
                'arrival_time' => $arrivalTime,
                'finish_time' => $arrivalTime,
            ];

            JoeyRouteLocation::create($data);

            DB::commit();
        }catch (\Exception $exception){
            DB::rollBack();
            return RestAPI::response($exception->getMessage(), false, 'error_exception');
        }

        return RestAPI::response([], true, 'Rider Accept Order Successfully');

    }

}
