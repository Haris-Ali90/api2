<?php

namespace App\Http\Controllers\Api;

use App\Classes\HaillifyClient;
use App\Models\BoradlessDashboard;
use App\Models\HaillifyBooking;
use App\Models\HaillifyDeliveryDetail;
use App\Models\JoeyRoutes;
use App\Models\Location;
use Illuminate\Database\Eloquent\Builder;
use App\Classes\RestAPI;
use App\Http\Requests\Api\JoeyLocationRequest;
use App\Http\Requests\Api\StartWorkRequest;
use App\Http\Resources\BasicCategoryResource;
use App\Http\Resources\BasicVendorListResource;
use App\Http\Resources\CategoryListResource;
use App\Http\Resources\ComplaintResource;
use App\Http\Resources\JoeyChecklistResource;
use App\Http\Resources\JoeyNewOrderResource;
use App\Http\Resources\JoeyOrderDetailResource;
use App\Http\Resources\JoeyOrderListResource;
use App\Http\Resources\JoeyOrderResource;
use App\Http\Resources\JoeySeenBasicCategoryResource;
use App\Http\Resources\JoeySprintResource;
use App\Http\Resources\GrocerySprintResource;
use App\Http\Resources\JoeySummaryResource;
use App\Http\Resources\ReviewResource;
use App\Http\Resources\RouteStatusListResource;
use App\Http\Resources\SprintContactResource;
use App\Http\Resources\VehicleResource;
use App\Http\Resources\VendorListResource;
use App\Http\Resources\WorkTimeResource;
use App\Http\Resources\WorkTypeResource;
use App\Models\BasicCategory;
use App\Models\BasicVendor;
use App\Models\Complaint;
use App\Models\ExclusiveOrderJoeys;
use App\Models\FlagHistory;
use App\Models\Joey;
use App\Models\JoeyChecklist;
use App\Models\JoeyDutyHistory;
use App\Models\JoeyLocations;
use App\Models\JoeyRouteLocation;
use App\Models\JoeysZoneSchedule;
use App\Models\JoeyTransactions;
use App\Models\MerchantsIds;
use App\Models\OrderCategory;
use App\Models\QuizQuestion;
use App\Models\Ratings;
use App\Models\RouteHistory;
use App\Models\Sprint;
use App\Models\SprintSprint;
use App\Models\SprintTaskHistory;
use App\Models\SprintTasks;
use App\Models\StatusMap;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkTime;
use App\Models\WorkType;
use App\Models\Zones;
use App\Models\ZoneSchedule;
use App\Models\JoeyQuiz;
use App\Models\JoeyPerformanceHistory;
use App\Models\Claim;
use App\Models\CustomerFlagCategories;
use App\Models\OptimizeItinerary;
use App\Models\TestTask;
use App\Http\Resources\JoeySprintOnlyResource;
use App\Repositories\Interfaces\JoeyTransactionsRepositoryInterface;
use Carbon\Carbon;
use App\Repositories\Interfaces\ComplaintRepositoryInterface;
use App\Repositories\Interfaces\JoeyDutyHistoryRepositoryInterface;
use App\Repositories\Interfaces\SprintRepositoryInterface;
use App\Repositories\Interfaces\SprintSprintRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\JoeyRouteRepositoryInterface;
use Faker\Provider\DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;
use App\Models\TrackingNote;
use App\Models\OptimizeTask;
use App\Http\Requests\Api\CreateNoteRequest;


class JoeyController extends ApiBaseController
{

    private $userRepository;
    private $sprintRepository;
    private $joeyDutyHistoryRepository;
    private $joeyTransactionRepository;

    /**
     * Create a new controller instance.
     *
     * @return void
     */

    /**  *
     * @param HaillifyClient $client
     * @return void
     */

    public function __construct(UserRepositoryInterface             $userRepository, SprintRepositoryInterface $sprintRepository,
                                JoeyDutyHistoryRepositoryInterface  $joeyDutyHistoryRepositoryInterface,
                                JoeyTransactionsRepositoryInterface $joeyTransactionsRepository,
                                JoeyRouteRepositoryInterface $joeyrouteRepository, HaillifyClient $client)
    {

        $this->userRepository = $userRepository;
        $this->sprintRepository = $sprintRepository;
        $this->joeyDutyHistoryRepository = $joeyDutyHistoryRepositoryInterface;
        $this->joeyTransactionRepository = $joeyTransactionsRepository;
        $this->joeyrouteRepository = $joeyrouteRepository;
        $this->client = $client;

    }

    /**
     * to get orders
     */

    public function Orders(Request $request)
    {
        $data = $request->all();

        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);
            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            } else if (empty($joey['on_duty'])) {
                return RestAPI::response('You must be on duty to view orders.', false);
            }

            $joeylistOrder= $this->sprintRepository->findWithtask($joey->id);

            $response['Orders'] =  JoeySprintResource::collection($joeylistOrder);
            $response['Status'] =  new RouteStatusListResource($request);
            $response['at_location_rdaius'] = '500';
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'joey order');
    }
    /**
     * to get order details by id agianst sprint (new work)
     */
    public function joeyOrderDetails(Request $request)
    {
        $data = $request->all();
        DB::beginTransaction();
        try {
            $joey = $this->userRepository->find(auth()->user()->id);
            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            } else if (empty($joey['on_duty'])) {
                return RestAPI::response('You must be on duty to view orders.', false);
            }
            $joeylistOrder= $this->sprintRepository->findWithtaskid($data['id']);

            if(empty($joeylistOrder)){return RestAPI::response(new \stdClass(), true, 'joey order details');}

            $response['OrderDetails'] =  new GrocerySprintResource($joeylistOrder);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, 'joey order details');

    }

    /**
     * to get order details by joyid agianst sprint table columns only(new work)
     */
    public function joeyOrderList(Request $request)
    {
        $data = $request->all();
        // print_r($data);die;
        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);
            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            } else if (empty($joey['on_duty'])) {
                return RestAPI::response('You must be on duty to view orders.', false);
            }

            $joeylistOrder= $this->sprintRepository->findWithtask($joey->id);

            if(!empty($joeylistOrder)){
                $response['Orders'] =  JoeySprintOnlyResource::collection($joeylistOrder);
            }else{
                $response['Orders'] =[];
            }
            $response['Status'] =  new RouteStatusListResource($request);
            $response['at_location_rdaius'] = '500';

            // $response['Status'] =  new RouteStatusListResource($request);
            // $response['at_location_rdaius'] = '500';
            // $joeylistOrder= $this->sprintRepository->findWithtask($joey->id);
            // if(empty($joeylistOrder)){$response['Orders'] =[];return RestAPI::response($response, true, 'joey order details');}
            // $response['Orders'] =  JoeySprintOnlyResource::collection($joeylistOrder);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'joey order');
    }

    /**
     * to get new order list by joy id agianst sprint table columns only(new work)
     */
    public function newOrdersList(Request $request)
    {
        $data = $request->all();

        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);
            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            } else if (empty($joey['on_duty'])) {
                return RestAPI::response('You must be on duty to view orders.', false);
            }
            $exclusiveOrderIds = ExclusiveOrderJoeys::where('joey_id', $joey->id)->pluck('order_id');

            if (!empty($exclusiveOrderIds)) {
                $joeylistOrder = $this->sprintRepository->findWithOrderId($exclusiveOrderIds);
            }

            if (!empty($joeylistOrder)) {
                $response = JoeySprintOnlyResource::collection($joeylistOrder);
            } else {
                $response = [];
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'joey order');
    }

    /**
     * to get order list agianst joey
     */
    public function joey_order_list(Request $request)
    {
        $data = $request->validate([
            'start_date' => 'Required|date_format:Y-m-d',
            'end_date' => 'Required|date_format:Y-m-d',
            'timezone' => 'Required'
        ]);

        $data = $request->all();
        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);
            if (empty($joey)) {
                return RestAPI::response('Joey not found', false);
            }
            if (empty($data['start_date'])) {

                return RestAPI::response('startDate required', false);
            }

            if (empty($data['end_date'])) {
                return RestAPI::response('EndDate required', false);
            }

            $startdate = $data['start_date'] . ' 00:00:00';
            $endDate = $data['end_date'] . ' 23:59:59';

            $startDateConversion = convertTimeZone($startdate, $data['timezone'], 'UTC', 'Y/m/d H:i:s');
            $endDateConversion = convertTimeZone($endDate, $data['timezone'], 'UTC', 'Y/m/d H:i:s');

            $joeylistOrder = $this->joeyTransactionRepository->getDurationOfJoey($joey->id,$startDateConversion,$endDateConversion);

            foreach ($joeylistOrder as $k => $v) {
                $joeylistOrder[$k]['convert_to_timezone'] = $data['timezone'];
            }

            $response = JoeyOrderListResource::collection($joeylistOrder);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'joey Order List');
    }


    /**
     * to get details of the orders
     */
    public function OrdersDetails(Request $request)
    {
        $data = $request->all();

        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);
            if (empty($joey)) {
                return RestAPI::response('Joey not found', false);
            }
            if (empty($data['order_num'])) {
                return RestAPI::response('Provide order number', false);
            }
            $joeyOrderDetail = $this->sprintRepository->joeyOrderDetail($joey->id, $data['order_num']);

            $response = new JoeyOrderDetailResource($joeyOrderDetail);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'joey Order Details');
    }


    /**
     * to get new orders from exclusive table
     */
    public function New_Orders(Request $request)
    {
        $data = $request->all();

        DB::beginTransaction();
        try {
            $joey = $this->userRepository->find(auth()->user()->id);
            $exclusiveOrderIds = ExclusiveOrderJoeys::where('joey_id', $joey->id)->pluck('order_id');


            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            } else if (empty($joey['on_duty'])) {
                return RestAPI::response('You must be on duty to view orders.', false);
            }
            $joeylistOrder = '';

            if (!empty($exclusiveOrderIds)) {
                $joeylistOrder = $this->sprintRepository->findWithOrderId($exclusiveOrderIds);
            }
            $response = [];


            if (!empty($joeylistOrder)) {
                $response = JoeyNewOrderResource::collection($joeylistOrder);
            } else {
                return RestAPI::response('There are no Order for this joey', false);
            }


            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'joey exclusive order');
    }


    /**
     * start shift
     */
    public function start_shift(Request $request)
    {
        $request->validate([
//            'shift_id ' => 'required|exists:zone_schedule,id',
            'latitude' => 'required',
            'longitude' => 'required'
        ]);

        $data = $request->all();
        $joeyLatitude = $data['latitude'];
        $joeyLongitude = $data['longitude'];
        $currentDate = Carbon::now()->format('Y/m/d H:i:s');

        DB::beginTransaction();
        try {
            $joey = $this->userRepository->find(auth()->user()->id);
            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }

            if($joey->on_duty == null){
                return RestAPI::response('Joey should be on duty.', false);
            }

            $joeyShift = JoeysZoneSchedule::where('joey_id', '=', $joey->id)
                ->whereNull('joeys_zone_schedule.start_time')
                ->where('zone_schedule_id', '=', $data['shift_id'])
                ->whereNull('deleted_at')
                ->first();

            if (empty($joeyShift)) {
                return RestAPI::response('This shift has already started.', false);
            }

            $shift = ZoneSchedule::where('id', $data['shift_id'])->first();
            $zone = Zones::find($shift->zone_id);

            $radius = $zone->radius;
            $zoneLatitude = $zone->latitude / 1000000;
            $zoneLongitude = $zone->longitude / 1000000;

            $joeyDistance = $this->twopoints_on_earth($zoneLatitude, $zoneLongitude, $joeyLatitude, $joeyLongitude);


            if (!empty($data['timezone'])) {
                if($zone->id == 71){
                    $startShiftDate = Carbon::parse($shift->start_time)->subHour(1);
                    $endShiftDate = Carbon::parse($shift->end_time)->subHour(1);
                    $shiftStartTimeCoversion = convertTimeZone($startShiftDate, 'UTC', $data['timezone'], 'Y/m/d H:i:s');
                    $shiftEndTimeCoversion = convertTimeZone($endShiftDate, 'UTC', $data['timezone'], 'Y/m/d H:i:s');
                }else{
                    $shiftStartTimeCoversion = convertTimeZone($shift->start_time, 'UTC', $data['timezone'], 'Y/m/d H:i:s');
                    $shiftEndTimeCoversion = convertTimeZone($shift->end_time, 'UTC', $data['timezone'], 'Y/m/d H:i:s');
                }

            }

            $shiftStartDate = $shiftStartTimeCoversion;
            $shiftEndDate = $shiftEndTimeCoversion;

            $currentDateConverion = convertTimeZone($currentDate, 'UTC', $data['timezone'], 'Y/m/d H:i:s');

            $currentDate = $currentDateConverion;

            if ($currentDate < $shiftStartDate) {
                return RestAPI::response('You cannot start your shift early!', false);
            }

            if ($currentDate >= $shiftEndDate) {
                return RestAPI::response('You can not start your past shift!', false);
            }

            if ($radius <= $joeyDistance) {
                return RestAPI::response('You cant start your shift because you are not in shift zone.!', false);
            }

            $joeyLocationRecord = [
                'joey_id' => $joey->id,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'created_at' => $currentDate,
                'updated_at' => $currentDate,
            ];

            JoeyLocations::insert($joeyLocationRecord);
            $joeyShift->joey_notes = $data['note'];
            $joeyShift->start_time = Carbon::now()->format('Y-m-d H:i:s');
            $joeyShift->save();


            $response = 'Shift started';
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response(new \stdClass(), true, $response);

    }


    public function twopoints_on_earth($latitudeFrom, $longitudeFrom,
                                       $latitudeTo, $longitudeTo)
    {
        $long1 = deg2rad($longitudeFrom);
        $long2 = deg2rad($longitudeTo);
        $lat1 = deg2rad($latitudeFrom);
        $lat2 = deg2rad($latitudeTo);

        //Haversine Formula
        $dlong = $long2 - $long1;
        $dlati = $lat2 - $lat1;

        $val = pow(sin($dlati / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($dlong / 2), 2);

        $res = 2 * asin(sqrt($val));

        $radius = 3958.756;

        return ($res * $radius) * 1609.344 + 10;
    }

    /**
     * end shift
     */
    public function end_shift(Request $request)
    {

        $data = $request->all();

        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);
            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }
            $shift = ZoneSchedule::where('id', $data['shift_id'])->first();


            $joeyShift = JoeysZoneSchedule::where('joey_id', '=', $joey->id)
                ->whereNotNull('start_time')
                ->whereNull('end_time')
                ->where('zone_schedule_id', '=', $shift->id)
                ->whereNull('deleted_at')
                ->first();

            if (empty($joeyShift)) {
                return RestAPI::response('Shift not found', false);
            }

