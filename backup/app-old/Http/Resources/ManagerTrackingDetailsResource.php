<?php

namespace App\Http\Resources;

use App\Models\Sprint;
use App\Models\SprintConfirmation;
use App\Models\SprintReattempt;
use App\Models\SprintTaskHistory;
use App\Models\SprintTasks;
use App\Models\StatusMap;
use App\TaskHistory;
use Illuminate\Http\Resources\Json\JsonResource;

class ManagerTrackingDetailsResource extends JsonResource
{


    public function __construct($resource)
    {

        parent::__construct($resource);
        $this->resource=$resource;

    }
    public function statusmap($id)
    {
        $statusid = array("136" => "Client requested to cancel the order",
            "137" => "Delay in delivery due to weather or natural disaster",
            "118" => "left at back door",
            "117" => "left with concierge",
            "135" => "Customer refused delivery",
            "108" => "Customer unavailable-Incorrect address",
            "106" => "Customer unavailable - delivery returned",
            "107" => "Customer unavailable - Left voice mail - order returned",
            "109" => "Customer unavailable - Incorrect phone number",
            "142" => "Damaged at hub (before going OFD)",
            "143" => "Damaged on road - undeliverable",
            "144" => "Delivery to mailroom",
            "103" => "Delay at pickup",
            "139" => "Delivery left on front porch",
            "138" => "Delivery left in the garage",
            "114" => "Successful delivery at door",
            "113" => "Successfully hand delivered",
            "120" => "Delivery at Hub",
            "110" => "Delivery to hub for re-delivery",
            "111" => "Delivery to hub for return to merchant",
            "121" => "Out for delivery",
            "102" => "Joey Incident",
            "104" => "Damaged on road - delivery will be attempted",
            "105" => "Item damaged - returned to merchant",
            "129" => "Joey at hub",
            "128" => "Package on the way to hub",
            "140" => "Delivery missorted, may cause delay",
            "116" => "Successful delivery to neighbour",
            "132" => "Office closed - safe dropped",
            "101" => "Joey on the way to pickup",
            "32" => "Order accepted by Joey",
            "14" => "Merchant accepted",
            "36" => "Cancelled by JoeyCo",
            "124" => "At hub - processing",
            "38" => "Draft",
            "18" => "Delivery failed",
            "56" => "Partially delivered",
            "17" => "Delivery success",
            "68" => "Joey is at dropoff location",
            "67" => "Joey is at pickup location",
            "13" => "Waiting for merchant to accept",
            "16" => "Joey failed to pickup order",
            "57" => "Not all orders were picked up",
            "15" => "Order is with Joey",
            "112" => "To be re-attempted",
            "131" => "Office closed - returned to hub",
            "125" => "Pickup at store - confirmed",
            "61" => "Scheduled order",
            "37" => "Customer cancelled the order",
            "34" => "Customer is editting the order",
            "35" => "Merchant cancelled the order",
            "42" => "Merchant completed the order",
            "54" => "Merchant declined the order",
            "33" => "Merchant is editting the order",
            "29" => "Merchant is unavailable",
            "24" => "Looking for a Joey",
            "23" => "Waiting for merchant(s) to accept",
            "28" => "Order is with Joey",
            "133" => "Packages sorted",
            "55" => "ONLINE PAYMENT EXPIRED",
            "12" => "ONLINE PAYMENT FAILED",
            "53" => "Waiting for customer to pay",
            "141" => "Lost package",
            "60" => "Task failure",
            "145" => 'Returned To Merchant',
            "146" => "Delivery Missorted, Incorrect Address",
            '147' => 'Scanned at Hub',
            '148' => 'Scanned at Hub and labelled',
            '149' => 'pick from hub',
            '150' => 'drop to other hub',
            '153' => 'Miss sorted to be reattempt',
            '154' => 'Joey unable to complete the route', '155' => 'To be re-attempted tomorrow');
        return $statusid[$id];
    }
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $status = '';
        if(isset($this->taskids))
        {
            $status = StatusMap::getDescription($this->taskids->status_id);
        }

        $route_id = '';
        $ordinal = '';
        $address = '';
        $joey = '';
        if(isset($this->taskids->managerJoeyRouteLocation)){
            if(isset($this->taskids->managerJoeyRouteLocation->managerJoeyRoute)) {
                $route_id = $this->taskids->managerJoeyRouteLocation->route_id;
                $ordinal = $this->taskids->managerJoeyRouteLocation->ordinal;
            }
        }
        if (isset($this->taskids->managerLocation))
        {
            $address = $this->taskids->managerLocation->address;
        }

