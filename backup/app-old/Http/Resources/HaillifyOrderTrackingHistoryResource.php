<?php

namespace App\Http\Resources;

use App\Models\HaillifyBooking;
use App\Models\HaillifyDeliveryDetail;
use App\Models\JoeyLocations;
use App\Models\Sprint;
use App\Models\SprintTaskHistory;
use App\Models\SprintTasks;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class HaillifyOrderTrackingHistoryResource extends JsonResource
{

    private $test = array("136" => "Client requested to cancel the order",
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
        "121" => "Pickup from Hub",
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
        "255" =>"Order delay",
        "145"=>"Returned To Merchant",
        "146" => "Delivery Missorted, Incorrect Address",
        "147" => "Scanned at hub",
        "148" => "Scanned at Hub and labelled",
        "149" => "",
        "150" => "",
        "151" => "",
        "152" => "",
    );

    private $hailifyStatus = array(
        "61" => "booked",
        "101" => "to_pickup",
        "67" => "at_pickup",
        "125" => "to_delivery",
        "68" => "at_delivery",
        "17" => "delivered",
        "113" => "delivered",
        "114" => "delivered",
        "116" => "delivered",
        "117" => "delivered",
        "118" => "delivered",
        "132" => "delivered",
        "138" => "delivered",
        "139" => "delivered",
        "144" => "delivered",

        "104" => "to_return",
        "105" => "to_return",
        "106" => "to_return",
        "107" => "to_return",
        "108" => "to_return",
        "109" => "to_return",
        "135" => "to_return",

        "145" => "returned",
        "36" => "cancelled",
    );

    private $statusReason = array(
        "104" => "PackageDamage",
        "105" => "PackageDamage",
        "106" => "CustomerWontAccept",
        "107" => "CustomerWontAccept",
        "108" => "CustomerWontAccept",
        "109" => "CustomerWontAccept",
        "135" => "CustomerWontAccept",
    );

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
        $timestamp = strtotime($this->pickup_time) + 60*60;
        $dropOffTime = date('Y-m-d H:i:s', $timestamp);
        $sprint = Sprint::whereNull('deleted_at')->where('id', $this->sprint_id)->first();
        $taskHistory = SprintTaskHistory::where('sprint_id', $sprint->id)->whereIn('status_id', [61,101,67,125,68,17, 113, 114, 116, 117, 118, 132, 138, 139, 144,104, 105, 106, 107, 108, 109, 135,145])->groupBy('status_id')->orderBy('id', 'ASC')->get();
        $deliveries= HaillifyDeliveryDetail::where('haillify_booking_id', $this->id)->whereNotNull('dropoff_id')->first();

        $latitude=0;
        $longitude=0;

        if(isset($sprint->joey)){
            $location = JoeyLocations::where('joey_id', $sprint->joey->id)->orderBy('id', 'DESC')->first();
            if(isset($location)){
                $latitude = $location->latitude/1000000;
                $longitude =$location->longitude/1000000;
            }
        }

        $orderTrackingHistory = [];
        $status='';
        $attachmentPath = '';
        $deliveredStatus = [17, 113, 114, 116, 117, 118, 132, 138, 139, 144];
        $returnStatus = [104, 105, 106, 107, 108, 109, 135];
        $deliveryNotes = '';
        foreach($taskHistory as $history){
            $task = SprintTasks::where('sprint_id',$history->sprint_id)->where('type', 'dropoff')->first();

            foreach($task->sprintConfirmation as $confirmation){
                if(in_array($history->status_id,$deliveredStatus)){
                    $deliveryNotes = $this->test[$history->status_id];
                    $attachmentPath = $confirmation->attachment_path;
                }
                if(in_array($history->status_id,$returnStatus)){
                    $deliveryNotes = $this->test[$history->status_id];
                    $additionalPhotos = $confirmation->attachment_path;
                }
            }

            $orderTrackingHistory[]=[
                'status' => isset($this->hailifyStatus[$history->status_id]) ? $this->hailifyStatus[$history->status_id] : '',
                'statusReason' => isset($this->test[$history->status_id]) ? $this->test[$history->status_id] : '',
                'driverId' => $sprint->joey_id,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'statusTime' => $history->date,
                'hailifyId' => $this->haillify_id,
                'dropoffs' => [
                    'dropoffId' => $deliveries->dropoff_id,
                    'status' => isset($this->hailifyStatus[$history->status_id]) ? $this->hailifyStatus[$history->status_id] : '',
                    'statusReason' => isset($this->test[$history->status_id]) ? $this->test[$history->status_id] : '',
                    'signature' => (isset($task->sprintConfirmation->title)) ? $task->sprintConfirmation->title : '',
                    'photo' => (isset($attachmentPath)) ? $attachmentPath : '' ,
                    'customerId' => (isset($task->sprintConfirmation->pin)) ? $task->sprintConfirmation->pin : '',
                    'deliveryNotes' => $deliveryNotes,
                    'additionalPhotos' => (isset($additionalPhotos)) ? [$additionalPhotos] : [],
                ]
            ];
        }

        return $orderTrackingHistory;

    }
}
