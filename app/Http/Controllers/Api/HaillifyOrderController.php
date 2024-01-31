<?php

namespace App\Http\Controllers\Api;


use App\Classes\HaillifyResponse;
use App\Http\Resources\HaillifyOrderTrackingHistoryResource;
use App\Models\Dispatch;
use App\Models\HaillifyDeliveryDetail;
use App\Models\Joey;
use App\Models\JoeyLocations;
use App\Models\JoeyRouteLocation;
use App\Models\JoeyRoutes;
use App\Models\JoeysZoneSchedule;
use App\Models\SprintSprintHistory;
use App\Models\SprintZone;
use App\Models\SprintZoneSchedule;
use App\Models\StatusCode;
use App\Models\StatusMap;
use App\Models\Vehicle;
use App\Models\ZoneSchedule;
use App\Models\ZoneVendorRelationship;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use App\Models\Hub;
use App\Models\City;
use App\Models\Sprint;
use App\Models\Vendor;
use App\Classes\RestAPI;
use App\Models\Location;
use App\Models\ContactEnc;
use App\Models\LocationEnc;
use App\Models\SprintTasks;
use App\Models\Country;
use App\Models\State;
use App\Models\CtcEnteries;
use App\Models\BoradlessDashboard;
// use App\Models\SprintSprintHistory;
use App\Models\MerchantsIds;
// use App\Http\Resources\CreateOrderResource;
use Illuminate\Http\Request;
// use App\Http\Requests\Api\CreateOrderRequest;
use App\Models\SprintContact;
use App\Models\HaillifyBooking;
use App\Models\SprintTaskHistory;
use App\Models\SprintConfirmation;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\CreateOrderMultipleResource;
use App\Http\Resources\HaillifyBookingResource;
use App\Http\Requests\Api\OrderCreateRequest;
use App\Http\Requests\Api\CreateOrderOtherRequest;
use App\Http\Requests\Api\CreateOrderContactRequest;
use App\Http\Requests\Api\CreateOrderLoblawsRequest;
use App\Http\Requests\Api\CreateOrderPaymentRequest;
use App\Http\Requests\Api\CreateOrderWalmartRequest;
use App\Http\Requests\Api\CreateOrderLocationRequest;

class HaillifyOrderController extends Controller
{
    public function orderRequest(Request $request)
    {
        // checking is valid json
        if (json_last_error() != JSON_ERROR_NONE) {
            return HaillifyResponse::responsewithCode('Json request is not valid', false, 'error_exception');
        }

        if(!isset($_SERVER['HTTP_X_API_KEY'])){
            return HaillifyResponse::responsewithCode('Header token is required',false,'error_exception');
        }

        $header_token= $_SERVER['HTTP_X_API_KEY'];
        $token = 'aGFpbGxpZnlqb2V5Y29uZXd5b3JrLTIwMjItMjAyMw==';

        if(empty($header_token)){
            return HaillifyResponse::responsewithCode('Header token is required',false,'error_exception');
        }

        if($header_token != $token ){
            return HaillifyResponse::responsewithCode('Invalid header token',false,'error_exception');
        }

        $data_input = $request->all();

        $bookingExist = HaillifyBooking::where('booking_id',$data_input['bookingId'])->whereNull('deleted_at')->exists();

        if($bookingExist == true){
            return HaillifyResponse::responsewithCode('This booking id is already exists', false, 'error_exception');
        }

        //check delivery id is already exists or not unique
        foreach($data_input['orders'] as $order){
            $deliveryIdIsExists = $this->checkDeliveryId($order['deliveryId']);

            if($deliveryIdIsExists == true){
                return HaillifyResponse::responsewithCode('Duplicate active delivery id can not be apply', false, 'error_exception');
            }

            $haillifyIdIsExists = $this->checkHailifyId($order['hailifyId']);

            if($haillifyIdIsExists == true){
                return HaillifyResponse::responsewithCode('Duplicate active hailify id can not be apply', false, 'error_exception');
            }

//            $trackingIdReturnAndDelivered = $this->checkTrackingIdReturnAndDelivered($order['hailifyTrackingID']);
//            dd($trackingIdReturnAndDelivered);
//            if($trackingIdIsExists == true){
//                return HaillifyResponse::responsewithCode('Duplicate tracking id '. $order['hailifyTrackingID'] .' can not be apply', false, 'error_exception');
//            }

            $trackingIdIsExists = $this->checkTrackingId($order['hailifyTrackingID']);

            if($trackingIdIsExists == true){
                return HaillifyResponse::responsewithCode('Duplicate tracking id '. $order['hailifyTrackingID'] .' can not be apply', false, 'error_exception');
            }

        }

        DB::beginTransaction();
        try {
            $data = $this->getMainFields($data_input);
            $this->createPickupLocations($data_input['orders'], $data);
            DB::commit();
        }catch (\Exception $e) {
            DB::rollback();
            return HaillifyResponse::responsewithCode('this request has some issues', false, 'error_exception');
        }
        return HaillifyResponse::responsewithCode('',true, 'Booking request has been created');
    }

