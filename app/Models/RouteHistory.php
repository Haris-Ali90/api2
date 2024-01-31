<?php

namespace App\Models;

use App\Models\Interfaces\RouteHistoryInterface;


use DB;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;


class RouteHistory extends Model implements RouteHistoryInterface
{
    /**
     * Working For Mark Complete Route Payout Calculation
     */
    const AssignRopute = 0;
    const TransferRoute = 1;

    private $Statuses = ["assign" => 0  ,"transfer" => 1, "completed" => 2 ,"pickup"=> 3 , "return"=> 4];
    private $FlagDataByJoey = null;
    private $JoeyTasksIds = [];
    private $IsJoeyTasksIdsLoaded = false;
    private $JoeyTrackingids = [];
    private $JoeyCompletedDrops = 0;
    private $JoeyReturnDrops = 0;
    private $JoeyPickUpOrders = 0;
    private $JoeyFirstPickupTime = '';
    private $JoeyFirstDropTime = '';
    private $JoeyLastDropTime = '';
    private $JoeyActualTotalKM = 0;
    private $IsCalculationDoneByJoey = false;
    public $DateRangeSelected = ['start_date'=>'','end_date'=>''];

    public $table = 'route_history';

    use SoftDeletes,Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded =[];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
    ];


    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
//for sprint task against route location
    public function taskIdAgainstRouteLocationId(){
        return $this->belongsTo(JoeyRouteLocation::class,'route_location_id','id');
    }
    // for self relation against joey, route and status id 2
    public function routeHistoryForJoey(){
        return $this->hasMany(self::class,'joey_id','joey_id')->where('route_id',$this->route_id)->whereIn('status',[2,4])->groupBy('route_location_id');
    }


    //Working For Mark Complete Route Payout Calculation
    /**
     * Model filter scopes
     *
     */

    public function scopeRouteLocationIdExsit($query)
    {
        return $query->where('route_location_id','!=',null);
    }

    /**
     * ORM Relation
     *
     * @var array
     */

    /**
     * Get joey data.
     */
    public function Joey()
    {
        return $this->belongsTo(Joey::class,'joey_id', 'id');
    }

    /**
     * Get JoeyRoute data.
     */
    public function JoeyRoute()
    {
        return $this->belongsTo(JoeyRoutes::class,'route_id', 'id');
    }

    /**
     * Get JoeyRoute data.
     */
    public function JoeyRouteLocation()
    {
        return $this->belongsTo(JoeyRouteLocation::class,'route_location_id', 'id');
    }

    /**
     * Get All JoeyRoute locataion  data.
     */
    public function JoeyRouteLocationsByRouteID()
    {
        return $this->hasMany(JoeyRouteLocation::class,'route_id', 'route_id');
    }

    /**
     * Get task .
     */
    public function SprintTask()
    {
        return $this->belongsTo(SprintTasks::class,'task_id', 'id');
    }

    /**
     * Get manual adjustment by route.
     */
    public function RouteManualAdjustmentByJoey()
    {
        return $this->hasMany(PayoutManualAdjustment::class,'route_id', 'route_id')
            ->where('joey_id',$this->joey_id)
            ;
    }

    /**
     * Get the ZoneType.
     */
    public function ZoneRouting()
    {
        return $this->hasOneThrough(
            ZoneRouting::class,
            JoeyRoutes::class,
            'id', // Foreign key on users table...
            'id', // Foreign key on history table...
            'route_id', // Local key on suppliers table...
            'zone' // Local key on users table...
        );
    }

    /**
     * Get All tasks ids on current joey data.
     */
    public function JoeyTasksIds()
    {
        // applying cache
        if($this->IsJoeyTasksIdsLoaded)
        {
            return $this->JoeyTasksIds;
        }

        $retrun_data = [];
        $query = $this->hasMany(self::class,'joey_id', 'joey_id')
            ->has('JoeyRouteLocation')
            ->where('route_id',$this->route_id)
            ->get();

        // checking the data is exist
        if($query != null)
        {
            $retrun_data = $query->pluck('task_id')->toArray();
            $retrun_data = array_filter(array_unique($retrun_data));
        }

        //setting current task ids
        $this->JoeyTasksIds = $retrun_data;
        $this->IsJoeyTasksIdsLoaded = true;
        return $retrun_data;
    }

    // get cached task ids
    public function GetCachedJoeyTasksIds()
    {
        // checking the joey ids cached
        if(count($this->JoeyTasksIds) == 0)
        {
            $this->JoeyTasksIds();
        }

        return $this->JoeyTasksIds;

    }

    // get current joey all task Trackingids
    public function GetJoeyTasksTrackingids()
    {
        if(count($this->JoeyTrackingids) <= 0)
        {
            $task_ids = $this->GetCachedJoeyTasksIds();
            $this->JoeyTrackingids = MerchantsIds::whereIn('task_id',$task_ids)->pluck('tracking_id')->toArray();
        }

        return $this->JoeyTrackingids;

    }

    /**
     * Get FirstSortScan on current joey data.
     */
    public function FirstSortScan($return_type = 'created_at')
    {
        $joey_tasks_ids =  $this->JoeyTasksIds();

        // getting fisrt sort scan status codes
        $status_code = getStatusCodes('sort');

        $data =  SprintTaskHistory::whereIn('sprint__tasks_id',$joey_tasks_ids)
            ->where('status_id',$status_code)
            ->orderBy('date','asc')
            ->first(['*',\DB::raw("CONVERT_TZ(date,'UTC','America/Toronto') as created_at")]);

        if($data != null) {
            // checking return type
            if ($return_type == 'created_at') {
                /**
                 * return only date time
                 */
                $data->toArray();
                return $data['created_at'];
            } elseif ($return_type == 'array') {
                /**
                 * return array of this db raw
                 */

                return $data->toArray();

            } elseif ($return_type == 'object') {
                /**
                 * return model object of this raw
                 */
                return $data;
            }

            /**
             * return empty string data not find
             */
            return '';
        }

    }

    /**
     * Get FirstPickUpScan current joey data.
     */
    public function FirstPickUpScan()
    {
        $joey_tasks_ids =  $this->JoeyTasksIds();

        $data = self::whereIn('task_id',$joey_tasks_ids)->where('status',3)
            ->orderBy('created_at','asc')
            ->first(\DB::raw("CONVERT_TZ(created_at,'UTC','America/Toronto') as created_at"));

        if($data != null) {
            return $data->created_at;
        }

        /**
         * return empty string data not find
         */
        return '';

    }

    /**
     * Get FirstPickUpScan current joey data by sprint tasks history table.
     */
    public function FirstPickUpScanBySprintTaskHistroy($return_type = 'created_at')
    {
        $joey_tasks_ids =  $this->JoeyTasksIds();

        // getting fisrt sort scan status codes
        $status_code = getStatusCodes('pickup');

        $data =  SprintTaskHistory::whereIn('sprint__tasks_id',$joey_tasks_ids)
            ->where('status_id',$status_code)
            ->orderBy('date','asc')
            ->first(['*',\DB::raw("CONVERT_TZ(date,'UTC','America/Toronto') as created_at")]);

        if($data != null) {
            // checking return type
            if ($return_type == 'created_at') {
                /**
                 * return only date time
                 */
                $data->toArray();
                return $data['created_at'];
            } elseif ($return_type == 'array') {
                /**
                 * return array of this db raw
                 */

                return $data->toArray();

            } elseif ($return_type == 'object') {
                /**
                 * return model object of this raw
                 */
                return $data;
            }

            /**
             * return empty string data not find
             */
            return '';
        }

    }

    /**
     * Get  FirstDropScan current joey data.
     */
    public function FirstDropScan()
    {
        $joey_tasks_ids =  $this->JoeyTasksIds();

        $data = self::whereIn('task_id',$joey_tasks_ids)->where('status',2)->orderBy('created_at','asc')->first(\DB::raw("CONVERT_TZ(created_at,'UTC','America/Toronto') as created_at"));

        if($data != null) {
            return $data->created_at;
        }

        /**
         * return empty string data not find
         */
        return '';
    }

    /**
     * Get  FirstDropScan current joey data from sprint tasks history.
     */
    public function FirstDropScanBySprintTaskHistroy($return_type = 'created_at')
    {
        $joey_tasks_ids =  $this->JoeyTasksIds();

        // getting fisrt sort scan status codes
        $status_code = getStatusCodes('completed');

        $data =  SprintTaskHistory::whereIn('sprint__tasks_id',$joey_tasks_ids)
            ->whereIn('status_id',$status_code)
            ->orderBy('date','asc')
            ->first(['*',\DB::raw("CONVERT_TZ(date,'UTC','America/Toronto') as created_at")]);

        if($data != null) {
            // checking return type
            if ($return_type == 'created_at') {
                /**
                 * return only date time
                 */
                $data->toArray();
                return $data['created_at'];
            } elseif ($return_type == 'array') {
                /**
                 * return array of this db raw
                 */

                return $data->toArray();

            } elseif ($return_type == 'object') {
                /**
                 * return model object of this raw
                 */
                return $data;
            }

            /**
             * return empty string data not find
             */
            return '';
        }

    }

    /**
     * Get  LastDropScan current joey data .
     */
    public function LastDropScan()
    {
        $joey_tasks_ids =  $this->JoeyTasksIds();

        $data = self::whereIn('task_id',$joey_tasks_ids)->where('status',2)->orderBy('created_at','DESC')->first(\DB::raw("CONVERT_TZ(created_at,'UTC','America/Toronto') as created_at"));

        if($data != null) {
            return $data->created_at;
        }

        /**
         * return empty string data not find
         */
        return '';
    }

    /**
     * Get  LastDropScan current joey data from sprint tasks history.
     */
    public function LastDropScanBySprintTaskHistroy($return_type = 'created_at')
    {
        $joey_tasks_ids =  $this->JoeyTasksIds();

        // getting fisrt sort scan status codes
        $status_code = getStatusCodes('completed');

        $data =  SprintTaskHistory::whereIn('sprint__tasks_id',$joey_tasks_ids)
            ->whereIn('status_id',$status_code)
            ->orderBy('date','DESC')
            ->first(['*',\DB::raw("CONVERT_TZ(date,'UTC','America/Toronto') as created_at")]);

        if($data != null) {
            // checking return type
            if ($return_type == 'created_at') {
                /**
                 * return only date time
                 */
                $data->toArray();
                return $data['created_at'];
            } elseif ($return_type == 'array') {
                /**
                 * return array of this db raw
                 */

                return $data->toArray();

            } elseif ($return_type == 'object') {
                /**
                 * return model object of this raw
                 */
                return $data;
            }

            /**
             * return empty string data not find
             */
            return '';
        }

    }

    /**
     * Get Time Of TotalKM Scan Of Order in this Route .
     */
    public function TotalKM()  //calculate Assign Route Total KM
    {
        $joey_tasks_ids =  $this->JoeyTasksIds();

        $data = JoeyRouteLocation::whereIn('task_id',$joey_tasks_ids)->sum('distance');
        return round( $data / 1000 ,2);
    }

    /**
     * Get Time Of TotalKM Scan Of Order in this Route of this joey .
     */
    public function ActualTotalKM()  //calculate ActualTotalKM Route Total KM
    {
        $joey_tasks_ids =  $this->JoeyTasksIds();

        // getting fisrt sort scan status codes
        $status_code = getStatusCodes('completed');

        $data = JoeyRouteLocation::join('sprint__tasks' , 'sprint__tasks.id', '=', 'joey_route_locations.task_id')
            ->join('sprint__sprints' , 'sprint__sprints.id', '=', 'sprint__tasks.sprint_id')
            ->whereIn('sprint__sprints.status_id',$status_code)
            ->whereIn('joey_route_locations.task_id',$joey_tasks_ids)
            ->distinct('joey_route_locations.id')
            ->pluck('joey_route_locations.distance','sprint__sprints.id')->toArray();

        $data = round( array_sum($data) / 1000 , 2);
        return $data;
    }

    /**
     * Get Total Numbers Of Order Drops in this Route of this joey .
     */
    public function TotalOrderDropsCount()
    {
        $joey_tasks_ids =  $this->JoeyTasksIds();

        // counting assigned task count
        return count($joey_tasks_ids);
    }

    /**
     * Get Total Numbers Of Orders Picked in this Route by this joey .
     */
    public function TotalOrderPickedCount()
    {
        // gating current routs tasks ids by this joey
        $joey_tasks_ids =  $this->JoeyTasksIds();

        // getting fisrt sort scan status codes
        $status_code = getStatusCodes('pickup');

        return SprintTaskHistory::whereIn('sprint__tasks_id',$joey_tasks_ids)
            ->where('status_id',$status_code)
            ->distinct('sprint__tasks_id')
            ->count();
    }

    /**
     * Get Total Numbers Of Orders Completed in this Route by this joey .
     */
    public function TotalOrderDropsCompletedCount()
    {
        // gating current routs tasks ids by this joey
        $joey_tasks_ids =  $this->JoeyTasksIds();

        // getting fisrt sort scan status codes
        $status_code = getStatusCodes('completed');

        return SprintTasks::join('sprint__sprints','sprint__tasks.sprint_id', '=', 'sprint__sprints.id')
            ->whereIn('sprint__sprints.status_id',$status_code)
            ->whereIn('sprint__tasks.id', $joey_tasks_ids)
            ->where('sprint__sprints.deleted_at', null)
            ->distinct('sprint__sprints.id')
            ->count();
    }

    /**
     * Get Total Numbers Of Orders Unattempted in this Route this joey.
     */
    public function TotalOrderReturnCount()
    {
        // gating current routs tasks ids by this joey
        $joey_tasks_ids =  $this->JoeyTasksIds();

        // getting fisrt sort scan status codes
        $status_code = getStatusCodes('return');

        return SprintTasks::join('sprint__sprints','sprint__tasks.sprint_id', '=', 'sprint__sprints.id')
            ->whereIn('sprint__sprints.status_id',$status_code)
            ->whereIn('sprint__tasks.id', $joey_tasks_ids)
            ->where('sprint__sprints.deleted_at', null)
            ->distinct('sprint__sprints.id')
            ->count();
    }

    /**
     * Get Total Numbers Of Orders Unattempted in this Route this joey.
     */
    public function TotalOrderUnattemptedCount()
    {
        //return $this->TotalOrderPickedCount() - ($this->TotalOrderDropsCompletedCount() + $this->TotalOrderReturnCount());
        return $this->TotalOrderDropsCount() - ($this->TotalOrderDropsCompletedCount() + $this->TotalOrderReturnCount());
    }

    /**
     * Get Total Numbers Of Orders Not Scan in this Route this joey.
     */
    public function TotalOrderNotScanCount()
    {

        // gating current routs tasks ids by this joey
        $joey_tasks_ids =  $this->JoeyTasksIds();

        // getting fisrt sort scan status codes
        $status_code = getStatusCodes('unattempted');

        return SprintTasks::join('sprint__sprints','sprint__tasks.sprint_id', '=', 'sprint__sprints.id')
            ->whereIn('sprint__sprints.status_id',$status_code)
            ->whereIn('sprint__tasks.id', $joey_tasks_ids)
            ->where('sprint__sprints.deleted_at', null)
            ->distinct('sprint__sprints.id')
            ->count();
    }

    /**
     * Get the Location data.
     */
    public function locations()
    {
        // gating current task location
        $task = (isset($this->SprintTask))?$this->SprintTask:null;


        if($task == null)
        {
            return 'task is null';
        }
        elseif($task->location == null)
        {
            return 'location is null';
        }
        return $task->location;

    }

    /**
     * Get Vendor.
     */
    public function Vendor()
    {

        // gating current task vendor
        $task = $this->SprintTask;

        if($task == null)
        {
            return 'task is null';
        }
        elseif ($task->Sprints == null) {
            return 'Sprint is null';
        } elseif ($task->Sprints->Vendor == null) {
            return 'vendor is null';
        }
        return $task->Sprints->Vendor;

    }

    // getting current joey work on big box route
    function CustomRoutingTrackingId($return_type = 'get',$orderBy ='DESC' , $data_type ='object')
    {

        $tracking_ids = $this->GetJoeyTasksTrackingids();
        $query = CustomRoutingTrackingId::whereIn('tracking_id',$tracking_ids)->orderBy('id',$orderBy);
        $data = ($data_type == 'first')?$query->first():$query->get();

        // checking the data is not null
        if($data != null && count($data) > 0)
        {
            if($data_type =='object')
            {
                return $data;
            }
            elseif($data_type =='array')
            {
                return $data->toArray();
            }
        }

        return $data;

    }

    // checking this route use big box
    function IsthisRouteUseBigBox()
    {
        $tracking_ids = $this->GetJoeyTasksTrackingids();
        $data = CustomRoutingTrackingId::whereIn('tracking_id',$tracking_ids)
            ->where('is_big_box',1)
            ->first();

        if($data == null)
        {
            return 0;
        }
        return  $data->is_big_box;
    }

    /**
     * get flag orders data by sprint and joey id
     */

    public function FlagDataByJoey()
    {

        // checking the flag order data is already loaded
        if($this->FlagDataByJoey != null)
        {
            return $this->FlagDataByJoey;
        }

        // gating current routs tasks ids by this joey
        $joey_tasks_ids =  $this->JoeyTasksIds();

        // getting this route sprint ids
        $sprints_ids = SprintTasks::join('sprint__sprints','sprint__tasks.sprint_id', '=', 'sprint__sprints.id')
            ->whereIn('sprint__tasks.id', $joey_tasks_ids)
            ->where('sprint__sprints.deleted_at', null)
            ->orderBy('sprint__sprints.id',"ASC")
            ->distinct('sprint__sprints.id')
            ->select('sprint__sprints.id')
            ->pluck('sprint__sprints.id')
            ->toArray();

        // getting joey performance data
        $data =  JoeyPerformanceHistory::where('joey_id',$this->joey_id)
            ->FilterUnFlagged()
            ->TypeOrder()
            ->whereIn('sprint_id',$sprints_ids)
            ->orderBy('id','DESC')
            ->get();

        $this->FlagDataByJoey = $data;

        // check data empty
        if($data->isEmpty())
        {
            return null;
        }

        return $this->FlagDataByJoey;

    }

    /**
     * get flag orders data by route
     */
    public function FlagDataByRoute()
    {

        return $this->hasOne(JoeyPerformanceHistory::class,'route_id','route_id')->TypeRoute();

    }

    private function PayoutQueryCalculatoinByJoey($optional_filters = [])
    {

        // checking the calculation is already done
        if(!$this->IsCalculationDoneByJoey)
        {

            // getting current date search
            $self_date = $this->updated_at->timezone('America/Toronto')->toDateString();
            $start_data = (!empty($this->DateRangeSelected['start_date'])) ? $this->DateRangeSelected['start_date'] : $self_date.' 00:00:00' ;
            $end_date = (!empty($this->DateRangeSelected['end_date'])) ? $this->DateRangeSelected['end_date'] : $self_date.' 23:59:59' ;

            $sorted_data = [
                "pickup_sort"=> [],
                "completed_and_return"=> [],
                "completed_drops_task_ids" => [],
                "first_pickup_time"=> '',
                "first_pickup_time_capture"=> false,
                "first_drop_time"=> '',
                "first_drop_time_capture"=> false,
                "last_drop_time"=> '',
                "actual_total_km"=> 0,
            ];


            // getting data for calculation
            $query = self::has('JoeyRouteLocation')
                ->where('joey_id', $this->joey_id)
                ->where('route_id',$this->route_id);

            // applying optional filters
            if(isset($optional_filters['date']))
            {
                $query->whereBetween('created_at',[$start_data,$end_date]);
            }

            $query  =  $query->whereIn('status',[$this->Statuses['completed'],$this->Statuses['pickup'],$this->Statuses['return']])
                ->orderBy('id','asc')
                ->get();

            // now sorting data
            foreach($query as $single_data)
            {
                //dd($single_data);
                // checking the status is pickup
                if($single_data->status == $this->Statuses['pickup'])
                {
                    // adding the records of pickup
                    $sorted_data['pickup_sort'][$single_data->task_id] = $single_data->task_id;
                    // now capturing first pickup time
                    if(!$sorted_data['first_pickup_time_capture'] && !is_null($single_data->created_at))
                    {
                        $sorted_data['first_pickup_time'] =  ConvertTimeZone($single_data->created_at,'UTC','America/Toronto');
                        $sorted_data['first_pickup_time_capture'] = true;
                    }
                    else
                    {
                        $sorted_data['first_pickup_time_capture'] = true;
                    }

                }
                else
                {
                    // now capturing first drop time
                    if(!$sorted_data['first_drop_time_capture'] && $single_data->status == $this->Statuses['completed'] && !is_null($single_data->created_at))
                    {
                        $sorted_data['first_drop_time'] = ConvertTimeZone($single_data->created_at,'UTC','America/Toronto');
                        $sorted_data['first_drop_time_capture'] = true;

                    }
                    elseif($single_data->status == $this->Statuses['completed'])
                    {
                        $sorted_data['first_drop_time_capture'] = true;
                    }

                    // now capturing last drop time
                    $sorted_data['last_drop_time'] = ($single_data->status == $this->Statuses['completed'] && !is_null($single_data->created_at)) ? ConvertTimeZone($single_data->created_at,'UTC','America/Toronto') : $sorted_data['last_drop_time'];

                    // data of completed drops and returns
                    $sorted_data['completed_and_return'][$single_data->task_id] = $single_data->status;
                }

            }

            // getting count values
            $count_values =  array_count_values($sorted_data['completed_and_return']);

            if(isset($count_values[$this->Statuses['completed']]))
            {
                // getting all completed orders task ids
                foreach ($sorted_data['completed_and_return'] as $index => $data) {
                    if ($data == $this->Statuses['completed']) {
                        $sorted_data['completed_drops_task_ids'][$index . '-' . $data] = $index;
                    }
                }


                // calculating actual km
                $actual_km = JoeyRouteLocation::whereIn('task_id', $sorted_data['completed_drops_task_ids'])->sum('distance');
                $sorted_data['actual_total_km'] = round($actual_km / 1000, 2);
            }


            // updating values of counts
            $this->JoeyPickUpOrders =  count($sorted_data['pickup_sort']);
            $this->JoeyCompletedDrops = (isset($count_values[$this->Statuses['completed']])) ?  $count_values[$this->Statuses['completed']]:0;
            $this->JoeyReturnDrops = (isset($count_values[$this->Statuses['return']])) ?  $count_values[$this->Statuses['return']]:0;
            $this->JoeyFirstPickupTime = $sorted_data['first_pickup_time'];
            $this->JoeyFirstDropTime = $sorted_data['first_drop_time'];
            $this->JoeyLastDropTime = $sorted_data['last_drop_time'];
            $this->JoeyActualTotalKM = $sorted_data['actual_total_km'];
            $this->IsCalculationDoneByJoey = true;

        }

    }

    /**
     * Get Total Numbers Of Orders Completed in this Route by this joey .
     */
    public function JoeyTotalOrderDropsCompletedCount($optional_filters = [])
    {
        // trigger
        $this->PayoutQueryCalculatoinByJoey($optional_filters);
        return $this->JoeyCompletedDrops;
    }

    /**
     * Get Total Numbers Of Orders Picked in this Route by this joey .
     */
    public function JoeyTotalOrderPickedCount($optional_filters = [])
    {
        // trigger
        $this->PayoutQueryCalculatoinByJoey($optional_filters);
        return $this->JoeyPickUpOrders;
    }

    /**
     * Get Total Numbers Of Orders Unattempted in this Route this joey.
     */
    public function JoeyTotalOrderReturnCount($optional_filters = [])
    {
        // trigger
        $this->PayoutQueryCalculatoinByJoey($optional_filters);
        return $this->JoeyReturnDrops;
    }

    /**
     * Get Total Numbers Of Orders Unattempted in this Route this joey.
     */
    public function JoeyTotalOrderUnattemptedCount($optional_filters = [])
    {
        // trigger
        $this->PayoutQueryCalculatoinByJoey($optional_filters);
        return $this->TotalOrderDropsCount() - ($this->JoeyCompletedDrops + $this->JoeyReturnDrops);
    }

    /**
     * Get FirstPickUpScan current joey data.
     */
    public function JoeyFirstPickUpScan($optional_filters = [])
    {
        // trigger
        $this->PayoutQueryCalculatoinByJoey($optional_filters);
        return $this->JoeyFirstPickupTime;
    }

    /**
     * Get  FirstDropScan current joey data.
     */
    public function JoeyFirstDropScan($optional_filters = [])
    {
        // trigger
        $this->PayoutQueryCalculatoinByJoey($optional_filters);
        return $this->JoeyFirstDropTime;
    }

    /**
     * Get  LastDropScan current joey data .
     */
    public function JoeyLastDropScan($optional_filters = [])
    {
        // trigger
        $this->PayoutQueryCalculatoinByJoey($optional_filters);
        return $this->JoeyLastDropTime;
    }

    /**
     * Get Time Of TotalKM Scan Of Order in this Route of this joey .
     */
    public function JoeyActualTotalKM($optional_filters = [])  //calculate ActualTotalKM Route Total KM
    {
        // trigger
        $this->PayoutQueryCalculatoinByJoey($optional_filters);
        return $this->JoeyActualTotalKM;
    }
}
