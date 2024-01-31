<?php

namespace App\Http\Resources;

use App\Models\ExclusiveOrderJoeys;
use App\Models\Interfaces\ExclusiveOrderJoeysInterface;
use App\Models\Sprint;
use App\Models\SprintConfirmation;
use App\Models\SprintTaskHistory;
use App\Models\SprintTasks;
use App\Models\StatusMap;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class JoeySprintOnlyResource extends JsonResource
{

    private $duration;

    public function __construct($resource)
    {
        parent::__construct($resource);


    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $time='';
        // $status = StatusMap::getDescription($this->status_id);
        /*
         * for time
         * */
        // $startTime =SprintTaskHistory::where('sprint__tasks_history.sprint_id','=',$this->id)->whereIn('status_id',[15,28])->first();
        $startTime =$this->sprintHistoryPickup;
        // print_r($startTime);die;
        if(!empty($startTime)){
            $task= $startTime->sprintTaskDropoffLocationId;
            // print_r($task);die;

            // $tasks= SprintTasks::where('sprint_id',$this->id)->where('type','=','dropoff')->get();
        //    $tasks=$this->mulipleSprintLastDropOffTask;
        //     foreach ($tasks as $task){
        //     //    $confirmation=SprintConfirmation::where('task_id',$task->id)
        //     //        ->where('confirmed',0)
        //     //        ->count();
        //       $confirmation= $task->countForConfirmationAgaintTaskId->count();

        //         if($confirmation>0){
        //             $time=$task->etc_time;

        //         }else{

        //             continue;
        //         }
        //     }
        $time=$task->etc_time;
        // if(!empty($task)){
        //     $time=$task->etc_time;
        // }

        }else{
            // $task=SprintTasks::where('sprint_id',$this->id)->where('type','=','pickup')->first();
            $task= $this->sprintPickupTask;
            // $time=$task->due_time_converted->format('H:m:s');
            $time=$task->due_time;
        }
        // echo $time;die;

                    /*
             * for duration
             * */
        // $startTime=SprintTasks::where('sprint_id',$this->id)->where('type','=','pickup')->first('due_time');
        if(isset($this->sprintPickupTask->due_time)){
            $startTime=$this->sprintPickupTask->due_time;
        }else{
            $startTime=null;
        }
        // $endTimeArray=SprintTasks::where('sprint_id',$this->id)->where('type','=','dropoff')->orderBy('ordinal', 'desc')->first('etc_time');
        if(isset($this->sprintLastDropOffTask->etc_time)){
            $endTimeArray=$this->sprintLastDropOffTask->etc_time;
        }else{
            $endTimeArray=null;
        }
        // echo  $startTime.'/'.$endTimeArray;die;


        /* difference for duration calculation  */
        $differenceTimeConversion='';
        if(isset($startTime) && isset($endTimeArray)  ){

            $endTime=$endTimeArray;
            $difference= $endTime - $startTime;
            $differenceTimeConversion=date('H:i:s', $difference);
        }else{

            $differenceTimeConversion='0';
        }


        if(isset($differenceTimeConversion)) {
            $valueBrakeDown = explode(':', $differenceTimeConversion);
            $duration = $valueBrakeDown[0] . ' Hrs ' . $valueBrakeDown[1] . ' Min ' . $valueBrakeDown[2] . ' Sec';
        }else{
            $duration='0';
        }
        $locations=[];
        $sprinttasks=$this->sprintTask;
        if (!empty($sprinttasks)) {
            $countforindex=0;
            foreach ($sprinttasks as $sprinttask) {
                $locations[$countforindex]['task_id']=$sprinttask->id??'';
                $locations[$countforindex]['type']=$sprinttask->type??'';
                $locations[$countforindex]['location']=$sprinttask->Location->address??'';
                $locations[$countforindex]['latitude']=(isset($sprinttask->Location->latitude))?(float)($sprinttask->Location->latitude/1000000):'';
                $locations[$countforindex]['longitude']=(isset($sprinttask->Location->longitude))?(float)($sprinttask->Location->longitude/1000000):'';

                $countforindex++;
            }
        }




        return [
            'id' => $this->id,
            'distance' => $this->distance/1000,
            'time'=> $time,
            'duration'=>$duration,
            'distance_allowance'=> $this->distance_allowance??0,
            'vehicle_id' => $this->vehicle_id??0,
            'distance_charge' =>round($this->distance_charge, 2)??0,
            'total_task_charge'=> round($this->task_total, 2)??0,
            'subtotal' => round($this->subtotal, 2)??0,
            'tax'=> round($this->tax, 2)??0,
            'tip' => round($this->tip, 2)??0,
            'total'=>round($this->total, 2)??0,
            'credit_amount' => round($this->credit_amount, 2)??0,
            'grand_total' => round($this->total + $this->make_payment_total, 2)??0,
            'locations'=>$locations,

        ];
    }
}
