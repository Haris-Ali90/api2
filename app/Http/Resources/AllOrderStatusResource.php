<?php

namespace App\Http\Resources;

use App\Classes\RestAPI;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;
use App\Http\Traits\BasicModelFunctions;
use App\Models\SprintTaskHistory;
use Illuminate\Support\Facades\DB;

class AllOrderStatusResource extends JsonResource
{
    use BasicModelFunctions;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {



        $status = $this->getStatusCodesWithKey('status_labels.'.$this->status_id);

        $response = SprintTaskResource::collection($this->sprintTask);
        $task = RestAPI::response($response, true, "Task History");

        if($this->optimize_route == 0){
            $optimize_route = false;
        }else{
            $optimize_route = true;
        }

        return [
            'id'=> $this->id,
            'num'=> 'CR-'.$this->id,
            'editable'=> "",
            'status'=> [
                'id'=>$this->status_id,
                'description'=> $status
            ],
            'distance'=> $this->distance,
            'distance_allowance'=> $this->distance_allowance,
            'vendor_pin' =>0,
            'optimized' => $optimize_route,
            'valid_tasks' => [
                'pickup',
                'dropoff'
            ],
            'vehicle_id'=> $this->vehicle_id,
            'only_vehicle' => "",
            'active'=> $this->active,
            'duration'=>[
                'elapsed'=>"",
                'eta'=>""
            ],
            'remaintime'=>"",
            'due_time'=> $this->task['due_time'],
            'joey'=>[
                'id'=>$this->joey_id,
                'name'=>$this->joey->display_name ?? ''
            ],
            'history'=>SprintTaskHistoryResource::Collection($this->sprintTask,$this->status),
            'distance_charge'=> $this->distance_charge,
            'total_task_charge'=>"",
            'subtotal'=> $this->subtotal,
            'tax'=> $this->tax,
            'tip'=> $this->tip,
            'make_payment_total'=> $this->make_payment_total,
            'collect_payment_total'=> $this->collect_payment_total,
            'total'=> $this->total,
            'grand_total'=> $this->total,
            'cost'=>[
                'name'=>'Grand Total',
                'code'=>'grand_total',
                'value'=> ""
            ],
            'task'=>SprintTaskResource::Collection($this->sprintTask,$this->status),
            'type'=>'custom-run',
            'is_sameday'=> $this->is_sameday,
            'timestamp'=> "",
    ];


    }
}
