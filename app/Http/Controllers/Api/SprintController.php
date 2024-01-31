<?php

namespace App\Http\Controllers\Api;

use App\Classes\RestAPI;
use App\Models\Dispatch;
use App\Models\ExclusiveOrderJoeys;
use App\Models\Sprint;
use App\Models\SprintSprintHistory;
use App\Models\SprintTaskHistory;
use App\Models\SprintTasks;
use App\Models\StatusMap;

use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class SprintController extends ApiBaseController
{

    private $userRepository;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Accept order
     *
     */
    public function accept(Request $request)
    {

        $data = $request->all();

        DB::beginTransaction();
        try {


            $joey = $this->userRepository->find(auth()->user()->id);


            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }
             $status = StatusMap::getDescription(32);
             Sprint::where('id',$data['sprint'])->update(['joey_id'=>$joey->id,'active'=>1,'status_id'=>32]);
             Dispatch::where('sprint_id',$data['sprint'])->update(['joey_id'=>$joey->id,'status'=>32,'status_copy'=>$status]);
             SprintTasks::where('sprint_id',$data['sprint'])->update(['status_id'=>32]);



             $sprintTask=SprintTasks::where('sprint_id',$data['sprint'])->first();
            $taskHistoryRecord = [
                'sprint__tasks_id' =>$sprintTask->id,
                'sprint_id' =>$data['sprint'],
                'status_id' => 32,
                'active' => 1,
                'date' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s')
            ];

            $sprintHistoryRecord[] = [
                'sprint__sprints_id' => $data['sprint'],
                'joey_id'=>$joey->id,
                'status_id' => 32,
                'active' => 1,
                'date' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s')

            ];

            SprintTaskHistory::insert( $taskHistoryRecord );
            SprintSprintHistory::insert($sprintHistoryRecord);

            ExclusiveOrderJoeys::where('order_id',$data['sprint'])->update(['deleted_at'=>date('Y-m-d H:i:s')]);


               DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response(new \stdClass(), true, 'Order accepted by Joey');

    }
}
