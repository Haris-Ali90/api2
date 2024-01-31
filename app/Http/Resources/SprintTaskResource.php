<?php

namespace App\Http\Resources;

use App\Models\Notification;
use App\Models\SprintConfirmation;
use App\Models\StatusMap;
use App\Models\Sprint;
use Illuminate\Http\Resources\Json\JsonResource;

class SprintTaskResource extends JsonResource
{
    public $resource;
    public $status;
    public $contact;
    public function __construct($resource,$status)
    {
        parent::__construct($resource);

        $this->resource=$resource;
        $this->status=$status;

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

        $confirmation=SprintConfirmation::where('task_id',$this->id)
            //->where('confirmed','!=',0)
            ->where('confirmed',0)
            ->count();

            if(!empty($confirmation)){
                if($confirmation>0){
                    $active=0;
                }else{
                    $active=1;
                }
            }


        $flag=0 ;

        if($this->type!='dropoff') {
            $flag = 1;
            $contactDetails = $this->sprintContact;
        }else {
            $contactDetails = $this->sprintContact;
            if (isset($this->merchantIds)) {
                if ($this->merchantIds->is_contactless_delivery != 1) {
                    $flag = 0;
                }
            }
        }
        $flag =1;
        $position=0;
        $ordinal='';
        $status_task_history=[];
        $is_key='';
        $is_value=0;
        if ($this->type=='pickup') {
            $position='A';
              $status_task_history=[15,28];
            $is_key='is_pick';
            $is_value=(empty($this->sprintTaskHistoryforAtPickup))?0:1;

        }
        elseif ($this->type=='dropoff') {
            $ordinal=$this->ordinal-1;
            $status_task_history=[113,114,116,117,118,132,138,139,144,101,104,105,106,107,108,109,110,111,112,131,135,136];
            $is_key='is_drop';
            $is_value=(empty($this->sprintTaskHistoryforAtDropOff))?0:1;

            $sprint = Sprint::where('id', $this->sprint_id)->first();

            if($sprint->optimize_route == 0){
                $position = $sprint->getSerialNumber($this->id);
            }
            if($sprint->optimize_route == 1){
                $position = $ordinal;
            }
        }
        elseif ($this->type=='return') {
            $position='RE';
             $status_task_history=[145];
            $is_key='is_return';

        }
        $is_complete=$this->sprintTaskHistoryStatus($status_task_history,$this->id);





        return [
            'id' => $this->id??'',
            'num'=> 'CR-'.$this->sprint_id.'-'.$position,
            'type' => $this->type??'',
            'is_active'=>$active??1,
            'tracking_code' => $this->sprintTaskTrackinCodes->code??'',
            'confirm_signature'=> $this->confirm_signature??'',
            'confirm_pin' => $this->confirm_pin??'',
            'confirm_image'=> $this->confirm_image??'',
            'confirm_seal' => $this->confirm_seal??'',
            'due_time'=> $this->due_time??'',
            'etc_time' => $this->etc_time??'',
            'eta_time'=> $this->eta_time??'',
            'is_completed'=>$is_complete,
            $is_key=> $is_value,
            'sprint' => [
                'id'=>$this->sprint_id??''
            ],
            'status' => [
                'id' => $this->status_id,
                'description' => $status
            ],

             'contact'=>$flag?[new SprintContactResource($contactDetails)]:[[
                 'name'=> $contactDetails->name,
                 'phone' => '',
                 'email' => '',
             ]],
             'weight_charge'=>$this->weight_charge,
             'weight'=>[
                 'value'=>0,
                 'unit'=>'kg',
             ],
             'confirmation'=>SprintConfirmationResource::collection($this->sprintConfirmation),
         /*   'confirmation'=>[new SprintConfirmationResource($this->sprintConfirmation)],*/
             'Location'=>[new LocationResource($this->Location)],
               'merchant_order_num'=>$this->merchantIds->merchant_order_num??'',
               'start_time'=>$this->merchantIds->start_time??'',
               'end_time'=>$this->merchantIds->end_time??'',
               'description' => $this->description??'',
               'buzzer' => (!empty($this->Location))?$this->Location->buzzer??'':'',
               'pin' => $this->pin??'',
                'address_line2' => $this->merchantIds->address_line2??''

        ];
    }
}
