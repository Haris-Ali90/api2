<?php

namespace App\Http\Controllers\Api;

use App\Classes\RestAPI;
use App\Http\Controllers\Controller;
use App\Models\AmazonEnteries;
use App\Models\CTCEntry;
use App\Models\CtcVendor;
use App\Models\FinanceVendorCity;
use App\Models\FinanceVendorCityDetail;
use App\Models\JoeyRoutes;
use App\Models\Sprint;
use App\Models\WarehouseJoeysCount;
use DateTime;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InBoundController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'hub_id' => 'required|exists:finance_vendor_city_relations,id',
            'date' => 'required|date_format:Y-m-d'
        ]);

        $input = $request->all();
        $hub_id = $input['hub_id'];
        $date = $input['date'];

        $hub_name = FinanceVendorCity::where('id', $hub_id)->first();
        $vendors = FinanceVendorCityDetail::where('vendor_city_realtions_id', $hub_id)->pluck('vendors_id')->toArray();
        $ctcVendorIds = CtcVendor::pluck('vendor_id')->toArray();

        $sprint = new Sprint();
        $warehouse_data = [];

        #Total Packages
        $montreal_packages = 0;
        $ottawa_packages = 0;
        $ctc_packages = 0;

        #Total Route
        $montreal_route = [];
        $ottawa_route = [];
        $ctc_route = [];

        #Total damaged
        $montreal_damage = 0;
        $ottawa_damage = 0;
        $ctc_damage = 0;

        #Total Not Receive
        $montreal_not_receive = 0;
        $ottawa_not_receive = 0;
        $ctc_not_receive = 0;

        $warehouse_data[0] = [
            'date_id' => '',
            'setup_start_time' => null,
            'setup_end_time' => null,
            'start_sorting_time' => null,
            'end_sorting_time' => null,
            'number_sorter_count' => 0,
            'internal_sorter_count' => 0,
            'brooker_sorter_count' => 0,
            'total_packages' => 0,
            'total_damaged_packages' => 0,
            'not_received_orders' => 0,
            'total_route' => 0,
//            'total_normal_route' => 0,
            'total_custom_route' => 0,
            'total_big_box_route' => 0,
            'hub_name' => 'N/A',
            'manager_on_duty' => 'N/A'
        ];


        $firstOfMonth = date("Y", strtotime($date)) . '-' . date("m", strtotime($date)) . '-01';