    public function createPickupLocations($data, $mainData)
    {

        $routes = JoeyRoutes::create([
            'date'=> date('Y-m-d H:i:s'),
            'total_travel_time' => $mainData['duration'],
            'total_distance' => $mainData['distance'],
            'mile_type' => 5
        ]);

        foreach($data as $index => $order){
            $countryId = $this->getCountryIdOrCreation($order['pickup']);
            $stateId = $this->getStateIdOrCreation($order['pickup'], $countryId);
            $cityId = $this->getCityIdOrCreation($order['pickup'], $countryId, $stateId);
            $location = $this->getLocationAndCreation($order['pickup'], $countryId, $stateId, $cityId);
            $contact = $this->getContactAndCreation($order['pickup']);
            $sprint = $this->getSprintAndCreation($order);
            $booking = $this->getBookingAndCreation($mainData, $order, $sprint->id);
            $this->HaillifyDeliveries($booking->id, $order['pickup'], 'pickup');
            $sprintTask = $this->getTaskAndCreation($order['pickup'], $sprint->id, $location->id, $contact->id, 'pickup', 1);
            SprintTaskHistory::insert(['created_at'=>date("Y-m-d H:i:s"),'date'=>date('Y-m-d H:i:s'),'sprint__tasks_id'=>$sprintTask->id,'sprint_id'=>$sprint->id,'status_id'=>61,'active'=>1]);
            DB::table('sprint__sprints_history')->insert(['sprint__sprints_id'=>$sprint->id, 'vehicle_id'=>$sprint->vehicle_id, 'distance'=>$mainData['distance'], 'status_id'=>$sprint->status_id, 'active'=>1, 'optimize_route'=>1]);
            $this->createMerchantIds('pickup', $order, $sprintTask->id, 0, 0, 0);
            $this->createJoeyRouteLocations($routes->id, $order['pickup']['sequence'], $sprintTask->id);


            $key=2;
            $merchantOrderNo=1;
            $trackingId=1;
            $dropoffCount=0;

            if(count($order['dropoffs']) == 1){
                $dropoffCount=1;
            }else{
                $dropoffCount=0;
            }

            foreach($order['dropoffs'] as $dropoff){
                $countryId = $this->getCountryIdOrCreation($dropoff);
                $stateId = $this->getStateIdOrCreation($dropoff, $countryId);
                $cityId = $this->getCityIdOrCreation($dropoff, $countryId, $stateId);
                $location = $this->getLocationAndCreation($dropoff, $countryId, $stateId, $cityId);
                $contact = $this->getContactAndCreation($dropoff);
                $sprintTask = $this->getTaskAndCreation($dropoff, $sprint->id, $location->id, $contact->id, 'dropoff', $key++);
                $this->createMerchantIds('dropoff', $order, $sprintTask->id, $merchantOrderNo++, $trackingId++, $dropoffCount);
                $this->HaillifyDeliveries($booking->id, $dropoff, 'dropoff');
                $this->createJoeyRouteLocations($routes->id, $dropoff['sequence'], $sprintTask->id);
                $dropoffTaskIds[]=$sprintTask->id;
            }

            $this->createBoradlessEntry('pickup', $order, $dropoff, $routes->id, $sprint->id, $dropoffTaskIds, $index);
            $sprints[]=$sprint;
        }

        return $sprints;
    }

    public function getMainFields($data_input){
        $data['bookingId'] = $data_input['bookingId'];
        $data['pick_up_time_utc'] = $data_input['pickupTime'];
        $data['local_pickup_time_toronto'] = $data_input['localPickupTime'];
        $data['distance'] = $data_input['distance'];
        $data['numberOfStops'] = $data_input['numberOfStops'];
        $data['routeNumber'] = $data_input['routeNumber'];
        $data['duration'] = $data_input['duration'];
        $data['batchId'] = $data_input['batchId'];
        return $data;
    }

