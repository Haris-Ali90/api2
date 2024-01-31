<?php

namespace App\Http\Controllers\Api;

use App\Models\City;
use App\Models\Sprint;
use App\Models\Vendor;
use App\Classes\RestAPI;
use App\Models\Dispatch;
use App\Models\Location;
use App\Models\SprintZone;
use App\Models\LocationEnc;
use App\Models\SprintTasks;
use App\Models\MerchantsIds;
use Illuminate\Http\Request;
use App\Models\SprintContact;
use App\Models\SprintTaskHistory;
use App\Models\SprintConfirmation;
use Illuminate\Support\Facades\DB;
use App\Models\SprintSprintHistory;
use App\Http\Resources\CityResource;
use App\Http\Resources\StateResource;
use App\Models\MerchantOrderCsvUpload;
use App\Http\Resources\CountryResource;
use App\Http\Resources\LocationResource;
use Illuminate\Support\Facades\Validator;
use App\Models\MerchantOrderCsvUploadDetail;
use App\Http\Controllers\Api\ApiBaseController;
use App\Models\ContactEnc;
use App\Models\BorderlessDashboard;
use App\Repositories\MerchantOrderCsvUploadRepository;

class MerchantCSVUploadController extends ApiBaseController
{


    private $MerchantOrderCsvUploadRepository;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(MerchantOrderCsvUploadRepository $MerchantOrderCsvUploadRepository)
    {
        $this->MerchantOrderCsvUploadRepository = $MerchantOrderCsvUploadRepository;
    }

    /**
     * merchant Csv upload for orders save job id
     *
     */
    public function merchant_orders_csv_save_job(Request $request)
    {

        // converting row data to array
        $raw_request_data = json_decode($request->getContent(),true);

        // checking is valid json
        if (json_last_error() != JSON_ERROR_NONE) {
            return RestAPI::response("Json request is not valid ".json_last_error_msg(), false, json_last_error());
        }
        $response=[];
        // data validation
        $rules = [
            'is_optimize' => 'required',
            // 'job_id' => 'required',

            'merchant_id' => 'required|exists:vendors,id',
            'orders'=> 'required|present|array'
        ];
        $validator = Validator::make($raw_request_data, $rules);
        // checking validation passes ??
        if ($validator->fails()) {
            return RestAPI::response($validator->errors()->all(), false, 'error_exception');
        }
        if($raw_request_data['is_optimize']==1){
            if(!isset($raw_request_data['job_id'])){
                return RestAPI::response("Job Id is required", false, 'Validation Error');
            }
        }
        else{
            if(!isset($raw_request_data['count_joeys'])){
                return RestAPI::response("Count of Joeys is required", false, 'Validation Error');
            }
            $raw_request_data['job_id']='JCUO-'.substr(md5(time()),0,10);
            $response=['job_id'=>$raw_request_data['job_id']];

            $response['OrderSummary']['OrdersUploaded']=count($raw_request_data['orders']);
            $response['OrderSummary']['TotalJoeysRequired']=$raw_request_data['count_joeys'];
            $response['OrderSummary']['FailedLocations']=0;
            $response['CostBreakdown']['DistanceCharge']=0;
            $response['CostBreakdown']['TaskCharges']=0;

        }

        $piuckuptime = date("Y-m-d H:i:s", strtotime($raw_request_data['options']['pickupDate'].' '.$raw_request_data['options']['pickupTime']));

        // creating data
        $Merchant_csv_mian_data = [
            'job_id'=>$raw_request_data['job_id'],
            'vendor_id'=>$raw_request_data['merchant_id'],
            'is_delivery_image'=>$raw_request_data['options']['isDeliveryImage'],
            'is_singnature'=>$raw_request_data['options']['isSingnature'],
            'pickup_date_time'=>$piuckuptime,
        ];

        DB::beginTransaction();

        try
        {
            // saving data
            $created_data =  $this->MerchantOrderCsvUploadRepository->save_merchant_csv_upload($Merchant_csv_mian_data,$raw_request_data['orders']);
            DB::commit();

            $merchantOrderCsvUpload=MerchantOrderCsvUpload::where('job_id',$raw_request_data['job_id'])->first();
            $csvdetails = MerchantOrderCsvUploadDetail::where('merchant_order_csv_upload_id',$merchantOrderCsvUpload->id)->get(['lat','lng']);
            $getTaskDistance = 0;
            $dropoff_charge = 0;

            for($i=0;$i < count($csvdetails);$i++){

                if($i<0){
                    $currentTask = $csvdetails[$i];
                    $lastTask = $csvdetails[$i-1];
                    $getTaskDistance += $this->getDistanceChargeCSV($currentTask,$lastTask,$merchantOrderCsvUpload->vendor_id,$i);
                }

                $dropoff_charge += $this->getDropoffCharge($raw_request_data['merchant_id'],$i);
            }

            $response['CostBreakdown']['TaskCharges'] = $dropoff_charge;
            $response['CostBreakdown']['DistanceCharge'] = $getTaskDistance;
            $response['CostBreakdown']['SubTotal']=0;
            $response['CostBreakdown']['SubTotal']+=$response['CostBreakdown']['TaskCharges'];
            $response['CostBreakdown']['Tax']=round(number_format((float)(13/100), 2, '.', '')*$response['CostBreakdown']['TaskCharges'],2);
            $response['CostBreakdown']['Total']=round($response['CostBreakdown']['SubTotal']+$response['CostBreakdown']['Tax'],2);
            return RestAPI::response($response, true, "CSV record save sussfully ");

        }
        catch (\Exception $e)
        {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }


    }