        if(isset($this->taskids->managerJoeyRouteLocation)) {
            if (isset($this->taskids->managerJoeyRouteLocation->managerJoeyRoute)) {
                if (isset($this->taskids->managerJoeyRouteLocation->managerJoeyRoute->joey)) {
                    $joey = $this->taskids->managerJoeyRouteLocation->managerJoeyRoute->joey;
                }
            }
        }

        $joey_name= '';
        if (isset($joey->first_name))
        {

            $joey_name =  $joey->first_name.' '.$joey->last_name;
        }

        $task = SprintTasks::find($this->task_id);
        $sprint = Sprint::find($task->sprint_id);


        $status2 = array();
        $statusfirst = array();
        $status1 = array();
//        $data[$i] =  $task;
        $taskHistory= SprintTaskHistory::where('sprint_id','=',$task->sprint_id)->WhereNotIn('status_id',[17,38])->orderBy('date')
            //->where('active','=',1)
            ->get(['status_id',\DB::raw("CONVERT_TZ(created_at,'UTC','America/Toronto') as created_at")]);

        $returnTOHubDate = SprintReattempt::
        where('sprint_reattempts.sprint_id','=' ,$task->sprint_id)->orderBy('created_at')
            ->first();

        if(!empty($returnTOHubDate))
        {
            $taskHistoryre= SprintTaskHistory::where('sprint_id','=', $returnTOHubDate->reattempt_of)->WhereNotIn('status_id',[17,38,61])->orderBy('date')
                //->where('active','=',1)
                ->get(['status_id',\DB::raw("CONVERT_TZ(created_at,'UTC','America/Toronto') as created_at")]);

            foreach ($taskHistoryre as $history){

                $statusfirst[$history->status_id]['id'] = $history->status_id;
                if($history->status_id==13)
                {
                    $statusfirst[$history->status_id]['description'] ='At hub - processing';
                }
                else
                {
                    $statusfirst[$history->status_id]['description'] =$this->statusmap($history->status_id);
                }
                $statusfirst[$history->status_id]['created_at'] = $history->created_at;

            }

        }
        if(!empty($returnTOHubDate))
        {
            $returnTO2 = SprintReattempt::
            where('sprint_reattempts.sprint_id','=' , $returnTOHubDate->reattempt_of)->orderBy('created_at')
                ->first();

            if(!empty($returnTO2))
            {
                $taskHistoryre= SprintTaskHistory::where('sprint_id','=',$returnTO2->reattempt_of)->WhereNotIn('status_id',[17,38])->orderBy('date')
                    //->where('active','=',1)
                    ->get(['status_id',\DB::raw("CONVERT_TZ(created_at,'UTC','America/Toronto') as created_at")]);

                foreach ($taskHistoryre as $history){

                    $status2[$history->status_id]['id'] = $history->status_id;
                    if($history->status_id==13)
                    {
                        $status2[$history->status_id]['description'] ='At hub - processing';
                    }
                    else
                    {
                        $status2[$history->status_id]['description'] = $this->statusmap($history->status_id);
                    }
                    $status2[$history->status_id]['created_at'] = $history->created_at;

                }

            }
        }

        //    dd($taskHistory);

        foreach ($taskHistory as $history) {

            if (in_array($history->status_id, [61, 13]) or in_array($history->status_id, [124, 125])) {
                $status1[$history->status_id]['id'] = $history->status_id;

                if ($history->status_id == 13) {
                    $status1[$history->status_id]['description'] = 'At hub - processing';
                } else {
                    $status1[$history->status_id]['description'] = $this->statusmap($history->status_id);
                }
                $status1[$history->status_id]['created_at'] = $history->created_at;

            }
            else{
                if ($history->created_at >= $task->route_date) {
                    $status1[$history->status_id]['id'] = $history->status_id;

                    if ($history->status_id == 13) {
                        $status1[$history->status_id]['description'] = 'At hub - processing';
                    } else {
                        $status1[$history->status_id]['description'] = $this->statusmap($history->status_id);
                    }
                    $status1[$history->status_id]['created_at'] = $history->created_at;
                }
            }
        }


        if($statusfirst!=null)
        {
            $sort_key = array_column($statusfirst, 'created_at');
            array_multisort($sort_key, SORT_ASC, $statusfirst);
        }
        if($status1!=null)
        {
            $sort_key = array_column($status1, 'created_at');
            array_multisort($sort_key, SORT_ASC, $status1);
        }
        if($status2!=null)
        {
            $sort_key = array_column($status2, 'created_at');
            array_multisort($sort_key, SORT_ASC, $status2);
        }


        $firstattempt=[];
        $secondattempt=[];
        $thirdattempt=[];

