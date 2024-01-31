<?php

namespace App\Models;
use App\Models\Interfaces\JoeyRouteLocationInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class JoeyRouteLocation extends Model implements JoeyRouteLocationInterface
{

    public $table = 'joey_route_locations';



    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','route_id','ordinal','task_id','arrival_time',
        'finish_time','distance'
    ];

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

    public  function sprintTask(){

        return $this->belongsTo(SprintTasks::class,'task_id','id')
            ->whereNotIn('sprint__tasks.status_id',[145,105,111,17,113,114,116,117,118,132,138,139,144,141,36]);
    }

    public  function joeyRoute(){

        return $this->belongsTo(JoeyRoutes::class,'route_id','id');
    }
    public  function sprintTaskAgainstRouteLocationId(){
        return $this->belongsTo(SprintTasks::class,'task_id','id');
    }
    public  function merchant(){
        return $this->belongsTo(MerchantsIds::class,'task_id','task_id');
    }

    public static function getDistanceSum($routeId)
    {
        $distance = 0;
        $routeLocations = JoeyRouteLocation::where('route_id', $routeId)->get();
        foreach($routeLocations as $location){
            $distance += $location->distance;
        }
        return $distance;
    }

    /**
     * Get This Route Total Distance calculation
     */
    public function SelfDataByRouteID()
    {
        return $this->hasMany( self::class,'route_id', 'route_id');
    }

    /**
     * Get This Route Total Distance calculation
     */
    public function GetRoutificEstimatedTotalTime($force_recalculate = false)
    {
        $routific_estimated_total_time_responce = $this->routific_estimated_total_time_responce;

        // the difference is already calculated
        if($force_recalculate == false && $routific_estimated_total_time_responce['is_updated'] == true)
        {
            //return $routific_estimated_total_time_responce;
        }

        $data  = $this->SelfDataByRouteID->sortBy('ordinal')->toArray();
        // checking the data exist
        $start_time = '';
        $end_time = '';
        $array_count = count($data);
        $last_index = $array_count - 1;
        $current_data = date('Y-m-d');
        $diff_time = '00:00:00';
        if($array_count > 0)
        {
            $start_time =   $data[0]['arrival_time'];
            $end_time = $data[$last_index]['finish_time'];
            $diff_time = DifferenceTwoDataTime(
                $current_data.' '.$start_time.":00",
                $current_data.' '.$end_time.":00"
            );

            $this->routific_estimated_total_time_responce['total_time'] = $diff_time;
            $this->routific_estimated_total_time_responce['start_time'] = $start_time;
            $this->routific_estimated_total_time_responce['end_time'] = $start_time;
        }


        // updating data
        $this->routific_estimated_total_time_responce['is_updated']  = true;
        return $this->routific_estimated_total_time_responce;




    }

    public function managerSprintTask()
    {
        return $this->belongsTo(SprintTasks::class,'task_id','id')/*->whereNull('sprint__tasks.deleted_at')*/;
    }

    public  function managerJoeyRoute(){

        return $this->belongsTo(JoeyRoutes::class,'route_id','id')->where('mile_type', 3);
    }

    public static  function getDurationOfRoute($id)
    {
        $data=self::where('route_id','=',$id)->whereNull('deleted_at')->orderby('id');
        //$first_element=" 11:30:00";
        $first_element=$data->first();

        $last_element=self::where('route_id','=',$id)->whereNull('deleted_at')->orderby('id','DESC')->first();

        //" 12:30:00";
        //$data->last();
        if(strpos($first_element->arrival_time,'-') && strpos($first_element->arrival_time,'T'))
        {
            $arrival_time=explode("T",$first_element->arrival_time);
            $last_element=explode("T",$last_element->finish_time);
            $arrival_time=$arrival_time[0]." ".explode("-",$arrival_time[1])[0];
            $last_element=$last_element[0]." ".explode("-",$last_element[1])[0];
            $date1= new \DateTime($arrival_time);
            $date2 = new \DateTime($last_element);
            $interval = $date1->diff($date2);
            // echo($interval->h.":".$interval->i."".$interval->s);
            return $interval->format("%H:%I:%S");

        }
        if(!empty($first_element) && !empty($last_element))
        {

            $arrival_time=explode(":",$first_element->arrival_time);
            $finish_time=explode(":",$last_element->finish_time);
            if(isset($arrival_time[1]) && isset($finish_time[1]))
            {

                $duration[0]=$finish_time[0]-$arrival_time[0];
                $duration[1]=$finish_time[1]-$arrival_time[1];
                if($duration[1]<0)
                {
                    $duration[0]--;
                    $duration[1]=60+$duration[1];
                }
                if($duration[0]<10)
                {
                    $duration[0]="0".$duration[0];
                }
                if($duration[1]<10)
                {
                    $duration[1]="0".$duration[1];
                }

                return $duration[0].':'.$duration[1].':00';
            }





        }
        return 0;

    }

}
