<?php

namespace App\Classes;

/**
 * Created by Muhammad Adnan Nadeem.
 * Email: adnannadeem1994@gamil.com
 * Date: 6/02/2021
 * Time: 5:38 PM
 */
class JoeyPayoutCalculation
{

    static function calculate($data = [], $extra_query_data = [])
    {

        $return_data = [];
        $system_parameters = $extra_query_data['system_parameters'];
        $query_filters = (isset($extra_query_data['filters']))? $extra_query_data['filters']: [] ;
        $JoeysPlanTypes = $extra_query_data['JoeysPlanTypes'];

        // static work for temp
        $city_names = [
            "montreal" => [477260],
            "ottawa" => [477282, 477340, 477341, 477342, 477342, 477343, 477344, 477345, 477346],
            'toronto' => [477255, 477254, 477283, 477284, 477286, 477287, 477288, 477289, 477307, 477308, 477309, 477310, 477311, 477312, 477313, 477314, 477292, 477294, 477315, 477317, 477316, 477295, 477302, 477303, 477304, 477305, 477306, 477296, 477290, 477297, 477298, 477299, 477300, 477320, 477301, 477318, 477328, 476294, 477334, 477335, 477336, 477337, 477338, 477339],
        ];

        $city_name = '';

        $index = 0;

        // looping up data for calculation
        foreach ($data as $single_data) {

            // initializing variables for loop data which are calling the same function multiple time causing query load

            //setting filter date
            if(isset($query_filters['date'])) {
                $single_data->DateRangeSelected['start_date'] = $extra_query_data['start_date'];
                $single_data->DateRangeSelected['end_date'] = $extra_query_data['end_date'];
            }

            /*block for checking the RouteHistoryLatest have data */
            $FirstPickUpScan = $single_data->JoeyFirstPickUpScan($query_filters);
            $FirstDropScan = $single_data->JoeyFirstDropScan($query_filters);
            $LastDropScan = $single_data->JoeyLastDropScan($query_filters);
            $TotalKM = $single_data->TotalKM();
            $ActualTotalKM = $single_data->JoeyActualTotalKM($query_filters);

            // adding less then sign if its zero
            $ActualTotalKM = ($ActualTotalKM > 0) ? $ActualTotalKM : '< 1';
            $ActualTotalKM_numaric = floatval($ActualTotalKM);

//            if(isset($single_data->JoeyRouteLocationsByRouteID))
//            {
//                dd($single_data->JoeyRouteLocationsByRouteID);
//            }
            $TotalOrderDropsCount = (isset($single_data->JoeyRouteLocationsByRouteID)) ? $single_data->JoeyRouteLocationsByRouteID->count() : 0;
            $TotalOrderDropsCompletedCount = $single_data->JoeyTotalOrderDropsCompletedCount($query_filters);
            $TotalOrderPickedCount = $single_data->JoeyTotalOrderPickedCount($query_filters);
            $TotalOrderReturnCount = $single_data->JoeyTotalOrderReturnCount($query_filters);
            $TotalOrderUnattemptedCount = $single_data->JoeyTotalOrderUnattemptedCount($query_filters);

            // checking if the joey did not attempt any order then remove this route from report
            if ($TotalOrderDropsCompletedCount <= 0 && $TotalOrderReturnCount <= 0 && $TotalOrderPickedCount <= 0) {
                continue;
            }

            $ActualDuration = DifferenceTwoDataTime($FirstDropScan, $LastDropScan);
            $TotalDuration = DifferenceTwoDataTime($FirstPickUpScan, $LastDropScan);
            $routific_duration = (isset($single_data->JoeyRouteLocation)) ? $single_data->JoeyRouteLocation->GetRoutificEstimatedTotalTime() : null ;

            // getting joey plan
            $plan_name = 'Default plan not set';
            $plan_internal_name = 'Not set';
            $brooker = (count($single_data->joey->Brooker) > 0)  ? $single_data->joey->Brooker->first() : null ;
            $brooker_name = (!is_null($brooker)) ? $brooker->name : 'N/A';
//            $is_tax_applied = ($single_data->Joey->is_tax_applied == 0 &&  $brooker_name == 'N/A')? false: true;
            $is_tax_applied = (!is_null($single_data->Joey->hst_number) && $brooker_name == 'N/A') ? true : false;
            $is_tax_applied_string = ($is_tax_applied) ? 'true' : 'false';

            $joey_plan_data = (isset($single_data->joey->plan)) ? $single_data->joey->plan : null;
            $joey_plan_detail_data = (isset($joey_plan_data->PlanDetails)) ? $joey_plan_data->PlanDetails : null;

            //getting default plan type
            $joey_default_plan_data = (isset($extra_query_data['default_plan'])) ? $extra_query_data['default_plan'] : null;
            $joey_default_plan_detail_data = (isset($joey_default_plan_data->PlanDetails)) ? $joey_default_plan_data->PlanDetails : null;
            //dd([$joey_default_plan_data->PlanDetailNames,$joey_default_plan_detail_data]);
            /*calculation for payout report*/
            $tech_amount = $system_parameters['tech']->value * $TotalOrderDropsCompletedCount;
            $gas_amount = 0;
            $truck_amount = 0;
            $hourly = 0;
            $plan_estimated_total_time = '00:00:00';
            $total_cost = 0;
            $flag_bonus = 0;
            $flag_deduction = 0;
            $plan_applied_amount = 0;
            $menual_adjustment = 0;
            $payout_without_tax = 0;
            $tax_percentage = 0;
            $tax_amount = 0;
            $tax_on = 'JoeyCo';
            $final_payout = 0;
            $payout_with_actual_duraion = 0;
            $payout_with_total_duraion = 0;
            $cost_per_drop_on_hourly = 0;
            $payout_calculations = "";


            // checking the route type
            $zone_id = $single_data->JoeyRoute->zone;
            $zone_routing_data = $single_data->ZoneRouting;
            $is_custom_route = 'false';
            $route_type = 'normal_route';
            $zone_name = (isset($zone_routing_data->title)) ? $zone_routing_data->title : 'Not set';

            if ($zone_routing_data != null) {
                // now checking this route type
                if ($zone_routing_data->is_custom_routing == 1) {
                    $is_custom_route = 'true';
                    $route_type = 'custom_route';
                }

            } elseif (is_null($zone_id)) {
                $is_custom_route = 'true';
                $route_type = 'custom_route';
            }


            // checking this route use big box
            if ($single_data->IsthisRouteUseBigBox()) {
                $route_type = 'big_box_route';
            }

            // checking the type exist and plan details
            if ($joey_plan_data != null && $joey_plan_detail_data != null && count($joey_plan_detail_data) > 0) {
                // static setting for plan type by area sub or downtown
                $planTypeSelection = [
                    "normal_route" => [1 => 0, 2 => 1],
                    "custom_route" => [1 => 2, 2 => 3],
                    "big_box_route" => [1 => 4, 2 => 5],
                ];


                // setting plan name
                $plan_name = $joey_plan_data->PlanDetailNames;

                $plan_internal_name = $joey_plan_data->internal_name;

                // checking the joey useing company vehicle
                if ($joey_plan_data->vehicle_using_type == 1) {

                    $gas_amount = $system_parameters['gas']->value * $ActualTotalKM_numaric;
                }

                if (in_array($joey_plan_data->plan_type, $JoeysPlanTypes['per_drop_plan'])) {

                    // selecting plan according to route type
                    $selected_joey_plan_detail = null;
                    if ($route_type == 'custom_route') // this block use for select custom route rates
                    {
                        $selected_joey_plan_detail = $joey_plan_detail_data[1];
                    } elseif ($route_type == 'big_box_route') // block for big box
                    {
                        $selected_joey_plan_detail = $joey_plan_detail_data[2];
                    } else {
                        $selected_joey_plan_detail = $joey_plan_detail_data[0];
                    }

//                    $total_cost = ($is_tax_applied) ? $selected_joey_plan_detail->total_cost : $selected_joey_plan_detail->sub_joey_charges;
                    $total_cost = $selected_joey_plan_detail->sub_joey_charges;
                    $tax_percentage = $selected_joey_plan_detail->tax;
                    $total_cost = $total_cost / $selected_joey_plan_detail->drops;
                    $plan_applied_amount = $total_cost . ' Per drop';
                    $payout_without_tax = ($total_cost * $TotalOrderDropsCompletedCount);
                    $payout_without_tax = round($payout_without_tax, 3);
                    $truck_amount = $selected_joey_plan_detail->gas_truck_amount;
                    $hourly = round(HoursToMinutes($ActualDuration) / 60, 2);
                    $payout_calculations = 'per drop cost  [' . $total_cost . '] x total compeleted drops[' . $TotalOrderDropsCompletedCount . ']';
                    $payout_calculations .= '<br>  Route type = ' . $route_type;
                    $payout_calculations .= '<br>  is applying tax = ' . $is_tax_applied_string;

                } elseif (in_array($joey_plan_data->plan_type, $JoeysPlanTypes['by_duration'])) // calculation for by duration plan
                {
                    // checking the plan type is set for any error occurred fallback on sub plan
//                    $selected_joey_plan_detail  = $joey_plan_detail_data[0];
//                    $zone_not_found_massage = 'Zone id not found applying sub plan ';
//                    if(isset($zone_routing_data->zone_type) && isset($planTypeSelection['normal_route'][$zone_routing_data->zone_type]))
//                    {
//                        $selected_joey_plan_detail  =  $joey_plan_detail_data[$planTypeSelection['normal_route'][$single_data->ZoneRouting->zone_type]];
//                        $zone_not_found_massage = '';
//                    }


                    // sorting plan data
                    $joey_plan_detail_data = $joey_plan_detail_data->sortBy('sorting_order');

                    $zone_not_found_massage = 'Zone id not found applying sub plan ';
                    if (isset($zone_routing_data->zone_type) && isset($planTypeSelection[$route_type][$zone_routing_data->zone_type])) {
                        $selected_joey_plan_detail = $joey_plan_detail_data[$planTypeSelection[$route_type][$single_data->ZoneRouting->zone_type]];
                        $zone_not_found_massage = '';
                    } else {
                        $selected_joey_plan_detail = $joey_plan_detail_data[$planTypeSelection[$route_type][1]];
                    }
                    // checking the plan type and according to hourly or per drop
                    if ($selected_joey_plan_detail->plane_type == 'sub_hourly' || $selected_joey_plan_detail->plane_type == 'downtown_hourly') // hourly calculation block
                    {
                        $plan_estimated_total_time = $selected_joey_plan_detail->total_hours;

                        // calculation by per drop time window define plan
                        //$database_total_cost = ($is_tax_applied) ? $selected_joey_plan_detail->total_cost : $selected_joey_plan_detail->sub_joey_charges;
                        $database_total_cost = $selected_joey_plan_detail->sub_joey_charges;
                        $tax_percentage = $selected_joey_plan_detail->tax;
                        $database_total_hours = $selected_joey_plan_detail->total_hours;
                        $plan_hours_work_in_seconds = TimeStringToSeconds($database_total_hours);
                        $rate_in_seconds = $database_total_cost / $plan_hours_work_in_seconds;
                        $plan_pre_drop_estimated_time = $plan_hours_work_in_seconds / $selected_joey_plan_detail->drops;
                        $current_working_time_in_seconds_by_plan_estimation = $TotalOrderDropsCompletedCount * $plan_pre_drop_estimated_time;
                        $total_cost = $rate_in_seconds * $current_working_time_in_seconds_by_plan_estimation;
                        $plan_applied_amount = 'per drop time ' . gmdate("H:i:s", $plan_pre_drop_estimated_time) . ' pre drop cost by pre drop time ' . $rate_in_seconds * $plan_pre_drop_estimated_time;

                        $payout_without_tax = round($total_cost, 3);
                        $hourly = round(ConvertSecondsToHours($current_working_time_in_seconds_by_plan_estimation), 2);
                        $truck_amount = $selected_joey_plan_detail->gas_truck_amount;
                        // calculating payout on total duration
                        $payout_with_total_duraion = round($rate_in_seconds * TimeStringToSeconds($TotalDuration), 2);
                        $payout_with_actual_duraion = $payout_without_tax;

                        $payout_calculations = [
                            'Plan hourly rate = ' . $selected_joey_plan_detail->hourly_rate . '<br>',
                            'Plan per drop estimated time with buffer in seconds = ' . $plan_pre_drop_estimated_time . '<br>',
                            'Applied rate pre second = ' . $rate_in_seconds . '<br>',
                            'Plan tax percentage = ' . $selected_joey_plan_detail->tax . ' % <br>',
                            'Plan total cost = ' . $selected_joey_plan_detail->total_cost . '<br>',
                            'Plan total hours = ' . $database_total_hours . '<br>',
                            'Working total hours by joey = ' . $hourly . '<br>',
                            'is applying tax =' . $is_tax_applied_string . '<br>',
                            'Working hours in seconds by joey estimated by plan per drop  = (' . $current_working_time_in_seconds_by_plan_estimation . ' X  rate in seconds by plane = ' . $rate_in_seconds . ' ) =  [' . $payout_without_tax . '] <br>',
                            'Area Type   = [' . $selected_joey_plan_detail->plane_type . '] <br>',
                            'Error message  =' . $zone_not_found_massage,
                        ];


                    } else //block for per drop
                    {
                        //$total_cost = ($is_tax_applied) ? $selected_joey_plan_detail->total_cost : $selected_joey_plan_detail->sub_joey_charges;
                        $total_cost = $selected_joey_plan_detail->sub_joey_charges;
                        $tax_percentage = $selected_joey_plan_detail->tax;
                        $total_cost = $total_cost / $selected_joey_plan_detail->drops;
                        $plan_applied_amount = $total_cost . ' Per drop';
                        $payout_without_tax = ($total_cost * $TotalOrderDropsCompletedCount);
                        $payout_without_tax = round($payout_without_tax, 3);
                        $truck_amount = $selected_joey_plan_detail->gas_truck_amount;
                        $hourly = round(HoursToMinutes($ActualDuration) / 60, 2);

                        $payout_calculations = [
                            'per drop cost[' . $total_cost . '] x total compeleted drops[' . $TotalOrderDropsCompletedCount . '] <br>',
                            'Route type = ' . $route_type . '<br>',
                            'is applying tax = ' . $is_tax_applied_string,
                            'Area Type   = [' . $selected_joey_plan_detail->plane_type . '] <br>',
                            'Error message  =' . $zone_not_found_massage,
                        ];

                    }

                    $payout_calculations = implode('', $payout_calculations);

                } elseif (in_array($joey_plan_data->plan_type, $JoeysPlanTypes['by_area_per_drop'])) // calculation for by duration plan
                {

                    // checking the plan type is set for any error occurred fallback on sub plan
                    // checking zone id if its exist then applied normal rates if it is not then applied custom route rate
                    $zone_not_found_massage = 'Zone not found applying sub plan ';
                    if (isset($zone_routing_data->zone_type) && isset($joey_plan_detail_data[$planTypeSelection[$route_type][$zone_routing_data->zone_type]])) {

                        $selected_joey_plan_detail = $joey_plan_detail_data[$planTypeSelection[$route_type][$zone_routing_data->zone_type]];
                        $zone_not_found_massage = '';
                    } else {

                        $selected_joey_plan_detail = $joey_plan_detail_data[$planTypeSelection[$route_type][1]];
                    }

                    // getting data base charges
                    //$database_total_cost = ($is_tax_applied) ? $selected_joey_plan_detail->total_cost : $selected_joey_plan_detail->sub_joey_charges;
                    $database_total_cost = $selected_joey_plan_detail->sub_joey_charges;
                    $tax_percentage = $selected_joey_plan_detail->tax;
                    $total_cost = $database_total_cost / $selected_joey_plan_detail->drops;
                    $plan_applied_amount = $total_cost . ' Per drop';
                    $payout_without_tax = ($total_cost * $TotalOrderDropsCompletedCount);
                    $payout_without_tax = round($payout_without_tax, 2);
                    $truck_amount = $selected_joey_plan_detail->gas_truck_amount;
                    $hourly = round(HoursToMinutes($ActualDuration) / 60, 2);

                    $payout_calculations = [
                        'Plan cost without tax= ' . $selected_joey_plan_detail->sub_joey_charges . '<br>',
                        'Plan tax= ' . $selected_joey_plan_detail->tax . ' % <br>',
                        'Plan total cost with tax= ' . $selected_joey_plan_detail->total_cost . '<br>',
                        'Plan No Drops = ' . $selected_joey_plan_detail->drops . '<br>',
                        'is applying tax =' . $is_tax_applied_string . '<br>',
                        'Area Type   = ' . $selected_joey_plan_detail->plane_type . ' <br>',
                        'Calculation  = plan pre drop amount (' . $database_total_cost . ' / ' . $selected_joey_plan_detail->drops . ') = ' . $total_cost . ' , joey completed drop = ' . $TotalOrderDropsCompletedCount . ' X ' . $total_cost . ' = [' . $payout_without_tax . '] <br>',
                        'Error message  =' . $zone_not_found_massage . '<br>',
                        'Route Type  =' . $route_type,


                    ];
                    $payout_calculations = implode('', $payout_calculations);


                } elseif (in_array($joey_plan_data->plan_type, $JoeysPlanTypes['bracket_plan'])) // calculation for bracket plan
                {
                    $plan_for_apply_range = null;

                    $bracket_plan_details_filters_plan_type = [
                        "bracket_pricing|hourly|custom_route|big_box" => ['bracket_pricing_hourly', 'bracket_pricing_hourly_custom_route', 'bracket_pricing_hourly_big_box'],
                        "bracket_pricing|per_drop|custom_route|big_box" => ['bracket_pricing_per_drop', 'bracket_pricing_per_drop_custom_route', 'bracket_pricing_per_drop_big_box'],
                    ];

                    if ($route_type == 'normal_route') // block for bracket priceing
                    {
                        $plan_for_apply_range = $joey_plan_data->PlanDetails
                            ->where('max_range_drops', '>=', $TotalOrderDropsCompletedCount)
                            ->where('plane_type', '==', $bracket_plan_details_filters_plan_type[$joey_plan_data->plan_type][0])
                            ->sortBy('max_range_drops')
                            ->first();
                    } elseif ($route_type == 'custom_route') {
                        $plan_for_apply_range = $joey_plan_data->PlanDetails
                            ->where('plane_type', '==', $bracket_plan_details_filters_plan_type[$joey_plan_data->plan_type][1])
                            ->sortByDesc('id')
                            ->first();
                    } elseif ($route_type == 'big_box_route') {
                        $plan_for_apply_range = $joey_plan_data->PlanDetails
                            ->where('plane_type', '==', $bracket_plan_details_filters_plan_type[$joey_plan_data->plan_type][2])
                            ->sortByDesc('id')
                            ->first();
                    }

                    if ($plan_for_apply_range == null) // fallback if the completed drops  is grater then maximum range
                    {
                        $plan_for_apply_range = $joey_plan_data->PlanDetails
                            ->sortByDesc('max_range_drops')->first();
                    }


                    if ($joey_plan_data->plan_type == $JoeysPlanTypes['bracket_plan'][0] || $route_type == 'custom_route' || $route_type == 'big_box_route') // calculation for range per drop plan OR custom route or big box
                    {
                        $tax_amount_per_drop = $plan_for_apply_range->tax_charges / $plan_for_apply_range->min_range_drops;
//                        $total_cost = ($is_tax_applied) ?  $plan_for_apply_range->amount + $tax_amount_per_drop : $plan_for_apply_range->amount;
                        $total_cost = $plan_for_apply_range->amount;
                        $tax_percentage = $plan_for_apply_range->tax;
                        $plan_applied_amount = $total_cost . ' Per drop';
                        $payout_without_tax = ($total_cost * $TotalOrderDropsCompletedCount);
                        $payout_without_tax = round($payout_without_tax, 2);
                        $truck_amount = $plan_for_apply_range->gas_truck_amount;
                        $hourly = round(HoursToMinutes($ActualDuration) / 60, 2);
                        $payout_calculations = 'Get range amount per drop , total cost = per drop cost [' . $total_cost . '] x total compeleted drops[' . $TotalOrderDropsCompletedCount . '] , </br> range apply min=' . $plan_for_apply_range->min_range_drops . 'max=' . $plan_for_apply_range->max_range_drops . ' <br> Route type  =' . $route_type . '<br> is applying tax =' . $is_tax_applied_string . '<br>';
                    } elseif ($joey_plan_data->plan_type == $JoeysPlanTypes['bracket_plan'][1]) // calculation for range hourly plan
                    {
//                        $database_total_cost = ($is_tax_applied) ? $plan_for_apply_range->total_cost : $plan_for_apply_range->sub_joey_charges;
//                        $database_total_hours = $plan_for_apply_range->total_hours;
//                        $database_hourly_rate = $plan_for_apply_range->hourly_rate;
//                        $current_hours_work_in_seconds = TimeStringToSeconds($ActualDuration);
//                        $plan_hours_work_in_seconds = TimeStringToSeconds($database_total_hours);
//                        $rate_in_seconds = $database_total_cost /  $plan_hours_work_in_seconds;
//                        $total_cost = $rate_in_seconds * $current_hours_work_in_seconds;
//                        // checking the completed drops count are grater then zero so we can get the cost per drop
//                        $cost_per_drop_on_hourly = ($TotalOrderDropsCompletedCount > 0) ? round($total_cost / $TotalOrderDropsCompletedCount,2 ): 0;

                        $plan_estimated_total_time = $plan_for_apply_range->total_hours;

                        //calculate estimate time per drop plan hourly
                        $database_total_hours = $plan_for_apply_range->total_hours;
                        $database_total_hours_in_seconds = TimeStringToSeconds($database_total_hours);
//                        $database_total_cost = ($is_tax_applied) ? $plan_for_apply_range->total_cost : $plan_for_apply_range->sub_joey_charges;
                        $database_total_cost = $plan_for_apply_range->sub_joey_charges;
                        $tax_percentage = $plan_for_apply_range->tax;
                        $database_total_cost_in_seconds = $database_total_cost / $database_total_hours_in_seconds;
                        $per_drop_time_in_seconds_estimated = $database_total_hours_in_seconds / $plan_for_apply_range->min_range_drops;
                        $current_work_time_in_seconds_by_plan_estimation = $per_drop_time_in_seconds_estimated * $TotalOrderDropsCompletedCount;
                        $total_cost = $database_total_cost_in_seconds * $current_work_time_in_seconds_by_plan_estimation;
                        $plan_applied_amount = 'per drop time ' . gmdate("H:i:s", $per_drop_time_in_seconds_estimated) . ' pre drop cost by pre drop time ' . $database_total_cost_in_seconds * $per_drop_time_in_seconds_estimated;
                        $payout_without_tax = round($total_cost, 2);
                        $truck_amount = $plan_for_apply_range->gas_truck_amount;
                        $hourly = round(ConvertSecondsToHours($current_work_time_in_seconds_by_plan_estimation), 2);
                        $payout_calculations = 'Get range amount Hourly rate ,</br>
                                        original cost by plan ' . $database_total_hours . ' hours = [' . $database_total_cost . '] ,</br>
                                        original hours in second [' . $database_total_hours_in_seconds . '],</br>
                                        original total cost per second [' . $database_total_cost_in_seconds . '],</br>
                                        per drop seconds estimated by plan [' . $per_drop_time_in_seconds_estimated . '] formula ( original hours in second [' . $database_total_hours_in_seconds . '] / min drop  on plan [' . $plan_for_apply_range->min_range_drops . ']  ) ,</br>
                                        total working hours actual duration [' . $ActualDuration . '] ,</br>
                                        current working seconds according to estimation of plan [' . $current_work_time_in_seconds_by_plan_estimation . '] formula (per drop seconds estimated by plan [' . $per_drop_time_in_seconds_estimated . '] X total completed drops by joey [' . $TotalOrderDropsCompletedCount . '] )  ,
                                        range apply min=' . $plan_for_apply_range->min_range_drops . 'max=' . $plan_for_apply_range->max_range_drops . ' , </br>
                                        calculation formula to find payout (original total cost per second [' . $database_total_cost_in_seconds . '] x  current working seconds according to estimation of plan [' . $current_work_time_in_seconds_by_plan_estimation . '])
                                        ';

                        // calculating payout on total duration
                        $payout_with_total_duraion = round($database_total_cost_in_seconds * TimeStringToSeconds($TotalDuration), 2);
                        $payout_with_actual_duraion = $payout_without_tax;

                    }

                } elseif (in_array($joey_plan_data->plan_type, $JoeysPlanTypes['dynamic_section'])) {
                    $plan_for_apply = null;
                    // getting  route zone id
                    $route_zone = $single_data->JoeyRoute->zone;

                    if ($route_type == 'normal_route') // block for bracket priceing
                    {
                        $plan_for_apply = $joey_plan_data->PlanDetails()
                            ->whereHas('JoeyPlanGroupZones.ZonesRouting', function ($query) use ($route_zone) {
                                $query->where('zones_routing.id', $route_zone);
                            })->first();
                    } elseif ($route_type == 'custom_route') {
                        $plan_for_apply = $joey_plan_data->PlanDetails
                            ->where('section_name', '==', 'custom_route_dynamic_section')
                            ->first();
                    } elseif ($route_type == 'big_box_route') {
                        $plan_for_apply = $joey_plan_data->PlanDetails
                            ->where('section_name', '==', 'big_box_dynamic_section')
                            ->first();
                    }

                    if ($plan_for_apply == null) // fallback if the completed drops  is grater then maximum range
                    {

                       $plan_for_apply = $joey_plan_data->PlanDetails
                            ->where('section_name','==','dynamic_section')
                            ->sortBy('total_cost')
                            ->first();

                    }


//                    $total_cost = ($is_tax_applied) ? $plan_for_apply->total_cost : $plan_for_apply->sub_joey_charges;
                    $plan_total_cost = $plan_for_apply->sub_joey_charges;
                    $tax_percentage = $plan_for_apply->tax;
                    $per_drop_charges = $plan_total_cost / $plan_for_apply->drops;
                    $plan_applied_amount = $per_drop_charges . ' Per drop';
                    $payout_without_tax = ($per_drop_charges * $TotalOrderDropsCompletedCount);
                    $truck_amount = $plan_for_apply->gas_truck_amount;
                    $hourly = round(HoursToMinutes($ActualDuration) / 60, 2);
                    $payout_calculations = 'total cost without tax [' . $payout_without_tax . '] = ( per drop cost [' . $per_drop_charges . '] X total completed drops [' . $TotalOrderDropsCompletedCount . '] )';
                    $payout_calculations .= '<br>  Route type  = ' . $route_type;
                    $payout_calculations .= '<br>  Zone Name = ' . $zone_name;


                }
            }

            // flag deduction and bonus calculation after payout calculation by joey
            $flag_data = $single_data->FlagDataByJoey();

            if ($flag_data != null) {

                // looping on the flag data
                foreach ($flag_data as $flag_data) {

                    $applied_values = json_decode($flag_data->finance_incident_value_applied, true);

                    if ($applied_values['operator'] == "+") {
                        $flag_bonus += $applied_values['value'];

                    } elseif ($applied_values['operator'] == "-") {
                        $flag_deduction += $applied_values['value'];
                    }
                }


            }
            // flag deduction and bonus calculation on route
            $flag_route_value_applied = $single_data->FlagDataByRoute;
            if (!is_null($flag_route_value_applied)) {

                $applied_values = json_decode($flag_route_value_applied->finance_incident_value_applied, true);
                if ($applied_values['operator'] == "+") {
                    $flag_bonus += $applied_values['value'];
                } elseif ($applied_values['operator'] == "-") {
                    $flag_deduction += $applied_values['value'];
                }
                //dd($flag_deduction,$single_data);
            }

            // calculating payout after flag bonus and deduction
            $payout_without_tax += ($flag_bonus - $flag_deduction);


            // manual adjustment calculation
            $manual_adjustment_data = self::ManualAdjustmentCalculation($single_data, $payout_without_tax);
            $payout_without_tax = $manual_adjustment_data['final_amount'];
            $menual_adjustment = $manual_adjustment_data['value'];


            // calculating tax amount
            $tax_amount = round(getPercentageValue($tax_percentage, $payout_without_tax), 2);

            // updating final payout
            $final_payout = $payout_without_tax;
            // checking the tax applied on
            if ($brooker_name != 'N/A') {
                $tax_on = 'Broker';
            } elseif ($is_tax_applied) {
                $tax_on = 'Joey';
                $final_payout = $final_payout + $tax_amount;

            }


            // setting city name
            $vendor_id_test = (isset($single_data->vendor()->id)) ? $single_data->vendor()->id : '';
            if (in_array($vendor_id_test, $city_names['montreal'])) {
                $city_name = 'Montreal';
            } elseif (in_array($vendor_id_test, $city_names['ottawa'])) {
                $city_name = 'Ottawa';
            } elseif (in_array($vendor_id_test, $city_names['toronto'])) {
                $city_name = 'Toronto';
            }

            $return_data[$index++] = [
                'route_id' => $single_data->route_id,
                'joey_id' => $single_data->Joey->id ?? 'not set yet',
                'broker_id'=> (!is_null($brooker)) ? $brooker->id : null,
                'zone_id'=> $single_data->JoeyRoute->zone,
                'plan_id'=> (isset($joey_plan_data->id)) ? $joey_plan_data->id : null,
                'joey_name' => $single_data->joey->full_name ?? 'not set yet',
                'broker_name' => $brooker_name,
                'zone_name' => (isset($zone_routing_data->title)) ? $zone_routing_data->title : $city_name,
                'city_name' => $city_name,
                'vendor_id' => $single_data->vendor()->id ?? 'Not set yet',
                'vendor_name' => $single_data->vendor()->full_name ?? 'Not set yet',
                'route_total_orders' => $TotalOrderDropsCount,
                'total_completed_orders' => $TotalOrderDropsCompletedCount,
                'total_pickup_orders' => $TotalOrderPickedCount,
                'total_unattempt_orders' => $TotalOrderUnattemptedCount,
                'total_return_orders' => $TotalOrderReturnCount,
                'plan_type' => $plan_name,
                'plan_name' => $plan_internal_name,
                'first_drop_scan_date_time' => $FirstDropScan,
                'first_pickup_scan_date_time' => $FirstPickUpScan,
                'last_drop_scan_date_time' => $LastDropScan,
                'total_km_by_routific' => $TotalKM . ' Km',
                'actual_km' => $ActualTotalKM . ' Km',
                'total_duration' => $TotalDuration,
                'actual_duration' => $ActualDuration,
                'routific_duration' => (is_null($routific_duration)) ? "00:00:00": $routific_duration['total_time'],
                'plan_estimated_duration' => $plan_estimated_total_time,
                'gas_amount' => $gas_amount,
                'truck_amount' => $truck_amount,
                'tech_amount' => $tech_amount,
                'hourly' => $hourly,
                'flag_total_bonus' => $flag_bonus . ' $',
                'flag_total_deduction' => $flag_deduction . '$',
                'manual_adjustment' => $menual_adjustment,
                'plan_applied_amount_per_drop' => $plan_applied_amount,
                'payout_without_tax' => $payout_without_tax,
                'tax_amount' => $tax_amount,
                'tax_percentage' => $tax_percentage,
                'tax_on' => $tax_on,
                'final_payout' => $final_payout . ' $',
                'payout_with_actual_duration' => $payout_with_actual_duraion . ' $',
                'payout_with_total_duration' => $payout_with_total_duraion . ' $',
                'cost_per_drop_on_hourly' => $cost_per_drop_on_hourly . ' $',
                'route_type' => str_replace("_", " ", $route_type),
                'calculation_text' => $payout_calculations,
            ];


        }


        // returning data
        return $return_data;
    }

