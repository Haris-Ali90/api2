<?php

namespace App\Http\Resources;

use App\Models\JoeyLocations;
use App\Models\Sprint;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class HaillifyBookingResource extends JsonResource
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
        "36" => "cancelled",
        "121" => "to_delivery",
        "17" => "at_delivery",
        "113" => "at_delivery",
        "114" => "at_delivery",
        "116" => "at_delivery",
        "117" => "at_delivery",
        "118" => "at_delivery",
        "132" => "at_delivery",
        "138" => "at_delivery",
        "139" => "at_delivery",
        "144" => "at_delivery",
        "104" => "PackageDamage",
        "105" => "PackageDamage",
        "106" => "CustomerWontAccept",
        "107" => "CustomerWontAccept",
        "108" => "CustomerWontAccept",
        "109" => "CustomerWontAccept",
        "135" => "returned",
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

        $sprint = Sprint::find($this->sprint_id);

        $latitude='';
        $longitude='';

        if(isset($sprint->joey)){
            $location = JoeyLocations::where('joey_id', $sprint->joey->id)->orderBy('id', 'DESC')->first();
            if(isset($location)){
                $latitude = $location->latitude/1000000;
                $longitude =$location->longitude/1000000;
            }
        }

        return [
            'statusReason' => $this->test[$sprint->status_id],
            'status' =>  (isset($this->hailifyStatus[$sprint->status_id])) ? $this->hailifyStatus[$sprint->status_id] : $this->test[$sprint->status_id],
            'driverId' => $sprint->joey_id,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'hailifyId' => $this->haillify_id,
            'estimatedPickupTime' => $this->pickup_time,
            'estimatedDropoffTime' => $dropOffTime,
            'dropoffs' => HaillifyBookingDropoffsResource::collection($this->dropoff),
        ];


    }
}