    public function merchant_orders_csv_job_create(Request $request)
    {
        // converting row data to array
        $raw_request_data = json_decode($request->getContent(),true);
        // checking is valid json
        if (json_last_error() != JSON_ERROR_NONE) {
            return RestAPI::response("Json request is not valid ".json_last_error_msg(), false, json_last_error());
        }

        // data validation
        $rules = [
            'job_id' => 'required',
            'output' => 'required|present|array',
            'output.solution' => 'required|present|array',
            'output.num_unserved' => 'required',
            'output.unserved' => 'nullable',

        ];
        $validator = Validator::make($raw_request_data, $rules);
        // checking validation passes ??
        if ($validator->fails()) {
            return RestAPI::response($validator->errors()->all(), false, 'error_exception');
        }

        $this->MerchantOrderCsvUploadRepository->update_data_by_job_responce($raw_request_data['job_id'],$raw_request_data);
        DB::beginTransaction();
        try{
            // new work
                    $merchantOrder=MerchantOrderCsvUpload::where('job_id',$raw_request_data['job_id'])->first();
                    $sprint_duetime=strtotime($this->UTCconversion($merchantOrder->pickup_date_time));
                    $datetime=time() + (0.5*60*60);
                    if($sprint_duetime<$datetime){$sprint_duetime= $datetime;}
                    // print_r($merchantOrder->MerchantOrderCsvUploadDetailsWithAprrovedStatus);
                    if(count($raw_request_data['output']['solution'])>0){

                        // $dropoff_charge=0;
                        // if($merchantOrder->vendorDetails!=null){
                        //     if($merchantOrder->vendorDetails->vehileCharge!=null){

                        //         $dropoff_task_charge=$merchantOrder->vendorDetails->vehileCharge->where('vehicle_id',3)->where('type','dropoff')
                        //                                             ->where('limit','>=',count($merchantOrder->MerchantOrderCsvUploadDetailsWithAprrovedStatus))
                        //                                             ->sortBy('limit');
                        //         $dropoff_task_charge=$dropoff_task_charge->all();
                        //         $dropoff_task_charge= reset($dropoff_task_charge)??'';



                        //         if($dropoff_task_charge!=null){
                        //             $dropoff_charge=$dropoff_task_charge->price??0;
                        //         }


                        //     }
                        // }
                        // print_r(count($merchantOrder->MerchantOrderCsvUploadDetailsWithAprrovedStatus));die;
                        $dropoff_charge=$this->getDropoffCharge($merchantOrder->vendor_id,count($merchantOrder->MerchantOrderCsvUploadDetailsWithAprrovedStatus));
                        // print_r($dropoff_charge);die;

                        foreach ($raw_request_data['output']['solution'] as $solutionKeys => $solutionValues) {
                            $sprint_id='';
                            $ordinal=1;
                            $count=0;
                            $last_task_id=null;
                            $total_distance=0;
                            $alltasks_id=[];

                                foreach ($solutionValues as $solutionKey => $solutionValue) {

                                    if($solutionKey==0){    //pickup
                                        // create sprint
                                            $sprintData=[
                                                'creator_id'=>$merchantOrder->vendor_id,
                                                'timezone'=>'America/Toronto',
                                                'status_id'=>38,
                                                'creator_type'=>'vendor',
                                                'vehicle_id'=>3
                                            ];
                                            $sprint=Sprint::create($sprintData);
                                            $sprint_id=$sprint->id;
                                            SprintSprintHistory::insert(['sprint__sprints_id'=>$sprint_id,'vehicle_id'=>$sprint->vehicle_id,'distance'=>$sprint['distance'],
                                            'status_id'=>38,'active'=>1,'optimize_route'=>1,'date'=>date('Y-m-d H:i:s'),'created_at'=>date('Y-m-d H:i:s')]);
                                             $sprint_update['distance_charge']=0;
                                             $sprint_update['task_total']=0;


                                        // create sprint
                                        // create task
                                            $contact_id_pickup=$merchantOrder->vendor_id;
                                            $location_id_pickup=null;
                                            if(!empty($merchantOrder->vendorDetails)){
                                                $contact_data_pickup['name']=$this->RemoveSpecialChar($merchantOrder->vendorDetails->name)??'';
                                                $contact_data_pickup['phone']=$this->RemoveSpecialChar($merchantOrder->vendorDetails->phone);
                                                $contact_data_pickup['email']=$this->RemoveSpecialChar($merchantOrder->vendorDetails->email);
                                                $contact_pickup=SprintContact::create($contact_data_pickup);

                                                $this->insertContactEnc($contact_pickup->id,$contact_data_pickup);

                                                $contact_id_pickup=$contact_pickup->id;
                                                $location_id_pickup=$merchantOrder->vendorDetails->location_id;
                                            }
                                            $sprintTaskData=[
                                                'sprint_id'=>$sprint_id,
                                                'ordinal'=>$ordinal,
                                                'type'=>'pickup',
                                                'status_id'=>38,
                                                'contact_id'=>$contact_id_pickup,
                                                'location_id'=>$location_id_pickup,
                                                'active'=>1,
                                                'due_time'=>$sprint_duetime,
                                                'eta_time'=>$sprint_duetime,
                                                'etc_time'=>$sprint_duetime + (0.25*60*60),//adding 15 mins
                                                'charge'=>0

                                            ];
                                            $sprint_task_pickup=SprintTasks::create($sprintTaskData);
                                            $sprint_task_pickup_id=$sprint_task_pickup->id;
                                            $alltasks_id[]=$sprint_task_pickup_id;
                                            SprintTaskHistory::insert(['created_at'=>date('Y-m-d H:i:s'),'date'=>date('Y-m-d H:i:s'),'sprint__tasks_id'=>$sprint_task_pickup_id,'sprint_id'=>$sprint_id,'status_id'=>38,'active'=>1]);
                                            $ordinal++;
                                            $sprint_confirmation_default_data['task_id']=$sprint_task_pickup_id;
                                            $sprint_confirmation_default_data['name']="default";
                                            $sprint_confirmation_default_data['title']="Confirm Pickup";
                                            SprintConfirmation::create($sprint_confirmation_default_data);
                                    }
                                    else{  //dropoff
                                        //    echo $solutionValue['location_id'];die;
                                    $merchantOrderDetails=$merchantOrder->MerchantOrderCsvUploadDetailsWithAprrovedStatus->where('merchant_order_no',$solutionValue['location_id']);
                                    $merchantOrderDetails=$merchantOrderDetails->all();
                                    $merchantOrderDetails= reset($merchantOrderDetails)??'';

                                        if(!empty($merchantOrderDetails)){
                                                $contactData=[
                                                    'name'=> $this->RemoveSpecialChar($merchantOrderDetails->name)??'',
                                                    'email'=>$this->RemoveSpecialChar($merchantOrderDetails->email)??'',
                                                    'phone'=>$this->RemoveSpecialChar($merchantOrderDetails->phone)??'',
                                                ];
                                                $contact=SprintContact::create($contactData);
                                                $contact_id=$contact->id;

                                                $this->insertContactEnc($contact_id,$contactData);

                                                $city=City::where('name',$merchantOrderDetails->city_name)->first();
                                                $locationData=[
                                                    'address'=>$this->RemoveSpecialChar($merchantOrderDetails->address)??'',
                                                    'postal_code'=>$merchantOrderDetails->postal_code??'',
                                                    'latitude'=>str_replace('.','',$merchantOrderDetails->lat)??'',
                                                    'longitude'=>str_replace('.','',$merchantOrderDetails->lng)??'',
                                                    'city_id'=>$city->id??'',
                                                    'state_id'=>$city->state_id??'',
                                                    'country_id'=>$city->country_id??'',
                                                    'buzzer'=>$this->RemoveSpecialChar($merchantOrderDetails->buzzer)??'',
                                                    'suite'=>$this->RemoveSpecialChar($merchantOrderDetails->suite)??'',


                                                ];

                                                $location=Location::create($locationData);
                                                $location_id=$location->id;

                                                $this->insertLocationEnc($location_id,$locationData);

                                                $arrivaltimeArray=explode(':',$solutionValue['arrival_time']);
                                                $finishtimeArray=explode(':',$solutionValue['finish_time']);

                                                $mins=(int)$finishtimeArray[0]-(int) $arrivaltimeArray[0];
                                                $secs=$finishtimeArray[1]- $arrivaltimeArray[1];
                                                $secs=(int)(str_replace('-','',$secs));
                                                $time_difference_to_hub=(int)($mins+($secs/60));

                                                    $sprintTaskData[$count]=[
                                                        'sprint_id'=>$sprint_id,
                                                        'ordinal'=>$ordinal,
                                                        'type'=>'dropoff',
                                                        'status_id'=>38,
                                                        'description'=>$merchantOrderDetails->description??'',
                                                        'contact_id'=>$contact_id,
                                                        'location_id'=>$location_id??'',
                                                        'notify_by'=>$merchantOrderDetails->notification??'',
                                                        'due_time'=>$sprint_duetime,
                                                        'eta_time'=>$sprint_duetime+($time_difference_to_hub * 60),//add time difference between two points
                                                        'etc_time'=>($sprint_duetime+($time_difference_to_hub * 60)) + (0.25*60*60),//adding 15 mins
                                                        'active'=>1,
                                                        'charge'=>$dropoff_charge

                                                    ];
                                                    // print_r($sprintTaskData[$count]);die;

                                                    if($count>0){
                                                        $sprintTaskData[$count]['eta_time']= $sprintTaskData[$count-1]['eta_time']+($time_difference_to_hub * 60);
                                                        $sprintTaskData[$count]['etc_time']= $sprintTaskData[$count-1]['eta_time'] + (0.25*60*60);
                                                    }
                                                    $task= SprintTasks::create($sprintTaskData[$count]);
                                                    // die('1');

                                                    $task_id=$task->id;
                                                    $alltasks_id[]=$task_id;
                                                    SprintTaskHistory::insert(['created_at'=>date('Y-m-d H:i:s'),'date'=>date('Y-m-d H:i:s'),'sprint__tasks_id'=>$task_id,'sprint_id'=>$sprint_id,'status_id'=>38,'active'=>1]);


                                                    $merchantData=[
                                                        'task_id'=>$task_id,
                                                        'merchant_order_num'=>$solutionValue['location_id'],
                                                        'item_count'=>$merchantOrderDetails->item_count,
                                                        'package_count'=>$merchantOrderDetails->package_count,
                                                        'start_time'=>date('H:i',strtotime($merchantOrderDetails->dropoff_start_hour)),
                                                        'end_time'=>date('H:i',strtotime($merchantOrderDetails->dropoff_end_hour)),
                                                    ];

                                                    MerchantsIds::create($merchantData);
                                                    $ordinal++;

                                                    $ordinalConfirmation=0;
                                                    $sprint_confirmation_data['task_id']=$task_id;
                                                    if($merchantOrder->is_delivery_image==1){
                                                        $ordinalConfirmation+=1;
                                                        $sprint_confirmation_data['name']="image";
                                                        $sprint_confirmation_data['title']="Confirm Image";
                                                        $sprint_confirmation_data['input_type']="image/jpeg";
                                                        $sprint_confirmation_data['ordinal']=$ordinalConfirmation;
                                                        SprintConfirmation::create($sprint_confirmation_data);
                                                    }
                                                    if($merchantOrder->is_singnature==1){
                                                        $ordinalConfirmation+=1;
                                                        $sprint_confirmation_data['name']="signature";
                                                        $sprint_confirmation_data['title']="Confirm Signature";
                                                        $sprint_confirmation_data['input_type']="image/jpeg";
                                                        $sprint_confirmation_data['ordinal']=$ordinalConfirmation;
                                                        SprintConfirmation::create($sprint_confirmation_data);
                                                    }else{
                                                        $sprint_confirmation_default_data['task_id']=$task_id;
                                                        $sprint_confirmation_default_data['name']="default";
                                                        $sprint_confirmation_default_data['title']="Confirm Dropoff";
                                                        SprintConfirmation::create($sprint_confirmation_default_data);
                                                    }

                                                    $last_task_id=$task_id;
                                                    $push_at=date("Y-m-d H:i:s", substr(($sprint_duetime), 0, 10));
                                                    $total_distance+=$solutionValue['distance'];
                                                    $count++;
                                                    $sprint_update['distance_charge']+=$this->getDistanceCharge($task);
                                                    $sprint_update['task_total']+=$dropoff_charge;

                                            }


                                    }
                                    // $ordinal++;
                                }

                                if($last_task_id!=null){
                                    $sprint_update['last_task_id']=$last_task_id;
                                    $sprint_update['optimize_route']=1;
                                    $sprint_update['status_id']=61;
                                    $sprint_update['active']=1;
                                    $sprint_update['timezone']='America/Toronto';
                                    $sprint_update['push_at']=$push_at;
                                    $sprint_update['distance']=$total_distance;
                                    $sprint_update['checked_out_at']=date('Y-m-d H:i:s');
                                    $sprint_update['subtotal'] = $sprint_update['distance_charge']+$sprint_update['task_total'];
                                    $sprint_update['tax']=round(number_format((float)(13/100), 2, '.', '')*$sprint_update['subtotal'],2);
                                    $sprint_update['total'] = $sprint_update['subtotal'] + $sprint_update['tax'];
                                    $sprint_update['merchant_charge'] = $sprint_update['total'];
                                    // $sprint->save;

                                    Sprint::where('id',$sprint_id)->update($sprint_update);
                                    SprintSprintHistory::insert(['sprint__sprints_id'=>$sprint_id,'vehicle_id'=>$sprint->vehicle_id,'distance'=>$sprint['distance'],'status_id'=>61,'active'=>1,'optimize_route'=>1,'date'=>date('Y-m-d H:i:s'),'created_at'=>date('Y-m-d H:i:s')]);
                                    SprintTasks::whereIn('id',$alltasks_id)->update(['status_id' => 61]);
                                    foreach ($alltasks_id as $alltask_id) {
                                        SprintTaskHistory::insert(['created_at'=>date("Y-m-d H:i:s"),'date'=>date('Y-m-d H:i:s'),'sprint__tasks_id'=>$alltask_id,'sprint_id'=>$sprint_id,'status_id'=>61,'active'=>1]);

                                    }

                                    $newsprint=Sprint::find($sprint_id);
                                    $this->addToDispatch($newsprint,['sprint_duetime'=>$sprint_duetime,'copy'=>'']);

                                }
                            // die;
                        }
                    }
            // new work
            DB::commit();
            // $newsprint=Sprint::find($sprint_id);
            // print_r($newsprint);die;
            // $this->addToDispatch($newsprint,['sprint_duetime'=>$sprint_duetime,'copy'=>'']);
            return RestAPI::response("Order Created Successfully", true, 'Order Created');
        }
        catch (\Exception $e)
        {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

    }
    public function merchant_orders_csv_job_create_unoptimize(Request $request)
    {
        // converting row data to array
        $raw_request_data = json_decode($request->getContent(),true);
        // checking is valid json
        if (json_last_error() != JSON_ERROR_NONE) {
            return RestAPI::response("Json request is not valid ".json_last_error_msg(), false, json_last_error());
        }

        // data validation
        $rules = [
            'job_id' => 'required',
            // 'output' => 'required|present|array',
            'sprint' => 'required|present|array',
            // 'output.num_unserved' => 'required',
            // 'output.unserved' => 'nullable',

        ];
        $validator = Validator::make($raw_request_data, $rules);
        // checking validation passes ??
        if ($validator->fails()) {
            return RestAPI::response($validator->errors()->all(), false, 'error_exception');
        }

        $this->MerchantOrderCsvUploadRepository->update_data_by_job_response_unoptimize($raw_request_data['job_id'],$raw_request_data);
        // die('1');
        DB::beginTransaction();
        try{
            // new work
                    $merchantOrder=MerchantOrderCsvUpload::where('job_id',$raw_request_data['job_id'])->first();

                    $sprint_duetime=strtotime($this->UTCconversion($merchantOrder->pickup_date_time));

                    $datetime=time() + (0.5*60*60);
                    if($sprint_duetime<$datetime){
                        $sprint_duetime=$datetime;
                    }
                    if(count($raw_request_data['sprint'])>0){

                       // $dropoff_charge=$this->getDropoffCharge($merchantOrder->vendor_id,count($merchantOrder->MerchantOrderCsvUploadDetailsWithAprrovedStatus));

                        foreach ($raw_request_data['sprint'] as $solutionKeys => $solutionValues) {
                            $sprint_id='';
                            $ordinal=1;
                            $count=0;
                            $last_task_id=null;
                            $total_distance=0;
                            $alltasks_id=[];
                            $first_pickup=[];
                            $last_dropoff=[];
                            $dropCount=0;
                            $limit=1;
                                foreach ($solutionValues as $solutionKey => $solutionValue) {
                                    if(isset($raw_request_data['is_ecommerce'])){
                                        $check_exists = BorderlessDashboard::where('tracking_id',$solutionValue['tracking_id'])->pluck('tracking_id')->toArray();
                                        if(count($check_exists) > 0){
                                            continue;
                                        }
                                    }

                                    if($solutionKey==0){    //pickup
                                        // create sprint
                                            $sprintData=[
                                                'creator_id'=>$merchantOrder->vendor_id,
                                                'timezone'=>'America/Toronto',
                                                'status_id'=>38,
                                                'creator_type'=>'vendor',
                                                'vehicle_id'=>3
                                            ];
                                            $sprint=Sprint::create($sprintData);
                                            $sprint_id=$sprint->id;

                                            SprintSprintHistory::insert(['sprint__sprints_id'=>$sprint_id,'vehicle_id'=>$sprint->vehicle_id,'distance'=>$sprint['distance'],
                                            'status_id'=>38,'active'=>1,'optimize_route'=>1,'date'=>date('Y-m-d H:i:s'),'created_at'=>date('Y-m-d H:i:s')]);

                                        // create sprint
                                        // create task
                                        $first_pickup['lng']=$solutionValue['lng'];
                                        $first_pickup['lat']=$solutionValue['lat'];


                                            $contact_id_pickup=$merchantOrder->vendor_id;
                                            $location_id_pickup=null;
                                            if(!empty($merchantOrder->vendorDetails)){
                                                $contact_data_pickup['name']=$this->RemoveSpecialChar($merchantOrder->vendorDetails->name)??'';
                                                $contact_data_pickup['phone']=$this->RemoveSpecialChar($merchantOrder->vendorDetails->phone);
                                                $contact_data_pickup['email']=$this->RemoveSpecialChar($merchantOrder->vendorDetails->email);
                                                $contact_pickup=SprintContact::create($contact_data_pickup);
                                                $contact_id_pickup=$contact_pickup->id;

                                                $this->insertContactEnc($contact_id_pickup,$contact_data_pickup);

                                                $location_id_pickup=$merchantOrder->vendorDetails->location_id??'';
                                                if(!empty($merchantOrder->vendorDetails->location)){
                                                    $first_pickup['lat']=$merchantOrder->vendorDetails->location->latitude??'';
                                                    $first_pickup['lng']=$merchantOrder->vendorDetails->location->longitude??'';
                                                }
                                            }
                                            $sprintTaskData=[
                                                'sprint_id'=>$sprint_id,
                                                'ordinal'=>$ordinal,
                                                'type'=>'pickup',
                                                'status_id'=>38,
                                                'contact_id'=>$contact_id_pickup,
                                                'location_id'=>$location_id_pickup,
                                                'active'=>1,
                                                'due_time'=>$sprint_duetime,
                                                'eta_time'=>$sprint_duetime,
                                                'etc_time'=>$sprint_duetime + (0.25*60*60),//adding 15 mins
                                                'charge'=>0

                                            ];
                                            $sprint_task_pickup=SprintTasks::create($sprintTaskData);
                                            $sprint_task_pickup_id=$sprint_task_pickup->id;
                                            $alltasks_id[]=$sprint_task_pickup_id;
                                            SprintTaskHistory::insert(['created_at'=>date('Y-m-d H:i:s'),'date'=>date('Y-m-d H:i:s'),'sprint__tasks_id'=>$sprint_task_pickup_id,'sprint_id'=>$sprint_id,'status_id'=>38,'active'=>1]);
                                            $ordinal++;
                                            $sprint_confirmation_default_data['task_id']=$sprint_task_pickup_id;
                                            $sprint_confirmation_default_data['name']="default";
                                            $sprint_confirmation_default_data['title']="Confirm Pickup";
                                            SprintConfirmation::create($sprint_confirmation_default_data);
                                            $sprint_update['distance_charge']=0;
                                            $sprint_update['task_total'] = 0;


                                    }

                                    $merchantOrderDetails=$merchantOrder->MerchantOrderCsvUploadDetailsWithAprrovedStatus->where('merchant_order_no',$solutionValue['merchant_order_no']);
                                    $merchantOrderDetails=$merchantOrderDetails->all();
                                    $merchantOrderDetails= reset($merchantOrderDetails)??'';

                                        if(!empty($merchantOrderDetails)){
                                                // if(!is_int($merchantOrderDetails->phone)){
                                                //     $merchantOrderDetails->phone = '';
                                                // }

                                                $contactData=[
                                                    'name'=>$this->RemoveSpecialChar($merchantOrderDetails->name)??'',
                                                    'email'=>$this->RemoveSpecialChar($merchantOrderDetails->email)??'',
                                                    'phone'=>$this->RemoveSpecialChar($merchantOrderDetails->phone)??'',
                                                ];
                                                $contact=SprintContact::create($contactData);
                                                $contact_id=$contact->id;

                                                $this->insertContactEnc($contact_id,$contactData);

                                                $city=City::where('name',$merchantOrderDetails->city_name)->first();
                                                $locationData=[
                                                    'address'=>$this->RemoveSpecialChar($merchantOrderDetails->address)??'',
                                                    'postal_code'=>$merchantOrderDetails->postal_code??'',
                                                    'latitude'=>str_replace('.','',$merchantOrderDetails->lat)??'',
                                                    'longitude'=>str_replace('.','',$merchantOrderDetails->lng)??'',
                                                    'city_id'=>$city->id??'',
                                                    'state_id'=>$city->state_id??'',
                                                    'country_id'=>$city->country_id??'',
                                                    'buzzer'=>$this->RemoveSpecialChar($merchantOrderDetails->buzzer)??'',
                                                    'suite'=>$this->RemoveSpecialChar($merchantOrderDetails->suite)??'',


                                                ];
                                                $location=Location::create($locationData);
                                                $location_id=$location->id;

                                                $this->insertLocationEnc($location_id,$locationData);

                                                $arrivaltimeArray=explode(':',$solutionValue['dropoff_start_hour']);
                                                $finishtimeArray=explode(':',$solutionValue['dropoff_end_hour']);

                                                if(isset($finishtimeArray[1]) || isset($arrivaltimeArray[1])){
                                                    $mins=(int)$finishtimeArray[0]-(int) $arrivaltimeArray[0];
                                                    $secs=$finishtimeArray[1]- $arrivaltimeArray[1];
                                                    $secs=(int)(str_replace('-','',$secs));
                                                    $time_difference_to_hub=(int)($mins+($secs/60));
                                                }
                                                else{
                                                    $time_difference_to_hub = 0;
                                                }

                                                    $sprintTaskData[$count]=[
                                                        'sprint_id'=>$sprint_id,
                                                        'ordinal'=>$ordinal,
                                                        'type'=>'dropoff',
                                                        'status_id'=>38,
                                                        'description'=>$merchantOrderDetails->description??'',
                                                        'contact_id'=>$contact_id,
                                                        'location_id'=>$location_id??'',
                                                        'notify_by'=>$merchantOrderDetails->notification??'',
                                                        'due_time'=>$sprint_duetime,
                                                        'eta_time'=>$sprint_duetime+($time_difference_to_hub * 60),//add time difference between two points
                                                        'etc_time'=>($sprint_duetime+($time_difference_to_hub * 60)) + (0.25*60*60),//adding 15 mins
                                                        'active'=>1
                                                    ];

                                                    $dropoff_charge=$this->getDropoffCharge($merchantOrder->vendor_id,$limit);
                                                    $sprintTaskData[$count]['charge']= $dropoff_charge;
                                                    $limit++;
                                                    if($count>0){
                                                        $sprintTaskData[$count]['eta_time']= $sprintTaskData[$count-1]['eta_time']+($time_difference_to_hub * 60);
                                                        $sprintTaskData[$count]['etc_time']= $sprintTaskData[$count-1]['eta_time'] + (0.25*60*60);
                                                    }
                                                    $task= SprintTasks::create($sprintTaskData[$count]);

                                                    $task_id=$task->id;
                                                    $alltasks_id[]=$task_id;
                                                    SprintTaskHistory::insert(['created_at'=>date('Y-m-d H:i:s'),'date'=>date('Y-m-d H:i:s'),'sprint__tasks_id'=>$task_id,'sprint_id'=>$sprint_id,'status_id'=>38,'active'=>1]);

                                                    if(isset($solutionValue['tracking_id'])){
                                                        $tracking_id  = $solutionValue['tracking_id'];
                                                    }
                                                    else{
                                                        $tracking_id = NULL;
                                                    }

                                                    // dd($merchantOrderDetails->dropoff_start_hour);
                                                    // check if start time  exist or not
                                                    if($merchantOrderDetails->dropoff_start_hour == '00:00:00'){
                                                        $vendor = Vendor::find($merchantOrder->vendor_id);
                                                        $merchantOrderDetails->dropoff_start_hour = empty($vendor->attributes['order_start_time']) ? time() : $vendor->attributes['order_start_time'];
                                                        $merchantOrderDetails->dropoff_start_hour = date('H:i',$merchantOrderDetails->dropoff_start_hour);
                                                    }
                                                    // check if end time  exist or not
                                                    if($merchantOrderDetails->dropoff_end_hour == '00:00:00'){
                                                        $merchantOrderDetails->dropoff_end_hour = date('H:i:s',strtotime("21:00:00") );
                                                    }

                                                    $merchantData=[
                                                        'task_id'=>$task_id,
                                                        'merchant_order_num'=>$solutionValue['merchant_order_no'],
                                                        'tracking_id'=>$tracking_id,
                                                        'item_count'=>$merchantOrderDetails->item_count,
                                                        'package_count'=>$merchantOrderDetails->package_count,
                                                        'address_line2' => $solutionValue['address'],
                                                        'start_time'=>date('H:i',strtotime($merchantOrderDetails->dropoff_start_hour)),
                                                        'end_time'=>date('H:i',strtotime($merchantOrderDetails->dropoff_end_hour)),
                                                    ];
                                                    MerchantsIds::create($merchantData);

                                                    // if ecomerce order updaed on farzan's instructions
                                                    if(isset($raw_request_data['is_ecommerce']) /*$raw_request_data['is_ecommerce'] == true || $raw_request_data['is_ecommerce'] == 1*/ ){
                                                        $user = Vendor::find($merchantOrder->vendor_id);
                                                        $ecom_csv_uploader = [
                                                            'sprint_id' => $sprint_id,
                                                            'task_id' => $task_id,
                                                            'creator_id' => $merchantOrder->vendor_id,
                                                            'tracking_id' => $tracking_id,
                                                            'eta_time' => $sprint_duetime+($time_difference_to_hub * 60),
                                                            'task_status_id' => 38,
                                                            'customer_name' => $this->RemoveSpecialChar($merchantOrderDetails->name),
                                                            // 'task_status_id' => 61,
                                                            'store_name' => $user->name,
                                                            'address_line_1' => $solutionValue['address'],
                                                        ];
                                                        BorderlessDashboard::create($ecom_csv_uploader);
                                                    }
                                                    $ordinal++;

                                                    $ordinalConfirmation=0;
                                                    $sprint_confirmation_data['task_id']=$task_id;
                                                    if($merchantOrder->is_delivery_image==1){
                                                        $ordinalConfirmation+=1;
                                                        $sprint_confirmation_data['name']="image";
                                                        $sprint_confirmation_data['title']="Confirm Image";
                                                        $sprint_confirmation_data['input_type']="image/jpeg";
                                                        $sprint_confirmation_data['ordinal']=$ordinalConfirmation;
                                                        SprintConfirmation::create($sprint_confirmation_data);
                                                    }
                                                    if($merchantOrder->is_singnature==1){
                                                        $ordinalConfirmation+=1;
                                                        $sprint_confirmation_data['name']="signature";
                                                        $sprint_confirmation_data['title']="Confirm Signature";
                                                        $sprint_confirmation_data['input_type']="image/jpeg";
                                                        $sprint_confirmation_data['ordinal']=$ordinalConfirmation;
                                                        SprintConfirmation::create($sprint_confirmation_data);
                                                    }else{
                                                        $sprint_confirmation_default_data['task_id']=$task_id;
                                                        $sprint_confirmation_default_data['name']="default";
                                                        $sprint_confirmation_default_data['title']="Confirm Dropoff";
                                                        SprintConfirmation::create($sprint_confirmation_default_data);
                                                    }


                                                    $last_task_id=$task_id;
                                                    $push_at=date("Y-m-d H:i:s", substr(($sprint_duetime), 0, 10));
                                                    $count++;

                                                    $last_dropoff[$dropCount]['lng']=$merchantOrderDetails['lng'];
                                                    $last_dropoff[$dropCount]['lat']=$merchantOrderDetails['lat'];
                                                    $dropCount++;


                                                    $sprint_update['distance_charge']+=$this->getDistanceCharge($task);
                                                    $sprint_update['task_total']+= $dropoff_charge;
                                        }
                                }

                                if($last_task_id!=null){
                                    $total_distance=$this->getDistanceMapBox($first_pickup,$last_dropoff);

                                    $sprint_update['last_task_id']=$last_task_id;
                                    $sprint_update['optimize_route']=0;
                                    $sprint_update['status_id']=61;
                                    $sprint_update['active']=1;
                                    $sprint_update['timezone']='America/Toronto';
                                    $sprint_update['push_at']=$push_at;
                                    $sprint_update['distance']=$total_distance;
                                    $sprint_update['checked_out_at']=date('Y-m-d H:i:s');
                                    $sprint_update['subtotal'] = $sprint_update['distance_charge']+$sprint_update['task_total'];
                                    $sprint_update['tax']=round(number_format((float)(13/100), 2, '.', '')*$sprint_update['subtotal'],2);
                                    $sprint_update['total'] = $sprint_update['subtotal'] + $sprint_update['tax'];
                                    $sprint_update['merchant_charge'] = $sprint_update['total'];
                                    // $sprint->save;
                                    Sprint::where('id',$sprint_id)->update($sprint_update);
                                    BorderlessDashboard::where('sprint_id',$sprint_id)->update(['task_status_id' => 61]);
                                    SprintSprintHistory::insert(['sprint__sprints_id'=>$sprint_id,'vehicle_id'=>$sprint->vehicle_id,'distance'=>$sprint['distance'],'status_id'=>61,'active'=>1,'optimize_route'=>1,'date'=>date('Y-m-d H:i:s'),'created_at'=>date('Y-m-d H:i:s')]);
                                    SprintTasks::whereIn('id',$alltasks_id)->update(['status_id' => 61]);
                                    foreach ($alltasks_id as $alltask_id) {
                                        SprintTaskHistory::insert(['created_at'=>date("Y-m-d H:i:s"),'date'=>date('Y-m-d H:i:s'),'sprint__tasks_id'=>$alltask_id,'sprint_id'=>$sprint_id,'status_id'=>61,'active'=>1]);

                                    }

                                    $newsprint=Sprint::find($sprint_id);
                                    $this->addToDispatch($newsprint,['sprint_duetime'=>$sprint_duetime,'copy'=>'']);


                                }

                        }
                    }
            // new work
            DB::commit();
            // $newsprint=Sprint::find($sprint_id);
            // print_r($newsprint);die;
            // $this->addToDispatch($newsprint,['sprint_duetime'=>$sprint_duetime,'copy'=>'']);
            return RestAPI::response("Order Created Successfully", true, 'Order Created');
        }
        catch (\Exception $e)
        {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

    }
    public function getDistanceCharge($task)
    {

        $distance_charge=0;


        $currentTaskLat=substr($task->Location->latitude,0,8)/1000000;
        $currentTaskLng=substr($task->Location->longitude,0,9)/1000000;

        $sprintTaskVendor=$task->sprintsSprints->vendor;

        if($sprintTaskVendor!=null){
            if($sprintTaskVendor->vendorPackage!=null){

                if(count($sprintTaskVendor->vendorPackage->vehicleAllowance())>0){

                    $secondLastTask=$task->sprintsSprints->getSecondLastTask;
                    $lastTaskLat=substr($secondLastTask->Location->latitude,0,8)/1000000;
                    $lastTaskLng=substr($secondLastTask->Location->longitude,0,9)/1000000;

                    $distance=$this->getDistanceBetweenPoints($lastTaskLat, $lastTaskLng, $currentTaskLat, $currentTaskLng);


                    $dropoff_distance_charge_collection=$sprintTaskVendor->vendorPackage->vehicleAllowance()
                                                                        ->where('vehicle_id',$task->sprintsSprints->vehicle_id)
                                                                        ->whereIn('type',['custom_distance','distance'])
                                                                        ->where('limit','>=',$task->ordinal)
                                                                        ->sortBy('limit');
                    $dropoff_distance_charge_collection=$dropoff_distance_charge_collection->all();

                    if(count($dropoff_distance_charge_collection)>0){

                        $dropoff_distance_charge_collection= reset($dropoff_distance_charge_collection);

                        if($dropoff_distance_charge_collection->value < $distance){


                            $remaining_distance= round(($distance-$dropoff_distance_charge_collection->value)/1000,2);


                            if($sprintTaskVendor->vendorPackage->vehicleCharge()!=null){

                                $distance_vehicle_charge=$sprintTaskVendor->vendorPackage->vehicleCharge()
                                                                    ->where('vehicle_id',$task->sprintsSprints->vehicle_id)
                                                                    ->whereIn('type',['custom_distance','distance'])
                                                                    ->where('ordinal','>=',$task->ordinal)
                                                                    ->sortBy('limit');

                                $distance_vehicle_charge=$distance_vehicle_charge->all();

                                if(count($distance_vehicle_charge)>0){
                                    $distance_vehicle_charge= reset($distance_vehicle_charge);
                                    $distance_charge=round($distance_vehicle_charge->price * $remaining_distance);
                                }


                            }

                        }
                    }



                }
            }
        }
        return $distance_charge;
    }

    public function getDistanceChargeCSV($currentTask,$lastTask,$vendorId,$ordinal)
    {

        $distance_charge=0;

        $currentTaskLat= $currentTask->lat;
        $currentTaskLng= $currentTask->lng;

        $vendor=Vendor::where('id',$vendorId)->first();
        if($vendor!=null){
            if($vendor->vendorPackage!=null){

                if(count($vendor->vendorPackage->vehicleAllowance())>0){

                    $lastTaskLat= $lastTask->lat;
                    $lastTaskLng= $lastTask->lng;

                    $distance=$this->getDistanceBetweenPoints($lastTaskLat, $lastTaskLng, $currentTaskLat, $currentTaskLng);

                    $dropoff_distance_charge_collection=$vendor->vendorPackage->vehicleAllowance()
                                                                        ->where('vehicle_id',3)
                                                                        ->whereIn('type',['custom_distance','distance'])
                                                                        ->where('limit','>=',$ordinal)
                                                                        ->sortBy('limit');
                    $dropoff_distance_charge_collection=$dropoff_distance_charge_collection->all();


                    if(count($dropoff_distance_charge_collection)>0){

                        $dropoff_distance_charge_collection= reset($dropoff_distance_charge_collection);

                        if($dropoff_distance_charge_collection->value < $distance){

                            $remaining_distance= round(($distance-$dropoff_distance_charge_collection->value)/1000,2);

                            if($vendor->vendorPackage->vehicleCharge()!=null){

                                $distance_vehicle_charge=$vendor->vendorPackage->vehicleCharge()
                                                                    ->where('vehicle_id',3)
                                                                    ->whereIn('type',['custom_distance','distance'])
                                                                    ->where('ordinal','>=',$ordinal)
                                                                    ->sortBy('limit');

                                $distance_vehicle_charge=$distance_vehicle_charge->all();

                                if(count($distance_vehicle_charge)>0){
                                    $distance_vehicle_charge= reset($distance_vehicle_charge);
                                    $distance_charge=round($distance_vehicle_charge->price * $remaining_distance);
                                }

                            }

                        }
                    }



                }
            }
        }
        return $distance_charge;
    }

    function getDistanceBetweenPoints($lat1, $lon1, $lat2, $lon2)
    {

        $token='pk.eyJ1Ijoiam9leWNvIiwiYSI6ImNpbG9vMGsydzA4aml1Y2tucjJqcDQ2MDcifQ.gyd_3OOVqdByGDKjBO7lyA';

        try{
            $response = file_get_contents('https://api.mapbox.com/directions/v5/mapbox/driving/'.$lon2.','.$lat2.';'.$lon1.','.$lat1.'?access_token='.$token);
            $response=json_decode($response,true);

            if(isset($response['routes'][0]['distance'])){
                return $response['routes'][0]['distance'];
            }else{
                $theta = $lon1 - $lon2;
                $miles = (sin(deg2rad($lat1)) * sin(deg2rad($lat2))) + (cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta)));
                $miles = acos($miles);
                $miles = rad2deg($miles);
                $miles = $miles * 60 * 1.1515;
                $feet = $miles * 5280;
                $yards = $feet / 3;
                $kilometers = $miles * 1.609344;
                $meters = $kilometers * 1000;
                return $meters;
            }
        }
        catch(\Exception $e){
            return 0;
        }





    }
    public function getDistanceMapBox($first_pickup=[],$last_dropoff=[])
    {

        $first_pickup['lat']=$first_pickup['lat']/1000000;
        $first_pickup['lng']=$first_pickup['lng']/1000000;

        // if(count($last_dropoff)>1){
            // print_r($last_dropoff);die;

        // }
        $dropoff_string='';
        foreach ($last_dropoff as $key => $value) {
            $dropoff_string.=$value['lng'].',';
            $dropoff_string.=$value['lat'].';';
        }
        $dropoff_string=substr_replace($dropoff_string, "", -1);
        // print_r($dropoff_string);die;

        try{
            $token='pk.eyJ1Ijoiam9leWNvIiwiYSI6ImNpbG9vMGsydzA4aml1Y2tucjJqcDQ2MDcifQ.gyd_3OOVqdByGDKjBO7lyA';
            $response = file_get_contents('https://api.mapbox.com/directions/v5/mapbox/driving/'.$first_pickup["lng"].','.$first_pickup["lat"].';'.$dropoff_string.'?access_token='.$token);

            $response=json_decode($response,true);
            // print_r($response);die;
            if(isset($response['routes'][0]['distance'])){
                return $response['routes'][0]['distance'];
            }else{
                return 0;
            }
        }
        catch(\Exception $e){
            return 0;
        }

    }