//            JoeysZoneSchedule::where('joey_id',$joey->id)->update(['zone_schedule_id' =>$shift->id,'joey_notes' =>$data['note'],
//                'end_time'=>Carbon::now()->format('Y-m-d H:i:s')]);
            $joeyShift->joey_notes = $data['note'];
            $joeyShift->end_time = Carbon::now()->format('Y-m-d H:i:s');
            $joeyShift->save();

            JoeyDutyHistory::where('joey_id', $joey->id)->update(['ended_at' => Carbon::now()->format('Y-m-d H:i:s')]);
            Joey::where('id', $joey->id)->update(['shift_amount_due' => 0, 'is_on_shift' => 0]);


            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response(new \stdClass(), true, 'Shift ended');
    }


    // optimize work 10-05-2022
    public function optimize(Request $request)
    {
        $request->validate([
            'is_optimize' => 'required:boolean',
        ]);
        try{
            //get joey id
            $joeyId = auth()->user()->id;
            //get joey lat lng
            $joey = $this->userRepository->find($joeyId);
            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }
            //send is optimize param for optimize joeys routes
            $isOptimize = $request->get('is_optimize');
            //get joey current lat or lng
            $joeyLocation = JoeyLocations::where('joey_id', '=', $joeyId)
                ->orderBy('id', 'DESC')
                ->first(['latitude', 'longitude']);


            if(empty($joeyLocation)){
                return RestAPI::response('Please Update Your Location First', false);
            }
            if($isOptimize == 1){
                //check optimize entry is exists in database
                $optimize = OptimizeItinerary::where('joey_id', $joeyId)->exists();

                //create optimize entry if not exists
                if ($optimize == false) {
                    $data = [
                        'joey_id' => $joeyId,
                        'is_optimize' => 1 // change into 1 after fixing issue
                    ];
                    $optimizeItinereary = OptimizeItinerary::create($data);
                    $itineraryId = $optimizeItinereary->id;
                }

                //update optimize if optimize is exists
                if($optimize == true){
                    $updateOptimizeItinerary = OptimizeItinerary::where('joey_id', $joeyId)->first();
                    $itineraryId = $updateOptimizeItinerary->id;
                    $updateOptimizeItinerary->update(['is_optimize' => 1]); // change into 1 after fixing issue
                }

                //get joey routes

                $routes = $this->joeyrouteRepository->optimizeOnlyLastMileByJoeyId($joey->id);

                // if joey has no routes show message
                if(empty($routes)){
                    return RestAPI::response('Joey has no routes', false);
                }

                $optimizeTask = [];
                $ordinal = 0;

                //get route location and their tasks
                foreach ($routes as $route_key => $route) {
                    $ordinal++;

                    if(isset($route->sprintTask->location_id)){

                        $location = Location::where('id',$route->sprintTask->location_id)->first();

                        $JoeyLatitude = (float)substr($joeyLocation->latitude, 0, 8) / 1000000;
                        $JoeyLongitude = (float)substr($joeyLocation->longitude, 0, 9) / 1000000;
                        $taskLatitude = (float)substr($location->latitude, 0, 8) / 1000000;
                        $taskLongitude = (float)substr($location->longitude, 0, 9) / 1000000;

                        // Distance Matrix Api implement for lat lng distances
                        $url = "https://maps.googleapis.com/maps/api/distancematrix/json?destinations=$taskLatitude,$taskLongitude&origins=$JoeyLatitude,$JoeyLongitude&key=AIzaSyDTK4viphUKcrJBSuoidDqRhVA4AWnHOo0";
                        // response get from file content
                        $resp_json = file_get_contents($url);
                        // decode the json
                        $resp = json_decode($resp_json, true);
                        // if status is ok then get distance


                        if($resp['status']=='OK'){
                            if(isset($resp['rows'][0]['elements'][0]['distance']['text'])){
                                $joeyDistance = $resp['rows'][0]['elements'][0]['distance']['text'];
                            }
                            else{
                                $updateOptimizeItinerary = OptimizeItinerary::where('joey_id', $joeyId)->first();
                                $updateOptimizeItinerary->update(['is_optimize' => 0]);
                                return RestAPI::response('Please Update Your Location First', false);
                            }
                        }
                        else{
                            $updateOptimizeItinerary = OptimizeItinerary::where('joey_id', $joeyId)->first();
                            $updateOptimizeItinerary->update(['is_optimize' => 0]);
                            return RestAPI::response('Please Update Your Location First', false);
                        }

                        //explode distance from km
                        $joeyDistance = explode(" ",$joeyDistance);

                        $optimizeTask[] = [
                            'task_id'       => $route->sprintTask->id,
                            'task_loc_lat'  => $taskLatitude,
                            'task_loc_lng'  => $taskLongitude,
                            'distance'      => $joeyDistance[0],
                            'ordinal'       => $ordinal,
                            'joey_id'       => $joey->id
                        ];

                    }
                }
                // array sort by distance of assending order
                usort($optimizeTask, function($a, $b) { return $b['distance'] < $a['distance'];});

                foreach($optimizeTask as $key => $task){

                    OptimizeTask::where('task_id', $task['task_id'])->update(['deleted_at' => date('Y-m-d H:i:s')]);

                    OptimizeTask::create([
                        'itinerary_id'  => $itineraryId,
                        'task_id'       => $task['task_id'],
                        'ordinal'       => $key+1,
                        'created_at'    => date('Y-m-d H:i:s'),
                        'updated_at'    => date('Y-m-d H:i:s'),
                    ]);
                }

                return RestAPI::response(new \stdClass(), true, 'Optimization applied on itinerary');

            }
            if($isOptimize == 0){
                $optimize = OptimizeItinerary::where('joey_id', $joeyId)->exists();
                if($optimize == true){
                    $updateOptimizeItinerary = OptimizeItinerary::where('joey_id', $joeyId)->first();
                    $updateOptimizeItinerary->update(['is_optimize' => 0]);
                }
                return RestAPI::response(new \stdClass(), true, 'Optimization removed from itinerary');
            }

        } catch (\Exception $e) {
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

    }
