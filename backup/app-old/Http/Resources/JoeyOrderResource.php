<?php

namespace App\Http\Resources;


use Illuminate\Http\Resources\Json\JsonResource;

class JoeyOrderResource extends JsonResource
{


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

        return [
            'id' => $this->id,
            // 'num'=> $this->num,
            'status' => [
                'id' => $this->status_id??'',
                'description' => $this->status_copy??''
            ],
            'distance' => $this->distance??'',
            'distance_allowance'=> $this->distance_allowance??'',
            'vehicle_id' => $this->vehicle_id??'',
            'only_vehicle'=> $this->only_this_vehicle??'',
            'active' => $this->active??'',
            'duration'=>[
                'elapsed'=>$this->orderDuration??'',
                'eta'=> $this->eta??''
            ],
            'due_time'=>  $this->dueDate ??'',
            'joey' =>[
                'id' => $this->joey->id??'',
                'name' => $this->joey->nickname??''
            ],
            'history'=> [array([
                'id' => $this->status_id??'',
                'name' => $this->status??''
            ])],
            'distance_charge' =>round($this->distance_charge, 2),
            'total_task_charge'=> round($this->totalTaskCharge, 2)??'',



            'subtotal' => round($this->subtotal, 2)??'',
            'tax'=> round($this->tax, 2),
            'tip' => round($this->tip, 2)??'',
            'total'=>round($this->total, 2),
            'credit_amount' => round($this->credit_amount, 2),
            'grand_total' => round($this->total + $this->make_payment_total, 2),







        ];
    }
}