//        dd($date);

        $number_of_sorters = WarehouseJoeysCount::where('hub_id', $hub_id)->where('date', $date)->first();


        if ($number_of_sorters) {
            $internal_sorter_count = isset($number_of_sorters->internal_sorter_count) ? $number_of_sorters->internal_sorter_count : 0;
            $brooker_sorter_count = isset($number_of_sorters->brooker_sorter_count) ? $number_of_sorters->brooker_sorter_count : 0;

            $warehouse_data[0]['warehouse_sorters_id'] = $number_of_sorters->id;
            $warehouse_data[0]['setup_start_time'] = isset($number_of_sorters->setup_start_time) ? date('H:i A', strtotime($number_of_sorters->setup_start_time)) : null;
            $warehouse_data[0]['setup_end_time'] = isset($number_of_sorters->setup_end_time) ? date('H:i A', strtotime($number_of_sorters->setup_end_time)) : null;
            $warehouse_data[0]['start_sorting_time'] = isset($number_of_sorters->start_sorting_time) ? date('H:i A', strtotime($number_of_sorters->start_sorting_time)) : null;
            $warehouse_data[0]['end_sorting_time'] = isset($number_of_sorters->end_sorting_time) ? date('H:i A', strtotime($number_of_sorters->end_sorting_time)) : null;
            $warehouse_data[0]['number_sorter_count'] = $internal_sorter_count + $brooker_sorter_count;
            $warehouse_data[0]['internal_sorter_count'] = $internal_sorter_count;
            $warehouse_data[0]['brooker_sorter_count'] = $brooker_sorter_count;
            $warehouse_data[0]['manager_on_duty'] = isset($number_of_sorters->Manager) ? $number_of_sorters->Manager->name : 'N/A';
            $warehouse_data[0]['manager_on_duty_id'] = ($number_of_sorters->manager_on_duty==null) ? 'N/A':$number_of_sorters->manager_on_duty;

        }

        $warehouse_data[0]['hub_name'] = $hub_name->city_name;

        if (in_array('477260', $vendors)) {
            $amazon_date = date('Y-m-d', strtotime($date . ' -1 days'));

            $start_dt = new DateTime($amazon_date." 00:00:00", new DateTimezone('America/Toronto'));
            $start_dt->setTimeZone(new DateTimezone('UTC'));
            $start = $start_dt->format('Y-m-d H:i:s');

            $end_dt = new DateTime($amazon_date." 23:59:59", new DateTimezone('America/Toronto'));
            $end_dt->setTimeZone(new DateTimezone('UTC'));
            $end = $end_dt->format('Y-m-d H:i:s');


            $sprintIds = AmazonEnteries::where('created_at','>',$start)->where('created_at','<',$end)
                ->where('creator_id', '477260')->pluck('sprint_id')->toArray();

            #Total Packages
            $montreal_packages = AmazonEnteries::whereIn('sprint_id', $sprintIds)->whereNotNull('sorted_at')->count();
            //SprintTaskHistory::whereIn('sprint_id', $sprintIds)->where('status_id', 133)->groupBy('sprint_id')->pluck('id')->toArray();

            #Item Damaged Packages
            $montreal_damage = AmazonEnteries::whereIn('sprint_id', $sprintIds)->where('task_status_id', 105)->count();

            #Total Route
            $montreal_route = JoeyRoutes::where('hub',16)->where('date', 'like', $date . "%")->pluck('id')->toArray();
            //$montreal_route = AmazonEnteries::whereIn('sprint_id', $sprintIds)->groupBy('route_id')->pluck('route_id')->toArray();

            #Total Not Receive
            $montreal_not_receive = AmazonEnteries::whereIn('sprint_id', $sprintIds)->where('task_status_id', 61)->count();

        }
        if (in_array('477282', $vendors)) {
            $amazon_date = date('Y-m-d', strtotime($date . ' -1 days'));


            $start_dt = new DateTime($amazon_date." 00:00:00", new DateTimezone('America/Toronto'));
            $start_dt->setTimeZone(new DateTimezone('UTC'));
            $start = $start_dt->format('Y-m-d H:i:s');

            $end_dt = new DateTime($amazon_date." 23:59:59", new DateTimezone('America/Toronto'));
            $end_dt->setTimeZone(new DateTimezone('UTC'));
            $end = $end_dt->format('Y-m-d H:i:s');


            $sprintIds = AmazonEnteries::where('created_at','>',$start)->where('created_at','<',$end)
                ->where('creator_id', '477282')->pluck('sprint_id')->toArray();

            #Total Packages
            $ottawa_packages = AmazonEnteries::whereIn('sprint_id', $sprintIds)->whereNotNull('sorted_at')->count();
            // SprintTaskHistory::whereIn('sprint_id', $sprintIds)->where('status_id', 133)->groupBy('sprint_id')->pluck('id')->toArray();

            #Item Damaged Packages
            $ottawa_damage = AmazonEnteries::whereIn('sprint_id', $sprintIds)->where('task_status_id', 105)->count();

            #Total Route
            $ottawa_route = JoeyRoutes::where('hub',19)->where('date', 'like', $date . "%")->pluck('id')->toArray();
            // $ottawa_route = AmazonEnteries::whereIn('sprint_id', $sprintIds)->groupBy('route_id')->pluck('route_id')->toArray();

            #Total Not Receive
            $ottawa_not_receive = AmazonEnteries::whereIn('sprint_id', $sprintIds)->where('task_status_id', 61)->count();
        }
        if (count(array_intersect($ctcVendorIds, $vendors)) > 0) {
            $ctc_ids = array_intersect($ctcVendorIds, $vendors);

            $start_dt = new DateTime($date." 00:00:00", new DateTimezone('America/Toronto'));
            $start_dt->setTimeZone(new DateTimezone('UTC'));
            $start = $start_dt->format('Y-m-d H:i:s');

            $end_dt = new DateTime($date." 23:59:59", new DateTimezone('America/Toronto'));
            $end_dt->setTimeZone(new DateTimezone('UTC'));
            $end = $end_dt->format('Y-m-d H:i:s');


            $sprintIds = CTCEntry::whereIn('creator_id', $ctc_ids)->where('created_at','>',$start)->where('created_at','<',$end)
                ->pluck('sprint_id')->toArray();

            #Total Packages
            $ctc_packages = CTCEntry::whereIn('sprint_id', $sprintIds)->whereNotNull('sorted_at')->count();
            //SprintTaskHistory::whereIn('sprint_id', $sprintIds)->where('status_id', 133)->groupBy('sprint_id')->pluck('id')->toArray();

            #Item Damaged Packages
            $ctc_damage = CTCEntry::whereIn('sprint_id', $sprintIds)->where('task_status_id', 105)->count();

            #Total Route
            $ctc_route = JoeyRoutes::where('hub',17)->where('date', 'like', $date . "%")->pluck('id')->toArray();
            //$ctc_route = CTCEntry::whereIn('sprint_id', $sprintIds)->groupBy('route_id')->pluck('route_id')->toArray();

            #Total Not Receive
            $ctc_not_receive = CTCEntry::whereIn('sprint_id', $sprintIds)->where('task_status_id', 61)->count();
        }

        $warehouse_data[0]['total_packages'] = $montreal_packages + $ottawa_packages + $ctc_packages;
        $warehouse_data[0]['total_damaged_packages'] = $montreal_damage + $ottawa_damage + $ctc_damage;

        $warehouse_data[0]['not_received_orders'] = $montreal_not_receive + $ottawa_not_receive + $ctc_not_receive;
        $total_route = 0;
        $normal_route = 0;
        $custom_route = 0;
        $big_box_route = 0;
        $routeIds = array_merge($montreal_route, $ottawa_route, $ctc_route);

        if (!empty($routeIds)) {
            $route_data = JoeyRoutes::whereIn('id',$routeIds)->where('date', 'like', $date . "%")->whereNull('deleted_at')->get();
            foreach ($route_data as $route) {
                $route_location_check = DB::table('joey_route_locations')->where('route_id', $route->id)->whereNull('deleted_at')->first();
                if ($route_location_check) {
                    if ($route->zone != null) {
                        $is_custom_check = DB::table("zones_routing")->where('id', $route->zone)->whereNull('is_custom_routing')->first();
                        if ($is_custom_check) {
                            $normal_route++;
                        } else {
                            $route_location = DB::table('joey_route_locations')->where('route_id', $route->id)->first();
                            if ($route_location) {
                                $tracking = DB::table('merchantids')->where('task_id', $route_location->task_id)->first();
                                if ($tracking) {
                                    $custom_route_data = DB::table('custom_routing_tracking_id')->where('tracking_id', $tracking->tracking_id)->first();
                                    if ($custom_route_data) {
                                        if ($custom_route_data->is_big_box == 1) {
                                            $big_box_route++;
                                        } else {
                                            $custom_route++;
                                        }
                                    } else {
                                        $custom_route++;
                                    }
                                } else {
                                    $custom_route++;
                                }
                            } else {
                                $custom_route++;
                            }
                        }
                    } else {
                        $route_location = DB::table('joey_route_locations')->where('route_id', $route->id)->first();
                        if ($route_location) {
                            $tracking = DB::table('merchantids')->where('task_id', $route_location->task_id)->first();
                            if ($tracking) {
                                $custom_route_data = DB::table('custom_routing_tracking_id')->where('tracking_id', $tracking->tracking_id)->first();
                                if ($custom_route_data) {
                                    if ($custom_route_data->is_big_box == 1) {
                                        $big_box_route++;
                                    } else {
                                        $custom_route++;
                                    }
                                } else {
                                    $custom_route++;
                                }
                            } else {
                                $custom_route++;
                            }
                        } else {
                            $custom_route++;
                        }
                    }
                    $total_route++;
                }
            }
        }

        $warehouse_data[0]['total_route'] = $total_route;