//

    // optimize work 16-02-2023 for testing and checking
    public function optimizeCopy(Request $request)
    {
        $request->validate([
            'is_optimize' => 'required:boolean',
        ]);
        try{
            //get joey id
            $joeyId = auth()->user()->id;
            //get joey lat lng
            $joey = $this->userRepository->find($joeyId);
            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }
            //send is optimize param for optimize joeys routes
            $isOptimize = $request->get('is_optimize');
            //get joey current lat or lng
            $joeyLocation = JoeyLocations::where('joey_id', '=', $joeyId)
                ->orderBy('id', 'DESC')
                ->first(['latitude', 'longitude']);


            if(empty($joeyLocation)){
                return RestAPI::response('Please Update Your Location First', false);
            }
            if($isOptimize == 1){
                //check optimize entry is exists in database
                $optimize = OptimizeItinerary::where('joey_id', $joeyId)->exists();

                //create optimize entry if not exists
                if ($optimize == false) {
                    $data = [
                        'joey_id' => $joeyId,
                        'is_optimize' => 1 // change into 1 after fixing issue
                    ];
                    $optimizeItinereary = OptimizeItinerary::create($data);
                    $itineraryId = $optimizeItinereary->id;
                }

                //update optimize if optimize is exists
                if($optimize == true){
                    $updateOptimizeItinerary = OptimizeItinerary::where('joey_id', $joeyId)->first();
                    $itineraryId = $updateOptimizeItinerary->id;
                    $updateOptimizeItinerary->update(['is_optimize' => 1]); // change into 1 after fixing issue
                }

                //get joey routes

                $routes = $this->joeyrouteRepository->optimizeOnlyLastMileByJoeyId($joey->id);

                // if joey has no routes show message
                if(empty($routes)){
                    return RestAPI::response('Joey has no routes', false);
                }

                $optimizeTask = [];
                $ordinal = 0;

                //get route location and their tasks
                foreach ($routes as $route_key => $route) {
                    $ordinal++;

                    if(isset($route->sprintTask->location_id)){

                        $location = Location::where('id',$route->sprintTask->location_id)->first();

                        $JoeyLatitude = (float)substr($joeyLocation->latitude, 0, 8) / 1000000;
                        $JoeyLongitude = (float)substr($joeyLocation->longitude, 0, 9) / 1000000;
                        $taskLatitude = (float)substr($location->latitude, 0, 8) / 1000000;
                        $taskLongitude = (float)substr($location->longitude, 0, 9) / 1000000;

                        // Distance Matrix Api implement for lat lng distances
                        $url = "https://maps.googleapis.com/maps/api/distancematrix/json?destinations=$taskLatitude,$taskLongitude&origins=$JoeyLatitude,$JoeyLongitude&key=AIzaSyDTK4viphUKcrJBSuoidDqRhVA4AWnHOo0";
                        // response get from file content
                        $resp_json = file_get_contents($url);
                        // decode the json
                        $resp = json_decode($resp_json, true);
                        // if status is ok then get distance


                        if($resp['status']=='OK'){
                            if(isset($resp['rows'][0]['elements'][0]['distance']['text'])){
                                $joeyDistance = $resp['rows'][0]['elements'][0]['distance']['text'];
                            }
                            else{
                                $updateOptimizeItinerary = OptimizeItinerary::where('joey_id', $joeyId)->first();
                                $updateOptimizeItinerary->update(['is_optimize' => 0]);
                                return RestAPI::response('Please Update Your Location First', false);
                            }
                        }
                        else{
                            $updateOptimizeItinerary = OptimizeItinerary::where('joey_id', $joeyId)->first();
                            $updateOptimizeItinerary->update(['is_optimize' => 0]);
                            return RestAPI::response('Please Update Your Location First', false);
                        }

                        //explode distance from km
                        $joeyDistance = explode(" ",$joeyDistance);

                        $optimizeTask[] = [
                            'task_id'       => $route->sprintTask->id,
                            'task_loc_lat'  => $taskLatitude,
                            'task_loc_lng'  => $taskLongitude,
                            'distance'      => $joeyDistance[0],
                            'ordinal'       => $ordinal,
                            'joey_id'       => $joey->id
                        ];

                    }
                }
                // array sort by distance of assending order
                usort($optimizeTask, function($a, $b) { return $b['distance'] < $a['distance'];});

                foreach($optimizeTask as $key => $task){

                    OptimizeTask::where('task_id', $task['task_id'])->update(['deleted_at' => date('Y-m-d H:i:s')]);

                    OptimizeTask::create([
                        'itinerary_id'  => $itineraryId,
                        'task_id'       => $task['task_id'],
                        'ordinal'       => $key+1,
                        'created_at'    => date('Y-m-d H:i:s'),
                        'updated_at'    => date('Y-m-d H:i:s'),
                    ]);
                }

                return RestAPI::response(new \stdClass(), true, 'Optimization applied on itinerary');

            }
            if($isOptimize == 0){
                $optimize = OptimizeItinerary::where('joey_id', $joeyId)->exists();
                if($optimize == true){
                    $updateOptimizeItinerary = OptimizeItinerary::where('joey_id', $joeyId)->first();
                    $updateOptimizeItinerary->update(['is_optimize' => 0]);
                }
                return RestAPI::response(new \stdClass(), true, 'Optimization removed from itinerary');
            }

        } catch (\Exception $e) {
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

    }

    //calculate distance
    public function get_distance_between_points($latitude1, $longitude1, $latitude2, $longitude2)
    {
        $meters = $this->get_meters_between_points($latitude1, $longitude1, $latitude2, $longitude2);
        $kilometers = $meters / 1000;
        $miles = $meters / 1609.34;
        $yards = $miles * 1760;
        $feet = $miles * 5280;
        return $kilometers;
    }

    public function get_meters_between_points($latitude1, $longitude1, $latitude2, $longitude2)
    {
        if (($latitude1 == $latitude2) && ($longitude1 == $longitude2)) {
            return 0;
        } // distance is zero because they're the same point
        $p1 = deg2rad($latitude1);
        $p2 = deg2rad($latitude2);
        $dp = deg2rad($latitude2 - $latitude1);
        $dl = deg2rad($longitude2 - $longitude1);
        $a = (sin($dp / 2) * sin($dp / 2)) + (cos($p1) * cos($p2) * sin($dl / 2) * sin($dl / 2));
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $r = 6371008; // Earth's average radius, in meters
        $d = $r * $c;
        return $d; // distance, in meters
    }

    /**
     * Duty start
     */
    public function start_work(StartWorkRequest $request)
    {
        $data = $request->all();
        $currentDate = Carbon::now()->format('Y/m/d H:i:s');
        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);

            if (empty($joey)) {
                return RestAPI::response('Joey record not found', false);
            }

            $offDuty = $joey->where('is_enabled', '=', 1)->whereNull('on_duty')->first();
            $working = $this->joeyDutyHistoryRepository->isWorking($joey->id);
//            if($working!=true){
//                return RestAPI::response('No Record Found', false);
//            }

            $this->userRepository->update($joey->id, ['on_duty' => 1, 'updated_at' => Carbon::now()->format('Y-m-d H:i:s')]);
            $updatedLatitude = (int)str_replace(".", "", $data['latitude']);
            $updatedLongitude = (int)str_replace(".", "", $data['longitude']);

            $joeyLocationRecord = [
                'joey_id' => $joey->id,
                'latitude' => $updatedLatitude,
                'longitude' => $updatedLongitude,
                'created_at' => $currentDate,
                'updated_at' => $currentDate,
            ];
            JoeyLocations::insert($joeyLocationRecord);


            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response(new \stdClass(), true, 'Work started.');
    }


    /**
     * end Duty
     */

    public function end_work(Request $request)
    {

        $data = $request->all();

        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);
            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }

            $onDuty = $joey->where('is_enabled', '=', 1)->whereNotNull('on_duty')->first();

            if (empty($onDuty)) {
                return RestAPI::response('No duty found', false);
            }


            $workingOnOrder = $this->userRepository->isbusy($joey->id);

            $joeyZoneSchedule = JoeysZoneSchedule::whereNotNull('start_time')->where('joey_id', $joey->id)->first();

            if(isset($joeyZoneSchedule->zone_schedule_id)){
                $zoneShift = ZoneSchedule::find($joeyZoneSchedule->zone_schedule_id);
                if (!empty($joeyZoneSchedule)) {
                    if($zoneShift->end_time < Carbon::now()->format('Y-m-d H:i:s')){
                        JoeysZoneSchedule::where('joey_id', $joey->id)
                            ->whereNotNull('start_time')
                            //->where('zone_schedule_id',$joeyZoneSchedule->zone_schedule_id)
                            ->whereNull('end_time')
                            ->update(['end_time' => Carbon::now()->format('Y-m-d H:i:s')]);
                    }
                    if($zoneShift->end_time > Carbon::now()->format('Y-m-d H:i:s')){
                        JoeysZoneSchedule::where('joey_id', $joey->id)
                            ->whereNotNull('start_time')
                            //->where('zone_schedule_id',$joeyZoneSchedule->zone_schedule_id)
                            ->whereNull('end_time')
                            ->update(['end_time' => Carbon::now()->format('Y-m-d H:i:s')]);
                    }
                }
            }

            JoeyDutyHistory::where('joey_id', $joey->id)->update(['ended_at' => Carbon::now()->format('Y-m-d H:i:s')]);
            Joey::where('id', $joey->id)->update(['preferred_zone_id' => null, 'on_duty' => null]);


            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response(new \stdClass(), true, 'Work ended.');
    }


    /**
     * for location
     */


    public function location(JoeyLocationRequest $request)
    {

        $data = $request->all();


        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);
            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }

            $onDuty = $this->userRepository->findOnDuty('on_duty', 1);



//            if (empty($onDuty)) {
//                return RestAPI::response('Joey is not on duty. Location not logged.', false);
//            }

//            if (!empty($onDuty)) {

            $joeylocation = JoeyLocations::where('joey_id', '=', $joey->id)
                ->orderBy("id", 'DESC')
                ->first();


            if (!empty($joeylocation)) {

                // $lat[0] = substr($joeylocation->latitude, 0, 2);
                // $lat[1] = substr($joeylocation->latitude, 2);
                // $joeylat = $lat[0].".".$lat[1];

                $joeylat = ((float)($joeylocation->latitude / 1000000));

                // $long[0] = substr($joeylocation->longitude, 0, 3);
                // $long[1] = substr($joeylocation->longitude, 3);
                // $joeylon = $long[0].".".$long[1];

                $joeylon = ((float)($joeylocation->longitude / 1000000));

                // echo $joeylon;die;
                $check_fivem_range = $this->getDistanceBetweenPoints($joeylat, $joeylon, $data['latitude'], $data['longitude']);
                // echo $check_fivem_range;die;

                // if( $joeylat!= $data['latitude'] && $joeylon != $data['longitude'] ){
                if ($check_fivem_range > 5) {

                    $this->updateCurrentLocation($data['latitude'], $data['longitude']);


                    $sprint = $this->sprintRepository->findJoeyLocationWithTask($joey->id);

                    if (!empty($sprint)) {
                        // foreach($sprint->sprintTask as $order) {
                        foreach ($sprint as $order) {

                            foreach ($order->sprintTask as $one_order) {

                                $sprint_task_history_forpickup = $one_order->sprintTaskHistoryforPickup; //status id =15 or 28

                                if (!empty($sprint_task_history_forpickup)) {
                                    //         if($sprint_task_history_forpickup->sprintTaskDropoffLocationId->type=='dropoff'){
                                    if (!empty($sprint_task_history_forpickup->sprintTaskDropoffLocationId)) {


                                        $drop_loaction_lat = $sprint_task_history_forpickup->sprintTaskDropoffLocationId->Location->latitude;
                                        $drop_loaction_lat = ((float)($drop_loaction_lat / 1000000));
                                        $drop_loaction_long = $sprint_task_history_forpickup->sprintTaskDropoffLocationId->Location->longitude;
                                        $drop_loaction_long = ((float)($drop_loaction_long / 1000000));
                                        $check_hundredm_range = $this->getDistanceBetweenPoints($data['latitude'], $data['longitude'], $drop_loaction_lat, $drop_loaction_long);


                                        if ($check_hundredm_range < 500) {

                                            $checkstatus = DB::table('sprint__tasks_history')->where('status_id', 68)->where('sprint__tasks_id', $sprint_task_history_forpickup->sprint__tasks_id)->get();

                                            if (count($checkstatus) == 0) {


                                                $insertdata['status_id'] = 68;
                                                $insertdata['sprint__tasks_id'] = $sprint_task_history_forpickup->sprint__tasks_id;
                                                $insertdata['sprint_id'] = $sprint_task_history_forpickup->sprint_id;
                                                $insertdata['date'] = date('Y-m-d H:i:s');
                                                $insertdata['created_at'] = date('Y-m-d H:i:s');
                                                // SprintTaskHistory::create($insertdata);
                                                DB::table('sprint__tasks_history')->insert($insertdata);


                                            }
                                        }

                                    }
                                } else {
                                    $sprint_task_history_for_atpickup = $one_order->sprintTaskHistoryforAtPickup;
                                    if (empty($sprint_task_history_for_atpickup)) {


                                        if ($one_order->type == 'pickup') {


                                            $atpickup_loaction_lat = $one_order->Location->latitude;
                                            $atpickup_loaction_lat = ((float)($atpickup_loaction_lat / 1000000));
                                            $atpickup_loaction_long = $one_order->Location->longitude;
                                            $atpickup_loaction_long = ((float)($atpickup_loaction_long / 1000000));
                                            $check_hundredm_range = $this->getDistanceBetweenPoints($data['latitude'], $data['longitude'], $atpickup_loaction_lat, $atpickup_loaction_long);


                                            if ($check_hundredm_range < 500) {

                                                // $checkstatus=DB::table('sprint__tasks_history')->where('status_id',67)->where('sprint__tasks_id',$one_order->id)->get();
                                                // if(count($checkstatus)==0){

                                                $insertdata['status_id'] = 67;
                                                $insertdata['sprint__tasks_id'] = $one_order->id;
                                                $insertdata['sprint_id'] = $one_order->sprint_id;
                                                $insertdata['date'] = date('Y-m-d H:i:s');
                                                $insertdata['created_at'] = date('Y-m-d H:i:s');
                                                // SprintTaskHistory::create($insertdata);
                                                DB::table('sprint__tasks_history')->insert($insertdata);


                                                // }
                                            }

                                        }

                                    }
                                }
                            }
                        }
                    }
                }
            }

            else{

                $this->updateCurrentLocation($data['latitude'], $data['longitude']);
            }