    public function merchant_orders_csv_job_create_confirmation(Request $request)
    {
            // converting row data to array
            $raw_request_data = json_decode($request->getContent(),true);
            // checking is valid json
            if (json_last_error() != JSON_ERROR_NONE) {
                return RestAPI::response("Json request is not valid ".json_last_error_msg(), false, json_last_error());
            }


            $rules = [
                'job_id' => 'required',
                'output' => 'required|present|array',
                'output.solution' => 'required|present|array',
                'output.num_unserved' => 'required',
                'output.unserved' => 'nullable',

            ];
            $validator = Validator::make($raw_request_data, $rules);
            // checking validation passes ??
            if ($validator->fails()) {
                return RestAPI::response($validator->errors()->all(), false, 'error_exception');
            }


            $response['OrderSummary']['TotalJoeysRequired']=count($raw_request_data['output']['solution']);
            $response['OrderSummary']['FailedLocations']=$raw_request_data['output']['num_unserved'];
            $response['CostBreakdown']['DistanceCharge']=0;
            $response['CostBreakdown']['TaskCharges']=0;
            $getTaskDistance=0;

            $merchantOrderCsvUpload=MerchantOrderCsvUpload::where('job_id',$raw_request_data['job_id'])->first();

            $csvdetails = MerchantOrderCsvUploadDetail::where('merchant_order_csv_upload_id',$merchantOrderCsvUpload->id)->get(['lat','lng']);

            for($i=1;$i < count($csvdetails);$i++){

                $currentTask = $csvdetails[$i];
                $lastTask = $csvdetails[$i-1];
                $getTaskDistance += $this->getDistanceChargeCSV($currentTask,$lastTask,$merchantOrderCsvUpload->vendor_id,$i);
            }


            foreach ($raw_request_data['output']['solution'] as $sprint_key => $sprint_value) {
                $response['CostBreakdown']['TaskCharges']+=count($sprint_value);
            }

            $response['OrderSummary']['OrdersUploaded']=$response['CostBreakdown']['TaskCharges']-count($raw_request_data['output']['solution']);

            $dropoff_charge=$this->getDropoffCharge($merchantOrderCsvUpload->vendor_id,($response['CostBreakdown']['TaskCharges']-count($raw_request_data['output']['solution'])));

            $response['CostBreakdown']['TaskCharges']=  $response['OrderSummary']['OrdersUploaded']*$dropoff_charge;
            $response['CostBreakdown']['DistanceCharge'] = $getTaskDistance;

            // ((13/100)*amount) to get 13% of a number
            $response['CostBreakdown']['SubTotal']=0;
            $response['CostBreakdown']['SubTotal']+=$response['CostBreakdown']['TaskCharges']+$response['CostBreakdown']['DistanceCharge'];
            $response['CostBreakdown']['Tax']=round(number_format((float)(13/100), 2, '.', '')*$response['CostBreakdown']['SubTotal'],2);
            $response['CostBreakdown']['Total']=round($response['CostBreakdown']['SubTotal']+$response['CostBreakdown']['Tax'],2);

        return RestAPI::response($response, true, 'Order Create Confirmation');


    }

