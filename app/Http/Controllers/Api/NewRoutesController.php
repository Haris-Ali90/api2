<?php

namespace App\Http\Controllers\Api;

use App\Classes\RestAPI;
use App\Models\Country;
use App\Models\HaillifyBooking;
use App\Models\MerchantsIds;
use App\Models\OptimizeItinerary;
use App\Models\SprintTasks;
use App\Models\LocationEnc;
use App\Models\Vendors;
use Illuminate\Http\Request;
use App\Models\JoeyRoutes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Vendor;
use App\Models\Hub;
use App\Models\MiJobDetail;
use App\Models\SprintTaskHistory;
use App\Models\MicroHubOrder;
use App\Models\Dashboard;
use App\Models\Sprint;
use App\Models\Location;
use App\Models\MiJobRoute;
use App\Models\MidMilePickDrop;
use App\Models\CurrentHubOrder;
use App\Models\JoeycoUsers;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\JoeyRepositoryInterface;
use App\Repositories\Interfaces\JoeyRouteRepositoryInterface;
use App\Http\Resources\NewJoeyRouteResource;
use App\Http\Resources\HubBundleResource;
use App\Http\Resources\RouteStatusListResource;
use App\Http\Resources\BundleHubOrderResource;
use App\Http\Resources\VendorOrderResource;
use App\Events\Api\NotificationEvent;
use App\Http\Requests\Api\VendorOrderListRequest;


class NewRoutesController extends ApiBaseController
{
    private $userRepository;
    private $joeyrouteRepository;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(UserRepositoryInterface $userRepository, JoeyRouteRepositoryInterface $joeyrouteRepository)
    {

        $this->userRepository = $userRepository;
        $this->joeyrouteRepository = $joeyrouteRepository;

    }