//            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response(new \stdClass(), true, 'Joey Location');
    }



    public function locationCopy(JoeyLocationRequest $request)
    {

        $data = $request->all();

        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);
            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }

            $joeylocation = JoeyLocations::where('joey_id', '=', $joey->id)
                ->orderBy("id", 'DESC')
                ->first();

            if (!empty($joeylocation)) {
                $joeylat = ((float)($joeylocation->latitude / 1000000));
                $joeylon = ((float)($joeylocation->longitude / 1000000));


                $check_fivem_range = $this->getDistanceBetweenPoints($joeylat, $joeylon, $data['latitude'], $data['longitude']);


                if ($check_fivem_range > 5) {

                    $this->updateCurrentLocation($data['latitude'], $data['longitude']);
                    $sprint = $this->sprintRepository->findJoeyLocationWithTask($joey->id);

                    $bookingSprintIdForPickup = HaillifyBooking::whereHas('sprint', function($query){
                        $query->whereIn('status_id', [61,101]);
                    })->whereIn('sprint_id', $sprint)->whereNull('deleted_at')->pluck('sprint_id')->toArray();

                    $bookingSprintIdForDropOff = HaillifyBooking::whereHas('sprint', function($query){
                        $query->whereIn('status_id', [125]);
                    })->whereIn('sprint_id', $sprint)->whereNull('deleted_at')->pluck('sprint_id')->toArray();

                    $bookingSprintId = array_merge($bookingSprintIdForPickup, $bookingSprintIdForDropOff);
                    $uniqueBookingSprintId = array_unique($bookingSprintId);

                    if(!empty($uniqueBookingSprintId)){
                        $sprintHailify = Sprint::whereIn('id', $uniqueBookingSprintId)->get();
                        foreach ($sprintHailify as $order) {
                            foreach ($order->sprintTask as $task) {
                                if($task->sprintsSprints->status_id == 61 || $task->sprintsSprints->status_id == 101){

                                    $lat[0] = substr($task->Location->latitude, 0, 2);
                                    $lat[1] = substr($task->Location->latitude, 2);
                                    $latitude = $lat[0] . "." . $lat[1];
                                    $pickupLocationLat = ((float)($latitude));

                                    $long[0] = substr($task->Location->longitude, 0, 3);
                                    $long[1] = substr($task->Location->longitude, 3);
                                    $longitude = $long[0] . "." . $long[1];
                                    $pickupLocationLong = ((float)($longitude));

                                    $check_hundredm_range = $this->getDistanceBetweenPoints($request->latitude, $request->longitude, $pickupLocationLat, $pickupLocationLong);
                                    $statusId = 67;

                                    if ($check_hundredm_range < 500) {

                                        Sprint::where('id', $task->sprint_id)->update(['status_id' => $statusId]);
                                        SprintTasks::where('id', $task->id)->update(['status_id' => $statusId]);
                                        BoradlessDashboard::where('sprint_id', $task->sprint_id)->update(['task_status_id' => $statusId]);

                                        $insertdata['status_id'] = $statusId;
                                        $insertdata['sprint__tasks_id'] = $task->id;
                                        $insertdata['sprint_id'] = $task->sprint_id;
                                        $insertdata['date'] = date('Y-m-d H:i:s');
                                        $insertdata['created_at'] = date('Y-m-d H:i:s');
                                        DB::table('sprint__tasks_history')->insert($insertdata);
                                        $bookings = HaillifyBooking::where('sprint_id', $task->sprint_id)->whereNull('deleted_at')->first();
                                        $deliveries= HaillifyDeliveryDetail::where('haillify_booking_id', $bookings->id)->whereNotNull('dropoff_id')->first();
                                        $updateStatusUrl = 'https://api.drivehailify.com/carrier/' . $bookings->delivery_id . '/status';

                                        $updateStatusArray = [
                                            'status' => 'at_pickup',
                                            'driverId' => $joey->id,
                                            'latitude' => $request->latitude,
                                            'longitude' => $request->longitude,
                                            'hailifyId' => $bookings->haillify_id,
                                            'dropoffs' => [[
                                                "dropoffId" => $deliveries->dropoff_id,
                                                "status" => 'at_pickup',
                                            ]],
                                        ];

                                        $result = json_encode($updateStatusArray);
                                        $response = $this->client->bookingRequestWithParam($result, $updateStatusUrl);

                                        $data = [
                                            'url' => $updateStatusUrl,
                                            'request' => $result,
                                            'code' => $response['http_code']
                                        ];

                                        \Log::channel('hailify')->info($data);
                                    }

                                }
                                if($task->status_id == 125){

                                    $lat[0] = substr($task->Location->latitude, 0, 2);
                                    $lat[1] = substr($task->Location->latitude, 2);
                                    $latitude = $lat[0] . "." . $lat[1];
                                    $pickupLocationLat = ((float)($latitude));

                                    $long[0] = substr($task->Location->longitude, 0, 3);
                                    $long[1] = substr($task->Location->longitude, 3);
                                    $longitude = $long[0] . "." . $long[1];
                                    $pickupLocationLong = ((float)($longitude));

                                    $check_hundredm_range = $this->getDistanceBetweenPoints($request->latitude, $request->longitude, $pickupLocationLat, $pickupLocationLong);
                                    $statusId = 68;

                                    if ($check_hundredm_range < 500) {
                                        Sprint::where('id', $task->sprint_id)->update(['status_id' => $statusId]);
                                        SprintTasks::where('id', $task->id)->update(['status_id' => $statusId]);
                                        BoradlessDashboard::where('sprint_id', $task->sprint_id)->update(['task_status_id' => $statusId]);
                                        $insertdata['status_id'] = $statusId;
                                        $insertdata['sprint__tasks_id'] = $task->id;
                                        $insertdata['sprint_id'] = $task->sprint_id;
                                        $insertdata['date'] = date('Y-m-d H:i:s');
                                        $insertdata['created_at'] = date('Y-m-d H:i:s');
                                        DB::table('sprint__tasks_history')->insert($insertdata);

                                        $bookings = HaillifyBooking::where('sprint_id', $task->sprint_id)->whereNull('deleted_at')->first();
                                        $deliveries= HaillifyDeliveryDetail::where('haillify_booking_id', $bookings->id)->whereNotNull('dropoff_id')->first();
                                        $updateStatusUrl = 'https://api.drivehailify.com/carrier/' . $bookings->delivery_id . '/status';

                                        $updateStatusArray = [
                                            'status' => 'at_delivery',
                                            'driverId' => $joey->id,
                                            'latitude' => $request->latitude,
                                            'longitude' => $request->longitude,
                                            'hailifyId' => $bookings->haillify_id,
                                            'dropoffs' => [[
                                                "dropoffId" => $deliveries->dropoff_id,
                                                "status" => 'at_delivery',
                                            ]],
                                        ];

                                        $result = json_encode($updateStatusArray);
                                        $response = $this->client->bookingRequestWithParam($result, $updateStatusUrl);

                                        $data = [
                                            'url' => $updateStatusUrl,
                                            'request' => $result,
                                            'code' => $response['http_code']
                                        ];
                                        \Log::channel('hailify')->info($data);

                                        }
                                    }
                            }
                        }
                    }

                    if (!empty($sprint)) {
                        $grocerySprint = Sprint::whereIn('id', $sprint)->get();
                        foreach ($grocerySprint as $order) {
                            foreach ($order->sprintTask as $one_order) {
                                $sprint_task_history_forpickup = $one_order->sprintTaskHistoryforPickup; //status id =15 or 28
                                if (!empty($sprint_task_history_forpickup)) {
                                    if (!empty($sprint_task_history_forpickup->sprintTaskDropoffLocationId)) {
                                        $drop_loaction_lat = $sprint_task_history_forpickup->sprintTaskDropoffLocationId->Location->latitude;
                                        $drop_loaction_lat = ((float)($drop_loaction_lat / 1000000));
                                        $drop_loaction_long = $sprint_task_history_forpickup->sprintTaskDropoffLocationId->Location->longitude;
                                        $drop_loaction_long = ((float)($drop_loaction_long / 1000000));
                                        $check_hundredm_range = $this->getDistanceBetweenPoints($request->latitude, $request->longitude, $drop_loaction_lat, $drop_loaction_long);
                                        if ($check_hundredm_range < 500) {
                                            $checkstatus = DB::table('sprint__tasks_history')->where('status_id', 68)->where('sprint__tasks_id', $sprint_task_history_forpickup->sprint__tasks_id)->get();
                                            if (count($checkstatus) == 0) {
                                                $insertdata['status_id'] = 68;
                                                $insertdata['sprint__tasks_id'] = $sprint_task_history_forpickup->sprint__tasks_id;
                                                $insertdata['sprint_id'] = $sprint_task_history_forpickup->sprint_id;
                                                $insertdata['date'] = date('Y-m-d H:i:s');
                                                $insertdata['created_at'] = date('Y-m-d H:i:s');
                                                DB::table('sprint__tasks_history')->insert($insertdata);
                                            }
                                        }
                                    }
                                } else {
                                    $sprint_task_history_for_atpickup = $one_order->sprintTaskHistoryforAtPickup;
                                    if (empty($sprint_task_history_for_atpickup)) {
                                        if ($one_order->type == 'pickup') {
                                            $atpickup_loaction_lat = $one_order->Location->latitude;
                                            $atpickup_loaction_lat = ((float)($atpickup_loaction_lat / 1000000));
                                            $atpickup_loaction_long = $one_order->Location->longitude;
                                            $atpickup_loaction_long = ((float)($atpickup_loaction_long / 1000000));
                                            $check_hundredm_range = $this->getDistanceBetweenPoints($request->latitude, $request->longitude, $atpickup_loaction_lat, $atpickup_loaction_long);

                                            if ($check_hundredm_range < 500) {
                                                $insertdata['status_id'] = 67;
                                                $insertdata['sprint__tasks_id'] = $one_order->id;
                                                $insertdata['sprint_id'] = $one_order->sprint_id;
                                                $insertdata['date'] = date('Y-m-d H:i:s');
                                                $insertdata['created_at'] = date('Y-m-d H:i:s');
                                                DB::table('sprint__tasks_history')->insert($insertdata);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            else{

                $this->updateCurrentLocation($data['latitude'], $data['longitude']);
            }
//            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response(new \stdClass(), true, 'Joey Location');
    }



//    public function locationNew(JoeyLocationRequest $request)
//    {
//        $data = $request->all();
//        DB::beginTransaction();
//        try {
//            $joey = $this->userRepository->find(auth()->user()->id);
//            if (empty($joey)) {
//                return RestAPI::response('Joey  record not found', false);
//            }
//
//            $joeylocation = JoeyLocations::where('joey_id', '=', $joey->id)
//                ->orderBy("id", 'DESC')
//                ->first();
//
//            if (!empty($joeylocation)) {
//
//                $joeylat = ((float)($joeylocation->latitude / 1000000));
//                $joeylon = ((float)($joeylocation->longitude / 1000000));
//
//                $check_fivem_range = $this->getDistanceBetweenPoints($joeylat, $joeylon, $data['latitude'], $data['longitude']);
//
//                if ($check_fivem_range > 5) {
//
//                    $this->updateCurrentLocation($data['latitude'], $data['longitude']);
//                    $sprint = $this->sprintRepository->findJoeyLocationWithTaskHailify($joey->id);
//
//                    $bookingSprintIdForPickup = HaillifyBooking::whereHas('sprint', function($query){
//                        $query->whereIn('status_id', [61,101]);
//                    })->whereIn('sprint_id', $sprint)->whereNull('deleted_at')->pluck('sprint_id')->toArray();
//
//                    $bookingSprintIdForDropOff = HaillifyBooking::whereHas('sprint', function($query){
//                        $query->whereIn('status_id', [125]);
//                    })->whereIn('sprint_id', $sprint)->whereNull('deleted_at')->pluck('sprint_id')->toArray();
//
//                    $bookingSprintId = array_merge($bookingSprintIdForPickup, $bookingSprintIdForDropOff);
//
//                    $sprintHailify = Sprint::whereIn('id', $bookingSprintId)->get();
//
//
//                    $sprintIdForBooking=[];
//                    if (!empty($sprintHailify)) {
//                        foreach ($sprintHailify as $order) {
//                            foreach ($order->sprintTask as $task) {
//                                if($task->sprintsSprints->status_id == 61 || $task->sprintsSprints->status_id == 101){
//
//                                    $lat[0] = substr($task->Location->latitude, 0, 2);
//                                    $lat[1] = substr($task->Location->latitude, 2);
//                                    $latitude = $lat[0] . "." . $lat[1];
//                                    $pickupLocationLat = ((float)($latitude));
//
//                                    $long[0] = substr($task->Location->longitude, 0, 3);
//                                    $long[1] = substr($task->Location->longitude, 3);
//                                    $longitude = $long[0] . "." . $long[1];
//                                    $pickupLocationLong = ((float)($longitude));
//
//                                    $check_hundredm_range = $this->getDistanceBetweenPoints($request->latitude, $request->longitude, $pickupLocationLat, $pickupLocationLong);
//                                    $statusId = 67;
//
//                                    if ($check_hundredm_range < 500) {
//
//                                        Sprint::where('id', $task->sprint_id)->update(['status_id' => $statusId]);
//                                        SprintTasks::where('id', $task->id)->update(['status_id' => $statusId]);
//
//                                        $insertdata['status_id'] = $statusId;
//                                        $insertdata['sprint__tasks_id'] = $task->id;
//                                        $insertdata['sprint_id'] = $task->sprint_id;
//                                        $insertdata['date'] = date('Y-m-d H:i:s');
//                                        $insertdata['created_at'] = date('Y-m-d H:i:s');
//                                        DB::table('sprint__tasks_history')->insert($insertdata);
//
//                                        $bookings = HaillifyBooking::where('sprint_id', $task->sprint_id)->whereNull('deleted_at')->first();
//                                        $deliveries= HaillifyDeliveryDetail::where('haillify_booking_id', $bookings->id)->whereNotNull('dropoff_id')->first();
//                                        $updateStatusUrl = 'https://sandbox.drivegomo.com/gomo.web/api/fleet/' . $bookings->delivery_id . '/status';
//
//                                        $updateStatusArray = [
//                                            'status' => 'at_pickup',
//                                            'driverId' => $joey->id,
//                                            'latitude' => $request->latitude,
//                                            'longitude' => $request->longitude,
//                                            'hailifyId' => $bookings->haillify_id,
//                                            'dropoffs' => [[
//                                                "dropoffId" => $deliveries->dropoff_id,
//                                                "status" => 'at_pickup',
//                                            ]],
//                                        ];
//
//                                        $result = json_encode($updateStatusArray);
//                                        $response = $this->client->bookingRequestWithParam($result, $updateStatusUrl);
//
//                                        $data = [
//                                            'url' => $updateStatusUrl,
//                                            'request' => $result,
//                                            'code' => $response['http_code']
//                                        ];
//
//                                        \Log::channel('hailify')->info($data);
//                                    }
//
//                                }
//                                if($task->status_id == 125){
//
//                                    $lat[0] = substr($task->Location->latitude, 0, 2);
//                                    $lat[1] = substr($task->Location->latitude, 2);
//                                    $latitude = $lat[0] . "." . $lat[1];
//                                    $pickupLocationLat = ((float)($latitude));
//
//                                    $long[0] = substr($task->Location->longitude, 0, 3);
//                                    $long[1] = substr($task->Location->longitude, 3);
//                                    $longitude = $long[0] . "." . $long[1];
//                                    $pickupLocationLong = ((float)($longitude));
//
//                                    $check_hundredm_range = $this->getDistanceBetweenPoints($request->latitude, $request->longitude, $pickupLocationLat, $pickupLocationLong);
//                                    $statusId = 68;
//
//                                    if ($check_hundredm_range < 500) {
//                                        Sprint::where('id', $task->sprint_id)->update(['status_id' => $statusId]);
//                                        SprintTasks::where('id', $task->id)->update(['status_id' => $statusId]);
//
//                                        $insertdata['status_id'] = $statusId;
//                                        $insertdata['sprint__tasks_id'] = $task->id;
//                                        $insertdata['sprint_id'] = $task->sprint_id;
//                                        $insertdata['date'] = date('Y-m-d H:i:s');
//                                        $insertdata['created_at'] = date('Y-m-d H:i:s');
//                                        DB::table('sprint__tasks_history')->insert($insertdata);
//
//                                        $bookings = HaillifyBooking::where('sprint_id', $task->sprint_id)->whereNull('deleted_at')->first();
//                                        $deliveries= HaillifyDeliveryDetail::where('haillify_booking_id', $bookings->id)->whereNotNull('dropoff_id')->first();
//                                        $updateStatusUrl = 'https://sandbox.drivegomo.com/gomo.web/api/fleet/' . $bookings->delivery_id . '/status';
//
//                                        $updateStatusArray = [
//                                            'status' => 'at_delivery',
//                                            'driverId' => $joey->id,
//                                            'latitude' => $request->latitude,
//                                            'longitude' => $request->longitude,
//                                            'hailifyId' => $bookings->haillify_id,
//                                            'dropoffs' => [[
//                                                "dropoffId" => $deliveries->dropoff_id,
//                                                "status" => 'at_delivery',
//                                            ]],
//                                        ];
//
//                                        $result = json_encode($updateStatusArray);
//                                        $response = $this->client->bookingRequestWithParam($result, $updateStatusUrl);
//
//                                        $data = [
//                                            'url' => $updateStatusUrl,
//                                            'request' => $result,
//                                            'code' => $response['http_code']
//                                        ];
//                                        \Log::channel('hailify')->info($data);
//
//                                    }
//                                }
//                            }
//                        }
//                    }else{
//                        return RestAPI::response('This sprint is already pickup marked', false);
//                    }
//                }
//            }
//
//            else{
//                $this->updateCurrentLocation($data['latitude'], $data['longitude']);
//            }
////            }
//
//            DB::commit();
//        } catch (\Exception $e) {
//            DB::rollback();
//            return RestAPI::response($e->getMessage(), false, 'error_exception');
//        }
//
//        return RestAPI::response(new \stdClass(), true, 'Joey Location');
//    }

    /**
     * get distance in meters
     */
    function getDistanceBetweenPoints($lat1, $lon1, $lat2, $lon2)
    {
        $theta = $lon1 - $lon2;
        $miles = (sin(deg2rad($lat1)) * sin(deg2rad($lat2))) + (cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta)));
        $miles = acos($miles);
        $miles = rad2deg($miles);
        $miles = $miles * 60 * 1.1515;
        $feet = $miles * 5280;
        $yards = $feet / 3;
        $kilometers = $miles * 1.609344;
        $meters = $kilometers * 1000;
        return $meters;
    }

    function haversineGreatCircleDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000)
    {
        // convert from degrees to radians
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
                cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        return $angle * $earthRadius;
    }


    /**
     * for location current update
     */


    public function updateCurrentLocation($lat, $lon)
    {

        $currentDate = Carbon::now()->format('Y-m-d H:i:s');
        $lat = $this->userRepository->unfix($lat);
        $lon = $this->userRepository->unfix($lon);
        $joey = $this->userRepository->find(auth()->user()->id);
        $joeyLocationData = [
            'joey_id' => $joey->id,
            'latitude' => $lat,
            'longitude' => $lon,
            'created_at' => $currentDate,
            'updated_at' => $currentDate,
        ];

        //JoeyLocations::where('joey_id',$joey->id)->delete();
        $joeyLocation = JoeyLocations::create($joeyLocationData);
        $this->resetPreferredLocation($lat, $lon);


    }

    public function resetPreferredLocation($lat, $lon)
    {


        $zone = Zones::where('latitude', $lat)->where('longitude', $lon)->first();

        $rad = 30; // radius of bounding circle in kilometers

        $R = 6371;  // earth's mean radius, km

        $joey = $this->userRepository->find(auth()->user()->id);


        if (!empty($zone)) {
            Joey::where('id', $joey->id)->update(['preferred_zone_id' => $zone->id, 'preferred_zone' => $zone->id]);

        }
    }


    /**
     * joey total summary
     */

    public function summary(Request $request)
    {
        // $data = $request->all();
        $data = $request->validate([
            'start_date' => 'date_format:Y-m-d',
            'end_date' => 'date_format:Y-m-d',
            'timezone' => 'required'
        ]);

        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);


            if (empty($joey)) {
                return RestAPI::response('Joey not found', false);
            }

            if (empty($data['start_date'])) {

                return RestAPI::response('startDate required', false);
            }

            if (empty($data['end_date'])) {
                return RestAPI::response('EndDate required', false);
            }
            $startDate = $data['start_date'] . ' 00:00:00';
            $endDate = $data['end_date'] . ' 23:59:59';

            $startDateConversion = convertTimeZone($startDate, $data['timezone'], 'UTC', 'Y/m/d H:i:s');
            $endDateConversion = convertTimeZone($endDate, $data['timezone'], 'UTC', 'Y/m/d H:i:s');

            /**
             * for average time and total time duration
             *
             */


            $joeyRecord = JoeysZoneSchedule::where('joey_id', $joey->id)
                ->where('start_time', '>=', $startDateConversion)
                ->where('end_time', '<=', $endDateConversion)
                ->get();
            $joeyScheduleCount = JoeysZoneSchedule::where('joey_id', $joey->id)
                ->where('start_time', '>=', $startDateConversion)
                ->where('end_time', '<=', $endDateConversion)
                ->count();

            $sumSeconds = 0;

            foreach ($joeyRecord as $joeyRecord) {
                $startTime = Carbon::parse($joeyRecord->start_time);
                $endTime = Carbon::parse($joeyRecord->end_time);

                $Duration = $endTime->diff($startTime)->format('%H:%I:%S');

                $explodedTime = explode(':', $Duration);
                $seconds = $explodedTime[0] * 3600 + $explodedTime[1] * 60 + $explodedTime[2];
                $sumSeconds += $seconds;


            }

            $hours = floor($sumSeconds / 3600);
            $minutes = floor(($sumSeconds % 3600) / 60);
            $seconds = (($sumSeconds % 3600) % 60);


            $totalTime = $hours;


            if ($joeyScheduleCount != 0) {
                $averageSum = $sumSeconds / $joeyScheduleCount;
            } else {
                $averageSum = $sumSeconds / 1;
            }

            $avgHours = floor($averageSum / 3600);
            $avgMinutes = floor(($averageSum % 3600) / 60);
            $avgSeconds = (($averageSum % 3600) % 60);
            $avgTime = $avgHours;


            /**
             * for average distance  and distance
             *
             */


            $routes = RouteHistory::join('joey_route_locations', 'route_history.route_location_id', '=', 'joey_route_locations.id')->where('joey_id', $joey->id)->whereBetween('joey_route_locations.created_at', [$startDateConversion, $endDateConversion])->whereIn('status', [2, 4])->groupBy('route_location_id')->get();


            $ecommercecounts = count($routes);

            // $routesCount=RouteHistory::where('joey_id',$joey->id)->whereBetween('created_at',[$startDateConversion,$endDateConversion])->count();

            // $totalDistance=0;
            // $furthestDistance=0;
            // $totalecommerceDistance=0;

            $distances = [];
            foreach ($routes as $routes) {
                $joeyroutedistance = JoeyRouteLocation::where('id', $routes->route_location_id)->whereBetween('created_at', [$startDateConversion, $endDateConversion])->orderBy('created_at', 'asc')->get('distance');

                foreach ($joeyroutedistance as $edistance) {
                    $distances[] = $edistance->distance;
                }


            }

            $groceryDistance = Sprint::where('joey_id', $joey->id)
                ->whereIn('status_id', [145, 17, 113, 114, 116, 117, 118, 132, 138, 139, 144, 101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112, 131, 135, 136, 143])->whereBetween('created_at', [$startDateConversion, $endDateConversion])
                ->get('distance');

            foreach ($groceryDistance as $gdistance) {
                $distances[] = $gdistance->distance;
            }

            if (!empty($distances)) {
                $maxDistance = max($distances);

                $totalD = array_sum($distances);

                $avgD = array_sum($distances) / count($distances);
            } else {
                $maxDistance = 0;
                $totalD = 0;
                $avgD = 0;
            }


            /**
             *  for order count
             *
             */
            $groceryorderCounts = Sprint::where('joey_id', $joey->id)->whereBetween('created_at', [$startDateConversion, $endDateConversion])
                ->whereIn('status_id', [145, 17, 113, 114, 116, 117, 118, 132, 138, 139, 144, 105, 104, 111, 119, 106, 108, 109, 107, 131, 135, 140, 110])->count();

            $joeylistOrder = $this->joeyTransactionRepository->getDurationOfJoey($joey->id,$startDateConversion,$endDateConversion);
            $totalOrderCount = $ecommercecounts + $groceryorderCounts;
            // echo $ecommercecounts .'  '. $groceryorderCounts.'  ';
            // print_r($totalOrderCount);die;


            /**
             *  for earning
             *
             */
            $earningtypeArray = ['order', 'sprint'];
            $earningrecord = $this->joeyTransactionRepository->joeyCalculationForSummaryMultiple($joey->id, $earningtypeArray, $startDateConversion, $endDateConversion);


            $totalEarning = 0;
            foreach ($earningrecord as $earningrecord) {

                $totalEarning = $totalEarning + $earningrecord->earning->amount;

            }

            /**
             *  for total tip
             *
             */

            $earningtype = 'tip';
            $tiprecord = $this->joeyTransactionRepository->joeyCalculationForSummary($joey->id, $earningtype, $startDateConversion, $endDateConversion);
            $totalTip = 0;
            foreach ($tiprecord as $tiprecord) {

                $totalTip = $totalTip + $tiprecord->earning->amount;
            }


            /**
             *  for wages
             *
             */

            $wageType = 'shift';
            $wagerecord = $this->joeyTransactionRepository->joeyCalculationForSummary($joey->id, $wageType, $startDateConversion, $endDateConversion);
            $wage = 0;
            foreach ($wagerecord as $wagerecord) {

                $wage = $wage + $wagerecord->earning->amount;
            }


            /**
             *  for cash collected
             *
             */

            $cashCollectionType = 'collect';
            $cashCollected = $this->joeyTransactionRepository->joeyCalculationForSummary($joey->id, $cashCollectionType, $startDateConversion, $endDateConversion);

            $cash = 0;
            foreach ($cashCollected as $cashCollected) {

                $cash = $cash + $cashCollected->earning->amount;
            }


            /**
             *  for Total transfers
             *
             */

            $transferType = 'transfer';
            $transferred = $this->joeyTransactionRepository->joeyCalculationForSummary($joey->id, $transferType, $startDateConversion, $endDateConversion);

            $trasnferredAmount = 0;
            foreach ($transferred as $transferred) {
                $trasnferredAmount = $trasnferredAmount + $transferred->earning->amount;
            }


            /**
             *  for hours logged
             *
             */
            // $joeyRecordList=JoeysZoneSchedule::where('joey_id',$joey->id)->where('start_time', '>=', $startDateConversion )->where('end_time', '<=', $endDateConversion )->whereNull('deleted_at')->get();
            $joeyRecordList = JoeysZoneSchedule::where('joey_id', $joey->id)->where('start_time', '>=', $startDateConversion)->where('end_time', '<=', $endDateConversion)->whereNull('deleted_at')->whereNotNull('start_time')->whereNotNull('end_time')->get();


            $TotalDifferenceInHours = 0;
            foreach ($joeyRecordList as $joeyRecordList) {
                $startTime = strtotime($joeyRecordList->start_time);
                $endTime = strtotime($joeyRecordList->end_time);
                $totalTime = $endTime - $startTime;
                $TotalDifferenceInHours += $totalTime;
            }

            $TotalDifferenceInHours = gmdate("H:i:s", $TotalDifferenceInHours);

            $getDurations = $this->joeyTransactionRepository->getDurationOfJoey($joey->id,$startDateConversion,$endDateConversion);

            $totalDuration = 0;
            foreach($getDurations as $transaction){
                $totalDuration += $transaction->duration;
            }

            if($getDurations->count() > 0){
                $averageDuration = $totalDuration/$getDurations->count();
            }else{
                $averageDuration = 0;
            }


            $averageDuration = $averageDuration;
            $totalDuration = gmdate("H:i:s", $totalDuration);
            $averageDuration = gmdate("H:i:s", $averageDuration);
            /**
             *  for payment made
             *
             */

            $payemntType = 'make';
            $paymentrecord = $this->joeyTransactionRepository->joeyCalculationForSummary($joey->id, $payemntType, $startDateConversion, $endDateConversion);
            $payemntMade = 0;
            foreach ($paymentrecord as $paymentrecord) {

                $payemntMade = $payemntMade + $paymentrecord->earning->amount;
            }


            $response['joey_id'] = $joey->id;
            $response['average_time'] = $averageDuration;
            $response['total_time'] = $totalDuration;
            $response['total_distance'] = round($totalD / 1000, 2) ?? '';;

            $response['average_distance'] = round($avgD / 1000, 2) ?? '';
            $response['furthest_distance'] = round($maxDistance / 1000, 2) ?? '';
            $response['total_orders'] = $joeylistOrder->count() ?? '';
            $response['total_hours_logged'] = $TotalDifferenceInHours ?? '';
            $response['total_earning'] = round($totalEarning, 3);
            $response['total_cash_collected'] = $cash;

            $response['total_payment_made'] = $payemntMade;
            $response['total_wages'] = $wage;
            $response['total_wages_owned'] = $joey->shift_amount_due ?? '';
            $response['total_tips'] = $totalTip;
            $response['total_transfer_made'] = $trasnferredAmount;


            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'joey Summary Details');
    }


    /**
     * joey total summary For joey portal
     */
    public function getSummary(Request $request)
    {
        // $data = $request->all();
        $data = $request->all();

        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find($data['joeyId']);


            if (empty($joey)) {
                return RestAPI::response('Joey not found', false);
            }

            if (empty($data['start_date'])) {

                return RestAPI::response('startDate required', false);
            }

            if (empty($data['end_date'])) {
                return RestAPI::response('EndDate required', false);
            }
            $startDate = $data['start_date'] . ' 00:00:00';
            $endDate = $data['end_date'] . ' 23:59:59';

            $startDateConversion = convertTimeZone($startDate, $data['timezone'], 'UTC', 'Y-m-d H:i:s');
            $endDateConversion = convertTimeZone($endDate, $data['timezone'], 'UTC', 'Y-m-d H:i:s');

            /**
             * for average time and total time duration
             *
             */


            $joeyRecord = JoeysZoneSchedule::where('joey_id', $joey->id)
                ->where('start_time', '>=', $startDateConversion)
                ->where('end_time', '<=', $endDateConversion)
                ->get();
            $joeyScheduleCount = JoeysZoneSchedule::where('joey_id', $joey->id)
                ->where('start_time', '>=', $startDateConversion)
                ->where('end_time', '<=', $endDateConversion)
                ->count();

            $sumSeconds = 0;

            foreach ($joeyRecord as $joeyRecord) {
                $startTime = Carbon::parse($joeyRecord->start_time);
                $endTime = Carbon::parse($joeyRecord->end_time);

                $Duration = $endTime->diff($startTime)->format('%H:%I:%S');

                $explodedTime = explode(':', $Duration);
                $seconds = $explodedTime[0] * 3600 + $explodedTime[1] * 60 + $explodedTime[2];
                $sumSeconds += $seconds;


            }

            $hours = floor($sumSeconds / 3600);
            $minutes = floor(($sumSeconds % 3600) / 60);
            $seconds = (($sumSeconds % 3600) % 60);


            $totalTime = $hours;


            if ($joeyScheduleCount != 0) {
                $averageSum = $sumSeconds / $joeyScheduleCount;
            } else {
                $averageSum = $sumSeconds / 1;
            }

            $avgHours = floor($averageSum / 3600);
            $avgMinutes = floor(($averageSum % 3600) / 60);
            $avgSeconds = (($averageSum % 3600) % 60);
            $avgTime = $avgHours;


            /**
             * for average distance  and distance
             *
             */

            $routes = RouteHistory::join('joey_route_locations', 'route_history.route_location_id', '=', 'joey_route_locations.id')->where('joey_id', $joey->id)->whereBetween('joey_route_locations.created_at', [$startDateConversion, $endDateConversion])->whereIn('status', [2, 4])->groupBy('route_location_id')->get();


            $ecommercecounts = count($routes);

            // $routesCount=RouteHistory::where('joey_id',$joey->id)->whereBetween('created_at',[$startDateConversion,$endDateConversion])->count();

            // $totalDistance=0;
            // $furthestDistance=0;
            // $totalecommerceDistance=0;

            $distances = [];
            foreach ($routes as $routes) {
                $joeyroutedistance = JoeyRouteLocation::where('id', $routes->route_location_id)->whereBetween('created_at', [$startDateConversion, $endDateConversion])->orderBy('created_at', 'asc')->get('distance');

                foreach ($joeyroutedistance as $edistance) {
                    $distances[] = $edistance->distance;
                }


            }

            $groceryDistance = Sprint::where('joey_id', $joey->id)
                ->whereIn('status_id', [145, 17, 113, 114, 116, 117, 118, 132, 138, 139, 144, 101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112, 131, 135, 136, 143])->whereBetween('created_at', [$startDateConversion, $endDateConversion])
                ->get('distance');

            foreach ($groceryDistance as $gdistance) {
                $distances[] = $gdistance->distance;
            }

            if (!empty($distances)) {
                $maxDistance = max($distances);

                $totalD = array_sum($distances);

                $avgD = array_sum($distances) / count($distances);
            } else {
                $maxDistance = 0;
                $totalD = 0;
                $avgD = 0;
            }


            /**
             *  for order count
             *
             */
            $groceryorderCounts = Sprint::where('joey_id', $joey->id)->whereBetween('created_at', [$startDateConversion, $endDateConversion])
                ->whereIn('status_id', [145, 17, 113, 114, 116, 117, 118, 132, 138, 139, 144, 105, 104, 111, 119, 106, 108, 109, 107, 131, 135, 140, 110])->count();

            $totalOrderCount = $ecommercecounts + $groceryorderCounts;
            // echo $ecommercecounts .'  '. $groceryorderCounts.'  ';
            // print_r($totalOrderCount);die;


            /**
             *  for earning
             *
             */
            $earningtype = 'sprint';
            $earningrecord = $this->joeyTransactionRepository->joeyCalculationForSummary($joey->id, $earningtype, $startDateConversion, $endDateConversion);


            $totalEarning = 0;
            foreach ($earningrecord as $earningrecord) {

                $totalEarning = $totalEarning + $earningrecord->earning->amount;

            }

            /**
             *  for total tip
             *
             */

            $earningtype = 'tip';
            $tiprecord = $this->joeyTransactionRepository->joeyCalculationForSummary($joey->id, $earningtype, $startDateConversion, $endDateConversion);
            $totalTip = 0;
            foreach ($tiprecord as $tiprecord) {

                $totalTip = $totalTip + $tiprecord->earning->amount;
            }


            /**
             *  for wages
             *
             */

            $wageType = 'shift';
            $wagerecord = $this->joeyTransactionRepository->joeyCalculationForSummary($joey->id, $wageType, $startDateConversion, $endDateConversion);
            $wage = 0;
            foreach ($wagerecord as $wagerecord) {

                $wage = $wage + $wagerecord->earning->amount;
            }


            /**
             *  for cash collected
             *
             */

            $cashCollectionType = 'collect';
            $cashCollected = $this->joeyTransactionRepository->joeyCalculationForSummary($joey->id, $cashCollectionType, $startDateConversion, $endDateConversion);

            $cash = 0;
            foreach ($cashCollected as $cashCollected) {

                $cash = $cash + $cashCollected->earning->amount;
            }


            /**
             *  for Total transfers
             *
             */

            $transferType = 'transfer';
            $transferred = $this->joeyTransactionRepository->joeyCalculationForSummary($joey->id, $transferType, $startDateConversion, $endDateConversion);

            $trasnferredAmount = 0;
            foreach ($transferred as $transferred) {
                $trasnferredAmount = $trasnferredAmount + $transferred->earning->amount;
            }


            /**
             *  for hours logged
             *
             */
            // $joeyRecordList=JoeysZoneSchedule::where('joey_id',$joey->id)->where('start_time', '>=', $startDateConversion )->where('end_time', '<=', $endDateConversion )->whereNull('deleted_at')->get();
            $joeyRecordList = JoeysZoneSchedule::where('joey_id', $joey->id)->where('start_time', '>=', $startDateConversion)->where('end_time', '<=', $endDateConversion)->whereNull('deleted_at')->whereNotNull('start_time')->whereNotNull('end_time')->get();
            $TotalDifferenceInHours = 0;
            foreach ($joeyRecordList as $joeyRecordList) {
                $startTime = $joeyRecordList->start_time;
                $endTime = $joeyRecordList->end_time;
                $start = Carbon::parse($startTime);
                $end = Carbon::parse($endTime);
                $TotalDifferenceInHours = $TotalDifferenceInHours + $end->diffInHours($start);
            }


            /**
             *  for payment made
             *
             */

            $payemntType = 'make';
            $paymentrecord = $this->joeyTransactionRepository->joeyCalculationForSummary($joey->id, $payemntType, $startDateConversion, $endDateConversion);
            $payemntMade = 0;
            foreach ($paymentrecord as $paymentrecord) {

                $payemntMade = $payemntMade + $paymentrecord->earning->amount;
            }


            $response['joey_id'] = $joey->id;
            $response['average_time'] = $avgTime;
            $response['total_time'] = $totalTime;
            $response['total_distance'] = round($totalD / 1000, 2) ?? '';;

            $response['average_distance'] = round($avgD / 1000, 2) ?? '';
            $response['furthest_distance'] = round($maxDistance / 1000, 2) ?? '';
            $response['total_orders'] = $totalOrderCount ?? '';
            $response['total_hours_logged'] = $TotalDifferenceInHours ?? '';
            $response['total_earning'] = round($totalEarning, 3);
            $response['total_cash_collected'] = $cash;

            $response['total_payment_made'] = $payemntMade;
            $response['total_wages'] = $wage;
            $response['total_wages_owned'] = $joey->shift_amount_due ?? '';
            $response['total_tips'] = $totalTip;
            $response['total_transfer_made'] = $trasnferredAmount;

            if (!empty($data['filter'])) {
                $joeyTransaction = JoeyTransactions::join('financial_transactions', 'transaction_id', '=', 'financial_transactions.id')
                    ->where('financial_transactions.created_at', '>=', $startDateConversion)
                    ->where('financial_transactions.created_at', '<=', $endDateConversion)
                    ->where('joey_id', $joey->id)->where('type', $data['filter'])->orderBy('created_at', 'asc')->get(['reference', 'created_at', 'description', 'payment_method', 'distance', 'duration', 'amount', 'balance']);
            } else {
                $joeyTransaction = JoeyTransactions::join('financial_transactions', 'transaction_id', '=', 'financial_transactions.id')
                    ->where('financial_transactions.created_at', '>=', $startDateConversion)
                    ->where('financial_transactions.created_at', '<=', $endDateConversion)
                    ->where('joey_id', $joey->id)->orderBy('created_at', 'asc')->get(['reference', 'created_at', 'description', 'payment_method', 'distance', 'duration', 'amount', 'balance']);
            }
            //$joeyTransaction->get();
            //dd($joeyTransaction);
            $response['joey_transactions'] = $joeyTransaction;
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'joey Summary Details');
    }


    /**
     * get workTime
     */
    public function workTime(Request $request)
    {


        $data = $request->all();

        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);
            if (empty($joey)) {
                return RestAPI::response('Joey not found', false);
            }


            $workTime = WorkTime::get();
            $response = WorkTimeResource::collection($workTime);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'Joey Work Times');
    }


    /**
     * get workType
     */
    public function workType(Request $request)
    {


        $data = $request->all();

        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);
            if (empty($joey)) {
                return RestAPI::response('Joey not found', false);
            }


            $workType = WorkType::get();
            $response = WorkTypeResource::collection($workType);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'Joey Work Types');
    }


    /**
     * get basic vendor
     */
    public function vendors(Request $request)
    {
        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);
            if (empty($joey)) {
                return RestAPI::response('Joey not found', false);
            }
            $vendor = BasicVendor::with('vendor')->get();
            $response = BasicVendorListResource::collection($vendor);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'Vendor');
    }


    /**
     * get categories
     */
    public function categories(Request $request)
    {


        $data = $request->all();

        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);
            if (empty($joey)) {
                return RestAPI::response('Joey not found', false);
            }
            $category = OrderCategory::whereNull('deleted_at')->get();
            $response = CategoryListResource::collection($category);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'Categories');
    }


    /**
     * get Joey Checklist
     */
    public function checkList(Request $request)
    {


        $data = $request->all();

        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);
            if (empty($joey)) {
                return RestAPI::response('Joey not found', false);
            }
            $checklist = JoeyChecklist::whereNull('deleted_at')->get();
            $response = JoeyChecklistResource::collection($checklist);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'Joey CheckLst');
    }


    public function ratingSummary(Request $request)
    {
//dd('asd');
        $data = $request->all();

        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);
            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }

            /**
             * rating calculation
             */
            $ratingRecord = Ratings::where('object_type', 'joey')->where('object_id', $joey->id)->get();
            $totalratingSum = $ratingRecord->sum('rating');
            if (count($ratingRecord) != 0) {
                $ratting = $totalratingSum / count($ratingRecord);
            } else {
                $ratting = $totalratingSum / 1;
            }
            if (count($ratingRecord) > 0) {
                $rattingCount = count($ratingRecord);
            } else {
                $rattingCount = 0;
            }


            /**
             * distance and furthest distance calucation for ecoomerce orders
             */


            // $routes=RouteHistory::where('joey_id',$joey->id)->whereIn('status',[2,4])->groupBy('route_id')->get();
            // $ecommercecounts=count($routes);

            $routes = RouteHistory::where('joey_id', $joey->id)->whereIn('status', [2, 4])->groupBy('route_location_id')->get();
            $ecommercecounts = count($routes);

            $routesCount = RouteHistory::where('joey_id', $joey->id)->count();

            $totalDistance = 0;
            $furthestEccomerceDistance = 0;
            $totalecommerceDistance = 0;

            foreach ($routes as $routes) {
                $joeyroutedistance = JoeyRouteLocation::where('id', $routes->route_location_id)->orderBy('created_at', 'asc')->get('distance');

                foreach ($joeyroutedistance as $edistance) {
                    $distances[] = $edistance->distance;
                }

            }

            $groceryorderCounts = Sprint::where('joey_id', $joey->id)
                ->whereIn('status_id', [145, 17, 113, 114, 116, 117, 118, 132, 138, 139, 144, 105, 104, 111, 119, 106, 108, 109, 107, 131, 135, 140, 110])->count();


            $totalOrderCount = $ecommercecounts + $groceryorderCounts;


            /**
             *  for hours logged
             *
             */
            $joeyRecordList = JoeysZoneSchedule::where('joey_id', $joey->id)->whereNull('deleted_at')->whereNotNull('start_time')->whereNotNull('end_time')->get();
            $TotalDifferenceInHours = 0;
            foreach ($joeyRecordList as $joeyRecordList) {

                $startTime = strtotime($joeyRecordList->start_time);
                $endTime = strtotime($joeyRecordList->end_time);
                $totalTime = $endTime - $startTime;
                $TotalDifferenceInHours += $totalTime;
            }

            /**
             *  for earning
             *
             */
            $earningtypeArray = ['order', 'sprint'];
            $earningrecord = $this->joeyTransactionRepository->joeyCalculationForRatingMultiple($joey->id, $earningtypeArray);


            $totalEarning = 0;
            foreach ($earningrecord as $earningrecord) {

                $totalEarning = $totalEarning + $earningrecord->earning->amount;

            }

            // joey performance overall haris
            $result = [];
            $categories = CustomerFlagCategories::where('parent_id', '>', 0)->whereNull('deleted_at')->get();
            $flags = JoeyPerformanceHistory::whereJoeyId($joey->id)->get();
            $routeHistory = RouteHistory::select('task_id')->where('joey_id', $joey->id)->whereIn('status', [2, 3, 4])->groupBy('task_id')->get()->count();

            $flagCount = FlagHistory::whereJoeyId($joey->id)->where('unflaged_by', 0)->count();
            $claimCount = Claim::where('joey_id', $joey->id)->whereNotIn('status', [0])->count();

