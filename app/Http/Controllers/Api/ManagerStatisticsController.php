<?php

namespace App\Http\Controllers\Api;

use App\Classes\RestAPI;
use App\Http\Resources\ManagerAllCountResource;
use App\Http\Resources\ManagerCustomOrdersCountResource;
use App\Http\Resources\ManagerFailedOrdersCountResource;
use App\Http\Resources\ManagerOtdResource;
use App\Http\Resources\ManagerRouteOrdersCountResource;
use App\Http\Traits\BasicModelFunctions;
use App\Models\AmazonEnteries;
use App\Models\CTCEntry;
use App\Models\JoeyRouteLocation;
use App\Models\JoeyRoutes;
use App\Models\ManagerAmazonEntriesViewData;
use App\Models\ManagerCtcEntriesViewData;
use App\Models\ManagerCtcVendor;
use App\Models\ManagerFinanceVendorCityDetail;
use App\Models\MerchantsIds;
use App\Models\Sprint;
use App\Models\SprintTaskHistory;
use Carbon\Carbon;
use DateTime;
use DateTimeZone;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ManagerStatisticsController extends ApiBaseController
{
    use BasicModelFunctions;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * for Get OTD
     *
     */
    public function getOtd(Request $request)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'hub_id' => 'required|exists:finance_vendor_city_relations,id',
            'type' => 'required',
        ]);
        $data = $request->all();

        //$date = $data['date'];
        //$hub_id = $data['hub_id'];
        $date = $data['date'] ? $data['date'] : date('Y-m-d');
        $hub_id = isset($data['hub_id']) ? $data['hub_id'] : 4;
        $type = $data['type'];
        DB::beginTransaction();
        try {
            $vendors = ManagerFinanceVendorCityDetail::where('vendor_city_realtions_id', $hub_id)->pluck('vendors_id')->toArray();
            $ctcVendorIds = ManagerCtcVendor::pluck('vendor_id')->toArray();
            $sprint = new Sprint();

            $totalcount = 0;
            $totallates = 0;

            $all_dates = [];

            if ($type == 'day')
            {
                $range_from_date = new Carbon(date('Y-m-d',strtotime($date)));
            }
            elseif ($type == 'week')
            {
                $range_from_date = new Carbon(date('Y-m-d', strtotime('-6 day', strtotime($date))));
            }
            elseif ($type == 'month')
            {
                $range_from_date = new Carbon(date('Y-m-d', strtotime('-1 month', strtotime($date))));
            }
            elseif ($type == 'six_month')
            {
                $range_from_date = new Carbon(date('Y-m-d', strtotime('-6 month', strtotime($date))));
            }
            $range_to_date = new Carbon($date);
            //print_r($range_to_date);
            while ($range_from_date->lte($range_to_date)) {

                $all_dates[] = $range_from_date->toDateString();
                $range_from_date->addDay();
            }

            foreach ($all_dates as $range_date) {

                if (in_array('477260', $vendors)) {
                    $amazon_date = date('Y-m-d', strtotime($range_date . ' -1 days'));


                    $start_dt = new DateTime($amazon_date." 00:00:00", new DateTimezone('America/Toronto'));
                    $start_dt->setTimeZone(new DateTimezone('UTC'));
                    $start = $start_dt->format('Y-m-d H:i:s');

                    $end_dt = new DateTime($amazon_date." 23:59:59", new DateTimezone('America/Toronto'));
                    $end_dt->setTimeZone(new DateTimezone('UTC'));
                    $end = $end_dt->format('Y-m-d H:i:s');

                    $query = AmazonEnteries::where('creator_id', '477260')->where('created_at','>',$start)->where('created_at','<',$end)
                        ->where('is_custom_route', 0)->get(['task_status_id', \DB::raw("CONVERT_TZ(delivered_at,'UTC','America/Toronto') as delivered_at"),
                            \DB::raw("CONVERT_TZ(returned_at,'UTC','America/Toronto') as returned_at")]);
                    if (!empty($query)) {
                        foreach ($query as $record) {
                            if (in_array($record->task_status_id,$this->getStatusCodes('competed'))) {
                                if (!is_null($record->delivered_at) && $record->delivered_at > $range_date . " 21:00:00") {
                                    $totallates++;
                                }
                            }
                            if (in_array($record->task_status_id,$this->getStatusCodes('return'))) {
                                if (!is_null($record->returned_at)  && $record->returned_at > $range_date . " 21:00:00") {
                                    $totallates++;
                                }
                            }
                            $totalcount++;
                        }
                    }
                }

                if (in_array('477282', $vendors)) {
                    $amazon_date  = date('Y-m-d', strtotime($range_date . ' -1 days'));

                    $start_dt = new DateTime($amazon_date." 00:00:00", new DateTimezone('America/Toronto'));
                    $start_dt->setTimeZone(new DateTimezone('UTC'));
                    $start = $start_dt->format('Y-m-d H:i:s');

                    $end_dt = new DateTime($amazon_date." 23:59:59", new DateTimezone('America/Toronto'));
                    $end_dt->setTimeZone(new DateTimezone('UTC'));
                    $end = $end_dt->format('Y-m-d H:i:s');


                    $query = AmazonEnteries::where('creator_id', '477282')->where('created_at','>',$start)->where('created_at','<',$end)
                        ->where('is_custom_route', 0)->get(['task_status_id', \DB::raw("CONVERT_TZ(delivered_at,'UTC','America/Toronto') as delivered_at"),
                            \DB::raw("CONVERT_TZ(returned_at,'UTC','America/Toronto') as returned_at")]);
                    if (!empty($query)) {
                        foreach ($query as $record) {
                            if (in_array($record->task_status_id,$this->getStatusCodes('competed'))) {
                                if (!is_null($record->delivered_at) && $record->delivered_at > $range_date . " 21:00:00") {
                                    $totallates++;
                                }
                            }
                            if (in_array($record->task_status_id,$this->getStatusCodes('return'))) {
                                if (!is_null($record->returned_at) && $record->returned_at > $range_date . " 21:00:00") {
                                    $totallates++;
                                }
                            }
                            $totalcount++;
                        }
                    }
                }

                if (count(array_intersect($ctcVendorIds, $vendors))> 0) {
                    $ctc_ids = array_intersect($ctcVendorIds, $vendors);
                    $ctc_date  = date('Y-m-d', strtotime($range_date . ' -1 days'));

                    $start_dt = new DateTime($ctc_date." 00:00:00", new DateTimezone('America/Toronto'));
                    $start_dt->setTimeZone(new DateTimezone('UTC'));
                    $start = $start_dt->format('Y-m-d H:i:s');

                    $end_dt = new DateTime($ctc_date." 23:59:59", new DateTimezone('America/Toronto'));
                    $end_dt->setTimeZone(new DateTimezone('UTC'));
                    $end = $end_dt->format('Y-m-d H:i:s');


                    $sprint_id = SprintTaskHistory::where('created_at','>',$start)->where('created_at','<',$end)->where('status_id', 125)->pluck('sprint_id');
                    $query = CTCEntry::whereIn('creator_id', $ctc_ids)->whereIn('sprint_id', $sprint_id)
                        ->where('is_custom_route', 0)->get(['task_status_id', \DB::raw("CONVERT_TZ(delivered_at,'UTC','America/Toronto') as delivered_at"),
                            \DB::raw("CONVERT_TZ(returned_at,'UTC','America/Toronto') as returned_at")]);
                    if (!empty($query)) {
                        foreach ($query as $record) {
                            if (in_array($record->task_status_id,$this->getStatusCodes('competed'))) {
                                if (!is_null($record->delivered_at) && $record->delivered_at > $range_date . " 21:00:00") {
                                    $totallates++;
                                }
                            }
                            if (in_array($record->task_status_id,$this->getStatusCodes('return'))) {
                                if (!is_null($record->returned_at) && $record->returned_at > $range_date . " 21:00:00") {
                                    $totallates++;
                                }
                            }
                            $totalcount++;
                        }
                    }
                }
            }

            if ($totalcount == 0)
            {
                $odt_data_1 = ['y1' => 0, 'y2' => 0 ,'ontime'=>  $totalcount - $totallates  , 'offtime'=> $totallates ];
            }
            elseif ($totalcount != 0)
            {
                $odt_data_1 = ['y1' => round(($totallates / $totalcount) * 100, 2), 'y2' => round(100 - (($totallates / $totalcount) * 100), 2) ,'ontime'=>  $totalcount - $totallates  , 'offtime'=> $totallates ];
            }







            $response = new ManagerOtdResource($odt_data_1,$type);


            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'OTD Report');
    }

    /**
     * for Get All Counts
     *
     */
    public function allCounts(Request $request)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'hub_id' => 'required|exists:finance_vendor_city_relations,id',
        ]);
        $data = $request->all();
        $date = $data['date'] ? $data['date'] : date('Y-m-d');
        $hub_id = isset($data['hub_id']) ? $data['hub_id'] : 4;

        DB::beginTransaction();
        try {

            $vendors = ManagerFinanceVendorCityDetail::where('vendor_city_realtions_id', $hub_id)->pluck('vendors_id')->toArray();

            $ctcVendorIds = ManagerCtcVendor::pluck('vendor_id')->toArray();

            $montreal_count = [
                'total' => 0,
                'sorted' => 0,
                'pickup' => 0,
                'delivered_order' => 0,
                'return_orders' => 0,
                'hub_return_scan' => 0,
                'notscan' => 0,
            ];
            $ottawa_count = [
                'total' => 0,
                'sorted' => 0,
                'pickup' => 0,
                'delivered_order' => 0,
                'return_orders' => 0,
                'hub_return_scan' => 0,
                'notscan' => 0,
            ];
            $ctc_count = [
                'total' => 0,
                'sorted' => 0,
                'pickup' => 0,
                'delivered_order' => 0,
                'return_orders' => 0,
                'hub_return_scan' => 0,
                'notscan' => 0,
            ];

            if (in_array('477260', $vendors)) {

                $amazon_date = date('Y-m-d', strtotime($date . ' -1 days'));
                $rec = $this->timeConvert($amazon_date);
                $taskIds = \DB::table('amazon_enteries')->where('created_at','>',$rec['start'])->where('created_at','<',$rec['end'])
                    ->where(['creator_id' => 477260])->where('is_custom_route', 0)->pluck('task_id');
                $amazon = new AmazonEnteries();
                $montreal_count = $amazon->getAmazonCountsForLoop($taskIds, 'all');
            }

            if (in_array('477282', $vendors)) {
                $amazon_date  = date('Y-m-d', strtotime($date . ' -1 days'));
                $rec = $this->timeConvert($amazon_date);
                $taskIds = \DB::table('amazon_enteries')->where('created_at','>',$rec['start'])->where('created_at','<',$rec['end'])
                    ->where(['creator_id' => 477282])->where('is_custom_route', 0)->pluck('task_id');
                $amazon = new AmazonEnteries();
                $ottawa_count = $amazon->getAmazonCountsForLoop($taskIds, 'all');
            }

            if (count(array_intersect($ctcVendorIds, $vendors))> 0) {
                $ctc_ids = array_intersect($ctcVendorIds, $vendors);
                $rec = $this->timeConvert($date);
                $taskIds = \DB::table('ctc_entries')->where('is_custom_route', 0)->whereIn('creator_id',$ctc_ids)->where('created_at','>',$rec['start'])->where('created_at','<',$rec['end'])->pluck('task_id');
                $ctc = new CTCEntry();
                $ctc_count = $ctc->getCtcCounts($taskIds, 'all');
            }
            $counts['total'] = $montreal_count['total']+$ottawa_count['total']+$ctc_count['total'];
            $counts['sorted'] = $montreal_count['sorted']+$ottawa_count['sorted']+$ctc_count['sorted'];
            $counts['pickup'] = $montreal_count['pickup']+$ottawa_count['pickup']+$ctc_count['pickup'];
            $counts['delivered_order'] = $montreal_count['delivered_order']+$ottawa_count['delivered_order']+$ctc_count['delivered_order'];
            $counts['return_orders'] = $montreal_count['return_orders']+$ottawa_count['return_orders']+$ctc_count['return_orders'];
            $counts['hub_return_scan'] = $montreal_count['hub_return_scan']+$ottawa_count['hub_return_scan']+$ctc_count['hub_return_scan'];
            $counts['hub_not_return_scan'] = $counts['return_orders']-$counts['hub_return_scan'];
            $counts['notscan'] = $montreal_count['notscan']+$ottawa_count['notscan']+$ctc_count['notscan'];



            $response = new ManagerAllCountResource($counts);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'All Order Counts');
    }

    /**
     * for Get Failed Counts
     *
     */
    public function failedOrderCounts(Request $request)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'hub_id' => 'required|exists:finance_vendor_city_relations,id',
        ]);
        $data = $request->all();
        $date = $data['date'] ? $data['date'] : date('Y-m-d');
        $hub_id = isset($data['hub_id']) ? $data['hub_id'] : 4;

        DB::beginTransaction();
        try {

            $vendors = ManagerFinanceVendorCityDetail::where('vendor_city_realtions_id', $hub_id)->pluck('vendors_id')->toArray();
            $ctcVendorIds = ManagerCtcVendor::pluck('vendor_id')->toArray();
            $failed_order = 0;
            $system_failed_order = 0;
            $not_in_system_failed_order = 0;

            if (in_array('477260', $vendors)) {
                $amazon_date = date('Y-m-d', strtotime($date . ' -1 days'));
                $rec = $this->timeConvert($amazon_date);
                $failed_tracking_Ids = DB::table('xml_failed_orders')->join('mainfest_fields', 'mainfest_fields.trackingID', '=', 'xml_failed_orders.tracking_id')
                    ->where('xml_failed_orders.created_at','>',$rec['start'])->where('xml_failed_orders.created_at','<',$rec['end'])->whereNotNull('mainfest_fields.trackingID')
                    ->whereNull('mainfest_fields.deleted_at')->where(['vendor_id' => 477260])->pluck('tracking_id')->toArray();

                $merchnatTracking = MerchantsIds::whereIn('tracking_id', $failed_tracking_Ids)->pluck('tracking_id')->toArray();
                $failed_order = $failed_order + count($failed_tracking_Ids);
                $system_failed_order = $system_failed_order + count($merchnatTracking);
                $not_in_system_failed_order = $not_in_system_failed_order + count(array_diff($failed_tracking_Ids, $merchnatTracking));
            }

            if (in_array('477282', $vendors)) {
                $amazon_date = date('Y-m-d', strtotime($date . ' -1 days'));
                $rec = $this->timeConvert($amazon_date);
                $failed_tracking_Ids = DB::table('xml_failed_orders')->join('mainfest_fields', 'mainfest_fields.trackingID', '=', 'xml_failed_orders.tracking_id')
                    ->where('xml_failed_orders.created_at','>',$rec['start'])->where('xml_failed_orders.created_at','<',$rec['end'])->whereNotNull('mainfest_fields.trackingID')
                    ->whereNull('mainfest_fields.deleted_at')->where(['vendor_id' => 477282])->pluck('tracking_id')->toArray();
                $merchnatTracking = MerchantsIds::whereIn('tracking_id',$failed_tracking_Ids)->pluck('tracking_id')->toArray();
                $failed_order = $failed_order + count($failed_tracking_Ids);
                $system_failed_order = $system_failed_order + count($merchnatTracking);
                $not_in_system_failed_order = $not_in_system_failed_order + count(array_diff($failed_tracking_Ids, $merchnatTracking));
            }

            if (count(array_intersect($ctcVendorIds, $vendors)) > 0) {
                $ctc_ids = array_intersect($ctcVendorIds, $vendors);
                $rec = $this->timeConvert($date);
                $failed_tracking_Ids = DB::table('ctc_failed_orders')->whereIn('vendor_id' ,$ctc_ids)
                    ->where('ctc_failed_orders.created_at','>',$rec['start'])->where('ctc_failed_orders.created_at','<',$rec['end'])->pluck('tracking_num')->toArray();
                $merchnatTracking = MerchantsIds::whereIn('tracking_id',$failed_tracking_Ids)->pluck('tracking_id')->toArray();
                $failed_order = $failed_order + count($failed_tracking_Ids);
                $system_failed_order = $system_failed_order + count($merchnatTracking);

                $not_in_system_failed_order = $not_in_system_failed_order + count(array_diff($failed_tracking_Ids, $merchnatTracking));

            }

            $counts['failed'] = $failed_order;
            $counts['system_failed_order'] = $system_failed_order;
            $counts['not_in_system_failed_order'] = $not_in_system_failed_order;



            $response = new ManagerFailedOrdersCountResource($counts);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'Failed Order Counts');
    }

    /**
     * for Custom Order Counts
     *
     */
    public function customOrderCounts(Request $request)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'hub_id' => 'required|exists:finance_vendor_city_relations,id',
        ]);
        $data = $request->all();
        $date = $data['date'] ? $data['date'] : date('Y-m-d');
        $hub_id = isset($data['hub_id']) ? $data['hub_id'] : 4;

        DB::beginTransaction();
        try {

            $vendors = ManagerFinanceVendorCityDetail::where('vendor_city_realtions_id', $hub_id)->pluck('vendors_id')->toArray();
            $ctcVendorIds = ManagerCtcVendor::pluck('vendor_id')->toArray();

            $custom_order = 0;

            if (in_array('477260', $vendors)) {
                $rec = $this->timeConvert($date);
                $custom_order = $custom_order + DB::table('amazon_enteries')->where('created_at','>',$rec['start'])->where('created_at','<',$rec['end'])
                        ->where(['creator_id' => 477260])->where('is_custom_route', 1)->count();
            }
            if (in_array('477282', $vendors)) {
                $rec = $this->timeConvert($date);
                $custom_order = $custom_order + DB::table('amazon_enteries')->where('created_at','>',$rec['start'])->where('created_at','<',$rec['end'])
                        ->where(['creator_id' => 477282])->where('is_custom_route', 1)->count();
            }

            if (count(array_intersect($ctcVendorIds, $vendors)) > 0) {
                $rec = $this->timeConvert($date);
                $ctc_ids = array_intersect($ctcVendorIds, $vendors);
                $custom_order = $custom_order + DB::table('ctc_entries')->whereIn('creator_id',$ctc_ids)->where('created_at','>',$rec['start'])->where('created_at','<',$rec['end'])->where('is_custom_route', 1)->count();

            }
            $counts['custom_order'] = $custom_order;

            $response = new ManagerCustomOrdersCountResource($counts);


            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'Total Route Order Count');
    }

    /**
     * for Route Order Counts
     *
     */
    public function routeOrderCounts(Request $request)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'hub_id' => 'required|exists:finance_vendor_city_relations,id',
        ]);
        $data = $request->all();
        $date = $data['date'] ? $data['date'] : date('Y-m-d');
        $hub_id = isset($data['hub_id']) ? $data['hub_id'] : 4;

        DB::beginTransaction();
        try {

            $vendors = ManagerFinanceVendorCityDetail::where('vendor_city_realtions_id', $hub_id)->pluck('vendors_id')->toArray();
            $ctcVendorIds = ManagerCtcVendor::pluck('vendor_id')->toArray();

            $routeIds = [];
            $ottawa_routeIds = [];
            $montreal_routeIds = [];
            $ctc_routeIds = [];
            $total_route = 0;
            $normal_route = 0;
            $custom_route = 0;
            $big_box_route = 0;

            if (in_array('477260', $vendors)) {
                $amazon_date = date('Y-m-d', strtotime($date . ' -1 days'));
                $montreal_routeIds = JoeyRoutes::where('hub',16)->where('date', 'like', $date . "%")->pluck('id')->toArray();
            }
            if (in_array('477282', $vendors)) {
                $amazon_date = date('Y-m-d', strtotime($date . ' -1 days'));
                $ottawa_routeIds = JoeyRoutes::where('hub',19)->where('date', 'like', $date . "%")->pluck('id')->toArray();
            }

            if (count(array_intersect($ctcVendorIds, $vendors)) > 0) {
                $ctc_ids = array_intersect($ctcVendorIds, $vendors);
                $ctc_routeIds = JoeyRoutes::where('hub',17)->where('date', 'like', $date . "%")->pluck('id')->toArray();
            }
            $routeIds = array_merge($montreal_routeIds, $ottawa_routeIds, $ctc_routeIds);

            $route_data = JoeyRoutes::whereIn('id', $routeIds)->where('date', 'like', $date . "%")->whereNull('deleted_at')->get();
            foreach ($route_data as $route) {
                $route_location_check = JoeyRouteLocation::where('route_id', $route->id)->whereNull('deleted_at')->first();
                if ($route_location_check) {
                    if ($route->zone != null) {
                        $is_custom_check = \DB::table("zones_routing")->where('id', $route->zone)->whereNull('is_custom_routing')->first();
                        if ($is_custom_check) {
                            $normal_route++;
                        } else {
                            $route_location = JoeyRouteLocation::where('route_id', $route->id)->first();
                            if ($route_location) {
                                $tracking = MerchantsIds::where('task_id', $route_location->task_id)->first();
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
            $counts['total_route'] = $total_route;
            $counts['normal_route'] = $normal_route;
            $counts['custom_route'] = $custom_route;
            $counts['big_box_route'] = $big_box_route;



            $response = new ManagerRouteOrdersCountResource($counts);


            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'Custom Order Counts');
    }
}
