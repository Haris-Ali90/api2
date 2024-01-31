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

class JoeyNewOrderResource extends JsonResource
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
         $status = StatusMap::getDescription($this->status_id);


         $startTime =SprintTaskHistory::where('sprint__tasks_history.sprint_id','=',$this->id)->whereIn('status_id',[15])->first();

         if(!empty($startTime)){
             $tasks= SprintTasks::where('sprint_id',$this->id)->where('type','=','dropoff')->get();
             foreach ($tasks as $task){
 
                 $confirmation=SprintConfirmation::where('task_id',$task->id)
                     ->where('confirmed',0)
                     ->count();
                 if($confirmation>0){
                    $time=$task->due_time;
                     
                 }else{
 
                    continue;
                 }
             }
         }else{
             $task=SprintTasks::where('sprint_id',$this->id)->where('type','=','pickup')->first('due_time');
             // $time=$task->due_time_converted->format('H:m:s');
             $time=$task->due_time;
         }
 
                     /*
              * for duration
              * */
         //$startTime=SprintTasks::where('sprint_id',$this->id)->where('type','=','pickup')->first('eta_time');
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
 
 
         /* difference for duration calculation  */
         $differenceTimeConversion;
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


        $now = Carbon::now();
        return [
            'id' => $this->id,
            'status' => [
                'id' => $this->status_id,
                'description' => $status
            ],
            'distance' => $this->distance/1000,
            'time'=> $time,
            'duration'=> $duration,
            'distance_allowance'=> $this->distance_allowance??0,
            'is_completed'=>0,
            'vehicle_id' => $this->vehicle_id,
            'only_vehicle'=> $this->only_this_vehicle,
            'active' => $this->active,
//            'duration'=>[
//                'elapsed'=> $this->duration
//            ],
            'due_time'=>  $this->dueDate ??'',

            'joey' =>[
                'id' => null,
                'name' => ''
            ],
            'history'=> SprintTaskHistoryResource::collection($this->sprintHistory),
            'meta'=>[
                'links' =>[
                'take' => [
                    'link' => $request->url('/sprints/' . $this->id . '/joey'),
                    'method' => 'PUT',
                ]],
                ],
            'distance_charge' =>round($this->distance_charge, 2),
            
            'total_task_charge'=> round($this->totalTaskCharge, 2)??'',

            'subtotal' => round($this->subtotal, 2)??'',
            'tax'=> round($this->tax, 2),
            'tip' => round($this->tip, 2)??'',
            'total'=>round($this->total, 2),
            'credit_amount' => round($this->credit_amount, 2),
            'grand_total' => round($this->total + $this->make_payment_total, 2),
            'task'=>SprintTaskResource::Collection($this->sprintTask,$this->status)

        ];
    }
}
