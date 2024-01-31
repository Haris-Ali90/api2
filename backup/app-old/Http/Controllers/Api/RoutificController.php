<?php

namespace App\Http\Controllers\Api;

use App\Classes\RestAPI;
use App\Classes\HaillifyClient;
use App\Classes\HaillifyResponse;

use App\Http\Resources\JoeyRouteDetailsResource;
use App\Http\Resources\JoeyRouteListResource;
use App\Http\Resources\JoeyRouteResource;
use App\Http\Resources\JoeyStorePickupResource;
use App\Http\Resources\MainfestFieldsResource;
use App\Http\Resources\MainfestFieldsResource1;
use App\Http\Resources\MainfestFieldsResource2;
use App\Http\Resources\OrderConfirmResource;
use App\Http\Resources\RouteStatusListResource;
use App\Http\Resources\TrackingDetailsResource;
use App\Models\AmazonEnteries;
use App\Models\Dispatch;
use App\Models\HaillifyBooking;
use App\Models\HaillifyDeliveryDetail;
use App\Models\ItinerariesTask;
use App\Models\JoeyRouteLocation;
use App\Models\JoeyRoutes;
use App\Models\JoeyStorePickup;
use App\Models\Location;
use App\Models\MainfestFields;
use App\Models\MerchantsIds;
use App\Models\CtcEnteries;
use App\Models\BoradlessDashboard;
use App\Models\JoeysZoneSchedule;
use App\Models\ExchangeRequest;
use App\Models\SprintContact;

use App\Models\Vendor;
use App\Models\Vendors;
use App\Models\VendorTransaction;
use App\Models\FinancialTransactions;
use App\Models\Joey;
use App\Models\JoeyTransactions;
use App\Models\User;
use App\Models\RouteHistory;
use App\Models\Sprint;
use App\Models\SprintConfirmation;
use App\Models\SprintSprintHistory;
use App\Models\SprintTaskHistory;
use App\Models\SprintTasks;
use App\Models\OrderImage;
use App\Repositories\Interfaces\JoeyRouteRepositoryInterface;
use App\Repositories\Interfaces\SprintRepositoryInterface;

use App\Repositories\Interfaces\UserRepositoryInterface;
use Carbon\Carbon;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\StatusMap;
use App\Repositories\Interfaces\SprintTaskRepositoryInterface;
use Illuminate\Support\Str;
use PharIo\Manifest\Manifest;
use App\Models\Claim;
use App\Models\OptimizeItinerary;
use stdClass;
use Twilio\Rest\Client;
use JWTAuth;

class RoutificController extends ApiBaseController
{

    private $test = array(
        "136" => "Client requested to cancel the order",
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

    private $userRepository;
    private $joeyrouteRepository;
    private $sprintTaskRepository;

    /**
     * Create a new controller instance.
     *
     *
     * @param HaillifyClient $client
     * @return void
     */
    public function __construct(UserRepositoryInterface       $userRepository, SprintRepositoryInterface $sprintRepository, JoeyRouteRepositoryInterface $joeyrouteRepository,
                                SprintTaskRepositoryInterface $sprintTaskRepository , HaillifyClient $client)
    {

        $this->userRepository = $userRepository;
        $this->sprintRepository = $sprintRepository;
        $this->joeyrouteRepository = $joeyrouteRepository;
        $this->sprintTaskRepository = $sprintTaskRepository;
        $this->client = $client;
    }


    /**
     * for joey routes ,old function
     *
     */
    public function joeyRoute(Request $request)
    {

        date_default_timezone_set('America/Toronto');

        $data = $request->all();

        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);
            // dd($joey->id);
            //dd($joey->joeyRoute->joeyRouteLocation->task_id);
            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }
            //getting routes
            $routes = JoeyRouteLocation::join('sprint__tasks', 'sprint__tasks.id', '=', 'joey_route_locations.task_id')
                ->join('locations', 'location_id', '=', 'locations.id')
                ->join('sprint__contacts', 'contact_id', '=', 'sprint__contacts.id')
                ->join('merchantids', 'merchantids.task_id', '=', 'sprint__tasks.id')
                ->join('joey_routes', 'route_id', '=', 'joey_routes.id')
                //->join('hubs','hubs.id','=','joey_routes.hub')
                ->join('sprint__sprints','sprint__tasks.sprint_id','=','sprint__sprints.id')
                ->whereNull('joey_routes.deleted_at')
                ->whereNull('joey_route_locations.deleted_at')
                ->whereNull('hubs.deleted_at')
                ->whereNull('sprint__tasks.deleted_at')
                ->whereNull('sprint__sprints.deleted_at')
                ->where('joey_routes.joey_id', '=', $joey->id)
                ->whereNotNull('merchantids.tracking_id')
                //->whereNotIn('sprint__tasks.status_id',[17,36,113,114,116,117,118,132,138,139,144])
                ->where('sprint__sprints.is_reattempt','=',0)
                //->whereNull('joey_route_locations.is_unattempted')
                ->distinct()
                ->orderBy('joey_route_locations.ordinal', 'asc')
                ->get(['joey_route_locations.task_id as task_id', 'sprint__tasks.sprint_id', 'route_id as num', 'joey_route_locations.ordinal', 'joey_route_locations.arrival_time',
                    'joey_route_locations.finish_time'
                    , 'sprint__tasks.status_id', 'pin', 'description as copy', 'confirm_signature', 'confirm_pin', 'confirm_image', 'notify_by',
                    // 'hubs.title as hub_title','hubs.address as hub_address',
                    'type', 'start_time', 'eta_time', 'end_time', 'merchantids.merchant_order_num'
                    , 'merchantids.tracking_id', 'address_line2', 'name', 'phone', 'email', 'locations.address', 'postal_code', 'latitude', 'longitude', 'buzzer'
                    , 'suite',
                    //  'sprint__sprints.status_id',
                    'joey_route_locations.arrival_time', 'joey_route_locations.finish_time'
                    , 'joey_route_locations.distance', 'locations.suite'
                ]);

