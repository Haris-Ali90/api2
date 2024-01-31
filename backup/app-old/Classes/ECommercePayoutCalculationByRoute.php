<?php
namespace App\Classes;

/**
 * Created by Muhammad Adnan Nadeem.
 * Email: adnannadeem1994@gamil.com
 * Date: 6/02/2021
 * Time: 5:38 PM
 */

use App\Models\JoeyPayoutHistory;
use App\Models\JoeyPlan;
use App\Models\JoeyRouteLocation;
use App\Models\JoeyRoutes;
use App\Models\RouteHistory;
use App\Models\SprintTasks;
use App\Models\SystemParameters;
use App\Repositories\SystemParametersRepository;
use App\Classes\JoeyPayoutCalculation;

class ECommercePayoutCalculationByRoute
{
    private $requestRouteIds;
    private $incompleteRoutes;
    private $completeRoutes;
    private $SystemParametersRepository;
    private $extra_query_perams = [];
    private $current_date;
    private $markedBy = 0;



    public function __construct()
    {
        // class binding
        $this->SystemParametersRepository = new SystemParametersRepository(new SystemParameters());

        // setting current date
        $this->current_date = date('Y-m-d');

        // getting default values
        $this->extra_query_perams['system_parameters'] = $this->SystemParametersRepository->getKeyValue(['gas','truck','hourly','tech']);

        //getting plan types
        $this->extra_query_perams['JoeysPlanTypes'] = JoeyPlan::JoeysPlanTypes;


    }

    //set route for request
    public function setRequestRoutes($route_ids) {
        //Set Route Ids
        $this->requestRouteIds = $route_ids;
    }

    //set user who mark complete routes
    public function setMarkedBy($id) {
        //Set Route Ids
        $this->markedBy = $id;
    }

    //set validation for complete / incomplete route
    public function validateRequestForCompletion() {

        //Pass Route Id In  Private Helper Function
        $this->validateRequestForCompletionHelper($this->requestRouteIds);

    }

    //validation request helper function for routes
    private function validateRequestForCompletionHelper($requestIDs)
    {

        //var_dump($requestIDs);
        $this->incompleteRoutes = [];
        $this->completeRoutes = [];

        $completed_status_code = getStatusCodes('competed');
        $return_status_code = getStatusCodes('return');
        $status_code = array_merge($completed_status_code,$return_status_code);

        foreach ($requestIDs as $requestID)
        {
            //Getting Task Id From JoeyRouteLocation
            $gettingTaskIDs = JoeyRouteLocation::where('route_id', $requestID)
                ->whereNull('deleted_at')
                ->pluck('task_id')->toArray();

            //Getting Task IDs Count From JoeyRouteLocation
            $gettingTaskCount = count($gettingTaskIDs);

            //Getting Count From SprintTask Against Task ID
            $gettingCompletedTaskCount = SprintTasks::whereIn('id',$gettingTaskIDs)->whereIn('status_id',$status_code)->count();
            if ($gettingTaskCount == $gettingCompletedTaskCount && $gettingTaskCount > 0)
            {
                array_push($this->completeRoutes,$requestID);
                continue;
            }
            else
            {
                //dd($incompleteRoutes,$requestID);
                array_push($this->incompleteRoutes,$requestID);
                continue;
            }

        }


    }

    //process function for marking complete routes
    public function markCompleteRouteAndSaveCalculation()
    {
        // updating joey routes as completed
        $joey_routes = JoeyRoutes::WhereIn('id',$this->completeRoutes)->update([
            "payout_generated" => 1,
            "payout_generated_by" => $this->markedBy,
        ]);

        // now processing routes for calculation
        $data_for_calculation = RouteHistory::has('JoeyRoute')
            ->whereIn('route_id',$this->completeRoutes)
            ->where('joey_id','!=' ,null)
            ->groupBy('route_history.route_id','route_history.joey_id')
            ->get();

        //doing calculation
        $inserting_data = JoeyPayoutCalculation::calculate($data_for_calculation,$this->extra_query_perams);

        // saving data
        JoeyPayoutHistory::insert($inserting_data);

        // returning
        return $this->incompleteRoutes;

    }

}
