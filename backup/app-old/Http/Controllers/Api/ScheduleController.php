<?php

namespace App\Http\Controllers\Api;


use App\Classes\Fcm;
use App\Models\UserDevice;
use App\Classes\RestAPI;
use App\Http\Requests\Api\ScheduleRequest;
use App\Http\Resources\JoeyAcceptedSlotsResource;
use App\Http\Resources\JoeyScheduleDetailResource;
use App\Http\Resources\JoeyScheduleResource;
use App\Http\Resources\JoeyZoneScheduleResource;
use App\Http\Resources\ZoneScheduleResource;
use App\Models\Dispatch;
use App\Models\Notification;
use App\Models\JoeysZoneSchedule;
use App\Models\Sprint;
use App\Models\SprintConfirmation;
use App\Models\SprintSprintHistory;
use App\Models\SprintTaskHistory;
use App\Models\SprintTasks;
use App\Models\MerchantsIds;
use App\Models\Location;
use App\Models\MicroHubPostalCode;
use App\Models\MicroHubOrder;
use App\Http\Resources\ZoneSchedulenewResource;


use App\Repositories\Interfaces\JoeyRouteRepositoryInterface;
use App\Repositories\Interfaces\SprintRepositoryInterface;

use App\Repositories\Interfaces\UserRepositoryInterface;
use App\SlotsPostalCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\StatusMap;
use App\Models\ZoneSchedule;
use App\Repositories\Interfaces\SprintTaskRepositoryInterface;
use Carbon\Carbon;

class ScheduleController extends ApiBaseController
{

    private $userRepository;
    private $joeyrouteRepository;
    private $sprintTaskRepository;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(UserRepositoryInterface $userRepository, SprintRepositoryInterface $sprintRepository, JoeyRouteRepositoryInterface $joeyrouteRepository,
                                SprintTaskRepositoryInterface $sprintTaskRepository)
    {

        $this->userRepository = $userRepository;
        $this->sprintRepository = $sprintRepository;
        $this->joeyrouteRepository = $joeyrouteRepository;
        $this->sprintTaskRepository = $sprintTaskRepository;

    }