            $response = JoeyRouteResource::collection($routes);


            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'joey route');


    }


    /**
     * for joey routes ,new  function
     *
     */
    public function joeyRoute2(Request $request)
    {
        date_default_timezone_set('America/Toronto');

        $data = $request->all();

        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);

            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }

            $routes = $this->joeyrouteRepository->findWithJoeyId($joey->id);

            if (empty($routes)) {
                return RestAPI::response('route  record not found', false);
            }
            $tasks_ids = $routes->map(
                function ($routes) {
                    return $routes->task_id;
                }
            );

            $exchangeArray=[];

            foreach ($routes as $route){
                $exchangeRequest = ExchangeRequest::where('tracking_id', $route->sprintTask->merchantIds->tracking_id)->exists();
                if($exchangeRequest == true) {
                    if(isset($route->sprintTask)){

                        $latitude='';
                        $longitude='';

                        if(isset($this->sprintTask->Location)){
                            $lat[0] = substr($this->sprintTask->Location->latitude, 0, 2);
                            $lat[1] = substr($this->sprintTask->Location->latitude, 2);
                            $latitude = $lat[0].".".$lat[1];

                            $long[0] = substr($this->sprintTask->Location->longitude, 0, 3);
                            $long[1] = substr($this->sprintTask->Location->longitude, 3);
                            $longitude = $long[0].".".$long[1];

                        }

                        if($route->sprintTask->merchantIds){
                            $exchange = ExchangeRequest::where('tracking_id', $route->sprintTask->merchantIds->tracking_id)->first();
                            $exchangeArray[] = [
                                'num' => 'R-'.$route->route_id.'-'.$route->ordinal ,
                                'start_time' => $route->sprintTask->merchantIds->start_time??'',
                                'end_time' => $route->sprintTask->merchantIds->end_time??'',
                                'arrival_time' => $route->arrival_time ??'',
                                'finish_time' => $route->finish_time??'',
                                'contact' => [
                                    'name' => $route->getExchangeRequestTask->ordinal??'',
                                    'phone' => $route->sprintTask->sprintContact->phone??'',
                                    'email' => $route->sprintTask->sprintContact->email??''
                                ],
                                'location' =>[
                                    'address' => $route->sprintTask->Location->address??'',
                                    'latitude' => $latitude??'',
                                    'longitude' => $longitude??'',
                                    'address_line2' => $route->sprintTask->merchantIds->address_line2??''
                                ],
                                'task_id' =>$route->task_id??'',
                                'tracking_id' => $route->sprintTask->merchantIds->tracking_id??'',
                                'merchant_order_num' => $route->sprintTask->merchantIds->merchant_order_num??'',
                                'ordinal' =>$route->ordinal??'',
                                'has_picked'=> 0,
                                'returned' => '',
                            ];
                        }
                    }
                }
            }

            $response['orders_picked'] = count(SprintTaskHistory::where('status_id', 121)->whereIn('sprint__tasks_id', $tasks_ids)->groupBy('sprint__tasks_id')->get(['id']));
            $response['orders_sorted'] = count(SprintTaskHistory::where('status_id', 133)->whereIn('sprint__tasks_id', $tasks_ids)->groupBy('sprint__tasks_id')->get(['id']));
            $response['is_optimize'] = 0;

            $check = JoeyRouteResource::collection($routes);
            $response['routes'] = [];
            $response['exchange'] = [];
            $optimize = [];
            $optimizeItinerary = OptimizeItinerary::with('itinerary')->where('joey_id',auth()->user()->id)->first();

            if(isset($optimizeItinerary->is_optimize)){
                $response['is_optimize'] = $optimizeItinerary->is_optimize;
                if($optimizeItinerary->is_optimize == 1){
                    $optimize =[];
                    foreach($optimizeItinerary->itinerary as $key => $itinerary){
                        array_push($optimize, $itinerary->task_id);
                    }

                    foreach ($optimize as $op){
                        foreach($check as $key => $ind){
                            if($ind->task_id == $op){
                                $response['routes'][] = $check[$key];
                                $response['exchange'] = $exchangeArray;
                            }
                        }
                    }
                    if(count($check) == 0){
                        $response['routes'] = [];
                        $response['exchange'] = [];
                    }

                }else{
                    $response['routes'] = $check;
                    $response['exchange'] = $exchangeArray;

//                    $response = array_merge($response['routes'], $response['exchange']);

                }
            }else{
                $response['routes'] = $check;
                $response['exchange'] = $exchangeArray;
            }



            $response['Status'] = new RouteStatusListResource($request);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'joey route');


    }

    /**
     * for joey routes pickup
     *
     */
    public function trackingPickup(Request $request)
    {

        $data = $request->all();
        $walMartVendorIds = [477621,477587,477607,477589,477641,477631,477629,477625,477633,477635,477171];
        $wildForkVendorIds = [477625,477633,477635];
        $currentDate = Carbon::now()->format('Y/m/d H:i:s');

        if (empty($data['tracking_id'])) {
            return RestAPI::response('Tracking Id required', false);
        }
        DB::beginTransaction();
        try {

            $authUser = JWTAuth::setToken($request->header('ApiToken'))->toUser();

            $joey = $this->userRepository->find($authUser->id);


            $trackingIdCheck = MerchantsIds::where('tracking_id', $data['tracking_id'])->orderBy('id','DESC')->first();

            if (empty($trackingIdCheck)) {
                return RestAPI::response('Invalid Tracking Id', false);
            }
            $sprintId = MerchantsIds::join('sprint__tasks', 'merchantids.task_id', '=', 'sprint__tasks.id')
                ->join('joey_route_locations', 'joey_route_locations.task_id', '=', 'sprint__tasks.id')
                ->join('joey_routes', 'joey_route_locations.route_id', '=', 'joey_routes.id')
                ->whereNull('sprint__tasks.deleted_at')
                ->whereNull('joey_route_locations.deleted_at')
                ->whereNull('joey_routes.deleted_at')
                ->where('merchantids.tracking_id', '=', $data['tracking_id'])
                ->orderBy('merchantids.id','DESC')
                ->first(['sprint__tasks.sprint_id', 'joey_route_locations.id', 'joey_routes.joey_id', 'joey_route_locations.route_id',
                    'joey_route_locations.ordinal', 'joey_route_locations.task_id','sprint__tasks.contact_id','merchantids.merchant_order_num']);

            if (!empty($sprintId)) {
                // $sprint_task_history_check=SprintTaskHistory::where('sprint_id',$sprintId->sprint_id)->where('status_id',121)->get();
                $sprintForWM = Sprint::where('id', $sprintId->sprint_id)->first();
                if(in_array($sprintForWM->creator_id,$wildForkVendorIds)){
                    try{
                        $vendor = Vendors::find($sprintForWM->creator_id);
                        $contact = SprintContact::where('id', $sprintId->contact_id)->first();
//                        $message = 'Dear '.$contact->name.', Your order # '.$sprintId->merchant_order_num.' from "'.$vendor->name.'" has on the way for delivery, track your order by click on link https://www.joeyco.com/track-order/'.$data['tracking_id'].'';
//                        $subject = 'Order Out For Delivery';
//                        $contact->sendPickupEmail($contact,$message, $subject);

                        $receiverNumber = $contact->phone;
                        $message = 'Dear '.$contact->name.', Your order # '.$sprintId->merchant_order_num.' from "'.$vendor->name.'" is on the way for delivery. Track your order using https://www.joeyco.com/track-order/'.$data['tracking_id'].'';

                        $account_sid = "ACb414b973404343e8895b05d5be3cc056";
                        $auth_token = "c135f0fc91ff9fdd0fcb805a6bdf3108";
                        $twilio_number = "+16479316176";

                        $client = new Client($account_sid, $auth_token);
                        $client->messages->create($receiverNumber, [
                            'from' => $twilio_number,
                            'body' => $message]);

                    }catch(\Exception $e){

                    }
                }
                if(in_array($sprintForWM->creator_id, $wildForkVendorIds)){

                    $client_id = 'sb-646b6a39-bf8d-4453-93d7-209c90cfa646!b106018|it-rt-cpi-prod-ev6oz563!b56186';
                    $url_token = 'https://cpi-prod-ev6oz563.authentication.us10.hana.ondemand.com/oauth/token';
                    $client_secret = 'b96311a2-af61-48de-b8fd-873a2718622b$kbc8vB_csYmne3vjCdH3GMKGsrFkMnZzc3EJV39kD74=';

                    $curl = curl_init();

                    curl_setopt_array($curl, array(
                        CURLOPT_URL => "$url_token",
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_SSL_VERIFYHOST =>false,
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_ENCODING => "",
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 30,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => "POST",
                        CURLOPT_POSTFIELDS => "grant_type=client_credentials&client_id=".$client_id."&client_secret=".$client_secret,
                        CURLOPT_HTTPHEADER => array(
                            "content-type: application/x-www-form-urlencoded"
                        ),
                    ));

                    $oAuth = curl_exec($curl);
                    $oAuth =json_decode($oAuth);

                    $curl2 = curl_init();

                    curl_setopt_array($curl2, array(
                        CURLOPT_URL => 'https://cpi-prod-ev6oz563.it-cpi019-rt.cfapps.us10-002.hana.ondemand.com/http/prod/joeyco/webhook',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS =>'{
                       "tracking_id": "'.$request->get('tracking_id').'",
                       "status_id": "125",
                       "description": "'.$this->test[125].'",
                       "timestamp": "'.strtotime(date('Y-m-d H:i:s')).'"
                   }',
                        CURLOPT_HTTPHEADER => array(
                            'Authorization: Bearer '.$oAuth->access_token,
                            'Content-Type: application/json',
                            'Cookie: sap-usercontext=sap-client=100'
                        ),
                    ));

                    $response = curl_exec($curl2);
                    curl_close($curl2);

                }

                $booking = HaillifyBooking::where('sprint_id', $sprintId->sprint_id)->first();

                $status = '';
                if(empty($booking)){
                    $status=121;
                    $this->updateAmazonEntry($status, $sprintId->sprint_id);
                    $this->updateCTCEntry($status, $sprintId->sprint_id);
                    $this->updateBoradlessDashboard($status, $sprintId->sprint_id);
                    $this->updateClaims($status, $sprintId->sprint_id);
                }else{
                    $status=125;
                    $deliveries= HaillifyDeliveryDetail::where('haillify_booking_id', $booking->id)->whereNotNull('dropoff_id')->first();
                    $deliveryId = $booking->delivery_id;
                    $updateStatusUrl = 'https://api.drivehailify.com/carrier/'.$deliveryId.'/status';
                    $scanUrl = 'https://api.drivehailify.com/carrier/'.$deliveryId.'/scan';
                    $scanArray = [
                        'scanDate' => date('Y-m-d H:i:s'),
                        'scanData' => $data['tracking_id'],
                        'dropoffId' => (isset($deliveries->dropoff_id)) ? $deliveries->dropoff_id : '',
                        'status' => 'scanned_pickup',
                        'latitude' => ($request->get('latitude') != null) ? $request->get('latitude') : 0,
                        'longitude' => ($request->get('longitude') != null) ? $request->get('longitude') : 0,
                        'hailifyId' => $booking->haillify_id,
                    ];
                    $updateStatusArray = [
                        'status' => 'to_delivery',
                        'driverId' => $sprintId->joey_id,
                        'latitude' => ($request->get('latitude') != null) ? $request->get('latitude') : 0,
                        'longitude' => ($request->get('longitude') != null) ? $request->get('longitude') : 0,
                        'hailifyId' => $booking->haillify_id,
                        'dropoffs' => [
                            array("dropoffId" => (isset($deliveries->dropoff_id)) ? $deliveries->dropoff_id : '',
                                "status" => 'to_delivery',
                            )],
                    ];

                    $scanResult = json_encode($scanArray);
                    $scanResponse = $this->client->bookingRequestWithParam($scanResult, $scanUrl);

                    $result = json_encode($updateStatusArray);
                    $response = $this->client->bookingRequestWithParam($result, $updateStatusUrl);
                    if(isset($response)) {
                        $data = [
                            'url' => $updateStatusUrl,
                            'request' => $result,
                            'code' => $response['http_code']
                        ];
                        \Log::channel('hailify')->info($data);
                    }

                    if(isset($scanResponse)) {
                        $data = [
                            'url' => $scanUrl,
                            'request' => $scanResult,
                            'code' => $scanResponse['http_code']
                        ];
                        \Log::channel('hailify')->info($data);
                    }

                }

                $Tasks = SprintTasks::where('sprint_id', '=', $sprintId->sprint_id)->update(['status_id' => $status]);
                Sprint::where('id', $sprintId->sprint_id)->update(['status_id' => $status]);
                BoradlessDashboard::where('sprint_id', $sprintId->sprint_id)->update(['task_status_id' => 125, 'picked_up_at' => date('Y-m-d H:i:s')]);
                $taskHistoryRecord = [
                    'sprint__tasks_id' => $sprintId->task_id,
                    'sprint_id' => $sprintId->sprint_id,
                    'status_id' => $status,
                    'date' => $currentDate,
                    'created_at' => $currentDate,
                    'active' => 0,

                ];
                SprintTaskHistory::insert($taskHistoryRecord);

                $routeHistoryRecord = [
                    'route_id' => $sprintId->route_id,
                    'route_location_id' => $sprintId->id,
                    'ordinal' => $sprintId->ordinal,
                    'joey_id' => $sprintId->joey_id,
                    'task_id' => $sprintId->task_id,
                    'status' => 3,
                    'created_at' => $currentDate,
                    'updated_at' => $currentDate
                ];
                RouteHistory::insert($routeHistoryRecord);



            } else {
                return RestAPI::response('No route found against this tracking id', false);
            }


            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response('success', true, 'Packages picked successfully');

    }


    /**
     * for order confirm
     *
     */

    public function confirmTracking(Request $request)
    {

        $data = $request->all();
        if (empty($data['tracking_id'])) {
            return RestAPI::response('Tracking Id required', false);
        }

        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);

            $trackingIdCheck = MerchantsIds::where('tracking_id', $data['tracking_id'])->first();

            if (empty($trackingIdCheck)) {
                return RestAPI::response('Invalid Tracking Id', false);
            }

            $route = MerchantsIds::join('sprint__tasks', 'merchantids.task_id', '=', 'sprint__tasks.id')
                ->join('joey_route_locations', 'joey_route_locations.task_id', '=', 'merchantids.task_id')
                ->where('tracking_id', '=', $data['tracking_id'])
                ->whereNull('sprint__tasks.deleted_at')
                ->first();

            if (empty($route)) {
                return RestAPI::response('No record found against this tracking id', false);

            }

            // $sprint = SprintTasks::find($route->task_id);

            $pickedUp = SprintTaskHistory::where('sprint_id', '=', $route->sprint_id)
                ->whereIn('status_id', [121,125])
                ->first();


            if (empty($pickedUp)) {
                return RestAPI::response('Pickup is necessary before dropoff package', false);
            }

            $response = new OrderConfirmResource($route);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'Packages Confirmed successfully');

    }


    /**
     * for update itinary status
     *
     */
    public function updateStatusItinary(Request $request)
    {

        $data = $request->all();
        $currentDate = Carbon::now()->format('Y/m/d H:i:s');
        $walMartVendorIds = [477621,477587,477607,477589,477641,477631,477629,477625,477633,477635,477171];
        $wildForkVendorIds = [477625,477633,477635];
        DB::beginTransaction();
        try {

            $authUser = JWTAuth::setToken($request->header('ApiToken'))->toUser();
            $joey = $this->userRepository->find($authUser->id);

            if (empty($data['status_id'])) {
                return RestAPI::response('status_id is require', false);
            }

            if($data['task_id'] == -1){
                $this->firstMileDropOffMinusOne($data, $joey, $currentDate);
            }
            else{

//                $routeId = JoeyRouteLocation::where('task_id', $data['task_id'])->pluck('route_id');
//                $route = JoeyRoutes::where('id', $routeId)->first();
//
//                if($route->is_delivered == false){
//                    return RestAPI::response('Please contact dispatch as your route has not been approved for delivery.', false);
//                }

                $statusDescription = StatusMap::getDescription($data['status_id']);
                $updateData = [
                    'ordinal' => 2,
                    'task_id' => $data['task_id'],
                    'joey_id' => $joey->id,
                    'name' => $statusDescription,
                    'title' => $statusDescription,
                    'confirmed' => 1,
                    'input_type' => 'image/jpeg',
                    'created_at' => $currentDate,
                    'updated_at' => $currentDate,


                ];
                $path = '';
                if (!empty($data['image'])) {
                    $path = $this->upload($data['image']);
                    if (!isset($path)) {
                        return RestAPI::response('File cannot be uploaded due to server error!', false);
                    }

                    $updateData['attachment_path'] = $path;
                }


                SprintConfirmation::insert($updateData);
                $sprintId = SprintTasks::find($data['task_id']);

                if(!empty($sprintId)){
                    SprintTasks::where('sprint_id', $sprintId->sprint_id)->update(['status_id' => $data['status_id']]);
                    Sprint::where('id', $sprintId->sprint_id)->update(['status_id' => $data['status_id']]);



                    $haillifyDeliveryDetails=[];
                    $booking = HaillifyBooking::where('sprint_id', $sprintId->sprint_id)->whereNull('deleted_at')->first();
                    if(!empty($booking)){
                        $haillifyDeliveryDetails = HaillifyDeliveryDetail::where('haillify_booking_id', $booking->id)->whereNull('deleted_at')->get();
                        $deliveryId = $booking->delivery_id;
                        $updateStatusUrl = 'https://api.drivehailify.com/carrier/'.$deliveryId.'/status';
                        $scanUrl = 'https://api.drivehailify.com/carrier/'.$deliveryId.'/scan';
                    }

                    $return_status = [101, 104, 105, 106, 107, 108, 109, 110, 111, 112, 131, 135, 136,143,146];
                    $delivered_status = [17, 113, 114, 116, 117, 118, 132, 138, 139, 144];

                    $route_data = JoeyRoutes::join('joey_route_locations', 'joey_route_locations.route_id', '=', 'joey_routes.id')
                        ->where('joey_route_locations.task_id', '=', $data['task_id'])
                        ->whereNull('joey_route_locations.deleted_at')
                        ->first(['joey_route_locations.id', 'joey_routes.joey_id', 'joey_route_locations.route_id', 'joey_route_locations.ordinal']);

                    $dropOffId='';
                    if (!empty($route_data)) {
                        $merchantForWm = MerchantsIds::where('task_id', $data['task_id'])->first();
                        $taskForWm = SprintTasks::whereNull('deleted_at')->where('id', $data['task_id'])->first();
                        $sprintForWm = Sprint::whereNull('deleted_at')->where('id', $taskForWm->sprint_id)->first();

                        if (in_array($data['status_id'], $delivered_status)) {


                            if(in_array($sprintForWm->creator_id, $wildForkVendorIds)){

                                $client_id = 'sb-646b6a39-bf8d-4453-93d7-209c90cfa646!b106018|it-rt-cpi-prod-ev6oz563!b56186';
                                $url_token = 'https://cpi-prod-ev6oz563.authentication.us10.hana.ondemand.com/oauth/token';
                                $client_secret = 'b96311a2-af61-48de-b8fd-873a2718622b$kbc8vB_csYmne3vjCdH3GMKGsrFkMnZzc3EJV39kD74=';

                                $curl = curl_init();

                                curl_setopt_array($curl, array(
                                    CURLOPT_URL => "$url_token",
                                    CURLOPT_RETURNTRANSFER => true,
                                    CURLOPT_SSL_VERIFYHOST =>false,
                                    CURLOPT_SSL_VERIFYPEER => false,
                                    CURLOPT_ENCODING => "",
                                    CURLOPT_MAXREDIRS => 10,
                                    CURLOPT_TIMEOUT => 30,
                                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                    CURLOPT_CUSTOMREQUEST => "POST",
                                    CURLOPT_POSTFIELDS => "grant_type=client_credentials&client_id=".$client_id."&client_secret=".$client_secret,
                                    CURLOPT_HTTPHEADER => array(
                                        "content-type: application/x-www-form-urlencoded"
                                    ),
                                ));

                                $oAuth = curl_exec($curl);
                                $oAuth =json_decode($oAuth);

                                $curl2 = curl_init();

                                curl_setopt_array($curl2, array(
                                    CURLOPT_URL => 'https://cpi-prod-ev6oz563.it-cpi019-rt.cfapps.us10-002.hana.ondemand.com/http/prod/joeyco/webhook',
                                    CURLOPT_RETURNTRANSFER => true,
                                    CURLOPT_ENCODING => '',
                                    CURLOPT_MAXREDIRS => 10,
                                    CURLOPT_TIMEOUT => 0,
                                    CURLOPT_FOLLOWLOCATION => true,
                                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                    CURLOPT_CUSTOMREQUEST => 'POST',
                                    CURLOPT_POSTFIELDS =>'{
                                        "tracking_id": "'.$request->get('tracking_id').'",
                                        "status_id": "125",
                                        "description": "'.$this->test[125].'",
                                        "timestamp": "'.strtotime(date('Y-m-d H:i:s')).'"
                                    }',
                                    CURLOPT_HTTPHEADER => array(
                                        'Authorization: Bearer '.$oAuth->access_token,
                                        'Content-Type: application/json',
                                        'Cookie: sap-usercontext=sap-client=100'
                                    ),
                                ));

                                $response = curl_exec($curl2);
                                curl_close($curl2);

                            }

                            $taskHistoryRecord = [
                                'sprint__tasks_id' => $data['task_id'],
                                'sprint_id' => $sprintId->sprint_id,
                                'status_id' => $data['status_id'],
                                'created_at'=>$currentDate,
                                'date'=>$currentDate,
                                'active' => 0,
                            ];
                            SprintTaskHistory::insert($taskHistoryRecord);

                            $routeHistoryRecord = [
                                'route_id' => $route_data->route_id,
                                'route_location_id' => $route_data->id,
                                'ordinal' => $route_data->ordinal,
                                'joey_id' => $route_data->joey_id,
                                'task_id' => $data['task_id'],
                                'status' => 2,
                                'created_at' => $currentDate,
                                'updated_at' => $currentDate
                            ];

                            RouteHistory::insert($routeHistoryRecord);



                            if(!empty($booking)){

                                $order_image = OrderImage::where('tracking_id', $booking->tracking_id)->whereNull('deleted_at')->orderBy('id','asc')->get();
                                $additionalImage = [];
                                foreach($order_image as $image){
                                    $additionalImage[] = $image->image;
                                }

                                $dropoff=[];
                                foreach($haillifyDeliveryDetails as $details){
                                    if($details->dropoff_id != null){
                                        $dropOffId = $details->dropoff_id;
                                        $dropoff[]=[
                                            "dropoffId" => $details->dropoff_id,
                                            "status" => 'delivered',
                                            "photo" => $path,
                                            "additionalPhotos"=> $additionalImage,
                                            "deliveryNotes"=>$this->test[$data['status_id']]
                                        ];
                                    }
                                }

                                $updateStatusArray = [
                                    'status' => 'delivered',
                                    'driverId' => $joey->id,
                                    'latitude' => ($request->get('latitude') != null) ? $request->get('latitude') : 0,
                                    'longitude' => ($request->get('longitude') != null) ? $request->get('longitude') : 0,
                                    'hailifyId' => $booking->haillify_id,
                                    'dropoffs' => $dropoff,
                                ];

                                $scanArray = [
                                    'scanDate' => date('Y-m-d H:i:s'),
                                    'scanData' => $booking->tracking_id,
                                    'dropoffId' => $dropOffId,
                                    'status' => 'scanned_dropoff',
                                    'latitude' => ($request->get('latitude') != null) ? $request->get('latitude') : 0,
                                    'longitude' => ($request->get('longitude') != null) ? $request->get('longitude') : 0,
                                    'hailifyId' => $booking->haillify_id,
                                ];

                                $scanResult = json_encode($scanArray);

                                $scanResponse = $this->client->bookingRequestWithParam($scanResult, $scanUrl);

                                $result = json_encode($updateStatusArray);

                                $response = $this->client->bookingRequestWithParam($result, $updateStatusUrl);
                                if(isset($response)){
                                    $curlData = [
                                        'url' => $updateStatusUrl,
                                        'request' => $result,
                                        'code'=>$response['http_code']
                                    ];
                                    \Log::channel('hailify')->info($curlData);
                                }
                                if(isset($scanResponse)){
                                    $curlData = [
                                        'url' => $scanUrl,
                                        'request' => $scanResult,
                                        'code'=>$scanResponse['http_code']
                                    ];
                                    \Log::channel('hailify')->info($curlData);
                                }

                            }



                        }
                        else if (in_array($data['status_id'], $return_status)) {

                            if(in_array($sprintForWm->creator_id,$wildForkVendorIds)){
//                                 try{
//                                     $vendor = Vendors::find($sprintForWm->creator_id);
//                                     $contact = SprintContact::where('id', $taskForWm->contact_id)->first();
//                                     $receiverNumber = $contact->phone;
//                                     $message = 'Dear '.$contact->name.', Your order # '.$merchantForWm->merchant_order_num.' from "'.$vendor->name.'" has been returned. Get delivery details using https://www.joeyco.com/track-order/'.$merchantForWm->tracking_id.'';
// //                                    $subject = 'Order Returned';
// //                                    $contact->sendPickupEmail($contact,$message, $subject);
//                                     $account_sid = "ACb414b973404343e8895b05d5be3cc056";
//                                     $auth_token = "c135f0fc91ff9fdd0fcb805a6bdf3108";
//                                     $twilio_number = "+16479316176";

//                                     $client = new Client($account_sid, $auth_token);
//                                     $client->messages->create($receiverNumber, [
//                                         'from' => $twilio_number,
//                                         'body' => $message]);

//                                 }catch(\Exception $e){
//                                 }
                            }

                            if(in_array($sprintForWm->creator_id, $wildForkVendorIds)){

                                $client_id = 'sb-646b6a39-bf8d-4453-93d7-209c90cfa646!b106018|it-rt-cpi-prod-ev6oz563!b56186';
                                $url_token = 'https://cpi-prod-ev6oz563.authentication.us10.hana.ondemand.com/oauth/token';
                                $client_secret = 'b96311a2-af61-48de-b8fd-873a2718622b$kbc8vB_csYmne3vjCdH3GMKGsrFkMnZzc3EJV39kD74=';

                                $curl = curl_init();

                                curl_setopt_array($curl, array(
                                    CURLOPT_URL => "$url_token",
                                    CURLOPT_RETURNTRANSFER => true,
                                    CURLOPT_SSL_VERIFYHOST =>false,
                                    CURLOPT_SSL_VERIFYPEER => false,
                                    CURLOPT_ENCODING => "",
                                    CURLOPT_MAXREDIRS => 10,
                                    CURLOPT_TIMEOUT => 30,
                                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                    CURLOPT_CUSTOMREQUEST => "POST",
                                    CURLOPT_POSTFIELDS => "grant_type=client_credentials&client_id=".$client_id."&client_secret=".$client_secret,
                                    CURLOPT_HTTPHEADER => array(
                                        "content-type: application/x-www-form-urlencoded"
                                    ),
                                ));

                                $oAuth = curl_exec($curl);
                                $oAuth =json_decode($oAuth);

                                $curl2 = curl_init();

                                curl_setopt_array($curl2, array(
                                    CURLOPT_URL => 'https://cpi-prod-ev6oz563.it-cpi019-rt.cfapps.us10-002.hana.ondemand.com/http/prod/joeyco/webhook',
                                    CURLOPT_RETURNTRANSFER => true,
                                    CURLOPT_ENCODING => '',
                                    CURLOPT_MAXREDIRS => 10,
                                    CURLOPT_TIMEOUT => 0,
                                    CURLOPT_FOLLOWLOCATION => true,
                                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                    CURLOPT_CUSTOMREQUEST => 'POST',
                                    CURLOPT_POSTFIELDS =>'{
                                        "tracking_id": "'.$request->get('tracking_id').'",
                                        "status_id": "125",
                                        "description": "'.$this->test[125].'",
                                        "timestamp": "'.strtotime(date('Y-m-d H:i:s')).'"
                                    }',
                                    CURLOPT_HTTPHEADER => array(
                                        'Authorization: Bearer '.$oAuth->access_token,
                                        'Content-Type: application/json',
                                        'Cookie: sap-usercontext=sap-client=100'
                                    ),
                                ));

                                $response = curl_exec($curl2);
                                curl_close($curl2);

                            }

                            $routeHistoryRecord = [
                                'route_id' => $route_data->route_id,
                                'route_location_id' => $route_data->id,
                                'ordinal' => $route_data->ordinal,
                                'joey_id' => $route_data->joey_id,
                                'task_id' => $data['task_id'],
                                'status' => 4,
                                'created_at' => $currentDate,
                                'updated_at' => $currentDate
                            ];

                            RouteHistory::insert($routeHistoryRecord);

                            $taskHistoryRecord = [
                                'sprint__tasks_id' => $data['task_id'],
                                'sprint_id' => $sprintId->sprint_id,
                                'status_id' => $data['status_id'],
                                'date' => $currentDate,
                                'created_at' => $currentDate,
                                'active' => 0,
                            ];
                            SprintTaskHistory::insert($taskHistoryRecord);

                            $damagedStatus = [104, 105];
                            $unAvailableStatus = [106, 107, 108, 109, 135];

                            $statusMsg = '';
                            if(in_array($data['status_id'], $damagedStatus)){
                                $statusMsg = 'PackageDamage';
                            }else if(in_array($data['status_id'], $unAvailableStatus)){
                                $statusMsg = 'CustomerWontAccept';
                            }



                            if(!empty($booking)){

                                $return_id  = $this->task_sprint_create($sprintId->sprint_id, $route_data->route_id, $data);

                                $order_image = OrderImage::where('tracking_id', $booking->tracking_id)->whereNull('deleted_at')->orderBy('id','asc')->get();
                                $additionalImage = [];
                                foreach($order_image as $image){
                                    $additionalImage[] = $image->image;
                                }

                                foreach($haillifyDeliveryDetails as $details){
                                    if($details->dropoff_id != null){
                                        $dropoff[]=[
                                            "statusReason" => $this->test[$data['status_id']],
                                            "dropoffId" => $details->dropoff_id,
                                            "status" => 'to_return',
                                            "photo" => $path,
                                            "additionalPhotos"=> $additionalImage,
                                            "deliveryNotes"=>$this->test[$data['status_id']],
                                        ];
                                    }
                                }

                                $updateStatusArray = [
                                    'statusReason' => $this->test[$data['status_id']],
                                    'status' => 'to_return',
                                    'driverId' => $joey->id,
                                    'latitude' => ($request->get('latitude') != null) ? $request->get('latitude') : 0,
                                    'longitude' => ($request->get('longitude') != null) ? $request->get('longitude') : 0,
                                    'hailifyId' => $booking->haillify_id,
                                    'dropoffs' => $dropoff,
                                ];


                                $result = json_encode ($updateStatusArray, JSON_UNESCAPED_SLASHES );

                                $response = $this->client->bookingRequestWithParam($result, $updateStatusUrl);
                                if(isset($response)) {
                                    $logdata = [
                                        'url' => $updateStatusUrl,
                                        'request' => $result,
                                        'code' => $response['http_code']
                                    ];
                                    \Log::channel('hailify')->info($logdata);
                                }



                            }

                        }
                        elseif (in_array($data['status_id'], [145])){

                            $taskHistoryRecord = [
                                'sprint__tasks_id' => $data['task_id'],
                                'sprint_id' => $sprintId->sprint_id,
                                'status_id' => $data['status_id'],
                                'date' => $currentDate,
                                'created_at' => $currentDate,
                                'active' => 0,
                            ];
                            SprintTaskHistory::insert($taskHistoryRecord);

                            $routeHistoryRecord = [
                                'route_id' => $route_data->route_id,
                                'route_location_id' => $route_data->id,
                                'ordinal' => $route_data->ordinal,
                                'joey_id' => $route_data->joey_id,
                                'task_id' => $data['task_id'],
                                'status' =>  4,
                                'created_at' => $currentDate,
                                'updated_at' => $currentDate
                            ];

                            BoradlessDashboard::where('sprint_id', $sprintId->sprint_id)->update(['task_status_id' => 145]);
                            if(!empty($booking)) {
                                $order_image = OrderImage::where('tracking_id', $booking->tracking_id)->whereNull('deleted_at')->orderBy('id', 'asc')->get();
                                $additionalImage = [];
                                foreach ($order_image as $image) {
                                    $additionalImage[] = $image->image;
                                }

                                foreach ($haillifyDeliveryDetails as $details) {
                                    if ($details->dropoff_id != null) {
                                        $dropoff[] = [
                                            "dropoffId" => $details->dropoff_id,
                                            "status" => 'returned',
                                            "photo" => $path,
                                            "additionalPhotos" => $additionalImage,
                                        ];
                                    }
                                }

                                RouteHistory::insert($routeHistoryRecord);
                                $updateStatusArray = [
                                    'status' => 'returned',
                                    'driverId' => $joey->id,
                                    'latitude' => ($request->get('latitude') != null) ? $request->get('latitude') : 0,
                                    'longitude' => ($request->get('longitude') != null) ? $request->get('longitude') : 0,
                                    'hailifyId' => $booking->haillify_id,
                                    'dropoffs' => $dropoff,
                                ];

                                $result = json_encode($updateStatusArray, JSON_UNESCAPED_SLASHES);

                                $response = $this->client->bookingRequestWithParam($result, $updateStatusUrl);
                                if (isset($response)) {
                                    $logdata = [
                                        'url' => $updateStatusUrl,
                                        'request' => $result,
                                        'code' => $response['http_code']
                                    ];
                                    \Log::channel('hailify')->info($logdata);
                                }

                                $scanArray = [
                                    'scanDate' => date('Y-m-d H:i:s'),
                                    'scanData' => $booking->tracking_id,
                                    'dropoffId' => $dropOffId,
                                    'status' => 'scanned_return',
                                    'latitude' => ($request->get('latitude') != null) ? $request->get('latitude') : 0,
                                    'longitude' => ($request->get('longitude') != null) ? $request->get('longitude') : 0,
                                    'hailifyId' => $booking->haillify_id,
                                ];

                                $scanResult = json_encode($scanArray);

                                $scanResponse = $this->client->bookingRequestWithParam($scanResult, $scanUrl);
                                if (isset($scanResponse)) {
                                    $logdata = [
                                        'url' => $scanUrl,
                                        'request' => $scanResult,
                                        'code' => $scanResponse['http_code']
                                    ];
                                    \Log::channel('hailify')->info($logdata);
                                }
                            }

                        }

                        $this->updateAmazonEntry($data['status_id'], $sprintId->sprint_id, $path);
                        $this->updateCTCEntry($data['status_id'], $sprintId->sprint_id, $path);
                        $this->updateBoradlessDashboard($data['status_id'], $sprintId->sprint_id, $path);
                        $this->updateClaims($data['status_id'], $sprintId->sprint_id, $path);
                    }

                }
            }


            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response('Success', true, 'Status updated successfully');

    }

    public function firstMileDropOffMinusOne($data, $joey, $currentDate)
    {
        $statusDescription = StatusMap::getDescription($data['status_id']);

        $joeyStorePickup = JoeyStorePickup::where('joey_id', $joey->id)->whereNull('deleted_at')->get();

        foreach($joeyStorePickup as $sprint){
            $updateData = [
                'ordinal' => 2,
                'task_id' => $sprint->task_id,
                'joey_id' => $joey->id,
                'name' => $statusDescription,
                'title' => $statusDescription,
                'confirmed' => 1,
                'input_type' => 'image/jpeg',
                'created_at' => $currentDate,
                'updated_at' => $currentDate,
            ];

            $path = '';
            if (!empty($data['image'])) {
                $path = $this->upload($data['image']);
                if (!isset($path)) {
                    return RestAPI::response('File cannot be uploaded due to server error!', false);
                }

                $updateData['attachment_path'] = $path;
            }

            SprintConfirmation::insert($updateData);

            $taskHistoryRecord = [
                'sprint__tasks_id' => $sprint->task_id,
                'sprint_id' => $sprint->sprint_id,
                'status_id' => $data['status_id'],
                'date' => $currentDate,
                'created_at' => $currentDate
            ];


            SprintTasks::where('id', $sprint->task_id)->update(['status_id' => $data['status_id']]);
            Sprint::where('id', $sprint->sprint_id)->update(['status_id' => $data['status_id']]);
            SprintTaskHistory::insert($taskHistoryRecord);

            $return_status = [101, 104, 105, 106, 107, 108, 109, 110, 111, 112, 131, 135, 136,143,146];
            $delivered_status = [17, 113, 114, 116, 117, 118, 132, 138, 139, 144];

            if (in_array($data['status_id'], $delivered_status)) {

                $routeLocation = JoeyRouteLocation::where('route_id', $sprint->route_id)->whereNull('deleted_at')->get();

                $routeHistoryRecord = [
                    'route_id' => $sprint->route_id,
                    'route_location_id' => $routeLocation->id,
                    'ordinal' => 2,
                    'joey_id' => $joey->id,
                    'task_id' => $sprint->task_id,
                    'status' => 2,
                    'created_at' => $currentDate,
                    'updated_at' => $currentDate
                ];

                RouteHistory::insert($routeHistoryRecord);

            } else if (in_array($data['status_id'], $return_status)) {
                $routeHistoryRecord = [
                    'route_id' => $sprint->route_id,
                    'route_location_id' => $routeLocation->id,
                    'ordinal' => 2,
                    'joey_id' => $joey->id,
                    'task_id' => $sprint->task_id,
                    'status' => 4,
                    'created_at' => $currentDate,
                    'updated_at' => $currentDate
                ];

                RouteHistory::insert($routeHistoryRecord);

            }
            $this->updateAmazonEntry($data['status_id'], $sprint->sprint_id, $path);
            $this->updateCTCEntry($data['status_id'], $sprint->sprint_id, $path);
            $this->updateBoradlessDashboard($data['status_id'], $sprint->sprint_id, $path);
            $this->updateClaims($data['status_id'], $sprint->sprint_id, $path);

        }

    }


    /**
     * for route status
     *
     */
    public function routesStatus(Request $request)
    {
        date_default_timezone_set('America/Toronto');

        $data = $request->all();

        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);

            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }

            $response = new RouteStatusListResource($request);


            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'Routes Status List');


    }


    /**
     * for  joey store picks
     *
     */
    public function pickupstore(Request $request)
    {


        //  $data = $request->all();

        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);

            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }


            $data = JoeyStorePickup::whereHas('sprint',function ($query){
                $query->whereNull('deleted_at');
            })->where('joey_id', '=', $joey->id)
                ->whereNull('joey_storepickup.deleted_at')
                ->get();

            $response = JoeyStorePickupResource::collection($data);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'Joey Pick From Store');

    }


    /**
     * store pickup
     *
     */
    public function storepickup(Request $request)
    {
        $currentDate = Carbon::now()->format('Y/m/d H:i:s');

        $request = $request->all();

        DB::beginTransaction();
        try {
            $joey = $this->userRepository->find(auth()->user()->id);


            if (empty($request['tracking_id'])) {
                return RestAPI::response('Tracking Id is require', false);
            }
            if (empty($joey)) {
                return RestAPI::response('Joey record not found', false);
            }

            $record = JoeyStorePickup::where('joey_id', $joey->id)
                ->where('tracking_id', $request['tracking_id'])
                ->whereNull('deleted_at')
                ->first();

            if(!empty($record)){
                return RestAPI::response('Success', true, 'item pickup from  store successfully');
            }
            $data = MerchantsIds::join('sprint__tasks', 'sprint__tasks.id', '=', 'merchantids.task_id')
                ->join('sprint__sprints', 'sprint__sprints.id', '=', 'sprint__tasks.sprint_id')
                ->wherenull('sprint__sprints.deleted_at')
                ->whereNull('sprint__tasks.deleted_at')
                ->where('merchantids.tracking_id', '=', $request['tracking_id'])
                ->first(['sprint__tasks.sprint_id', 'sprint__sprints.vehicle_id', 'sprint__tasks.id as task_id']);

            if (empty($data)) {
                return RestAPI::response('Invalid tracking code', false);
            }


            SprintTasks::where('sprint_id', '=', $data->sprint_id)->update(['status_id' => 125]);
            //$task=SprintTasks::where('sprint_id','=',$data->sprint_id)->where('ordinal','=',1)->whereNull('deleted_at')->first();
            Sprint::where('id', '=', $data->sprint_id)->whereNull('deleted_at')->update(['status_id' => 125]);


            $taskHistoryRecord = [
                'sprint__tasks_id' => $data->task_id,
                'sprint_id' => $data->sprint_id,
                'status_id' => 125,
                'active' => 1,
                'date' => $currentDate,
                'created_at' => $currentDate

            ];


            SprintTaskHistory::insert($taskHistoryRecord);


            $sprintHistoryRecord = [
                'sprint__sprints_id' => $data->sprint_id,
                'vehicle_id' => $data->vehicle_id,
                'status_id' => 125,
                'active' => 1,
                'date' => $currentDate,
                'created_at' => $currentDate
            ];

            SprintSprintHistory::insert($sprintHistoryRecord);

            //update ctc entries
            $ctc_entries = CtcEnteries::where('sprint_id','=',$data->sprint_id)->whereNUll('deleted_at')->update(['task_status_id' => 125]);

            $boradless = BoradlessDashboard::where('sprint_id','=',$data->sprint_id)->whereNull('deleted_at')->update(['task_status_id' => 125]);


            $joeyStopPickupRecord = [
                'joey_id' => $joey->id,
                'tracking_id' => $request['tracking_id'],
                'sprint_id' => $data->sprint_id,
                'task_id' => $data->task_id,
                'status_id' => 125,
                'created_at' => $currentDate,
                'updated_at' => $currentDate
            ];


            JoeyStorePickup::insert($joeyStopPickupRecord);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response('Success', true, 'Item pickup from store successfully');

    }


    /**
     * for  hub delivery
     *
     */
    public function hubdeliver(Request $request)
    {
        $currentDate = Carbon::now()->format('Y/m/d H:i:s');

        $request = $request->all();

        //getting route ids
        $tracking_ids = explode(',',$request['tracking_id']);
        DB::beginTransaction();
        try {
            $joey = $this->userRepository->find(auth()->user()->id);

            if (empty($joey)) {
                return RestAPI::response('Joey record not found', false);
            }

            $record = JoeyStorePickup::where('joey_id', $joey->id)
                ->whereIn('tracking_id', $tracking_ids)
                ->whereNull('deleted_at')
                ->distinct()
                ->get();

            $task_ids = [];
            $sprint_ids = [];
            $taskHistoryRecord = [];
            $sprintHistoryRecord = [];


            if ($record->count() <= 0) {
                return RestAPI::response('No record found against tracking id', false);
            }
            foreach ($record as $value) {
                $task_ids[] = $value->task_id;
                $sprint_ids[] = $value->sprint_id;

                $taskHistoryRecord[] = [
                    'sprint__tasks_id' => $value->task_id,
                    'sprint_id' => $value->sprint_id,
                    'status_id' => 124,
                    'active' => 3,


                ];

                if(!empty($request['image'])){
                    $path=  $this->upload($request['image']);
                    $extArray = explode('.',$path);
                    $extension = end($extArray);
                    if(!isset($path)){
                        return RestAPI::response('File cannot be uploaded due to server error!', false);
                    }
                    $confirmations = [
                        'ordinal' => 1,
                        'task_id' => $value->task_id,
                        'joey_id' => Auth::user()->id,
                        'name'    => 'hub_deliver',
                        'title'   => 'hub_deliver',
                        'confirmed' => 0,
                        'input_type' => $extension,
                        'attachment_path' => $path,
                    ];
                    SprintConfirmation::create($confirmations);
                }



                $sprintHistoryRecord[] = [
                    'sprint__sprints_id' => $value->sprint_id,
                    'vehicle_id' => 3,
                    'status_id' => 124,
                    'active' => 3,

                ];

                CtcEnteries::where('sprint_id','=',$value->sprint_id)->whereNull('deleted_at')->update(['task_status_id' => 124]);
                BoradlessDashboard::where('sprint_id','=',$value->sprint_id)->whereNull('deleted_at')->update(['task_status_id' => 124]);
            }

            //ctc and boradless status updates



            SprintTasks::whereIn('id', $task_ids)->update(['status_id' => 124]);

            Sprint::whereIn('id', $sprint_ids)->update(['status_id' => 124]);

            SprintTaskHistory::insert($taskHistoryRecord);
            SprintSprintHistory::insert($sprintHistoryRecord);

            $response = 'Items picked by this joey has delivered at hub successfully';

            $record = JoeyStorePickup::where('joey_id', $joey->id)
                ->whereIn('tracking_id', $tracking_ids)
                ->whereNull('deleted_at')
                ->update(['deleted_at' => date('Y-m-d H:i:s')]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response(\stdClass::class, true, $response);

    }


    /**
     * for  customer information
     *
     */

    public function customerInfo(Request $num)
    {

        $data = $num->all();
        //    dd($data['num']);

        DB::beginTransaction();
        try {
            $joey = $this->userRepository->find(auth()->user()->id);

            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }

            if (!empty($num)) {
                if (MainfestFields::where('sprint_id', $data['num'])->doesntExist()) {
                    return RestAPI::response('This order does not exists', false);
                }
                $addresses = MainfestFields::where('sprint_id', '=', $data['num'])
                    ->first();
                $response = new MainfestFieldsResource1($addresses);
            }

            if (empty($addresses)) {
                if (SprintTasks::where('sprint_id', $data['num'])->doesntExist()) {
                    return RestAPI::response('This order does not exists', false);
                }
                $addresses = $this->sprintTaskRepository->findWithSprint($data['num']);
                $response = new MainfestFieldsResource2($addresses);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, 'Mainfest Fields');
    }

    /**
     * update status
     *
     */

    public function updateStatus(Request $request)
    {
        $data = $request->all();

        $currentDate=Carbon::now()->format('Y-m-d H:i:s');

        DB::beginTransaction();
        try {
            $delivered_status = [113,114,116,117,118,132,138,139,144];
            $joey = $this->userRepository->find(auth()->user()->id);

            if(empty($data['status_id'])){
                return RestAPI::response('status_id is require', false);
            }
            $statusDescription= StatusMap::getDescription($data['status_id']);

            if(!empty($data['image'])){

                $updateData = [
                    'ordinal' => 2,
                    'task_id' => $data['task_id'],
                    'joey_id' =>$joey->id,
                    'name' =>$statusDescription,
                    'title' =>$statusDescription,
                    'confirmed' => 1,
                    'input_type' => 'image/jpeg',
                    'created_at' => $currentDate,
                    'updated_at' => $currentDate,

                ];

                $path=  $this->upload($data['image']);
                if(!isset($path)){
                    return RestAPI::response('File cannot be uploaded due to server error!', false);
                }

                $updateData['attachment_path'] =$path;

                SprintConfirmation::insert($updateData);
            }

            $sprintId = SprintTasks::find($data['task_id']);

            $taskHistoryRecord = [
                'sprint__tasks_id' =>$data['task_id'],
                'sprint_id' => $sprintId->sprint_id,
                'status_id' => $data['status_id'],
                'date' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s')
            ];


            SprintTasks::where('id',$data['task_id'])->update(['status_id'=>$data['status_id']]);
            SprintTaskHistory::insert( $taskHistoryRecord );

            ItinerariesTask::where('sprint_id', $sprintId->sprint_id)->update(['active' => 0, 'status' => 17]);

            $return_status = [104,105,106,107,108,109,110,111,112,131,135,136,101,102,103];

            if($data['status_id']==67){

                $checkAtPick  = SprintTaskHistory::where('sprint__tasks_id','=',$data['task_id'])
                    ->where('status_id','=',67)
                    ->where('active','=',0)
                    ->first();

                if(empty($checkAtPick)){

                    $pickupTask = SprintTasks::where('id','=',$data['task_id'])
                        ->where('type','=','pickup')
                        ->first();
                    if(!empty($pickupTask )) {
                        $taskHistoryRecord = [
                            'sprint__tasks_id' => $data['task_id'],

                            'sprint_id' => $sprintId->sprint_id,

                            'status_id' => $data['status_id'],
                            'date' => date('Y-m-d H:i:s'),
                            'created_at' => date('Y-m-d H:i:s')
                        ];


                        SprintTaskHistory::insert($taskHistoryRecord);
                        SprintTasks::where('id', '=', $pickupTask->id)->update(['status_id' => $data['status_id']]);
                        SprintTasks::where('sprint_id', '=', $pickupTask->sprint_id) ->where('type', '=', 'dropoff')->update(['status_id' => 24]);

                    }
                }

            }
            else{

                if(!in_array($data['status_id'], $delivered_status)){
                    $disptachId=Dispatch::where('sprint_id', '=', $sprintId->sprint_id)->first();
                    if(!empty($disptachId)){
                        Dispatch::where('id','=',$disptachId->id)->update(['status'=>$data['status_id'],'status_copy'=>$statusDescription]);
                    }
                }

            }

            // new work for delivered status

            if(in_array($data['status_id'], $delivered_status)){

                $task=SprintTasks::where('id',$data['task_id'])->first();
                $task->update(['status_id'=>17]);
                $returnTaskHistoryRecord = [
                    'sprint__tasks_id' => $data['task_id'],
                    'sprint_id' => $task->sprint_id,
                    'status_id' => 17,
                    'date' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s'),
                    'resolve_time' => date('Y-m-d H:i:s')
                ];

                SprintTaskHistory::insert($returnTaskHistoryRecord);

                $allConfirmationCounts=0;
                $confirmedCounts=0;
                if($task->sprintsSprints!=null){
                    $alltasks=$task->sprintsSprints->sprintTask;
                    if(count($alltasks) > 0){
                        foreach ($alltasks as $alltasks_key => $alltasks_value) {
                            $taskConfirmations=$alltasks_value->sprintConfirmation;
                            $allConfirmationCounts+=count($taskConfirmations);
                            if(count($taskConfirmations) > 0){
                                foreach ($taskConfirmations as $taskConfirmations_key => $taskConfirmations_value) {
                                    if($taskConfirmations_value->confirmed==1){
                                        $confirmedCounts+=1;
                                    }
                                }
                            }
                        }
                    }
                }
                if($allConfirmationCounts == $confirmedCounts){
                    Sprint::where('id',$task->sprint_id)->update(['status_id'=>17,'active'=>0]);
                    $disptachId=Dispatch::where('sprint_id', '=', $task->sprint_id)->first();
                    if(!empty($disptachId)){
                        Dispatch::where('id',$disptachId->id)->update(['status'=>17,'status_copy'=>'Delivery success']);
                    }
                }
            }
            /// End new work for delivered status


            //new work for return status
            $return_status = [101,104,105,106,107,108,109,110,111,112,131,135,136];
            if(in_array($data['status_id'], $return_status)){

                $task=SprintTasks::where('id',$data['task_id'])->first();

                $sprint_tasks=SprintTasks::where('sprint_id',$sprintId->sprint_id)->orderBy('ordinal','ASC')->get()->toArray();

                if(count($sprint_tasks)>0){

                    $tasksHere = SprintTasks::where('sprint_id', $task->sprint_id)->where('type', 'return')->first();

                    if($tasksHere == null){

                        $ordinalforreturn=SprintConfirmation::where('task_id',$data['task_id'])->update(['confirmed'=>1]);
                        $ordinal=end($sprint_tasks);
                        $ordinal=$ordinal['ordinal'];
                        $returnTaskData=[];

                        $retun_charge=$this->getReturnCharge($task);
                        $charge = 0;

                        $returnTask = new SprintTasks();

                        foreach ($sprint_tasks as $sprint_task) {
                            if($sprint_task['type']=='pickup'){
                                $returnTaskData=$sprint_task;
                                $returnTaskData['status_id']=18;
                                $returnTaskData['charge']=$retun_charge;
                                $returnTaskData['type']='return';
                                $returnTaskData['ordinal']=$ordinal+1;
                                unset($returnTaskData['id']);
                                break;
                            }
                        }

                        $returnTask = SprintTasks::create($returnTaskData);

                        $sprint = Sprint::find($task->sprint_id);
                        $sprint->task_total = $sprint->task_total+$retun_charge;
                        $sprint->subtotal = $sprint->task_total+$sprint->distance_charge;
                        $sprint->tax = $sprint->subtotal*0.13;
                        $sprint->total = $sprint->subtotal+$sprint->tax+$sprint->tip;
                        $sprint->status_id=18;
                        $sprint->save();

                        $updateData = [
                            'ordinal' => 3,
                            'task_id' => ($returnTask->id) ? $returnTask->id : $data['task_id'],
                            'joey_id' =>$joey->id,
                            'name' =>'default',
                            'title' =>'Confirm Return',
                            'confirmed' => 0,
                            'input_type' => '',
                            'created_at' => date('Y-m-d H:i:s')
                        ];
                        SprintConfirmation::insert($updateData);

                        $returnTaskId =$returnTask->id;


                        $returnTaskHistoryRecord = [
                            'sprint__tasks_id' => ($returnTaskId) ? $returnTaskId : $data['task_id'],
                            'sprint_id' => $sprintId->sprint_id,
                            'status_id' => 18,
                            'date' => date('Y-m-d H:i:s'),
                            'created_at' => date('Y-m-d H:i:s')
                        ];

                        SprintTaskHistory::insert($returnTaskHistoryRecord);

                        $sprint = Sprint::find($task->sprint_id);
                        $sprint->task_total = $sprint->task_total+$retun_charge;
                        $sprint->subtotal = $sprint->task_total+$sprint->distance_charge;
                        $sprint->tax = $sprint->subtotal*0.13;
                        $sprint->total = $sprint->subtotal+$sprint->tax+$sprint->tip;
                        $sprint->status_id=18;
                        $sprint->save();
                    }

                }

            }
            //new work for return status

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response('Success', true, 'Status updated successfully');

    }


// BELOW fUNCTION FOR IMAGE UPLOAD IN ASSETS
    public function upload($base64Data)
    {

        //dd($base64Data);

        //   $request = new Image_JsonRequest();
        $data = ['image' => $base64Data];

        $response = $this->sendData('POST', '/', $data);
        if (!isset($response->url)) {
            return null;

        }
        return $response->url;

    }


    public function sendData($method, $uri, $data = [])
    {


        $host = 'marscouriers.com';

        $json_data = json_encode($data);

        $headers = [
            'Accept-Encoding: utf-8',
            'Accept: application/json; charset=UTF-8',
            'Content-Type: application/json; charset=UTF-8',
            'User-Agent: JoeyCo',
            'Host: ' . $host,
        ];

        if (!empty($json_data)) {

            $headers[] = 'Content-Length: ' . strlen($json_data);
        }

        $url = 'http://marscouriers.com/mars-assets/public/index.php';

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if (strlen($json_data) > 2) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        }

        if (env('APP_ENV') === 'local') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        set_time_limit(0);

        $this->originalResponse = curl_exec($ch);

        $error = curl_error($ch);

        curl_close($ch);

        if (empty($error)) {


            $this->response = explode("\n", $this->originalResponse);

            $code = explode(' ', $this->response[0]);
            $code = $code[1];

            $this->response = $this->response[count($this->response) - 1];
            $this->response = json_decode($this->response);

            if (json_last_error() != JSON_ERROR_NONE) {

                $this->response = (object)[
                    'copyright' => 'Copyright © ' . date('Y') . ' JoeyCo Inc. All rights reserved.',
                    'http' => (object)[
                        'code' => 500,
                        'message' => json_last_error_msg(),// \JoeyCo\Http\Code::get(500),
                    ],
                    'response' => new \stdClass()
                ];
            }
        }

        return $this->response;
    }


    /**
     * for  joey route list
     *
     */

    public function routeslist(Request $request)
    {
        $data = $request->validate([
            'start_date' => 'Required|date_format:Y-m-d',
            'end_date' => 'Required|date_format:Y-m-d',
            'timezone' => 'Required'
        ]);

        $data = $request->all();

        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);


            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }

            if (empty($data['start_date'])) {
                return RestAPI::response('startDate required', false);
            }

            if (empty($data['end_date'])) {
                return RestAPI::response('EndDate required', false);
            }

            $startDate = $data['start_date'] . ' 00:00:00';
            $endDate = $data['end_date'] . ' 23:59:59';


            $startDateConversion = convertTimeZone($startDate, $data['timezone'], 'UTC', 'Y/m/d H:i:s');
            $endDateConversion = convertTimeZone($endDate, $data['timezone'], 'UTC', 'Y/m/d H:i:s');


            $routes = RouteHistory::where('joey_id', $joey->id)
                ->whereBetween('created_at', array($startDateConversion, $endDateConversion))
                // ->whereIn('status',[2,4])
                ->groupBy('route_id')
                ->orderBy('created_at', 'desc')
                ->get();

            foreach ($routes as $k => $v) {
                $routes[$k]['convert_to_timezone'] = $data['timezone'];
            }


            $response = JoeyRouteListResource::collection($routes);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'joey route List');
    }


    /**
     * for  joey route list details
     *
     */
    public function routeslistDetails(Request $request)
    {
        $data = $request->all();

        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);

            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }

            $routes = RouteHistory::where('joey_id', $joey->id)
                ->where('route_id', $data['route_id'])
                ->whereIn('status', [2, 4])
                ->groupBy('route_location_id')
                ->get();

            if (!empty($routes)) {
                $response = JoeyRouteDetailsResource::collection($routes);
            } else {
                return RestAPI::response('No route found against joey and route id', false);
            }


            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'joey Route List Details');
    }


    /**
     * for update itinary status offline
     *
     */
    public function updateStatusItinaryOffline(Request $request)
    {

        $data = $request->all();
        $dateTime = $data['created_at'];
        $wildForkVendorIds = [477625,477633,477635];
        $walMartVendorIds = [477621,477587,477607,477589,477641,477631,477629,477625,477633,477635,477171];
        DB::beginTransaction();
        try {
            $joey = $this->userRepository->find(auth()->user()->id);
            if (empty($data['status_id'])) {
                return RestAPI::response('status_id is require', false);
            }
            $statusDescription = StatusMap::getDescription($data['status_id']);

            $updateData = [
                'ordinal' => 2,
                'task_id' => $data['task_id'],
                'joey_id' => $joey->id,
                'name' => $statusDescription,
                'title' => $statusDescription,
                'confirmed' => 1,
                'input_type' => 'image/jpeg',
                'created_at' => $dateTime,
            ];
            $path = '';
            if (!empty($data['image'])) {
                $path = $this->upload($data['image']);

                if (!isset($path)) {
                    return RestAPI::response('File cannot be uploaded due to server error!', false);
                }

                $updateData['attachment_path'] = $path;
            }


            SprintConfirmation::insert($updateData);
            $sprintId = SprintTasks::find($data['task_id']);
            $taskHistoryRecord = [
                'sprint__tasks_id' => $data['task_id'],
                'sprint_id' => $sprintId->sprint_id,
                'status_id' => $data['status_id'],
                'date' => $dateTime,
                'created_at' => $dateTime
            ];


            $checkfordeliverorreturn = [];
            $delay_status = [103, 102, 137, 140, 102, 137];

            if (in_array($data['status_id'], $delay_status)) {
                $checkfordeliverorreturn = SprintTaskHistory::where('sprint_id', $sprintId->sprint_id)->whereIn('status_id', [101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112, 131, 135, 136, 17, 113, 114, 116, 117, 118, 132, 138, 139, 144])->first();
            }

            if (empty($checkfordeliverorreturn)) {
                SprintTasks::where('id', $data['task_id'])->update(['status_id' => $data['status_id']]);
                Sprint::where('id', $sprintId->sprint_id)->update(['status_id' => $data['status_id']]);
            }

            SprintTaskHistory::insert($taskHistoryRecord);

            $return_status = [101, 104, 105, 106, 107, 108, 109, 110, 111, 112, 131, 135, 136, 143, 146];
            $delivered_status = [17, 113, 114, 116, 117, 118, 132, 138, 139, 144];


            $route_data = JoeyRoutes::join('joey_route_locations', 'joey_route_locations.route_id', '=', 'joey_routes.id')
                ->where('joey_route_locations.task_id', '=', $data['task_id'])
                ->whereNull('joey_route_locations.deleted_at')
                ->first(['joey_route_locations.id', 'joey_routes.joey_id', 'joey_route_locations.route_id', 'joey_route_locations.ordinal']);


            if (!empty($route_data)) {
                $merchantForWm = MerchantsIds::where('task_id', $data['task_id'])->first();
                $taskForWm = SprintTasks::whereNull('deleted_at')->where('id', $data['task_id'])->first();
                $sprintForWm = Sprint::whereNull('deleted_at')->where('id', $taskForWm->sprint_id)->first();
                if (in_array($data['status_id'], $delivered_status)) {

                    if(in_array($sprintForWm->creator_id,$wildForkVendorIds)){
                        try{
                            $vendor = Vendors::find($sprintForWm->creator_id);
                            $contact = SprintContact::where('id', $taskForWm->contact_id)->first();
//                                    $receiverNumber = $contact->phone;
                            $message = 'Dear '.$contact->name.', Your order # '.$merchantForWm->merchant_order_num.' from "'.$vendor->name.'" has been delivered. Get delivery details using https://www.joeyco.com/track-order/'.$merchantForWm->tracking_id.' and also rate our service by clicking on the link https://g.page/r/CaFSrnNcMW1KEB0/review'.'';
//                            $subject = 'Order Delivered';
//                            $contact->sendPickupEmail($contact,$message, $subject);
                            $receiverNumber = $contact->phone;
//                            $message = 'test message';
//
                            $account_sid = "ACb414b973404343e8895b05d5be3cc056";
                            $auth_token = "c135f0fc91ff9fdd0fcb805a6bdf3108";
                            $twilio_number = "+16479316176";

                            $client = new Client($account_sid, $auth_token);
                            $client->messages->create($receiverNumber, [
                                'from' => $twilio_number,
                                'body' => $message]);

                        }catch(\Exception $e){
                        }
                    }
                    if(in_array($sprintForWm->creator_id, $wildForkVendorIds)){

                        $client_id = 'sb-646b6a39-bf8d-4453-93d7-209c90cfa646!b106018|it-rt-cpi-prod-ev6oz563!b56186';
                        $url_token = 'https://cpi-prod-ev6oz563.authentication.us10.hana.ondemand.com/oauth/token';
                        $client_secret = 'b96311a2-af61-48de-b8fd-873a2718622b$kbc8vB_csYmne3vjCdH3GMKGsrFkMnZzc3EJV39kD74=';

                        $curl = curl_init();

                        curl_setopt_array($curl, array(
                            CURLOPT_URL => "$url_token",
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_SSL_VERIFYHOST =>false,
                            CURLOPT_SSL_VERIFYPEER => false,
                            CURLOPT_ENCODING => "",
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 30,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => "POST",
                            CURLOPT_POSTFIELDS => "grant_type=client_credentials&client_id=".$client_id."&client_secret=".$client_secret,
                            CURLOPT_HTTPHEADER => array(
                                "content-type: application/x-www-form-urlencoded"
                            ),
                        ));

                        $oAuth = curl_exec($curl);
                        $oAuth =json_decode($oAuth);

                        $curl2 = curl_init();

                        curl_setopt_array($curl2, array(
                            CURLOPT_URL => 'https://cpi-prod-ev6oz563.it-cpi019-rt.cfapps.us10-002.hana.ondemand.com/http/prod/joeyco/webhook',
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => '',
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 0,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => 'POST',
                            CURLOPT_POSTFIELDS =>'{
                                        "tracking_id": "'.$request->get('tracking_id').'",
                                        "status_id": "125",
                                        "description": "'.$this->test[125].'",
                                        "timestamp": "'.strtotime(date('Y-m-d H:i:s')).'"
                                    }',
                            CURLOPT_HTTPHEADER => array(
                                'Authorization: Bearer '.$oAuth->access_token,
                                'Content-Type: application/json',
                                'Cookie: sap-usercontext=sap-client=100'
                            ),
                        ));

                        $response = curl_exec($curl2);
                        curl_close($curl2);

                    }

                    $routeHistoryRecord = [
                        'route_id' => $route_data->route_id,
                        'route_location_id' => $route_data->id,
                        'ordinal' => $route_data->ordinal,
                        'joey_id' => $route_data->joey_id,
                        'task_id' => $data['task_id'],
                        'status' => 2,
                        'created_at' => $dateTime,
                        'updated_at' => $dateTime
                    ];

                    RouteHistory::insert($routeHistoryRecord);

                } else if (in_array($data['status_id'], $return_status)) {
                    if(in_array($sprintForWm->creator_id, $wildForkVendorIds)){
                        try{
                            $vendor = Vendors::find($sprintForWm->creator_id);
                            $contact = SprintContact::where('id', $taskForWm->contact_id)->first();
                            $receiverNumber = $contact->phone;
                            $message = 'Dear '.$contact->name.', Your order # '.$merchantForWm->merchant_order_num.' from "'.$vendor->name.'" has been returned. Get delivery details using https://www.joeyco.com/track-order/'.$merchantForWm->tracking_id.'';
//                            $subject = 'Order Returned';
//                            $contact->sendPickupEmail($contact,$message, $subject);

                            $account_sid = "ACb414b973404343e8895b05d5be3cc056";
                            $auth_token = "c135f0fc91ff9fdd0fcb805a6bdf3108";
                            $twilio_number = "+16479316176";

                            $client = new Client($account_sid, $auth_token);
                            $client->messages->create($receiverNumber, [
                                'from' => $twilio_number,
                                'body' => $message]);

                        }catch(\Exception $e){
                        }
                    }

                    if(in_array($sprintForWm->creator_id, $wildForkVendorIds)){

                        $client_id = 'sb-646b6a39-bf8d-4453-93d7-209c90cfa646!b106018|it-rt-cpi-prod-ev6oz563!b56186';
                        $url_token = 'https://cpi-prod-ev6oz563.authentication.us10.hana.ondemand.com/oauth/token';
                        $client_secret = 'b96311a2-af61-48de-b8fd-873a2718622b$kbc8vB_csYmne3vjCdH3GMKGsrFkMnZzc3EJV39kD74=';

                        $curl = curl_init();

                        curl_setopt_array($curl, array(
                            CURLOPT_URL => "$url_token",
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_SSL_VERIFYHOST =>false,
                            CURLOPT_SSL_VERIFYPEER => false,
                            CURLOPT_ENCODING => "",
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 30,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => "POST",
                            CURLOPT_POSTFIELDS => "grant_type=client_credentials&client_id=".$client_id."&client_secret=".$client_secret,
                            CURLOPT_HTTPHEADER => array(
                                "content-type: application/x-www-form-urlencoded"
                            ),
                        ));

                        $oAuth = curl_exec($curl);
                        $oAuth =json_decode($oAuth);

                        $curl2 = curl_init();

                        curl_setopt_array($curl2, array(
                            CURLOPT_URL => 'https://cpi-prod-ev6oz563.it-cpi019-rt.cfapps.us10-002.hana.ondemand.com/http/prod/joeyco/webhook',
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => '',
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 0,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => 'POST',
                            CURLOPT_POSTFIELDS =>'{
                                        "tracking_id": "'.$data['tracking_id'].'",
                                        "status_id": "125",
                                        "description": "'.$this->test[125].'",
                                        "timestamp": "'.strtotime(date('Y-m-d H:i:s')).'"
                                    }',
                            CURLOPT_HTTPHEADER => array(
                                'Authorization: Bearer '.$oAuth->access_token,
                                'Content-Type: application/json',
                                'Cookie: sap-usercontext=sap-client=100'
                            ),
                        ));

                        $response = curl_exec($curl2);
                        curl_close($curl2);

                    }

                    $routeHistoryRecord = [
                        'route_id' => $route_data->route_id,
                        'route_location_id' => $route_data->id,
                        'ordinal' => $route_data->ordinal,
                        'joey_id' => $route_data->joey_id,
                        'task_id' => $data['task_id'],
                        'status' => 4,
                        'created_at' => $dateTime,
                        'updated_at' => $dateTime
                    ];

                    RouteHistory::insert($routeHistoryRecord);

                }
                $this->updateAmazonEntry($data['status_id'], $sprintId->sprint_id, $path);
                $this->updateCTCEntry($data['status_id'], $sprintId->sprint_id, $path);
                $this->updateBoradlessDashboard($data['status_id'], $sprintId->sprint_id, $path);
                $this->updateClaims($data['status_id'], $sprintId->sprint_id, $path);

            }


            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response('Success', true, 'Status updated successfully');

    }


    /**
     * update status offline
     *
     */

    public function updateStatusOffline(Request $request)
    {

        $data = $request->all();
        $dateTime=$data['created_at'];

        DB::beginTransaction();
        try {

            $delivered_status = [17,113,114,116,117,118,132,138,139,144];
            $joey = $this->userRepository->find(auth()->user()->id);

            if(empty($data['status_id'])){
                return RestAPI::response('status_id is require', false);
            }


            $sprintId = SprintTasks::find($data['task_id']);

            $checkstatus=[];
            if($data['status_id']==67 || $data['status_id']==68){

                if(!empty($sprintId)){
                    $checkstatus=SprintTaskHistory::where('sprint_id' , $sprintId->sprint_id)->where('status_id' ,$data['status_id'])->first();
                }

            }

            if(empty($checkstatus)){

                $statusDescription= StatusMap::getDescription($data['status_id']);

                if(!empty($data['image'])){
                    $path=  $this->upload($data['image']);
                    if(!isset($path)){
                        return RestAPI::response('File cannot be uploaded due to server error!', false);
                    }

                    $updateData = [
                        'ordinal' => 2,
                        'task_id' => $data['task_id'],
                        'joey_id' =>$joey->id,
                        'name' =>$statusDescription,
                        'title' =>$statusDescription,
                        'confirmed' => 1,
                        'input_type' => 'image/jpeg',
                        'created_at' => $dateTime,
                        'updated_at' => $dateTime,

                    ];

                    $updateData['attachment_path'] =$path;

                    SprintConfirmation::insert($updateData);
                }


                $taskHistoryRecord = [
                    'sprint__tasks_id' =>$data['task_id'],
                    'sprint_id' => $sprintId->sprint_id??'',
                    'status_id' => $data['status_id']??'',
                    'date' => $dateTime,
                    'created_at' => $dateTime
                ];

                SprintTaskHistory::insert( $taskHistoryRecord );

                if($data['status_id']==67){

                    $checkAtPick  = SprintTaskHistory::where('sprint__tasks_id','=',$data['task_id'])
                        ->where('status_id','=',67)
                        ->where('active','=',0)
                        ->first();

                    if(empty($checkAtPick)){

                        $pickupTask = SprintTasks::where('id','=',$data['task_id'])
                            ->where('type','=','pickup')
                            ->first();
                        if(!empty($pickupTask )) {
                            $taskHistoryRecord = [
                                'sprint__tasks_id' => $data['task_id'],
                                'sprint_id' => $sprintId->sprint_id??'',
                                'status_id' => $data['status_id'],
                                'date' => $dateTime,
                                'created_at' => $dateTime
                            ];


                            SprintTaskHistory::insert($taskHistoryRecord);

                            SprintTasks::where('id', '=', $pickupTask->id)
                                ->update(['status_id' => $data['status_id']]);


                            SprintTasks::where('sprint_id', '=', $pickupTask->sprint_id)
                                ->where('type', '=', 'dropoff')
                                ->update(['status_id' => 24]);
                        }
                    }
                }
                else{

                    if(!in_array($data['status_id'], $delivered_status)){
                        $disptachId=Dispatch::where('sprint_id', '=', $sprintId->sprint_id)->first();
                        if(!empty($disptachId)){
                            Dispatch::where('id','=',$disptachId->id)->update(['status'=>$data['status_id'],'status_copy'=>$statusDescription]);
                        }
                    }
                }
            }
            ItinerariesTask::where('sprint_id', $sprintId->sprint_id)->update(['active' => 0, 'status' => 17]);
            // new work for delivered

            if(in_array($data['status_id'], $delivered_status)){

                $task=SprintTasks::where('id',$data['task_id'])->first();

                //check ordinal work
                $checkTaskBySprintIds=SprintTasks::where('sprint_id',$task->sprint_id)->orderBy('ordinal', 'ASC')->get()->toArray();
                $ordinalCheck = end($checkTaskBySprintIds);
                $ordinalCheckLast = $ordinalCheck['ordinal'];
                //end check ordinal work

                $task->update(['status_id'=>17]);


                $returnTaskHistoryRecord = [
                    'sprint__tasks_id' => $data['task_id'],
                    'sprint_id' => $task->sprint_id,
                    'status_id' => 17,
                    'date' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s'),
                    'resolve_time' => date('Y-m-d H:i:s')

                ];

                SprintTaskHistory::insert($returnTaskHistoryRecord);


                if($task->ordinal == $ordinalCheckLast){
                    Sprint::where('id',$task->sprint_id)->update(['status_id'=>17, 'active' =>0]);
                    $disptachId=Dispatch::where('sprint_id', '=', $task->sprint_id)->first();
                    if(!empty($disptachId)){
                        Dispatch::where('id',$disptachId->id)->update(['status'=>17,'status_copy'=>'Delivery success']);
                    }
                }
            }

            // end new work for delivered

            //new work for return status
            $return_status = [101,104,105,106,107,108,109,110,111,112,131,135,136];

            if(in_array($data['status_id'], $return_status)){

                $task=SprintTasks::where('id',$data['task_id'])->first();

                $sprint_tasks=SprintTasks::where('sprint_id',$sprintId->sprint_id)->orderBy('ordinal','ASC')->get()->toArray();

                if(count($sprint_tasks)>0){

                    $tasksHere = SprintTasks::where('sprint_id', $task->sprint_id)->where('type', 'return')->first();

                    if($tasksHere == null){

                        $ordinalforreturn=SprintConfirmation::where('task_id',$data['task_id'])->update(['confirmed'=>1]);
                        $ordinal=end($sprint_tasks);
                        $ordinal=$ordinal['ordinal'];
                        $returnTaskData=[];

                        $retun_charge=$this->getReturnCharge($task);
                        $charge = 0;

                        $returnTask = new SprintTasks();

                        foreach ($sprint_tasks as $sprint_task) {
                            if($sprint_task['type']=='pickup'){
                                $returnTaskData=$sprint_task;
                                $returnTaskData['status_id']=18;
                                $returnTaskData['charge']=$retun_charge;
                                $returnTaskData['type']='return';
                                $returnTaskData['ordinal']=$ordinal+1;
                                unset($returnTaskData['id']);
                                break;
                            }
                        }

                        $returnTask = SprintTasks::create($returnTaskData);

                        $sprint = Sprint::find($task->sprint_id);
                        $sprint->task_total = $sprint->task_total+$retun_charge;
                        $sprint->subtotal = $sprint->task_total+$sprint->distance_charge;
                        $sprint->tax = $sprint->subtotal*0.13;
                        $sprint->total = $sprint->subtotal+$sprint->tax+$sprint->tip;
                        $sprint->status_id=18;
                        $sprint->save();

                        $updateData = [
                            'ordinal' => 3,
                            'task_id' => ($returnTask->id) ? $returnTask->id : $data['task_id'],
                            'joey_id' =>$joey->id,
                            'name' =>'default',
                            'title' =>'Confirm Return',
                            'confirmed' => 0,
                            'input_type' => '',
                            'created_at' => date('Y-m-d H:i:s')
                        ];
                        SprintConfirmation::insert($updateData);

                        $returnTaskId =$returnTask->id;


                        $returnTaskHistoryRecord = [
                            'sprint__tasks_id' => ($returnTaskId) ? $returnTaskId : $data['task_id'],
                            'sprint_id' => $sprintId->sprint_id,
                            'status_id' => 18,
                            'date' => date('Y-m-d H:i:s'),
                            'created_at' => date('Y-m-d H:i:s')
                        ];

                        SprintTaskHistory::insert($returnTaskHistoryRecord);

                        $sprint = Sprint::find($task->sprint_id);
                        $sprint->task_total = $sprint->task_total+$retun_charge;
                        $sprint->subtotal = $sprint->task_total+$sprint->distance_charge;
                        $sprint->tax = $sprint->subtotal*0.13;
                        $sprint->total = $sprint->subtotal+$sprint->tax+$sprint->tip;
                        $sprint->status_id=18;
                        $sprint->save();
                    }

                }

            }
            //new work for return status

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response('Success', true, 'Status updated successfully');

    }


    /**
     * for joey routes pickup offline
     *
     */
    public function trackingPickupOffline(Request $request)
    {

        $data = $request->all();
        $dateTime = $data['created_at'];
        $walMartVendorIds = [477621,477587,477607,477589,477641,477631,477629,477625,477633,477635];
        $wildForkVendorIds = [477625,477633,477635];
        if (empty($data['tracking_id'])) {
            return RestAPI::response('Tracking Id required', false);
        }
        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);


            $trackingIdCheck = MerchantsIds::where('tracking_id', $data['tracking_id'])->first();
            if (empty($trackingIdCheck)) {
                return RestAPI::response('Invalid Tracking Id', false);
            }
            $sprintId = MerchantsIds::join('sprint__tasks', 'merchantids.task_id', '=', 'sprint__tasks.id')
                ->join('joey_route_locations', 'joey_route_locations.task_id', '=', 'sprint__tasks.id')
                ->join('joey_routes', 'joey_route_locations.route_id', '=', 'joey_routes.id')
                ->whereNull('sprint__tasks.deleted_at')
                ->whereNull('joey_route_locations.deleted_at')
                ->whereNull('joey_routes.deleted_at')
                ->where('merchantids.tracking_id', '=', $data['tracking_id'])
                ->first(['sprint__tasks.sprint_id', 'joey_route_locations.id', 'joey_routes.joey_id', 'joey_route_locations.route_id',
                    'joey_route_locations.ordinal', 'joey_route_locations.task_id', 'merchantids.merchant_order_num', 'merchantids.tracking_id']);

            if (!empty($sprintId)) {
                // $sprint_task_history_check=SprintTaskHistory::where('sprint_id',$sprintId->sprint_id)->where('status_id',121)->get();

                // if(empty($sprint_task_history_check)){
                // $Tasks = SprintTasks::where('sprint_id','=',$sprintId->sprint_id)->update(['status_id'=>121]);
                $sprintForWM = Sprint::where('id', $sprintId->sprint_id)->first();
                if(in_array($sprintForWM->creator_id,$wildForkVendorIds)){
                    try{
                        $vendor = Vendors::find($sprintForWM->creator_id);
                        $contact = SprintContact::where('id', $sprintId->contact_id)->first();
                        $message = 'Dear '.$contact->name.', Your order '.$sprintId->merchant_order_num.' from "'.$vendor->name.'" is on the way for delivery. Track your order using https://www.joeyco.com/track-order/'.$sprintId->tracking_id.'';
//                        $subject = 'Order Out For Delivery';
//                        $contact->sendPickupEmail($contact,$message,$subject);
                        $receiverNumber = $contact->phone;

                        $account_sid = "ACb414b973404343e8895b05d5be3cc056";
                        $auth_token = "c135f0fc91ff9fdd0fcb805a6bdf3108";
                        $twilio_number = "+16479316176";

                        $client = new Client($account_sid, $auth_token);
                        $client->messages->create($receiverNumber, [
                            'from' => $twilio_number,
                            'body' => $message]);

                    }catch(\Exception $e){

                    }
                }

                if(in_array($sprintForWM->creator_id, $wildForkVendorIds)){

                    $client_id = 'sb-646b6a39-bf8d-4453-93d7-209c90cfa646!b106018|it-rt-cpi-prod-ev6oz563!b56186';
                    $url_token = 'https://cpi-prod-ev6oz563.authentication.us10.hana.ondemand.com/oauth/token';
                    $client_secret = 'b96311a2-af61-48de-b8fd-873a2718622b$kbc8vB_csYmne3vjCdH3GMKGsrFkMnZzc3EJV39kD74=';

                    $curl = curl_init();

                    curl_setopt_array($curl, array(
                        CURLOPT_URL => "$url_token",
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_SSL_VERIFYHOST =>false,
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_ENCODING => "",
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 30,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => "POST",
                        CURLOPT_POSTFIELDS => "grant_type=client_credentials&client_id=".$client_id."&client_secret=".$client_secret,
                        CURLOPT_HTTPHEADER => array(
                            "content-type: application/x-www-form-urlencoded"
                        ),
                    ));

                    $oAuth = curl_exec($curl);
                    $oAuth =json_decode($oAuth);

                    $curl2 = curl_init();

                    curl_setopt_array($curl2, array(
                        CURLOPT_URL => 'https://cpi-prod-ev6oz563.it-cpi019-rt.cfapps.us10-002.hana.ondemand.com/http/prod/joeyco/webhook',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS =>'{
                       "tracking_id": "'.$request->get('tracking_id').'",
                       "status_id": "125",
                       "description": "'.$this->test[125].'",
                       "timestamp": "'.strtotime(date('Y-m-d H:i:s')).'"
                   }',
                        CURLOPT_HTTPHEADER => array(
                            'Authorization: Bearer '.$oAuth->access_token,
                            'Content-Type: application/json',
                            'Cookie: sap-usercontext=sap-client=100'
                        ),
                    ));

                    $response = curl_exec($curl2);
                    curl_close($curl2);

                }

                // Sprint::where('id',$sprintId->sprint_id)->update(['status_id'=>121]);
                $checkfordeliverorreturn = [];
                // deliver and return statuses
                $checkfordeliverorreturn = SprintTaskHistory::where('sprint_id', $sprintId->sprint_id)->whereIn('status_id', [101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112, 131, 135, 136, 17, 113, 114, 116, 117, 118, 132, 138, 139, 144])->first();

                if (empty($checkfordeliverorreturn)) {
                    $Tasks = SprintTasks::where('sprint_id', '=', $sprintId->sprint_id)->update(['status_id' => 121]);
                    Sprint::where('id', $sprintId->sprint_id)->update(['status_id' => 121]);
                    $this->updateAmazonEntry(121, $sprintId->sprint_id);
                    $this->updateCTCEntry(121, $sprintId->sprint_id);
                    $this->updateBoradlessDashboard(121, $sprintId->sprint_id);
                    $this->updateClaims(121, $sprintId->sprint_id);

                }
                $taskHistoryRecord = [
                    'sprint__tasks_id' => $sprintId->task_id,
                    'sprint_id' => $sprintId->sprint_id,
                    'status_id' => 121,
                    'date' => $dateTime,
                    'created_at' => $dateTime,

                ];
                SprintTaskHistory::insert($taskHistoryRecord);
                //dd($taskHistoryRecord);

                $routeHistoryRecord = [
                    'route_id' => $sprintId->route_id,
                    'route_location_id' => $sprintId->id,
                    'ordinal' => $sprintId->ordinal,
                    'joey_id' => $sprintId->joey_id,
                    'task_id' => $sprintId->task_id,
                    'status' => 3,
                    'created_at' => $dateTime,
                    'updated_at' => $dateTime
                ];
                RouteHistory::insert($routeHistoryRecord);
                // $this->updateAmazonEntry(121, $sprintId->sprint_id);
                // }
            } else {
                return RestAPI::response('No route found against this tracking id', false);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response('success', true, 'Packages picked successfully');

    }


    /**
     * trackin id
     *
     */
    public function orderImage(Request $request)
    {

        $data = $request->all();


        if (empty($data['tracking_id'])) {
            return RestAPI::response('Tracking Id required', false);
        }
        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);

            $merchantRecord = MerchantsIds::where('tracking_id', $data['tracking_id'])->first();
            if (empty($merchantRecord)) {
                return RestAPI::response('Invalid Tracking Id', false);
            }

            $record = [

                'tracking_id' => $data['tracking_id'],
                'task_id' => $merchantRecord->task_id,
            ];
            if (!empty($data['image'])) {
                $path = $this->upload($data['image']);
                if (!isset($path)) {
                    return RestAPI::response('File cannot be uploaded due to server error!', false);
                }

                $record['image'] = $path;
            }

            OrderImage::create($record);


            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response('success', true, 'Tracking Image added');

    }


    /**
     * function for bulk tracking ids
     *
     */
    public function trackingIdsBulk(Request $request)
    {

        $data = $request->all();

        $walMartVendorIds = [477621,477587,477607,477589,477641,477631,477629,477625,477633,477635,477171];

        if (empty($data['tracking_id'])) {
            return RestAPI::response('Atleast One Tracking Id is required', false);
        }
        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);
            $currentDate = Carbon::now()->format('Y/m/d H:i:s');
            $merchantRecords = MerchantsIds::whereNull('deleted_at')->whereIn('tracking_id', $data['tracking_id'])->get();

            if (empty($merchantRecords)) {
                return RestAPI::response('Invalid Tracking Ids', false);
            }

            foreach ($merchantRecords as $merchantRecord) {
                // $sprint_task_history_check=SprintTaskHistory::where('sprint_id',$merchantRecord->taskids->sprintsSprints->id)->where('status_id',121)->get();
                // if(empty($sprint_task_history_check)){
                /**
                 * getting records from different tables interlinked witheach oth
                 *
                 */
                if (!empty($merchantRecord)) {
                    $sprintTask = SprintTasks::where('id', $merchantRecord->task_id)->first();
                }

                if (!empty($sprintTask)) {
                    $sprintSprint = Sprint::where('id', $sprintTask->sprint_id)->first();
                }


                if (!empty($merchantRecord)) {
                    $joeyRouteLocation = JoeyRouteLocation::whereNull('deleted_at')->where('task_id', $merchantRecord->task_id)->first();
                }


                if (!empty($joeyRouteLocation)) {
                    $joeyRoute = JoeyRoutes::whereNull('deleted_at')->where('id', $joeyRouteLocation->route_id)->first();
                }

//                if(isset($sprintTask->sprint_id)){
                $taskHistoryRecord = [
                    'sprint__tasks_id' => $merchantRecord->task_id ?? '',
                    'sprint_id' => $sprintTask->sprint_id ?? '',
                    'status_id' => 121,
                    'date' => $currentDate ?? '',
                    'created_at' => $currentDate ?? ''

                ];

                $routeHistoryRecord = [
                    'route_id' => $joeyRouteLocation->route_id ?? '',
                    'route_location_id' => $joeyRouteLocation->id ?? '',
                    'ordinal' => $joeyRouteLocation->ordinal ?? '',
                    'joey_id' => $joeyRoute->joey_id ?? '',
                    'task_id' => $merchantRecord->task_id ?? '',
                    'status' => 3,
                    'created_at' => $currentDate,
                    'updated_at' => $currentDate
                ];

                SprintTasks::where('id', $merchantRecord->task_id)->update(['status_id' => 121]);
                Sprint::where('id', $sprintTask->sprint_id)->update(['status_id' => 121]);
                SprintTaskHistory::insert($taskHistoryRecord);
                RouteHistory::insert($routeHistoryRecord);
                $this->updateAmazonEntry(121, $sprintTask->sprint_id);
                $this->updateCTCEntry(121, $sprintTask->sprint_id);
                $this->updateBoradlessDashboard(121, $sprintTask->sprint_id);
                $this->updateClaims(121, $sprintTask->sprint_id);

                if(isset($sprintSprint)){
                    if(in_array($sprintSprint->creator_id,$walMartVendorIds)){
                        try {
                            $contact = SprintContact::where('id', $sprintTask->contact_id)->first();
                            $vendor = Vendor::where('id', $sprintSprint->creator_id)->first();
                            $receiverNumber = $contact->phone;
                            $message = 'Dear ' . $contact->name . ', Your order # ' . $merchantRecord->merchant_order_num . ' from "' . $vendor->name . '" is on the way for delivery. Track your order using https://www.joeyco.com/track-order/' . $merchantRecord->tracking_id .'';
//                            $subject = 'Order Out For Delivery';
//                            $contact->sendPickupEmail($contact,$message, $subject);
                            $account_sid = "ACb414b973404343e8895b05d5be3cc056";
                            $auth_token = "c135f0fc91ff9fdd0fcb805a6bdf3108";
                            $twilio_number = "+16479316176";

                            $client = new Client($account_sid, $auth_token);
                            $client->messages->create($receiverNumber, [
                                'from' => $twilio_number,
                                'body' => $message]);
                        }catch (\Exception $e){
                            continue;
                        }
                    }
                }
            }
