<?php

namespace App\Http\Controllers\Api;

use App\Classes\RestAPI;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AssignDriverRequest;
use App\Http\Requests\Api\CancelOrderRequest;
use App\Http\Requests\Api\RetrieveJoeyLocationRequest;
use App\Http\Requests\Api\UpdateEtaEtcRequest;
use App\Http\Requests\Api\UpdateOrderRequest;
use App\Models\Dispatch;
use App\Models\Joey;
use App\Models\JoeyLocations;
use App\Models\Sprint;
use App\Models\SprintSprintHistory;
use App\Models\SprintTaskHistory;
use App\Models\StatusMap;

class OnDemandUpdateController extends Controller
{
    public function orderStatus(UpdateOrderRequest $request)
    {
        $statusId = $request->get('status_id');
        $sprintId = $request->get('sprint_id');

//        $statusCode = StatusCode::find($statusId);
        $sprint = Sprint::find($sprintId);
        $dispatch = Dispatch::whereSprintId($sprintId)->first();

//        $dispatches = Dispatch::find($dispatch[0]->id);
        $statusDescription= StatusMap::getDescription($statusId);

        $sprint->update([
            'status_id' => $statusId
        ]);

        $sprint->sprintTask()->update([
            'status_id' => $statusId,
        ]);

        if(!empty($dispatch)){
            $dispatch->update([
                'status' => $statusId,
                'status_copy' => $statusDescription
            ]);
        }


        SprintSprintHistory::create([
            'sprint__sprints_id' => $sprint->id,
            'status_id' => $statusId
        ]);

        foreach ($sprint->sprintTask as $taskHistory){
            SprintTaskHistory::create([
                'sprint__tasks_id' => $taskHistory->id,
                'sprint_id' => $taskHistory->sprint_id,
                'status_id' => $statusId
            ]);
        }

        return RestAPI::response($statusDescription, true, 'order status update successfully');

    }

    public function assignDriver(AssignDriverRequest $request)
    {
        $sprint = Sprint::find($request->get('sprint_id'));
        $sprint->update([
            'joey_id' => $request->get('joey_id')
        ]);

        return RestAPI::response([], true, 'Order Assign To Joey Successfully');

    }

    public function cancelOrder(CancelOrderRequest $request)
    {
        $sprint = Sprint::find($request->get('sprint_id'));
        $sprint->update([
            'status_id' => 36
        ]);
        return RestAPI::response([], true, 'successfully canceled order');
    }

    public function joeyLocationAndStatus(RetrieveJoeyLocationRequest $request)
    {
        $joeyId = $request->get('joey_id');
        $joey = Joey::find($joeyId);
        $joeyLocation = JoeyLocations::whereJoeyId($joeyId)->orderBy('updated_at', 'DESC')->first();

        $online = 'Offline';
        $active = 'InActive';
        $duty = 'Not On Duty';
        $shift = 'Not On Shift';

        if($joey->is_online == 1){
            $online = 'Online';
        }
        if($joey->is_on_shift == 1){
            $shift = 'On Shift';
        }
        if($joey->on_duty == 1){
            $duty = 'On Duty';
        }
        if($joey->is_active == 1){
            $active = 'Active';
        }

        $response = [
            'on_duty' => $duty,
            'is_active' => $active,
            'is_on_shift' => $shift,
            'is_online' => $online,
            'latitude' => $joeyLocation->latitude,
            'longitude' => $joeyLocation->longitude
        ];


        return RestAPI::response($response, true, 'successfully retrieve joey location and status');
    }

    public function updateArrivalAndCompletionTime(UpdateEtaEtcRequest $request)
    {
        $sprint = Sprint::find($request->get('sprint_id'));
        $sprint->sprintTask()->update([
            'eta_time' => $request->get('eta_time'),
            'etc_time' => $request->get('etc_time'),
        ]);

        return RestAPI::response([], true, 'successfully update Arrival And Completion Time');
    }
}