    public function addToDispatch($sprint,$data=[])
    {
        // $dispatch=Dispatch::where('sprint_id',$sprint->id)->get();
        // if(count($dispatch)==0){
            $dispatchData['order_id']=$sprint->id;
            $dispatchData['num']='CR-'.$sprint->id;
            $dispatchData['creator_id']=$sprint->creator_id;
            $dispatchData['sprint_id']=$sprint->id;
            $dispatchData['status']=$sprint->status_id;
            $dispatchData['distance']=($sprint->distance==null)?0:$sprint->distance;
            $dispatchData['active']=$sprint->active;
            $dispatchData['type']="custom-run";
            $dispatchData['vehicle_id']=$sprint->vehicle_id;
            $dispatchData['vehicle_name']=($sprint->vehicle!=null)?$sprint->vehicle->name:'';
            $dispatchData['pickup_location_id']=$sprint->sprintFirstPickupTask->location_id;
            $dispatchData['pickup_contact_name']=$sprint->sprintFirstPickupTask->sprintContact->name;
            $dispatchData['pickup_address']=$sprint->sprintFirstPickupTask->Location->address??'';
            $dispatchData['pickup_contact_phone']=$sprint->sprintFirstPickupTask->sprintContact->phone;
            $dispatchData['pickup_eta']=$sprint->sprintFirstPickupTask->eta_time;
            $dispatchData['pickup_etc']=$sprint->sprintFirstPickupTask->etc_time;
            $dispatchData['dropoff_contact_phone']=$sprint->sprintFirstDropoffTask->sprintContact->phone;
            $dispatchData['dropoff_location_id']=$sprint->sprintFirstDropoffTask->location_id;
            $dispatchData['dropoff_address']=$sprint->sprintFirstDropoffTask->Location->address??'';
            $dispatchData['dropoff_eta']=$sprint->sprintFirstDropoffTask->eta_time;
            $dispatchData['dropoff_etc']=$sprint->sprintFirstDropoffTask->etc_time;
            $dispatchData['date']=$data['sprint_duetime'];
            $dispatchData['has_notes']=0;
            $dispatchData['sprint_duration']=0;
            $dispatchData['zone_id']='';
            $dispatchData['zone_name']='';
            // $dispatchData['status_copy']="JCO_ORDER_SCHEDULED";
            $dispatchData['status_copy']="Scheduled order";



            $ts1 = strtotime($sprint->sprintFirstPickupTask->etc_time);
            $ts2 = strtotime($sprint->sprintFirstDropoffTask->etc_time);
            $seconds_diff = $ts2 - $ts1;
            $time = ($seconds_diff/3600);//in seconds


            $dispatchData['sprint_duration']=$time/60;//in minutes

            $zoneVendorRelationship=$sprint->vendor->zoneVendorRelationship;


            if($zoneVendorRelationship!=null){
                $zone=$zoneVendorRelationship->zones;
                if($zone!=null) {

                    $dispatchData['zone_name']=$zone->name;
                    $dispatchData['zone_id']=$zone->id;

                }
            }
            SprintZone::create(['sprint_id'=>$sprint->id,'zone_id'=>$dispatchData['zone_id']]);
            // print_r($dispatchData);die;
            Dispatch::insert($dispatchData);
        // }
    }
    public function getDropoffCharge($vendorId,$limit)
    {
        $dropoff_charge=0;
        $vendor=Vendor::where('id',$vendorId)->first();
        if($vendor!=null){
            if($vendor->vendorPackage->vehicleCharge()!=null){
                $dropoff_task_charge=$vendor->vendorPackage->vehicleCharge()->where('vehicle_id',3)->whereIn('type',['dropoff','custom_dropoff'])
                                                    ->where('limit','>=',$limit)
                                                    ->sortBy('limit');

                if(count($dropoff_task_charge)==0){
                    $dropoff_task_charge=$vendor->vendorPackage->vehicleCharge()->where('vehicle_id',3)->where('type','custom_dropoff')
                    ->where('limit','>=',$limit)
                    ->sortBy('limit');
                }

                $dropoff_task_charge=$dropoff_task_charge->all();
                $dropoff_task_charge= reset($dropoff_task_charge)??'';

                if($dropoff_task_charge!=null){
                    $dropoff_charge=$dropoff_task_charge->price??0;
                }


            }
        }

        return $dropoff_charge;
    }

