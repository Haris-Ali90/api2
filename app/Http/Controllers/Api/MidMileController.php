<?php

namespace App\Http\Controllers\Api;

use App\Classes\RestAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Dashboard;
use App\Models\ManagerDashboard;
use App\Models\SprintConfirmation;
use App\Models\Hub;
use App\Models\Joey;
use App\Models\SprintTasks;
use App\Models\Sprint;
use App\Models\MicroHubOrder;
use App\Models\SprintTaskHistory;
use App\Models\CurrentHubOrder;
use App\Models\CtcEnteries;
use App\Models\BoradlessDashboard;
use App\Models\MidMilePickDrop;
use App\Models\JoeyRoutes;
use App\Models\JoeyRouteLocation;
use App\Http\Resources\MidMilePickAndDropResource;
use App\Repositories\Interfaces\SprintRepositoryInterface;

class MidMileController extends ApiBaseController
{
    private $sprintRepository;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(SprintRepositoryInterface $sprintRepository)
    {
        $this->sprintRepository = $sprintRepository;
    }

    public function midMilePickDropOrder(Request $request)
    {

        $request->validate([
            'route_id' => 'required|exists:joey_routes,id',
            'bundle_id' => 'required|exists:orders_actual_hub,bundle_id',
            'hub_id' => 'required|exists:hubs,id',
            'type' => 'required'
        ]);

        $data = $request->all();
        $response =[];
        $hub =[];
        DB::beginTransaction();

        try {

            $joey = Joey::find(auth()->user()->id);

            if (empty($joey)) {
                return RestAPI::response('Joey record not found', false);
            }

            $checkJoey = JoeyRoutes::where('joey_id', $joey->id)->where('id', $data['route_id'])->first();
            if (empty($checkJoey)) {
                return RestAPI::response('This route not assign to this joey', false);
            }

            $hub = Hub::find($data['hub_id']);

            if($data['type'] == 'pickup' || $data['type'] == 'pick'){
                $status = 149;
                $bundleData = explode('-', $data['bundle_id']);
                MidMilePickDrop::create([
                    'joey_id' => $joey->id,
                    'route_id' => $data['route_id'],
                    'bundle_id' => $data['bundle_id'],
                    'pickup_hub_id' => $data['hub_id'],
                    'status_id' => $status,
                ]);
                $message = 'This bundle is picked from hub successfully';

                $microhubOrders = MicroHubOrder::where('bundle_id', $data['bundle_id'])
                    ->where('is_my_hub', 0)
                    ->get();
            }

            if($data['type'] == 'dropoff' || $data['type'] == 'drop'){
                $status = 150;
                $message = 'This bundle is dropped to other hub successfully';
                $bundleData = explode('-', $data['bundle_id']);
                $isMyHub = 0;
                if($bundleData[1] == $data['hub_id']){
                    $status = 147;
                    $message = 'This bundle is dropped to related hub successfully';
                    $isMyHub = 1;
                }

                $microhubOrders = MicroHubOrder::where('bundle_id', $data['bundle_id'])
                    ->where('is_my_hub', 0)
                    ->get();

                $record = MidMilePickDrop::where('joey_id', $joey->id)
                    ->where('bundle_id', $data['bundle_id'])
                    ->where('route_id', $data['route_id'])
                    ->whereNull('deleted_at')
                    ->update(['deleted_at'=>date('Y-m-d H:i:s')]);

                $checkRecord = MidMilePickDrop::where('joey_id', $joey->id)
                    ->where('route_id', $data['route_id'])
                    ->whereNull('deleted_at')
                    ->pluck('pickup_hub_id');

                if(count($checkRecord) == 0){
                    $joeyRoutes = JoeyRoutes::where('id',$data['route_id'])->whereIn('mile_type',[2,4])->update(['route_completed'=>1]);
                }

            }

            if(count($microhubOrders) > 0){
                foreach ($microhubOrders as $microhubOrder){
                    $sprint = Sprint::find($microhubOrder->sprint_id)->update(['status_id'=> $status]);
                    $sprintTask = SprintTasks::whereNull('deleted_at')->where('sprint_id',$microhubOrder->sprint_id)->update(['status_id'=>$status]);

                    CtcEnteries::where('sprint_id', $microhubOrder->sprint_id)->whereNull('deleted_at')->update(['task_status_id' => $status]);
                    BoradlessDashboard::where('sprint_id', $microhubOrder->sprint_id)->whereNull('deleted_at')->update(['task_status_id' => $status]);

                    $spTask = SprintTasks::whereNull('deleted_at')->where('sprint_id',$microhubOrder->sprint_id)->get();

                    if(!empty($data['image'])){
                        $path=  $this->upload($data['image']);
                        $extArray = explode('.',$path);
                        $extension = end($extArray);
                        if(!isset($path)){
                            return RestAPI::response('File cannot be uploaded due to server error!', false);
                        }
                    }
                    if($data['type'] == 'dropoff' || $data['type'] == 'drop'){
                        CurrentHubOrder::firstOrCreate([
                            'hub_id' => $hub->id,
                            'sprint_id' => $microhubOrder->sprint_id,
                            'joey_id' => Auth::user()->id,
                            'is_actual_hub' => $isMyHub,
                        ]);
                    }
                    foreach($spTask as $task){
                        $sprintTaskHistory = [
                            'sprint__tasks_id' => $task->id,
                            'sprint_id' => $task->sprint_id,
                            'status_id' => $status,
                        ];
                        SprintTaskHistory::create($sprintTaskHistory);

                        $confirmations = [
                            'ordinal' => $task->ordinal,
                            'task_id' => $task->id,
                            'joey_id' => Auth::user()->id,
                            'name'    => 'hub',
                            'title'   => 'other hub',
                            'confirmed' => 1,
                            'input_type' => $extension,
                            'attachment_path' => $path,
                        ];
                        SprintConfirmation::create($confirmations);
                    }
                }
            }else{
                return RestAPI::response('Invalid bundle id.', false);
            }

            $response = new MidMilePickAndDropResource($hub,$status);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, $message);

    }

    public function upload($base64Data) {

        $data = ['image' => $base64Data];
        $response =  $this->sendData('POST', '/',  $data );
        if(!isset($response->url)) {
            return null;
        }
        return $response->url;

    }

    public function sendData($method, $uri, $data=[] ) {

        $host ='assets.joeyco.com';

        $json_data = json_encode($data);

        $headers = [
            'Accept-Encoding: utf-8',
            'Accept: application/json; charset=UTF-8',
            'Content-Type: application/json; charset=UTF-8',
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
                $this->response = (object) [
                    'copyright' => 'Copyright © ' . date('Y') . ' JoeyCo Inc. All rights reserved.',
                    'http' => (object) [
                        'code' => 500,
                        'message' => json_last_error_msg(),//\JoeyCo\Http\Code::get(500),
                    ],
                    'response' => new \stdClass()
                ];
            }
        }
        return $this->response;
    }


}
