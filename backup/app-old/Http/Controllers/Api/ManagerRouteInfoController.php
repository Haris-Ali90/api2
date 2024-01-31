<?php

namespace App\Http\Controllers\Api;

use App\Classes\RestAPI;
use App\Http\Resources\JoeyRoutesResource;
use App\Http\Resources\ManagerJoeyRoutesResource;
use App\Http\Resources\ManagerResource;
use App\Http\Resources\ManagerRoutesDetailResource;
use App\Http\Resources\TrackingDetailResource;
use App\Http\Resources\ManagerTrackingDetailsResource;
use App\Models\Joey;
use App\Models\JoeyRouteLocation;
use App\Models\JoeyRoutes;
use App\Models\ManagerBrokerJoey;
use App\Models\MerchantsIds;
use App\Models\Sprint;
use App\Models\SprintTasks;
use App\Models\SprintReattempt;
use App\Models\SprintTaskHistory;
use App\Repositories\Interfaces\ManagerRepositoryInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use stdClass;

class ManagerRouteInfoController extends ApiBaseController
{
    private $managerRepository;
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    public function statusmap($id)
    {
        $statusid = array("136" => "Client requested to cancel the order",
            "137" => "Delay in delivery due to weather or natural disaster",
            "118" => "left at back door",
            "117" => "left with concierge",
            "135" => "Customer refused delivery",
            "108" => "Customer unavailable-Incorrect address",
            "106" => "Customer unavailable - delivery returned",
            "107" => "Customer unavailable - Left voice mail - order returned",
            "109" => "Customer unavailable - Incorrect phone number",
            "142" => "Damaged at hub (before going OFD)",
            "143" => "Damaged on road - undeliverable",
            "144" => "Delivery to mailroom",
            "103" => "Delay at pickup",
            "139" => "Delivery left on front porch",
            "138" => "Delivery left in the garage",
            "114" => "Successful delivery at door",
            "113" => "Successfully hand delivered",
            "120" => "Delivery at Hub",
            "110" => "Delivery to hub for re-delivery",
            "111" => "Delivery to hub for return to merchant",
            "121" => "Out for delivery",
            "102" => "Joey Incident",
            "104" => "Damaged on road - delivery will be attempted",
            "105" => "Item damaged - returned to merchant",
            "129" => "Joey at hub",
            "128" => "Package on the way to hub",
            "140" => "Delivery missorted, may cause delay",
            "116" => "Successful delivery to neighbour",
            "132" => "Office closed - safe dropped",
            "101" => "Joey on the way to pickup",
            "32" => "Order accepted by Joey",
            "14" => "Merchant accepted",
            "36" => "Cancelled by JoeyCo",
            "124" => "At hub - processing",
            "38" => "Draft",
            "18" => "Delivery failed",
            "56" => "Partially delivered",
            "17" => "Delivery success",
            "68" => "Joey is at dropoff location",
            "67" => "Joey is at pickup location",
            "13" => "Waiting for merchant to accept",
            "16" => "Joey failed to pickup order",
            "57" => "Not all orders were picked up",
            "15" => "Order is with Joey",
            "112" => "To be re-attempted",
            "131" => "Office closed - returned to hub",
            "125" => "Pickup at store - confirmed",
            "61" => "Scheduled order",
            "37" => "Customer cancelled the order",
            "34" => "Customer is editting the order",
            "35" => "Merchant cancelled the order",
            "42" => "Merchant completed the order",
            "54" => "Merchant declined the order",
            "33" => "Merchant is editting the order",
            "29" => "Merchant is unavailable",
            "24" => "Looking for a Joey",
            "23" => "Waiting for merchant(s) to accept",
            "28" => "Order is with Joey",
            "133" => "Packages sorted",
            "55" => "ONLINE PAYMENT EXPIRED",
            "12" => "ONLINE PAYMENT FAILED",
            "53" => "Waiting for customer to pay",
            "141" => "Lost package",
            "60" => "Task failure",
            "145" => 'Returned To Merchant',
            "146" => "Delivery Missorted, Incorrect Address");
        return $statusid[$id];
    }

    public function __construct(ManagerRepositoryInterface $managerRepository)
    {
        $this->managerRepository = $managerRepository;
    }