    public function getVendorDetails($id)
    {
        $id= base64_decode($id);
        // echo $id;die;
        $vendor=Vendor::where('id',$id)->first();
        if(empty($vendor)){
            return RestAPI::response("Vendor not found.", false, 'Invalid Vendor Id.');
        }
        $location=[];
        if($vendor->location!=null){
            $vendorLocation=$vendor->location;
            $location = [
                'address' => $vendorLocation->address,
                'postal_code'=> $vendorLocation->postal_code,
                'latitude' => $vendorLocation->latitude/1000000,
                'longitude' => $vendorLocation->longitude/1000000,
                'city' => [
                    'id' => $vendorLocation->City->id??'',
                    'name'=> $vendorLocation->City->name??'',
                ],
                'state'=>[
                    'id' => $vendorLocation->State->id??'',
                    'name'=> $vendorLocation->State->name??'',
                ],
                'country' => [
                    'id' => $vendorLocation->Country->id??'',
                    'name'=> $vendorLocation->Country->name??'',
                    ]
            ];
        }

        $response['vendor_id']=$id;
        $response['name']=$vendor->name??'';
        $response['email']=$vendor->email??'';
        $response['phone']=$vendor->phone??'';
        $response['location']=$location;


        return RestAPI::response($response, true, 'Vendor Details');

    }

