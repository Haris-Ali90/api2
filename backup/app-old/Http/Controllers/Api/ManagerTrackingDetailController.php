<?php

namespace App\Http\Controllers\Api;

use App\Classes\RestAPI;
use App\Http\Resources\ManagerResource;
use App\Http\Resources\TrackingDetailResource;
use App\Http\Resources\ManagerTrackingDetailsResource;
use App\Models\MerchantsIds;
use App\Repositories\Interfaces\ManagerRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\ManagerDashboard;
use App\Models\CtcEnteries;
use App\Models\Hub;
use App\Models\SprintTasks;
use App\Models\Location;
use App\Models\Sprint;
use App\Models\MicroHubPostalCode;
use App\Models\MicroHubOrder;
use App\Models\SprintTaskHistory;
use App\Http\Resources\FirstMileSortOrderResource;

class ManagerTrackingDetailController extends ApiBaseController
{
    private $managerRepository;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(ManagerRepositoryInterface $managerRepository)
    {
        $this->managerRepository = $managerRepository;
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


            $merchantRecord=MerchantsIds::where('tracking_id',$data['tracking_id'])->first();


            if(!empty($merchantRecord)){

                $response = new ManagerTrackingDetailsResource($merchantRecord);
                //print_r($response);
            }
            else{
                return RestAPI::response('No record found against this Tracking Id', false);
            }


            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'Tracking Details');
    }


    public function firstMileOrderSort(Request $request)
    {

        $request->validate([
            'tracking_id' => 'required',
        ]);

        $data = $request->all();

        DB::beginTransaction();
        try {
            $hub = ManagerDashboard::find(auth()->user()->id);
            $hubId = $hub->hub_id;

            $merchantIds = MerchantsIds::with('taskids')->whereNull('deleted_at')->where('tracking_id', $data['tracking_id'])->first();

            if(empty($merchantIds)){
                return RestAPI::response('Tracking Id Is In valid', false);
            }

            $sprintTask = SprintTasks::whereNull('deleted_at')->find($merchantIds->task_id);

            if(empty($sprintTask)){
                return RestAPI::response('Task Not Available', false);
            }

            $location = Location::whereNull('deleted_at')->find($sprintTask->location_id);

            if(empty($location)){
                return RestAPI::response('Location Not Found Of This Tracking Id', false);
            }

            $sprint = Sprint::find($sprintTask->sprint_id);
            $postalCode = substr($location->postal_code, 0, 3);

            $microHubPostalCode = MicroHubPostalCode::where('postal_code', $postalCode)->first();

            if(empty($microHubPostalCode)){
                return RestAPI::response('Postal code not found', false);
            }

            $microHubOrder = MicroHubOrder::where('hub_id', $hubId)->where('sprint_id', $sprint->id)->first();

            $status = 0;
            $isMyHub = 0;
            if($microHubPostalCode->hub_id != $hubId){
                $status = 128;
                $isMyHub = 0;
            }
            if($microHubPostalCode->hub_id == $hubId){
                $status = 127;
                $isMyHub = 1;
            }



            if(!empty($microHubOrder)){
                $microHubOrder->update([
                    'hub_id' => $hubId,
                    'sprint_id' => $sprint->id,
                    'is_my_hub' => $isMyHub,
                ]);
            }else{
                MicroHubOrder::create([
                    'hub_id' => $hubId,
                    'sprint_id' => $sprint->id,
                    'is_my_hub' => $isMyHub,
                ]);
            }

            $sprint->update(['status_id'=>$status]);
            $sprintTask->update(['status_id'=>$status]);
            $sprintTaskHistory = [
                'sprint__tasks_id' => $sprintTask->id,
                'sprint_id' => $sprint->id,
                'status_id' => $status,
            ];
            SprintTaskHistory::create($sprintTaskHistory);
            $ctcEntries = CtcEnteries::where('tracking_id', $data['tracking_id'])->update(['task_status_id' => $status]);

            $hub = Hub::whereNull('deleted_at')->find($microHubPostalCode->hub_id);

            $response = new FirstMileSortOrderResource($hub,$hubId);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, 'order sort');

    }

}
