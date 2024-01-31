<?php

namespace App\Http\Resources;


use App\Models\Joey;

use App\Models\Location;
use App\Models\MerchantsIds;
use App\Models\SprintTasks;
use App\Models\SprintTaskHistory;
use App\Models\FinancialTransactions;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class JoeyOrderDetailResource extends JsonResource
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
/*         $status = StatusMap::getDescription($this->status_id);*/

        $now = Carbon::now();
        $joeyDetails=Joey::where('id',$this->joey_id)->first();

        $sprintTask=SprintTasks::where('sprint_id',$this->id)->where('type','=','dropoff')->first();
        $location=Location::where('id',$sprintTask->location_id)->first();
        $mercahntIds=MerchantsIds::where('task_id',$sprintTask->id)->first();

        $associatedShift = FinancialTransactions::getAssociatedShift($this->id, $this->joey_id);

        $taskAcceptedJoey=SprintTaskHistory::where('status_id',32)->where('sprint_id',$this->id)->first();
        $taskCompleted=SprintTaskHistory::whereIn('status_id',[17,18,145])->where('sprint_id',$this->id)->OrderBy('date','DESC')->first();
        $startTime = Carbon::createFromTimestamp(strtotime($taskAcceptedJoey->date))??'';
        $endTime = Carbon::createFromTimestamp(strtotime($taskCompleted->date))??'';

          $interval = $endTime->diff($startTime)->format('%H:%I:%S');

            if(empty($this->distance)){
                $distance=$this->distance;
            }else{
                $distance=$this->distance/1000;
            }
        return [
            'id' => $this->id,
            'order_num'=>'CR-'.$this->id??'',
            'date' => $this->created_at->format('d/m/Y')??'',
            'distance' => $distance??'',
            'time'=> $this->created_at->format('g:i A'),
            'start_time'=>$mercahntIds->start_time??'',
            'end_time'=>$mercahntIds->end_time??'',
            'duration'=>$interval,
            'credit_amount' => round($this->joey_pay, 2)??'',
            'tax_amount' => round($this->joey_tax_pay, 2)??'',
            'work_type'=>($associatedShift)? 'SH-'.$associatedShift : '',
            'address'=>$location->address??'',
            'payment'=>'',
            'date_time' => $this->created_at->format('d/m/Y H:i:s')??''


        ];
    }
}
