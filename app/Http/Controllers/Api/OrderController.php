<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\AssignDriverRequest;
use App\Http\Requests\Api\CancelOrderRequest;
use App\Http\Requests\Api\RetrieveJoeyLocationRequest;
use App\Http\Requests\Api\UpdateEtaEtcRequest;
use App\Http\Requests\Api\UpdateOrderRequest;
use App\Http\Resources\ChatMessageResource;
use App\Http\Resources\ManagerTrackingDetailsResource;
use App\Http\Resources\TrackingDetailsResource;
use App\Http\Resources\SprintTaskResource;
use App\Models\BoradlessDashboard;
use App\Models\BorderlessDashboard;
use App\Models\Dispatch;
use App\Http\Traits\BasicModelFunctions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\Joey;
use App\Models\JoeycoUsers;
use App\Models\JoeyLocations;
use App\Models\JoeysZoneSchedule;
use App\Models\SprintSprintHistory;
use App\Models\SprintZone;
use App\Models\SprintZoneSchedule;
use App\Models\StatusCode;
use App\Models\StatusMap;
use App\Models\Thread;
use App\Models\Vehicle;
use App\Models\ZoneSchedule;
use App\Models\ZoneVendorRelationship;
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
use App\Models\SprintTaskHistory;
// use App\Models\SprintSprintHistory;
use App\Models\MerchantsIds;
// use App\Http\Resources\CreateOrderResource;
use Illuminate\Http\Request;
use App\Http\Resources\AllOrderStatusResource;
use App\Models\SprintContact;
use App\Models\SprintConfirmation;
use App\Http\Controllers\Controller;
use App\Http\Resources\CreateOrderResource;
use App\Http\Requests\Api\CreateOrderCTCRequest;
use App\Http\Requests\Api\CreateOrderOtherRequest;
use App\Http\Requests\Api\CreateOrderContactRequest;
use App\Http\Requests\Api\CreateOrderLoblawsRequest;
use App\Http\Requests\Api\CreateOrderPaymentRequest;
use App\Http\Requests\Api\CreateOrderWalmartRequest;
use App\Http\Requests\Api\CreateOrderLocationRequest;

class OrderController extends Controller
{

    // public function createOrderLoblaws(CreateOrderLoblawsRequest $request)