    public function orderRequestCancel(Request $request, $deliveryId)
    {

        if(!isset($_SERVER['HTTP_X_API_KEY'])){
            return HaillifyResponse::responsewithCode('Header token is required',false,'error_exception');
        }

        $header_token= $_SERVER['HTTP_X_API_KEY'];
        $token = 'aGFpbGxpZnlqb2V5Y29uZXd5b3JrLTIwMjItMjAyMw==';

        if(empty($header_token)){
            return HaillifyResponse::responsewithCode('Header token is required',false,'error_exception');

        }
        if($header_token != $token ){
            return HaillifyResponse::responsewithCode('Invalid header token',false,'error_exception');
        }

        $validator = Validator::make($request->all(), [
            'reason'=>'string|required',
        ]);

        if ($validator->fails()) {
            foreach($validator->messages()->keys() as $message){
                return HaillifyResponse::responsewithCode($message.' field is required', false, 'error_exception');
            }
        }

        $bookingExists = HaillifyBooking::where('booking_id',$request->get('bookingId'))->whereNull('deleted_at')->exists();

        if($bookingExists == false){
            return HaillifyResponse::responsewithCode('Booking id is invalid', false, 'error_exception');
        }

        $deliveryExists = HaillifyBooking::where('delivery_id',$deliveryId)->exists();

        if($deliveryExists == false){
            return HaillifyResponse::responsewithCode('Delivery id is invalid', false, 'error_exception');
        }

        DB::beginTransaction();
        try {
            HaillifyBooking::where('delivery_id',$deliveryId)->update(['deleted_at' => date('Y-m-d H:i:s')]);
            $sprintIds = HaillifyBooking::where('delivery_id',$deliveryId)->pluck('sprint_id');
            $orders = Sprint::whereIn('id', $sprintIds)->get();
            foreach($orders as $order){
                $sprint = Sprint::whereNull('deleted_at')->where('id',$order->id)->update(['status_id' => 36, 'deleted_at' => date('Y-m-d H:i:s')]);
                $sprintTask = SprintTasks::whereNull('deleted_at')->where('sprint_id',$order->id)->update(['status_id'=>36, 'deleted_at' => date('Y-m-d H:i:s')]);
                $spTask = SprintTasks::whereNull('deleted_at')->where('sprint_id',$order->id)->get();
                foreach($spTask as $task) {
                    $sprintTaskHistory = [
                        'sprint__tasks_id' => $task->id,
                        'sprint_id' => $task->sprint_id,
                        'status_id' => 36,
                    ];
                    SprintTaskHistory::create($sprintTaskHistory);
                }
                BoradlessDashboard::where('sprint_id', $order->id)->update(['task_status_id' => 36, 'deleted_at' => date('Y-m-d H:i:s')]);
            }
            DB::commit();
        }catch (\Exception $e) {
            DB::rollback();
            return HaillifyResponse::responsewithCode('something went wrong', false, 'error_exception');
        }
        return HaillifyResponse::responsewithCode('',true, 'order cancel successfully');
    }