//            if($categories){
            foreach ($categories as $category) {
                $result[$category->id] = [
                    "percentage" => 100,
                ];
            }
//            }


            $applied_cat_data = [];
            foreach ($flags as $flag) {
                $ratingValue = json_decode($flag->rating_value);
                if ($ratingValue->operator == '-') {
                    if (key_exists($flag->flag_cat_id, $applied_cat_data)) {
                        $applied_cat_data[$flag->flag_cat_id] = $applied_cat_data[$flag->flag_cat_id] + 1;
                    } else {
                        $applied_cat_data[$flag->flag_cat_id] = 1;
                    }
                }
            }

            $totalPercentage = 0;
            $totalNegativePer = 0;
            $percentage = 100;


            foreach ($applied_cat_data as $key => $data) {
                if (isset($result[$key])) {
                    $multiCategoryCount = $data / 4;
                    if ($routeHistory > $multiCategoryCount) {
                        $negativePercentage = ($multiCategoryCount / $routeHistory) * 100;
                        $percentage = $result[$key]['percentage'] - $negativePercentage;
                        $totalNegativePer += $negativePercentage;
                    } else {
                        $percentage = 100;
                    }
                    $result[$key]['percentage'] = $percentage;
                }
            }

            $overAll = 0;
            if ($categories->count() > 0) {
                $totalValue = $categories->count() * 100;
                $averagePercentage = $totalValue - $totalNegativePer;
                $overAll = ($averagePercentage / $totalValue) * 100;
            }


            /**
             *  for cash collected
             *
             */

            $cashCollectionType = 'collect';
            $cashCollected = $this->joeyTransactionRepository->joeyCalculationForrating($joey->id, $cashCollectionType);

            $cash = 0;
            foreach ($cashCollected as $cashCollected) {

                $cash = $cash + $cashCollected->earning->amount;
            }


            $response['rating'] = round($ratting, 2) ?? '';
            $response['reviews_count'] = $rattingCount ?? 0;


            $groceryDistance = Sprint::where('joey_id', $joey->id)
                ->whereIn('status_id', [145, 17, 113, 114, 116, 117, 118, 132, 138, 139, 144, 101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112, 131, 135, 136, 143])
                ->get('distance');

            foreach ($groceryDistance as $gdistance) {
                $distances[] = $gdistance->distance;
            }

            if (!empty($distances)) {
                $maxDistance = max($distances);

                $totalD = array_sum($distances);

                $avgD = array_sum($distances) / count($distances);
            } else {
                $maxDistance = 0;
                $totalD = 0;
                $avgD = 0;
            }

            $TotalDifferenceInHours = gmdate("H:i:s", $TotalDifferenceInHours);


            $response['average_distance'] = round($avgD / 1000, 2) ?? '';
            $response['furthest_distance'] = round($maxDistance / 1000, 2) ?? '';
            $response['total_orders'] = $totalOrderCount ?? '';
            $response['total_hours_logged'] = $TotalDifferenceInHours ?? '';
            $response['total_earning'] = round($totalEarning, 2);
            $response['total_cash_collected'] = $cash;
            $response['total_flagged_count'] = $flagCount;
            $response['total_claim_count'] = $claimCount;
            $response['over_all_performance'] = ($overAll == 0) ? 100 : round($overAll, 2);
