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

class CreateOrderResource extends JsonResource
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


        /*
        * for remaining time=duration start
        * */
        // $startTime=SprintTasks::where('sprint_id',$this->id)->where('type','=','pickup')->first('due_time');
    if(!isset($this->is_new_task)){
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
//            dd($valueBrakeDown);

            $valueBrakeDown[0]=$valueBrakeDown[0]??0;
            $valueBrakeDown[1]=$valueBrakeDown[1]??0;
            $valueBrakeDown[2]=$valueBrakeDown[2]??0;
            $duration = $valueBrakeDown[0]. ' Hrs ' . $valueBrakeDown[1]. ' Min ' . $valueBrakeDown[2]. ' Sec';
        }else{

            $duration='0';
        }

    }

       /*
         * for remaining time=duration end
        */

        /**
         * get all details of task of current sprint
         */
        $tasksanddetails=[];
        $tasks=$this->sprintTask;
        $validtasks=[];
        $totaltaskcharge=0;
        foreach ($tasks as $taskvalue) {

            $validtasks[]=$taskvalue->type??null;

            $totaltaskcharge+=($taskvalue->payment_service_charge!=null)?$taskvalue->payment_service_charge:0;
            $totaltaskcharge+=($taskvalue->charge!=null)?$taskvalue->charge:0;
            $totaltaskcharge+=($taskvalue->merchant_charge!=null)?$taskvalue->merchant_charge:0;
            $totaltaskcharge+=($taskvalue->weight_charge!=null)?$taskvalue->weight_charge:0;
            $totaltaskcharge+=($taskvalue->staging_charge!=null)?$taskvalue->staging_charge:0;
            $num='';
            $contact=[];
            $location=[
                'coordinates'=>[
                    "lat"=>($taskvalue->Location->latitude!=null)?((int)(floor(log10(abs($taskvalue->Location->latitude))-log10(abs(1000000))) + 1)==2)?(float)substr($taskvalue->Location->latitude,0,7)/1000000:(float)substr($taskvalue->Location->latitude,0,7)/100000:"",
                    "lng"=>($taskvalue->Location->longitude!=null)?((int)(floor(log10(abs($taskvalue->Location->longitude))-log10(abs(1000000))) + 1)==2)?(float)substr($taskvalue->Location->longitude,0,8)/1000000:(float)substr($taskvalue->Location->longitude,0,8)/100000:"",

                    // "lat"=>($taskvalue->Location->latitude!=null)?(float)$taskvalue->Location->latitude/1000000:"",
                    // "lng"=>($taskvalue->Location->longitude!=null)?(float)$taskvalue->Location->longitude/1000000:""
                ],
                "address_components"=>[
                    [
                        "code"=> null,
                        "name"=> $taskvalue->Location->address??null,
                        "type"=> "address"
                    ],
                    [
                        "code"=> null,
                        "name"=> $taskvalue->Location->postal_code??null,
                        "type"=> "postal_code"
                    ],
                    [
                        "code"=> $taskvalue->Location->City->name??null,
                        "name"=> $taskvalue->Location->City->name??null,
                        "type"=> "city"
                    ],
                    [
                        "code"=> $taskvalue->Location->Country->code??null,
                        "name"=> $taskvalue->Location->Country->name??null,
                        "type"=> "country"
                    ],
                    [
                        "code"=> null,
                        "name"=> $taskvalue->Location->buzzer??null,
                        "type"=> "buzzer"
                    ],
                    [
                        "code"=> null,
                        "name"=> $taskvalue->Location->suite??null,
                        "type"=> "suite"
                    ],
                    [
                        "code"=> $taskvalue->Location->State->code??null,
                        "name"=> $taskvalue->Location->State->name??null,
                        "type"=> "division"
                    ]
                    ],
                    "type"=>$taskvalue->Location->type??null

            ];
            $confirmation=[];
            $sprint_confirmations=$taskvalue->sprintConfirmation;
            if(!empty($sprint_confirmations)){
                // $confiramtioncount=0;
                foreach($sprint_confirmations as $sprint_confirmation){
                    $confirmation[]=[

                                    "id"=> $sprint_confirmation->id??null,
                                    "name"=>  $sprint_confirmation->name??null,
                                    "title"=>  $sprint_confirmation->title??null,
                                    "description"=>  $sprint_confirmation->description??null,
                                    "inputType"=>  $sprint_confirmation->input_type??null,
                                    "confirmed"=>  ($sprint_confirmation->confirmed==1)?true:false,
                                    "imageUrl"=>  $sprint_confirmation->attachment_path??null,
                                    "action_resource"=> [
                                        "url"=> "https://api.staging.joeyco.com/task-confirmations/".$sprint_confirmation->id,
                                        "method"=> "PUT"
                                    ]
                    ];
                    // $confiramtioncount++;
                }
            }
            $time=[

                    "due"=> [
                        "unix"=> $taskvalue->due_time,
                        "iso8601"=> date(DATE_ISO8601, strtotime(date("Y-m-d H:i:s", substr(($taskvalue->due_time == null) ? 0 : $taskvalue->due_time, 0, 10)))),
                        "timezone_abbreviation"=> "EST"
                    ],
                    "eta"=> [
                        "unix"=> $taskvalue->eta_time,
                        "iso8601"=> date(DATE_ISO8601, strtotime(date("Y-m-d H:i:s", substr(($taskvalue->eta_time == null) ? 0 : $taskvalue->eta_time, 0, 10)))),
                        "timezone_abbreviation"=> "EST"
                    ],
                    "etc"=> [
                        "unix"=> $taskvalue->etc_time,
                        "iso8601"=> date(DATE_ISO8601, strtotime(date("Y-m-d H:i:s", substr(($taskvalue->etc_time == null) ? 0 : $taskvalue->etc_time, 0, 10)))),
                        "timezone_abbreviation"=> "EST"
                    ]

                ];
//            dd('asd');


            if($taskvalue->type=='pickup'){
                $num="CR-".$this->id."-A";
                // for vendor
                // $contact=['id'=>$taskvalue->contact_id,'name'=>$taskvalue->vendorcontact->first_name.' '.$taskvalue->vendorcontact->last_name,'phone'=>$taskvalue->vendorcontact->phone,'email'=>$taskvalue->vendorcontact->email];
            }
            elseif($taskvalue->type=='dropoff'){
                $c=(int)$taskvalue->ordinal-1;
                $num="CR-".$this->id.'-'.$c;
                // $contact=['id'=>$taskvalue->contact_id,'name'=>$taskvalue->sprintContact->name,'phone'=>$taskvalue->sprintContact->phone,'email'=>$taskvalue->sprintContact->email];
            }
            $contact=['id'=>$taskvalue->contact_id,'name'=>$taskvalue->sprintContact->name??null,'phone'=>$taskvalue->sprintContact->phone??null,'email'=>$taskvalue->sprintContact->email??null];


            $tasksanddetails[]=array(
                "id"=> $taskvalue->id??null,
                "num"=> $num,
                "pin"=> $taskvalue->pin??null,
                "type"=> $taskvalue->type??null,
                "copy"=> $taskvalue->description??null,
                "confirm_signature"=> ($taskvalue->confirm_signature==1)?true:false,
                "confirm_pin"=> ($taskvalue->confirm_pin==1)?true:false,
                "confirm_image"=>( $taskvalue->confirm_image==1)?true:false,
                "confirm_seal"=>( $taskvalue->confirm_seal==1)?true:false,
                "due_time"=> $taskvalue->due_time??null,
                "etc_time"=> $taskvalue->etc_time??null,
                "eta_time"=> $taskvalue->eta_time??null,
                "sprint"=> array(
                    "id"=> $this->id
                ),
                "status"=> array(
                    "id"=> $taskvalue->status_id,
                    "copy"=>  StatusMap::getDescription($taskvalue->status_id)
                ),
                "contact"=> $contact,
                "location"=>$location,
                "notify_by"=> $taskvalue->notify_by??null,
                "payment"=> $taskvalue->payment_amount??null,
                "charge"=> $taskvalue->charge??null,
                "payment_service_charge"=>$taskvalue->payment_service_charge??null,
                "weight_charge"=>$taskvalue->weight_charge??null,
                "weight"=>$taskvalue->weight_estimate??null,
                "confirmations"=> $confirmation,
                "time"=> $time,
                "items"=> [],
                "merchant_order_num"=>$taskvalue->merchantIds->merchant_order_num??null,
                "end_time"=>$taskvalue->merchantIds->end_time??null,
                "start_time"=>$taskvalue->merchantIds->start_time??null,
                "address_line2"=>$taskvalue->merchantIds->address_line2??null
            );
        }

        $distancecharge=$this->distance_charge??0;
        $subtotal=$distancecharge+$totaltaskcharge;
        $tax=$this->tax??0;
        $tip=$this->tip??0;
        $total=$subtotal+$tax+$tip;
        $credit_amount=$this->credit_amount??0;
        $grand_total=$total+$credit_amount;



        $history=[];
        $histories=$this->sprintHistory;
        $count=0;
        foreach ($histories as $historyvalue) {
            $history[$count]['code']=$historyvalue->status_id;
            $history[$count]['description']=StatusMap::getDescription($historyvalue->status_id);
            $history[$count]['timestamp']=strtotime($historyvalue->date);
            $count++;
        }
        // $validtasks;
        if(isset($this->is_new_task)){
            $tasksanddetails=end($tasksanddetails);
        }
        return [
            'id' => $this->id??null,
            "num"=> "CR-".$this->id??null,
            'status' => [
                'id' => $this->status_id??null,
                'description' => StatusMap::getDescription($this->status_id)
            ],
            "editable"=> true,
            'distance' => $this->distance??null,
            "distance_allowance"=> $this->distance_allowance??null,
            "optimized"=> ($this->optimize_route==1)?true:false,
            "valid_tasks"=>  $validtasks??null,
            "vehicle_id"=> $this->vehicle_id??null,
            "only_vehicle"=> ($this->only_this_vehicle==1)?true:false,
            "active"=> ($this->active==1)?true:false,
            "isSameday"=> ($this->is_sameday==1)?true:false,
            "duration"=> array(
                "elapsed"=> 0,
                "eta"=> $this->sprintPickupTask->due_time
            ),
            "remaintime"=> $duration??null,
            "due_time"=> $this->task->due_time??null,
            "joey"=> null,
            "history" => $history??null,
            "meta" => [],
            "distance_charge" =>$distancecharge,
            "total_task_charge" => $totaltaskcharge,
            "subtotal" => $subtotal,//distance charge + total_task_charge
            "tax" => $tax,
            "tip" => $tip,
            "total" =>  $total,// distance charge + total task charge + tax + tip
            "credit_amount" => $credit_amount,
            "grand_total" => $grand_total,
            "tasks"=>$tasksanddetails


        ];
    }
}
