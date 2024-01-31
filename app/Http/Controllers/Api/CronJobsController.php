<?php

namespace App\Http\Controllers\Api;

use App\Classes\ECommercePayoutCalculationByRoute;
use App\Classes\RestAPI;
use App\Models\JoeyRouteLocation;
use App\Models\JoeyRoutes;
use App\Models\RouteHistory;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Validator;
use App\Models\ReturnAndReattemptProcessHistory;
use App\Http\Requests\Api\MarkedRouteCompleteCronRequest;


class CronJobsController extends ApiBaseController
{


    private $MerchantOrderCsvUploadRepository;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Update status of return portal scan orders on routing and dashboard
     *
     */

    public function update_status_return_portal_scan_orders()
    {
        $before_three_days_date = date('Y-m-d 23:59:59', strtotime(' -3 day'));
        $data_for_update = ReturnAndReattemptProcessHistory::where('created_at','<=',$before_three_days_date)
            ->where('is_expired_updated',0)
            ->where('process_type', '=', 'customer_support')
            ->whereNull('verified_by')
            ->whereNull('deleted_at')
            ->update(['is_expired_updated'=>1]);

        return RestAPI::response([], true, "Scan orders status update successfully ");

    }

    /**
     * Marked route completed cron job
    */

    public function markCompleteRouteCronJob(MarkedRouteCompleteCronRequest $request , ECommercePayoutCalculationByRoute $ECommercePayoutCalculationByRoute)
    {


        // creating default variable
        $current_date = date('Y-m-d');
        $current_date_time = date('Y-m-d H:i:s');
        $except_routes = explode(',',$request->except);

        $start_date = (is_null($request->date)) ? $current_date.' 00:00:00' : $request->date.' 00:00:00';
        $end_date = (is_null($request->date)) ? $current_date.' 23:59:59' : $request->date.' 23:59:59';

        $completed_status_list = getStatusCodes('competed');
        $return_status_list = getStatusCodes('return');
        $complete_and_return_status = array_merge($completed_status_list,$return_status_list);
        $route_history_completed_and_return_orders_count = 0;
        $sprint_task_completed_and_return_orders_count = 0;
        $joey_route_locations_total_tasks_count = 0;
        $route_history_completed_and_return_task_ids = [];
        $missing_ids_data = [];
        $processed_routes = [];
        $unprocessed_routes = [];
        $metaData = [];
        $test = [];
        DB::beginTransaction();

        try {

            // getting routes which are not completed

            $routes = JoeyRoutes::where('payout_generated', 0)
                //->where('id',125226)
                ->whereNotIn('id',$except_routes)
                ->whereNotNull('joey_id')
                ->whereNull('deleted_at')
                ->whereHas('RouteHistory', function ($query) {
                    $query->whereHas('JoeyRouteLocation')
                        ->whereNull('deleted_at');
                });
            // add date filter if used
            if (!is_null($request->date)) {
                $routes->whereBetween('date', [$start_date, $end_date]);
            }

            // getting data for process
            $routes_data = $routes->orderBy('id', 'DESC')
                ->paginate($request->limit);


            // looping on every route for checking the route is completed or not and also make an sprint completed if it is missing from route history
            foreach ($routes_data as $route) {

                array_push($test, $route->id);
                //$brooker = (count($route->joey->Brooker) > 0 )  ? $route->joey->Brooker->first() : null ;
                //dd($route->joey->Brooker->isEmpty());


                // getting current route history completed and return tasks ids
                $route_history_completed_and_return_task_ids = RouteHistory::where('route_id', $route->id)
                    ->whereHas('JoeyRouteLocation', function ($query) {
                        $query->whereNull('deleted_at');
                    })
                    ->whereIn('status', [2, 4])
                    ->pluck('task_id')
                    ->toArray();

                // getting route history completed and return task count
                $route_history_completed_and_return_orders_count = count($route_history_completed_and_return_task_ids);


                // now getting route location completed and return tasks
                $joey_route_locations_data_with_completed_and_return = JoeyRouteLocation::where('route_id', $route->id)
                    ->whereHas('sprintTaskAgainstRouteLocationId', function ($query) use ($complete_and_return_status) {
                        $query->where('type', 'dropoff')
                            ->whereIn('status_id', $complete_and_return_status)
                            ->orderBy('id', 'DESC');
                    })
                    ->whereNull('deleted_at')
                    ->get();

                // getting route task completed and return count
                $sprint_task_completed_and_return_orders_count = count($joey_route_locations_data_with_completed_and_return);

                // now getting route locations total task count
                $joey_route_locations_total_tasks_count = JoeyRouteLocation::where('route_id', $route->id)
                    ->whereNull('deleted_at')
                    ->count();


                // now checking there is any missing ids on route history
                if ($sprint_task_completed_and_return_orders_count > $route_history_completed_and_return_orders_count)
                {
                    // getting missing orders data
                    $missing_ids_data = $joey_route_locations_data_with_completed_and_return->whereNotIn('task_id', $route_history_completed_and_return_task_ids);

                    //
                    $missing_data_insert = [];
                    foreach ($missing_ids_data as $key => $missing_id_data) {
                        // getting sprint task status
                        $sprint_task_status = $missing_id_data->sprintTaskdropOffLatest->status_id;

                        // creating data for completed status
                        $missing_data_insert[$key] = [
                            "route_id" => $route->id,
                            "joey_id" => $route->joey_id,
                            "status" => (in_array($sprint_task_status, $completed_status_list)) ? 2 : 4,
                            "route_location_id" => $missing_id_data->id,
                            "task_id" => $missing_id_data->task_id,
                            "ordinal" => $missing_id_data->ordinal,
                            "created_at" => $current_date_time,
                            "updated_at" => $current_date_time,
                        ];

                        // inserting data
                        RouteHistory::insert($missing_data_insert);

                        // updating count with new inserting
                        $route_history_completed_and_return_orders_count += count($missing_data_insert);

                    }

                }

                // now checking the this route is compeleted or not
                if ($joey_route_locations_total_tasks_count == $sprint_task_completed_and_return_orders_count && $route_history_completed_and_return_orders_count > 0) {

                    // push route id in processed route
                    array_push($processed_routes, $route->id);


                } else {
                    // push route id in unprocessed route
                    array_push($unprocessed_routes, $route->id);
                }

            }

            //calling class for sending request
            $payoutCalculation = $ECommercePayoutCalculationByRoute;

            //sending data for set route ids
            $payoutCalculation->setRequestRoutes($processed_routes);

            //Validate route ids
            $payoutCalculation->validateRequestForCompletion();
            $payoutCalculation->markCompleteRouteAndSaveCalculation();

            // updating meta data
            $metaData['processed_routes'] = $processed_routes;
            $metaData['unprocessed_routes'] = $unprocessed_routes;

            DB::commit();
            return RestAPI::setPagination($routes_data)->response($routes_data,true,'',$metaData);

        }
        catch (\Exception $e)
        {
            DB::rollback();
            //return RestAPI::response([$e,$test], false, $e->getMessage());
            return RestAPI::response($test, false, $e->getMessage());
        }

    }



}