    /**
     * Schedules
     */
    public function schedules(ScheduleRequest $request)
    {
        $data = $request->all();

        $start_datataime = $data['start'].' 00:00:00';
        $endDate = $data['end'].' 23:59:59';

        $startDateConversion=convertTimeZone($start_datataime,$data['timezone'],'UTC','Y-m-d H:i:s');
        $endDateConversion=convertTimeZone($endDate,$data['timezone'],'UTC','Y-m-d H:i:s');
         if($data['timezone']=='America/Edmonton' || $data['timezone']=='GMT-6'){
            $startDateConversion = date('Y-m-d H:i:s',strtotime('+1 hour',strtotime($startDateConversion)));
            $endDateConversion = date('Y-m-d H:i:s',strtotime('+1 hour',strtotime($endDateConversion)));
        }

        // dd($startDateConversion.'//'.$endDateConversion);

        DB::beginTransaction();


        try {
            $joey = $this->userRepository->find(auth()->user()->id);


            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }


            $zoneSchedules = ZoneSchedule::where('start_time', '>=', $startDateConversion)
                ->where('end_time', '<=',$endDateConversion)
                ->where('zone_id', '=', $data['zone_id'])
                ->where('vehicle_id',$data['vehicle_id'])
                ->where('is_display',1)
                ->whereNull('deleted_at')
                ->get();

            // $response = ZoneScheduleResource::collection($zoneSchedules);
            $response = ZoneSchedulenewResource::collection($zoneSchedules,$data['timezone']);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'Zone Schedules');


    }






    /**
     * update shift status rejected or accepted
     *
     */
    public function updateStatus(Request $request)
    {
        $data = $request->all();

        $type = true;
        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);


            if ($data['type'] == 'rejected') {
                $ScheduleId = ZoneSchedule::where('id', $data['ScheduleId'])->first();

                $startTime = $ScheduleId->start_time;
                $dateNow = Carbon::now()->format('Y-m-d H:i:s');
                if($ScheduleId->start_time > date('Y-m-d H:i:s')){


	                $startTime = Carbon::parse($startTime);
	                $endTime = Carbon::parse($dateNow);

	                $totalDuration = $endTime->diff($startTime);


	                $hours = $totalDuration->h;

	                $hours = $hours + ($totalDuration->days * 24);

                if ($hours > 24) {
                    $data = [
	                   'joey_id' => $joey->id,
	                   'zone_schedule_id' => $ScheduleId->id,
	               ];


	                    $test= JoeysZoneSchedule::where('joey_id', $joey->id)
	                        ->where('zone_schedule_id', $ScheduleId->id)
	                        ->update(['deleted_at'=>date('Y-m-d H:i:s')]);

	                    $occupancy=$ScheduleId->occupancy - 1;
	                    ZoneSchedule::where('id', $ScheduleId->id)->update(['occupancy'=>$occupancy]);
	                    $response = 'JOEY SHIFT REMOVED';
	                    $type = true;
	                }
                    else
                    {
                        $response = 'You can not remove past shift.';
                        $type = false;
                    }
                }else
                {
                    $response = 'You can not remove past shift.';
                    $type = false;
                }


                //return RestAPI::response($response, false);
            }

            else{


                if(empty($data['ScheduleId'])){
                    return RestAPI::response('Please provide with schedule ID', false);
                }

                $ScheduleId = ZoneSchedule::where('id', $data['ScheduleId'])->first();

                if(empty($ScheduleId)){
                    return RestAPI::response('Record for schedule not found', false);
                }

                if($ScheduleId->occupancy == $ScheduleId->capacity){
                    return RestAPI::response('This shift is fully occupied, you can not accept this shift', false);
                }

                $occupancy=$ScheduleId->occupancy +1;

                $joeyZoneSchedule = JoeysZoneSchedule::where('joey_id', $joey->id)
                    ->whereNull('end_time')
                    ->whereNull('deleted_at')
                    ->pluck('zone_schedule_id');

                $zoneSchedule = ZoneSchedule::whereIn('id', $joeyZoneSchedule)
                    ->whereNull('deleted_at')
                    ->get();

                foreach($zoneSchedule as $schedule){
                    if($schedule->start_time <= $ScheduleId->start_time && $schedule->end_time >= $ScheduleId->end_time){
                        return RestAPI::response('You already have a shift on same time.', false);
                    }
                    if($schedule->start_time < $ScheduleId->end_time && $schedule->end_time > $ScheduleId->end_time){
                        return RestAPI::response('You already have a shift on same time.', false);
                    }
                    if($schedule->end_time > $ScheduleId->start_time && $schedule->end_time < $ScheduleId->end_time){
                        return RestAPI::response('You already have a shift on same time.', false);
                    }
                    if($schedule->start_time > $ScheduleId->start_time && $schedule->end_time == $ScheduleId->end_time){
                        return RestAPI::response('You already have a shift on same time.', false);
                    }
                }

                if (empty($joey)) {
                    return RestAPI::response('Joey  record not found', false);
                }

                if($ScheduleId->start_time >= date('Y-m-d H:i:s')){
                                $data = [
                                    'joey_id' => $joey->id,
                                    'zone_schedule_id' => $ScheduleId->id,
                                ];

                                $recordCheck=JoeysZoneSchedule::where('joey_id',$joey->id)
                                    ->where('zone_schedule_id',$ScheduleId->id)
                                    ->whereNull('deleted_at')
                                    ->first();

                                if(empty($recordCheck)){
                                    JoeysZoneSchedule::insert($data);

                                    ZoneSchedule::where('id', $data['zone_schedule_id'])->update(['occupancy'=>$occupancy]);
                                }else{
                                    return RestAPI::response('Accepted slot cannot be accepted again!', false);
                                }
                                $response = 'Joey shift accepted';
                }
                else{
                    return RestAPI::response('You can not accept this past shift!', false);
                }
            }


            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        // return RestAPI::response($response, $type,new \stdClass());

        return RestAPI::responseforschedule($response, $type, '');


    }




    /**
     *
     *  accept schedule slot
     *
     */


    public function accepted_slots(Request $request)
    {
        $data = $request->validate([
            'date' => 'date_format:Y-m-d',
            'timezone'=>'required'
        ]);

        DB::beginTransaction();
        $start_date  = $data['date']. ' 00:00:00';
        $end_date  = $data['date']. ' 23:59:59';


        $startDateConversion=convertTimeZone($start_date,$data['timezone'],'UTC','Y/m/d H:i:s');
        $endDateConversion=convertTimeZone($end_date,$data['timezone'],'UTC','Y/m/d H:i:s');

         if($data['timezone']=='America/Edmonton' || $data['timezone']=='GMT-6'){
            $startDateConversion = date('Y-m-d H:i:s',strtotime('+1 hour',strtotime($startDateConversion)));
            $endDateConversion = date('Y-m-d H:i:s',strtotime('+1 hour',strtotime($endDateConversion)));
        }

        try {
            $joey = $this->userRepository->find(auth()->user()->id);


            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }

            $slots = JoeysZoneSchedule::join('zone_schedule', 'zone_schedule.id', '=', 'joeys_zone_schedule.zone_schedule_id')
                ->where('zone_schedule.start_time', '>=', $startDateConversion )
                ->where('zone_schedule.end_time', '<=', $endDateConversion )
                ->whereNull('joeys_zone_schedule.deleted_at')
                ->whereNull('zone_schedule.deleted_at')
                ->where('joey_id', $joey->id)->distinct()->get(['joeys_zone_schedule.id','joeys_zone_schedule.joey_id','joeys_zone_schedule.zone_schedule_id',
                    'zone_schedule.zone_id','zone_schedule.start_time','zone_schedule.end_time']);

//            dd($joey->id);

            $response = JoeyAcceptedSlotsResource::collection($slots);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'Accepted Slots');
    }





    /**
     *
     *  next schedule for the joey
     *
     */

    public function next_for_joey(Request $request)
    {

        $data = $request->all();

        DB::beginTransaction();


        try {
            $joey = $this->userRepository->find(auth()->user()->id);


            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }


            $currentTime = Carbon::now()->format('Y-m-d H:i:s');
//            $currentJoeyTimeZone = convertTimeZone(date('Y-m-d H:i:s'), 'UTC', 'America/Toronto', 'Y-m-d H:i:s');

//            dd($currentTime);

            $next_shift = JoeysZoneSchedule::join('zone_schedule', 'zone_schedule.id', '=', 'joeys_zone_schedule.zone_schedule_id')
                ->join('zones', 'zone_schedule.zone_id', '=', 'zones.id')
                ->where('joeys_zone_schedule.joey_id', $joey->id)
                ->whereNull('zone_schedule.deleted_at')
                ->whereNull('joeys_zone_schedule.deleted_at')
                ->whereNull('joeys_zone_schedule.end_time')
                ->where('zone_schedule.end_time', '>=', $currentTime)
                ->orderBy("zone_schedule.start_time")
                ->get(['zones.timezone','joeys_zone_schedule.id', 'joeys_zone_schedule.start_time as joey_start_time', 'joeys_zone_schedule.end_time as joey_end_time',
                    'zone_schedule.start_time as zone_start_time', 'zone_schedule.end_time as zone_end_time','zone_schedule.zone_id as zone_id', 'joeys_zone_schedule.joey_id', 'joeys_zone_schedule.zone_schedule_id']);


            $response = '';
            if (!empty($next_shift)) {
//
                foreach ($next_shift as $shift) {

                    $currentJoeyTimeZone =  convertTimeZone(date('Y-m-d H:i:s'), 'UTC', $shift->timezone, 'Y-m-d H:i:s');
                    if($shift->zone_id == 71){
                        $zoneEndTime= Carbon::parse($shift->zone_end_time)->subHour(1);
                        $shiftEndTime = convertTimeZone($zoneEndTime, 'UTC', $shift->timezone, 'Y-m-d H:i:s');
                    }else{
                        $shiftEndTime = convertTimeZone($shift->zone_end_time, 'UTC', $shift->timezone, 'Y-m-d H:i:s');
                    }


                    $shiftStartTime = convertTimeZone($shift->zone_start_time, 'UTC', $shift->timezone, 'Y-m-d H:i:s');
                    $joeyStartTime = convertTimeZone($shift->joey_start_time, 'UTC', $shift->timezone, 'Y-m-d H:i:s');

                    if($currentJoeyTimeZone > $shiftEndTime){
                        $joeyZoneSchedule = JoeysZoneSchedule::where('id', $shift->id)->where('joey_id', $joey->id)->first();
                        if(!empty($joeyZoneSchedule)){
                            JoeysZoneSchedule::where('joey_id',$joey->id)->whereNotNull('start_time')->where('id', $shift->id)->update(['end_time'=> $shift->zone_end_time]);
                        }
                    }

                    if(!empty($shift->joey_start_time) && $shift->joey_end_time == null){
                        if($response==''){
                            $response = $shift;
                        }
                    }

                    if($shift->zone_end_time > $currentJoeyTimeZone){
                        if($response==''){
                            $response = $shift;
                        }
                    }
                }
            }
            if (!empty($response)){
                $response = new JoeyZoneScheduleResource($response);
            }else
            {
                $response=new \stdClass();;
            }


            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'Joey Next Shift');

    }





    /**
     *
     *  Schedule of the joey
     *
     */
    public function joeySchedules(Request $request)
    {


        $data = $request->validate([
            'start_date' => 'Required|date_format:Y-m-d',
            'end_date' => 'Required|date_format:Y-m-d',
            'timezone'=> 'Required'
        ]);


        $data = $request->all();


        DB::beginTransaction();


        try {
            $joey = $this->userRepository->find(auth()->user()->id);


            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }

            if (empty($data['start_date'] )) {

                return RestAPI::response('startDate required', false);
            }

            if(empty($data['end_date'])){
                return RestAPI::response('EndDate required', false);
            }
            $startdate=$data['start_date'].' 00:00:00';
            $endDate=$data['end_date'].' 23:59:59';

            $startDateConversion=convertTimeZone($startdate,$data['timezone'],'UTC','Y/m/d H:i:s');
            $endDateConversion=convertTimeZone($endDate,$data['timezone'],'UTC','Y/m/d H:i:s');

                $joeySchedules = JoeysZoneSchedule::join('zone_schedule', 'zone_schedule.id', '=', 'joeys_zone_schedule.zone_schedule_id')
                ->where('joeys_zone_schedule.joey_id',$joey->id)
                ->whereNull('zone_schedule.deleted_at')
                // ->where('start_time', '>=', $startdate )
                //->where('end_time', '<=', $endDate )
                ->whereNotNull('joeys_zone_schedule.start_time')->whereNotNull('joeys_zone_schedule.end_time')
                ->whereBetween('joeys_zone_schedule.start_time', [$startDateConversion, $endDateConversion])
                ->orderBy('zone_schedule.start_time', 'ASC')
                ->get();

                 foreach($joeySchedules as $k=>$v){
                    $joeySchedules[$k]['convert_to_timezone']=$data['timezone'];
                }


            $response = JoeyScheduleResource::collection($joeySchedules);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'Joey Schedules');


    }




    /**
     *
     *  Joey schedule details
     *
     */



    public function joeySchedulesDetails(Request $request)
    {


        $data = $request->all();

        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);
            if (empty($joey)) {
                return RestAPI::response('Joey not found', false);
            }
            if (empty($data['schedule_id'])) {
                return RestAPI::response('Provide schedule id', false);
            }



            $joeySchedulesDetail = JoeysZoneSchedule::where('joey_id',$joey->id)
                ->where('zone_schedule_id',$data['schedule_id'])
                ->whereNull('deleted_at')
                ->first();

            if(empty($joeySchedulesDetail->zone_schedule_id)) {
                return RestAPI::response('provide with correct schedule id', false);

            }
            $response = new JoeyScheduleDetailResource($joeySchedulesDetail);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'Joey schedule Details');
    }


    public function jobScheduleShifts(Request $request)
    {
        $current_date = date('Y-m-d');
        $Date_time = date('Y-m-d H:i:s');
        //dd($Date_time);
        $Date_time = strtotime($Date_time);
        $zoneSchedules = ZoneSchedule::where('start_time', 'like', $current_date."%")
            ->whereNull('deleted_at')
            ->get();
        foreach ($zoneSchedules as $zone)
        {
            if($zone->joeys) {
                foreach ($zone->joeys as $joey) {


                        $start_time = strtotime($zone->start_time);
                        $end_time = strtotime($zone->end_time);
                        $zone_minutes = intval(round(($Date_time - $start_time) / 60, 2));

                        if ($zone_minutes >= -5) {
                            $zone_check = DB::table('zone_notifications')->where('zone_id', $zone->id)
                                ->where('joey_id',  $joey->joey_id)->where('type', 'zone-start')->first();
                            if ($zone_check == null) {
                                DB::table('zone_notifications')->insert(['zone_id' => $zone->id,'joey_id'=> $joey->joey_id,'type'=>'zone-start'
                                ,'created_at' => date('Y-m-d H:i:s'),'updated_at' => date('Y-m-d H:i:s')]);
                                $subject = 'Shift Start';
                                $message = 'Your shift has been start with in 5 minutes';
                                $payload = ['notification' => ['title' => $subject, 'body' => $message, 'click_action' => 'shift'],
                                    'data' => ['data_title' => $subject, 'data_body' => $message, 'data_click_action' => 'shift']];
                                $deviceIds = UserDevice::where('user_id', $joey->joey_id)->pluck('device_token');
                                Fcm::sendPush($subject, $message, 'shift', null, $deviceIds);
                                $createNotification[] = [
                                    'user_id' => $joey->joey_id,
                                    'user_type' => 'Joey',
                                    'notification' => $subject,
                                    'notification_type' => 'admin-notification',
                                    'notification_data' => json_encode(["body" => $message]),
                                    'payload' => json_encode($payload),
                                    'is_silent' => 0,
                                    'is_read' => 0,
                                    'created_at' => date('Y-m-d H:i:s')
                                ];
                                Notification::insert($createNotification);
                            }
                        }
                        if ($zone_minutes >= 5 && $joey->start_time == null) {
                            $zone_check = DB::table('zone_notifications')->where('zone_id', $zone->id)
                                ->where('joey_id',  $joey->joey_id)->where('type', 'joey-start')->first();
                            if ($zone_check == null) {
                                DB::table('zone_notifications')->insert(['zone_id' => $zone->id, 'joey_id' => $joey->joey_id, 'type' => 'joey-start'
                                    ,'created_at' => date('Y-m-d H:i:s'),'updated_at' => date('Y-m-d H:i:s')]);
                                $subject = 'Shift Not Start';
                                $message = 'Your shift time has been exceed 5 minutes';
                                $payload = ['notification' => ['title' => $subject, 'body' => $message, 'click_action' => 'shift'],
                                    'data' => ['data_title' => $subject, 'data_body' => $message, 'data_click_action' => 'shift']];
                                $deviceIds = UserDevice::where('user_id', $joey->joey_id)->pluck('device_token');
                                Fcm::sendPush($subject, $message, 'shift', null, $deviceIds);
                                $createNotification[] = [
                                    'user_id' => $joey->joey_id,
                                    'user_type' => 'Joey',
                                    'notification' => $subject,
                                    'notification_type' => 'admin-notification',
                                    'notification_data' => json_encode(["body" => $message]),
                                    'payload' => json_encode($payload),
                                    'is_silent' => 0,
                                    'is_read' => 0,
                                    'created_at' => date('Y-m-d H:i:s')
                                ];
                                Notification::insert($createNotification);
                            }
                        }
                        $joey_minutes = intval(round(($Date_time - $end_time) / 60, 2));
                        if ($joey_minutes >= 5 && $joey->end_time == null) {
                            $zone_check = DB::table('zone_notifications')->where('zone_id', $zone->id)
                                ->where('joey_id',  $joey->joey_id)->where('type', 'joey-end')->first();
                            if ($zone_check == null) {
                                DB::table('zone_notifications')->insert(['zone_id' => $zone->id, 'joey_id' => $joey->joey_id, 'type' => 'joey-end'
                                    ,'created_at' => date('Y-m-d H:i:s'),'updated_at' => date('Y-m-d H:i:s')]);
                                $subject = 'Shift Not End';
                                $message = 'Your shift not end exceed 5 minutes';
                                $payload = ['notification' => ['title' => $subject, 'body' => $message, 'click_action' => 'shift'],
                                    'data' => ['data_title' => $subject, 'data_body' => $message, 'data_click_action' => 'shift']];
                                $deviceIds = UserDevice::where('user_id', $joey->joey_id)->pluck('device_token');
                                Fcm::sendPush($subject, $message, 'shift', null, $deviceIds);
                                $createNotification[] = [
                                    'user_id' => $joey->joey_id,
                                    'user_type' => 'Joey',
                                    'notification' => $subject,
                                    'notification_type' => 'admin-notification',
                                    'notification_data' => json_encode(["body" => $message]),
                                    'payload' => json_encode($payload),
                                    'is_silent' => 0,
                                    'is_read' => 0,
                                    'created_at' => date('Y-m-d H:i:s')
                                ];
                                Notification::insert($createNotification);
                            }
                        }
                    }

            }
        }
        return RestAPI::response(new \stdClass(), true, 'Zone Push');
    }



}