    public function orderRequestRejected(Request $request, $bookingId)
    {

        if(!isset($_SERVER['HTTP_X_API_KEY'])){
            return HaillifyResponse::responsewithCode('Header token is required',false,'error_exception');
        }

        $header_token= $_SERVER['HTTP_X_API_KEY'];
        $token = 'aGFpbGxpZnlqb2V5Y29uZXd5b3JrLTIwMjItMjAyMw==';

        if(empty($header_token)){
            return HaillifyResponse::responsewithCode('Header token is required',false,'error_exception');

        }
        if($header_token != $token ){
            return HaillifyResponse::responsewithCode('Invalid header token',false,'error_exception');
        }

        $bookingExists = HaillifyBooking::where('booking_id',$bookingId)->exists();

        if($bookingExists == false){
            return HaillifyResponse::responsewithCode('Booking id is invalid', false, 'error_exception');
        }

        DB::beginTransaction();
        try {
            HaillifyBooking::where('booking_id',$bookingId)->update(['deleted_at' => date('Y-m-d H:i:s')]);
            $sprintIds = HaillifyBooking::where('booking_id',$bookingId)->pluck('sprint_id');
            $orders = Sprint::whereIn('id', $sprintIds)->get();
            foreach($orders as $order){
                $sprint = Sprint::whereNull('deleted_at')->where('id',$order->id)->update(['status_id' => 36, 'deleted_at' => date('Y-m-d H:i:s')]);
                $sprintTask = SprintTasks::whereNull('deleted_at')->where('sprint_id',$order->id)->update(['status_id'=>36, 'deleted_at' => date('Y-m-d H:i:s')]);
                $spTask = SprintTasks::whereNull('deleted_at')->where('sprint_id',$order->id)->get();
                foreach($spTask as $task) {
                    $sprintTaskHistory = [
                        'sprint__tasks_id' => $task->id,
                        'sprint_id' => $task->sprint_id,
                        'status_id' => 36,
                    ];
                    SprintTaskHistory::create($sprintTaskHistory);
                }
                BoradlessDashboard::where('sprint_id', $order->id)->update(['task_status_id' => 36, 'deleted_at' => date('Y-m-d H:i:s')]);
            }
            DB::commit();
        }catch (\Exception $e) {
            DB::rollback();
            return HaillifyResponse::responsewithCode('', false, 'error_exception');
        }
        return HaillifyResponse::responsewithCode('',true, 'Booking Rejected successfully');
    }

    public function GetOrderStatus(Request $request, $deliveryId)
    {

        if(!isset($_SERVER['HTTP_TOKEN'])){
            return HaillifyResponse::responsewithCode('Header token is required',false,'error_exception');
        }

        $header_token= $_SERVER['HTTP_TOKEN'];
        $token = 'aGFpbGxpZnlqb2V5Y29uZXd5b3JrLTIwMjItMjAyMw==';

        if(empty($header_token)){
            return HaillifyResponse::responsewithCode('Header token is required',false,'error_exception');

        }
        if($header_token != $token ){
            return HaillifyResponse::responsewithCode('Invalid header token',false,'error_exception');
        }

        DB::beginTransaction();
        try {

            $haillifyBooking = HaillifyBooking::where('delivery_id', $deliveryId)->exists();

            if($haillifyBooking == false){
                return HaillifyResponse::responsewithCode('Delivery id is invalid', false, 'error_exception');
            }

            $booking = HaillifyBooking::where('delivery_id',$deliveryId)->first();

            $response=  new HaillifyBookingResource($booking);

            DB::commit();
        }catch (\Exception $e) {
            DB::rollback();
            return HaillifyResponse::responsewithCode('', false, 'error_exception');
        }
        return json_encode($response);


    }

    public function checkDeliveryId($deliveryId)
    {
        return HaillifyBooking::whereHas('sprint', function ($query){
            $query->whereNotIn('status_id', [36]);
        })->where('delivery_id', $deliveryId)
            ->whereNull('deleted_at')
            ->exists();
    }

    public function checkHailifyId($hailifyId)
    {
        return HaillifyBooking::whereHas('sprint', function ($query){
            $query->whereNotIn('status_id', [36]);
        })->where('haillify_id', $hailifyId)
            ->whereNull('deleted_at')
            ->exists();
    }

    public function checkTrackingId($trackingId)
    {
        return MerchantsIds::whereHas('taskids.sprintsSprints', function ($query){
            $query->whereNotIn('status_id', [36, 101, 104, 105, 106, 107, 108, 109, 110, 111, 112, 131, 135, 136,143,146,145, 17, 113, 114, 116, 117, 118, 132, 138, 139, 144]);
        })->where('tracking_id', $trackingId)
            ->whereNull('deleted_at')
            ->exists();
    }

//    public function checkTrackingIdReturnAndDelivered($trackingId)
//    {
//        return MerchantsIds::whereHas('taskids.sprintsSprints', function ($query){
//            $query->whereNotIn('status_id', [101, 104, 105, 106, 107, 108, 109, 110, 111, 112, 131, 135, 136,143,146,145]);
//        })->where('tracking_id', $trackingId)
//            ->whereNull('deleted_at')
//            ->exists();
//    }

    public function getCountryIdOrCreation($order)
    {
        $country = Country::where('name', $order['country'])->orWhere('code', $order['country'])->first();
        if($country != null){
            $countryId =  $country->id;
        }

        if($country == null){
            $country = Country::insert([
                'name' => $order['country'],
            ]);
            if($country == true){
                $country = Country::where('name', $order['country'])->first();
            }
            $countryId = $country->id;
        }
        return $countryId;
    }