    public function index(Request $request)
    {
        $data = $request->all();
        $date = date('Y-m-d');
        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);

            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }

            $routes = JoeyRoutes::join('joey_route_locations as jrl','jrl.route_id' ,'=', 'joey_routes.id')
                ->leftJoin('sprint__tasks as task','jrl.task_id','=', 'task.id')
                ->leftJoin('sprint__sprints as sprint', 'task.sprint_id','=','sprint.id')
                ->leftJoin('merchantids as mrids', 'task.id','=','mrids.task_id')
                ->leftJoin('sprint__contacts as spcon', 'spcon.id', '=', 'task.contact_id')
                ->leftJoin('locations as loc', 'loc.id', '=', 'task.location_id')
                ->where('joey_routes.joey_id', $joey->id)
                ->where('is_reattempt','=',0)
                ->where('joey_routes.route_completed',0)
                ->whereNotIn('sprint.status_id',[111,17,113,114,116,117,118,132,138,139,144,141,145,36])
                ->whereNotIn('task.status_id',[111,17,113,114,116,117,118,132,138,139,144,141,145,36])
                ->whereNull('jrl.deleted_at')
                ->whereNull('task.deleted_at')
                ->whereNull('sprint.deleted_at')
                ->whereNull('jrl.is_unattempted')
                ->whereNull('joey_routes.deleted_at')
                ->orderBy('joey_routes.mile_type', 'ASC')
                ->orderBy('joey_routes.id', 'ASC')
                ->get(['joey_routes.id as route_id', 'joey_routes.date as route_date', 'joey_routes.total_travel_time',
                    'joey_routes.total_distance', 'joey_routes.hub', 'joey_routes.mile_type','jrl.ordinal','jrl.task_id', 'jrl.arrival_time', 'jrl.finish_time',
                    'mrids.start_time', 'mrids.end_time', 'spcon.name', 'spcon.email', 'spcon.phone', 'loc.address as loc_address', 'loc.suite', 'mrids.address_line2', 'mrids.tracking_id',
                    'mrids.merchant_order_num', 'task.sprint_id', 'task.status_id as task_status_id','loc.id as location_id', 'loc.latitude as loc_latitude',
                    'loc.longitude as loc_longitude', 'task.type', 'task.description', 'mrids.additional_info']);


            if (empty($routes)) {
                return RestAPI::response('route  record not found', false);
            }
            $response = [];
            $tasks_ids = $routes->map(
                function ($routes) {
                    return $routes->task_id;
                }
            );

            $taskIdsForCurrentRoute=[];
            $toPickup=0;
            foreach($routes as $route){
                if(isset($route->task_status_id)) {
                    if (!in_array($route->task_status_id, [111, 17, 113, 114, 116, 117, 118, 132, 138, 139, 144, 141])) {
                        $taskIdsForCurrentRoute[] = $route->task_id;
                    }
                }
            }

            $toPickupCount = SprintTaskHistory::whereIn('status_id', [101,18])->whereIn('sprint__tasks_id', $taskIdsForCurrentRoute)->groupBy('sprint__tasks_id')->pluck('sprint__tasks_id')->toArray();

            if (count($toPickupCount) == count($taskIdsForCurrentRoute)) {
                $toPickup = 1;
            }

            $response['orders_picked'] = count(SprintTaskHistory::where('status_id', 121)->whereIn('sprint__tasks_id', $tasks_ids)->groupBy('sprint__tasks_id')->get(['id']));
            $response['orders_sorted'] = count(SprintTaskHistory::where('status_id', 133)->whereIn('sprint__tasks_id', $tasks_ids)->groupBy('sprint__tasks_id')->get(['id']));
            //$response['to_pickup'] = $toPickup;
			$response['to_pickup'] = 1;
            $response['is_optimize'] = 0;
            $response['routes'] = [];
            $optimize = [];
            $optimizeItinerary = OptimizeItinerary::with('itinerary')->where('joey_id',auth()->user()->id)->first();

            $hubId=0;
            $midMileOrderCount=[];
            $storeOrderCount = 0;
            $bundleOrderCount = 0;
            $count = 0;
            $vendor_ids=[];
            $lastMileRoute2=[];

            foreach($routes as $route){
                if($route->mile_type == 1){
                    $vendor = Vendor::find($route->task_id);
                    $vendorOrderCount = $vendor->getVendorOrdersCount(date("Y-m-d", strtotime($route->route_date)), $route->task_id);

                    $address = $vendor->business_address;
                    $latitude = $vendor->latitude/1000000;
                    $longitude = $vendor->longitude/1000000;

                    if($vendor->business_address == null){
                        $location = Location::find($vendor->location_id);
                        if($location){
                            $address = $location->address;
                            $latitude = $location->latitude/1000000;
                            $longitude = $location->longitude/1000000;
                        }else{
                            $locationEnc = LocationEnc::find($vendor->location_id);
                            $address = $locationEnc->setDecryptAddressAttribute($locationEnc->address, $vendor->location_id);
                        }
                    }

//                    $locations = Location::find($vendor->location_id);
//
//                    $lat[0] = substr($locations->latitude, 0, 2);
//                    $lat[1] = substr($locations->latitude, 2);
//                    $latitude = $lat[0] . "." . $lat[1];
//
//                    $long[0] = substr($locations->longitude, 0, 3);
//                    $long[1] = substr($locations->longitude, 3);
//                    $longitude = $long[0] . "." . $long[1];

                    $response['routes'][] =[
                        'route_id' => $route->route_id,
                        'order_count' => $vendorOrderCount,
                        'route_type' => $route->mile_type,
                        'type' => 'pickup',
                        'vendor_id' => (string)$vendor->id,
                        'contact' => [
                            'name' => $vendor->name,
                            'email' => $vendor->email,
                            'phone' => $vendor->phone
                        ],
                        'location' => [
                            'address' => $address ?? '',
                            'latitude' => $latitude ?? '',
                            'longitude' => $longitude ?? '',
                        ],
                    ];

                    $vendorIds[] = $route->task_id;
                    $vendor_ids = array_unique($vendorIds);

                    $hub = Hub::find($route->hub);
                    $dashboardUserEmails = Dashboard::where('hub_id', $route->hub)->pluck('email');
                    $hubDetail = JoeycoUsers::whereIn('email_address', $dashboardUserEmails)->first();


//                    if ($response['routes'][$count]['route_id'] == $route->route_id) {
                        if($response['routes'][$count]['type'] != 'dropoff'){
                            $dropoffCount = Vendor::getVendorDropoffOrdersCount($route->route_id, 125);
                            $response['routes'][] = [
                                'route_id' => $route->route_id,
                                'dropoff_count' => $dropoffCount,
                                'route_type' => 1,
                                'type' => 'dropoff',
                                'hub_id' => (string)$hub->id,
                                'vendor_id' => '',
                                'contact' => [
                                    'name' => $hubDetail->full_name ?? 'N/A',
                                    'hub_name' => $hubDetail->full_name ?? 'N/A',
                                    'email' => $hubDetail->email_address ?? 'N/A',
                                    'phone' => $hubDetail->phone_no ?? 'N/A',
                                ],
                                'location' => [
                                    'address' => $hub->address ?? '',
                                    'latitude' => (string)$hub->hub_latitude ?? '',
                                    'longitude' => (string)$hub->hub_longitude ?? '',
                                ],
                            ];
                        }

//                    }
                    if ($response['routes'][$count]['type'] == 'dropoff') {
                        $response['routes'][$count]['vendor_id'] = implode(', ', $vendor_ids);

                        $response['routes'][$count]['dropoff_count'] = $dropoffCount;
                        sort($response['routes']);
                    }
                    $count++;
                }
                if($route->mile_type == 2){
                    $miJobRoute = MiJobRoute::where('route_id', $route->route_id)->first();
                    if(isset($miJobRoute)){
                        $miJobDetail = MiJobDetail::where('locationid',$route->task_id)->where('mi_job_id', $miJobRoute->mi_job_id)->first();
                        if(isset($miJobDetail->location_type)){
                            if($miJobDetail->location_type == 'hub'){
                                if($miJobDetail->type == 'pickup'){
                                    $hub = Hub::whereNull('deleted_at')->find($miJobDetail->locationid);
                                    $user = Dashboard::where('hub_id', $hub->id)->pluck('id');

                                    $dashboardUserEmails = Dashboard::where('hub_id', $hub->id)->pluck('email');
                                    $hubDetail = JoeycoUsers::whereIn('email_address', $dashboardUserEmails)->first();

                                    $microHubBundle = MicroHubOrder::whereHas('sprint', function($query) {
                                        $query->where('status_id', 148)->whereNotIn('status_id', [36]);
                                    })->where('is_my_hub', 0)
//                                        ->whereDate('created_at', 'LIKE', $date.'%')
                                        ->whereNull('deleted_at')
                                        ->whereIn('scanned_by',$user)
                                        ->groupBy('hub_id')
                                        ->get()
                                        ->toArray();

                                    $sprintIds = CurrentHubOrder::where('hub_id', $hub->id)->where('is_actual_hub', 0)->pluck('sprint_id');
                                    $hubBundleOther = MicroHubOrder::whereHas('sprint', function($query) {
                                        $query->where('status_id', 150)->whereNotIn('status_id', [36]);
                                    })->whereIn('sprint_id',$sprintIds)
//                                        ->whereDate('created_at', 'LIKE', $date.'%')
                                        ->whereNull('deleted_at')
                                        ->groupBy('bundle_id')
                                        ->get()
                                        ->toArray();

                                    $count = array_merge($microHubBundle, $hubBundleOther);
                                        $response['routes'][] =[
                                            'route_id' => $route->route_id,
                                            'reference_no' => 'MR-'.$miJobDetail->mi_job_id,
                                            'bundle_count' => count($count),
                                            'route_type' => $route->mile_type,
                                            'type' => $miJobDetail->type,
                                            'hub_id' => (string)$hub->id,
                                            'contact' => [
                                                'hub_name' => $hubDetail->full_name ?? 'N/A',
                                                'email' => $hubDetail->email_address ?? 'N/A',
                                                'phone' => $hubDetail->phone_no ?? 'N/A',
                                            ],
                                            'location' => [
                                                'address' => $hub->address ?? '',
                                                'latitude' => (string)$hub->hub_latitude ?? '',
                                                'longitude' => (string)$hub->hub_longitude ?? '',
                                            ],
                                        ];

                                }
                                if($miJobDetail->type == 'dropoff'){
                                    $hub = Hub::whereNull('deleted_at')->find($miJobDetail->locationid);

                                    $dashboardUserEmails = Dashboard::where('hub_id', $hub->id)->pluck('email');
                                    $hubDetail = JoeycoUsers::whereIn('email_address', $dashboardUserEmails)->first();

                                    $dropoffBundleCount = MidMilePickDrop::where('route_id', $route->route_id)
                                        ->where('joey_id', $joey->id)->where('status_id', 149)->whereNull('deleted_at')->count();
                                    $response['routes'][] =[
                                        'route_id' => $route->route_id,
                                        'reference_no' => 'MR-'.$miJobDetail->mi_job_id,
                                        'bundle_count' => $dropoffBundleCount,
                                        'route_type' => $route->mile_type,
                                        'type' => $miJobDetail->type,
                                        'hub_id' => (string)$hub->id,
                                        'contact' => [
                                            'hub_name' => $hubDetail->full_name ?? 'N/A',
                                            'email' => $hubDetail->email_address ?? 'N/A',
                                            'phone' => $hubDetail->phone_no ?? 'N/A',
                                        ],
                                        'location' => [
                                            'address' => $hub->address ?? '',
                                            'latitude' => (string)$hub->hub_latitude ?? '',
                                            'longitude' => (string)$hub->hub_longitude ?? '',
                                        ],
                                    ];
                                }
                            }
                        }
                    }
                }
                if($route->mile_type == 4){
                    $miJobRoute = MiJobRoute::where('route_id', $route->route_id)->first();
                    if(isset($miJobRoute)){
                        $miJobDetail = MiJobDetail::where('locationid',$route->task_id)->where('mi_job_id', $miJobRoute->mi_job_id)->first();
                        if(isset($miJobDetail->location_type)){
                            if($miJobDetail->type == 'pickup'){
                                if($miJobDetail->location_type == 'store'){
                                    $vendor = Vendor::whereNull('deleted_at')->find($miJobDetail->locationid);

                                    $address = $vendor->business_address;
                                    $vendorLatitude = $vendor->latitude;
                                    $vendorLongitude = $vendor->longitude;

                                    $lat[0] = substr($vendorLatitude, 0, 2);
                                    $lat[1] = substr($vendorLatitude, 2);
                                    $latitude = $lat[0] . "." . $lat[1];

                                    $long[0] = substr($vendorLongitude, 0, 3);
                                    $long[1] = substr($vendorLongitude, 3);
                                    $longitude = $long[0] . "." . $long[1];

                                    $midMileOrderCount[] = $vendor->id;

                                    if($vendor->business_address == null){
                                        $location = Location::find($vendor->location_id);
                                        $address = $location->address;

                                        $lat[0] = substr($location->latitude, 0, 2);
                                        $lat[1] = substr($location->latitude, 2);
                                        $latitude = $lat[0] . "." . $lat[1];

                                        $long[0] = substr($location->longitude, 0, 3);
                                        $long[1] = substr($location->longitude, 3);
                                        $longitude = $long[0] . "." . $long[1];
                                    }





                                    $storeOrderCount += $vendor->getVendorOrdersCount($route->route_date, $route->task_id);

                                    $response['routes'][] =[
                                        'route_id' => $route->route_id,
                                        'order_count' => $vendor->getVendorOrdersCount(date("Y-m-d", strtotime($route->route_date)), $route->task_id),
                                        'reference_no' => 'MR-'.$miJobDetail->mi_job_id,
                                        'route_type' => $route->mile_type,
                                        'type' => $miJobDetail->type,
                                        'vendor_id' => (string)$vendor->id,
                                        'contact' => [
                                            'name' => $vendor->name,
                                            'email' => $vendor->email,
                                            'phone' => $vendor->phone
                                        ],
                                        'location' => [
                                            'address' => $address ?? '',
                                            'latitude' => $latitude ?? '',
                                            'longitude' => $longitude ?? '',
                                        ],
                                    ];

                                    $vendorIds[] = $route->task_id;
                                    $vendor_ids = array_unique($vendorIds);

                                }
                                if($miJobDetail->location_type == 'hub'){


                                    $hub = Hub::whereNull('deleted_at')->find($miJobDetail->locationid);
                                    $user = Dashboard::where('hub_id', $hub->id)->pluck('id');

                                    $dashboardUserEmails = Dashboard::where('hub_id', $hub->id)->pluck('email');
                                    $hubDetail = JoeycoUsers::whereIn('email_address', $dashboardUserEmails)->first();

                                    $microHubBundle = MicroHubOrder::whereHas('sprint', function($query) {
                                        $query->where('status_id', 148)->whereNotIn('status_id', [36]);
                                    })->where('is_my_hub', 0)
                                        ->whereNull('deleted_at')
                                        ->whereIn('scanned_by',$user)
                                        ->groupBy('hub_id')
                                        ->get()
                                        ->toArray();

                                    $sprintIds = CurrentHubOrder::where('hub_id', $hub->id)->where('is_actual_hub', 0)->pluck('sprint_id');
                                    $hubBundleOther = MicroHubOrder::whereHas('sprint', function($query) {
                                        $query->where('status_id', 150)->whereNotIn('status_id', [36]);
                                    })->whereIn('sprint_id',$sprintIds)
                                        ->whereNull('deleted_at')
                                        ->groupBy('bundle_id')
                                        ->get()
                                        ->toArray();

                                    $count = array_merge($microHubBundle, $hubBundleOther);

                                        $response['routes'][] =[
                                            'route_id' => $route->route_id,
                                            'bundle_count' => count($count),
                                            'reference_no' => 'MR-'.$miJobDetail->mi_job_id,
                                            'route_type' => $route->mile_type,
                                            'type' => $miJobDetail->type,
                                            'hub_id' => (string)$hub->id,
                                            'contact' => [
                                                'hub_name' => $hubDetail->full_name ?? 'N/A',
                                                'email' => $hubDetail->email_address ?? 'N/A',
                                                'phone' => $hubDetail->phone_no ?? 'N/A',
                                            ],
                                            'location' => [
                                                'address' => $hub->address ?? '',
                                                'latitude' => (string)$hub->hub_latitude ?? '',
                                                'longitude' => (string)$hub->hub_longitude ?? '',
                                            ],
                                        ];

                                }
                            }
                            if($miJobDetail->type == 'dropoff'){

                                $hub = Hub::whereNull('deleted_at')->find($miJobDetail->locationid);
                                $dashboardUserEmails = Dashboard::where('hub_id', $hub->id)->pluck('email');
                                $hubDetail = JoeycoUsers::whereIn('email_address', $dashboardUserEmails)->first();

                                $dropoffBundleCount = MidMilePickDrop::where('route_id', $route->route_id)->where('joey_id', $joey->id)->where('status_id', 149)->whereNull('deleted_at')->count();
                                $dropoffOrderCount = Vendor::getVendorDropoffOrdersCount($route->route_id, 125);

                                $response['routes'][] =[
                                    'route_id' => $route->route_id,
                                    'bundle_count' => $dropoffBundleCount,
                                    'order_count' => $dropoffOrderCount,
                                    'reference_no' => 'MR-'.$miJobDetail->mi_job_id,
                                    'route_type' => $route->mile_type,
                                    'type' => $miJobDetail->type,
                                    'hub_id' => (string)$hub->id,
                                    'contact' => [
                                        'hub_name' => $hubDetail->full_name ?? 'N/A',
                                        'email' => $hubDetail->email_address ?? 'N/A',
                                        'phone' => $hubDetail->phone_no?? 'N/A'
                                    ],
                                    'location' => [
                                        'address' => $hub->address ?? '',
                                        'latitude' => (string)$hub->hub_latitude ?? '',
                                        'longitude' => (string)$hub->hub_longitude ?? '',
                                    ],
                                ];
                            }
                        }
                    }
                }
                if($route->mile_type == 3){
                    if(isset($route->task_status_id)){
                        if (!in_array($route->task_status_id, [105, 111, 17, 113, 114, 116, 117, 118, 132, 138, 139, 144, 141])) {
                            $pickedUp = '';
                            if (!empty($route->sprint_id)) {
                                $pickedUp = SprintTaskHistory::where('sprint_id', '=', $route->sprint_id)
                                    ->groupBy('sprint_id')
                                    ->where('status_id', '=', '121')->first();
                            }

                            $returned = 0;
                            if (isset($route->task_status_id)) {
                                if (in_array($route->task_status_id, [101, 110, 112, 104, 105, 106, 107, 108, 109, 111, 131, 135, 140])) {
                                    $returned = 1;
                                }
                            }

                            $latitude = '';
                            $longitude = '';


                            if (isset($route->location_id)) {
                                $lat[0] = substr($route->loc_latitude, 0, 2);
                                $lat[1] = substr($route->loc_latitude, 2);
                                $latitude = $lat[0] . "." . $lat[1];

                                $long[0] = substr($route->loc_longitude, 0, 3);
                                $long[1] = substr($route->loc_longitude, 3);
                                $longitude = $long[0] . "." . $long[1];

                            }

                            $lastMileRoute = [
                                'route_id' => $route->route_id,
                                'num' => 'R-' . $route->route_id . '-' . $route->ordinal,
                                'start_time' => $route->start_time ?? '',
                                'end_time' => $route->end_time ?? '',
                                'arrival_time' => $route->arrival_time ?? '',
                                'finish_time' => $route->finish_time ?? '',
                                'route_type' => $route->mile_type,
                                'description' => $route->description,
                                'additional_info' => $route->additional_info,
                                'contact' => [
                                    'name' => $route->name ?? '',
                                    'phone' => $route->phone ?? '',
                                    'email' => $route->email ?? ''
                                ]
                                ,
                                'location' => [
                                    'address' => $route->loc_address ?? '',
                                    'latitude' => $latitude ?? '',
                                    'longitude' => $longitude ?? '',
                                    'address_line2' => $route->address_line2 ?? ''
                                ],
                                'task_id' => $route->task_id ?? '',
                                'tracking_id' => $route->tracking_id ?? '',
                                'merchant_order_num' => $route->merchant_order_num ?? '',
                                'ordinal' => $route->ordinal ?? '',
                                'has_picked' => isset($pickedUp) ? 1 : 0,
                                'returned' => $returned ?? '',
                            ];

                            if(isset($optimizeItinerary->is_optimize)) {
                                if ($optimizeItinerary->is_optimize == 0) {
                                    $response['routes'][] = $lastMileRoute;
                                }
                                $lastMileRoute2[]=$lastMileRoute;
                            }else{
                                $response['routes'][] = $lastMileRoute;
                            }
                        }
                    }



                }
                if($route->mile_type == 5){
                    if(isset($route->task_status_id)){
                        if (!in_array($route->task_status_id, [111, 17, 113, 114, 116, 117, 118, 132, 138, 139, 144, 141, 145])) {
                            $pickedUp = '';
                            if (!empty($route->sprint_id)) {
                                $pickedUp = SprintTaskHistory::where('sprint_id', '=', $route->sprint_id)
                                    ->groupBy('sprint_id')
                                    ->where('status_id', '=', '125')->first();
                            }

                            $returned = 0;
                            if (isset($route->task_status_id)) {
                                if (in_array($route->task_status_id, [110, 112, 104, 105, 106, 107, 108, 109, 111, 131, 135])) {
                                    $returned = 1;
                                }
                            }

                            $taskId = SprintTasks::where('sprint_id', $route->sprint_id)->where('type', '=', 'dropoff')->pluck('id');
                            $merchant = MerchantsIds::whereIn('task_id', $taskId)->pluck('tracking_id');
                            $booking =  HaillifyBooking::where('sprint_id', $route->sprint_id)->first();
                            $latitude = '';
                            $longitude = '';

                            if (isset($route->location_id)) {
                                $lat[0] = substr($route->loc_latitude, 0, 2);
                                $lat[1] = substr($route->loc_latitude, 2);
                                $latitude = $lat[0] . "." . $lat[1];

                                $long[0] = substr($route->loc_longitude, 0, 3);
                                $long[1] = substr($route->loc_longitude, 3);
                                $longitude = $long[0] . "." . $long[1];

                            }
                            $response['routes'][]=[
                                'route_id' => $route->route_id,
                                'num' => 'R-' . $route->route_id . '-' . $route->ordinal,
                                'start_time' => $route->start_time ?? '',
                                'end_time' => $route->end_time ?? '',
                                'arrival_time' => $route->arrival_time ?? '',
                                'finish_time' => $route->finish_time ?? '',
                                'pickup_time' => ($route->type == 'pickup') ? $booking->pickup_time : date('Y-m-d H:i:s', strtotime($booking->pickup_time) + 60*60),
                                'route_num' => ($booking->route_num) ?? 'N/A',
                                'route_type' => $route->mile_type,
                                'type' => $route->type,
                                'description' => $route->description,
                                'contact' => [
                                    'name' => $route->name ?? '',
                                    'phone' => $route->phone ?? '',
                                    'email' => $route->email ?? ''
                                ],
                                'location' => [
                                    'address' => $route->suite.' '.$route->loc_address ?? '',
                                    'latitude' => $latitude ?? '',
                                    'longitude' => $longitude ?? '',
                                    'address_line2' => $route->address_line2 ?? ''
                                ],
                                'task_id' => $route->task_id ?? '',
                                'tracking_id' => $route->tracking_id ?? '',
                                'merchant_order_num' => $route->merchant_order_num ?? '',
                                'ordinal' => $route->ordinal ?? '',
                                'has_picked' => isset($pickedUp) ? 1 : 0,
                                'returned' => $returned ?? '',
                            ];
                            if($route->type == 'pickup'){
                                $response['routes'][$count]['tracking_ids'] = $merchant;
                            }
                            $count++;
//                            $response['routes'] = collect($response['routes'])->sortBy('route_type')->values()->toArray();
//                            $response['routes'] = collect($response['routes'])->sortBy('ordinal')->values()->toArray();
                        }
                    }
                }
            }

            if(isset($optimizeItinerary->is_optimize)){

                $response['is_optimize'] = $optimizeItinerary->is_optimize;
                if($optimizeItinerary->is_optimize == 1){
                    $optimize =[];
                    foreach($optimizeItinerary->itinerary as $key => $itinerary){
                        array_push($optimize, $itinerary->task_id);
                    }
                    foreach ($optimize as $op){
                        foreach($lastMileRoute2 as $key => $ind){
                            if($ind['task_id'] == $op){
                                $response['routes'][] = $lastMileRoute2[$key];
                            }
                        }
                    }
                }
            }


            $response['Status'] = new RouteStatusListResource($request);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, 'joey route');
    }

    public function bundleList(Request $request)
    {

        $data = $request->all();
        $date = date('Y-m-d');
        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);
            $user = Dashboard::where('hub_id', $data['hub_id'])->pluck('id');
            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }

            if($data['type'] == 'pickup' || $data['type'] == 'pick'){

                $microHubBundlepick = MicroHubOrder::whereHas('sprint', function($query) {
                    $query->where('status_id', 148)->whereNotIn('status_id', [36]);
                })->where('is_my_hub', 0)
//                    ->whereDate('created_at', 'LIKE', $date.'%')
                    ->whereNull('deleted_at')
                    ->whereIn('scanned_by',$user)
                    ->groupBy('hub_id')
                    ->get()
                    ->toArray();

                $sprintIds = CurrentHubOrder::where('hub_id', $data['hub_id'])->where('is_actual_hub', 0)->pluck('sprint_id');
                $hubBundleOther = MicroHubOrder::whereHas('sprint', function($query) {
                    $query->where('status_id', 150)->whereNotIn('status_id', [36]);
                })->whereIn('sprint_id',$sprintIds)
//                    ->whereDate('created_at', 'LIKE', $date.'%')
                    ->whereNull('deleted_at')
                    ->groupBy('bundle_id')
                    ->get()
                    ->toArray();

                $microHubBundle = array_merge($microHubBundlepick, $hubBundleOther);
            }

            if($data['type'] == 'dropoff' || $data['type'] == 'drop'){
                $microHubBundle = MidMilePickDrop::where('joey_id', $joey->id)->where('route_id', $data['route_id'])->where('status_id', 149)->whereNull('deleted_at')->get();
            }

            if(empty($microHubBundle)){
                return RestAPI::response('No Bundle Available', false);
            }

            $data=[];
            $referenceNo = 0;

            foreach($microHubBundle as $hubBundle){
                if(isset($hubBundle['pickup_hub_id'])){
                    $hub = Hub::find($hubBundle['pickup_hub_id']);
                    $reference = MiJobDetail::where('locationid', $hubBundle['pickup_hub_id'])->first();
                    $hubId = $hubBundle['pickup_hub_id'];
                }
                if(isset($hubBundle['hub_id'])){
                    $hub = Hub::find($hubBundle['hub_id']);
                    $reference = MiJobDetail::where('locationid', $hubBundle['hub_id'])->first();
                    $hubId = $hubBundle['hub_id'];
                }
                if(isset($reference)){
                    $referenceNo = $reference->mi_job_id;
                }
                $microHubOrderCount = MicroHubOrder::where('is_my_hub', 0)->where('bundle_id', $hubBundle['bundle_id'])->count();
                $data[] = [
                    'id' => $hubBundle['bundle_id'],
                    'bundle_id' => 'MMB-'.$hubId,
                    'reference_no' => 'MR-'.$referenceNo,
                    'hub_name' => $hub->title,
                    'pickup_hub_id' => $hubBundle['pickup_hub_id'] ?? $hubBundle['hub_id'],
                    'hub_address' => $hub->address,
                    'hub_latitude' => $hub->hub_latitude,
                    'hub_longitude' => $hub->hub_longitude,
                    'no_of_order' => $microHubOrderCount,
                ];

            }
            DB::commit();
        }catch(\Exception $exception){
            DB::rollback();
            return RestAPI::response($exception->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($data, true, 'Bundle List');

    }

    public function bundleHubOrder(Request $request)
    {
        $data = $request->all();
        $date = date('Y-m-d');
        DB::beginTransaction();
        try {

            $microHubBundle = MicroHubOrder::where('is_my_hub', 0)->where('bundle_id',$data['bundle_id'])->whereNull('deleted_at')->get();

            if(empty($microHubBundle)){
                return RestAPI::response('No Orders Available', false);
            }

            $response = BundleHubOrderResource::collection($microHubBundle);

            DB::commit();
        }catch(\Exception $exception){
            DB::rollback();
            return RestAPI::response($exception->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'hub order list');
    }

    public function vendorOrderList(VendorOrderListRequest $request)
    {


        $data = $request->all();

        DB::beginTransaction();
        try {

            $sprint = Sprint::with('sprintTask', 'sprintTask.merchantIds')
                ->whereNull('deleted_at')
                ->whereIn('status_id',[24,61,111])
                ->whereNotIn('status_id',[36])
//                ->whereDate('created_at', 'LIKE', date('Y-m-d').'%')
                ->where('creator_id',$data['vendor_id'])
                ->get();

            if(empty($sprint)){
                return RestAPI::response('No Orders Available', false);
            }

            $response = VendorOrderResource::collection($sprint, $data['vendor_id']);

            DB::commit();
        }catch(\Exception $exception){
            DB::rollback();
            return RestAPI::response($exception->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'vendor order list');
    }


    public function testIndex(Request $request)
    {
        $data = $request->all();
        $date = date('Y-m-d');
        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);

            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }

            $routes = JoeyRoutes::join('joey_route_locations as jrl','jrl.route_id' ,'=', 'joey_routes.id')
                ->leftJoin('sprint__tasks as task','jrl.task_id','=', 'task.id')
                ->leftJoin('sprint__sprints as sprint', 'task.sprint_id','=','sprint.id')
                ->leftJoin('merchantids as mrids', 'task.id','=','mrids.task_id')
                ->leftJoin('sprint__contacts as spcon', 'spcon.id', '=', 'task.contact_id')
                ->leftJoin('locations as loc', 'loc.id', '=', 'task.location_id')
                ->where('joey_routes.joey_id', $joey->id)
                ->where('is_reattempt','=',0)
                ->where('joey_routes.route_completed',0)
                ->whereNotIn('sprint.status_id',[111,17,113,114,116,117,118,132,138,139,144,141,145,36])
                ->whereNotIn('task.status_id',[111,17,113,114,116,117,118,132,138,139,144,141,145,36])
                ->whereNull('jrl.deleted_at')
                ->whereNull('task.deleted_at')
                ->whereNull('sprint.deleted_at')
                ->whereNull('jrl.is_unattempted')
                ->whereNull('joey_routes.deleted_at')
                ->orderBy('joey_routes.mile_type', 'ASC')
                ->orderBy('joey_routes.id', 'ASC')
                ->get(['joey_routes.id as route_id', 'joey_routes.date as route_date', 'joey_routes.total_travel_time',
                    'joey_routes.total_distance', 'joey_routes.hub', 'joey_routes.mile_type','jrl.ordinal','jrl.task_id', 'jrl.arrival_time', 'jrl.finish_time',
                    'mrids.start_time', 'mrids.end_time', 'spcon.name', 'spcon.email', 'spcon.phone', 'loc.address as loc_address', 'loc.suite', 'mrids.address_line2', 'mrids.tracking_id',
                    'mrids.merchant_order_num', 'task.sprint_id', 'task.status_id as task_status_id','loc.id as location_id', 'loc.latitude as loc_latitude',
                    'loc.longitude as loc_longitude', 'task.type', 'task.description', 'mrids.additional_info']);


            if (empty($routes)) {
                return RestAPI::response('route  record not found', false);
            }
            $response = [];
            $tasks_ids = $routes->map(
                function ($routes) {
                    return $routes->task_id;
                }
            );

            $taskIdsForCurrentRoute=[];
            $toPickup=0;
            foreach($routes as $route){
                if(isset($route->task_status_id)) {
                    if (!in_array($route->task_status_id, [111, 17, 113, 114, 116, 117, 118, 132, 138, 139, 144, 141])) {
                        $taskIdsForCurrentRoute[] = $route->task_id;
                    }
                }
            }

            $toPickupCount = SprintTaskHistory::whereIn('status_id', [101,18])->whereIn('sprint__tasks_id', $taskIdsForCurrentRoute)->groupBy('sprint__tasks_id')->pluck('sprint__tasks_id')->toArray();

            if (count($toPickupCount) == count($taskIdsForCurrentRoute)) {
                $toPickup = 1;
            }

            $response['orders_picked'] = count(SprintTaskHistory::where('status_id', 121)->whereIn('sprint__tasks_id', $tasks_ids)->groupBy('sprint__tasks_id')->get(['id']));
            $response['orders_sorted'] = count(SprintTaskHistory::where('status_id', 133)->whereIn('sprint__tasks_id', $tasks_ids)->groupBy('sprint__tasks_id')->get(['id']));
            $response['to_pickup'] = $toPickup;
            $response['is_optimize'] = 0;
            $response['routes'] = [];
            $optimize = [];
            $optimizeItinerary = OptimizeItinerary::with('itinerary')->where('joey_id',auth()->user()->id)->first();

            $hubId=0;
            $midMileOrderCount=[];
            $storeOrderCount = 0;
            $bundleOrderCount = 0;
            $count = 0;
            $vendor_ids=[];
            $lastMileRoute2=[];

            foreach($routes as $routeKey => $route){
                if($route->mile_type == 1){
                    $vendor = Vendor::find($route->task_id);
                    $vendorOrderCount = $vendor->getVendorOrdersCount(date("Y-m-d", strtotime($route->route_date)), $route->task_id);

                    $address = $vendor->business_address;
                    $latitude = $vendor->latitude/1000000;
                    $longitude = $vendor->longitude/1000000;

                    if($vendor->business_address == null){
                        $location = Location::find($vendor->location_id);
                        if($location){
                            $address = $location->address;
                            $latitude = $location->latitude/1000000;
                            $longitude = $location->longitude/1000000;
                        }else{
                            $locationEnc = LocationEnc::find($vendor->location_id);
                            $address = $locationEnc->setDecryptAddressAttribute($locationEnc->address, $vendor->location_id);
                        }
                    }

//                    $locations = Location::find($vendor->location_id);
//
//                    $lat[0] = substr($locations->latitude, 0, 2);
//                    $lat[1] = substr($locations->latitude, 2);
//                    $latitude = $lat[0] . "." . $lat[1];
//
//                    $long[0] = substr($locations->longitude, 0, 3);
//                    $long[1] = substr($locations->longitude, 3);
//                    $longitude = $long[0] . "." . $long[1];

                    $response['routes'][] =[
                        'route_id' => $route->route_id,
                        'order_count' => $vendorOrderCount,
                        'route_type' => $route->mile_type,
                        'type' => 'pickup',
                        'vendor_id' => (string)$vendor->id,
                        'contact' => [
                            'name' => $vendor->name,
                            'email' => $vendor->email,
                            'phone' => $vendor->phone
                        ],
                        'location' => [
                            'address' => $address ?? '',
                            'latitude' => $latitude ?? '',
                            'longitude' => $longitude ?? '',
                        ],
                    ];

                    $vendorIds[] = $route->task_id;
                    $vendor_ids = array_unique($vendorIds);

                    $hub = Hub::find($route->hub);
                    $dashboardUserEmails = Dashboard::where('hub_id', $route->hub)->pluck('email');
                    $hubDetail = JoeycoUsers::whereIn('email_address', $dashboardUserEmails)->first();


//                    if ($response['routes'][$count]['route_id'] == $route->route_id) {
                    if($response['routes'][$count]['type'] != 'dropoff'){
                        $dropoffCount = Vendor::getVendorDropoffOrdersCount($route->route_id, 125);
                        $response['routes'][] = [
                            'route_id' => $route->route_id,
                            'dropoff_count' => $dropoffCount,
                            'route_type' => 1,
                            'type' => 'dropoff',
                            'hub_id' => (string)$hub->id,
                            'vendor_id' => '',
                            'contact' => [
                                'name' => $hubDetail->full_name ?? 'N/A',
                                'hub_name' => $hubDetail->full_name ?? 'N/A',
                                'email' => $hubDetail->email_address ?? 'N/A',
                                'phone' => $hubDetail->phone_no ?? 'N/A',
                            ],
                            'location' => [
                                'address' => $hub->address ?? '',
                                'latitude' => (string)$hub->hub_latitude ?? '',
                                'longitude' => (string)$hub->hub_longitude ?? '',
                            ],
                        ];
                    }

//                    }
                    if ($response['routes'][$count]['type'] == 'dropoff') {
                        $response['routes'][$count]['vendor_id'] = implode(', ', $vendor_ids);

                        $response['routes'][$count]['dropoff_count'] = $dropoffCount;
                        sort($response['routes']);
                    }
                    $count++;
                }
                if($route->mile_type == 2){
                    $miJobRoute = MiJobRoute::where('route_id', $route->route_id)->first();
                    if(isset($miJobRoute)){
                        $miJobDetail = MiJobDetail::where('locationid',$route->task_id)->where('mi_job_id', $miJobRoute->mi_job_id)->first();
                        if(isset($miJobDetail->location_type)){
                            if($miJobDetail->location_type == 'hub'){
                                if($miJobDetail->type == 'pickup'){
                                    $hub = Hub::whereNull('deleted_at')->find($miJobDetail->locationid);
                                    $user = Dashboard::where('hub_id', $hub->id)->pluck('id');

                                    $dashboardUserEmails = Dashboard::where('hub_id', $hub->id)->pluck('email');
                                    $hubDetail = JoeycoUsers::whereIn('email_address', $dashboardUserEmails)->first();

                                    $microHubBundle = MicroHubOrder::whereHas('sprint', function($query) {
                                        $query->where('status_id', 148)->whereNotIn('status_id', [36]);
                                    })->where('is_my_hub', 0)
//                                        ->whereDate('created_at', 'LIKE', $date.'%')
                                        ->whereNull('deleted_at')
                                        ->whereIn('scanned_by',$user)
                                        ->groupBy('hub_id')
                                        ->get()
                                        ->toArray();

                                    $sprintIds = CurrentHubOrder::where('hub_id', $hub->id)->where('is_actual_hub', 0)->pluck('sprint_id');
                                    $hubBundleOther = MicroHubOrder::whereHas('sprint', function($query) {
                                        $query->where('status_id', 150)->whereNotIn('status_id', [36]);
                                    })->whereIn('sprint_id',$sprintIds)
//                                        ->whereDate('created_at', 'LIKE', $date.'%')
                                        ->whereNull('deleted_at')
                                        ->groupBy('bundle_id')
                                        ->get()
                                        ->toArray();

                                    $count = array_merge($microHubBundle, $hubBundleOther);
                                    $response['routes'][] =[
                                        'route_id' => $route->route_id,
                                        'reference_no' => 'MR-'.$miJobDetail->mi_job_id,
                                        'bundle_count' => count($count),
                                        'route_type' => $route->mile_type,
                                        'type' => $miJobDetail->type,
                                        'hub_id' => (string)$hub->id,
                                        'contact' => [
                                            'hub_name' => $hubDetail->full_name ?? 'N/A',
                                            'email' => $hubDetail->email_address ?? 'N/A',
                                            'phone' => $hubDetail->phone_no ?? 'N/A',
                                        ],
                                        'location' => [
                                            'address' => $hub->address ?? '',
                                            'latitude' => (string)$hub->hub_latitude ?? '',
                                            'longitude' => (string)$hub->hub_longitude ?? '',
                                        ],
                                    ];

                                }
                                if($miJobDetail->type == 'dropoff'){
                                    $hub = Hub::whereNull('deleted_at')->find($miJobDetail->locationid);

                                    $dashboardUserEmails = Dashboard::where('hub_id', $hub->id)->pluck('email');
                                    $hubDetail = JoeycoUsers::whereIn('email_address', $dashboardUserEmails)->first();

                                    $dropoffBundleCount = MidMilePickDrop::where('route_id', $route->route_id)
                                        ->where('joey_id', $joey->id)->where('status_id', 149)->whereNull('deleted_at')->count();
                                    $response['routes'][] =[
                                        'route_id' => $route->route_id,
                                        'reference_no' => 'MR-'.$miJobDetail->mi_job_id,
                                        'bundle_count' => $dropoffBundleCount,
                                        'route_type' => $route->mile_type,
                                        'type' => $miJobDetail->type,
                                        'hub_id' => (string)$hub->id,
                                        'contact' => [
                                            'hub_name' => $hubDetail->full_name ?? 'N/A',
                                            'email' => $hubDetail->email_address ?? 'N/A',
                                            'phone' => $hubDetail->phone_no ?? 'N/A',
                                        ],
                                        'location' => [
                                            'address' => $hub->address ?? '',
                                            'latitude' => (string)$hub->hub_latitude ?? '',
                                            'longitude' => (string)$hub->hub_longitude ?? '',
                                        ],
                                    ];
                                }
                            }
                        }
                    }
                }
                if($route->mile_type == 4){
                    $miJobRoute = MiJobRoute::where('route_id', $route->route_id)->first();
                    if(isset($miJobRoute)){
                        $miJobDetail = MiJobDetail::where('locationid',$route->task_id)->where('mi_job_id', $miJobRoute->mi_job_id)->first();
                        if(isset($miJobDetail->location_type)){
                            if($miJobDetail->type == 'pickup'){
                                if($miJobDetail->location_type == 'store'){
                                    $vendor = Vendor::whereNull('deleted_at')->find($miJobDetail->locationid);

                                    $address = $vendor->business_address;
                                    $vendorLatitude = $vendor->latitude;
                                    $vendorLongitude = $vendor->longitude;

                                    $lat[0] = substr($vendorLatitude, 0, 2);
                                    $lat[1] = substr($vendorLatitude, 2);
                                    $latitude = $lat[0] . "." . $lat[1];

                                    $long[0] = substr($vendorLongitude, 0, 3);
                                    $long[1] = substr($vendorLongitude, 3);
                                    $longitude = $long[0] . "." . $long[1];

                                    $midMileOrderCount[] = $vendor->id;

                                    if($vendor->business_address == null){
                                        $location = Location::find($vendor->location_id);
                                        $address = $location->address;

                                        $lat[0] = substr($location->latitude, 0, 2);
                                        $lat[1] = substr($location->latitude, 2);
                                        $latitude = $lat[0] . "." . $lat[1];

                                        $long[0] = substr($location->longitude, 0, 3);
                                        $long[1] = substr($location->longitude, 3);
                                        $longitude = $long[0] . "." . $long[1];
                                    }





                                    $storeOrderCount += $vendor->getVendorOrdersCount($route->route_date, $route->task_id);

                                    $response['routes'][] =[
                                        'route_id' => $route->route_id,
                                        'order_count' => $vendor->getVendorOrdersCount(date("Y-m-d", strtotime($route->route_date)), $route->task_id),
                                        'reference_no' => 'MR-'.$miJobDetail->mi_job_id,
                                        'route_type' => $route->mile_type,
                                        'type' => $miJobDetail->type,
                                        'vendor_id' => (string)$vendor->id,
                                        'contact' => [
                                            'name' => $vendor->name,
                                            'email' => $vendor->email,
                                            'phone' => $vendor->phone
                                        ],
                                        'location' => [
                                            'address' => $address ?? '',
                                            'latitude' => $latitude ?? '',
                                            'longitude' => $longitude ?? '',
                                        ],
                                    ];

                                    $vendorIds[] = $route->task_id;
                                    $vendor_ids = array_unique($vendorIds);

                                }
                                if($miJobDetail->location_type == 'hub'){


                                    $hub = Hub::whereNull('deleted_at')->find($miJobDetail->locationid);
                                    $user = Dashboard::where('hub_id', $hub->id)->pluck('id');

                                    $dashboardUserEmails = Dashboard::where('hub_id', $hub->id)->pluck('email');
                                    $hubDetail = JoeycoUsers::whereIn('email_address', $dashboardUserEmails)->first();

                                    $microHubBundle = MicroHubOrder::whereHas('sprint', function($query) {
                                        $query->where('status_id', 148)->whereNotIn('status_id', [36]);
                                    })->where('is_my_hub', 0)
                                        ->whereNull('deleted_at')
                                        ->whereIn('scanned_by',$user)
                                        ->groupBy('hub_id')
                                        ->get()
                                        ->toArray();

                                    $sprintIds = CurrentHubOrder::where('hub_id', $hub->id)->where('is_actual_hub', 0)->pluck('sprint_id');
                                    $hubBundleOther = MicroHubOrder::whereHas('sprint', function($query) {
                                        $query->where('status_id', 150)->whereNotIn('status_id', [36]);
                                    })->whereIn('sprint_id',$sprintIds)
                                        ->whereNull('deleted_at')
                                        ->groupBy('bundle_id')
                                        ->get()
                                        ->toArray();

                                    $count = array_merge($microHubBundle, $hubBundleOther);

                                    $response['routes'][] =[
                                        'route_id' => $route->route_id,
                                        'bundle_count' => count($count),
                                        'reference_no' => 'MR-'.$miJobDetail->mi_job_id,
                                        'route_type' => $route->mile_type,
                                        'type' => $miJobDetail->type,
                                        'hub_id' => (string)$hub->id,
                                        'contact' => [
                                            'hub_name' => $hubDetail->full_name ?? 'N/A',
                                            'email' => $hubDetail->email_address ?? 'N/A',
                                            'phone' => $hubDetail->phone_no ?? 'N/A',
                                        ],
                                        'location' => [
                                            'address' => $hub->address ?? '',
                                            'latitude' => (string)$hub->hub_latitude ?? '',
                                            'longitude' => (string)$hub->hub_longitude ?? '',
                                        ],
                                    ];

                                }
                            }
                            if($miJobDetail->type == 'dropoff'){

                                $hub = Hub::whereNull('deleted_at')->find($miJobDetail->locationid);
                                $dashboardUserEmails = Dashboard::where('hub_id', $hub->id)->pluck('email');
                                $hubDetail = JoeycoUsers::whereIn('email_address', $dashboardUserEmails)->first();

                                $dropoffBundleCount = MidMilePickDrop::where('route_id', $route->route_id)->where('joey_id', $joey->id)->where('status_id', 149)->whereNull('deleted_at')->count();
                                $dropoffOrderCount = Vendor::getVendorDropoffOrdersCount($route->route_id, 125);

                                $response['routes'][] =[
                                    'route_id' => $route->route_id,
                                    'bundle_count' => $dropoffBundleCount,
                                    'order_count' => $dropoffOrderCount,
                                    'reference_no' => 'MR-'.$miJobDetail->mi_job_id,
                                    'route_type' => $route->mile_type,
                                    'type' => $miJobDetail->type,
                                    'hub_id' => (string)$hub->id,
                                    'contact' => [
                                        'hub_name' => $hubDetail->full_name ?? 'N/A',
                                        'email' => $hubDetail->email_address ?? 'N/A',
                                        'phone' => $hubDetail->phone_no?? 'N/A'
                                    ],
                                    'location' => [
                                        'address' => $hub->address ?? '',
                                        'latitude' => (string)$hub->hub_latitude ?? '',
                                        'longitude' => (string)$hub->hub_longitude ?? '',
                                    ],
                                ];
                            }
                        }
                    }
                }
                if($route->mile_type == 3){
                    if(isset($route->task_status_id)){
                        if (!in_array($route->task_status_id, [105, 111, 17, 113, 114, 116, 117, 118, 132, 138, 139, 144, 141])) {
                            $pickedUp = '';
                            if (!empty($route->sprint_id)) {
                                $pickedUp = SprintTaskHistory::where('sprint_id', '=', $route->sprint_id)
                                    ->groupBy('sprint_id')
                                    ->where('status_id', '=', '121')->first();
                            }

                            $returned = 0;
                            if (isset($route->task_status_id)) {
                                if (in_array($route->task_status_id, [101, 110, 112, 104, 105, 106, 107, 108, 109, 111, 131, 135, 140])) {
                                    $returned = 1;
                                }
                            }

                            $latitude = '';
                            $longitude = '';


                            if (isset($route->location_id)) {
                                $lat[0] = substr($route->loc_latitude, 0, 2);
                                $lat[1] = substr($route->loc_latitude, 2);
                                $latitude = $lat[0] . "." . $lat[1];

                                $long[0] = substr($route->loc_longitude, 0, 3);
                                $long[1] = substr($route->loc_longitude, 3);
                                $longitude = $long[0] . "." . $long[1];

                            }

                            $lastMileRoute = [
                                'route_id' => $route->route_id,
                                'num' => 'R-' . $route->route_id . '-' . $route->ordinal,
                                'start_time' => $route->start_time ?? '',
                                'end_time' => $route->end_time ?? '',
                                'arrival_time' => $route->arrival_time ?? '',
                                'finish_time' => $route->finish_time ?? '',
                                'route_type' => $route->mile_type,
                                'description' => $route->description,
                                'additional_info' => $route->additional_info,
                                'contact' => [
                                    'name' => $route->name ?? '',
                                    'phone' => $route->phone ?? '',
                                    'email' => $route->email ?? ''
                                ]
                                ,
                                'location' => [
                                    'address' => $route->loc_address ?? '',
                                    'latitude' => $latitude ?? '',
                                    'longitude' => $longitude ?? '',
                                    'address_line2' => $route->address_line2 ?? ''
                                ],
                                'task_id' => $route->task_id ?? '',
                                'tracking_id' => $route->tracking_id ?? '',
                                'merchant_order_num' => $route->merchant_order_num ?? '',
                                'ordinal' => $route->ordinal ?? '',
                                'has_picked' => isset($pickedUp) ? 1 : 0,
                                'returned' => $returned ?? '',
                            ];

                            if(isset($optimizeItinerary->is_optimize)) {
                                if ($optimizeItinerary->is_optimize == 0) {
                                    $response['routes'][] = $lastMileRoute;
                                }
                                $lastMileRoute2[]=$lastMileRoute;
                            }else{
                                $response['routes'][] = $lastMileRoute;
                            }
                        }
                    }
                }
                if($route->mile_type == 5){
                    if(isset($route->task_status_id)){
                        if (!in_array($route->task_status_id, [111, 17, 113, 114, 116, 117, 118, 132, 138, 139, 144, 141, 145])) {
                            $pickedUp = '';
                            if (!empty($route->sprint_id)) {
                                $pickedUp = SprintTaskHistory::where('sprint_id', '=', $route->sprint_id)
                                    ->groupBy('sprint_id')
                                    ->where('status_id', '=', '125')->first();
                            }

                            $returned = 0;
                            if (isset($route->task_status_id)) {
                                if (in_array($route->task_status_id, [110, 112, 104, 105, 106, 107, 108, 109, 111, 131, 135])) {
                                    $returned = 1;
                                }
                            }

                            $taskId = SprintTasks::where('sprint_id', $route->sprint_id)->where('type', '=', 'dropoff')->pluck('id');
                            $merchant = MerchantsIds::whereIn('task_id', $taskId)->pluck('tracking_id');
                            $booking =  HaillifyBooking::where('sprint_id', $route->sprint_id)->first();
                            $latitude = '';
                            $longitude = '';

                            if (isset($route->location_id)) {
                                $lat[0] = substr($route->loc_latitude, 0, 2);
                                $lat[1] = substr($route->loc_latitude, 2);
                                $latitude = $lat[0] . "." . $lat[1];

                                $long[0] = substr($route->loc_longitude, 0, 3);
                                $long[1] = substr($route->loc_longitude, 3);
                                $longitude = $long[0] . "." . $long[1];

                            }
                            $response['routes'][]=[
                                'route_id' => $route->route_id,
                                'num' => 'R-' . $route->route_id . '-' . $route->ordinal,
                                'start_time' => $route->start_time ?? '',
                                'end_time' => $route->end_time ?? '',
                                'arrival_time' => $route->arrival_time ?? '',
                                'finish_time' => $route->finish_time ?? '',
                                'pickup_time' => ($route->type == 'pickup') ? $booking->pickup_time : date('Y-m-d H:i:s', strtotime($booking->pickup_time) + 60*60),
                                'route_num' => ($booking->route_num) ?? 'N/A',
                                'route_type' => $route->mile_type,
                                'type' => $route->type,
                                'description' => $route->description,
                                'contact' => [
                                    'name' => $route->name ?? '',
                                    'phone' => $route->phone ?? '',
                                    'email' => $route->email ?? ''
                                ],
                                'location' => [
                                    'address' => $route->suite.' '.$route->loc_address ?? '',
                                    'latitude' => $latitude ?? '',
                                    'longitude' => $longitude ?? '',
                                    'address_line2' => $route->address_line2 ?? ''
                                ],
                                'task_id' => $route->task_id ?? '',
                                'tracking_id' => $route->tracking_id ?? '',
                                'merchant_order_num' => $route->merchant_order_num ?? '',
                                'ordinal' => $route->ordinal ?? '',
                                'has_picked' => isset($pickedUp) ? 1 : 0,
                                'returned' => $returned ?? '',
                            ];
                            if($route->type == 'pickup'){
                                $response['routes'][$count]['tracking_ids'] = $merchant;
                            }
                            $count++;
//                            $response['routes'] = collect($response['routes'])->sortBy('route_type')->values()->toArray();
//                            $response['routes'] = collect($response['routes'])->sortBy('ordinal')->values()->toArray();
                        }
                    }
                }
            }

            if(isset($optimizeItinerary->is_optimize)){

                $response['is_optimize'] = $optimizeItinerary->is_optimize;
                if($optimizeItinerary->is_optimize == 1){
                    $optimize =[];
                    foreach($optimizeItinerary->itinerary as $key => $itinerary){
                        array_push($optimize, $itinerary->task_id);
                    }
                    foreach ($optimize as $op){
                        foreach($lastMileRoute2 as $key => $ind){
                            if($ind['task_id'] == $op){
                                $response['routes'][] = $lastMileRoute2[$key];
                            }
                        }
                    }
                }
            }


            $response['Status'] = new RouteStatusListResource($request);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, 'joey route');

    }

    public function getCountries()
    {
        $countries = Country::all();
        return RestAPI::response($countries, true, 'fetch Countries');
    }

}
