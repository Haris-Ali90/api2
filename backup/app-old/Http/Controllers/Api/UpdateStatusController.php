<?php

namespace App\Http\Controllers\Api;

use App\Classes\Fcm;
use App\Classes\RestAPI;
use App\Http\Controllers\Controller;
use App\Models\AmazonEnteries;
use App\Models\Claim;
use App\Models\CTCEntry;
use App\Models\CtcVendor;
use App\Models\JoeyRoutes;
use App\Models\MerchantsIds;
use App\Models\Reason;
use App\Models\RouteHistory;
use App\Models\Sprint;
use App\Models\SprintConfirmation;
use App\Models\SprintTaskHistory;
use App\Models\SprintTasks;
use App\Models\StatusMap;
use App\Models\TrackingImageHistory;
use App\Models\UserDevice;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UpdateStatusController extends ApiBaseController
{
    private     $status = array("136" => "Client requested to cancel the order",
        "137" => "Delay in delivery due to weather or natural disaster",
        "118" => "left at back door",
        "117" => "left with concierge",
        "135" => "Customer refused delivery",
        "108" => "Customer unavailable-Incorrect address",
        "106" => "Customer unavailable - delivery returned",
        "107" => "Customer unavailable - Left voice mail - order returned",
        "109" => "Customer unavailable - Incorrect phone number",
        "103" => "Delay at pickup",
        "114" => "Successful delivery at door",
        "113" => "Successfully hand delivered",
        "120" => "Delivery at Hub",
        "110" => "Delivery to hub for re-delivery",
        "111" => "Delivery to hub for return to merchant",
        "121" => "Out for delivery",
        "102" => "Joey Incident",
        "140" => "Delivery missorted, may cause delay",
        //  "104" => "Damaged on road - delivery will be attempted",
        "143" => "Damaged on road - undeliverable",
        "105" => "Item damaged - returned to merchant",
        "129" => "Joey at hub",
        "128" => "Package on the way to hub",
        "116" => "Successful delivery to neighbour",
        "132" => "Office closed - safe dropped",
        "101" => "Joey on the way to pickup",
        "124" => "At hub - processing",
        "112" => "To be re-attempted",
        "131" => "Office closed - returned to hub",
        "125" => "Pickup at store - confirmed",
        "133" => "Packages sorted",
        "141" => "Lost package");

    private $status_codes = [
        'completed'=>
            [
                "JCO_ORDER_DELIVERY_SUCCESS"=>17,
                "JCO_HAND_DELIEVERY" => 113,
                "JCO_DOOR_DELIVERY" => 114,
                "JCO_NEIGHBOUR_DELIVERY" => 116,
                "JCO_CONCIERGE_DELIVERY" => 117,
                "JCO_BACK_DOOR_DELIVERY" => 118,
                "JCO_OFFICE_CLOSED_DELIVERY" => 132,
                "JCO_DELIVER_GERRAGE" => 138,
                "JCO_DELIVER_FRONT_PORCH" => 139,
                "JCO_DEILVER_MAILROOM" => 144
            ],
        'return'=>
            [
                "JCO_ITEM_DAMAGED_INCOMPLETE" => 104,
                "JCO_ITEM_DAMAGED_RETURN" => 105,
                "JCO_CUSTOMER_UNAVAILABLE_DELIEVERY_RETURNED" => 106,
                "JCO_CUSTOMER_UNAVAILABLE_LEFT_VOICE" => 107,
                "JCO_CUSTOMER_UNAVAILABLE_ADDRESS" => 108,
                "JCO_CUSTOMER_UNAVAILABLE_PHONE" => 109,
                "JCO_HUB_DELIEVER_REDELIEVERY" => 110,
                "JCO_HUB_DELIEVER_RETURN" => 111,
                "JCO_ORDER_REDELIVER" => 112,
                "JCO_ORDER_RETURN_TO_HUB" => 131,
                "JCO_CUSTOMER_REFUSED_DELIVERY" => 135,
                "CLIENT_REQUEST_CANCEL_ORDER" => 136,
                "JCO_ON_WAY_PICKUP" => 101,
            ],

        'pickup'=>
            [
                "JCO_HUB_PICKUP"=>121
            ],

    ];

    public function statusRequestList()
    {
        DB::beginTransaction();
        try {
            $reasons = Reason::whereNull('deleted_at')->get();
            $response = [];
            foreach($this->status as $key => $value){
                $response['status'][] = [
                    'id' => $key,
                    'status' => $value
                ];
            }
            foreach($reasons as $reason){
                $response['reason'][] = [
                    'id' => $reason->id,
                    'status' => $reason->title
                ];
            }
            return RestAPI::response($response, true, "Status List");
        }catch(\Exception $exception){
            return RestAPI::response($exception->getMessage(), false, 'error_exception');
        }
    }

    public function managerUpdateStatus(Request $request)
    {
        $request->validate([
            'status_id' => 'required',
            'reason_id' => 'required',
            'image' => 'required'
        ]);
        $data = $request->all();


        $task=MerchantsIds::join('sprint__tasks','sprint__tasks.id','=','merchantids.task_id')
            ->join('sprint__sprints','sprint__sprints.id','=','sprint__tasks.sprint_id')
            ->where('sprint__sprints.id','=',$data['sprint_id'])
            ->where('sprint__tasks.type','=','dropoff')
            ->first(['sprint__tasks.id','sprint__tasks.sprint_id','sprint__tasks.ordinal','sprint__sprints.creator_id','merchantids.tracking_id']);

        if($task != null){
            $route_data=JoeyRoutes::join('joey_route_locations','joey_route_locations.route_id','=','joey_routes.id')
                ->where('joey_route_locations.task_id','=',$task->id)
                ->whereNull('joey_route_locations.deleted_at')
                ->first(['joey_route_locations.id','joey_routes.joey_id','joey_route_locations.route_id','joey_route_locations.ordinal']);
        }

        if(empty($route_data)) {
            return RestAPI::response([], false, 'Joey not assigned yet. Image cannot be uploaded.!');
        }

        $taskHistory=SprintTaskHistory::where('sprint_id','=',$data['sprint_id'])->where('status_id','=',125)->first();
        if($taskHistory) {
            if ($taskHistory->status_id == $data['status_id']) {
                return RestAPI::response('success', true, "Status updated successfully");
            }
        }

        $attachment_path = '';
//
//        $path = 'myfolder/myimage.png';
//        $type = pathinfo($path, PATHINFO_EXTENSION);
//        $data = file_get_contents($path);
//        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
//
//        if($data['image']){
//            $imageName = Str::random(12) . '.' . $request->file('profile_picture')->getClientOriginalExtension();
//            $path = public_path(Config::get('constants.front.dir.profilePicPath')).'\images\profile_images';
//            $request->file('profile_picture')->move($path, $imageName);
//            $data['profile_picture'] = url('/').'/images/profile_images/'.$imageName;
//        }else{
//            $imageName="default.png";
//            $postData['profile_picture'] = url('/').'/images/profile_images/'.$imageName;
//        }

//        $attachment_path = $this->base64_to_jpeg($data['image'], 'default.png');
//
//        dd($attachment_path);
        $image = ['image' => $data['image']];
//
        $response =  $this->sendData('POST', '/',  $image );

        if(!isset($response->url))
            return RestAPI::response('error', true, "File cannot be uploaded due to server error!");{
        }

        $attachment_path =   $response->url;

        $status = '';

        if(in_array($data['status_id'],$this->status_codes['completed']))
        {
            $status = 2;

        }elseif (in_array($data['status_id'],$this->status_codes['return'])){
            $status = 4;
        }
        elseif (in_array($data['status_id'],$this->status_codes['pickup'])){
            $status = 3;
        }

        $route_data=JoeyRoutes::join('joey_route_locations','joey_route_locations.route_id','=','joey_routes.id')
            ->where('joey_route_locations.task_id','=',$task->id)
            ->whereNull('joey_route_locations.deleted_at')
            ->first(['joey_route_locations.id','joey_routes.joey_id','joey_route_locations.route_id','joey_route_locations.ordinal','joey_route_locations.task_id']);

        if(!empty($route_data))
        {
            $routeHistoryRecord = [
                'route_id' =>$route_data->route_id,
                'route_location_id' => $route_data->id,
                'ordinal' => $route_data->ordinal,
                'joey_id'=>  $route_data->joey_id,
                'task_id'=>$task->id,
                'status'=> $status,
                'type'=>'Manual',
                'updated_by'=> jwt_manger()->user()->id,
            ];
            RouteHistory::create($routeHistoryRecord);
        }

        $statusDescription= StatusMap::getDescription($data['status_id']);

        $updateData = [
            'ordinal' => $task->ordinal,
            'task_id' => $task->id,
            'joey_id' =>$route_data->joey_id,
            'name' => $statusDescription,
            'title' => $statusDescription,
            'confirmed' => 1,
            'input_type' => 'image/jpeg',
            'attachment_path' => $attachment_path
        ];
        SprintConfirmation::create($updateData);

        if(!empty($task->id)) {

            $order_id = $task->sprint_id;
            $ctc_vendor_id = CtcVendor::where('vendor_id', '=', $task->creator_id)->first();
            if ($data['status_id']== 124 && !empty($ctc_vendor_id)) {
                $taskHistory = SprintTaskHistory::where('sprint_id', '=', $order_id)->where('status_id', '=', 125)->first();
                if ($taskHistory == null) {

                    $pickupstoretime_date=new \DateTime();
                    $pickupstoretime_date->modify('-2 minutes');

                    $taskHistory = new SprintTaskHistory();
                    $taskHistory->sprint_id = $order_id;
                    $taskHistory->sprint__tasks_id = $task->id;

                    $taskHistory->status_id = 125;
                    $taskHistory->date = $pickupstoretime_date->format('Y-m-d H:i:s');
                    $taskHistory->created_at = $pickupstoretime_date->format('Y-m-d H:i:s');
                    $taskHistory->save();
                }

            }

            $delivery_status = [17, 113, 114, 116, 117, 118, 132, 138, 139, 144, 104, 105, 106, 107, 108, 109, 110, 111, 112, 131, 135, 136];

            if (in_array($data['status_id'], $delivery_status)) {

                $taskHistory = SprintTaskHistory::where('sprint_id', '=', $order_id)->where('status_id', '=', 121)->first();
                if ($taskHistory == null) {

                    $pickuptime_date=new \DateTime();
                    $pickuptime_date->modify('-2 minutes');

                    $taskHistory = new SprintTaskHistory();
                    $taskHistory->sprint_id = $order_id;
                    $taskHistory->sprint__tasks_id = $task->id;
                    $taskHistory->status_id = 121;
                    $taskHistory->date=$pickuptime_date->format('Y-m-d H:i:s');
                    $taskHistory->created_at=$pickuptime_date->format('Y-m-d H:i:s');
                    $taskHistory->save();

                    if(!empty($route_data)){

                        $routehistory=new RouteHistory();
                        $routehistory->route_id=$route_data->route_id;
                        $routehistory->joey_id=$route_data->joey_id;
                        $routehistory->status=3;
                        $routehistory->route_location_id=$route_data->id;
                        $routehistory->task_id=$route_data->task_id;
                        $routehistory->ordinal=$route_data->ordinal;
                        $routehistory->created_at=$pickuptime_date->format('Y-m-d H:i:s');
                        $routehistory->updated_at=$pickuptime_date->format('Y-m-d H:i:s');
                        $routehistory->type='Manual';
                        $routehistory->updated_by=jwt_manger()->user()->id;

                        $routehistory->save();

                    }
                    $this->updateAmazonEntry(121,$order_id);
                    $this->updateCTCEntry(121,$order_id);
                    $this->updateClaims(121,$order_id);


                }

            }
        }
        SprintTasks::where('id','=',$task->id)->update(['status_id'=>$data['status_id']]);
        Sprint::where('id','=',$task->sprint_id)->whereNull('deleted_at')->update(['status_id'=>$data['status_id']]);

        $this->updateAmazonEntry($data['status_id'],$task->sprint_id,$attachment_path);
        $this->updateCTCEntry($data['status_id'],$task->sprint_id,$attachment_path);
        $this->updateClaims($data['status_id'],$task->sprint_id,$attachment_path);


        $taskHistoryRecord = [
            'sprint__tasks_id' =>$task->id,
            'sprint_id' => $task->sprint_id,
            'status_id' => $data['status_id'],
            'date' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),

        ];
        SprintTaskHistory::create( $taskHistoryRecord );

        $createData = [
            'tracking_id' => $task->tracking_id,
            'status_id' => $data['status_id'],
            'user_id' => jwt_manger()->user()->id,
            'attachment_path' => $attachment_path,
            'reason_id' => $data['reason_id'],
            'domain' => 'dashboard'
        ];
        TrackingImageHistory::create($createData);

        if (isset($route_data->joey_id)) {
            $deviceIds = UserDevice::where('user_id', $route_data->joey_id)->pluck('device_token');
            $subject = 'R-' . $route_data->route_id . '-' . $route_data->ordinal;
            $message = 'Your order status has been changed to ' . StatusMap::getDescription($data['status_id']);
            Fcm::sendPush($subject, $message, 'ecommerce', null, $deviceIds);
            $payload = ['notification' => ['title' => $subject, 'body' => $message, 'click_action' => 'ecommerce'],
                'data' => ['data_title' => $subject, 'data_body' => $message, 'data_click_action' => 'ecommerce']];
            $createNotification = [
                'user_id' => $route_data->joey_id,
                'user_type' => 'Joey',
                'notification' => $subject,
                'notification_type' => 'ecommerce',
                'notification_data' => json_encode(["body" => $message]),
                'payload' => json_encode($payload),
                'is_silent' => 0,
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ];
            UserNotification::create($createNotification);
        }

        return RestAPI::response(new \stdClass(), true, "Status updated successfully");

    }

    public function base64_to_jpeg($base64_string, $output_file) {
        // open the output file for writing
        $ifp = fopen( $output_file, 'wb' );

        // split the string on commas
        // $data[ 0 ] == "data:image/png;base64"
        // $data[ 1 ] == <actual base64 string>
        $data = explode( ',', $base64_string );

//        dd($data);
        // we could add validation here with ensuring count( $data ) > 1
        fwrite( $ifp, base64_decode( $data[0] ) );

        // clean up the file resource
        fclose( $ifp );

        return $output_file;
    }

    public function updateAmazonEntry($status_id,$order_id,$imageUrl=null)
    {
        if($status_id==133)
        {
            // Get amazon enteries data from tracking id and check if the data exist in database and if exist update the sort date of the tracking id and status of that tracking id.
            $amazon_enteries =AmazonEnteries::where('sprint_id','=',$order_id)->whereNull('deleted_at')->first();
            if($amazon_enteries!=null)
            {

                $amazon_enteries->sorted_at=date('Y-m-d H:i:s');
                $amazon_enteries->task_status_id=133;
                $amazon_enteries->order_image=$imageUrl;
                $amazon_enteries->save();

            }
        }
        elseif($status_id==121)
        {
            $amazon_enteries =AmazonEnteries::where('sprint_id','=',$order_id)->whereNull('deleted_at')->first();
            if($amazon_enteries!=null)
            {
                $amazon_enteries->picked_up_at=date('Y-m-d H:i:s');
                $amazon_enteries->task_status_id=121;
                $amazon_enteries->order_image=$imageUrl;
                $amazon_enteries->save();

            }
        }
        elseif(in_array($status_id,[17,113,114,116,117,118,132,138,139,144]))
        {
            $amazon_enteries =AmazonEnteries::where('sprint_id','=',$order_id)->whereNull('deleted_at')->first();
            if($amazon_enteries!=null)
            {
                $amazon_enteries->delivered_at=date('Y-m-d H:i:s');
                $amazon_enteries->task_status_id=$status_id;
                $amazon_enteries->order_image=$imageUrl;
                $amazon_enteries->save();

            }
        }
        elseif(in_array($status_id,[104,105,106,107,108,109,110,111,112,131,135,136,101,102,103,140]))
        {
            $amazon_enteries =AmazonEnteries::where('sprint_id','=',$order_id)->whereNull('deleted_at')->first();
            if($amazon_enteries!=null)
            {
                $amazon_enteries->returned_at=date('Y-m-d H:i:s');
                $amazon_enteries->task_status_id=$status_id;
                $amazon_enteries->order_image=$imageUrl;
                $amazon_enteries->save();

            }
        }

    }
    public function  updateCTCEntry($status_id,$order_id,$imageUrl=null)
    {
        if($status_id==133)
        {
            // Get amazon enteries data from tracking id and check if the data exist in database and if exist update the sort date of the tracking id and status of that tracking id.
            $ctc_entries =CTCEntry::where('sprint_id','=',$order_id)->whereNull('deleted_at')->first();
            if($ctc_entries!=null)
            {

                $ctc_entries->sorted_at=date('Y-m-d H:i:s');
                $ctc_entries->task_status_id=133;
                $ctc_entries->order_image=$imageUrl;
                $ctc_entries->save();

            }
        }
        elseif($status_id==121)
        {
            $ctc_entries =CTCEntry::where('sprint_id','=',$order_id)->whereNull('deleted_at')->first();
            if($ctc_entries!=null)
            {
                $ctc_entries->picked_up_at=date('Y-m-d H:i:s');
                $ctc_entries->task_status_id=121;
                $ctc_entries->order_image=$imageUrl;
                $ctc_entries->save();

            }
        }
        elseif(in_array($status_id,[17,113,114,116,117,118,132,138,139,144]))
        {
            $ctc_entries =CTCEntry::where('sprint_id','=',$order_id)->whereNull('deleted_at')->first();
            if($ctc_entries!=null)
            {
                $ctc_entries->delivered_at=date('Y-m-d H:i:s');
                $ctc_entries->task_status_id=$status_id;
                $ctc_entries->order_image=$imageUrl;
                $ctc_entries->save();

            }
        }
        elseif(in_array($status_id,[104,105,106,107,108,109,110,111,112,131,135,136,101,102,103,140,143]))
        {
            $ctc_entries =CTCEntry::where('sprint_id','=',$order_id)->whereNull('deleted_at')->first();
            if($ctc_entries!=null)
            {
                $ctc_entries->returned_at=date('Y-m-d H:i:s');
                $ctc_entries->task_status_id=$status_id;
                $ctc_entries->order_image=$imageUrl;
                $ctc_entries->save();

            }
        }

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

    public function sendData($method, $uri, $data=[] ) {
        $host ='assets.joeyco.com';

        $json_data = json_encode($data);
        $headers = [
            'Accept-Encoding: utf-8',
            'Accept: application/json; charset=UTF-8',
            'Content-Type: application/json; charset=UTF-8',
            // 'Accept-Language: ' . $locale->getLangCode(),
            'User-Agent: JoeyCo',
            'Host: ' . $host,
        ];

        if (!empty($json_data) ) {

            $headers[] = 'Content-Length: ' . strlen($json_data);

        }


        $url = 'https://' . $host . $uri;


        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if (strlen($json_data) > 2) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        }

        // $file=env('APP_ENV');
        //   dd(env('APP_ENV') === 'local');
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


//         var_dump([$this->originalResponse,$error]);
//         exit();
        curl_close($ch);

        if (empty($error)) {


            $this->response = explode("\n", $this->originalResponse);

            $code = explode(' ', $this->response[0]);
            $code = $code[1];

            $this->response = $this->response[count($this->response) - 1];
            $this->response = json_decode($this->response);

            if (json_last_error() != JSON_ERROR_NONE) {

                $this->response = (object) [
                    'copyright' => 'Copyright © ' . date('Y') . ' JoeyCo Inc. All rights reserved.',
                    'http' => (object) [
                        'code' => 500,
                        'message' => json_last_error_msg(),
                    ],
                    'response' => new \stdClass()
                ];
            }
        }
        // else{
        //     dd(['error'=> $error,'response'=>$this->originalResponse]);
        // }

        return $this->response;
    }
}