    public function getStateIdOrCreation($order, $countryId)
    {
        $state = State::where('name', $order['state'])->orWhere('code', $order['state'])->first();
        if($state != null){
            $stateId =  $state->id;
        }

        if($state == null){
            $state = State::insert([
                'name' => $order['state'],
                'country_id' => $countryId,
            ]);
            if($state == true){
                $state = State::where('name', $order['state'])->first();
            }
            $stateId = $state->id;
        }
        return $stateId;
    }

    public function getCityIdOrCreation($order, $countryId, $stateId)
    {
        $city = City::where('name', $order['city'])->first();
        if($city != null){
            $cityId = $city->id;
        }

        if($city == null){

            $cit = City::insert([
                'name' => $order['city'],
                'country_id' => $countryId,
                'state_id' => $stateId,
                'timezone' => 'America/Toronto'
            ]);
            if($cit == true){
                $cit = City::where('name', $order['city'])->first();
            }
            $cityId = $cit->id;
        }
        return $cityId;
    }

    public function getLocationAndCreation($order, $countryId, $stateId, $cityId)
    {
        $latitude = substr(str_replace('.','',$order['latitude']), 0, 9);
        $longitude = substr(str_replace('.','',$order['longitude']), 0, 9);

        $location = Location::create([
            'address' => $order['street'],
            'city_id' => $cityId,
            'state_id' => $stateId,
            'country_id' => $countryId,
            'postal_code' => $order['postalCode'],
            'suite' => $order['aptNumber'],
            'latitude' => str_replace('.','',$latitude),
            'longitude' => str_replace('.','',$longitude),
        ]);

        return $location;
    }

    public function getContactAndCreation($order)
    {
        $contact = SprintContact::create([
            'name' => $order['name'],
            'phone' => $order['phoneNumber'],
            'email' => $order['email'],
        ]);

        return $contact;
    }

    public function getSprintAndCreation($order)
    {
        $sprint_data['creator_id']= 477639;
        $sprint_data['creator_type']='vendor';
        $sprint_data['vehicle_id']=3;
        $sprint_data['status_id']=61;
        $sprint_data['tip']= $order['tip'];
        $sprint=Sprint::create($sprint_data);
        return $sprint;
    }

    public function getBookingAndCreation($mainData, $order, $sprintId)
    {
        $bookings = [
            'booking_id' => $mainData['bookingId'],
            'delivery_id' => $order['deliveryId'],
            'haillify_id' => $order['hailifyId'],
            'sprint_id' => $sprintId,
            'batch_id' => $mainData['batchId'],
            'route_num' => $mainData['routeNumber'],
            'pickup_time'=> $order['pickupTime'],
            'local_pickup_time' => $order['localPickupTime'],
            'fee' => $order['fee'],
            'tip' => $order['tip'],
            'distance' => $mainData['distance'],
            'duration' => $mainData['duration'],
            'number_of_stops' => $mainData['numberOfStops'],
            'order_value' => $order['orderValue'],
            'merchant_order_num' => $order['deliveryExternalReference'],
            'tracking_id' => $order['hailifyTrackingID'],
            'service_type' => $order['serviceType']
        ];

        $booking = HaillifyBooking::create($bookings);
        return $booking;
    }

    public function HaillifyDeliveries($bookingId, $order, $type)
    {
        $haillifyDeliveries = HaillifyDeliveryDetail::create([
            'haillify_booking_id' => $bookingId,
            'number_of_packages' => $order['numberOfPackages'],
            'sequence' => $order['sequence'],
            'apart_no' => $order['aptNumber'],
            'street' => $order['street'],
            'dropoff_id' => ($type == 'pickup') ? null : $order['dropoffId']
        ]);
        return $haillifyDeliveries;
    }