    public function createlocalOrder(Request $request)
    {
        $data_input = $request->all();
        $validation_data=$data_input;
        // checking is valid json
        if (json_last_error() != JSON_ERROR_NONE) {
            return RestAPI::response("Json request is not valid / ".json_last_error_msg(), false, json_last_error());
        }

        $location_rules =new CreateOrderLocationRequest;
        $location_validator = Validator::make($validation_data['location'], $location_rules->rules());

        if (!$location_validator->passes()) {
            return RestAPI::response($location_validator->errors()->all(), false, 'Validation Error');
        }
        /**
         *Sprint
         */

        $sprint_rules_loblaws=new CreateOrderLoblawsRequest;
        $sprint_validator = Validator::make($validation_data['sprint'], $sprint_rules_loblaws->rules());

        if (!$sprint_validator->passes()) {
            $sprintValidationErrors=$sprint_validator->errors()->all();
            foreach($sprintValidationErrors as $key=>$value){
                // if(in_array("The end time must be a date after start time.", $sprint_validator->errors()->all())) {
                if($value=="The end time must be a date after start time.") {
                    $sprintValidationErrors[$key]='End time should be greater than start time.';
                }
                if (strpos($value, 'The due time must be greater than or equal') !== false) {
                    $sprintValidationErrors[$key]='Due time must be half an hour from now!.';
                }
            }
            return RestAPI::response($sprintValidationErrors, false, 'Validation Error');
        }
        /**
         *Contact
         */

        $contact_rules = new CreateOrderContactRequest;
        $contact_validator = Validator::make($validation_data['contact'], $contact_rules->rules());

        if (!$contact_validator->passes()) {
            return RestAPI::response($contact_validator->errors()->all(), false, 'Validation Error');
        }
        /**
         *Payment
         */
        if(isset($validation_data['payment'])){

            $payment_rules =new CreateOrderPaymentRequest;
            $payment_validator = Validator::make($validation_data['payment'], $payment_rules->rules());

            if (!$payment_validator->passes()) {
                return RestAPI::response($payment_validator->errors()->all(), false, 'Validation Error');
            }
            unset($validation_data['payment']);
        }
        unset($validation_data['location']);unset($validation_data['sprint']);unset($validation_data['contact']);
        /**
         *Others
         */

        $other_rules=new CreateOrderOtherRequest;
        $other_validator = Validator::make($validation_data, $other_rules->rules());

        if (!$other_validator->passes()) {
            return RestAPI::response($other_validator->errors()->all(), false, 'Validation Error');
        }

        $checkaddress=$this->local_google_address($data_input['location']['address'],$data_input['location']['postal_code']);
        if($checkaddress['status']==0){
            return RestAPI::response('Invalid address/Postal code', false, 'Validation Error');
        }

        // validation=====================================================================
        else{
            $data=[];
            // sprint
            $data = $this->getFields($data_input);

            DB::beginTransaction();
            try {

                $merchant=[];
                if($data['sprint_merchant_order_num']!=null){
                    $merchant=MerchantsIds::where('merchant_order_num',$data['sprint_merchant_order_num'])->first();
                }

                if(empty($merchant)){

                    $location_data = $checkaddress;
                    $city_data=City::where('name',$location_data['city'])->first(); //get city data

                    if(empty($city_data)){
                        return RestAPI::response('We are not providing deliveries for this city', false, 'Validation Error');
                    }

                    $location_data['city_id'] = $city_data->id;
                    $location_data['state_id'] = $city_data->state_id;
                    $location_data['country_id'] = $city_data->country_id;
                    $location_data['buzzer'] = $data['location_buzzer'];

                    //save location for contact/drop off
                    $location_id = $this->createDropoffLoc($location_data);

                    //sprint contact save in sprint contact
                    $contact_id = $this->createdropoffCont($data);

                    // sprint save
                    $sprint = $this->initiateSprint($data);

                    //get time difference
                    $from['name']=$sprint->vendor->location->address;
                    $from['lat']=(float)($sprint->vendor->location->latitude/1000000);
                    $from['lng']=(float)($sprint->vendor->location->longitude/1000000);
                    $to['name']=$location_data['address'];
                    $to['lat']=$location_data['lat'];
                    $to['lng']=$location_data['lng'];

                    $time_difference= $this->gettimedifference($from,$to);
                    if(isset($time_difference['status'])){
                        return RestAPI::response($time_difference['error'], false, $time_difference['error_type']);
                    }

                    // then create two tasks in sprint task for drop off and pickup
                    //sprint tasks save type=pickup

                    $pickupTask = $this->createTask($sprint,'pickup',$data);

                    $sprint_task_pickup_data['sprint_id']=$sprint_id;
                    $sprint_task_pickup_data['type']='pickup';
                    $sprint_task_pickup_data['charge']=0;
                    $sprint_task_pickup_data['ordinal']=1;
                    $sprint_task_pickup_data['due_time']=$data['sprint_duetime'];
                    $sprint_task_pickup_data['eta_time']=$data['sprint_duetime'];
                    $sprint_task_pickup_data['etc_time']=$data['sprint_duetime'] + (0.25*60*60);//adding 15 mins
                    $sprint_task_pickup_data['location_id']=$sprint->vendor->location_id;//vendors table location id
                    $sprint_task_pickup_data['contact_id']=$sprint->vendor->contact_id;// Vendors table contact id
                    $sprint_task_pickup_data['status_id']=38;
                    $sprint_task_pickup_data['active']=1;
                    $sprint_task_pickup_data['confirm_image']=0;
                    $sprint_task_pickup_data['confirm_signature']=0;
                    $sprint_task_pickup_data['confirm_pin']=0;
                    $sprint_task_pickup_data['confirm_seal']=0;
                    $sprint_task_pickup=SprintTasks::create($sprint_task_pickup_data);
                    $sprint_task_pickup_id=$sprint_task_pickup->id;
                    SprintTaskHistory::insert(['created_at'=>date("Y-m-d H:i:s"),'date'=>date('Y-m-d H:i:s'),'sprint__tasks_id'=>$sprint_task_pickup_id,'sprint_id'=>$sprint_id,'status_id'=>38,'active'=>1]);

                    //sprint tasks save type=dropoff
                    $dropoff_charge=$this->getDropoffCharge($sprint->creator_id,1,$sprint->vehicle_id);
                    $sprint_task_dropoff_data['sprint_id']=$sprint_id;
                    $sprint_task_dropoff_data['type']='dropoff';
                    $sprint_task_dropoff_data['charge']=$dropoff_charge;
                    $sprint_task_dropoff_data['ordinal']=2;
                    $sprint_task_dropoff_data['due_time']=$data['sprint_duetime'];
                    $sprint_task_dropoff_data['eta_time']=$data['sprint_duetime']+($time_difference * 60 * 1000);//add time difference between two points
                    $sprint_task_dropoff_data['etc_time']=$sprint_task_dropoff_data['eta_time'] + (0.25*60*60);//adding 15 mins
                    $sprint_task_dropoff_data['location_id']=$location_id;//vendors table location id
                    $sprint_task_dropoff_data['contact_id']=$contact_id;
                    $sprint_task_dropoff_data['status_id']=38;
                    $sprint_task_dropoff_data['active']=1;
                    $sprint_task_dropoff_data['notify_by']=$data['notification_method'];
                    $sprint_task_dropoff_data['payment_type']=$data['payment_type'];
                    $sprint_task_dropoff_data['payment_amount']=$data['payment_amount'];
                    $sprint_task_dropoff_data['description']=$data['copy'];
                    $sprint_task_dropoff_data['confirm_image']=$data['confirm_image'];
                    $sprint_task_dropoff_data['confirm_signature']=$data['confirm_signature'];
                    $sprint_task_dropoff_data['confirm_pin']=$data['confirm_pin'];
                    $sprint_task_dropoff_data['confirm_seal']=$data['confirm_seal'];

                    if($data['confirm_pin']==1){
                        $check=true;
                        $pin_dropoff=0;
                        while ($check==true) {
                            $pin_dropoff=mt_rand(100000,999999);
                            $check_for_pin=SprintTasks::where('pin', $pin_dropoff)->where('type', 'dropoff')->first();
                            if(empty($check_for_pin)){
                                $check=false;
                            }
                        }
                        $sprint_task_dropoff_data['pin']=$pin_dropoff;
                    }

                    $sprint_task_dropoff=SprintTasks::create($sprint_task_dropoff_data);
                    $sprint_task_dropoff_id=$sprint_task_dropoff->id;
                    SprintTaskHistory::insert(['created_at'=>date("Y-m-d H:i:s"),'date'=>date('Y-m-d H:i:s'),'sprint__tasks_id'=>$sprint_task_dropoff_id,'sprint_id'=>$sprint_id,'status_id'=>38,'active'=>1,'date'=>date('Y-m-d H:i:s'),'created_at'=>date('Y-m-d H:i:s')]);

                    // save sprint confirmation
                    $ordinal=0;
                    $sprint_confirmation_data['task_id']=$sprint_task_dropoff_id;

                    if($data['confirm_pin']==1){
                        $ordinal+=1;
                        $sprint_confirmation_data['name']="confirm pin";
                        $sprint_confirmation_data['title']="Confirm Pin";
                        $sprint_confirmation_data['input_type']="text/plain";
                        $sprint_confirmation_data['ordinal']=$ordinal;
                        SprintConfirmation::create($sprint_confirmation_data);
                    }
                    if($data['confirm_image']==1){
                        $ordinal+=1;
                        $sprint_confirmation_data['name']="confirm image";
                        $sprint_confirmation_data['title']="Confirm Image";
                        $sprint_confirmation_data['input_type']="image/jpeg";
                        $sprint_confirmation_data['ordinal']=$ordinal;
                        SprintConfirmation::create($sprint_confirmation_data);
                    }
                    if($data['confirm_signature']==1){
                        $ordinal+=1;
                        $sprint_confirmation_data['name']="confirm signature";
                        $sprint_confirmation_data['title']="Confirm Signature";
                        $sprint_confirmation_data['input_type']="image/jpeg";
                        $sprint_confirmation_data['ordinal']=$ordinal;
                        SprintConfirmation::create($sprint_confirmation_data);
                    }
                    if($data['confirm_seal']==1){
                        $ordinal+=1;
                        $sprint_confirmation_data['name']="confirm seal";
                        $sprint_confirmation_data['title']="Confirm Seal";
                        $sprint_confirmation_data['input_type']="image/jpeg";
                        $sprint_confirmation_data['ordinal']=$ordinal;
                        SprintConfirmation::create($sprint_confirmation_data);
                    }
                    //default for dropoff
                    if ($ordinal==0) {
                        $sprint_confirmation_default_data['task_id']=$sprint_task_dropoff_id;
                        $sprint_confirmation_default_data['name']="default";
                        $sprint_confirmation_default_data['title']="Confirm Dropoff";
                        SprintConfirmation::create($sprint_confirmation_default_data);
                    }
                    //default for pickup
                    $sprint_confirmation_default_data['task_id']=$sprint_task_pickup_id;
                    $sprint_confirmation_default_data['name']="default";
                    $sprint_confirmation_default_data['title']="Confirm Pickup";
                    SprintConfirmation::create($sprint_confirmation_default_data);

                    // save merchant id data

                    $merchantid_data['task_id']=$sprint_task_dropoff_id; //task_id for dropoff
                    $merchantid_data['merchant_order_num']=$data['sprint_merchant_order_num'];
                    $merchantid_data['end_time']=$data['sprint_end_time'];
                    $merchantid_data['start_time']=$data['sprint_start_time'];
                    $merchantid_data['tracking_id']=$data['sprint_tracking_id'];
                    $merchantid_data['address_line2']=$data['location_address_line2'];
                    $merchantid=MerchantsIds::create($merchantid_data);

                    // update sprint
                    $sprint['last_task_id']=$sprint_task_dropoff_id;
                    $sprint['optimize_route']=1;
                    $sprint['status_id']=61;
                    $sprint['active']=1;
                    $sprint['timezone']=$city_data->timezone??'America/Toronto';
                    $sprint['push_at']=date("Y-m-d H:i:s", substr(($data['sprint_duetime']-(0.5*60*60)), 0, 10));
                    $sprint['distance']=$this->getDistanceBetweenPoints($to['lat'],$to['lng'],$from['lat'],$from['lng']);
                    $sprint['checked_out_at']=date('Y-m-d H:i:s');
                    $sprint['distance_charge']=$this->getDistanceCharge($sprint_task_dropoff);
                    $sprint->save();
                    // print_r($sprint);die;
                    DB::table('sprint__sprints_history')->insert(['sprint__sprints_id'=>$sprint_id,'vehicle_id'=>$sprint->vehicle_id,'distance'=>$sprint['distance'],'status_id'=>61,'active'=>1,'optimize_route'=>1,'date'=>date('Y-m-d H:i:s'),'created_at'=>date('Y-m-d H:i:s')]);

                    // update sprint task
                    SprintTasks::where('id',$sprint_task_pickup_id)->update(['status_id' => 61]);
                    SprintTaskHistory::insert(['created_at'=>date("Y-m-d H:i:s"),'date'=>date('Y-m-d H:i:s'),'sprint__tasks_id'=>$sprint_task_pickup_id,'sprint_id'=>$sprint_id,'status_id'=>61,'active'=>1]);
                    SprintTasks::where('id',$sprint_task_dropoff_id)->update(['status_id' => 61]);
                    SprintTaskHistory::insert(['created_at'=>date("Y-m-d H:i:s"),'date'=>date('Y-m-d H:i:s'),'sprint__tasks_id'=>$sprint_task_dropoff_id,'sprint_id'=>$sprint_id,'status_id'=>61,'active'=>1]);
                    $this->addToDispatch($sprint,['sprint_duetime'=> $data['sprint_duetime'],'copy'=> $data['copy']]);

                }
                else{
                    $sprint=$merchant->taskids->sprintsSprints;
                }

                $response=  new CreateOrderResource($sprint);

                DB::commit();
            }catch (\Exception $e) {
                DB::rollback();
                return RestAPI::response($e->getMessage(), false, 'error_exception');
            }
        }

        return RestAPI::responseForCreateOrder($response, true, 'Order Created');
    }
    public function getFields($data_input){

        $data['sprint_creator_id'] =$data_input['sprint']['creator_id'] ; //req
        $data['sprint_duetime'] =$data_input['sprint']['due_time'] ; //req
        $data['sprint_vehicle_id'] =$data_input['sprint']['vehicle_id'] ; //req
        $data['sprint_tracking_id'] =$data_input['sprint']['tracking_id']??null ;
        $data['sprint_merchant_order_num'] =$data_input['sprint']['merchant_order_num']??null ;
        $data['sprint_start_time'] =$data_input['sprint']['start_time']??null ;
        $data['sprint_end_time'] =$data_input['sprint']['end_time']??null ;
        $data['sprint_tip'] =$data_input['sprint']['tip']??null ;


        // contact
        $data['contact_name'] =$data_input['contact']['name'] ; //req
        $data['contact_email'] =$data_input['contact']['email']??null ;
        $data['contact_phone'] =$data_input['contact']['phone']??null ;

        // location
        $data['location_address'] =$data_input['location']['address'] ; //req
        $data['location_postal_code'] = $data_input['location']['postal_code']??null ; //req
        $data['location_address_line2'] =$data_input['location']['address_line2'] ??null;
        $data['location_pickup_buzzer'] =$data_input['location']['pickup_buzzer']??null ;
        $data['location_buzzer'] =$data_input['location']['buzzer'] ??null;

        // payment
        $data['payment_type'] =$data_input['payment']['type']??null ;
        $data['payment_amount'] =$data_input['payment']['amount'] ??null;

        // description
        $data['copy'] =$data_input['copy'] ??null;

        // notification method
        $data['notification_method'] =$data_input['notification_method'] ; //req

        // confirmation
        if(!isset($data_input['confirm_signature'])){
            $data_input['confirm_signature']=0;
        }
        if(!isset($data_input['confirm_pin'])){
            $data_input['confirm_pin']=0;
        }
        if(!isset($data_input['confirm_image'])){
            $data_input['confirm_image']=0;
        }
        if(!isset($data_input['confirm_seal'])){
            $data_input['confirm_seal']=0;
        }
        $data['confirm_signature'] =$data_input['confirm_signature'] ;
        $data['confirm_pin'] =$data_input['confirm_pin'] ;
        $data['confirm_image'] =$data_input['confirm_image'] ;
        $data['confirm_seal'] =$data_input['confirm_seal'] ;

        return $data;
    }
    public function createDropoffLoc($data){

        $location_data['buzzer']=$data['buzzer'];
        $location_data['postal_code']=$data['postal_code'];
        $location_data['latitude']=str_replace('.','',$data['lat']);
        $location_data['longitude']=str_replace('.','',$data['lng']);
        $location_data['address']=$data['address'];
        $location_data['city_id']=$data['city_id'];
        $location_data['state_id']=$data['state_id'];
        $location_data['country_id']=$data['country_id'];
        $location=Location::create($location_data);
        return $location->id;
    }
    public function createdropoffCont($data){
        $contact_data['name']=$data['contact_name'];
        $contact_data['phone']=$data['contact_phone'];
        $contact_data['email']=$data['contact_email'];
        $contact=SprintContact::create($data);
        return $contact->id;
    }
    public function initiateSprint($data){

        $sprint_data['creator_id']=$data['sprint_creator_id'];
        $sprint_data['creator_type']='vendor';
        $sprint_data['vehicle_id']=$data['sprint_vehicle_id'];
        $sprint_data['status_id']=38;
        $sprint_data['tip']=$data['sprint_tip'];

        if($data['payment_type']=='make'){
            $sprint_data['make_payment_total']=$data['payment_amount'];
        }elseif($data['payment_type']=='collect'){
            $sprint_data['collect_payment_total']=$data['payment_amount'];
        }

        $sprint=Sprint::create($sprint_data);
        $sprint_id=$sprint->id;

        DB::table('sprint__sprints_history')->insert([
            'sprint__sprints_id'=>$sprint_id,
            'vehicle_id'=>$sprint->vehicle_id,
            'distance'=>$sprint->distance,
            'status_id'=>$sprint->status_id,
            'active'=>1,
            'optimize_route'=>1,'date'=>date('Y-m-d H:i:s'),'created_at'=>date('Y-m-d H:i:s')
        ]);

        return $sprint;
    }
    public function createTask($sprint,$type,$data){

        $sprint_task_pickup_data['sprint_id']=$sprint->id;
        $sprint_task_pickup_data['type']=$type;
        $sprint_task_pickup_data['charge']=0;
        $sprint_task_pickup_data['ordinal']=1;
        $sprint_task_pickup_data['due_time']=$data['sprint_duetime'];
        $sprint_task_pickup_data['eta_time']=$data['sprint_duetime'];
        $sprint_task_pickup_data['etc_time']=$data['sprint_duetime'] + (0.25*60*60);//adding 15 mins
        $sprint_task_pickup_data['location_id']=$sprint->vendor->location_id;//vendors table location id
        $sprint_task_pickup_data['contact_id']=$sprint->vendor->contact_id;// Vendors table contact id
        $sprint_task_pickup_data['status_id']=38;
        $sprint_task_pickup_data['active']=1;
        $sprint_task_pickup_data['confirm_image']=0;
        $sprint_task_pickup_data['confirm_signature']=0;
        $sprint_task_pickup_data['confirm_pin']=0;
        $sprint_task_pickup_data['confirm_seal']=0;
        $sprint_task_pickup=SprintTasks::create($sprint_task_pickup_data);
        $sprint_task_pickup_id=$sprint_task_pickup->id;
        SprintTaskHistory::insert(['created_at'=>date("Y-m-d H:i:s"),'date'=>date('Y-m-d H:i:s'),'sprint__tasks_id'=>$sprint_task_pickup_id,'sprint_id'=>$sprint_id,'status_id'=>38,'active'=>1]);

        //sprint tasks save type=dropoff
        $dropoff_charge=$this->getDropoffCharge($sprint->creator_id,1,$sprint->vehicle_id);
        $sprint_task_dropoff_data['charge']=$dropoff_charge;
        $sprint_task_dropoff_data['ordinal']=2;
        $sprint_task_dropoff_data['due_time']=$data['sprint_duetime'];
        $sprint_task_dropoff_data['eta_time']=$data['sprint_duetime']+($time_difference * 60 * 1000);//add time difference between two points
        $sprint_task_dropoff_data['etc_time']=$sprint_task_dropoff_data['eta_time'] + (0.25*60*60);//adding 15 mins
        $sprint_task_dropoff_data['location_id']=$location_id;//vendors table location id
        $sprint_task_dropoff_data['contact_id']=$contact_id;
        $sprint_task_dropoff_data['status_id']=38;
        $sprint_task_dropoff_data['active']=1;
        $sprint_task_dropoff_data['notify_by']=$data['notification_method'];
        $sprint_task_dropoff_data['payment_type']=$data['payment_type'];
        $sprint_task_dropoff_data['payment_amount']=$data['payment_amount'];
        $sprint_task_dropoff_data['description']=$data['copy'];
        $sprint_task_dropoff_data['confirm_image']=$data['confirm_image'];
        $sprint_task_dropoff_data['confirm_signature']=$data['confirm_signature'];
        $sprint_task_dropoff_data['confirm_pin']=$data['confirm_pin'];
        $sprint_task_dropoff_data['confirm_seal']=$data['confirm_seal'];

    }
    public function createOrderLoblaws(Request $request)
    {
        $data_input = $request->all();
        $validation_data=$data_input;
        // checking is valid json
        if (json_last_error() != JSON_ERROR_NONE) {
            return RestAPI::response("Json request is not valid / ".json_last_error_msg(), false, json_last_error());
        }
        // $datetime=time() + (0.5*60*60);
        // validation=====================================================================
        // if($validation_data['sprint']['creator_id']=="" || $validation_data['sprint']['creator_id']==null){return RestAPI::response('The creator id field is required.', false, 'Validation Error');}

        /**
         *Sprint
         */

        $sprint_rules_loblaws=new CreateOrderLoblawsRequest;
        $sprint_validator = Validator::make($validation_data['sprint'], $sprint_rules_loblaws->rules());

        if (!$sprint_validator->passes()) {
            $sprintValidationErrors=$sprint_validator->errors()->all();
            foreach($sprintValidationErrors as $key=>$value){
                // if(in_array("The end time must be a date after start time.", $sprint_validator->errors()->all())) {
                if($value=="The end time must be a date after start time.") {
                    $sprintValidationErrors[$key]='End time should be greater than start time.';
                }
                if (strpos($value, 'The due time must be greater than or equal') !== false) {
                    $sprintValidationErrors[$key]='Due time must be half an hour from now!.';
                }
            }
            return RestAPI::response($sprintValidationErrors, false, 'Validation Error');
        }


        /**
         *Location
         */

        $location_rules =new CreateOrderLocationRequest;
        $checkDropOffLocation = (isset($validation_data['dropoff'])) ? $validation_data['dropoff']['location'] : $validation_data['location'];
        $checkLocationPickAndDrop = (isset($validation_data['pickup'])) ? $validation_data['pickup']['location'] : $checkDropOffLocation;
        $location_validator = Validator::make($checkLocationPickAndDrop, $location_rules->rules());
        if (!$location_validator->passes()) {
            return RestAPI::response($location_validator->errors()->all(), false, 'Validation Error');
        }

        /**
         *Contact
         */
        $contact_rules = new CreateOrderContactRequest;
        $checkDropOffContact = (isset($validation_data['dropoff'])) ? $validation_data['dropoff']['contact'] : $validation_data['contact'];
        $checkContactPickAndDrop = (isset($validation_data['pickup'])) ? $validation_data['pickup']['contact'] : $checkDropOffContact;
        $contact_validator = Validator::make($checkContactPickAndDrop, $contact_rules->rules());

        if (!$contact_validator->passes()) {
            return RestAPI::response($contact_validator->errors()->all(), false, 'Validation Error');
        }

        /**
         *Payment
         */
        if(isset($validation_data['payment'])){

            $payment_rules =new CreateOrderPaymentRequest;
            $payment_validator = Validator::make($validation_data['payment'], $payment_rules->rules());

            if (!$payment_validator->passes()) {
                return RestAPI::response($payment_validator->errors()->all(), false, 'Validation Error');
            }
            unset($validation_data['payment']);
        }
        unset($validation_data['location']);unset($validation_data['sprint']);unset($validation_data['contact']);
        /**
         *Others
         */

        $other_rules=new CreateOrderOtherRequest;
        $other_validator = Validator::make($validation_data, $other_rules->rules());

        if (!$other_validator->passes()) {
            return RestAPI::response($other_validator->errors()->all(), false, 'Validation Error');
        }

//        dd($data_input);
        // check address
        // $checkaddress=$this-> checkaddress($data_input['location']['address']);
        $addressDropOff = (isset($data_input['dropoff'])) ? $data_input['dropoff']['location']['address'] : $data_input['location']['address'];
        $postalCodeDropOff = (isset($data_input['dropoff'])) ? $data_input['dropoff']['location']['postal_code'] : $data_input['location']['postal_code'];

        $checkaddress=$this->loblaws_google_address($addressDropOff,$postalCodeDropOff);
        // dd($checkaddress);

        // check postal code
        // $checkpostalcode=$this->checkpostalcode($data_input['location']['postal_code']);

        //check postal code and address(valid and for canada only)
        // if($checkaddress['status']==0 || $checkpostalcode==0 ){return RestAPI::response('Invalid address/Postal code', false, 'Validation Error');}
        if($checkaddress['status']==0){return RestAPI::response('Invalid address/Postal code', false, 'Validation Error');}

        // validation=====================================================================
        else{

            $data=[];
            // sprint
            $data['sprint_creator_id'] =$data_input['sprint']['creator_id'] ; //req
            $data['sprint_duetime'] =$data_input['sprint']['due_time'] ; //req
            $data['sprint_vehicle_id'] =$data_input['sprint']['vehicle_id'] ; //req
            $data['sprint_tracking_id'] =$data_input['sprint']['tracking_id']??null ;
            $data['sprint_merchant_order_num'] =$data_input['sprint']['merchant_order_num']??null ;
            $data['sprint_start_time'] =$data_input['sprint']['start_time']??null ;
            $data['sprint_end_time'] =$data_input['sprint']['end_time']??null ;
            $data['sprint_tip'] =$data_input['sprint']['tip']??null ;


            // contact
            $data['contact_name'] =$data_input['contact']['name'] ; //req
            $data['contact_email'] =$data_input['contact']['email']??null ;
            $data['contact_phone'] =$data_input['contact']['phone']??null ;

            // location
            $data['location_address'] =$data_input['location']['address'] ; //req
            $data['location_postal_code'] =$data_input['location']['postal_code'] ; //req
            $data['location_address_line2'] =$data_input['location']['address_line2'] ??null;
            $data['location_pickup_buzzer'] =$data_input['location']['pickup_buzzer']??null ;
            $data['location_buzzer'] =$data_input['location']['buzzer'] ??null;

            // payment
            $data['payment_type'] =$data_input['payment']['type']??null ;
            $data['payment_amount'] =$data_input['payment']['amount'] ??null;

            // description
            $data['copy'] =$data_input['copy'] ??null;

            // notification method
            $data['notification_method'] =$data_input['notification_method'] ; //req

            // confirmation
            if(!isset($data_input['confirm_signature'])){
                $data_input['confirm_signature']=0;
            }
            if(!isset($data_input['confirm_pin'])){
                $data_input['confirm_pin']=0;
            }
            if(!isset($data_input['confirm_image'])){
                $data_input['confirm_image']=0;
            }
            if(!isset($data_input['confirm_seal'])){
                $data_input['confirm_seal']=0;
            }
            $data['confirm_signature'] =$data_input['confirm_signature'] ;
            $data['confirm_pin'] =$data_input['confirm_pin'] ;
            $data['confirm_image'] =$data_input['confirm_image'] ;
            $data['confirm_seal'] =$data_input['confirm_seal'] ;

            // echo 3;die;
            DB::beginTransaction();
            try {


                //get address data
                // $contact_address=$this->loblaws_google_address($data['location_address'],$data['location_postal_code']);
                $contact_address=$checkaddress;

                $city_data=City::where('name',$contact_address['city'])->first(); //get city data


                $merchant=[];
                if($data['sprint_tracking_id']!=null){$merchant=MerchantsIds::where('tracking_id',$data['sprint_tracking_id'])->first();}
                if(empty($merchant)){

                    // echo 1;die;
                    //save location for contact/drop off
                    $location_data['buzzer']=$data['location_buzzer'];
                    $location_data['postal_code']=$contact_address['postal_code'];
                    $location_data['latitude']=str_replace('.','',$contact_address['lat']);
                    $location_data['longitude']=str_replace('.','',$contact_address['lng']);
                    $location_data['address']=(isset($contact_address['street_number']) && isset($contact_address['route'])) ? $contact_address['street_number'].' '.$contact_address['route'] : $data['location_address'];
                    $location_data['city_id']=$city_data->id;
                    $location_data['state_id']=$city_data->state_id;
                    $location_data['country_id']=$city_data->country_id;
                    // print_r( $location_data);die;
                    $location=Location::create($location_data);
                    $location_id=$location->id;

                    //comment for now 2023-05-30 encryption error reason
                    // $this->insertLocationEnc($location_id,$location_data);

                    //sprint contact save in sprint contact
                    $contact_data['name']=$data['contact_name'];
                    $contact_data['phone']=$data['contact_phone'];
                    $contact_data['email']=$data['contact_email'];
                    $contact=SprintContact::create($contact_data);
                    $contact_id=$contact->id;

                    //comment for now 2023-05-30 encryption error reason
                    // $this->insertContactEnc($contact_id,$contact_data);

                    // sprint save
                    $sprint_data['creator_id']=$data['sprint_creator_id'];
                    $sprint_data['creator_type']='vendor';
                    $sprint_data['vehicle_id']=$data['sprint_vehicle_id'];
                    $sprint_data['status_id']=38;
                    $sprint_data['tip']=$data['sprint_tip'];
                    if($data['payment_type']=='make'){$sprint_data['make_payment_total']=$data['payment_amount'];}elseif($data['payment_type']=='collect'){$sprint_data['collect_payment_total']=$data['payment_amount'];}
                    $sprint=Sprint::create($sprint_data);
                    $sprint_id=$sprint->id;
                    DB::table('sprint__sprints_history')->insert([
                        'sprint__sprints_id'=>$sprint_id,
                        'vehicle_id'=>$sprint->vehicle_id,
//                        'distance'=>$sprint->distance,
                        'status_id'=>$sprint->status_id,
                        'active'=>1,
//                        'optimize_route'=>1,
                        'date'=>date('Y-m-d H:i:s'),
                        'created_at'=>date('Y-m-d H:i:s')
                    ]);

                    //get time difference
//                     dd($sprint->vendor->location->address);
                    $from['name']=$sprint->vendor->location->address;
                    $from['lat']=(float)($sprint->vendor->location->latitude);
                    $from['lng']=(float)($sprint->vendor->location->longitude);
                    $to['name']=$location_data['address'];
                    $to['lat']=$contact_address['lat'];
                    $to['lng']=$contact_address['lng'];

//                     dd($from,$to);

                    // $time_difference= $this->gettimedifference($from,$to);
                    // $time_difference= $this->gettimedifference($from,$to);
                    $time_difference = 24;
                    if(isset($time_difference['status'])){return RestAPI::response($time_difference['error'], false, $time_difference['error_type']);}

                    //save vendor as contact for vendor contact id
                    $vendor_contact_data['name']=$sprint->vendor->first_name.' '.$sprint->vendor->last_name;
                    $vendor_contact_data['phone']=$sprint->vendor->phone;
                    $vendor_contact_data['email']=$sprint->vendor->email;
                    $vendor_contact=SprintContact::create($vendor_contact_data);
                    $vendor_contact_id=$vendor_contact->id;

                    //comment for now 2023-05-30 encryption error reason
                    //  $this->insertContactEnc($vendor_contact_id,$vendor_contact_data);

                    // then create two tasks in sprint task for drop off and pickup
                    //sprint tasks save type=pickup
                    $sprint_task_pickup_data['sprint_id']=$sprint_id;
                    $sprint_task_pickup_data['type']='pickup';
                    $sprint_task_pickup_data['charge']=0;
                    $sprint_task_pickup_data['ordinal']=1;
                    $sprint_task_pickup_data['due_time']=$data['sprint_duetime'];
                    $sprint_task_pickup_data['eta_time']=$data['sprint_duetime'];
                    $sprint_task_pickup_data['etc_time']=$data['sprint_duetime'] + (0.25*60*60);//adding 15 mins
                    $sprint_task_pickup_data['location_id']=$sprint->vendor->location_id;//vendors table location id
                    $sprint_task_pickup_data['contact_id']=$vendor_contact_id;
                    $sprint_task_pickup_data['status_id']=38;
                    $sprint_task_pickup_data['active']=1;
                    $sprint_task_pickup_data['confirm_image']=0;
                    $sprint_task_pickup_data['confirm_signature']=0;
                    $sprint_task_pickup_data['confirm_pin']=0;
                    $sprint_task_pickup_data['confirm_seal']=0;
                    $sprint_task_pickup=SprintTasks::create($sprint_task_pickup_data);
                    $sprint_task_pickup_id=$sprint_task_pickup->id;
                    SprintTaskHistory::insert(['created_at'=>date("Y-m-d H:i:s"),'date'=>date('Y-m-d H:i:s'),'sprint__tasks_id'=>$sprint_task_pickup_id,'sprint_id'=>$sprint_id,'status_id'=>38,'active'=>1]);

                    //sprint tasks save type=dropoff
                    $dropoff_charge=$this->getDropoffCharge($sprint->creator_id,1,$sprint->vehicle_id);
                    $sprint_task_dropoff_data['sprint_id']=$sprint_id;
                    $sprint_task_dropoff_data['type']='dropoff';
                    $sprint_task_dropoff_data['charge']=$dropoff_charge;
                    $sprint_task_dropoff_data['ordinal']=2;
                    $sprint_task_dropoff_data['due_time']=$data['sprint_duetime'];
                    $sprint_task_dropoff_data['eta_time']=$data['sprint_duetime']+($time_difference * 60 * 1000);//add time difference between two points
                    $sprint_task_dropoff_data['etc_time']=$sprint_task_dropoff_data['eta_time'] + (0.25*60*60);//adding 15 mins
                    $sprint_task_dropoff_data['location_id']=$location_id;//vendors table location id
                    $sprint_task_dropoff_data['contact_id']=$contact_id;
                    $sprint_task_dropoff_data['status_id']=38;
                    $sprint_task_dropoff_data['active']=1;
                    $sprint_task_dropoff_data['notify_by']=$data['notification_method'];
                    $sprint_task_dropoff_data['payment_type']=$data['payment_type'];
                    $sprint_task_dropoff_data['payment_amount']=$data['payment_amount'];
                    $sprint_task_dropoff_data['description']=$data['copy'];
                    $sprint_task_dropoff_data['confirm_image']=$data['confirm_image'];
                    $sprint_task_dropoff_data['confirm_signature']=$data['confirm_signature'];
                    $sprint_task_dropoff_data['confirm_pin']=$data['confirm_pin'];
                    $sprint_task_dropoff_data['confirm_seal']=$data['confirm_seal'];
                    if($data['confirm_pin']==1){
                        $check=true;
                        $pin_dropoff=0;
                        while ($check==true) {
                            $pin_dropoff=mt_rand(100000,999999);
                            $check_for_pin=SprintTasks::where('pin', $pin_dropoff)->where('type', 'dropoff')->first();
                            if(empty($check_for_pin)){
                                $check=false;
                            }
                        }
                        $sprint_task_dropoff_data['pin']=$pin_dropoff;
                    }
                    $sprint_task_dropoff=SprintTasks::create($sprint_task_dropoff_data);
                    $sprint_task_dropoff_id=$sprint_task_dropoff->id;
                    SprintTaskHistory::insert(['created_at'=>date("Y-m-d H:i:s"),'date'=>date('Y-m-d H:i:s'),'sprint__tasks_id'=>$sprint_task_dropoff_id,'sprint_id'=>$sprint_id,'status_id'=>38,'active'=>1,'date'=>date('Y-m-d H:i:s'),'created_at'=>date('Y-m-d H:i:s')]);

                    // save sprint confirmation
                    $ordinal=0;
                    $sprint_confirmation_data['task_id']=$sprint_task_dropoff_id;
                    if($data['confirm_pin']==1){
                        $ordinal+=1;
                        $sprint_confirmation_data['name']="confirm pin";
                        $sprint_confirmation_data['title']="Confirm Pin";
                        $sprint_confirmation_data['input_type']="text/plain";
                        $sprint_confirmation_data['ordinal']=$ordinal;
                        SprintConfirmation::create($sprint_confirmation_data);
                    }
                    if($data['confirm_image']==1){
                        $ordinal+=1;
                        $sprint_confirmation_data['name']="confirm image";
                        $sprint_confirmation_data['title']="Confirm Image";
                        $sprint_confirmation_data['input_type']="image/jpeg";
                        $sprint_confirmation_data['ordinal']=$ordinal;
                        SprintConfirmation::create($sprint_confirmation_data);
                    }
                    if($data['confirm_signature']==1){
                        $ordinal+=1;
                        $sprint_confirmation_data['name']="confirm signature";
                        $sprint_confirmation_data['title']="Confirm Signature";
                        $sprint_confirmation_data['input_type']="image/jpeg";
                        $sprint_confirmation_data['ordinal']=$ordinal;
                        SprintConfirmation::create($sprint_confirmation_data);
                    }
                    if($data['confirm_seal']==1){
                        $ordinal+=1;
                        $sprint_confirmation_data['name']="confirm seal";
                        $sprint_confirmation_data['title']="Confirm Seal";
                        $sprint_confirmation_data['input_type']="image/jpeg";
                        $sprint_confirmation_data['ordinal']=$ordinal;
                        SprintConfirmation::create($sprint_confirmation_data);
                    }
                    //default for dropoff
                    if ($ordinal==0) {
                        $sprint_confirmation_default_data['task_id']=$sprint_task_dropoff_id;
                        $sprint_confirmation_default_data['name']="default";
                        $sprint_confirmation_default_data['title']="Confirm Dropoff";
                        SprintConfirmation::create($sprint_confirmation_default_data);
                    }
                    //default for pickup
                    $sprint_confirmation_default_data['task_id']=$sprint_task_pickup_id;
                    $sprint_confirmation_default_data['name']="default";
                    $sprint_confirmation_default_data['title']="Confirm Pickup";
                    SprintConfirmation::create($sprint_confirmation_default_data);

                    // save merchant id data

                    $merchantid_data['task_id']=$sprint_task_dropoff_id; //task_id for dropoff
                    $merchantid_data['merchant_order_num']=$data['sprint_merchant_order_num'];
                    $merchantid_data['end_time']=$data['sprint_end_time'];
                    $merchantid_data['start_time']=$data['sprint_start_time'];
                    $merchantid_data['tracking_id']=$data['sprint_tracking_id'];
                    $merchantid_data['address_line2']=$data['location_address_line2'];
                    $merchantid=MerchantsIds::create($merchantid_data);

                    // update sprint
                    // $sprint_update_data['last_task_id']=$sprint_task_dropoff_id;
                    // $sprint_update_data['optimize_route']=1;
                    // $sprint_update_data['status_id']=61;
                    // $sprint_update_data['active']=1;
                    // $sprint_update_data['timezone']=$city_data->timezone??'America/Toronto';
                    // $sprint_update_data['push_at']=date("Y-m-d H:i:s", substr(($data['sprint_duetime']-(0.5*60*60)), 0, 10));
                    // $sprint_update_data['distance']=$this->getDistanceBetweenPoints($to['lat'],$to['lng'],$from['lat'],$from['lng']);
                    // $sprint_update_data['checked_out_at']=date('Y-m-d H:i:s');
                    // $sprint=Sprint::where('id',$sprint_id)->update($sprint_update_data);
                    // $current_distance_charge=(($sprint->distance_charge==null)?0:$sprint->distance_charge);
                    // $sprint = Sprint::find($sprint_id);
                    $sprint['last_task_id']=$sprint_task_dropoff_id;
                    $sprint['optimize_route']=1;
                    $sprint['status_id']=61;
                    $sprint['active']=1;
//                    $sprint['timezone']=$city_data->timezone??'America/Toronto';
//                    $sprint['push_at']=date("Y-m-d H:i:s", substr(($data['sprint_duetime']-(0.5*60*60)), 0, 10));
//                    $sprint['distance']=$this->getDistanceBetweenPoints($to['lat'],$to['lng'],$from['lat'],$from['lng']);
//                    $sprint['checked_out_at']=date('Y-m-d H:i:s');
//                    $sprint['distance_charge']=$this->getDistanceCharge($sprint_task_dropoff);
                    // echo  $sprint['distance_charge'];die;
                    $sprint->save();
                    // print_r($sprint);die;
                    DB::table('sprint__sprints_history')->insert([
                        'sprint__sprints_id'=>$sprint_id,
                        'vehicle_id'=>$sprint->vehicle_id,
//                        'distance'=>$sprint['distance'],
                        'status_id'=>61,'active'=>1,
//                        'optimize_route'=>1,
                        'date'=>date('Y-m-d H:i:s'),
                        'created_at'=>date('Y-m-d H:i:s')
                    ]);

                    // update sprint task
                    SprintTasks::where('id',$sprint_task_pickup_id)->update(['status_id' => 61]);
                    SprintTaskHistory::insert(['created_at'=>date("Y-m-d H:i:s"),'date'=>date('Y-m-d H:i:s'),'sprint__tasks_id'=>$sprint_task_pickup_id,'sprint_id'=>$sprint_id,'status_id'=>61,'active'=>1]);
                    SprintTasks::where('id',$sprint_task_dropoff_id)->update(['status_id' => 61]);
                    SprintTaskHistory::insert(['created_at'=>date("Y-m-d H:i:s"),'date'=>date('Y-m-d H:i:s'),'sprint__tasks_id'=>$sprint_task_dropoff_id,'sprint_id'=>$sprint_id,'status_id'=>61,'active'=>1]);

//                    $this->addToDispatch($sprint,['sprint_duetime'=> $data['sprint_duetime'],'copy'=> $data['copy']]);


                    //create borderless dashboard data
                    $borderLessData['sprint_id'] = $sprint_id;
                    $borderLessData['task_id'] = $sprint_task_dropoff->id;
                    $borderLessData['creator_id'] = $data['sprint_creator_id'];
                    $borderLessData['tracking_id'] =  $data['sprint_tracking_id'];
                    $borderLessData['eta_time'] = $data['sprint_duetime']+($time_difference * 60 * 1000);
                    $borderLessData['task_status_id'] = 61;
                    $borderLessData['store_name'] = $sprint->vendor->name;
                    $borderLessData['customer_name'] = $data['contact_name'];
                    $borderLessData['weight'] = '';
                    $borderLessData['address_line_1'] = $data['location_address'];
                    $borderLessData['address_line_2'] = $data['location_address'];
                    $borderLessData['address_line_3'] = $data['location_address'];

                    BorderlessDashboard::create($borderLessData);
                    //Function

                    // $dispatchData['order_id']=$sprint->id;
                    // $dispatchData['num']='CR-'.$sprint->id;
                    // $dispatchData['creator_id']=$sprint->creator_id;
                    // $dispatchData['sprint_id']=$sprint->id;
                    // $dispatchData['status']=$sprint->status_id;
                    // $dispatchData['distance']=$sprint->distance;
                    // $dispatchData['active']=$sprint->active;
                    // $dispatchData['type']="custom-run";
                    // $dispatchData['vehicle_id']=$sprint->vehicle_id;
                    // $dispatchData['vehicle_name']=($sprint->vehicle!=null)?$sprint->vehicle->name:null;
                    // $dispatchData['pickup_location_id']=$sprint->sprintFirstPickupTask->location_id;
                    // $dispatchData['pickup_contact_name']=$sprint->sprintFirstPickupTask->sprintContact->name;
                    // $dispatchData['pickup_address']=$sprint->sprintFirstPickupTask->Location->address;
                    // $dispatchData['pickup_contact_phone']=$sprint->sprintFirstPickupTask->sprintContact->phone;
                    // $dispatchData['pickup_eta']=$sprint->sprintFirstPickupTask->eta_time;
                    // $dispatchData['pickup_etc']=$sprint->sprintFirstPickupTask->etc_time;
                    // $dispatchData['dropoff_contact_phone']=$sprint->sprintFirstDropoffTask->sprintContact->name;
                    // $dispatchData['dropoff_location_id']=$sprint->sprintFirstDropoffTask->location_id;
                    // $dispatchData['dropoff_address']=$sprint->sprintFirstDropoffTask->Location->address;
                    // $dispatchData['dropoff_eta']=$sprint->sprintFirstDropoffTask->eta_time;
                    // $dispatchData['dropoff_etc']=$sprint->sprintFirstDropoffTask->etc_time;
                    // $dispatchData['date']=$data['sprint_duetime'];
                    // $dispatchData['has_notes']=0;
                    // $dispatchData['sprint_duration']=null;
                    // $dispatchData['zone_id']=null;
                    // $dispatchData['zone_name']=null;
                    // $dispatchData['status_copy']=$data['copy'];


                    // $ts1 = strtotime($sprint->sprintFirstPickupTask->etc_time);
                    // $ts2 = strtotime($sprint->sprintFirstDropoffTask->etc_time);
                    // $seconds_diff = $ts2 - $ts1;
                    // $time = ($seconds_diff/3600);//in seconds


                    // $dispatchData['sprint_duration']=$time/60;//in minutes

                    // $zoneVendorRelationship=$sprint->vendor->zoneVendorRelationship;


                    // if($zoneVendorRelationship!=null){
                    //     $zone=$zoneVendorRelationship->zones;
                    //     if($zone!=null) {

                    //         $dispatchData['zone_name']=$zone->name;
                    //         $dispatchData['zone_id']=$zone->id;

                    //     }
                    // }
                    // SprintZone::create(['sprint_id'=>$sprint->id,'zone_id'=>$dispatchData['zone_id']]);

                    // Dispatch::create($dispatchData);


                    //Function

                }
                else{
                    $sprint=$merchant->taskids->sprintsSprints;
                    // print_r($sprint);
                }
                // die;
                //response
                // $time_end = microtime(true);
                // $execution_time = ($time_end - $time_start);


                $response=  new CreateOrderResource($sprint);
                // echo '<b>Total Execution Time:</b> '.($execution_time*1000).' Milliseconds';
                // die;
                DB::commit();
            }catch (\Exception $e) {
                DB::rollback();
                return RestAPI::response($e->getMessage(), false, 'error_exception');
            }
        }
        return RestAPI::responseForCreateOrder($response, true, 'Order Created');
    }
    public function createOrderWalmart(Request $request)
    {
        $data_input = $request->all();
        $validation_data=$data_input;
        if (json_last_error() != JSON_ERROR_NONE) {
            return RestAPI::response("Json request is not valid / ".json_last_error_msg(), false, json_last_error());
        }
        $datetime=time() + (0.5*60*60);
        // validation=====================================================================
        /**
         *Location
         */
        $location_rules =new CreateOrderLocationRequest;
        $location_validator = Validator::make($validation_data['location'], $location_rules->rules());


        if (!$location_validator->passes()) {
            return RestAPI::response($location_validator->errors()->all(), false, 'Validation Error');
        }
        /**
         *Sprint
         */
        $sprint_rules_wm=new CreateOrderWalmartRequest;
        $sprint_validator = Validator::make($validation_data['sprint'], $sprint_rules_wm->rules());

        if (!$sprint_validator->passes()) {
            $sprintValidationErrors=$sprint_validator->errors()->all();
            foreach($sprintValidationErrors as $key=>$value){
                // if(in_array("The end time must be a date after start time.", $sprint_validator->errors()->all())) {
                if($value=="The end time must be a date after start time.") {
                    $sprintValidationErrors[$key]='End time should be greater than start time.';
                }
            }
            return RestAPI::response($sprintValidationErrors, false, 'Validation Error');
        }
        // check storeid
        $check_store=$this->checkStoreId($validation_data['sprint']['store_id']);
        if($check_store==0){return RestAPI::response("Store Id doesn't exists.", false, 'Validation Error');}

        /**
         *Contact
         */
        $contact_rules = new CreateOrderContactRequest;
        $contact_validator = Validator::make($validation_data['contact'], $contact_rules->rules());


        if (!$contact_validator->passes()) {
            return RestAPI::response($contact_validator->errors()->all(), false, 'Validation Error');
        }
        /**
         *Payment
         */
        if(isset($validation_data['payment'])){
            $payment_rules =new CreateOrderPaymentRequest;
            $payment_validator = Validator::make($validation_data['payment'], $payment_rules->rules());


            if (!$payment_validator->passes()) {
                return RestAPI::response($payment_validator->errors()->all(), false, 'Validation Error');
            }
            unset($validation_data['payment']);
        }
        unset($validation_data['location']);unset($validation_data['sprint']);unset($validation_data['contact']);
        /**
         *Others
         */
        $other_rules=new CreateOrderOtherRequest;
        $other_validator = Validator::make($validation_data, $other_rules->rules());


        if (!$other_validator->passes()) {
            return RestAPI::response($other_validator->errors()->all(), false, 'Validation Error');
        }

        // check address
        // $checkaddress=$this->checkaddress($data_input['location']['address']);
        // check postal code
        // $checkpostalcode=$this->checkpostalcode($data_input['location']['postal_code']);
        $checkaddress=$this->loblaws_google_address($data_input['location']['address'],$data_input['location']['postal_code']);
        //check postal code and address(valid and for canada only)
        if($checkaddress['status']==0 ){return RestAPI::response('Invalid address/Postal code', false, 'Validation Error');}
        // validation=====================================================================
        else{
            $data=[];
            // sprint
            $data['sprint_creator_id'] =$check_store; //req
            if($data_input['sprint']['due_time']>=$datetime){$data['sprint_duetime'] =$data_input['sprint']['due_time'];}//req
            else{$data['sprint_duetime'] =time();}//req
            $data['sprint_vehicle_id'] =$data_input['sprint']['vehicle_id'] ; //req
            $data['sprint_tracking_id'] =$data_input['sprint']['tracking_id']??null ;
            $data['sprint_merchant_order_num'] =$data_input['sprint']['merchant_order_num']??null ;
            $data['sprint_start_time'] =$data_input['sprint']['start_time']??null ;
            $data['sprint_end_time'] =$data_input['sprint']['end_time']??null ;
            $data['sprint_tip'] =$data_input['sprint']['tip']??null ;


            // contact
            $data['contact_name'] =$data_input['contact']['name'] ; //req
            $data['contact_email'] =$data_input['contact']['email']??null ;
            $data['contact_phone'] =$data_input['contact']['phone']??null ;

            // location
            $data['location_address'] =$data_input['location']['address'] ; //req
            $data['location_postal_code'] =$data_input['location']['postal_code'] ; //req
            $data['location_address_line2'] =$data_input['location']['address_line2'] ??null;
            $data['location_pickup_buzzer'] =$data_input['location']['pickup_buzzer']??null ;
            $data['location_buzzer'] =$data_input['location']['buzzer'] ??null;

            // payment
            $data['payment_type'] =$data_input['payment']['type']??null ;
            $data['payment_amount'] =$data_input['payment']['amount'] ??null;

            // description
            $data['copy'] =$data_input['copy'] ??null;

            // notification method
            $data['notification_method'] =$data_input['notification_method'] ; //req

            // confirmation
            if(!isset($data_input['confirm_signature'])){
                $data_input['confirm_signature']=0;
            }
            if(!isset($data_input['confirm_pin'])){
                $data_input['confirm_pin']=0;
            }
            if(!isset($data_input['confirm_image'])){
                $data_input['confirm_image']=0;
            }
            if(!isset($data_input['confirm_seal'])){
                $data_input['confirm_seal']=0;
            }
            $data['confirm_signature'] =$data_input['confirm_signature'] ;
            $data['confirm_pin'] =$data_input['confirm_pin'] ;
            $data['confirm_image'] =$data_input['confirm_image'] ;
            $data['confirm_seal'] =$data_input['confirm_seal'] ;


            DB::beginTransaction();
            try {
                //get address data
                // $contact_address=$this->loblaws_google_address($data['location_address'],$data['location_postal_code']);
                $contact_address=$checkaddress;

                $city_data=City::where('name',$contact_address['city'])->first(); //get city data


                $merchant=[];
                if($data['sprint_tracking_id']!=null){$merchant=MerchantsIds::where('tracking_id',$data['sprint_tracking_id'])->first();}
                if(empty($merchant)){
                    // echo 1;die;
                    //save location for contact/drop off
                    $location_data['buzzer']=$data['location_buzzer'];
                    $location_data['postal_code']=$contact_address['postal_code'];
                    $location_data['latitude']=str_replace('.','',$contact_address['lat']);
                    $location_data['longitude']=str_replace('.','',$contact_address['lng']);
                    $location_data['address']=$contact_address['street_number'].' '.$contact_address['route'];
                    $location_data['city_id']=$city_data->id;
                    $location_data['state_id']=$city_data->state_id;
                    $location_data['country_id']=$city_data->country_id;
                    $location=Location::create($location_data);
                    $location_id=$location->id;

                    $this->insertLocationEnc($location_id,$location_data);


                    //sprint contact save in sprint contact
                    $contact_data['name']=$data['contact_name'];
                    $contact_data['phone']=$data['contact_phone'];
                    $contact_data['email']=$data['contact_email'];
                    $contact=SprintContact::create($contact_data);
                    $contact_id=$contact->id;
                    $this->insertContactEnc($contact_id,$contact_data);


                    // sprint save
                    $sprint_data['creator_id']=$data['sprint_creator_id'];
                    $sprint_data['creator_type']='vendor';
                    $sprint_data['vehicle_id']=$data['sprint_vehicle_id'];
                    $sprint_data['status_id']=38;
                    $sprint_data['tip']=$data['sprint_tip'];
                    if($data['payment_type']=='make'){$sprint_data['make_payment_total']=$data['payment_amount'];}elseif($data['payment_type']=='collect'){$sprint_data['collect_payment_total']=$data['payment_amount'];}
                    $sprint=Sprint::create($sprint_data);
                    $sprint_id=$sprint->id;
                    DB::table('sprint__sprints_history')->insert(['sprint__sprints_id'=>$sprint_id,'vehicle_id'=>$sprint->vehicle_id,'distance'=>$sprint->distance,'status_id'=>$sprint->status_id,'active'=>1,'optimize_route'=>1,'date'=>date('Y-m-d H:i:s'),'created_at'=>date('Y-m-d H:i:s')]);

                    //get time difference
                    $from['name']=$sprint->vendor->location->address;
                    $from['lat']=(float)($sprint->vendor->location->latitude/1000000);
                    $from['lng']=(float)($sprint->vendor->location->longitude/1000000);
                    $to['name']=$location_data['address'];
                    $to['lat']=$contact_address['lat'];
                    $to['lng']=$contact_address['lng'];
                    $time_difference= $this->gettimedifference($from,$to);
                    if(isset($time_difference['status'])){return RestAPI::response($time_difference['error'], false, $time_difference['error_type']);}

                    //save vendor as contact for vendor contact id
                    $vendor_contact_data['name']=$sprint->vendor->first_name.' '.$sprint->vendor->last_name;
                    $vendor_contact_data['phone']=$sprint->vendor->phone;
                    $vendor_contact_data['email']=$sprint->vendor->email;
                    $vendor_contact=SprintContact::create($vendor_contact_data);
                    $vendor_contact_id=$vendor_contact->id;
                    $this->insertContactEnc($vendor_contact_id,$vendor_contact_data);


                    // then create two tasks in sprint task for drop off and pickup
                    //sprint tasks save type=pickup
                    $sprint_task_pickup_data['sprint_id']=$sprint_id;
                    $sprint_task_pickup_data['type']='pickup';
                    $sprint_task_pickup_data['ordinal']=1;
                    $sprint_task_pickup_data['due_time']=$data['sprint_duetime'];
                    $sprint_task_pickup_data['eta_time']=$data['sprint_duetime'];
                    $sprint_task_pickup_data['etc_time']=$data['sprint_duetime'] + (0.25*60*60);//adding 15 mins
                    $sprint_task_pickup_data['location_id']=$sprint->vendor->location_id;//vendors table location id
                    $sprint_task_pickup_data['contact_id']=$vendor_contact_id;
                    $sprint_task_pickup_data['status_id']=38;
                    $sprint_task_pickup_data['active']=1;
                    $sprint_task_pickup_data['confirm_image']=0;
                    $sprint_task_pickup_data['confirm_signature']=0;
                    $sprint_task_pickup_data['confirm_pin']=0;
                    $sprint_task_pickup_data['confirm_seal']=0;
                    $sprint_task_pickup=SprintTasks::create($sprint_task_pickup_data);
                    $sprint_task_pickup_id=$sprint_task_pickup->id;
                    SprintTaskHistory::insert(['created_at'=>date("Y-m-d H:i:s"),'date'=>date('Y-m-d H:i:s'),'sprint__tasks_id'=>$sprint_task_pickup_id,'sprint_id'=>$sprint_id,'status_id'=>38,'active'=>1]);

                    //sprint tasks save type=dropoff
                    $sprint_task_dropoff_data['sprint_id']=$sprint_id;
                    $sprint_task_dropoff_data['type']='dropoff';
                    $sprint_task_dropoff_data['ordinal']=2;
                    $sprint_task_dropoff_data['due_time']=$data['sprint_duetime'];
                    $sprint_task_dropoff_data['eta_time']=$data['sprint_duetime']+($time_difference * 60 * 1000);//add time difference between two points
                    $sprint_task_dropoff_data['etc_time']=$sprint_task_dropoff_data['eta_time'] + (0.25*60*60);//adding 15 mins
                    $sprint_task_dropoff_data['location_id']=$location_id;//vendors table location id
                    $sprint_task_dropoff_data['contact_id']=$contact_id;
                    $sprint_task_dropoff_data['status_id']=38;
                    $sprint_task_dropoff_data['active']=1;
                    $sprint_task_dropoff_data['notify_by']=$data['notification_method'];
                    $sprint_task_dropoff_data['payment_type']=$data['payment_type'];
                    $sprint_task_dropoff_data['payment_amount']=$data['payment_amount'];
                    $sprint_task_dropoff_data['description']=$data['copy'];
                    $sprint_task_dropoff_data['confirm_image']=$data['confirm_image'];
                    $sprint_task_dropoff_data['confirm_signature']=$data['confirm_signature'];
                    $sprint_task_dropoff_data['confirm_pin']=$data['confirm_pin'];
                    $sprint_task_dropoff_data['confirm_seal']=$data['confirm_seal'];
                    if($data['confirm_pin']==1){
                        $check=true;
                        $pin_dropoff=0;
                        while ($check==true) {
                            $pin_dropoff=mt_rand(100000,999999);
                            $check_for_pin=SprintTasks::where('pin', $pin_dropoff)->where('type', 'dropoff')->first();
                            if(empty($check_for_pin)){
                                $check=false;
                            }
                        }
                        $sprint_task_dropoff_data['pin']=$pin_dropoff;
                    }
                    $sprint_task_dropoff=SprintTasks::create($sprint_task_dropoff_data);
                    $sprint_task_dropoff_id=$sprint_task_dropoff->id;
                    SprintTaskHistory::insert(['created_at'=>date("Y-m-d H:i:s"),'date'=>date('Y-m-d H:i:s'),'sprint__tasks_id'=>$sprint_task_dropoff_id,'sprint_id'=>$sprint_id,'status_id'=>38,'active'=>1,'date'=>date('Y-m-d H:i:s'),'created_at'=>date('Y-m-d H:i:s')]);

                    // save sprint confirmation
                    $ordinal=0;
                    $sprint_confirmation_data['task_id']=$sprint_task_dropoff_id;
                    if($data['confirm_pin']==1){
                        $ordinal+=1;
                        $sprint_confirmation_data['name']="confirm pin";
                        $sprint_confirmation_data['title']="Confirm Pin";
                        $sprint_confirmation_data['input_type']="text/plain";
                        $sprint_confirmation_data['ordinal']=$ordinal;
                        SprintConfirmation::create($sprint_confirmation_data);
                    }
                    if($data['confirm_image']==1){
                        $ordinal+=1;
                        $sprint_confirmation_data['name']="confirm image";
                        $sprint_confirmation_data['title']="Confirm Image";
                        $sprint_confirmation_data['input_type']="image/jpeg";
                        $sprint_confirmation_data['ordinal']=$ordinal;
                        SprintConfirmation::create($sprint_confirmation_data);
                    }
                    if($data['confirm_signature']==1){
                        $ordinal+=1;
                        $sprint_confirmation_data['name']="confirm signature";
                        $sprint_confirmation_data['title']="Confirm Signature";
                        $sprint_confirmation_data['input_type']="image/jpeg";
                        $sprint_confirmation_data['ordinal']=$ordinal;
                        SprintConfirmation::create($sprint_confirmation_data);
                    }
                    if($data['confirm_seal']==1){
                        $ordinal+=1;
                        $sprint_confirmation_data['name']="confirm seal";
                        $sprint_confirmation_data['title']="Confirm Seal";
                        $sprint_confirmation_data['input_type']="image/jpeg";
                        $sprint_confirmation_data['ordinal']=$ordinal;
                        SprintConfirmation::create($sprint_confirmation_data);
                    }
                    //default for dropoff
                    if ($ordinal==0) {
                        $sprint_confirmation_default_data['task_id']=$sprint_task_dropoff_id;
                        $sprint_confirmation_default_data['name']="default";
                        $sprint_confirmation_default_data['title']="Confirm Dropoff";
                        SprintConfirmation::create($sprint_confirmation_default_data);
                    }
                    //default for pickup
                    $sprint_confirmation_default_data['task_id']=$sprint_task_pickup_id;
                    $sprint_confirmation_default_data['name']="default";
                    $sprint_confirmation_default_data['title']="Confirm Pickup";
                    SprintConfirmation::create($sprint_confirmation_default_data);

                    // save merchant id data
                    $merchantid_data['task_id']=$sprint_task_dropoff_id; //task_id for dropoff
                    $merchantid_data['merchant_order_num']=$data['sprint_merchant_order_num'];
                    $merchantid_data['end_time']=$data['sprint_end_time'];
                    $merchantid_data['start_time']=$data['sprint_start_time'];
                    $merchantid_data['tracking_id']=$data['sprint_tracking_id'];
                    $merchantid_data['address_line2']=$data['location_address_line2'];
                    $merchantid=MerchantsIds::create($merchantid_data);

                    $sprint['last_task_id']=$sprint_task_dropoff_id;
                    $sprint['optimize_route']=1;
                    $sprint['status_id']=61;
                    $sprint['active']=1;
                    $sprint['timezone']=$city_data->timezone??'America/Toronto';
                    $sprint['push_at']=date("Y-m-d H:i:s", substr(($data['sprint_duetime']-(0.5*60*60)), 0, 10));
                    $sprint['distance']=$this->getDistanceBetweenPoints($to['lat'],$to['lng'],$from['lat'],$from['lng']);
                    $sprint['checked_out_at']=date('Y-m-d H:i:s');
                    $sprint->save();
                    DB::table('sprint__sprints_history')->insert(['sprint__sprints_id'=>$sprint_id,'vehicle_id'=>$sprint->vehicle_id,'distance'=>$sprint['distance'],'status_id'=>61,'active'=>1,'optimize_route'=>1,'date'=>date('Y-m-d H:i:s'),'created_at'=>date('Y-m-d H:i:s')]);


                    // update sprint task
                    SprintTasks::where('id',$sprint_task_pickup_id)->update(['status_id' => 61]);
                    SprintTaskHistory::insert(['created_at'=>date("Y-m-d H:i:s"),'date'=>date('Y-m-d H:i:s'),'sprint__tasks_id'=>$sprint_task_pickup_id,'sprint_id'=>$sprint_id,'status_id'=>61,'active'=>1]);
                    SprintTasks::where('id',$sprint_task_dropoff_id)->update(['status_id' => 61]);
                    SprintTaskHistory::insert(['created_at'=>date("Y-m-d H:i:s"),'date'=>date('Y-m-d H:i:s'),'sprint__tasks_id'=>$sprint_task_dropoff_id,'sprint_id'=>$sprint_id,'status_id'=>61,'active'=>1]);

                    $this->addToDispatch($sprint,['sprint_duetime'=> $data['sprint_duetime'],'copy'=> $data['copy']]);
                }
                else{
                    $sprint=$merchant->taskids->sprintsSprints;
                    // print_r($sprint);
                }
                //response
                // die;
                $response=  new CreateOrderResource($sprint);

                DB::commit();
            }catch (\Exception $e) {
                DB::rollback();
                return RestAPI::response($e->getMessage(), false, 'error_exception');
            }
        }
        return RestAPI::responseForCreateOrder($response, true, 'Order Created');
    }
    public function createCTC(Request $request)
    {
        $data_input = $request->all();
        $validation_data=$data_input;
        if (json_last_error() != JSON_ERROR_NONE) {
            return RestAPI::response("Json request is not valid / ".json_last_error_msg(), false, json_last_error());
        }
        $datetime=time() + (0.5*60*60);
        // validation=====================================================================
        /**
         *Location
         */
        $location_rules =new CreateOrderLocationRequest;
        $location_validator = Validator::make($validation_data['location'], $location_rules->rules());


        if (!$location_validator->passes()) {
            return RestAPI::response($location_validator->errors()->all(), false, 'Validation Error');
        }
        /**
         *Sprint
         */

        $sprint_rules_wm=new CreateOrderCTCRequest;
        $sprint_validator = Validator::make($validation_data['sprint'], $sprint_rules_wm->rules());

        if (!$sprint_validator->passes()) {
            $sprintValidationErrors=$sprint_validator->errors()->all();

            foreach($sprintValidationErrors as $key=>$value){
                // if(in_array("The end time must be a date after start time.", $sprint_validator->errors()->all())) {
                if($value=="The end time must be a date after start time.") {
                    $sprintValidationErrors[$key]='End time should be greater than start time.';
                }
            }
            return RestAPI::response($sprintValidationErrors, false, 'Validation Error');
        }
        // check creator_id for hub
        $check_hub=$this->creatorIdForHub($validation_data['sprint']['creator_id']);
        if($check_hub==0){return RestAPI::response("Hub doesn't exists for this Creator Id.", false, 'Validation Error');}

        // $check_hub
        $hub=Hub::find($check_hub);
        if(empty($hub)){return RestAPI::response("Hub doesn't exists.", false, 'Validation Error');}
        /**
         *Contact
         */
        $contact_rules = new CreateOrderContactRequest;
        $contact_validator = Validator::make($validation_data['contact'], $contact_rules->rules());


        if (!$contact_validator->passes()) {
            return RestAPI::response($contact_validator->errors()->all(), false, 'Validation Error');
        }
        /**
         *Payment
         */

        if(isset($validation_data['payment'])){
            $payment_rules =new CreateOrderPaymentRequest;
            $payment_validator = Validator::make($validation_data['payment'], $payment_rules->rules());


            if (!$payment_validator->passes()) {
                return RestAPI::response($payment_validator->errors()->all(), false, 'Validation Error');
            }
            unset($validation_data['payment']);
        }


        unset($validation_data['location']);unset($validation_data['sprint']);unset($validation_data['contact']);

        /**
         *Others
         */

        $other_rules=new CreateOrderOtherRequest;
        $other_validator = Validator::make($validation_data, $other_rules->rules());


        if (!$other_validator->passes()) {
            return RestAPI::response($other_validator->errors()->all(), false, 'Validation Error');
        }



        // check address
        // $checkaddress=$this->checkaddress($data_input['location']['address']);
        // check postal code
        // $checkpostalcode=$this->checkpostalcode($data_input['location']['postal_code']);
        $checkaddress=$this->loblaws_google_address($data_input['location']['address'],$data_input['location']['postal_code']);
        //check postal code and address(valid and for canada only)
        if($checkaddress['status']==0){return RestAPI::response('Invalid address/Postal code', false, 'Validation Error');}
        // validation=====================================================================
        else{



            $data=[];
            // sprint
            $data['sprint_creator_id'] =$data_input['sprint']['creator_id']; //req
            if($data_input['sprint']['due_time']>=$datetime){$data['sprint_duetime'] =$data_input['sprint']['due_time'];}//req
            else{$data['sprint_duetime'] =time();}//req
            $data['sprint_vehicle_id'] =$data_input['sprint']['vehicle_id'] ; //req
            $data['sprint_tracking_id'] =$data_input['sprint']['tracking_id']??null ;
            $data['sprint_merchant_order_num'] =$data_input['sprint']['merchant_order_num']??null ;
            $data['sprint_start_time'] =$data_input['sprint']['start_time']??null ;
            $data['sprint_end_time'] =$data_input['sprint']['end_time']??null ;
            $data['sprint_tip'] =$data_input['sprint']['tip']??null ;


            // contact
            $data['contact_name'] =$data_input['contact']['name'] ; //req
            $data['contact_email'] =$data_input['contact']['email']??null ;
            $data['contact_phone'] =$data_input['contact']['phone']??null ;

            // location
            $data['location_address'] =$data_input['location']['address'] ; //req
            $data['location_postal_code'] =$data_input['location']['postal_code'] ; //req
            $data['location_address_line2'] =$data_input['location']['address_line2'] ??null;
            $data['location_pickup_buzzer'] =$data_input['location']['pickup_buzzer']??null ;
            $data['location_buzzer'] =$data_input['location']['buzzer'] ??null;

            // payment
            $data['payment_type'] =$data_input['payment']['type']??null ;
            $data['payment_amount'] =$data_input['payment']['amount'] ??null;

            // description
            $data['copy'] =$data_input['copy'] ??null;

            // notification method
            $data['notification_method'] =$data_input['notification_method'] ; //req


            DB::beginTransaction();
            try {
                //get address data
                // $contact_address=$this->loblaws_google_address($data['location_address'],$data['location_postal_code']);
                $contact_address=$checkaddress;

                $city_data=City::where('name',$contact_address['city'])->first(); //get city data

                $merchant=[];

                if($data['sprint_merchant_order_num']!=null){$merchant=MerchantsIds::where('merchant_order_num',$data['sprint_merchant_order_num'])->orderBy('created_at',"desc")->first();}


                if(empty($merchant))
                {


                    //save location for contact/drop off
                    $location_data['buzzer']=$data['location_buzzer'];
                    $location_data['postal_code']=$contact_address['postal_code'];
                    $location_data['latitude']=str_replace('.','',$contact_address['lat']);
                    $location_data['longitude']=str_replace('.','',$contact_address['lng']);
                    $location_data['address']=$contact_address['street_number'].' '.$contact_address['route'];
                    $location_data['city_id']=$city_data->id;
                    $location_data['state_id']=$city_data->state_id;
                    $location_data['country_id']=$city_data->country_id;
                    $location=Location::create($location_data);
                    $location_id=$location->id;

                    $this->insertLocationEnc($location_id,$location_data);


                    //sprint contact save in sprint contact
                    $contact_data['name']=$data['contact_name'];
                    $contact_data['phone']=$data['contact_phone'];
                    $contact_data['email']=$data['contact_email'];
                    $contact=SprintContact::create($contact_data);
                    $contact_id=$contact->id;
                    $this->insertContactEnc($contact_id,$contact_data);


                    // sprint save
                    $sprint_data['creator_id']=$data['sprint_creator_id'];
                    $sprint_data['creator_type']='vendor';
                    $sprint_data['vehicle_id']=$data['sprint_vehicle_id'];
                    $sprint_data['status_id']=38;
                    $sprint_data['tip']=$data['sprint_tip'];
                    if($data['payment_type']=='make'){$sprint_data['make_payment_total']=$data['payment_amount'];}elseif($data['payment_type']=='collect'){$sprint_data['collect_payment_total']=$data['payment_amount'];}
                    $sprint=Sprint::create($sprint_data);
                    $sprint_id=$sprint->id;
                    DB::table('sprint__sprints_history')->insert(['sprint__sprints_id'=>$sprint_id,'vehicle_id'=>$sprint->vehicle_id,'distance'=>$sprint->distance,'status_id'=>$sprint->status_id,'active'=>1,'optimize_route'=>1,'date'=>date('Y-m-d H:i:s'),'created_at'=>date('Y-m-d H:i:s')]);



                    //save vendor as contact for vendor contact id
                    $vendor_contact_data['name']=$sprint->vendor->first_name.' '.$sprint->vendor->last_name;
                    $vendor_contact_data['phone']=$sprint->vendor->phone;
                    $vendor_contact_data['email']=$sprint->vendor->email;
                    $vendor_contact=SprintContact::create($vendor_contact_data);
                    $vendor_contact_id=$vendor_contact->id;
                    $this->insertContactEnc($vendor_contact_id,$vendor_contact_data);


                    // then create 4 tasks in sprint task for drop off and pickup (from vendor to hub and then from hub to customer)
                    //sprint tasks save type=pickup from vendor ordinal 1 ==================================================================================
                    $sprint_task_pickup_data['sprint_id']=$sprint_id;
                    $sprint_task_pickup_data['type']='pickup';
                    $sprint_task_pickup_data['ordinal']=1;
                    $sprint_task_pickup_data['due_time']=$data['sprint_duetime'];
                    $sprint_task_pickup_data['eta_time']=$data['sprint_duetime'];
                    $sprint_task_pickup_data['etc_time']=$data['sprint_duetime'] + (0.25*60*60);//adding 15 mins
                    $sprint_task_pickup_data['location_id']=$sprint->vendor->location_id;//vendors table location id
                    $sprint_task_pickup_data['contact_id']=$vendor_contact_id;
                    $sprint_task_pickup_data['status_id']=38;
                    $sprint_task_pickup_data['active']=1;
                    $sprint_task_pickup=SprintTasks::create($sprint_task_pickup_data);
                    $sprint_task_pickup_id=$sprint_task_pickup->id;
                    SprintTaskHistory::insert(['created_at'=>date("Y-m-d H:i:s"),'date'=>date('Y-m-d H:i:s'),'sprint__tasks_id'=>$sprint_task_pickup_id,'sprint_id'=>$sprint_id,'status_id'=>38,'active'=>1]);

                    // drop of to hub ordinal 2===============================================================================================================
                    // $hub;
                    // $contact_address_to_hub=$this->checkaddress($hub['address']);
                    //save location of hub for location id
                    // $location_hub_data['postal_code']=$contact_address_to_hub['postal_code'];
                    // $location_hub_data['latitude']=str_replace('.','',$contact_address_to_hub['lat']);
                    // $location_hub_data['longitude']=str_replace('.','',$contact_address_to_hub['lng']);
                    // $location_hub_data['address']=$contact_address_to_hub['street_number'].' '.$contact_address_to_hub['route'];

                    $location_hub_data['postal_code']=$hub['postal__code'];
                    $location_hub_data['latitude']=str_replace('.','',$hub['hub_latitude']);
                    $location_hub_data['longitude']=str_replace('.','',$hub['hub_longitude']);
                    $location_hub_data['address']=$hub['address'];

                    // $city_hub_data=City::where('name',$contact_address_to_hub['city'])->first();

                    // if(empty($city_hub_data)){return RestAPI::response('City not found', false, 'DB Error');}
                    // print_r($contact_address_to_hub['city']);die;
                    $location_hub_data['city_id']=$hub['city__id'];
                    $location_hub_data['state_id']=$hub['state__id'];
                    $location_hub_data['country_id']=$hub['country__id'];
                    $location_hub=Location::create($location_hub_data);
                    $location_hub_id=$location_hub->id;

                    $this->insertLocationEnc($location_hub_id,$location_hub_data);


                    //save hub as contact for contact id
                    $contact_hub_data['name']=$hub['title'];
                    $contact_hub=SprintContact::create($contact_hub_data);
                    $contact_hub_id=$contact_hub->id;

                    $this->insertContactEnc($contact_hub_id,$contact_hub_data);




                    //time difference from vendor to hub

                    $from_vendor['name']=$sprint->vendor->location->address;
                    $from_vendor['lat']=(float)($sprint->vendor->location->latitude/1000000);
                    $from_vendor['lng']=(float)($sprint->vendor->location->longitude/1000000);
                    $to_hub['name']=$location_hub_data['address'];
                    $to_hub['lat']=$location_hub_data['latitude']/1000000;
                    $to_hub['lng']=$location_hub_data['longitude']/1000000;
                    $time_difference_to_hub= $this->gettimedifference($from_vendor,$to_hub);

                    if(isset($time_difference_to_hub['status'])){return RestAPI::response($time_difference_to_hub['error'], false, $time_difference_to_hub['error_type']);}



                    $sprint_task_dropoff_hub_data['sprint_id']=$sprint_id;
                    $sprint_task_dropoff_hub_data['type']='dropoff';
                    $sprint_task_dropoff_hub_data['ordinal']=2;
                    $sprint_task_dropoff_hub_data['due_time']=$data['sprint_duetime'];
                    $sprint_task_dropoff_hub_data['eta_time']=$data['sprint_duetime']+($time_difference_to_hub * 60 * 1000);//add time difference between two points
                    $sprint_task_dropoff_hub_data['etc_time']=$sprint_task_dropoff_hub_data['eta_time'] + (0.25*60*60);//adding 15 mins
                    $sprint_task_dropoff_hub_data['location_id']=$location_hub_id;//vendors table location id
                    $sprint_task_dropoff_hub_data['contact_id']=$contact_hub_id;
                    // echo $contact_hub_id;die;
                    $sprint_task_dropoff_hub_data['status_id']=38;
                    $sprint_task_dropoff_hub_data['active']=1;
                    $sprint_task_dropoff_hub=SprintTasks::create($sprint_task_dropoff_hub_data);
                    $sprint_task_dropoff_hub_id=$sprint_task_dropoff_hub->id;
                    SprintTaskHistory::insert(['created_at'=>date("Y-m-d H:i:s"),'date'=>date('Y-m-d H:i:s'),'sprint__tasks_id'=>$sprint_task_dropoff_hub_id,'sprint_id'=>$sprint_id,'status_id'=>38,'active'=>1,'date'=>date('Y-m-d H:i:s'),'created_at'=>date('Y-m-d H:i:s')]);

                    $sprint['distance_charge']=$this->getDistanceCharge($sprint_task_dropoff_hub);



                    // pickup from hub ordinal 3=================================================================================================================

                    $sprint_task_pickup_from_hub_data['sprint_id']=$sprint_id;
                    $sprint_task_pickup_from_hub_data['type']='pickup';
                    $sprint_task_pickup_from_hub_data['ordinal']=3;
                    $sprint_task_pickup_from_hub_data['due_time']=$data['sprint_duetime'];
                    $sprint_task_pickup_from_hub_data['eta_time']=0;
                    $sprint_task_pickup_from_hub_data['etc_time']=$sprint_task_dropoff_hub_data['etc_time'] + (0.25*60*60);//adding 15 mins
                    $sprint_task_pickup_from_hub_data['location_id']=$location_hub_id;//vendors table location id
                    $sprint_task_pickup_from_hub_data['contact_id']=$contact_hub_id;
                    $sprint_task_pickup_from_hub_data['status_id']=38;
                    $sprint_task_pickup_from_hub_data['active']=1;
                    $sprint_task_pickup_from_hub=SprintTasks::create($sprint_task_pickup_from_hub_data);
                    $sprint_task_pickup_from_hub_id=$sprint_task_pickup_from_hub->id;
                    SprintTaskHistory::insert(['created_at'=>date("Y-m-d H:i:s"),'date'=>date('Y-m-d H:i:s'),'sprint__tasks_id'=>$sprint_task_pickup_from_hub_id,'sprint_id'=>$sprint_id,'status_id'=>38,'active'=>1]);


                    //sprint tasks save type=dropoff to customer ordinal 4=====================================================================================

                    //get time difference
                    $from_hub['name']= $to_hub['name'];
                    $from_hub['lat']=$to_hub['lat'];
                    $from_hub['lng']=$to_hub['lng'];
                    $to_customer['name']=$location_data['address'];
                    $to_customer['lat']=$contact_address['lat'];
                    $to_customer['lng']=$contact_address['lng'];
                    //     print_r($from_hub);
                    //     print_r($to_customer);
                    //  die;
                    $time_difference= $this->gettimedifference($from_hub,$to_customer);
                    if(isset($time_difference['status'])){return RestAPI::response($time_difference['error'], false, $time_difference['error_type']);}

                    $sprint_task_dropoff_data['sprint_id']=$sprint_id;
                    $sprint_task_dropoff_data['type']='dropoff';
                    $sprint_task_dropoff_data['ordinal']=4;
                    $sprint_task_dropoff_data['due_time']=$data['sprint_duetime'];
                    $sprint_task_dropoff_data['eta_time']= $sprint_task_pickup_from_hub_data['etc_time']+($time_difference * 60 * 1000);//add time difference between two points
                    $sprint_task_dropoff_data['etc_time']=$sprint_task_dropoff_data['eta_time'] + (0.25*60*60);//adding 15 mins
                    $sprint_task_dropoff_data['location_id']=$location_id;//vendors table location id
                    $sprint_task_dropoff_data['contact_id']=$contact_id;
                    $sprint_task_dropoff_data['status_id']=38;
                    $sprint_task_dropoff_data['active']=1;
                    $sprint_task_dropoff_data['notify_by']=$data['notification_method'];
                    $sprint_task_dropoff_data['payment_type']=$data['payment_type'];
                    $sprint_task_dropoff_data['payment_amount']=$data['payment_amount'];
                    $sprint_task_dropoff_data['description']=$data['copy'];
                    $sprint_task_dropoff=SprintTasks::create($sprint_task_dropoff_data);
                    $sprint_task_dropoff_id=$sprint_task_dropoff->id;
                    SprintTaskHistory::insert(['created_at'=>date("Y-m-d H:i:s"),'date'=>date('Y-m-d H:i:s'),'sprint__tasks_id'=>$sprint_task_dropoff_id,'sprint_id'=>$sprint_id,'status_id'=>38,'active'=>1,'date'=>date('Y-m-d H:i:s'),'created_at'=>date('Y-m-d H:i:s')]);

                    $sprint['distance_charge']+=$this->getDistanceCharge($sprint_task_dropoff);

                    // save sprint confirmation
                    $sprint_confirmation_default_data=[];
                    //default for pickup from vendor
                    $sprint_confirmation_default_data['task_id']=$sprint_task_pickup_id;
                    $sprint_confirmation_default_data['name']="default";
                    $sprint_confirmation_default_data['title']="Confirm Pickup";
                    SprintConfirmation::create($sprint_confirmation_default_data);

                    //  //default for pickup from hub
                    $sprint_confirmation_default_data['task_id']=$sprint_task_pickup_from_hub_id;
                    $sprint_confirmation_default_data['name']="default";
                    $sprint_confirmation_default_data['title']="Confirm Pickup";
                    SprintConfirmation::create($sprint_confirmation_default_data);


                    // //default for dropoff to hub

                    $sprint_confirmation_default_data['task_id']=$sprint_task_dropoff_hub_id;
                    $sprint_confirmation_default_data['name']="default";
                    $sprint_confirmation_default_data['title']="Confirm Dropoff";
                    SprintConfirmation::create($sprint_confirmation_default_data);

                    // //default for dropoff to customer
                    $sprint_confirmation_default_data['task_id']=$sprint_task_dropoff_id;
                    $sprint_confirmation_default_data['name']="default";
                    $sprint_confirmation_default_data['title']="Confirm Dropoff";
                    SprintConfirmation::create($sprint_confirmation_default_data);


                    // save merchant id data
                    $merchantid_data['task_id']=$sprint_task_dropoff_id; //task_id for dropoff
                    $merchantid_data['merchant_order_num']=$data['sprint_merchant_order_num'];
                    $merchantid_data['end_time']=$data['sprint_end_time'];
                    $merchantid_data['start_time']=$data['sprint_start_time'];
                    $merchantid_data['tracking_id']=$data['sprint_tracking_id'];
                    $merchantid_data['address_line2']=$data['location_address_line2'];
                    $merchantid=MerchantsIds::create($merchantid_data);

                    // echo 1;die;

                    $sprint['last_task_id']=$sprint_task_dropoff_id;
                    $sprint['optimize_route']=1;
                    $sprint['status_id']=61;
                    $sprint['active']=1;
                    $sprint['timezone']=$city_data->timezone??'America/Toronto';
                    $sprint['push_at']=date("Y-m-d H:i:s", substr(($data['sprint_duetime']-(0.5*60*60)), 0, 10));
                    $sprint['distance']=$this->getDistanceBetweenPoints($from_vendor['lat'],$from_vendor['lng'],$to_customer['lat'],$to_customer['lng']);
                    $sprint['checked_out_at']=date('Y-m-d H:i:s');

                    $sprint->save();
                    DB::table('sprint__sprints_history')->insert(['sprint__sprints_id'=>$sprint_id,'vehicle_id'=>$sprint->vehicle_id,'distance'=>$sprint['distance'],'status_id'=>61,'active'=>1,'optimize_route'=>1,'date'=>date('Y-m-d H:i:s'),'created_at'=>date('Y-m-d H:i:s')]);


                    // update sprint task and add history( pick form vendor and drop to customer)
                    SprintTasks::where('id',$sprint_task_pickup_id)->update(['status_id' => 61]);
                    SprintTaskHistory::insert(['created_at'=>date("Y-m-d H:i:s"),'date'=>date('Y-m-d H:i:s'),'sprint__tasks_id'=>$sprint_task_pickup_id,'sprint_id'=>$sprint_id,'status_id'=>61,'active'=>1]);
                    SprintTasks::where('id',$sprint_task_dropoff_id)->update(['status_id' => 61]);
                    SprintTaskHistory::insert(['created_at'=>date("Y-m-d H:i:s"),'date'=>date('Y-m-d H:i:s'),'sprint__tasks_id'=>$sprint_task_dropoff_id,'sprint_id'=>$sprint_id,'status_id'=>61,'active'=>1]);
                    // update sprint task and add history(pick form hub and drop to hub)
                    SprintTasks::where('id',$sprint_task_pickup_from_hub_id)->update(['status_id' => 61]);
                    SprintTaskHistory::insert(['created_at'=>date("Y-m-d H:i:s"),'date'=>date('Y-m-d H:i:s'),'sprint__tasks_id'=>$sprint_task_pickup_from_hub_id,'sprint_id'=>$sprint_id,'status_id'=>61,'active'=>1]);
                    SprintTasks::where('id',$sprint_task_dropoff_hub_id)->update(['status_id' => 61]);
                    SprintTaskHistory::insert(['created_at'=>date("Y-m-d H:i:s"),'date'=>date('Y-m-d H:i:s'),'sprint__tasks_id'=>$sprint_task_dropoff_hub_id,'sprint_id'=>$sprint_id,'status_id'=>61,'active'=>1]);

                    $this->addToDispatch($sprint,['sprint_duetime'=> $data['sprint_duetime'],'copy'=> $data['copy']]);

                }
                else{

                    $sprint=$merchant->taskids->sprintsSprints;
                }
                //response
                // die;
                $response=  new CreateOrderResource($sprint);

                DB::commit();
            }catch (\Exception $e) {
                DB::rollback();
                return RestAPI::response($e->getMessage(), false, 'error_exception');
            }
        }
        return RestAPI::responseForCreateOrder($response, true, 'Order Created');
    }
    public function tasksCreation(Request $request)
    {
        // $time_start = microtime(true);

        $data_input = $request->all();
        $validation_data=$data_input;
        // checking is valid json
        if (json_last_error() != JSON_ERROR_NONE) {
            return RestAPI::response("Json request is not valid / ".json_last_error_msg(), false, json_last_error());
        }

        // $datetime=time() + (0.5*60*60);
        // validation=====================================================================
        if(!isset($validation_data['sprint']['type']) || $validation_data['sprint']['type']=="" || $validation_data['sprint']['type']==null ){return RestAPI::response('The type field in sprint is required.', false, 'Validation Error');}
        $location_rules =new CreateOrderLocationRequest;
        $location_validator = Validator::make($validation_data['location'], $location_rules->rules());

        if (!$location_validator->passes()) {
            return RestAPI::response($location_validator->errors()->all(), false, 'Validation Error');
        }

        /**
         *Sprint
         */
        $rules=[];
        if($validation_data['sprint']['type']=='pickup'){
            $rules=[
                'creator_id'=> 'required|exists:vendors,id',
                'vehicle_id'=> 'required|exists:vehicles,id',
            ];
        }
        elseif ($validation_data['sprint']['type']=='dropoff') {
            $rules=[
                "sprint_id"=> 'required|exists:sprint__sprints,id',
                "merchant_order_num" => "required|unique:merchantids,merchant_order_num",
                'start_time'=> 'nullable|required_with:end_time|date_format:H:i',
                'end_time'  => 'nullable|date_format:H:i|after:start_time',

            ];
        }
        elseif ($validation_data['sprint']['type']=='return') {
            $rules=[
                "sprint_id"=>'required|exists:sprint__sprints,id'
            ];
        }
        $rules['tip']='nullable|numeric|gt:0';

        $sprint_validator = Validator::make($validation_data['sprint'], $rules);
        // $sprint_rules_loblaws=new CreateOrderLoblawsRequest;
        // $sprint_validator = Validator::make($validation_data['sprint'], $sprint_rules_loblaws->rules());

        if (!$sprint_validator->passes()) {
            $sprintValidationErrors=$sprint_validator->errors()->all();
            foreach($sprintValidationErrors as $key=>$value){
                // if(in_array("The end time must be a date after start time.", $sprint_validator->errors()->all())) {
                if($value=="The end time must be a date after start time.") {
                    $sprintValidationErrors[$key]='End time should be greater than start time.';
                }
                // if (strpos($value, 'The due time must be greater than or equal') !== false) {
                //     $sprintValidationErrors[$key]='Due time must be half an hour from now!.';
                // }
            }
            return RestAPI::response($sprintValidationErrors, false, 'Validation Error');
        }
        /**
         *Contact
         */

        $contact_rules = new CreateOrderContactRequest;
        $contact_validator = Validator::make($validation_data['contact'], $contact_rules->rules());

        if (!$contact_validator->passes()) {
            return RestAPI::response($contact_validator->errors()->all(), false, 'Validation Error');
        }
        /**
         *Payment
         */
        if(isset($validation_data['payment'])){

            $payment_rules =new CreateOrderPaymentRequest;
            $payment_validator = Validator::make($validation_data['payment'], $payment_rules->rules());

            if (!$payment_validator->passes()) {
                return RestAPI::response($payment_validator->errors()->all(), false, 'Validation Error');
            }
            unset($validation_data['payment']);
        }
        unset($validation_data['location']);unset($validation_data['sprint']);unset($validation_data['contact']);
        /**
         *Others
         */

        $other_rules=new CreateOrderOtherRequest;
        $other_validator = Validator::make($validation_data, $other_rules->rules());

        if (!$other_validator->passes()) {
            return RestAPI::response($other_validator->errors()->all(), false, 'Validation Error');
        }


        // check address
        // $checkaddress=$this-> checkaddress($data_input['location']['address']);
        $checkaddress=$this->loblaws_google_address($data_input['location']['address'],$data_input['location']['postal_code']);
        // check postal code
        // $checkpostalcode=$this->checkpostalcode($data_input['location']['postal_code']);

        //check postal code and address(valid and for canada only)
        // if($checkaddress['status']==0 || $checkpostalcode==0 ){return RestAPI::response('Invalid address/Postal code', false, 'Validation Error');}

        if($checkaddress['status']==0){return RestAPI::response('Invalid address/Postal code', false, 'Validation Error');}

        // validation=====================================================================
        else{
            $data=[];
            // sprint
            if($data_input['sprint']['type']=='pickup'){
                $data['sprint_creator_id'] =$data_input['sprint']['creator_id'];//req
                $data['sprint_vehicle_id'] =$data_input['sprint']['vehicle_id'];//req

            }
            elseif ($data_input['sprint']['type']=='dropoff') {
                $data['sprint_id'] =$data_input['sprint']['sprint_id'] ;//req
                $data['sprint_merchant_order_num'] =$data_input['sprint']['merchant_order_num'] ;//req
                $data['sprint_start_time'] =$data_input['sprint']['start_time']??null ;
                $data['sprint_end_time'] =$data_input['sprint']['end_time']??null ;

            }
            elseif ($data_input['sprint']['type']=='return') {
                $data['sprint_id'] =$data_input['sprint']['sprint_id'] ;//req
                // $sprint_status=18;
            }
            $data['sprint_tip'] =$data_input['sprint']['tip']??null ;

            // contact
            $data['contact_name'] =$data_input['contact']['name'] ; //req
            $data['contact_email'] =$data_input['contact']['email']??null ;
            $data['contact_phone'] =$data_input['contact']['phone']??null ;

            // location
            $data['location_address'] =$data_input['location']['address'] ; //req
            $data['location_postal_code'] =$data_input['location']['postal_code'] ; //req
            $data['location_address_line2'] =$data_input['location']['address_line2'] ??null;
            $data['location_pickup_buzzer'] =$data_input['location']['pickup_buzzer']??null ;
            $data['location_buzzer'] =$data_input['location']['buzzer'] ??null;

            // payment
            $data['payment_type'] =$data_input['payment']['type']??null ;
            $data['payment_amount'] =$data_input['payment']['amount'] ??null;

            // description
            $data['copy'] =$data_input['copy'] ??null;

            // notification method
            $data['notification_method'] =$data_input['notification_method'] ; //req

            // confirmation
            if(!isset($data_input['confirm_signature'])){
                $data_input['confirm_signature']=0;
            }
            if(!isset($data_input['confirm_pin'])){
                $data_input['confirm_pin']=0;
            }
            if(!isset($data_input['confirm_image'])){
                $data_input['confirm_image']=0;
            }
            if(!isset($data_input['confirm_seal'])){
                $data_input['confirm_seal']=0;
            }
            $data['confirm_signature'] =$data_input['confirm_signature'] ;
            $data['confirm_pin'] =$data_input['confirm_pin'] ;
            $data['confirm_image'] =$data_input['confirm_image'] ;
            $data['confirm_seal'] =$data_input['confirm_seal'] ;

            $dispatch=false;

            // echo 3;die;
            DB::beginTransaction();
            try {
                //get address data
                // $contact_address=$this->loblaws_google_address($data['location_address'],$data['location_postal_code']);
                $contact_address=$checkaddress;

                $city_data=City::where('name',$contact_address['city'])->first(); //get city data

                $data['sprint_duetime']=time();


                // echo 1;die;
                //save location for contact/drop off
                $location_data['buzzer']=$data['location_buzzer'];
                $location_data['postal_code']=$contact_address['postal_code'];
                $location_data['latitude']=substr(str_replace('.','',$contact_address['lat']),0,8);
                $location_data['longitude']=substr(str_replace('.','',$contact_address['lng']),0,9);
                $location_data['address']=$contact_address['street_number'].' '.$contact_address['route'];
                $location_data['city_id']=$city_data->id;
                $location_data['state_id']=$city_data->state_id;
                $location_data['country_id']=$city_data->country_id;
                // print_r( $location_data);die;
                $location=Location::create($location_data);
                $location_id=$location->id;
                $this->insertLocationEnc($location_id,$location_data);


                //sprint contact save in sprint contact
                $contact_data['name']=$data['contact_name'];
                $contact_data['phone']=$data['contact_phone'];
                $contact_data['email']=$data['contact_email'];
                $contact=SprintContact::create($contact_data);
                $contact_id=$contact->id;

                $this->insertContactEnc($contact_id,$contact_data);



                $sprint_ordinal=0;
                if($data_input['sprint']['type']=='pickup') // sprint save
                {
                    $sprint_data['creator_id']=$data['sprint_creator_id'];
                    $sprint_data['creator_type']='vendor';
                    $sprint_data['vehicle_id']=$data['sprint_vehicle_id'];
                    $sprint_data['status_id']=38;
                    $sprint_data['tip']=$data['sprint_tip'];
                    if($data['payment_type']=='make'){$sprint_data['make_payment_total']=$data['payment_amount'];}elseif($data['payment_type']=='collect'){$sprint_data['collect_payment_total']=$data['payment_amount'];}
                    $sprint=Sprint::create($sprint_data);
                    $sprint_task_dropoff_data['eta_time']=$data['sprint_duetime'];

                }
                else{
                    $sprint=Sprint::where("id",$data_input['sprint']['sprint_id'])->first();
                    $sprint_ordinal=count($sprint->sprintTask);
                    $from['name']=$sprint->sprintLastTask->Location->address;
                    $from['lat']=(float)($sprint->sprintLastTask->Location->latitude/1000000);
                    $from['lng']=(float)($sprint->sprintLastTask->Location->longitude/1000000);
                    $to['name']=$location_data['address'];
                    $to['lat']=$contact_address['lat'];
                    $to['lng']=$contact_address['lng'];
                    //get time difference
                    $time_difference= $this->gettimedifference($from,$to);
                    // print_r($from);die;
                    if(isset($time_difference['status'])){return RestAPI::response($time_difference['error'], false, $time_difference['error_type']);}
                    $sprint_task_dropoff_data['eta_time']=$data['sprint_duetime']+($time_difference * 60 * 1000);
                    if($data_input['sprint']['type']=='dropoff'){
                        if($sprint->sprintFirstDropoffTask==null){
                            $dispatch=true;
                        }
                    }

                }
                $sprint_id=$sprint->id;
                DB::table('sprint__sprints_history')->insert(['sprint__sprints_id'=>$sprint_id,'vehicle_id'=>$sprint->vehicle_id,'distance'=>$sprint->distance,'status_id'=>$sprint->status_id,'active'=>1,'optimize_route'=>1,'date'=>date('Y-m-d H:i:s'),'created_at'=>date('Y-m-d H:i:s')]);



                //sprint tasks save type=dropoff
                $dropoff_charge=$this->getDropoffCharge($sprint->creator_id,1,$sprint->vehicle_id);
                $sprint_task_dropoff_data['sprint_id']=$sprint_id;
                $sprint_task_dropoff_data['type']=$data_input['sprint']['type'];
                $sprint_task_dropoff_data['charge']=$dropoff_charge;
                $sprint_task_dropoff_data['ordinal']=($sprint_ordinal+1);
                $sprint_task_dropoff_data['due_time']=$data['sprint_duetime'];
                // if($data_input['sprint']['type']=="return" || $data_input['sprint']['type']=="dropoff"){$sprint_task_dropoff_data['eta_time']=$data['sprint_duetime']+($time_difference * 60 * 1000);}
                // $sprint_task_dropoff_data['eta_time']=$data['sprint_duetime']+($time_difference * 60 * 1000);//add time difference between two points
                $sprint_task_dropoff_data['etc_time']=$sprint_task_dropoff_data['eta_time'] + (0.25*60*60);//adding 15 mins
                $sprint_task_dropoff_data['location_id']=$location_id;//vendors table location id
                $sprint_task_dropoff_data['contact_id']=$contact_id;
                $sprint_task_dropoff_data['status_id']=38;
                $sprint_task_dropoff_data['active']=1;
                $sprint_task_dropoff_data['notify_by']=$data['notification_method'];
                $sprint_task_dropoff_data['payment_type']=$data['payment_type'];
                $sprint_task_dropoff_data['payment_amount']=$data['payment_amount'];
                $sprint_task_dropoff_data['description']=$data['copy'];
                $sprint_task_dropoff_data['confirm_image']=$data['confirm_image'];
                $sprint_task_dropoff_data['confirm_signature']=$data['confirm_signature'];
                $sprint_task_dropoff_data['confirm_pin']=$data['confirm_pin'];
                $sprint_task_dropoff_data['confirm_seal']=$data['confirm_seal'];
                if($data['confirm_pin']==1){
                    $check=true;
                    $pin_dropoff=0;
                    while ($check==true) {
                        $pin_dropoff=mt_rand(100000,999999);
                        $check_for_pin=SprintTasks::where('pin', $pin_dropoff)->where('type', 'dropoff')->first();
                        if(empty($check_for_pin)){
                            $check=false;
                        }
                    }
                    $sprint_task_dropoff_data['pin']=$pin_dropoff;
                }
                $sprint_task_dropoff=SprintTasks::create($sprint_task_dropoff_data);
                $sprint_task_dropoff_id=$sprint_task_dropoff->id;
                SprintTaskHistory::insert(['created_at'=>date("Y-m-d H:i:s"),'date'=>date('Y-m-d H:i:s'),'sprint__tasks_id'=>$sprint_task_dropoff_id,'sprint_id'=>$sprint_id,'status_id'=>38,'active'=>1,'date'=>date('Y-m-d H:i:s'),'created_at'=>date('Y-m-d H:i:s')]);

                // save sprint confirmation
                $ordinal=0;
                $sprint_confirmation_data['task_id']=$sprint_task_dropoff_id;
                if($data['confirm_pin']==1){
                    $ordinal+=1;
                    $sprint_confirmation_data['name']="confirm pin";
                    $sprint_confirmation_data['title']="Confirm Pin";
                    $sprint_confirmation_data['input_type']="text/plain";
                    $sprint_confirmation_data['ordinal']=$ordinal;
                    SprintConfirmation::create($sprint_confirmation_data);
                }
                if($data['confirm_image']==1){
                    $ordinal+=1;
                    $sprint_confirmation_data['name']="confirm image";
                    $sprint_confirmation_data['title']="Confirm Image";
                    $sprint_confirmation_data['input_type']="image/jpeg";
                    $sprint_confirmation_data['ordinal']=$ordinal;
                    SprintConfirmation::create($sprint_confirmation_data);
                }
                if($data['confirm_signature']==1){
                    $ordinal+=1;
                    $sprint_confirmation_data['name']="confirm signature";
                    $sprint_confirmation_data['title']="Confirm Signature";
                    $sprint_confirmation_data['input_type']="image/jpeg";
                    $sprint_confirmation_data['ordinal']=$ordinal;
                    SprintConfirmation::create($sprint_confirmation_data);
                }
                if($data['confirm_seal']==1){
                    $ordinal+=1;
                    $sprint_confirmation_data['name']="confirm seal";
                    $sprint_confirmation_data['title']="Confirm Seal";
                    $sprint_confirmation_data['input_type']="image/jpeg";
                    $sprint_confirmation_data['ordinal']=$ordinal;
                    SprintConfirmation::create($sprint_confirmation_data);
                }
                //default for dropoff
                if ($ordinal==0 && $data_input['sprint']['type']=='dropoff') {
                    $sprint_confirmation_default_data['task_id']=$sprint_task_dropoff_id;
                    $sprint_confirmation_default_data['name']="default";
                    $sprint_confirmation_default_data['title']="Confirm Dropoff";
                    SprintConfirmation::create($sprint_confirmation_default_data);
                }
                //default for pickup
                if($data_input['sprint']['type']=='pickup'){
                    $sprint_confirmation_default_data['task_id']=$sprint_task_dropoff_id;
                    $sprint_confirmation_default_data['name']="default";
                    $sprint_confirmation_default_data['title']="Confirm Pickup";
                    SprintConfirmation::create($sprint_confirmation_default_data);
                }

                if($data_input['sprint']['type']=='return'){
                    $sprint_confirmation_default_data['task_id']=$sprint_task_dropoff_id;
                    $sprint_confirmation_default_data['name']="default";
                    $sprint_confirmation_default_data['title']="Confirm Return";
                    SprintConfirmation::create($sprint_confirmation_default_data);
                }

                // save merchant id data

                if($data_input['sprint']['type']=='dropoff'){
                    $merchantid_data['task_id']=$sprint_task_dropoff_id; //task_id for dropoff
                    $merchantid_data['merchant_order_num']=$data['sprint_merchant_order_num'];
                    $merchantid_data['end_time']=$data['sprint_end_time'];
                    $merchantid_data['start_time']=$data['sprint_start_time'];
                    // $merchantid_data['tracking_id']=$data['sprint_tracking_id'];
                    $merchantid_data['address_line2']=$data['location_address_line2'];
                    $merchantid=MerchantsIds::create($merchantid_data);
                }

                $sprint['last_task_id']=$sprint_task_dropoff_id;
                if($data_input['sprint']['type']=='pickup')
                {

                    $sprint['optimize_route']=1;
                    // $sprint['status_id']=61;
                    $sprint['active']=1;
                    $sprint['timezone']=$city_data->timezone??'America/Toronto';
                    // $sprint['push_at']=date("Y-m-d H:i:s", substr(($data['sprint_duetime']-(0.5*60*60)), 0, 10));
                    // $sprint['checked_out_at']=date('Y-m-d H:i:s');
                    $sprint['distance']=0;
                }
                else{
                    if($data_input['sprint']['type']=='dropoff'){ //work for distance_charge in sprint
                        $current_distance_charge=(($sprint->distance_charge==null)?0:$sprint->distance_charge);
                        $sprint['distance_charge']=$current_distance_charge+$this->getDistanceCharge($sprint_task_dropoff);
                    }

                    $sprint['distance']=(($sprint->distance==null)?0:$sprint->distance)+($this->getDistanceBetweenPoints($to['lat'],$to['lng'],$from['lat'],$from['lng']));
                }

                $sprint->save();



                // print_r($sprint);die;

                // if($data_input['sprint']['type']=='pickup')
                // {
                //      DB::table('sprint__sprints_history')->insert(['sprint__sprints_id'=>$sprint_id,'vehicle_id'=>$sprint->vehicle_id,'distance'=>$sprint['distance'],'status_id'=>61,'active'=>1,'optimize_route'=>1,'date'=>date('Y-m-d H:i:s'),'created_at'=>date('Y-m-d H:i:s')]);
                // }
                // update sprint task
                SprintTasks::where('id',$sprint_task_dropoff_id)->update(['status_id' => ($data_input['sprint']['type']=='return')?18:61]);
                SprintTaskHistory::insert(['created_at'=>date("Y-m-d H:i:s"),'date'=>date('Y-m-d H:i:s'),'sprint__tasks_id'=>$sprint_task_dropoff_id,'sprint_id'=>$sprint_id,'status_id'=>($data_input['sprint']['type']=='return')?18:61,'active'=>1]);

                DB::commit();

                if($data_input['sprint']['type']=='dropoff'){
                    if($dispatch==true){
                        $newsprint=Sprint::find($sprint->id);
                        $this->addToDispatch($newsprint,['sprint_duetime'=> $data['sprint_duetime'],'copy'=> $data['copy']]);
                    }
                }
            }catch (\Exception $e) {
                DB::rollback();
                return RestAPI::response($e->getMessage(), false, 'error_exception');
            }

            $sprint->is_new_task=1;
            $response=  new CreateOrderResource($sprint);
            return RestAPI::responseForCreateOrder($response, true, 'Order Created');
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
                        ->where('type','custom_distance')
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
                                    ->where('type','custom_distance')
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
    public function checkStoreId($store_id)
    {
        $store = array(
            1004=>476610,
            1061=>475761,
            1080=>477192,
            1081=>477078,
            1095=>477153,
            1109=>476968,
            1116=>477069,
            1126=>476734,
            1211=>477157,
            3064=>476969,
            3195=>477154,
            4002=>476850,
            4006=>476867,
            4007=>476933,
            4020 =>477503
        );
        if(isset($store[$store_id])){
            return $store[$store_id];
        }else{
            return 0;
        }
    }
    public function creatorIdForHub($creator_id)
    {
        $hub = [
            477255=>17 ,
            477254=>17 ,
            477283=>17 ,
            477284=>17 ,
            477286=>17 ,
            477287=>17 ,
            477288=>17 ,
            477289=>17 ,
            477307=>17 ,
            477308=>17 ,
            477309=>17 ,
            477310=>17 ,
            477311=>17 ,
            477312=>17 ,
            477313=>17 ,
            477314=>17 ,
            477292=>17 ,
            477294=>17 ,
            477315=>17 ,
            477317=>17 ,
            477316=>17 ,
            477295=>17 ,
            477302=>17 ,
            477303=>17 ,
            477304=>17 ,
            477305=>17 ,
            477306=>17 ,
            477296=>17 ,
            477290=>17 ,
            477297=>17 ,
            477298=>17 ,
            477299=>17 ,
            477300=>17 ,
            477320=>17 ,
            477301=>17 ,
            477318=>17 ,
            477328=>17 ,
            476294=>17 ,
            477334=>17 ,
            477335=>17 ,
            477336=>17 ,
            477337=>17 ,
            477338=>17 ,
            477339=>17 ,
            477171=>17 ,

            477340=>19,
            477341=>19,
            477342=>19,
            477343=>19,
            477344=>19,
            477345=>19,
            477346=>19

        ];
        if(isset($hub[$creator_id])){
            return $hub[$creator_id];
        }else{
            return 0;
        }
    }
    public function loblaws_google_address($address,$postal_code)
    {

        $address = urlencode($address);
        $postal_code = urlencode($postal_code);

        // google map geocode api url
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key=AIzaSyBX0Z04xF9br04EGbVWR3xWkMOXVeKvns8";

        // dd($url);
        // get the json response
        $resp_json = file_get_contents($url);

        // decode the json
        $resp = json_decode($resp_json, true);

        // response status will be 'OK', if able to geocode given address
        if($resp['status']=='OK'){

            $completeAddress = [];
            $addressComponent = $resp['results'][0]['address_components'];

            // get the important data

            for ($i=0; $i < sizeof($addressComponent); $i++) {
                if ($addressComponent[$i]['types'][0] == 'administrative_area_level_1')
                {
                    $completeAddress['division'] = $addressComponent[$i]['short_name'];
                }
                elseif ($addressComponent[$i]['types'][0] == 'locality') {
                    $completeAddress['city'] = $addressComponent[$i]['short_name'];
                }
                else {
                    $completeAddress[$addressComponent[$i]['types'][0]] = $addressComponent[$i]['short_name'];
                }
                if($addressComponent[$i]['types'][0] == 'postal_code'){
                    $completeAddress['postal_code'] = $addressComponent[$i]['short_name'];
                }
            }

            if (array_key_exists('subpremise', $completeAddress)) {
                $completeAddress['suite'] = $completeAddress['subpremise'];
                unset($completeAddress['subpremise']);
            }
            else {
                $completeAddress['suite'] = '';
            }


            $completeAddress['address'] = $resp['results'][0]['formatted_address'];

            $completeAddress['lat'] = $resp['results'][0]['geometry']['location']['lat'];
            $completeAddress['lng'] = $resp['results'][0]['geometry']['location']['lng'];
            $completeAddress['status']=200;
            unset($completeAddress['administrative_area_level_2']);

            return $completeAddress;

        }
        else{
            // throw new GenericException($resp['status'],403);
            // return 0;
            return $error['status']=0;

        }


    }
    public function local_google_address($address,$postal_code){

        $address = urlencode($address);
        $postal_code = urlencode($postal_code);

        // google map geocode api url
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}components=country:pakistan|postal_code:{$postal_code}&key=AIzaSyDTK4viphUKcrJBSuoidDqRhVA4AWnHOo0";

        // get the json response
        $resp_json = file_get_contents($url);

        // decode the json
        $resp = json_decode($resp_json, true);

        // response status will be 'OK', if able to geocode given address
        if($resp['status']=='OK'){

            $completeAddress = [];
            $addressComponent = $resp['results'][0]['address_components'];

            // get the important data

            for ($i=0; $i < sizeof($addressComponent); $i++) {
                if ($addressComponent[$i]['types'][0] == 'administrative_area_level_1')
                {
                    $completeAddress['division'] = $addressComponent[$i]['short_name'];
                }
                elseif ($addressComponent[$i]['types'][0] == 'locality') {
                    $completeAddress['city'] = $addressComponent[$i]['short_name'];
                }
                else {
                    $completeAddress[$addressComponent[$i]['types'][0]] = $addressComponent[$i]['short_name'];
                }
                if($addressComponent[$i]['types'][0] == 'postal_code'){
                    $completeAddress['postal_code'] = $addressComponent[$i]['short_name'];
                }
            }

            if (array_key_exists('subpremise', $completeAddress)) {
                $completeAddress['suite'] = $completeAddress['subpremise'];
                unset($completeAddress['subpremise']);
            }
            else {
                $completeAddress['suite'] = '';
            }


            $completeAddress['address'] = $resp['results'][0]['formatted_address'];

            $completeAddress['lat'] = $resp['results'][0]['geometry']['location']['lat'];
            $completeAddress['lng'] = $resp['results'][0]['geometry']['location']['lng'];
            $completeAddress['status']=200;
            unset($completeAddress['administrative_area_level_2']);

            return $completeAddress;

        }
        else{
            // throw new GenericException($resp['status'],403);
            // return 0;
            return $error['status']=0;

        }


    }
    public function checkpostalcode($postalcode)
    {


        $url="https://maps.googleapis.com/maps/api/geocode/json?components=postal_code:{$postalcode}|country:canada&key=AIzaSyDTK4viphUKcrJBSuoidDqRhVA4AWnHOo0";

        // get the json response
        $resp_json = file_get_contents($url);



        // decode the json
        $resp = json_decode($resp_json, true);


        // response status will be 'OK', if able to geocode given address
        if($resp['results']!=null)
        {
            return 1;
        }
        else{
            // throw new GenericException($resp['status'],403);
            return 0;
        }


    }
    public function checkaddress($address)
    {
        $address = urlencode($address);
        $url="https://autocomplete.search.hereapi.com/v1/geocode?q={$address}components=country:canada&apiKey=G3ltf0YIlhIQxtGbWkI0jL_29xDDCZXy_ra88Mmhrn4";

        // get the json response
        $resp_json = file_get_contents($url);
        $completeAddress['status']=0;


        // decode the json
        $resp = json_decode($resp_json, true);
        // print_r($resp['items'][0]);die;

        // response status will be 'OK', if able to geocode given address
        if(!empty($resp['items']))
        {
            $completeAddress['status']=1;
            $completeAddress['city']=$resp['items'][0]['address']['city'];
            $completeAddress['postal_code']=$resp['items'][0]['address']['postalCode'];
            $completeAddress['lat']=$resp['items'][0]['position']['lat'];
            $completeAddress['lng']=$resp['items'][0]['position']['lng'];
            $completeAddress['street_number']=$resp['items'][0]['address']['houseNumber'];
            $completeAddress['route']=$resp['items'][0]['address']['street'];
            // echo  $completeAddress['lat'];die;
            return $completeAddress;
        }
        else{
            // throw new GenericException($resp['status'],403);
            return $completeAddress;
        }

    }
    public function gettimedifference($from=[],$to=[])
    {
        $ch = curl_init();

        $data=array(
            "visits"=>[
                "order_1"=>[
                    "location"=>[
                        "name"=>$to['name'],
                        "lat"=>$to['lat'],
                        "lng"=>$to['lng']
                    ]
                ]
            ],
            "fleet"=>[
                "vehicle_1"=>[
                    "start_location"=>[
                        "id" => "depot",
                        "name"=>$from['name'],
                        "lat"=>$from['lat'],
                        "lng"=>$from['lng']
                    ]
                ]
            ],
        );
        $data = json_encode($data);
        // print_r($data);
        // die();

        curl_setopt($ch, CURLOPT_URL,"https://api.routific.com/v1/vrp");
        curl_setopt($ch, CURLOPT_POST, 1);
        // curl_setopt($ch, CURLOPT_POSTFIELDS,
        //          http_build_query(array('postvar1' => 'value1')));
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJfaWQiOiI1Njk5ZDJjODUzNWFkMTBkMWQ0YmFlMTgiLCJpYXQiOjE0NTgxNjgzNjR9.RXZHpu7tVE3dersb5TZrtJMM8u4BehM0PriS9Dj1YAc'
        ));
        // routific_api_key
        // Receive server response ...
        $server_output = curl_exec($ch);
        curl_close ($ch);
        $res =json_decode($server_output,true);
        // print_r($res);die;
        // $res=
        // if($res['status']=="success"){
        //     echo $res['total_travel_time'];die;
        // }
        // die;
        if(isset($res['total_travel_time'])){
            // return $res['total_travel_time'];
            $return_data=$res['total_travel_time'];
        }
        else{
            $return_data['status']=400;
            $return_data['error']=$res['error'];
            $return_data['error_type']=$res['error_type'];
            // return $return_data;
        }
        return $return_data;

    }
    function getDistanceBetweenPoints($lat1, $lon1, $lat2, $lon2)
    {


        // $first_pickup['lat']=$first_pickup['lat']/1000000;
        // $first_pickup['lng']=$first_pickup['lng']/1000000;


        // $dropoff_string='';
        // foreach ($last_dropoff as $key => $value) {
        //     $dropoff_string.=$value['lng'].',';
        //     $dropoff_string.=$value['lat'].';';
        // }
        // $dropoff_string=substr_replace($dropoff_string, "", -1);


        $token='pk.eyJ1Ijoiam9leWNvIiwiYSI6ImNpbG9vMGsydzA4aml1Y2tucjJqcDQ2MDcifQ.gyd_3OOVqdByGDKjBO7lyA';
        // $response = file_get_contents('https://api.mapbox.com/directions/v5/mapbox/driving/'.$first_pickup["lng"].','.$first_pickup["lat"].';'.$dropoff_string.'?access_token='.$token);

        $response = file_get_contents('https://api.mapbox.com/directions/v5/mapbox/driving/'.$lon2.','.$lat2.';'.$lon1.','.$lat1.'?access_token='.$token);
        // $response = file_get_contents('https://api.mapbox.com/directions/v5/mapbox/driving/-79.5747,43.7112;-79.5962,43.6236?access_token='.$token);


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
    public function getDropoffCharge($vendorId,$limit,$vehicleid)
    {
        $dropoff_charge=0;
        $vendor=Vendor::where('id',$vendorId)->first();
        if($vendor!=null){
            if($vendor->vendorPackage!=null){
                if($vendor->vendorPackage->vehicleCharge()!=null){

                    $dropoff_task_charge=$vendor->vendorPackage->vehicleCharge()->where('vehicle_id',$vehicleid)->where('type','dropoff')
                        ->where('limit','>=',$limit)
                        ->sortBy('limit');


                    if(count($dropoff_task_charge)==0){
                        $dropoff_task_charge=$vendor->vendorPackage->vehicleCharge()->where('vehicle_id',$vehicleid)->where('type','custom-dropoff')
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
        }

        return $dropoff_charge;
    }
    public function addToDispatch($sprint,$data=[])
    {

        $dispatchData['order_id']=$sprint->id;
        $dispatchData['num']='CR-'.$sprint->id;
        $dispatchData['creator_id']=$sprint->creator_id;
        $dispatchData['sprint_id']=$sprint->id;
        $dispatchData['status']=$sprint->status_id;
        $dispatchData['distance']=$sprint->distance;
        $dispatchData['active']=$sprint->active;
        $dispatchData['type']="custom-run";
        $dispatchData['vehicle_id']=$sprint->vehicle_id;
        $dispatchData['vehicle_name']=($sprint->vehicle!=null)?$sprint->vehicle->name:null;
        $dispatchData['pickup_location_id']=$sprint->sprintFirstPickupTask->location_id;
        $dispatchData['pickup_contact_name']=$sprint->sprintFirstPickupTask->sprintContact->name;
        $dispatchData['pickup_address']=$sprint->sprintFirstPickupTask->Location->address;
        $dispatchData['pickup_contact_phone']=$sprint->sprintFirstPickupTask->sprintContact->phone;
        $dispatchData['pickup_eta']=$sprint->sprintFirstPickupTask->eta_time;
        $dispatchData['pickup_etc']=$sprint->sprintFirstPickupTask->etc_time;
        $dispatchData['dropoff_contact_phone']=$sprint->sprintFirstDropoffTask->sprintContact->phone;
        $dispatchData['dropoff_location_id']=$sprint->sprintFirstDropoffTask->location_id;
        $dispatchData['dropoff_address']=$sprint->sprintFirstDropoffTask->Location->address;
        $dispatchData['dropoff_eta']=$sprint->sprintFirstDropoffTask->eta_time;
        $dispatchData['dropoff_etc']=$sprint->sprintFirstDropoffTask->etc_time;
        $dispatchData['date']=$data['sprint_duetime'];
        $dispatchData['has_notes']=0;
        $dispatchData['sprint_duration']=null;
        $dispatchData['zone_id']=null;
        $dispatchData['zone_name']=null;
        $dispatchData['status_copy']=$data['copy'];

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
        Dispatch::insert($dispatchData);
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

    //Mark:- Create Pickup POST
    public function createOrderPickup(Request $request)
    {

        $data_input = $request->all();
        $validation_data=$data_input;
        // checking is valid json
        if (json_last_error() != JSON_ERROR_NONE) {
            return RestAPI::response("Json request is not valid / ".json_last_error_msg(), false, json_last_error());
        }
        $location_rules =new CreateOrderLocationRequest;
        $location_validator = Validator::make($validation_data['location'], $location_rules->rules());

        if (!$location_validator->passes()) {
            return RestAPI::response($location_validator->errors()->all(), false, 'Validation Error');
        }
        /**
         *Sprint
         */

        $sprint_rules_loblaws=new CreateOrderLoblawsRequest;
        $sprint_validator = Validator::make($validation_data['sprint'], $sprint_rules_loblaws->rules());

        if (!$sprint_validator->passes()) {
            $sprintValidationErrors=$sprint_validator->errors()->all();
            foreach($sprintValidationErrors as $key=>$value){
                // if(in_array("The end time must be a date after start time.", $sprint_validator->errors()->all())) {
                if($value=="The end time must be a date after start time.") {
                    $sprintValidationErrors[$key]='End time should be greater than start time.';
                }
                if (strpos($value, 'The due time must be greater than or equal') !== false) {
                    $sprintValidationErrors[$key]='Due time must be half an hour from now!.';
                }
            }
            return RestAPI::response($sprintValidationErrors, false, 'Validation Error');
        }
        /**
         *Contact
         */

        $contact_rules = new CreateOrderContactRequest;
        $contact_validator = Validator::make($validation_data['contact'], $contact_rules->rules());

        if (!$contact_validator->passes()) {
            return RestAPI::response($contact_validator->errors()->all(), false, 'Validation Error');
        }
        /**
         *Payment
         */
        if(isset($validation_data['payment'])){

            $payment_rules =new CreateOrderPaymentRequest;
            $payment_validator = Validator::make($validation_data['payment'], $payment_rules->rules());

            if (!$payment_validator->passes()) {
                return RestAPI::response($payment_validator->errors()->all(), false, 'Validation Error');
            }
            unset($validation_data['payment']);
        }
        unset($validation_data['location']);unset($validation_data['sprint']);unset($validation_data['contact']);
        /**
         *Others
         */

        $other_rules=new CreateOrderOtherRequest;
        $other_validator = Validator::make($validation_data, $other_rules->rules());

        if (!$other_validator->passes()) {
            return RestAPI::response($other_validator->errors()->all(), false, 'Validation Error');
        }

        $checkaddress=$this->loblaws_google_address($data_input['location']['address'],$data_input['location']['postal_code']);
        if($checkaddress['status']==0){return RestAPI::response('Invalid address/Postal code', false, 'Validation Error');}

        // validation=====================================================================
        else{
            $data=[];
            // sprint
            $data['sprint_creator_id'] =$data_input['sprint']['creator_id'] ; //req
            $data['sprint_duetime'] =$data_input['sprint']['due_time'] ; //req
            $data['sprint_vehicle_id'] =$data_input['sprint']['vehicle_id'] ; //req
            $data['sprint_tracking_id'] =$data_input['sprint']['tracking_id']??null ;
            $data['sprint_merchant_order_num'] =$data_input['sprint']['merchant_order_num']??null ;
            $data['sprint_start_time'] =$data_input['sprint']['start_time']??null ;
            $data['sprint_end_time'] =$data_input['sprint']['end_time']??null ;
            $data['sprint_tip'] =$data_input['sprint']['tip']??null ;


            // contact
            $data['contact_name'] =$data_input['contact']['name'] ; //req
            $data['contact_email'] =$data_input['contact']['email']??null ;
            $data['contact_phone'] =$data_input['contact']['phone']??null ;

            // location
            $data['location_address'] =$data_input['location']['address'] ; //req
            $data['location_postal_code'] =$data_input['location']['postal_code'] ; //req
            $data['location_address_line2'] =$data_input['location']['address_line2'] ??null;
            $data['location_pickup_buzzer'] =$data_input['location']['pickup_buzzer']??null ;
            $data['location_buzzer'] =$data_input['location']['buzzer'] ??null;

            // payment
            $data['payment_type'] =$data_input['payment']['type']??null ;
            $data['payment_amount'] =$data_input['payment']['amount'] ??null;

            // description
            $data['copy'] =$data_input['copy'] ??null;

            // notification method
            $data['notification_method'] =$data_input['notification_method'] ; //req

            // confirmation
            if(!isset($data_input['confirm_signature'])){
                $data_input['confirm_signature']=0;
            }
            if(!isset($data_input['confirm_pin'])){
                $data_input['confirm_pin']=0;
            }
            if(!isset($data_input['confirm_image'])){
                $data_input['confirm_image']=0;
            }
            if(!isset($data_input['confirm_seal'])){
                $data_input['confirm_seal']=0;
            }
            $data['confirm_signature'] =$data_input['confirm_signature'] ;
            $data['confirm_pin'] =$data_input['confirm_pin'] ;
            $data['confirm_image'] =$data_input['confirm_image'] ;
            $data['confirm_seal'] =$data_input['confirm_seal'] ;

            // echo 3;die;
            DB::beginTransaction();
            try {
                //get address data
                $contact_address=$checkaddress;

                $city_data=City::where('name',$contact_address['city'])->first(); //get city data

                $merchant=[];
                if($data['sprint_tracking_id']!=null){
                    $merchant=MerchantsIds::where('tracking_id',$data['sprint_tracking_id'])->first();
                }
                if(empty($merchant)){

                    //save location for contact/drop off
                    $location_data['buzzer']=$data['location_buzzer'];
                    $location_data['postal_code']=$contact_address['postal_code'];
                    $location_data['latitude']=str_replace('.','',$contact_address['lat']);
                    $location_data['longitude']=str_replace('.','',$contact_address['lng']);
                    $location_data['address']=$contact_address['street_number'].' '.$contact_address['route'];
                    $location_data['city_id']=$city_data->id;
                    $location_data['state_id']=$city_data->state_id;
                    $location_data['country_id']=$city_data->country_id;
                    $location=Location::create($location_data);
                    $location_id=$location->id;
//                    $this->insertLocationEnc($location_id,$location_data);
                    //sprint contact save in sprint contact
                    $contact_data['name']=$data['contact_name'];
                    $contact_data['phone']=$data['contact_phone'];
                    $contact_data['email']=$data['contact_email'];
                    $contact=SprintContact::create($contact_data);
                    $contact_id=$contact->id;
//                    $this->insertContactEnc($contact_id,$contact_data);

                    // sprint save
                    $sprint_data['creator_id']=$data['sprint_creator_id'];
                    $sprint_data['creator_type']='vendor';
                    $sprint_data['vehicle_id']=$data['sprint_vehicle_id'];
                    $sprint_data['status_id']=38;
                    $sprint_data['tip']=$data['sprint_tip'];
                    if($data['payment_type']=='make'){$sprint_data['make_payment_total']=$data['payment_amount'];}elseif($data['payment_type']=='collect'){$sprint_data['collect_payment_total']=$data['payment_amount'];}
                    $sprint=Sprint::create($sprint_data);
                    $sprint_id=$sprint->id;

                    DB::table('sprint__sprints_history')->insert(['sprint__sprints_id'=>$sprint_id,'vehicle_id'=>$sprint->vehicle_id,'distance'=>$sprint->distance,'status_id'=>$sprint->status_id,'active'=>1,'optimize_route'=>1,'date'=>date('Y-m-d H:i:s'),'created_at'=>date('Y-m-d H:i:s')]);

                    //get time difference
                    $from['name']=$sprint->vendor->address;
                    $from['lat']=(float)($sprint->vendor->location->latitude/1000000);
                    $from['lng']=(float)($sprint->vendor->location->longitude/1000000);
                    $to['name']=$location_data['address'];
                    $to['lat']=$contact_address['lat'];
                    $to['lng']=$contact_address['lng'];
                    $time_difference= $this->gettimedifference($from,$to);
                    if(isset($time_difference['status'])){return RestAPI::response($time_difference['error'], false, $time_difference['error_type']);}

                    //save vendor as contact for vendor contact id
                    $vendor_contact_data['name']=$sprint->vendor->first_name.' '.$sprint->vendor->last_name;
                    $vendor_contact_data['phone']=$sprint->vendor->phone;
                    $vendor_contact_data['email']=$sprint->vendor->email;
                    $vendor_contact=SprintContact::create($vendor_contact_data);
                    $vendor_contact_id=$vendor_contact->id;
//                    $this->insertContactEnc($vendor_contact_id,$vendor_contact_data);

                    // then create two tasks in sprint task for drop off and pickup
                    //sprint tasks save type=pickup
                    $sprint_task_pickup_data['sprint_id']=$sprint_id;
                    $sprint_task_pickup_data['type']='pickup';
                    $sprint_task_pickup_data['charge']=0;
                    $sprint_task_pickup_data['ordinal']=1;
                    $sprint_task_pickup_data['due_time']=$data['sprint_duetime'];
                    $sprint_task_pickup_data['eta_time']=$data['sprint_duetime'];
                    $sprint_task_pickup_data['etc_time']=$data['sprint_duetime'] + (0.25*60*60);//adding 15 mins
                    $sprint_task_pickup_data['location_id']=$sprint->vendor->location_id;//vendors table location id
                    $sprint_task_pickup_data['contact_id']=$vendor_contact_id;
                    $sprint_task_pickup_data['status_id']=38;
                    $sprint_task_pickup_data['active']=1;
                    $sprint_task_pickup_data['confirm_image']=0;
                    $sprint_task_pickup_data['confirm_signature']=0;
                    $sprint_task_pickup_data['confirm_pin']=0;
                    $sprint_task_pickup_data['confirm_seal']=0;
                    $sprint_task_pickup=SprintTasks::create($sprint_task_pickup_data);
                    $sprint_task_pickup_id=$sprint_task_pickup->id;
                    SprintTaskHistory::insert(['created_at'=>date("Y-m-d H:i:s"),'date'=>date('Y-m-d H:i:s'),'sprint__tasks_id'=>$sprint_task_pickup_id,'sprint_id'=>$sprint_id,'status_id'=>38,'active'=>1]);

                    // save sprint confirmation
                    $ordinal=0;
//                    $sprint_confirmation_data['task_id']=$sprint_task_dropoff_id;
                    if($data['confirm_pin']==1){
                        $ordinal+=1;
                        $sprint_confirmation_data['name']="confirm pin";
                        $sprint_confirmation_data['title']="Confirm Pin";
                        $sprint_confirmation_data['input_type']="text/plain";
                        $sprint_confirmation_data['ordinal']=$ordinal;
                        SprintConfirmation::create($sprint_confirmation_data);
                    }
                    if($data['confirm_image']==1){
                        $ordinal+=1;
                        $sprint_confirmation_data['name']="confirm image";
                        $sprint_confirmation_data['title']="Confirm Image";
                        $sprint_confirmation_data['input_type']="image/jpeg";
                        $sprint_confirmation_data['ordinal']=$ordinal;
                        SprintConfirmation::create($sprint_confirmation_data);
                    }
                    if($data['confirm_signature']==1){
                        $ordinal+=1;
                        $sprint_confirmation_data['name']="confirm signature";
                        $sprint_confirmation_data['title']="Confirm Signature";
                        $sprint_confirmation_data['input_type']="image/jpeg";
                        $sprint_confirmation_data['ordinal']=$ordinal;
                        SprintConfirmation::create($sprint_confirmation_data);
                    }
                    if($data['confirm_seal']==1){
                        $ordinal+=1;
                        $sprint_confirmation_data['name']="confirm seal";
                        $sprint_confirmation_data['title']="Confirm Seal";
                        $sprint_confirmation_data['input_type']="image/jpeg";
                        $sprint_confirmation_data['ordinal']=$ordinal;
                        SprintConfirmation::create($sprint_confirmation_data);
                    }

                    //default for pickup
                    $sprint_confirmation_default_data['task_id']=$sprint_task_pickup_id;
                    $sprint_confirmation_default_data['name']="default";
                    $sprint_confirmation_default_data['title']="Confirm Pickup";
                    $sprint_confirmation_default_data['ordinal']=$ordinal;
                    SprintConfirmation::create($sprint_confirmation_default_data);

                    // update sprint task
                    SprintTasks::where('id',$sprint_task_pickup_id)->update(['status_id' => 61]);
                    SprintTaskHistory::insert(['created_at'=>date("Y-m-d H:i:s"),'date'=>date('Y-m-d H:i:s'),'sprint__tasks_id'=>$sprint_task_pickup_id,'sprint_id'=>$sprint_id,'status_id'=>61,'active'=>1]);

                }
                else{
                    $sprint=$merchant->taskids->sprintsSprints;
                }

                $response=  new CreateOrderResource($sprint);

                DB::commit();
            }catch (\Exception $e) {
                DB::rollback();
                return RestAPI::response($e->getMessage(), false, 'error_exception');
            }
        }
        return RestAPI::responseForCreateOrder($response, true, 'Order Created');
    }

    //Mark:- Create Dropoff POST
    public function createOrderDropoff(Request $request)
    {

        $data_input = $request->all();

        $validation_data=$data_input;

        // checking is valid json
        if (json_last_error() != JSON_ERROR_NONE) {
            return RestAPI::response("Json request is not valid / ".json_last_error_msg(), false, json_last_error());
        }

        $location_rules =new CreateOrderLocationRequest;
        $location_validator = Validator::make($validation_data['location'], $location_rules->rules());

        if (!$location_validator->passes()) {
            return RestAPI::response($location_validator->errors()->all(), false, 'Validation Error');
        }
        /**
         *Sprint
         */

        $sprint_rules_loblaws=new CreateOrderLoblawsRequest;
        $sprint_validator = Validator::make($validation_data['sprint'], $sprint_rules_loblaws->rules());

        if (!$sprint_validator->passes()) {
            $sprintValidationErrors=$sprint_validator->errors()->all();
            foreach($sprintValidationErrors as $key=>$value){
                // if(in_array("The end time must be a date after start time.", $sprint_validator->errors()->all())) {
                if($value=="The end time must be a date after start time.") {
                    $sprintValidationErrors[$key]='End time should be greater than start time.';
                }
                if (strpos($value, 'The due time must be greater than or equal') !== false) {
                    $sprintValidationErrors[$key]='Due time must be half an hour from now!.';
                }
            }
            return RestAPI::response($sprintValidationErrors, false, 'Validation Error');
        }
        /**
         *Contact
         */

        $contact_rules = new CreateOrderContactRequest;
        $contact_validator = Validator::make($validation_data['contact'], $contact_rules->rules());

        if (!$contact_validator->passes()) {
            return RestAPI::response($contact_validator->errors()->all(), false, 'Validation Error');
        }

        /**
         *Payment
         */
        if(isset($validation_data['payment'])){

            $payment_rules =new CreateOrderPaymentRequest;
            $payment_validator = Validator::make($validation_data['payment'], $payment_rules->rules());

            if (!$payment_validator->passes()) {
                return RestAPI::response($payment_validator->errors()->all(), false, 'Validation Error');
            }
            unset($validation_data['payment']);
        }
        unset($validation_data['location']);unset($validation_data['sprint']);unset($validation_data['contact']);
        /**
         *Others
         */

        $other_rules=new CreateOrderOtherRequest;
        $other_validator = Validator::make($validation_data, $other_rules->rules());

        if (!$other_validator->passes()) {
            return RestAPI::response($other_validator->errors()->all(), false, 'Validation Error');
        }

        // check address
        $checkaddress=$this->loblaws_google_address($data_input['location']['address'],$data_input['location']['postal_code']);
        // check postal code

        //check postal code and address(valid and for canada only)
        if($checkaddress['status']==0){return RestAPI::response('Invalid address/Postal code', false, 'Validation Error');}

        // validation=====================================================================
        else{
            $data=[];
            // sprint
            $data['sprint_creator_id'] =$data_input['sprint']['creator_id'] ; //req
            $data['sprint_duetime'] =$data_input['sprint']['due_time'] ; //req
            $data['sprint_vehicle_id'] =$data_input['sprint']['vehicle_id'] ; //req
            $data['sprint_tracking_id'] =$data_input['sprint']['tracking_id']??null ;
            $data['sprint_merchant_order_num'] =$data_input['sprint']['merchant_order_num']??null ;
            $data['sprint_start_time'] =$data_input['sprint']['start_time']??null ;
            $data['sprint_end_time'] =$data_input['sprint']['end_time']??null ;
            $data['sprint_tip'] =$data_input['sprint']['tip']??null ;


            // contact
            $data['contact_name'] =$data_input['contact']['name'] ; //req
            $data['contact_email'] =$data_input['contact']['email']??null ;
            $data['contact_phone'] =$data_input['contact']['phone']??null ;

            // location
            $data['location_address'] =$data_input['location']['address'] ; //req
            $data['location_postal_code'] =$data_input['location']['postal_code'] ; //req
            $data['location_address_line2'] =$data_input['location']['address_line2'] ??null;
            $data['location_pickup_buzzer'] =$data_input['location']['pickup_buzzer']??null ;
            $data['location_buzzer'] =$data_input['location']['buzzer'] ??null;

            // payment
            $data['payment_type'] =$data_input['payment']['type']??null ;
            $data['payment_amount'] =$data_input['payment']['amount'] ??null;

            // description
            $data['copy'] =$data_input['copy'] ??null;

            // notification method
            $data['notification_method'] =$data_input['notification_method'] ; //req

            // confirmation
            if(!isset($data_input['confirm_signature'])){
                $data_input['confirm_signature']=0;
            }
            if(!isset($data_input['confirm_pin'])){
                $data_input['confirm_pin']=0;
            }
            if(!isset($data_input['confirm_image'])){
                $data_input['confirm_image']=0;
            }
            if(!isset($data_input['confirm_seal'])){
                $data_input['confirm_seal']=0;
            }
            $data['confirm_signature'] =$data_input['confirm_signature'] ;
            $data['confirm_pin'] =$data_input['confirm_pin'] ;
            $data['confirm_image'] =$data_input['confirm_image'] ;
            $data['confirm_seal'] =$data_input['confirm_seal'] ;

            DB::beginTransaction();
            try {
                //get address data
                $contact_address=$checkaddress;

                $city_data=City::where('name',$contact_address['city'])->first(); //get city data

                $merchant=[];
                if($data['sprint_tracking_id']!=null){$merchant=MerchantsIds::where('tracking_id',$data['sprint_tracking_id'])->first();}
                if(empty($merchant)){

                    //save location for contact/drop off
                    $location_data['buzzer']=$data['location_buzzer'];
                    $location_data['postal_code']=$contact_address['postal_code'];
                    $location_data['latitude']=str_replace('.','',$contact_address['lat']);
                    $location_data['longitude']=str_replace('.','',$contact_address['lng']);
                    $location_data['address']=$contact_address['street_number'].' '.$contact_address['route'];
                    $location_data['city_id']=$city_data->id;
                    $location_data['state_id']=$city_data->state_id;
                    $location_data['country_id']=$city_data->country_id;
                    $location=Location::create($location_data);
                    $location_id=$location->id;
                    //sprint contact save in sprint contact
                    $contact_data['name']=$data['contact_name'];
                    $contact_data['phone']=$data['contact_phone'];
                    $contact_data['email']=$data['contact_email'];
                    $contact=SprintContact::create($contact_data);
                    $contact_id=$contact->id;

                    $sprint_id=$data_input["sprint"]["sprint_id"];
                    $sprint = Sprint::where('id',$data_input["sprint"]["sprint_id"])->first();

                    //get time difference
                    $from['name']=$sprint->vendor->location->address;
                    $from['lat']=(float)($sprint->vendor->location->latitude/1000000);
                    $from['lng']=(float)($sprint->vendor->location->longitude/1000000);
                    $to['name']=$location_data['address'];
                    $to['lat']=$contact_address['lat'];
                    $to['lng']=$contact_address['lng'];
                    // $time_difference= $this->gettimedifference($from,$to);
                    $time_difference= $this->gettimedifference($from,$to);
                    if(isset($time_difference['status'])){return RestAPI::response($time_difference['error'], false, $time_difference['error_type']);}

                    //save vendor as contact for vendor contact id
                    $vendor_contact_data['name']=$sprint->vendor->first_name.' '.$sprint->vendor->last_name;
                    $vendor_contact_data['phone']=$sprint->vendor->phone;
                    $vendor_contact_data['email']=$sprint->vendor->email;
                    $vendor_contact=SprintContact::create($vendor_contact_data);
                    $vendor_contact_id=$vendor_contact->id;

                    //sprint tasks save type=dropoff
                    $dropoff_charge=$this->getDropoffCharge($sprint->creator_id,1,$sprint->vehicle_id);
                    $sprint_task_dropoff_data['sprint_id']=$sprint_id;
                    $sprint_task_dropoff_data['type']='dropoff';
                    $sprint_task_dropoff_data['charge']=$dropoff_charge;
                    $sprint_task_dropoff_data['ordinal']=2;
                    $sprint_task_dropoff_data['due_time']=$data['sprint_duetime'];
                    $sprint_task_dropoff_data['eta_time']=$data['sprint_duetime']+($time_difference * 60 * 1000);//add time difference between two points
                    $sprint_task_dropoff_data['etc_time']=$sprint_task_dropoff_data['eta_time'] + (0.25*60*60);//adding 15 mins
                    $sprint_task_dropoff_data['location_id']=$location_id;//vendors table location id
                    $sprint_task_dropoff_data['contact_id']=$contact_id;
                    $sprint_task_dropoff_data['status_id']=38;
                    $sprint_task_dropoff_data['active']=1;
                    $sprint_task_dropoff_data['notify_by']=$data['notification_method'];
                    $sprint_task_dropoff_data['payment_type']=$data['payment_type'];
                    $sprint_task_dropoff_data['payment_amount']=$data['payment_amount'];
                    $sprint_task_dropoff_data['description']=$data['copy'];
                    $sprint_task_dropoff_data['confirm_image']=$data['confirm_image'];
                    $sprint_task_dropoff_data['confirm_signature']=$data['confirm_signature'];
                    $sprint_task_dropoff_data['confirm_pin']=$data['confirm_pin'];
                    $sprint_task_dropoff_data['confirm_seal']=$data['confirm_seal'];

                    if($data['confirm_pin']==1){
                        $check=true;
                        $pin_dropoff=0;
                        while ($check==true) {
                            $pin_dropoff=mt_rand(100000,999999);
                            $check_for_pin=SprintTasks::where('pin', $pin_dropoff)->where('type', 'dropoff')->first();
                            if(empty($check_for_pin)){
                                $check=false;
                            }
                        }
                        $sprint_task_dropoff_data['pin']=$pin_dropoff;
                    }

                    $sprint_task_dropoff=SprintTasks::create($sprint_task_dropoff_data);
                    $sprint_task_dropoff_id=$sprint_task_dropoff->id;
                    SprintTaskHistory::insert(['created_at'=>date("Y-m-d H:i:s"),'date'=>date('Y-m-d H:i:s'),'sprint__tasks_id'=>$sprint_task_dropoff_id,'sprint_id'=>$sprint_id,'status_id'=>38,'active'=>1,'date'=>date('Y-m-d H:i:s'),'created_at'=>date('Y-m-d H:i:s')]);

                    // save sprint confirmation
                    $ordinal=0;
                    $sprint_confirmation_data['task_id']=$sprint_task_dropoff_id;
                    if($data['confirm_pin']==1){
                        $ordinal+=1;
                        $sprint_confirmation_data['name']="confirm pin";
                        $sprint_confirmation_data['title']="Confirm Pin";
                        $sprint_confirmation_data['input_type']="text/plain";
                        $sprint_confirmation_data['ordinal']=$ordinal;
                        SprintConfirmation::create($sprint_confirmation_data);
                    }
                    if($data['confirm_image']==1){
                        $ordinal+=1;
                        $sprint_confirmation_data['name']="confirm image";
                        $sprint_confirmation_data['title']="Confirm Image";
                        $sprint_confirmation_data['input_type']="image/jpeg";
                        $sprint_confirmation_data['ordinal']=$ordinal;
                        SprintConfirmation::create($sprint_confirmation_data);
                    }
                    if($data['confirm_signature']==1){
                        $ordinal+=1;
                        $sprint_confirmation_data['name']="confirm signature";
                        $sprint_confirmation_data['title']="Confirm Signature";
                        $sprint_confirmation_data['input_type']="image/jpeg";
                        $sprint_confirmation_data['ordinal']=$ordinal;
                        SprintConfirmation::create($sprint_confirmation_data);
                    }
                    if($data['confirm_seal']==1){
                        $ordinal+=1;
                        $sprint_confirmation_data['name']="confirm seal";
                        $sprint_confirmation_data['title']="Confirm Seal";
                        $sprint_confirmation_data['input_type']="image/jpeg";
                        $sprint_confirmation_data['ordinal']=$ordinal;
                        SprintConfirmation::create($sprint_confirmation_data);
                    }
                    if ($ordinal==0) {
                        $sprint_confirmation_default_data['task_id']=$sprint_task_dropoff_id;
                        $sprint_confirmation_default_data['name']="default";
                        $sprint_confirmation_default_data['title']="Confirm Dropoff";
                        $sprint_confirmation_default_data['ordinal']=$ordinal;
                        SprintConfirmation::create($sprint_confirmation_default_data);
                    }

                    // save merchant id data

                    $merchantid_data['task_id']=$sprint_task_dropoff_id; //task_id for dropoff
                    $merchantid_data['merchant_order_num']=$data['sprint_merchant_order_num'];
                    $merchantid_data['end_time']=$data['sprint_end_time'];
                    $merchantid_data['start_time']=$data['sprint_start_time'];
                    $merchantid_data['tracking_id']=$data['sprint_tracking_id'];
                    $merchantid_data['address_line2']=$data['location_address_line2'];
                    $merchantid=MerchantsIds::create($merchantid_data);

                    $sprint['last_task_id']=$sprint_task_dropoff_id;
                    $sprint['optimize_route']=1;
                    $sprint['status_id']=61;
                    $sprint['active']=1;
                    $sprint['timezone']=$city_data->timezone??'America/Toronto';
                    $sprint['push_at']=date("Y-m-d H:i:s", substr(($data['sprint_duetime']-(0.5*60*60)), 0, 10));
                    $sprint['distance']=$this->getDistanceBetweenPoints($to['lat'],$to['lng'],$from['lat'],$from['lng']);
                    $sprint['checked_out_at']=date('Y-m-d H:i:s');
                    $sprint['distance_charge']=$this->getDistanceCharge($sprint_task_dropoff);
                    // echo  $sprint['distance_charge'];die;
                    $sprint->save();

                    DB::table('sprint__sprints_history')->insert(['sprint__sprints_id'=>$sprint_id,'vehicle_id'=>$sprint->vehicle_id,'distance'=>$sprint['distance'],'status_id'=>61,'active'=>1,'optimize_route'=>1,'date'=>date('Y-m-d H:i:s'),'created_at'=>date('Y-m-d H:i:s')]);

                    // update sprint task
                    SprintTasks::where('id',$sprint_task_dropoff_id)->update(['status_id' => 61]);
                    SprintTaskHistory::insert(['created_at'=>date("Y-m-d H:i:s"),'date'=>date('Y-m-d H:i:s'),'sprint__tasks_id'=>$sprint_task_dropoff_id,'sprint_id'=>$sprint_id,'status_id'=>61,'active'=>1]);


                    $newsprint=Sprint::find($data_input["sprint"]["sprint_id"]);
                    $status_copy = $this->getStatusCodesWithKey('status_labels.'.$newsprint["status_id"]);
                    $this->addToDispatch($newsprint,['sprint_duetime'=> $data_input['sprint']['due_time'],'copy'=> $status_copy]);
                }
                else{
                    $sprint=$merchant->taskids->sprintsSprints;
                    // print_r($sprint);
                }

                $response=  new CreateOrderResource($sprint);
                DB::commit();
            }catch (\Exception $e) {
                DB::rollback();
                return RestAPI::response($e->getMessage(), false, 'error_exception');
            }
        }
        return RestAPI::responseForCreateOrder($response, true, 'Order Created');

    }

    //Mark:- All status active,completed,rejected,returned... GET
    public function AllOrdersStatus(Request $request,$creator_id,$status)
    {

        $response=[];
        $status_id = [];
        DB::beginTransaction();
        try {
            $SprintData = "";

            if($status == "active"){
                $status_id = [24,32,67,68,15,28];
                $SprintData = Sprint::whereNull('deleted_at')->where('creator_id',$creator_id)->whereIn('status_id',$status_id)->get();
            }else if($status == "returned"){

                $from=date('Y-m-d', strtotime('-3 days'));
                $to=date('Y-m-d');

                $status_id = [101,102,103,104,105,106,107,108,109,110,111,112,131,135,136,143];

                $SprintData = Sprint::whereNull('deleted_at')->where('creator_id',$creator_id)->whereBetween('created_at',[$from,$to])->whereIn('status_id',$status_id)->get();
            }else if($status == "completed"){
                $from=date('Y-m-d', strtotime('-3 days'));
                $to=date('Y-m-d');

                $status_id = [17,113,114,116,117,118,132,138,139,144];
                $SprintData = Sprint::whereNull('deleted_at')->where('creator_id',$creator_id)->whereBetween('created_at',[$from,$to])->whereIn('status_id',$status_id)->get();
            }else if($status == "rejected"){
                $from=date('Y-m-d', strtotime('-3 days'));
                $to=date('Y-m-d');

                $status_id = [38,36];
                $SprintData = Sprint::whereNull('deleted_at')->where('creator_id',$creator_id)->whereBetween('created_at',[$from,$to])->whereIn('status_id',$status_id)->get();
            }else if($status == "scheduled"){
                $status_id = [13,61,124];
                $SprintData = Sprint::whereNull('deleted_at')->where('creator_id',$creator_id)->whereIn('status_id',$status_id)->get();
            }

            $response = AllOrderStatusResource::collection($SprintData);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::responseForOrderStatus($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::responseForOrderStatus($response, true, "User List ");
    }

    //Mark:- Detail of sprint... GET
    public function OrderDetails(Request $request,$sprint_id)
    {

        $response=[];
        $status_id = [];
        DB::beginTransaction();
        try {

            $SprintData = Sprint::whereNull('deleted_at')->where('id',$sprint_id)->get();
            $response = AllOrderStatusResource::collection($SprintData);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::responseForOrderDetail($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::responseForOrderDetail($response, true, "User List ");
    }

    //Mark:- Order Update GET
    public function OrderEditStatus(Request $request,$sprint_id,$task_id)
    {

        $response=[];
        DB::beginTransaction();
        try {
            $SprintData = SprintTasks::where('id',$task_id)->get();
            $response = SprintTaskResource::collection($SprintData);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "Task Detail For Edit");
    }

    //Mark:- Order Update POST
    public function OrderUpdateStatus(Request $request,$task_id)
    {

        $getData = $request->all();
        $data = [
            'type' => $getData["type"],
            'sprint' =>
                array (
                    'creator_id' => $getData["sprint"]["creator_id"],
                    'vehicle_id' => $getData["sprint"]["vehicle_id"],
                    'creator_type' => 'vendor',
                    'merchant' => 'merchant'
                ),
            'contact' =>
                array (
                    'name' => $getData["contact"]["name"],
                    'email' => $getData["contact"]["email"] ,
                    'phone' => $getData["contact"]["phone"]
                ),
            'location' =>
                array (
                    'address' => $getData["location"]["address"],
                    'postal_code' => $getData["location"]["postal_code"],
                    'buzzer' => $getData["location"]["buzzer"],
                    'latitude' => $getData["location"]["latitude"],
                    'longitude' => $getData["location"]["langitude"],
                    'division' => $getData["location"]["division"],
                    'city' => $getData["location"]["city"],
                    'country' => $getData["location"]["country"]
                ),
            'notification_method' => $getData["notification_method"]

        ];

        $response=[];
        DB::beginTransaction();
        try {
            $SprintUpdate = SprintTasks::where('id',$task_id)->update($data);
            $SprintData = SprintTasks::where('id',$task_id);
            $response = SprintTaskResource::collection($SprintData);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "Task Updated Successfully");
    }

    //Mark:- Order Update Due Time PUT
    public function OrderUpdateDueTime(Request $request,$vendor_id,$sprint_id)
    {

        // data validation
        $rules = [
            'due_time' => 'required'
        ];

        $due_time = $request->get('due_time');

        $dt = date("Y-m-d H:i:s", substr($due_time, 0, 10));
        $convert_date = date("Y-m-d H:i:s",strtotime("+15 minutes", strtotime($dt)));

        $date = new \DateTime($convert_date);
        $due_time_etc = $date->format('U');

        $response=[];
        $status_id = [];
        DB::beginTransaction();
        try {

            $pickupData = SprintTasks::join('locations', 'locations.id', '=', 'sprint__tasks.location_id')
                ->where('sprint__tasks.sprint_id',$sprint_id)->where('sprint__tasks.type','pickup')
                ->select('locations.latitude','locations.longitude')
                ->get();
            $dropoffData = SprintTasks::join('locations', 'locations.id', '=', 'sprint__tasks.location_id')
                ->where('sprint__tasks.sprint_id',$sprint_id)->where('sprint__tasks.type','dropoff')
                ->select('locations.latitude','locations.longitude')
                ->get();

            $pickupLat = $pickupData[0]['latitude']/1000000;
            $pickupLong = $pickupData[0]['longitude']/1000000;
            $dropoffLat = $dropoffData[0]['latitude']/1000000;
            $dropoffLong = $dropoffData[0]['longitude']/1000000;

            $new_due_time = $this->getTravelTime($pickupLat,$pickupLong,$dropoffLat,$dropoffLong) + $due_time;

            $dt3 = date("Y-m-d H:i:s", substr($new_due_time, 0, 10));
            $new_convert_date = date("Y-m-d H:i:s",strtotime("+15 minutes", strtotime($dt3)));


            $date2 = new \DateTime($new_convert_date);
            $due_time_etc2 = $date2->format('U');

            $pickupUpdate = SprintTasks::where('sprint_id',$sprint_id)->where('type','pickup')->update(['due_time'=>$due_time,'eta_time'=>$due_time,'etc_time'=>$due_time_etc]);

            $dropoffUpdate = SprintTasks::where('sprint_id',$sprint_id)->where('type','dropoff')->update(['due_time'=>$new_due_time,'eta_time'=>$new_due_time,'etc_time'=>$due_time_etc2]);

            $SprintData = SprintTasks::where('sprint_id',$sprint_id)->get();
            $response = SprintTaskResource::collection($SprintData);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "Due Time Updated Successfully");
    }

    //Mark:- Order Checkout POST
    public function OrderCheckout(Request $request,$vendor_id,$sprint_id)
    {

        $response=[];
        $status_id = [];
        DB::beginTransaction();
        try {
            $date = date("Y-m-d H:i:s");
            $sprintData = Sprint::where('id',$sprint_id)->update(['status_id'=>61,'checked_out_at'=>$date]);

            $taskData = SprintTasks::where('sprint_id',$sprint_id)->update(['status_id'=>61]);

            $taskId = SprintTasks::where('sprint_id',$sprint_id)->pluck('id')->toArray();

            for ($i=0;$i<count($taskId);$i++){

                $insertHistory = [
                    'sprint__tasks_id' => $taskId[$i],
                    'sprint_id' => $sprint_id,
                    'status_id' => 61,
                    'active' => 0,
                    'date'=>$date,
                    'created_at'=>$date
                ];
                SprintTaskHistory::create($insertHistory);

            }


            $SprintData = SprintTasks::where('sprint_id',$sprint_id)->get();
            $response = SprintTaskResource::collection($SprintData);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "Checked Out Successfully");
    }

    //Mark:- Order Task DELETE
    public function OrderTaskDelete(Request $request,$sprint_id,$task_id)
    {

        $response=[];
        $status_id = [];
        DB::beginTransaction();
        try {
            $date = date("Y-m-d H:i:s");

            $taskData = SprintTasks::where('id',$task_id)->update(['deleted_at'=>$date]);

            $TaskData = SprintTasks::where('id',$task_id)->get();
            $response = SprintTaskResource::collection($TaskData);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "Deleted Order Successfully");
    }

    //Mark:- Travel Time GET
    public function getTravelTime($pickupLat,$pickupLong,$dropoffLat,$dropoffLong){

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://maps.googleapis.com/maps/api/distancematrix/json?destinations=$pickupLat,$pickupLong&origins=$dropoffLat,$dropoffLong&key=AIzaSyDTK4viphUKcrJBSuoidDqRhVA4AWnHOo0",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30000,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                // Set here requred headers
                "accept: */*",
                "accept-language: en-US,en;q=0.8",
                "content-type: application/json",
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            $response_data = json_decode($response);
            return $response_data->rows[0]->elements[0]->duration->value;
        }

    }

    //Mark:- Cancel TASK POST
    public function cancelOrder(Request $request){

        $data_input = $request->all();
        $validation_data=$data_input;

        if (json_last_error() != JSON_ERROR_NONE) {
            return RestAPI::response("Json request is not valid / ".json_last_error_msg(), false, json_last_error());
        }

        $sprint_id = $validation_data["sprint_id"];
        DB::beginTransaction();
        try {
            $date = date("Y-m-d H:i:s");

            //Updating Sprint Data
            $sprintDataUpdate = Sprint::where('id',$sprint_id)->update(['status_id'=>36]);
            $sprintData = Sprint::where('id',$sprint_id)->whereNull('deleted_at')->first();

            //Updating Task Data
            $taskDataUpdate = SprintTasks::where('sprint_id',$sprint_id)->update(['status_id'=>36]);
            $taskData = SprintTasks::where('sprint_id',$sprint_id)->pluck('id')->toArray();
            foreach ($taskData as $x){
                SprintTaskHistory::insert(['created_at'=>$date,'date'=>$date,'sprint__tasks_id'=>$x,'sprint_id'=>$sprint_id,'status_id'=>36,'active'=>1]);
            }

            //Updating Borderless Data
            $borderlessDatapUpdate = BoradlessDashboard::where('sprint_id',$sprint_id)->update(['task_status_id'=>36]);
            $borderlessData = BoradlessDashboard::where('sprint_id',$sprint_id)->pluck('id')->toArray();

            foreach ($borderlessData as $x){
                SprintTaskHistory::insert(['created_at'=>$date,'date'=>$date,'sprint__tasks_id'=>$x,'sprint_id'=>$sprint_id,'status_id'=>36,'active'=>1]);
            }

            $SprintData = Sprint::whereNull('deleted_at')->where('id',$sprint_id)->get();
            $response = AllOrderStatusResource::collection($SprintData);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "Canceled Order Successfully");
    }

    public function getStatusCodesWithKey($type = null)
    {
        if($type != null)
        {
            $status_codes = config('statuscodes.'.$type);
        }
        else
        {
            $status_codes = config('statuscodes');
        }
        return $status_codes;
    }

    public function orderDetailByTrackingId(Request $request)
    {
        $data = $request->all();

        DB::beginTransaction();
        try {

            $merchantRecord = MerchantsIds::where('tracking_id', $data['tracking_id'])->first();

//            dd($merchantRecord);

            if (!empty($merchantRecord)) {

                $response = new TrackingDetailsResource($merchantRecord);
            } else {
                return RestAPI::response('No record found agianst this Tracking Id', false);
            }


            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'Tracking Details');
    }

}
