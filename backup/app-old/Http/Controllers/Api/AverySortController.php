<?php

namespace App\Http\Controllers\Api;

use App\Classes\RestAPI;
use App\Models\AmazonEntry;
use App\Models\BoradlessDashboard;
use App\Models\CtcEnteries;
use App\Models\Dispatch;
use App\Models\JoeyRouteLocation;
use App\Models\RouteTransferLocation;
use App\Models\Sprint;
use App\Models\SprintTaskHistory;
use App\Models\SprintTasks;
use App\Models\StatusMap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AverySortController extends ApiBaseController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */


    public function index(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'tracking_id' => 'required|exists:merchantids,tracking_id'
        ]);

        if ($validate->fails() == true) {
            return RestAPI::responsewithCode('Invalid tracking id', false);
        }

        $data = $request->all();

        $loc = JoeyRouteLocation::join('merchantids', 'merchantids.task_id', '=', 'joey_route_locations.task_id')
            ->where('tracking_id', '=', $data['tracking_id'])
            ->whereNull('joey_route_locations.deleted_at')
            ->first(['joey_route_locations.id', 'joey_route_locations.created_at', 'joey_route_locations.task_id', 'joey_route_locations.route_id', 'ordinal', 'merchant_order_num', 'joey_route_locations.is_transfered']);

        if (empty($loc)) {
            return RestAPI::responsewithCode('Invalid QR code', false);
        }
// dd($loc);
        $sprint = SprintTasks::where('id', $loc->task_id)->first();

        if(empty($sprint)){
            return RestAPI::responsewithCode('Invalid Tracking', false);
        }

        // check sort status in task history and if order already sorted then return
        $sortStatus = SprintTaskHistory::where('sprint_id', '=', $sprint->sprint_id)->where('status_id', 133)->orderBy('id', 'DESC')->first();
        $checksortStatus = true;
        if (!empty($sortStatus)) {
            $taskHistoryCreated = new \DateTime(date($sortStatus->created_at));
            $routeCreatedDate = new \DateTime(date($loc->created_at));
            if ($routeCreatedDate > $taskHistoryCreated) {
                $checksortStatus = true;
            } else {
                $checksortStatus = false;
            }
        }
        if (!$checksortStatus) {
            if ($loc->is_transfered) {
                $route_transfer_location = RouteTransferLocation::where('new_route_location_id', '=', $loc->id)->first();
                if ($route_transfer_location != null) {
                    $response['id'] = $route_transfer_location->old_route_location_id;
                    $response['num'] = "R-" . $route_transfer_location->old_route_id . "-" . $route_transfer_location->old_ordinal;
                }
            } else {
                $response['id'] = $loc->id;
                $response['num'] = "R-" . $loc->route_id . "-" . $loc->ordinal;
            }
            $response['merchant_order_num'] = $loc->merchant_order_num;
            $response['tracking_id'] = $data['tracking_id'];
            $response['message'] = "Confirmed successfully";
            $response['status'] = StatusMap::getDescription(133);
            return RestAPI::response($response, true, 'package sorted successfully');
        }

        $tasks = SprintTasks::where('sprint_id', '=', $sprint->sprint_id)->where('type', '=', 'dropoff')->get();
        foreach ($tasks as $task) {
            SprintTasks::where('id', $task->id)->update(['status_id' => 133]);
			
			$pickedStatus = SprintTaskHistory::where('sprint_id', '=', $sprint->sprint_id)->where('status_id', 125)->orderBy('id', 'DESC')->first();

			if(empty($pickedStatus)){
			 SprintTaskHistory::insert(['sprint__tasks_id' => $task->id, 'sprint_id' => $sprint->sprint_id, 'status_id' => 125, 'date' => date('Y-m-d H:i:s', strtotime('-1 hour')), 'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))]);
			}
			
            SprintTaskHistory::insert(['sprint__tasks_id' => $task->id, 'sprint_id' => $sprint->sprint_id, 'status_id' => 133, 'date' => date('y-m-d H:i:s'), 'created_at' => date('y-m-d H:i:s')]);
        }
        Sprint::where('id', $sprint->sprint_id)->update(['status_id' => 133]);
        Dispatch::where('sprint_id', '=', $sprint->sprint_id)
            ->update(['status' => 133, 'status_copy' => StatusMap::getDescription(133)]);

        // Get amazon enteries data from tracking id and check if the data exist in database and if exist update the sort date of the tracking id and status of that tracking id.
        $amazon_enteries = AmazonEntry::where('sprint_id', '=', $sprint->sprint_id)->whereNull('deleted_at')->first();
        if ($amazon_enteries != null) {
            $amazon_enteries->sorted_at = date('Y-m-d H:i:s');
            $amazon_enteries->task_status_id = 133;
            $amazon_enteries->save();
        }
        $ctc_entries = CtcEnteries::where('sprint_id', '=', $sprint->sprint_id)->whereNull('deleted_at')->first();
        if ($ctc_entries != null) {
            $ctc_entries->sorted_at = date('Y-m-d H:i:s');
            $ctc_entries->task_status_id = 133;
            $ctc_entries->save();
        }
        $borderless = BoradlessDashboard::where('sprint_id', '=', $sprint->sprint_id)->whereNull('deleted_at')->first();
        if ($borderless != null) {
            $borderless->sorted_at = date('Y-m-d H:i:s');
            $borderless->task_status_id = 133;
            $borderless->save();
        }
        if ($loc->is_transfered) {
            $route_transfer_location = RouteTransferLocation::where('new_route_location_id', '=', $loc->id)->first();
            if ($route_transfer_location != null) {
                $response['id'] = $route_transfer_location->old_route_location_id;
                $response['num'] = "R-" . $route_transfer_location->old_route_id . "-" . $route_transfer_location->old_ordinal;
            }
        } else {
            $response['id'] = $loc->id;
            $response['num'] = "R-" . $loc->route_id . "-" . $loc->ordinal;
        }
        $response['merchant_order_num'] = $loc->merchant_order_num;
        $response['tracking_id'] = $data['tracking_id'];
        $response['message'] = "Confirmed successfully";
        $response['status'] = StatusMap::getDescription(133);
        return RestAPI::response($response, true, 'package sorted successfully');

    }

    public function generateToken(Request $request)
    {
        $email = $request->header('email');
        $password = $request->header('password');

        if(isset($email) && !empty($email)){
            if(isset($password) && !empty($password))
            {
            }
            else
            {
                return RestAPI::responsewithCode('Password is required', false);
            }
        }
        else{
            return RestAPI::responsewithCode('Email is required', false);
        }

        $heademails=["avery@joeyco.com"=>"Avery123@","mtl@joeyco.com"=>"Montreal123@","ott@joeyco.com"=>"ottawa123@","toavery@joeyco.com"=>"to123@"];
        if(isset($heademails[$email]))
        {
            if($password==$heademails[$email])
            {
            }
            else
            {
                return RestAPI::responsewithCode('Invalid Email and Password', false);
            }
        }
        else
        {
            return RestAPI::responsewithCode('Invalid Email and Password', false);
        }
        $merchantId = $email.$password;
        $key = "8e15dde53fdd22423427b51655208f6abd7f5038f37ff938c10c1bb9b2cc8244";

        $res = $this->mac256($merchantId, $key);
        $result['token'] = $this->encodeBase64($res);

        return RestAPI::response($result, true, 'token created successfully');
    }

    public function mac256($ent,$key)
    {
        $res = hash_hmac('sha256', $ent, $key, true);
        return $res;
    }

    public function encodeBase64($data)
    {
        $data = base64_encode($data);
        return $data;
    }



}