    /**
     * for route info
     *
     */
    public function routeInfo(Request $request)
    {
        $request->validate([
            'hub_id' => 'required|exists:finance_vendor_city_relations,id',
            'date' => 'required|date_format:Y-m-d',
        ]);


        $data = $request->all();
        $date = $data['date'];
        $hub_id = $data['hub_id'];
        $hubId = changeHubIds($hub_id);


        if($request->has('joey_id')) {
            if ($data['joey_id'] == "''" || $data['joey_id'] == '""' || $data['joey_id'] == "null") {
                $data['joey_id'] = null;
            }
        }

        if($request->has('route_id')) {
            if ($data['route_id'] == "''" || $data['route_id'] == '""' || $data['route_id'] == "null") {
                $data['route_id'] = null;
            }
        }


        DB::beginTransaction();
        try {
            if(empty($date) && empty($hubId)){

                return RestAPI::response('No record found against this Tracking Id', false);
            }

            $montreal_info = JoeyRoutes::whereHas('ManagerJoeyRouteLocation')
                ->where('joey_routes.date', 'like', $date . "%")
                ->where('joey_routes.hub', $hubId)
                ->where('joey_routes.deleted_at', null);

            //Check Conditions Filter For Routes
            if($request->has('route_id')) {
                if ($data['route_id'] != null) {
                    $montreal_info->where('joey_routes.id', $data['route_id']);
                }
            }

            //Check Conditions Filter For Joeys
            if($request->has('joey_id')) {
                if ($data['joey_id'] != null) {
                    $montreal_info->where('joey_routes.joey_id', $data['joey_id']);
                }
            }

            //Getting All Broker Joey
//            if(isset( $data['broker_id']))
//            {
//                $broker_id = $data['broker_id'];
//                $broker = ManagerBrokerJoey::where('brooker_id',$broker_id)->pluck('joey_id')->toArray();
//                $montreal_info->whereIn('joey_routes.joey_id', $broker);
//
//            }

            $montreal_info = $montreal_info->orderBy('joey_routes.id', 'ASC')
                ->groupBy('joey_routes.id')
                ->select('joey_routes.*')
                ->get();

            if(!empty($montreal_info)){

                $response = ManagerJoeyRoutesResource::collection($montreal_info);
                if(count($montreal_info) > 0){
                    return RestAPI::response($response, true, 'Route Info Details');
                }else{
                    return RestAPI::response($response, true, 'No record in route info');
                }
            }
            else{
                return RestAPI::response('No record found against this Tracking Id', false);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }


    }

    /**
     * for route info
     *
     */
    public function routeDetail(Request $request)
    {
        $request->validate([
            'route_id' => 'required|exists:joey_routes,id',
            'hub_id' => 'required|exists:finance_vendor_city_relations,id',
        ]);

        $data = $request->all();

        if($request->has('tracking_id')) {
            if($data['tracking_id'] == "''" || $data['tracking_id'] == '""' || $data['tracking_id'] == "null"){
                $data['tracking_id'] = null;
            }
        }

        if($request->has('ordinal')) {
            if ($data['ordinal'] == "''" || $data['ordinal'] == '""' || $data['ordinal'] == "null") {
                $data['ordinal'] = null;
            }
        }

        if($request->has('status_id')) {
            if ($data['status_id'] == "''" || $data['status_id'] == '""' || $data['status_id'] == "null") {
                $data['status_id'] = null;
            }
        }

        $route_id = $data['route_id'];
        $hub_id = $data['hub_id'];

        $hubId = changeHubIds($hub_id);

        DB::beginTransaction();
        try {
            if(empty($route_id) && empty($hub_id)){

                return RestAPI::response('No record found against this Tracking Id', false);
            }
            $routeDetail = JoeyRouteLocation::whereHas('joeyRoute', function ($query) use ($hubId){
                    $query->where('hub', $hubId);
                })
                ->whereHas('managerSprintTask.managerMerchantIds')
                ->whereHas('managerSprintTask.managerLocation')
                ->whereHas('managerSprintTask.managerSprintsSprints')
                ->whereHas('managerSprintTask.managerSprintContact')
                ->whereNull('joey_route_locations.deleted_at')
                ->where('route_id', '=', $route_id)
                ->orderBy('joey_route_locations.ordinal', 'asc');
            $routeDetail->get();


            //Check Conditions Filter For Routes
            if($request->has('tracking_id')) {
                if ($data['tracking_id'] != null) {
                    $tracking_id = $data['tracking_id'];
                    $routeDetail->whereHas('managerSprintTask.managerMerchantIds', function ($query) use ($tracking_id) {
                        $query->where('merchantids.tracking_id', $tracking_id);
                    });
                }
            }

            //Check Conditions Filter For Joeys
            if($request->has('ordinal')) {
                if ($data['ordinal'] != null) {
                    $routeDetail->where('joey_route_locations.ordinal', $data['ordinal']);
                }
            }

            //Getting All Broker Joey
            if($request->has('status_id')) {
                if ($data['status_id'] != null) {
                    $status = $data['status_id'];
                    $routeDetail->whereHas('managerSprintTask.managerSprintsSprints', function ($query) use ($status) {
                        $query->where('sprint__sprints.status_id', $status);
                    });
                }
            }


            $routeDetail = $routeDetail->get();

            if(!empty($routeDetail)){

                $response = ManagerRoutesDetailResource::collection($routeDetail);
                if(count($routeDetail) > 0){
                    return RestAPI::response($response, true, 'Route info details');
                }else{
                    return RestAPI::response('No record in route detail', false, 'detail_failed');
                }
                //print_r($response);
            }
            else{
                return RestAPI::response('No record found against this Tracking Id', false);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'Route Details');
    }

    public function orderDetail(Request $request)
    {
        $request->validate([
            'sprint_id' => 'required|exists:sprint__sprints,id'
        ]);

        $data = $request->all();
        $sprint_id = $data['sprint_id'];

        DB::beginTransaction();
        try {
            if(empty($sprint_id)){
                return RestAPI::response('No record found against this sprint Id', false);
            }

            $data = [];
            $result = Sprint::join('sprint__tasks', 'sprint_id', '=', 'sprint__sprints.id')
                ->leftJoin('merchantids', 'merchantids.task_id', '=', 'sprint__tasks.id')
                ->leftJoin('joey_route_locations', 'joey_route_locations.task_id', '=', 'sprint__tasks.id')
                ->leftJoin('joey_routes', 'joey_routes.id', '=', 'joey_route_locations.route_id')
                ->leftJoin('joeys', 'joeys.id', '=', 'joey_routes.joey_id')
                ->join('locations', 'sprint__tasks.location_id', '=', 'locations.id')
                ->join('sprint__contacts', 'contact_id', '=', 'sprint__contacts.id')
                ->leftJoin('vendors', 'creator_id', '=', 'vendors.id')
                ->where('sprint__tasks.sprint_id', '=', $sprint_id)
                ->whereNull('joey_route_locations.deleted_at')
                ->orderBy('ordinal', 'DESC')->take(1)
                ->get(array('sprint__tasks.*', 'joey_routes.id as route_id',\DB::raw("CONVERT_TZ(joey_routes.date,'UTC','America/Toronto') as route_date"), 'locations.address', 'locations.suite', 'locations.postal_code', 'sprint__contacts.name', 'sprint__contacts.phone', 'sprint__contacts.email',
                    'joeys.first_name as joey_firstname', 'joeys.id as joey_id',
                    'joeys.last_name as joey_lastname', 'vendors.first_name as merchant_firstname', 'vendors.last_name as merchant_lastname', 'merchantids.scheduled_duetime'
                , 'joeys.id as joey_id', 'merchantids.tracking_id', 'joeys.phone as joey_contact', 'joey_route_locations.ordinal as stop_number', 'merchantids.merchant_order_num', 'merchantids.address_line2', 'sprint__sprints.creator_id'));

            $i = 0;

//            dd($result);

            foreach($result as $results){
                $data[] = [
                    'tracking_id' => $results->tracking_id,
                    'merchant_order_num' => $results->merchant_order_num,
                    'route_no' => 'R-'.$results->route_id,
                    'customer_name' => $results->name,
                    'customer_phone' => $results->phone,
                    'customer_email' => $results->email,
                    'customer_address' => $results->address,
                    'joey' => $results->joey_firstname . ' ' . $results->joey_lastname,
                    'joey_contact' => $results->joey_contact,
                    'merchant' => $results->address.' '.$results->merchant_last_name,
                ];
            }

//            return RestAPI::response($data, true, 'Order Details');
//            dd($data);

            $data = [];

            foreach ($result as $tasks) {
                $first = array();
                $second = array();
                $third = array();
                $data[$i] = $tasks;
                $taskHistory = SprintTaskHistory::where('sprint_id', '=', $tasks->sprint_id)->WhereNotIn('status_id', [17, 38])->orderBy('date')
                    //->where('active','=',1)
                    ->get(['status_id', \DB::raw("CONVERT_TZ(created_at,'UTC','America/Toronto') as created_at")]);

                $returnTOHubDate = SprintReattempt::
                where('sprint_reattempts.sprint_id', '=', $tasks->sprint_id)->orderBy('created_at')
                    ->first();

                if (!empty($returnTOHubDate)) {
                    $taskHistoryre = SprintTaskHistory::where('sprint_id', '=', $returnTOHubDate->reattempt_of)->WhereNotIn('status_id', [17, 38])->orderBy('date')
                        //->where('active','=',1)
                        ->get(['status_id', \DB::raw("CONVERT_TZ(created_at,'UTC','America/Toronto') as created_at")]);

                    foreach ($taskHistoryre as $history) {

                        $second[$history->status_id]['id'] = $history->status_id;
                        if ($history->status_id == 13) {
                            $second[$history->status_id]['description'] = 'At hub - processing';
                        } else {
                            $second[$history->status_id]['description'] = $this->statusmap($history->status_id);
                        }
                        $second[$history->status_id]['created_at'] = $history->created_at;

                    }

                }

                if (!empty($returnTOHubDate)) {
                    $returnTO2 = SprintReattempt::
                    where('sprint_reattempts.sprint_id', '=', $returnTOHubDate->reattempt_of)->orderBy('created_at')
                        ->first();

                    if (!empty($returnTO2)) {
                        $taskHistoryre = SprintTaskHistory::where('sprint_id', '=', $returnTO2->reattempt_of)->WhereNotIn('status_id', [17, 38])->orderBy('date')
                            //->where('active','=',1)
                            ->get(['status_id', \DB::raw("CONVERT_TZ(created_at,'UTC','America/Toronto') as created_at")]);

                        foreach ($taskHistoryre as $history) {

                            $first[$history->status_id]['id'] = $history->status_id;
                            if ($history->status_id == 13) {
                                $first[$history->status_id]['description'] = 'At hub - processing';
                            } else {
                                $first[$history->status_id]['description'] = $this->statusmap($history->status_id);
                            }
                            $first[$history->status_id]['created_at'] = $history->created_at;

                        }

                    }
                }



                foreach ($taskHistory as $history) {

                    if (in_array($history->status_id, [61,13]) or in_array($history->status_id, [124,125])) {

                        $third[$history->status_id]['id'] = $history->status_id;

                        if ($history->status_id == 13) {
                            $third[$history->status_id]['description'] = 'At hub - processing';
                        } else {
                            $third[$history->status_id]['description'] = $this->statusmap($history->status_id);
                        }
                        $third[$history->status_id]['created_at'] = $history->created_at;
                    }
                    else{
//                        dd($taskHistory);
                        if ($history->created_at >= $tasks->route_date){
                            if(isset($third[$history->status_id])){
                                $third[$history->status_id]['id'] = $history->status_id;
                                if ($history->status_id == 13) {
                                    $third[$history->status_id]['description'] = 'At hub - processing';
                                } else {
                                    $third[$history->status_id]['description'] = $this->statusmap($history->status_id);
                                }
                                $third[$history->status_id]['created_at'] = $history->created_at;
                            }
                        }
                    }
                }

                if ($second != null) {
                    $sort_key = array_column($second, 'created_at');
                    array_multisort($sort_key, SORT_ASC, $second);
                }
                if ($third != null) {
                    $sort_key = array_column($third, 'created_at');
                    array_multisort($sort_key, SORT_ASC, $third);
                }
                if ($first != null) {
                    $sort_key = array_column($first, 'created_at');
                    array_multisort($sort_key, SORT_ASC, $first);
                }

                $data[$i]['first'] = $first;
                $data[$i]['second'] = $second;
                $data[$i]['third'] = $third;
                $i++;
            }
//
//            dd($data);
            return RestAPI::response($data, true, 'Order Details');

//            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
    }

    public function trackingSort(Request $request){

        $request->validate([
            'tracking_id' => 'required|exists:merchantids,tracking_id'
        ]);

        $data = $request->all();

        $response =[];
        $message='Invalid Tracking Id';
        $status = '';
        $statusCode = false;

        $merchantIds = MerchantsIds::where('tracking_id',$data['tracking_id'])->first();
        $sprintTaskData = SprintTasks::find($merchantIds->task_id);

        if($sprintTaskData->status_id == 133){

 			   $loc = JoeyRouteLocation::join('merchantids','merchantids.task_id','=','joey_route_locations.task_id')
                    ->where('tracking_id','=',$data['tracking_id'])
                    ->whereNull('joey_route_locations.deleted_at')
                    ->first(['joey_route_locations.id','joey_route_locations.created_at','joey_route_locations.task_id','joey_route_locations.route_id','ordinal','merchant_order_num','joey_route_locations.is_transfered']);


                $response['id'] = $loc->id;
                $response['num'] = "R-".$loc->route_id."-".$loc->ordinal;
                $response['merchant_order_num'] = $loc->merchant_order_num;
                $response['tracking_id'] = $data['tracking_id'];
                $message = 'Package Sorted successfully';
                $statusCode = true;

				return RestAPI::response($response, $statusCode, $message);
        }

            if($sprintTaskData->status_id == 61 || $sprintTaskData->status_id == 125){
                $status = 124;
                $statusCode = true;
                SprintTaskHistory::insert([
                    'sprint__tasks_id'=>$sprintTaskData->id,
                    'sprint_id'=>$sprintTaskData->sprint_id,
                    'status_id'=>125,
                    'date'=> date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s').'- 10 minute')),
                    'created_at'=>date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s').'- 10 minute')),
                ]);
                SprintTaskHistory::insert([
                    'sprint__tasks_id'=>$sprintTaskData->id,
                    'sprint_id'=>$sprintTaskData->sprint_id,
                    'status_id'=> 124,
                    'date'=>date('y-m-d H:i:s'),
                    'created_at'=>date('y-m-d H:i:s')
                ]);
                $response = new stdClass();
                $message = 'InBound scanned successfully';
            }

            if($sprintTaskData->status_id == 124){

                $loc = JoeyRouteLocation::join('merchantids','merchantids.task_id','=','joey_route_locations.task_id')
                    ->where('tracking_id','=',$data['tracking_id'])
                    ->whereNull('joey_route_locations.deleted_at')
                    ->first(['joey_route_locations.id','joey_route_locations.created_at','joey_route_locations.task_id','joey_route_locations.route_id','ordinal','merchant_order_num','joey_route_locations.is_transfered']);

                if(empty($loc)){
                    return RestAPI::response('Invalid Tracking Id', false);
                }

                $status = 133;
                $statusCode = true;
                SprintTaskHistory::insert([
                    'sprint__tasks_id'=>$loc->task_id,
                    'sprint_id'=>$sprintTaskData->sprint_id,
                    'status_id'=>$status,
                    'date'=>date('y-m-d H:i:s'),
                    'created_at'=>date('y-m-d H:i:s')
                ]);
                $response['id'] = $loc->id;
                $response['num'] = "R-".$loc->route_id."-".$loc->ordinal;
                $response['merchant_order_num'] = $loc->merchant_order_num;
                $response['tracking_id'] = $data['tracking_id'];
                $message = 'Package Sorted successfully';
            }

            SprintTasks::find($sprintTaskData->id)->update(['status_id'=> ($status != '') ? $status : $sprintTaskData->status_id]);
            Sprint::find($sprintTaskData->sprint_id)->update(['status_id'=> ($status != '') ? $status : $sprintTaskData->status_id]);

            if($statusCode == false){
                return RestAPI::response($message, $statusCode);
            }
            return RestAPI::response($response, $statusCode, $message);

    }
}