//        $warehouse_data[0]['total_normal_route'] = $normal_route;
        $warehouse_data[0]['total_system_routes'] = $normal_route;
        $warehouse_data[0]['total_custom_route'] = $custom_route;
        $warehouse_data[0]['total_big_box_route'] = $big_box_route;
        $warehouse_data[0]['date'] = date('F d, Y', strtotime($date));
        $warehouse_data[0]['day'] = 'Day ' . date('d', strtotime($date));
        $week_count = intval(date("W", strtotime($date))) - intval(date("W", strtotime($firstOfMonth)));
        $week = '';
        if ($week_count == 1) {
            $week = '1st Week';
        } elseif ($week_count == 2) {
            $week = '2nd Week';
        } elseif ($week_count == 3) {
            $week = '3rd Week';
        } elseif ($week_count == 4) {
            $week = '4th Week';
        } else {
            $week = '';
        }
        $warehouse_data[0]['week'] = $week;
        $warehouse_data[0]['date_id'] = $date;
        $setupState = 0;
        $sortingState = 0;

        $warehouseSorters = WarehouseJoeysCount::where('hub_id', $input['hub_id'])->where('date', $input['date'])->first();

        if(!empty($warehouseSorters)){
            if($warehouseSorters->end_sorting_time == null && $warehouseSorters->start_sorting_time != null){
                $sortingState = 1;
            }
            if($warehouseSorters->end_sorting_time != null && $warehouseSorters->start_sorting_time != null){
                $sortingState = 2;
            }
            if($warehouseSorters->setup_start_time != null && $warehouseSorters->setup_end_time == null){
                $setupState = 1;
            }
            if($warehouseSorters->setup_start_time != null && $warehouseSorters->setup_end_time != null){
                $setupState = 2;
            }
        }
        $warehouse_data[0]['sorting_state'] = $sortingState;
        $warehouse_data[0]['setup_state'] = $setupState;

        return RestAPI::response($warehouse_data, true, 'In Bound');

    }

    public function inboundSortingTime(Request $request)
    {
        $request->validate([
            'hub_id' => 'required|exists:finance_vendor_city_relations,id',
            'date' => 'required|date_format:Y-m-d'
        ]);

        $input = $request->all();
        $time = date('H:i:s');
        $record = [];
        $warehouseSorters = WarehouseJoeysCount::where('hub_id', $input['hub_id'])->where('date', $input['date'])->first();
        if ($warehouseSorters) {
            if($warehouseSorters->start_sorting_time != null && $warehouseSorters->end_sorting_time != null){
                return RestAPI::response('Already end sorting time',false, 'Already end sorting time');
            }

            if($warehouseSorters->end_sorting_time == null && $warehouseSorters->start_sorting_time != null){
                $warehouseSorters->end_sorting_time = $time;
                $warehouseSorters->save();
                $record['state'] = 2;
                $record['time'] = date('H:i A', strtotime($time));
            }
            if($warehouseSorters->start_sorting_time == null && $warehouseSorters->end_sorting_time == null){
                $warehouseSorters->start_sorting_time = $time;
                $warehouseSorters->save();
                $record['state'] = 1;
                $record['time'] = date('H:i A', strtotime($time));
            }

        } else {
            $recordCreate = [
                'hub_id' => $input['hub_id'],
                'date' => $input['date'],
                'start_sorting_time' => $time
            ];
            WarehouseJoeysCount::create($recordCreate);
            $record['state'] = 1;
            $record['time'] = date('H:i A', strtotime($time));
        }
        return RestAPI::response($record, true, 'Success sorting time');
    }

    public function inboundSetupTime(Request $request)
    {

        $request->validate([
            'hub_id' => 'required|exists:finance_vendor_city_relations,id',
            'date' => 'required|date_format:Y-m-d'
        ]);
        $input = $request->all();
        $time = date('H:i:s');
        $record = [];
        $warehouseSorters = WarehouseJoeysCount::where('hub_id', $input['hub_id'])->where('date', $input['date'])->first();
        if ($warehouseSorters) {

            if($warehouseSorters->setup_start_time != null && $warehouseSorters->setup_end_time != null){
                return RestAPI::response('Already end setup time',false, 'Already end setup time');
            }

            if($warehouseSorters->setup_end_time == null && $warehouseSorters->setup_start_time != null){
                $warehouseSorters->setup_end_time = $time;
                $warehouseSorters->save();
                $record['state'] = 2;
                $record['time'] = date('H:i A', strtotime($time));
            }
            if($warehouseSorters->setup_start_time == null && $warehouseSorters->setup_end_time == null){
                $warehouseSorters->setup_start_time = $time;
                $warehouseSorters->save();
                $record['state'] = 1;
                $record['time'] = date('H:i A', strtotime($time));
            }

        } else {
            $recordCreate = [
                'hub_id' => $input['hub_id'],
                'date' => $input['date'],
                'setup_start_time' => $time
            ];
            WarehouseJoeysCount::create($recordCreate);
            $record['state'] = 1;
            $record['time'] = date('H:i A', strtotime($time));
        }
        return RestAPI::response($record, true, 'Success setup time');
    }

    public function wareHouseSorterUpdate(Request $request)
    {
        $request->validate([
            'hub_id' => 'required|exists:finance_vendor_city_relations,id',
            'date' => 'required|date_format:Y-m-d',
            'brooker_sorter_count' => 'required|numeric',
            'internal_sorter_count' => 'required|numeric',
            'manager_on_duty' => 'exists:dashboard_managers,id'
        ]);

        $input = $request->all();
        if($input['brooker_sorter_count']==''){
            $input['brooker_sorter_count']=0;
        }
        if($input['internal_sorter_count']==''){
            $input['internal_sorter_count']=0;
        }

        $warehouseSorters = WarehouseJoeysCount::where('hub_id', $input['hub_id'])->where('date', $input['date'])->first();
        if ($warehouseSorters) {

            $warehouseSorters->brooker_sorter_count = $input['brooker_sorter_count'];
            $warehouseSorters->internal_sorter_count = $input['internal_sorter_count'];
            if($input['manager_on_duty']!=''){
                $warehouseSorters->manager_on_duty = $input['manager_on_duty'];
            }
            $warehouseSorters->save();
        }else{
            $recordCreate = [
                'hub_id' => $input['hub_id'],
                'date' => $input['date'],
                'brooker_sorter_count' => $input['brooker_sorter_count'],
                'internal_sorter_count' => $input['internal_sorter_count'],

            ];
            if($input['manager_on_duty']!=''){
                $recordCreate['manager_on_duty']=$input['manager_on_duty'];
            }
            $warehouseSorters=WarehouseJoeysCount::create($recordCreate);

        }
        $return=[
            'brooker_sorter' => $input['brooker_sorter_count'],
            'internal_sorter' => $input['internal_sorter_count'],
            'manager'=>($warehouseSorters->Manager!=null) ? $warehouseSorters->Manager->name : ""
        ];
        return RestAPI::response($return, true, 'InBound warehouse sorter updated successfully');



    }
}