//            }


            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response('success', true, 'All trackings marked pick successfully');

    }


    /**
     * for  tracking details
     *
     */
    public function trackingDetails(Request $request)
    {
        $data = $request->all();

        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);

            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }

            $merchantRecord = MerchantsIds::where('tracking_id', $data['tracking_id'])->first();


            if (!empty($merchantRecord)) {

                $response = new TrackingDetailsResource($merchantRecord);
            } else {
                return RestAPI::response('No record found agianst this Tracking Id', false);
            }


            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'Tracking Details');
    }

    public function updateAmazonEntry($status_id, $order_id, $imageUrl = null)
    {
        if ($status_id == 133) {
            // Get amazon enteries data from tracking id and check if the data exist in database and if exist update the sort date of the tracking id and status of that tracking id.
            $amazon_enteries = AmazonEnteries::where('sprint_id', '=', $order_id)->whereNull('deleted_at')->first();
            if ($amazon_enteries != null) {

                $amazon_enteries->sorted_at = date('Y-m-d H:i:s');
                $amazon_enteries->task_status_id = 133;
                $amazon_enteries->order_image = $imageUrl;
                $amazon_enteries->save();

            }
        } elseif ($status_id == 121) {
            $amazon_enteries = AmazonEnteries::where('sprint_id', '=', $order_id)->whereNull('deleted_at')->first();
            if ($amazon_enteries != null) {
                $amazon_enteries->picked_up_at = date('Y-m-d H:i:s');
                $amazon_enteries->task_status_id = 121;
                $amazon_enteries->order_image = $imageUrl;
                $amazon_enteries->save();

            }
        } elseif (in_array($status_id, [17, 113, 114, 116, 117, 118, 132, 138, 139, 144])) {
            $amazon_enteries = AmazonEnteries::where('sprint_id', '=', $order_id)->whereNull('deleted_at')->first();
            if ($amazon_enteries != null) {
                $amazon_enteries->delivered_at = date('Y-m-d H:i:s');
                $amazon_enteries->task_status_id = $status_id;
                $amazon_enteries->order_image = $imageUrl;
                $amazon_enteries->save();

            }
        } elseif (in_array($status_id, [104, 105, 106, 107, 108, 109, 110, 111, 112, 131, 135, 136, 101, 102, 103, 143])) {
            $amazon_enteries = AmazonEnteries::where('sprint_id', '=', $order_id)->whereNull('deleted_at')->first();
            if ($amazon_enteries != null) {
                $amazon_enteries->returned_at = date('Y-m-d H:i:s');
                $amazon_enteries->task_status_id = $status_id;
                $amazon_enteries->order_image = $imageUrl;
                $amazon_enteries->save();

            }
        }

    }

    public function updateCTCEntry($status_id, $order_id, $imageUrl = null)
    {
        if ($status_id == 133) {
            // Get ctc enteries data from tracking id and check if the data exist in database and if exist update the sort date of the tracking id and status of that tracking id.
            $ctc_enteries = CtcEnteries::where('sprint_id', '=', $order_id)->whereNull('deleted_at')->first();
            // print_r($order_id);die;
            if ($ctc_enteries != null) {

                $ctc_enteries->sorted_at = date('Y-m-d H:i:s');
                $ctc_enteries->task_status_id = 133;
                $ctc_enteries->order_image = $imageUrl;
                $ctc_enteries->save();

            }
        } elseif ($status_id == 121) {
            $ctc_enteries = CtcEnteries::where('sprint_id', '=', $order_id)->whereNull('deleted_at')->first();
            if ($ctc_enteries != null) {
                $ctc_enteries->picked_up_at = date('Y-m-d H:i:s');
                $ctc_enteries->task_status_id = 121;
                $ctc_enteries->order_image = $imageUrl;
                $ctc_enteries->save();

            }
        } elseif (in_array($status_id, [17, 113, 114, 116, 117, 118, 132, 138, 139, 144])) {
            $ctc_enteries = CtcEnteries::where('sprint_id', '=', $order_id)->whereNull('deleted_at')->first();
            if ($ctc_enteries != null) {
                $ctc_enteries->delivered_at = date('Y-m-d H:i:s');
                $ctc_enteries->task_status_id = $status_id;
                $ctc_enteries->order_image = $imageUrl;
                $ctc_enteries->save();

            }
        } elseif (in_array($status_id, [104, 105, 106, 107, 108, 109, 110, 111, 112, 131, 135, 136, 101, 102, 103, 143])) {
            $ctc_enteries = CtcEnteries::where('sprint_id', '=', $order_id)->whereNull('deleted_at')->first();
            if ($ctc_enteries != null) {
                $ctc_enteries->returned_at = date('Y-m-d H:i:s');
                $ctc_enteries->task_status_id = $status_id;
                $ctc_enteries->order_image = $imageUrl;
                $ctc_enteries->save();

            }
        }

    }

    public function updateBoradlessDashboard($status_id, $order_id, $imageUrl = null)
    {
        if ($status_id == 133) {
            // Get ctc enteries data from tracking id and check if the data exist in database and if exist update the sort date of the tracking id and status of that tracking id.
            $boradless = BoradlessDashboard::where('sprint_id', '=', $order_id)->whereNull('deleted_at')->first();
            // print_r($order_id);die;
            if ($boradless != null) {

                $boradless->sorted_at = date('Y-m-d H:i:s');
                $boradless->task_status_id = 133;
                $boradless->order_image = $imageUrl;
                $boradless->save();

            }
        } elseif ($status_id == 121) {
            $boradless = BoradlessDashboard::where('sprint_id', '=', $order_id)->whereNull('deleted_at')->first();
            if ($boradless != null) {
                $boradless->picked_up_at = date('Y-m-d H:i:s');
                $boradless->task_status_id = 121;
                $boradless->order_image = $imageUrl;
                $boradless->save();

            }
        } elseif (in_array($status_id, [17, 113, 114, 116, 117, 118, 132, 138, 139, 144])) {
            $boradless = BoradlessDashboard::where('sprint_id', '=', $order_id)->whereNull('deleted_at')->first();
            if ($boradless != null) {
                $boradless->delivered_at = date('Y-m-d H:i:s');
                $boradless->task_status_id = $status_id;
                $boradless->order_image = $imageUrl;
                $boradless->save();

            }
        } elseif (in_array($status_id, [104, 105, 106, 107, 108, 109, 110, 111, 112, 131, 135, 136, 101, 102, 103, 143])) {
            $boradless = BoradlessDashboard::where('sprint_id', '=', $order_id)->whereNull('deleted_at')->first();
            if ($boradless != null) {
                $boradless->returned_at = date('Y-m-d H:i:s');
                $boradless->task_status_id = $status_id;
                $boradless->order_image = $imageUrl;
                $boradless->save();

            }
        }

    }

    public function getTaskPay($joey=[],$task=[])
    {
        $joey_pay=0;
        $joeyco_pay=$task->charge;
        $joeyZone_shift_check=[];
        $from = date('Y-m-d').' 00:00:00';
        $to = date('Y-m-d').' 23:59:59';

        $joeyZone_shift_check=JoeysZoneSchedule::where('start_time','<=',$to)
            ->whereNull('end_time')
            ->whereBetween('start_time', [$from, $to])
            ->where('joey_id',$joey->id)->first();

        if($joeyZone_shift_check!=null){ //on shift

            if(($joeyZone_shift_check->ZoneSchedule)!=null){
                if ($joeyZone_shift_check->ZoneSchedule->commission!=null) {
                    // $joey_pay=number_format((float)($joeyZone_shift_check->ZoneSchedule->commission/100), 2, '.', '')*$sprint_rec->subtotal;
                    // $joeyco_pay=number_format((float)(($sprint_rec->subtotal)-$joey_pay),1, '.', '');
                    $joey_pay=number_format((float)($joeyZone_shift_check->ZoneSchedule->commission/100), 2, '.', '')*$task->charge;
                    $joeyco_pay=number_format((float)(($task->charge)-$joey_pay),1, '.', '');

                }else{
                    if($joey->getPlan!=null) {
                        if($joey->getPlan->scheduled_commission!=null){
                            $joey_pay=number_format((float)($joey->getPlan->scheduled_commission/100), 2, '.', '')*$task->charge;
                            $joeyco_pay=number_format((float)(($task->charge)-$joey_pay),1, '.', '');

                        }
                    }
                }
            }
        }
        else{ //off shift
            if($joey->getPlan!=null) {
                if($joey->getPlan->unscheduled_commission!=null){
                    $joey_pay=number_format((float)($joey->getPlan->unscheduled_commission/100), 2, '.', '')*$task->charge;
                    $joeyco_pay=number_format((float)(($task->charge)-$joey_pay),1, '.', '');

                }
            }
        }
        $return['joey_pay']= $joey_pay;
        $return['joeyco_pay']=$joeyco_pay;

        return $return;
    }

    public function getSprintPay($all_tasks=[], $currentTask)
    {
        $total_joey_pay=0;
        $total_joeyco_pay=0;
        $joey_tax_pay=0;
        if(count($all_tasks)>0){

            $lastTask = $all_tasks;
            $lastDropOff = end($lastTask);

            if($currentTask->ordinal == $lastDropOff['ordinal']){

                $joey_pay=0;
                $joeyco_pay=0;
                $joeyZone_shift_check=[];
                $from = date('Y-m-d').' 00:00:00';
                $to = date('Y-m-d').' 23:59:59';

                $joey = User::find(auth()->user()->id);

                $sprint = Sprint::find($lastDropOff['sprint_id']);

                $totalCharge = $sprint->task_total+$sprint->distance_charge;

                $taxcharges = 0;
                $joey_tax_pay=0;
                if(!empty($joey->hst_number) || $joey->hst_number != NUll || $joey->hst_number != ''){
                    $taxcharges = $sprint->tax;
                }

                $joeyZone_shift_check=JoeysZoneSchedule::where('start_time','<=',$to)
                    ->whereNull('end_time')
                    ->whereBetween('start_time', [$from, $to])
                    ->where('joey_id',$joey->id)->first();

                if($joeyZone_shift_check!=null){ //on shift
                    if(($joeyZone_shift_check->ZoneSchedule)!=null){
                        if ($joeyZone_shift_check->ZoneSchedule->commission!=null) {
                            $joey_pay=number_format((float)($joeyZone_shift_check->ZoneSchedule->commission/100), 2, '.', '')*$totalCharge;

                            if($taxcharges > 0){
                                $joey_tax_pay=number_format((float)($joeyZone_shift_check->ZoneSchedule->commission/100), 2, '.', '')*$taxcharges;
                            }

                            $joeyco_pay=number_format((float)(($totalCharge)-$joey_pay),1, '.', '');

                        }else{
                            if($joey->getPlan!=null) {
                                if($joey->getPlan->scheduled_commission!=null){
                                    $joey_pay=number_format((float)($joey->getPlan->scheduled_commission/100), 2, '.', '')*$totalCharge;

                                    if($taxcharges > 0){
                                        $joey_tax_pay=number_format((float)($joey->getPlan->scheduled_commission/100), 2, '.', '')*$taxcharges;
                                    }

                                    $joeyco_pay=number_format((float)(($totalCharge)-$joey_pay),1, '.', '');

                                }
                            }
                        }
                    }
                }
                else{ //off shift
                    if($joey->getPlan!=null) {
                        if($joey->getPlan->unscheduled_commission!=null){
                            $joey_pay=number_format((float)($joey->getPlan->unscheduled_commission/100), 2, '.', '')*$totalCharge;

                            if($taxcharges > 0){
                                $joey_tax_pay=number_format((float)($joey->getPlan->unscheduled_commission/100), 2, '.', '')*$taxcharges;
                            }

                            $joeyco_pay=number_format((float)(($totalCharge)-$joey_pay),1, '.', '');

                        }
                    }
                }
                $return['total_joey_pay']= $joey_pay;
                $return['joey_tax_pay']=$joey_tax_pay;
                $return['total_joeyco_pay']=$joeyco_pay;
                return $return;
            }

            foreach ($all_tasks as $singleTask) {
                $total_joey_pay+=$singleTask->joey_pay;
                $total_joeyco_pay+=$singleTask->joeyco_pay;
            }
        }
        $return['total_joey_pay']= $total_joey_pay;
        $return['joey_tax_pay']=$joey_tax_pay;
        $return['total_joeyco_pay']=$total_joeyco_pay;
        return $return;
    }

    public function getReturnCharge($task)
    {
        $retun_charge = 12;
        if ($task->sprintsSprints->vendor->vendorPackage != null) {
            if ($task->sprintsSprints->vendor->vendorPackage->vehicleCharge() != null) {
                $return_task_charge = $task->sprintsSprints->vendor->vendorPackage->vehicleCharge()->where('vehicle_id', $task->sprintsSprints->vehicle_id)
                    ->where('type', 'return')
                    ->where('limit', 1)
                    ->sortByDesc('created_at');
                // ->sortBy('created_at');
                if (count($return_task_charge) == 0) {
                    $return_task_charge = $task->sprintsSprints->vendor->vendorPackage->vehicleCharge()->where('vehicle_id', $task->sprintsSprints->vehicle_id)
                        ->where('type', 'custom_return')
                        ->where('limit', 1)
                        ->sortByDesc('created_at');
                }
                $return_task_charge = $return_task_charge->all();
                $return_task_charge = reset($return_task_charge) ?? '';
                if ($return_task_charge != null) {
                    $retun_charge = $return_task_charge->price ?? 0;
                }
            }
        }
        return $retun_charge;
    }

    public function updateClaims($sprint_status_id,$sprint_id,$imageUrl=null)
    {
        $updateData = [
            'sprint_status_id'=>$sprint_status_id,
        ];
        if ($imageUrl != null)
        {
            $updateData['image'] = $imageUrl;
        }
        Claim::where('sprint_id',$sprint_id)->update($updateData);
    }

    function recordJoeyPayment($task=[],$total_joeyco_pay,$joey_tax_pay){
        $tip=0;
        $balance=0;
        $transaction=FinancialTransactions::create([
            'reference'=>'CR-'.$task->sprint_id,
            'description'=>'CR-'.$task->sprint_id.' Confirmed',
            'amount'=>$total_joeyco_pay,
            'merchant_order_num'=>($task->merchantIds!=null)?$task->merchantIds->merchant_order_num:null
        ]);

        $joey_id=$task->sprintsSprints->joey_id;
        $lastJoeyTransaction=JoeyTransactions::where('joey_id',$joey_id)->orderBy('transaction_id','desc')->first();


        $taskAcceptedJoey=SprintTaskHistory::where('status_id',32)->where('sprint__tasks_id',$task->id)->where('sprint_id',$task->sprint_id)->first();

        $secsDiff =0;
        $joeyzone=[];
        if($taskAcceptedJoey!=null){

            $secsDiff = time() - strtotime($taskAcceptedJoey->date);

            $joeyzone=JoeysZoneSchedule::where('joey_id',$joey_id)->where('start_time', '<=',$taskAcceptedJoey->date)->whereNull('end_time')->orderBy('id','DESC')->first();
        }
        $joeyTransactionsdata=[
            'transaction_id'=>$transaction->id,
            'joey_id'=>$joey_id,
            'type'=>'sprint',
            'payment_method'=>null,
            'distance'=>($task->sprintsSprints!=null)?$task->sprintsSprints->distance:null,
            'duration'=>($secsDiff)?$secsDiff:0,
            'date_identifier'=>null,
            'shift_id'=>($joeyzone!=null)?$joeyzone->zone_schedule_id:null,
            'balance'=>((isset($lastJoeyTransaction->balance))?$lastJoeyTransaction->balance:0)+$total_joeyco_pay
        ];
        JoeyTransactions::insert($joeyTransactionsdata);
        $balance=$joeyTransactionsdata['balance'];

        // Tax Transaction //

        if($joey_tax_pay > 0){

            $transactionTax=FinancialTransactions::create([
                'reference'=>'CR-'.$task->sprint_id.'-Tax',
                'description'=>'Tax for Order: CR-'.$task->sprint_id,
                'amount'=>($joey_tax_pay)?$joey_tax_pay:null,
                'merchant_order_num'=>($task->merchantIds!=null)?$task->merchantIds->merchant_order_num:null
            ]);

            $joeyTaxTransactionsdata=[
                'transaction_id'=>$transactionTax->id,
                'joey_id'=>$joey_id,
                'type'=>'tax',
                'payment_method'=>null,
                'distance' => null,
                'duration'=> null,
                'date_identifier'=>null,
                'shift_id'=>($joeyzone!=null)?$joeyzone->zone_schedule_id:null,
                'balance'=>$balance+$joey_tax_pay
            ];
            JoeyTransactions::insert($joeyTaxTransactionsdata);

            $balance=$joeyTaxTransactionsdata['balance'];

        }

        // Tax Transaction End


        //Tip------------------------------------------------------------------------------------------------------------------

        $allTasks=$task->sprintsSprints->sprintTask;
        $lastTask=$allTasks[count($allTasks)-1];


        if($lastTask->id==$task->id){

            $tip=($task->sprintsSprints->tip==null)?0:$task->sprintsSprints->tip;

            if($tip > 0){
                $transactionTip=FinancialTransactions::create([
                    'reference'=>'CR-'.$task->sprint_id.'-tip',
                    'description'=>'Tip for Order: CR-'.$task->sprint_id,
                    'amount'=>($tip)?$tip:0,
                    'merchant_order_num'=>($task->merchantIds!=null)?$task->merchantIds->merchant_order_num:null
                ]);

                $joeyTipTransactionsdata=[
                    'transaction_id'=>$transactionTip->id,
                    'joey_id'=>$joey_id,
                    'type'=>'tip',
                    'payment_method'=>null,
                    'distance' => null,
                    'duration'=> null,
                    'date_identifier'=>null,
                    'shift_id'=>($joeyzone!=null)?$joeyzone->zone_schedule_id:null,
                    'balance'=>$balance+$tip
                ];
                JoeyTransactions::insert($joeyTipTransactionsdata);

                $balance=$joeyTipTransactionsdata['balance'];
            }


        }

        //Tip--------------------------------------------------------------------------------------------------------------------------

        Joey::where('id',$joey_id)->update(['balance'=> $balance]);




    }

    function recordVendorPayment($task=[],$total_vendor_pay){
        $transaction=FinancialTransactions::create([
            'reference'=>'CR-'.$task->sprint_id,
            'description'=>'CR-'.$task->sprint_id.' Confirmed',
            'amount'=>$total_vendor_pay,
            'merchant_order_num'=>($task->merchantIds!=null)?$task->merchantIds->merchant_order_num:null
        ]);

        $vendor_id=$task->sprintsSprints->creator_id;
        $lastvendorTransaction=VendorTransaction::where('vendor_id',$vendor_id)->orderBy('transaction_id','desc')->first();


        $scheduleTime=SprintTaskHistory::where('status_id',24)->where('sprint__tasks_id',$task->id)->where('sprint_id',$task->sprint_id)->orderBy('id','DESC')->first();
        $pickUpTime=SprintTaskHistory::whereIn('status_id',[28,15])->where('sprint_id',$task->sprint_id)->orderBy('id','DESC')->first();


        $secsDiff=0;
        if(isset($pickUpTime) && isset($scheduleTime)){
            if($scheduleTime!=null && $pickUpTime != null){
                $secsDiff = strtotime($pickUpTime->date) - strtotime($scheduleTime->date);
            }
        }


        $vendorTransactionsdata=[
            'transaction_id'=>$transaction->id,
            'vendor_id'=>$vendor_id,
            'type'=>'sprint',
            'payment_method'=>null,
            'distance'=>($task->sprintsSprints!=null)?$task->sprintsSprints->distance:null,
            'duration'=>$secsDiff,
            'date_identifier'=>null,
            'balance'=>((isset($lastvendorTransaction->balance))?$lastvendorTransaction->balance:0)+$total_vendor_pay
        ];
        VendorTransaction::insert($vendorTransactionsdata);

        // tip transaction of vendor

        $allTasks=$task->sprintsSprints->sprintTask;
        $lastTask=$allTasks[count($allTasks)-1];

        $lastvendorTransactionData=VendorTransaction::where('vendor_id',$vendor_id)->orderBy('transaction_id','desc')->first();

        if($lastTask->id==$task->id){
            $tip=($task->sprintsSprints->tip==null)?0:$task->sprintsSprints->tip;

            if($tip > 0){
                $transactionTip=FinancialTransactions::create([
                    'reference'=>'CR-'.$task->sprint_id.'-tip',
                    'description'=>'Tip for Order: CR-'.$task->sprint_id,
                    'amount'=>$tip,
                    'merchant_order_num'=>($task->merchantIds!=null)?$task->merchantIds->merchant_order_num:null
                ]);

                $vendorTransactionsdata=[
                    'transaction_id'=>$transactionTip->id,
                    'vendor_id'=>$vendor_id,
                    'type'=>'tip',
                    'payment_method'=>null,
                    'distance'=>null,
                    'duration'=>null,
                    'date_identifier'=>null,
                    'balance'=>((isset($lastvendorTransactionData->balance))?$lastvendorTransactionData->balance:0)+$task->sprintsSprints->tip
                ];
                VendorTransaction::insert($vendorTransactionsdata);

            }
        }

    }
    public function storepickupNew(Request $request)
    {
        $currentDate = Carbon::now()->format('Y/m/d H:i:s');

        $request = $request->all();

        DB::beginTransaction();
        try {
            $joey = $this->userRepository->find(auth()->user()->id);

            $joeyPickupStore = JoeyStorePickup::where('tracking_id',$request['tracking_id'])->where('route_id', $request['route_id'])->whereNull('deleted_at')->first();

            if($joeyPickupStore){
                return RestAPI::response('Tracking id is already pickup', false);
            }

            $joeydDeliveredStore = JoeyStorePickup::where('tracking_id',$request['tracking_id'])->where('route_id', $request['route_id'])->whereNotNull('deleted_at')->first();

            if($joeydDeliveredStore){
                return RestAPI::response('Tracking id is already delivered', false);
            }

            if (empty($request['tracking_id'])) {
                return RestAPI::response('Tracking Id is require', false);
            }

            if (empty($request['route_id'])) {
                return RestAPI::response('Route Id is require', false);
            }

            if (empty($joey)) {
                return RestAPI::response('Joey record not found', false);
            }

            $validJoey = JoeyRoutes::where('joey_id', $joey->id)->where('id', $request['route_id'])->first();

            if(!$validJoey){
                return RestAPI::response('This route is not assigned this joey', false);
            }

            $record = JoeyStorePickup::where('joey_id', $joey->id)
                ->where('tracking_id', $request['tracking_id'])
                ->whereNull('deleted_at')
                ->first();

            if(!empty($record)){
                return RestAPI::response('Success', true, 'item pickup from  store successfully');
            }

            $data = MerchantsIds::join('sprint__tasks', 'sprint__tasks.id', '=', 'merchantids.task_id')
                ->join('sprint__sprints', 'sprint__sprints.id', '=', 'sprint__tasks.sprint_id')
                ->wherenull('sprint__sprints.deleted_at')
                ->whereNull('sprint__tasks.deleted_at')
                ->where('merchantids.tracking_id', '=', $request['tracking_id'])
                ->first(['sprint__tasks.sprint_id', 'sprint__sprints.vehicle_id', 'sprint__tasks.id as task_id']);

            if (empty($data)) {
                return RestAPI::response('Invalid tracking code', false);
            }


            SprintTasks::where('sprint_id', '=', $data->sprint_id)->update(['status_id' => 125]);
            //$task=SprintTasks::where('sprint_id','=',$data->sprint_id)->where('ordinal','=',1)->whereNull('deleted_at')->first();
            Sprint::where('id', '=', $data->sprint_id)->whereNull('deleted_at')->update(['status_id' => 125]);


            $taskHistoryRecord = [
                'sprint__tasks_id' => $data->task_id,
                'sprint_id' => $data->sprint_id,
                'status_id' => 125,
                'active' => 1,
                'date' => $currentDate,
                'created_at' => $currentDate

            ];


            SprintTaskHistory::insert($taskHistoryRecord);


            $sprintHistoryRecord = [
                'sprint__sprints_id' => $data->sprint_id,
                'vehicle_id' => $data->vehicle_id,
                'status_id' => 125,
                'active' => 1,
                'date' => $currentDate,
                'created_at' => $currentDate
            ];

            SprintSprintHistory::insert($sprintHistoryRecord);

            //update ctc entries
            $ctc_entries = CtcEnteries::where('sprint_id','=',$data->sprint_id)->whereNUll('deleted_at')->update(['task_status_id' => 125]);

            $boradless = BoradlessDashboard::where('sprint_id','=',$data->sprint_id)->whereNull('deleted_at')->update(['task_status_id' => 125]);


            $joeyStopPickupRecord = [
                'joey_id' => $joey->id,
                'tracking_id' => $request['tracking_id'],
                'route_id' => $request['route_id'],
                'sprint_id' => $data->sprint_id,
                'task_id' => $data->task_id,
                'status_id' => 125,
                'created_at' => $currentDate,
                'updated_at' => $currentDate
            ];


            JoeyStorePickup::insert($joeyStopPickupRecord);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response('Success', true, 'Item pickup from store successfully');

    }

    public function hubdelivernew(Request $request)
    {
        $currentDate = Carbon::now()->format('Y/m/d H:i:s');

        $request = $request->all();

        //getting route ids
        $tracking_ids = explode(',',$request['tracking_id']);
        $route_ids = explode(',',$request['route_id']);
        DB::beginTransaction();

        try {
            $joey = $this->userRepository->find(auth()->user()->id);

            if(empty($request['route_id'])){
                return RestAPI::response('Route Id is require', false);
            }

            if (empty($joey)) {
                return RestAPI::response('Joey record not found', false);
            }

            $record = JoeyStorePickup::where('joey_id', $joey->id)
                ->whereIn('route_id', $route_ids)
                ->whereIn('tracking_id', $tracking_ids)
                ->whereNull('deleted_at')
                ->distinct()
                ->get();

            $task_ids = [];
            $sprint_ids = [];
            $taskHistoryRecord = [];
            $sprintHistoryRecord = [];

            if ($record->count() <= 0) {
                return RestAPI::response('No record found against tracking id', false);
            }
            foreach ($record as $value) {
                $task_ids[] = $value->task_id;
                $sprint_ids[] = $value->sprint_id;

                $taskHistoryRecord[] = [
                    'sprint__tasks_id' => $value->task_id,
                    'sprint_id' => $value->sprint_id,
                    'status_id' => 124,
                    'active' => 3,


                ];

                if(!empty($request['image'])){
                    $path=  $this->upload($request['image']);
                    $extArray = explode('.',$path);
                    $extension = end($extArray);
                    if(!isset($path)){
                        return RestAPI::response('File cannot be uploaded due to server error!', false);
                    }
                    $confirmations = [
                        'ordinal' => 1,
                        'task_id' => $value->task_id,
                        'joey_id' => Auth::user()->id,
                        'name'    => 'hub_deliver',
                        'title'   => 'hub_deliver',
                        'confirmed' => 0,
                        'input_type' => $extension,
                        'attachment_path' => $path,
                    ];
                    SprintConfirmation::create($confirmations);
                }



                $sprintHistoryRecord[] = [
                    'sprint__sprints_id' => $value->sprint_id,
                    'vehicle_id' => 3,
                    'status_id' => 124,
                    'active' => 3,

                ];

                CtcEnteries::where('sprint_id','=',$value->sprint_id)->whereNull('deleted_at')->update(['task_status_id' => 124]);
                BoradlessDashboard::where('sprint_id','=',$value->sprint_id)->whereNull('deleted_at')->update(['task_status_id' => 124]);



            }

            //ctc and boradless status updates

            SprintTasks::whereIn('id', $task_ids)->update(['status_id' => 124]);

            Sprint::whereIn('id', $sprint_ids)->update(['status_id' => 124]);

            SprintTaskHistory::insert($taskHistoryRecord);
            SprintSprintHistory::insert($sprintHistoryRecord);

            $response = 'Items picked by this joey has delivered at hub successfully';

            $record = JoeyStorePickup::where('joey_id', $joey->id)
                ->whereIn('route_id', $route_ids)
                ->whereIn('tracking_id', $tracking_ids)
                ->whereNull('deleted_at')
                ->update(['deleted_at' => date('Y-m-d H:i:s')]);


            $checkRecord = JoeyStorePickup::where('joey_id', $joey->id)
                ->whereIn('route_id', $route_ids)
                ->whereNull('deleted_at')
                ->count();

            if($checkRecord==0){
                $joeyRoutes = JoeyRoutes::whereIn('id',$route_ids)->where('mile_type',1)->update(['route_completed'=>1]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response(new stdClass(), true, $response);

    }

    public function task_sprint_create($sprint_id,$route_id, $request){

        $taskPickup =SprintTasks::join('sprint__sprints' ,'sprint__sprints.id' , '=' , 'sprint__tasks.sprint_id' )
            ->where('sprint__tasks.sprint_id' , $sprint_id)
            ->whereNull('sprint__sprints.deleted_at')
            ->where('type', 'pickup')
            ->pluck('sprint__tasks.location_id');

        $lastOrdinal = JoeyRouteLocation::where('route_id' ,$route_id)->get()->last();

        foreach($taskPickup as $x){
            $data['location_id']=$x;
        }

        $task = SprintTasks::where('sprint_id' ,$sprint_id)->where('id', $request['task_id'])->where('type' , 'dropoff')->first();

        $sprintContact = SprintContact::where('id' ,$task->contact_id)->first();

        $contact_details['name']=$sprintContact->name;
        $contact_details['phone']=$sprintContact->phone;
        $contact_details['email']=$sprintContact->email;

        $contact_id= SprintContact::create($contact_details);

        $sprint_task_dropoff_data['sprint_id']= $sprint_id;
        $sprint_task_dropoff_data['type']='return';
        $sprint_task_dropoff_data['charge']=$task->charge;
        $sprint_task_dropoff_data['ordinal']=$lastOrdinal->ordinal+1;
        $sprint_task_dropoff_data['due_time']=$task->due_time;
        $sprint_task_dropoff_data['eta_time']=$task->due_time;
        $sprint_task_dropoff_data['etc_time']=$task->etc_time;
        $sprint_task_dropoff_data['location_id']=$data['location_id'];
        $sprint_task_dropoff_data['contact_id']=$contact_id->id;
        $sprint_task_dropoff_data['status_id']=125;
        $sprint_task_dropoff_data['active']=1;
        $sprint_task_dropoff_data['notify_by']=$task-> notify_by;
        $sprint_task_dropoff_data['payment_type']=$task->payment_type;
        $sprint_task_dropoff_data['payment_amount']=$task->payment_amount;
        $sprint_task_dropoff_data['description']=$task->copy;
        $sprint_task_dropoff_data['confirm_image']=$task->confirm_image;
        $sprint_task_dropoff_data['confirm_signature']=$task->confirm_signature;
        $sprint_task_dropoff_data['confirm_pin']=$task->confirm_pin;
        $sprint_task_dropoff_data['confirm_seal']=$task->confirm_seal;

        $sprint_task_dropoff_data = SprintTasks::create($sprint_task_dropoff_data);


        $sprint_task_dropoff_id = $sprint_task_dropoff_data->id;
        SprintTaskHistory::insert(['created_at' => date("Y-m-d H:i:s"), 'date' => date('Y-m-d H:i:s'), 'sprint__tasks_id' => $sprint_task_dropoff_id , 'sprint_id' => $sprint_id, 'status_id' => 18, 'active' => 0]);
        $merchant = MerchantsIds::where('task_id' , $task->id)->first();

        $merchantid_data['task_id']=$sprint_task_dropoff_id;
        $merchantid_data['merchant_order_num']=$merchant['merchant_order_num'];
        $merchantid_data['end_time']=$merchant['end_time'];
        $merchantid_data['start_time']=$merchant['start_time'];
        $merchantid_data['tracking_id']=$merchant['tracking_id'];
        $merchantid_data['address_line2']=$merchant['address_line2'];
        $merchantid=MerchantsIds::create($merchantid_data);


        $joey_route_data = [
            'route_id' => $route_id,
            'ordinal' => $lastOrdinal->ordinal+1,
            'task_id' => $sprint_task_dropoff_data->id
        ];

        JoeyRouteLocation::create($joey_route_data);

    }

    public function to_pickup(Request $request)
    {
        $routeId = $request->get('route_id');
        $routeIdInArray = explode(',', $routeId);
        $currentDate = Carbon::now()->format('Y/m/d H:i:s');

        $authUser = JWTAuth::setToken($request->header('ApiToken'))->toUser();

        $joey = $this->userRepository->find($authUser->id);

        $routes = JoeyRoutes::where('joey_id', $joey->id)->whereNull('deleted_at')->whereIn('mile_type', [5,3])->find($routeIdInArray);

//
        if(!$routes){
            return RestAPI::response('This route not assign to joey', false);
        }

        $sprintIdForBooking=[];
        DB::beginTransaction();
        try {
            $routeLocations = JoeyRouteLocation::whereIn('route_id', $routeIdInArray)->whereNull('deleted_at')->pluck('task_id')->toArray();

            $taskHistory = SprintTaskHistory::whereIn('sprint__tasks_id', $routeLocations)
                ->where('status_id', 101)
                ->groupBy('sprint__tasks_id')
                ->pluck('sprint__tasks_id')
                ->toArray();

            $taskIdNotMarkPickup = array_diff($routeLocations, $taskHistory);
            $sprind = SprintTasks::whereIn('id',$taskIdNotMarkPickup)->get();

            if(!empty($sprind)){
                foreach($sprind as $routeLocation){
//                    if(isset($routeLocation)) {
                    $sprintIds = SprintTasks::where('id', $routeLocation->id)->groupBy('sprint_id')->pluck('sprint_id');

                    $sprint = Sprint::whereIn('id', $sprintIds)->first();


                    Sprint::where('status_id', 61)->whereIn('id', $sprintIds)->update(['status_id' => 101]);
                    SprintTasks::where('status_id', 61)->where('id', $routeLocation->id)->update(['status_id' => 101]);

                    $sprintTaskHistory = [
                        'sprint__tasks_id' => $routeLocation->id,
                        'sprint_id' => $sprint->id,
                        'status_id' => 101,
                        'date' => $currentDate,
                        'created_at' => $currentDate,
                        'active' => 0,
                    ];

                    $this->updateBoradlessDashboard(101, $sprint->id);

                    $taskHistory = SprintTaskHistory::insert($sprintTaskHistory);
                    $sprintIdForBooking[] = $sprint->id;
//                    }
                }

                $bookings = HaillifyBooking::whereIn('sprint_id', $sprintIdForBooking)->whereNull('deleted_at')->get();

                foreach($bookings as $booking){

                    $updateStatusUrl = 'https://api.drivehailify.com/carrier/'.$booking->delivery_id.'/status';

                    $updateStatusArray = [
                        'status' => 'to_pickup',
                        'driverId' => $joey->id,
                        'latitude' => ($request->get('latitude') != null) ? $request->get('latitude') : 0,
                        'longitude' => ($request->get('longitude') != null) ? $request->get('longitude') : 0,
                        'hailifyId' => $booking->haillify_id,
                    ];

                    $result = json_encode($updateStatusArray);
                    $response = $this->client->bookingRequestWithParam($result, $updateStatusUrl);

                    $data = [
                        'url' => $updateStatusUrl,
                        'request' => $result,
                        'code'=>$response['http_code']
                    ];
                    \Log::channel('hailify')->info($data);
                }
            }else{
                return RestAPI::response('This route ids sprint is already pickup marked', false);
            }

            DB::commit();
        }catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response('some thing went wrong', false, 'error_exception');
        }

        return RestAPI::response(new stdClass(), true, 'To pickup mark successfully');

    }

}


