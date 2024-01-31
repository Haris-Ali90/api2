<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\AssignDriverRequest;
use App\Http\Requests\Api\CancelOrderRequest;
use App\Http\Requests\Api\RetrieveJoeyLocationRequest;
use App\Http\Requests\Api\UpdateEtaEtcRequest;
use App\Http\Requests\Api\UpdateOrderRequest;
use App\Models\Dispatch;
use App\Models\Joey;
use App\Models\JoeyLocations;
use App\Models\JoeyStorePickup;
use App\Models\JoeysZoneSchedule;
use App\Models\SprintSprintHistory;
use App\Models\SprintZone;
use App\Models\SprintZoneSchedule;
use App\Models\StatusCode;
use App\Models\StatusMap;
use App\Models\Vehicle;
use App\Models\ZoneSchedule;
use App\Models\ZoneVendorRelationship;
use stdClass;
use Validator;
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
use App\Http\Resources\CreateOrderResource;
use App\Http\Resources\CreateOrderMultipleResource;
use App\Http\Requests\Api\CreateOrderCTCRequest;
use App\Http\Requests\Api\CreateOrderOtherRequest;
use App\Http\Requests\Api\CreateOrderContactRequest;
use App\Http\Requests\Api\CreateOrderLoblawsRequest;
use App\Http\Requests\Api\CreateOrderPaymentRequest;
use App\Http\Requests\Api\CreateOrderWalmartRequest;
use App\Http\Requests\Api\CreateOrderLocationRequest;

class ManifestPickupController extends Controller
{

    public function manifestPickup(Request $request)
    {
        $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
        ]);

        $date = date('Y-m-d');

        DB::beginTransaction();

        try {

            $orders = Sprint::where('creator_id', $request->vendor_id)
                ->whereDate('created_at', 'LIKE', $date . '%')
                ->whereIn('status_id', [61, 24])
                ->get();

            if ($orders->count() == 0) {
                return RestAPI::response(new stdClass(), true, 'No Order Found');
            }

            foreach($orders as $order) {

                $sprint = Sprint::find($order->id);

                $sprintTask = SprintTasks::where('sprint_id', $sprint->id)->whereNull('deleted_at')->update(['status_id' => 125]);

                CtcEnteries::where('sprint_id', $sprint->id)->whereNull('deleted_at')->update(['task_status_id' => 125]);
                BoradlessDashboard::where('sprint_id', $sprint->id)->whereNull('deleted_at')->update(['task_status_id' => 125]);
                $spTask = SprintTasks::whereNull('deleted_at')->where('sprint_id', $sprint->id)->where('type', 'dropoff')->get();

                foreach ($spTask as $task) {
                    $sprintTaskHistory = [
                        'sprint__tasks_id' => $task->id,
                        'sprint_id' => $task->sprint_id,
                        'status_id' => 125,
                    ];
                    SprintTaskHistory::create($sprintTaskHistory);

                    $storePickup = [
                        'joey_id' => auth()->user()->id,
                        'tracking_id' => $task->merchantIds->tracking_id,
                        'sprint_id' => $task->sprint_id,
                        'task_id' => $task->id,
                        'status_id' => 125
                    ];
                    JoeyStorePickup::create($storePickup);

                }
                $sprints = Sprint::where('id', $sprint->id)->update(['status_id' => 125]);
            }
            DB::commit();
        }catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response(new stdClass(), true, 'orders pick successfully');

    }
}