    static function flagByRouteCalculation($routeData)
    {
        $value = 0;
        $flag_route_value_applied = $routeData->FlagDataByRoute;
        if (!is_null($flag_route_value_applied)) {
            $applied_values = json_decode($flag_route_value_applied->finance_incident_value_applied, true);
            if ($applied_values['operator'] == "+") {
                $value = $applied_values['value'];
            } elseif ($applied_values['operator'] == "-") {
                $value -= $applied_values['value'];
            }
        }

        return $value;

    }


    static function ManualAdjustmentCalculation($routeData, $amount)
    {
        $responce = [
            "type_applied" => null,
            "value" => 0,
            "final_amount" => 0,
        ];
        $total_adjustment_amount = 0;
        $final_amount = $amount;
        // getting data against adjustment
        $adjustment_data_list = $routeData->RouteManualAdjustmentByJoey->toArray();
        foreach ($adjustment_data_list as $adjustment_data) {
            // adding calculation
            if ($adjustment_data['type_applied'] == 0) // add
            {
                $total_adjustment_amount = $total_adjustment_amount + $adjustment_data['amount'];
                $final_amount = $final_amount + $adjustment_data['amount'];
            } elseif ($adjustment_data['type_applied'] == 1) // subtract
            {
                $total_adjustment_amount = $total_adjustment_amount - $adjustment_data['amount'];
                $final_amount = $final_amount - $adjustment_data['amount'];
            }
        }
        // creating responce
        $responce['type_applied'] = ($total_adjustment_amount >= 0) ? 'add' : 'subtract';
        $responce['value'] = $total_adjustment_amount;
        $responce['final_amount'] = $final_amount;

        return $responce;

    }



}