        if(!empty($status2)){

            $firstattempt = $status2;
            $secondattempt = $statusfirst;
            $thirdattempt = $status1;

        }
        else if(!empty($statusfirst)){
            $firstattempt = $statusfirst;
            $secondattempt = $status1;
        }
        else{
            $firstattempt = $status1;
        }


//        foreach ($firstattempt as $status){
            $statusHistory['first'] = $firstattempt;
        $statusHistory['second'] = $secondattempt;
        $statusHistory['third'] = $thirdattempt;
//        }




//            dd($first,$second);

//            if(empty($first)  && empty($second)){
//                $thirdName = 'first';
//            }else{
//                $thirdName = 'third';
//            }
//
//            if(empty($first)){
//                $firstName = 'third';
//            }else{
//                $firstName = 'first';
//            }

//        if(!empty($status3)){
//            $statusHistory['first'] = $status1;
//            $statusHistory['second'] = $status2;
//            $statusHistory['third'] = $status3;
//        }
//        else if(!empty($status1)){
//            $statusHistory['first'] = $status1;
//            $statusHistory['second'] = $status2;
//        }
//        else{
//            $statusHistory['second'] = $status2;
//        }

//        $i++;

//            $data[$i]['third'] = $third;
//            $i++;
//        }


//        if($sprint){
//            $reattempt = SprintReattempt::where('sprint_id', $sprint->id)->latest()->first();
//        }
//
//
//        $statusHistory['first'] = ManagerSprintTaskHistoryResource::collection(($this->taskids) ? $this->taskids->ManagerSprintTaskMultipleHistory : 'N/A');
//
//        if($reattempt != null && $reattempt->sprint_id == $task->sprint_id){
//            $secondAttempt = SprintTaskHistory::where('sprint_id', $reattempt->sprint_id)->whereNotIn('status_id',[36,38,17])->groupBy('status_id')->get();
//            $statusHistory['second'] = ManagerSprintTaskHistoryResource::collection($secondAttempt);
//        }else{
//            $statusHistory['second'] = [];
//        }
//
//        if($reattempt != null && $reattempt->reattempt_of == $task->sprint_id){
//            $checkReattempt = SprintReattempt::where('sprint_id', $reattempt->reattempt_of)->first();
//            $thirdAttempt = SprintTaskHistory::where('sprint_id', $checkReattempt->sprint_id)->whereNotIn('status_id',[36,38,17])->groupBy('status_id')->get();
//            $statusHistory['third'] = ManagerSprintTaskHistoryResource::collection($thirdAttempt);
//        }else{
//            $statusHistory['third'] = [];
//        }
//        $image = \App\SprintConfirmation::where('task_id', '=', $response['id'])->whereNotNull('attachment_path')->orderBy('id', 'desc')->first();
        $sprintConfirmation = SprintConfirmation::where('task_id', $task->id)->whereNotNull('attachment_path')->orderBy('id', 'desc')->first();
//        $cnfirm = new SprintConfirmationResource($sprintConfirmation);

        $statusHistory['image'] = ($sprintConfirmation)?$sprintConfirmation->attachment_path:'N/A';


        return [
            'sprint_id' => $this->taskids->sprint_id,
            'tracking_id' => $this->tracking_id??'N/A',
            'merchant_order_number' => $this->merchant_order_num??'N/A',
            'route_id' => $route_id,
            'ordinal' => $ordinal ,
            'customer_name' => ($this->taskids->managerSprintContact)?$this->taskids->managerSprintContact->name:'N/A',//new ManagerSprintContactResource(($this->taskids->managerSprintContact)?$this->taskids->managerSprintContact->name:''),
            'customer_email' => ($this->taskids->managerSprintContact)?$this->taskids->managerSprintContact->email:'N/A',//new ManagerSprintContactResource(($this->taskids->managerSprintContact)?$this->taskids->managerSprintContact->email:''),
            'customer_phone' => ($this->taskids->managerSprintContact)?$this->taskids->managerSprintContact->phone:'N/A',//new ManagerSprintContactResource(($this->taskids->managerSprintContact)?$this->taskids->managerSprintContact->phone:''),
            'customer_address' => ($this->taskids->managerLocation)?$this->taskids->managerLocation->address:'N/A',//new ManagerLocationResource(($this->taskids->managerLocation)?$this->taskids->managerLocation->address:''),
            'status'=>$status,
            'joey_id' => $joey->id??'N/A',
            'joey_name' => $joey_name??'N/A',
            'joey_phone' => $joey->phone??'N/A',

            //'address'=> $address??'',
            'status_history'=>$statusHistory,



        ];
    }
}