    public function testing()
    {

        //$key ='CJg8AnuMuKRhb3UFnb7m6W0G8c2Gy8BVCet4jrU0F5A8YDG628A7Q1AtKs0I6j36mghPwAh8g65TIFhj1t3q3B3v98ENJ9xH9868anD1NIqI318M007ybCE2z6vAFU2r';
        //$iv ='WToylQGWw4xWoM8BSbllf6m7JSkUbD8u8AAP3uDGAkvlt3Cmnk6QiKtBGDOkIENF7xK33PneMtLHCFyIwMPbHoh2qau11qo91hR9Ts663w3jeuZB62CxQolCGRxo38Dt';
        $key = 'c9e92bb1ffd642abc4ceef9f4c6b1b3aaae8f5291e4ac127d58f4ae29272d79d903dfdb7c7eb6e487b979001c1658bb0a3e5c09a94d6ae90f7242c1a4cac60663f9cbc36ba4fe4b33e735fb6a23184d32be5cfd9aa5744f68af48cbbce805328bab49c99b708e44598a4efe765d75d7e48370ad1cb8f916e239cbb8ddfdfe3fe';
        $iv ='f13c9f69097a462be81995330c7c68f754f0c6026720c16ad2c1f5f316452ee000ce71d64ed065145afdd99b43c0d632b1703fc6a6754284f5d19b82dc3697d664dc9f66147f374d46c94cf23a78f14f0c6823d1cbaa19c157b4cb81e106b79b11593dcddf675951bc07f54528fc8c03cf66e9c437595d1cac658a737ab1183f';


        //        $data = DB::select("SELECT id,
        //                   AES_DECRYPT(address, ".$key.", ".$iv.") AS address,
        //                   city_id,
        //                   state_id,
        //                   country_id,
        //                   AES_DECRYPT(postal_code, ".$key.", ".$iv.") AS postal_code,
        //                   AES_DECRYPT(buzzer, ".$key.", ".$iv.") AS buzzer,
        //                   AES_DECRYPT(suite, ".$key.", ".$iv.") AS suite,
        //                   AES_DECRYPT(latitude, ".$key.", ".$iv.") AS latitude,
        //                   AES_DECRYPT(longitude, ".$key.", ".$iv.") AS longitude,
        //                   AES_DECRYPT(type, ".$key.", ".$iv.") AS type,
        //                   created_at,
        //                   updated_at,
        //                   deleted_at
        //                   FROM locations_enc");

        //        $query = "
        //            SELECT id,
        //                   AES_DECRYPT(address, ?, ?) AS address,
        //                   city_id,
        //                   state_id,
        //                   country_id,
        //                   AES_DECRYPT(postal_code, ?, ?) AS postal_code,
        //                   AES_DECRYPT(buzzer, ?, ?) AS buzzer,
        //                   AES_DECRYPT(suite, ?, ?) AS suite,
        //                   AES_DECRYPT(latitude, ?, ?) AS latitude,
        //                   AES_DECRYPT(longitude, ?, ?) AS longitude,
        //                   AES_DECRYPT(type, ?, ?) AS type,
        //                   created_at,
        //                   updated_at,
        //                   deleted_at
        //            FROM locations_enc
        //            WHERE id in (8,8,10)
        //
        //        ";
        //
        //        $bindings = [
        //            $key, $iv,
        //            $key, $iv,
        //            $key, $iv,
        //            $key, $iv,
        //            $key, $iv,
        //            $key, $iv,
        //            $key, $iv,
        //
        //        ];

        //
        //            $query = 'INSERT INTO  locations_enc
        //                    (id, address, city_id, state_id, country_id, postal_code,
        //                     buzzer, suite, latitude, longitude, type,
        //                     created_at, updated_at, deleted_at)
        //                    VALUES (
        //                    NULL,
        //                    AES_ENCRYPT(?, ?, ?),
        //                    ?,
        //                    ?,
        //                    ?,
        //                    AES_ENCRYPT(?, ?, ?),
        //                    AES_ENCRYPT(?, ?, ?),
        //                    AES_ENCRYPT(?, ?, ?),
        //                    AES_ENCRYPT(?, ?, ?),
        //                    AES_ENCRYPT(?, ?, ?),
        //                    AES_ENCRYPT(?, ?, ?),
        //                    ?,
        //                    ?,
        //                    NULL
        //                );
        //            ';


        $test = LocationEnc::create([
        "address"=> 'testing address update qwe',
        "city_id" =>1,
        "state_id"=>1,
        "country_id"=>1,
        "postal_code"=>"MLS 115",
        "buzzer"=>"",
        "suite"=>"",
        "latitude"=>"72.12345",
        "longitude"=>"-123.12345",
        "type"=>'',
        ]);
        //$data = LocationEnc::whereIn('id',[8,9,10])->firstDecrypted();
        //$data = LocationEnc::where('id',1723)->firstDecrypted();
        dd($test);

        $data = LocationEnc::find(1723);
        $data->address = 'testing address update';
        $data->city_id =1;
        $data->state_id=1;
        $data->country_id=1;
        $data->postal_code="MLS 115";
        $data->buzzer="";
        $data->suite="";
        $data->latitude="72.12345";
        $data->longitude="-123.12345";
        $data->type='';

        $data->save();



        dd($data);
    }
    public function insertContactEnc($id,$data=[])
    {
        $contact_enc = new ContactEnc;
        $contact_enc->id = $id;
        $contact_enc->name = $data['name']??'';
        $contact_enc->phone = $data['phone']??'';
        $contact_enc->email = $data['email']??'';
        $contact_enc->created_at = date('Y-m-d H:i:s');
        $contact_enc->save();
    }
    public function insertLocationEnc($id,$data)
    {
        $location_enc = new LocationEnc;
        $location_enc->id = $id;
        $location_enc->address = $data['address']??'';
        $location_enc->postal_code = $data['postal_code']??'';
        $location_enc->latitude = $data['latitude']??'';
        $location_enc->longitude = $data['longitude']??'';
        $location_enc->city_id = $data['city_id']??'';
        $location_enc->state_id = $data['state_id']??'';
        $location_enc->country_id = $data['country_id']??'';
        $location_enc->buzzer = $data['buzzer']??'';
        $location_enc->suite = $data['suite']??'';
        $location_enc->created_at = date('Y-m-d H:i:s');
        $location_enc->type = 'dropoff';
        $location_enc->save();
    }

    private function RemoveSpecialChar($str) {

        // Using str_replace() function
        // to replace the word
        $res = str_replace( array( '\'', '"',
        ',' , ';', '<', '>' ), ' ', $str);

        // Returning the result
        return $res;
    }

    private function UTCconversion($datetime){
        $given = new \DateTime($datetime, new \DateTimeZone("America/Toronto"));
        $given->setTimezone(new \DateTimeZone("UTC"));
        $output = $given->format("Y-m-d H:i:s");
        return $output;
    }
}
