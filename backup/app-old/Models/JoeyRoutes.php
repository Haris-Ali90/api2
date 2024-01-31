<?php

namespace App\Models;
use App\Http\Traits\BasicModelFunctions;
use App\Models\Interfaces\JoeyRoutesInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class JoeyRoutes extends Model implements JoeyRoutesInterface
{
    use BasicModelFunctions;
    public $table = 'joey_routes';



    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [

    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
    ];

    ### for route location
    public  function joeyRouteLocation(){
        return $this->belongsTo(JoeyRouteLocation::class,'id','route_id');
       // return $this->hasMany(JoeyRouteLocation::class,'route_id','id');
        }

    public function joeyRouteLocations()
    {
        return $this->hasMany(JoeyRouteLocation::class,'route_id','id')->whereNull('deleted_at');
    }
    /**
     * Get Manager routs locations.
     */
    public  function ManagerJoeyRouteLocation(){
        return $this->hasMany(JoeyRouteLocation::class,'route_id','id')->where('joey_route_locations.deleted_at', null);
    }

    public function joey(){
        return $this->belongsTo(Joey::class,'joey_id','id');
    }

    /**
     * Get Total Numbers Of Order Drops in this Route .
     */
    public function TotalOrderDropsCount()
    {
        return $this->ManagerJoeyRouteLocation()->count();
    }

    /**
     * Get Total Tasks Ids in this Route .
     */
    public function getManagerAllTaskIds()
    {
        // gating current routs tasks ids
        return $this->ManagerJoeyRouteLocation()
            ->pluck('task_id')
            ->toArray();
    }

    /**
     * Get Total Numbers Of Sorted Orders in this Route .
     */
    public function ManagerTotalSortedOrdersCount()
    {
        // gating current routs tasks ids
        $tasks_ids = $this->getManagerAllTaskIds();

        // getting route creation datetime
        $created_at = $this->date;

        // getting status codes
        $status_codes = implode(',', $this->getStatusCodes('sort'));

        return SprintTaskHistory::whereIn('sprint__tasks_id', $tasks_ids)
            ->join('sprint__sprints', 'sprint__tasks_history.sprint_id', '=', 'sprint__sprints.id')
            ->where('sprint__tasks_history.status_id', $status_codes)
            ->where('sprint__sprints.deleted_at', null)
            ->where('sprint__tasks_history.date', '>=', $created_at)
            ->orderBy('sprint__tasks_history.date', 'DESC')
            ->distinct('sprint__tasks_history.sprint__tasks_id')
            ->count('sprint__tasks_history.sprint__tasks_id');
    }

    /**
     * Get Total Tasks Ids in this Route .
     */
    public function GetAllManagerTaskIds()
    {
        // gating current routs tasks ids
        return $this->ManagerJoeyRouteLocation()
            ->pluck('task_id')
            ->toArray();
    }

    /**
     * Get Total Numbers Of Orders Picked in this Route .
     */
    public function ManagerTotalOrderPickedCount()
    {
        // gating current routs tasks ids
        $tasks_ids = $this->GetAllManagerTaskIds();

        // getting route creation datetime
        $created_at = $this->date;

        // getting status codes
        $status_codes = implode(',', $this->getStatusCodes('pickup'));

        return SprintTaskHistory::whereIn('sprint__tasks_id', $tasks_ids)
            ->join('sprint__sprints', 'sprint__tasks_history.sprint_id', '=', 'sprint__sprints.id')
            ->where('sprint__tasks_history.status_id', $status_codes)
            ->where('sprint__sprints.deleted_at', null)
            ->where('sprint__tasks_history.date', '>=', $created_at)
            ->orderBy('sprint__tasks_history.date', 'DESC')
            //->groupBy('status_id')
            ->distinct('sprint__tasks_history.sprint__tasks_id')
            // ->Active()
            ->count('sprint__tasks_history.sprint__tasks_id');
    }

    /**
     * Get Total Numbers Of Orders Completed in this Route .
     */
    public function ManagerTotalOrderDropsCompletedCount()
    {
        // gating current routs tasks ids
        $tasks_ids = $this->GetAllManagerTaskIds();

        // getting status codes
        $status_codes = $this->getStatusCodes('competed');

        return SprintTasks::join('sprint__sprints', 'sprint__tasks.sprint_id', '=', 'sprint__sprints.id')
            ->whereIn('sprint__sprints.status_id', $status_codes)
            ->whereIn('sprint__tasks.id', $tasks_ids)
            ->where('sprint__sprints.deleted_at', null)
            ->count();
    }

    /**
     * Get Total Numbers Of Orders Unattempted in this Route .
     */
    public function ManagerTotalOrderReturnCount()
    {
        // getting status codes
        $status_codes = $this->getStatusCodes('return');

        // gating current routs tasks ids
        $tasks_ids = $this->GetAllManagerTaskIds();
        return SprintTasks::join('sprint__sprints', 'sprint__tasks.sprint_id', '=', 'sprint__sprints.id')
            ->whereIn('sprint__sprints.status_id', $status_codes)
            ->whereIn('sprint__tasks.id', $tasks_ids)
            ->where('sprint__sprints.deleted_at', null)
            ->count();

    }

    /**
     * Get Total Numbers Of Orders Unattempted in this Route .
     */
    public function ManagerTotalOrderNotScanCount()
    {
        // gating current routs tasks ids
        $tasks_ids = $this->GetAllManagerTaskIds();

        // getting status codes
        $status_codes = $this->getStatusCodes('unattempted');

        return SprintTasks::join('sprint__sprints', 'sprint__tasks.sprint_id', '=', 'sprint__sprints.id')
            ->whereIn('sprint__sprints.status_id', $status_codes)
            ->whereIn('sprint__tasks.id', $tasks_ids)
            ->where('sprint__sprints.deleted_at', null)
            ->count();

    }

    public function ManagerTotalOrderUnAttemptedCount()
    {
        if ($this->ManagerTotalOrderPickedCount() >= $this->ManagerTotalOrderDropsCompletedCount()) {
            return abs($this->ManagerTotalOrderPickedCount() - $this->ManagerTotalOrderDropsCompletedCount() - $this->ManagerTotalOrderReturnCount());
        } else
            return 0;
    }

    /**
     * Get Time Of First Pickup Scan Of Order in this Route .
     */
    public function ManagerEstimatedTime()  // first pick up scan from hub
    {
        $data = JoeyRouteLocation::where('route_id', '=', $this->id)
            ->selectRaw('SEC_TO_TIME(SUM(TIME_TO_SEC(finish_time)-TIME_TO_SEC(arrival_time))) AS duration')
            ->pluck('duration')->toArray();
        return $data[0];

    }

    /**
     * Is Custom Or Not .
     */
    public function managerIsCustom()
    {

        if ($this->zone == null) {
            return 'Yes';
        } else {
            $checkZone = \DB::table("zones_routing")->where('id', $this->zone)->whereNotNull('is_custom_routing')->first();
            if ($checkZone) {
                return 'Yes';
            } else {
                return 'No';
            }

        }

    }

    public  function RouteHistory(){

        return $this->hasMany(RouteHistory::class,'route_id','id');
    }

//    public static function newRoutesListQuery($joeyId)
//    {
//        $routes = JoeyRoutes::join('joey_route_locations as jrl','jrl.route_id' ,'=', 'joey_routes.id')
//            ->leftJoin('sprint__tasks as task','jrl.task_id','=', 'task.id')
//            ->leftJoin('sprint__sprints as sprint', 'task.sprint_id','=','sprint.id')
//            ->leftJoin('merchantids as mrids', 'task.id','=','mrids.task_id')
//            ->leftJoin('sprint__contacts as spcon', 'spcon.id', '=', 'task.contact_id')
//            ->leftJoin('locations as loc', 'loc.id', '=', 'task.location_id')
//            ->where('joey_routes.joey_id', $joeyId)
//            //->where('is_reattempt','=',0)
//            ->where('joey_routes.route_completed',0)
//            ->whereNull('jrl.deleted_at')
//            ->whereNull('task.deleted_at')
//            ->whereNull('sprint.deleted_at')
//            ->whereNull('jrl.is_unattempted')
//            ->whereNull('joey_routes.deleted_at')
//            ->orderBy('joey_routes.mile_type', 'ASC')
//            ->get(['joey_routes.id as route_id', 'joey_routes.date as route_date', 'joey_routes.total_travel_time',
//                'joey_routes.total_distance', 'joey_routes.hub', 'joey_routes.mile_type','jrl.ordinal','jrl.task_id', 'jrl.arrival_time', 'jrl.finish_time',
//                'mrids.start_time', 'mrids.end_time', 'spcon.name', 'spcon.email', 'spcon.phone', 'loc.address as loc_address', 'loc.suite', 'mrids.address_line2', 'mrids.tracking_id',
//                'mrids.merchant_order_num', 'task.sprint_id', 'task.status_id as task_status_id','loc.id as location_id', 'loc.latitude as loc_latitude',
//                'loc.longitude as loc_longitude', 'task.type', 'task.description']);
//        return $routes;
//    }

}