    public function getTaskAndCreation($order, $sprintId, $locationId, $contactId, $type, $ordinal)
    {
        $sprint_task_pickup_data['sprint_id']=$sprintId;
        $sprint_task_pickup_data['type']=$type;
        $sprint_task_pickup_data['charge']=0;
        $sprint_task_pickup_data['ordinal']= $ordinal;
        $sprint_task_pickup_data['location_id']=$locationId;//vendors table location id
        $sprint_task_pickup_data['contact_id']=$contactId;// Vendors table contact id
        $sprint_task_pickup_data['status_id']=61;
        $sprint_task_pickup_data['active']=1;
        $sprint_task_pickup_data['description']=$order['instructions'];
        $sprint_task_pickup_data['confirm_image']= ($type == 'pickup') ? 0 : $order['isPhotoRequired'];
        $sprint_task_pickup_data['confirm_signature'] = ($type == 'pickup') ? 0 : $order['isSignatureRequired'];
        $sprint_task_pickup_data['confirm_pin']= ($type == 'pickup') ? 0 : $order['isCustomerIDRequired'];
        $sprint_task_pickup_data['confirm_seal']=0;
        $sprint_task_pickup=SprintTasks::create($sprint_task_pickup_data);
        return $sprint_task_pickup;
    }

    public function createMerchantIds($type, $order, $taskId, $merchantOrderNo, $trackingId, $dropoffCount)
    {
        if($dropoffCount == 1){
            $merchantOrderNum = $order['deliveryExternalReference'];
            $trackingIdd = $order['hailifyTrackingID'];
        }else{
            $merchantOrderNum = $order['deliveryExternalReference'].'-'.$merchantOrderNo;
            $trackingIdd = $order['hailifyTrackingID'].'-'.$trackingId;
        }
        $merchantIds = MerchantsIds::create([
            'task_id' => $taskId,
            'merchant_order_num' => ($type == 'pickup') ? $order['deliveryExternalReference'] : $merchantOrderNum,
            'tracking_id' => ($type == 'pickup') ? $order['hailifyTrackingID'] : $trackingIdd,
        ]);
    }

    public function createJoeyRouteLocations($routeId, $ordinal, $taskId)
    {


        $joeyRouteLocation = JoeyRouteLocation::create([
            'route_id' => $routeId,
            'ordinal' => $ordinal,
            'task_id' => $taskId,
        ]);
    }

    public function createBoradlessEntry($type, $order, $pickAndDrop, $routeId, $sprintId, $taskId, $key)
    {
        $vendor = Vendor::find(477639);

        $boradless = BoradlessDashboard::create([
            'sprint_id' => $sprintId,
            'task_id' => $taskId[$key],
            'creator_id' => 477639,
            'route_id' => $routeId,
            'ordinal' => ($type == 'pickup') ? $pickAndDrop['sequence'] : $pickAndDrop['sequence'],
            'tracking_id' => $order['hailifyTrackingID'],
            'eta_time' => strtotime($order['pickupTime']),
            'task_status_id' => 61,
            'store_name' => (isset($vendor->name)) ? $vendor->name : 'drive hailify',
            'customer_name' => ($type == 'pickup') ? $pickAndDrop['name'] : $pickAndDrop['name'],
            'address_line_1' => ($type == 'pickup') ? $pickAndDrop['aptNumber']. ' ' .$pickAndDrop['street'] : $pickAndDrop['aptNumber']. ' ' .$pickAndDrop['street'],
        ]);
    }

    public function GetOrderTrackingHistory($deliveryId)
    {

        if(!isset($_SERVER['HTTP_X_API_KEY'])){
            return HaillifyResponse::responsewithCode('Header token is required',false,'error_exception');
        }

        $header_token= $_SERVER['HTTP_X_API_KEY'];
        $token = 'aGFpbGxpZnlqb2V5Y29uZXd5b3JrLTIwMjItMjAyMw==';

        if(empty($header_token)){
            return HaillifyResponse::responsewithCode('Header token is required',false,'error_exception');

        }
        if($header_token != $token ){
            return HaillifyResponse::responsewithCode('Invalid header token',false,'error_exception');
        }

        DB::beginTransaction();
        try {

            $haillifyBooking = HaillifyBooking::where('delivery_id', $deliveryId)->exists();

            if($haillifyBooking == false){
                return HaillifyResponse::responsewithCode('Delivery id is invalid', false, 'error_exception');
            }

            $booking = HaillifyBooking::whereNull('deleted_at')->where('delivery_id',$deliveryId)->first();

            $response=  new HaillifyOrderTrackingHistoryResource($booking);

            DB::commit();
        }catch (\Exception $e) {
            DB::rollback();
            return HaillifyResponse::responsewithCode('', false, 'error_exception');
        }
        return json_encode($response);


    }
}