//            $response['over_all_performance'] = ($overAllPerformance) ? $overAllPerformance . '%' : '100%';

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'rating and summary');
    }


    public function joeyPerformance()
    {
        try {
            $joey = $this->userRepository->find(auth()->user()->id);
            if (empty($joey)) {
                return RestAPI::response('Joey not found', false);
            }

            $categories = CustomerFlagCategories::where('parent_id', '>', 0)->where('is_enable', 1)->whereNull('deleted_at')->get();
            $flags = JoeyPerformanceHistory::whereJoeyId($joey->id)->get();
            $routeHistory = RouteHistory::select('task_id')->where('joey_id', $joey->id)->whereIn('status', [2, 3, 4])->groupBy('task_id')->get()->count();

            $categoryCount = 0;
            $response = ["individual" => [], "overall" => 100];
            foreach ($categories as $category) {
                foreach($category->getChilds as $children){
                    $categoryCount ++;
                    $response['individual'][$children->id] = [
                        "cat_id" => $children->id,
                        "cat_name" => $children->category_name,
                        "percentage" => 100,
                    ];
                }
            }

            $applied_cat_data = [];
            foreach ($flags as $flag) {
                $ratingValue = json_decode($flag->rating_value);
                if ($ratingValue->operator == '-') {
                    if (key_exists($flag->flag_cat_id, $applied_cat_data)) {
                        $applied_cat_data[$flag->flag_cat_id] = $applied_cat_data[$flag->flag_cat_id] + 1;
                    } else {
                        $applied_cat_data[$flag->flag_cat_id] = 1;
                    }
                }
            }

            $totalPercentage = 0;
            $totalNegativePer = 0;
            $percentage = 100;

            foreach ($applied_cat_data as $key => $data) {
                if (isset($response['individual'][$key])) {
                    $multiCategoryCount = $data / 4;
                    if ($routeHistory > $multiCategoryCount) {
                        $negativePercentage = ($multiCategoryCount / $routeHistory) * 100;
                        $percentage = $response['individual'][$key]['percentage'] - $negativePercentage;
                        $totalNegativePer += $negativePercentage;

                    } else {
                        $percentage = 100;
                    }
                    $response['individual'][$key]['percentage'] = round($percentage, 2);
//                    $totalPercentage += $response['individual'][$key]['percentage'];
//                    $totalPercentage += $percentage;

                }
            }

            $overAll = 0;
            if ($categoryCount > 0) {
                $totalValue = $categoryCount * 100;
                $averagePercentage = $totalValue - $totalNegativePer;
                $overAll = ($averagePercentage / $totalValue) * 100;
            }

            $response['overall'] = ($overAll == 0) ? 100 : round($overAll, 2);
            $response['individual'] = array_values($response['individual']);

            return RestAPI::response($response, true, 'joey Performance');

        } catch (\Exception $e) {
            return RestAPI::response($e->getMessage(), false, $e->getMessage());
        }
    }

    /**
     * Joey Overall rating
     */

    public function overallrating(Request $request)
    {

        $data = $request->all();

        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);
            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }

            /**
             * rating calculation
             */
            $rating = Ratings::where('object_type', 'joey')->where('object_id', $joey->id);
            $ratingRecord = $rating->get();

            $totalratingSum = $ratingRecord->sum('rating');

            if (count($ratingRecord) > 0) {
                $ratting = $totalratingSum / count($ratingRecord);
            } else {
                $ratting = $totalratingSum / 1;
            }
            if (count($ratingRecord) > 0) {
                $rattingCount = count($ratingRecord);
            } else {
                $rattingCount = 1;
            }


            /**
             * rating percentages calcualtion for stars
             */
            $ratingFor1Stars = Ratings::where('object_type', 'joey')->where('object_id', $joey->id)->where('rating', 1)->count();
            $star_1Percentage = $ratingFor1Stars / $rattingCount * 100;

            $ratingFor2Stars = Ratings::where('object_type', 'joey')->where('object_id', $joey->id)->where('rating', 2)->count();
            $star_2Percentage = $ratingFor2Stars / $rattingCount * 100;

            $ratingFor3Stars = Ratings::where('object_type', 'joey')->where('object_id', $joey->id)->where('rating', 3)->count();
            $star_3Percentage = $ratingFor3Stars / $rattingCount * 100;

            $ratingFor4Stars = Ratings::where('object_type', 'joey')->where('object_id', $joey->id)->where('rating', 4)->count();
            $star_4Percentage = $ratingFor4Stars / $rattingCount * 100;

            $ratingFor5Stars = Ratings::where('object_type', 'joey')->where('object_id', $joey->id)->where('rating', 5)->count();
            $star_5Percentage = $ratingFor5Stars / $rattingCount * 100;


            $response['overall_rating'] = [
                'rating' => round($ratting, 2) ?? '',
                'reviews_count' => $rattingCount ?? 0
            ];
            $response['rating_distribution'] = [
                '1_stars' => round($star_1Percentage, 2) ?? '',
                '2_stars' => round($star_2Percentage, 2) ?? '',
                '3_stars' => round($star_3Percentage, 2) ?? '',
                '4_stars' => round($star_4Percentage, 2) ?? '',
                '5_stars' => round($star_5Percentage, 2) ?? ''

            ];

            $review = Ratings::where('object_type', 'joey')->where('object_id', $joey->id)->get();

            $response['rating_reviews'] = ReviewResource::collection($review);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'Overall Rating');
    }


    /**
     * Joey Seen basic Category
     */

    public function joeySeenBasicCategory(Request $request)
    {
        $data = $request->all();

        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);
            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }

            $basicCategories = OrderCategory::where('type', 'basic')->where('user_type',NULL)->whereNull('order_category.deleted_at');
            $catIds = $basicCategories->pluck('id');

            $categoriesPassed = JoeyQuiz::whereIn('category_id', $catIds)->where('joey_id', $joey->id)->where('is_passed', 1);

            if ($categoriesPassed->count() >= $basicCategories->count()) {
                $response = [
                    'is_passed' => 1,
                ];
            } else {
                $response = [
                    'is_passed' => 0,
                ];
            }

            $response = $response;

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'Joey Seen Basic Category');
    }


    public function addNote(Request $request)
    {
        $data = $request->all();
        DB::beginTransaction();
        try {
            $joey = $this->userRepository->find(auth()->user()->id);
            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }
            $note_rules = new CreateNoteRequest;
            $note_validator = Validator::make($data, $note_rules->rules());
            if (!$note_validator->passes()) {
                return RestAPI::response($note_validator->errors()->all(), false, 'Validation Error');
            }
            TrackingNote::create(['user_id' => auth()->user()->id, 'tracking_id' => $data['tracking_id'], 'note' => $data['note'], 'type' => 'joey']);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response('Note added successfully!', true, 'Joey Notes');
    }

    public function joeyRouteList(Request $request)
    {
        DB::beginTransaction();
        $joey_id = auth()->user()->id;
        $response = [];
        $completed_status_list = getStatusCodes('competed');
        //$return_status_list = getStatusCodes('return');

        try {
            $routes = JoeyRoutes::where('payout_generated', 0)
                ->whereNull('deleted_at')
                ->whereHas('RouteHistory', function ($query) use ($joey_id) {
                    $query->where('joey_id', $joey_id)
                        ->whereHas('JoeyRouteLocation')->whereNull('deleted_at');
                })->orderBy('id', 'DESC')->limit(10)->get();

            foreach ($routes as $route_key => $route) {
                // getting current route history completed tasks
                $route_history_completed_task = RouteHistory::where('route_id', $route->id)
                    ->whereHas('JoeyRouteLocation', function ($query) {
                        $query->whereNull('deleted_at');
                    })
                    ->whereIn('status', [2, 4])
                    ->pluck('task_id')
                    ->toArray();

                // getting route history completed task count
                $route_history_completed_task_count = count($route_history_completed_task);

                // now getting route location
                $joey_route_locations_task_ids = JoeyRouteLocation::where('route_id', $route->id)
                    ->whereHas('sprintTaskAgainstRouteLocationId', function ($query) use ($completed_status_list) {
                        $query->where('type', 'dropoff')
                            ->whereIn('status_id', [$completed_status_list])
                            ->orderBy('id', 'DESC');
                    })
                    ->whereNull('deleted_at')
                    ->get();

                //
                $joey_route_locations_total_task_ids = JoeyRouteLocation::where('route_id', $route->id)
                    ->whereNull('deleted_at')
                    ->count();

                // now  getting count of locations
                $joey_route_locations_task_count = $joey_route_locations_task_ids->count();

                // now checking the route completed orders counts are matched
                if ($route_history_completed_task_count < $joey_route_locations_task_count) {
                    // updating missing ids to completed in route history on current joey
                    $missing_ids = array_diff($joey_route_locations_task_ids->pluck('task_id')->toArray(), $route_history_completed_task);
                    // geting filter route locatoin data
                    $missing_ids_data = $joey_route_locations_task_ids->whereIn('task_id', $missing_ids)->toArray();
                    $missing_data_insert = [];
                    foreach ($missing_ids_data as $key => $missing_ids_single_data) {
                        $missing_data_insert[$key] = [
                            "route_id" => $route->id,
                            "joey_id" => $joey_id,
                            "status" => 2,
                            "route_location_id" => $missing_ids_single_data['id'],
                            "task_id" => $missing_ids_single_data['task_id'],
                            "ordinal" => $missing_ids_single_data['ordinal'],
                        ];
                    }
                    // inserting data
                    RouteHistory::insert($missing_data_insert);
                    // updating count with new inserting
                    $route_history_completed_task_count += count($missing_data_insert);

                }

                // checking status
                $status = 'Pending';
                switch ($joey_id) {
                    case $joey_id != $route->joey_id:
                        $status = 'Transfer';
                        break;
                    case $route_history_completed_task_count == $joey_route_locations_total_task_ids:
                        $status = 'Completed';
                        break;
                    default:
                        $status = 'Pending';
                }
                // creating response
                $response[$route_key] = ['label' => 'R-' . $route->id,
                    "route_id" => $route->id,
                    "status" => $status,
                ];

            }

            DB::commit();
            if (count($response) > 0) {
                return RestAPI::response($response, true, '');
            } else {
                return RestAPI::response([], true, 'No record found');
            }
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

    }


}
