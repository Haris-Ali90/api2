<?php
namespace App\Http\Controllers\Api;

// use App\Classes\RestAPI;
use App\Models\BorderlessDashboard;
use App\Models\BorderlessFailedOrders;
use App\Models\MerchantsIds;
use App\Models\SprintContact;
use App\Models\SprintTasks;
use Illuminate\Http\Request;
use File;
// use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Support\Facades\DB;
use App\Models\ZoneVendorRelationship;
use App\Models\Vendors;
use App\Models\Vendor;
use App\Models\Sprint;
use App\Models\Location;
use App\Models\MainfestFields;

use App\Models\Hub;
use App\Models\ProcessedXmlFiles;
use App\Models\XmlFailedOrders;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Geocoder\Geocoder;
use App\Models\State;
use App\Models\City;
use Twilio\Rest\Client;

class ManifestOrderCreationController extends ApiBaseController{

    // Function to check Canadian address
    private $global_sprint_obj ;

    public function canadian_address($address){
        if (substr($address, -1) == ' ') {
            $postal_code = substr($address, -8, -1);
        } else {
            $postal_code = substr($address, -7);
        }

        if (substr($postal_code, 0, 1) == ' ' || substr($postal_code, 0, 1) == ',') {
            $postal_code = substr($postal_code, -6);
        }

        if (substr($postal_code, -1) == ' ') {
            $postal_code = substr($postal_code, 0, 6);
        }

        $address1 = substr($address, 0, -7);
        //parsing address for suite-Component
        $address = explode(' ', trim($address));
        $address[0] = str_replace('-', ' ', $address[0]);
        $address = implode(" ", $address);
        // url encode the address
        $address = urlencode($address);
        // google map geocode api url
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key=AIzaSyDTK4viphUKcrJBSuoidDqRhVA4AWnHOo0";
        // get the json response
        $resp_json = file_get_contents($url);
        // decode the json
        $resp = json_decode($resp_json, true);
        // response status will be 'OK', if able to geocode given address
        if ($resp['status'] == 'OK') {
            $completeAddress = [];
            $addressComponent = $resp['results'][0]['address_components'];
            // get the important data
            for ($i = 0; $i < sizeof($addressComponent); $i++) {
                if ($addressComponent[$i]['types'][0] == 'administrative_area_level_1') {
                    $completeAddress['division'] = $addressComponent[$i]['short_name'];
                } elseif ($addressComponent[$i]['types'][0] == 'locality') {
                    $completeAddress['city'] = $addressComponent[$i]['short_name'];
                } else {
                    $completeAddress[$addressComponent[$i]['types'][0]] = $addressComponent[$i]['short_name'];
                }
                if ($addressComponent[$i]['types'][0] == 'postal_code' && $addressComponent[$i]['short_name'] != $postal_code) {
                    $completeAddress['postal_code'] = $postal_code;
                }
            }

            if (array_key_exists('subpremise', $completeAddress)) {
                $completeAddress['suite'] = $completeAddress['subpremise'];
                unset($completeAddress['subpremise']);
            } else {
                $completeAddress['suite'] = '';
            }
            if ($resp['results'][0]['formatted_address'] == $address1) {
                $completeAddress['address'] = $resp['results'][0]['formatted_address'];
            } else {
                $completeAddress['address'] = $address1;
            }
            $completeAddress['lat'] = $resp['results'][0]['geometry']['location']['lat'];
            $completeAddress['lng'] = $resp['results'][0]['geometry']['location']['lng'];
            unset($completeAddress['administrative_area_level_2']);
            unset($completeAddress['street_number']);
            return $completeAddress;
        }
        else {
            $response = json_encode(array(403, $resp['status']));
            return $response;
        }
    }

    public function google_address($address,$zip_code){
        if (substr($address, -1) == ' ') {
            $postal_code = substr($address, -8, -1);
        } else {
            $postal_code = substr($address, -7);
        }

        if (substr($postal_code, 0, 1) == ' ' || substr($postal_code, 0, 1) == ',') {
            $postal_code = substr($postal_code, -6);
        }

        if (substr($postal_code, -1) == ' ') {
            $postal_code = substr($postal_code, 0, 6);
        }

        // $address1 = substr($address, 0, -7);
        $address1 = $address;
        //parsing address for suite-Component
        $address = explode(' ', trim($address));
        $address[0] = str_replace('-', ' ', $address[0]);
        $address = implode(" ", $address);
        // url encode the address
        $address = urlencode($address);
        // google map geocode api url
        // $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key=AIzaSyDTK4viphUKcrJBSuoidDqRhVA4AWnHOo0";
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}components=country:canada|postal_code:{$zip_code}&key=AIzaSyDTK4viphUKcrJBSuoidDqRhVA4AWnHOo0";
        // get the json response
        $resp_json = file_get_contents($url);
        // decode the json
        $resp = json_decode($resp_json, true);
        // response status will be 'OK', if able to geocode given address
        if ($resp['status'] == 'OK') {
            $completeAddress = [];
            $addressComponent = $resp['results'][0]['address_components'];
            // get the important data
            for ($i = 0; $i < sizeof($addressComponent); $i++) {
                if ($addressComponent[$i]['types'][0] == 'administrative_area_level_1') {
                    $completeAddress['division'] = $addressComponent[$i]['short_name'];
                    $completeAddress['division_long'] = $addressComponent[$i]['long_name'];
                } elseif ($addressComponent[$i]['types'][0] == 'locality') {
                    $completeAddress['city'] = $addressComponent[$i]['short_name'];
                } else {
                    $completeAddress[$addressComponent[$i]['types'][0]] = $addressComponent[$i]['short_name'];
                }
                if ($addressComponent[$i]['types'][0] == 'postal_code' && $addressComponent[$i]['short_name'] != $postal_code) {
                    //$completeAddress['postal_code'] = $postal_code;
                    $completeAddress['postal_code'] = $addressComponent[$i]['short_name'];
                }
            }

            if (array_key_exists('subpremise', $completeAddress)) {
                $completeAddress['suite'] = $completeAddress['subpremise'];
                unset($completeAddress['subpremise']);
            } else {
                $completeAddress['suite'] = '';
            }
            if ($resp['results'][0]['formatted_address'] == $address1) {
                $completeAddress['address'] = $resp['results'][0]['formatted_address'];
            } else {
                $completeAddress['address'] = $address1;
            }
            $completeAddress['latitude'] = $resp['results'][0]['geometry']['location']['lat'];
            $completeAddress['longitude'] = $resp['results'][0]['geometry']['location']['lng'];
            unset($completeAddress['administrative_area_level_2']);
            unset($completeAddress['street_number']);
            return $completeAddress;
        }
        else {
            $response = json_encode(array("status" =>403,  $resp['status']));
            return $response;
        }
    }

    // Pickup task function
    private function pickupTask($creatorId, $location){

        $vendor = Vendor::find($creatorId);

        $location = Location::find($vendor->location_id);

        $pickupData = [
            "type" => "pickup",
            "sprint" => [
                "creator_id" => $vendor->id,
                "creator_type" => "vendor",
                "vehicle_id" => $vendor->vehicle_id
            ],
            "contact" => [
                "name" => $vendor->first_name.' '.$vendor->last_name,
                "email" => $vendor->email,
                "phone" => $vendor->phone
            ],
            "location" => [
                //"address" => $location->attributes['address'],
                //"postal_code" => $location->attributes['postal_code'],
                "address" => $location->address,
                "postal_code" => $location->postal_code,
                "country" => "CA"
            ],
            "notification_method" => 'none'
        ];

        $pickUpResponse = $this->processTask($pickupData);
        $task=new SprintTasks();
        $task->pin=(string) rand(1000, 9999);
        $task->sprint_id=$pickUpResponse[1]->id;
        $task->location_id=$vendor->location_id;
        $task->contact_id= $vendor->contact_id;
        $task->status_id= 61;
        $task->ordinal= 1;
        $task->due_time = strtotime(date('Y-m-d H:i:s'));
        $task->eta_time = strtotime(date('Y-m-d H:i:s'));
        $task->etc_time = strtotime(date('Y-m-d H:i:s'));
        $task->save();
        // $task = SprintTasks::create();// creating sprint Task
        // $task['pin'] = (string) rand(1000, 9999);
        // $task['sprint_id'] = $pickUpResponse[1]->id;
        // $task['location_id'] = $vendor->location_id;
        // $task['contact_id'] = $vendor->contact_id;
        // $updatingSprintId = SprintTasks::where('id', '=', $task->id)
        //     ->update(
        //         array(
        //         'sprint_id' => $task['sprint_id'],
        //         'pin' => $task['pin'],
        //         'due_time' => strtotime(date('Y-m-d H:i:s')),
        //         'eta_time' => strtotime(date('Y-m-d H:i:s')),
        //         'etc_time' => strtotime(date('Y-m-d H:i:s')),
        //         'location_id' =>  $task['location_id'],
        //         'contact_id' => $task['contact_id'],
        //         'ordinal' => 1,
        //         'status_id' => 61
        //         )
        //     );

        //$task['tracking'] = TrackingCodes::createFor('task', $task->id);//generating hash and saving in tracking codes

        $pickUpResponse['task'] = $task;
        return $pickUpResponse;
    }

    // Process task function
    private function processTask($request_data){
        // Checking location and contact if exists
        try{
            $response = $this->isXmlOrderValid($request_data['location'],$request_data['sprint'],$request_data['contact']);
            if($response){
                return $response;
            }
            else{
                $response = json_encode(array("status" => 400, "message" => "Invalid address or contact info."));
                return $response;
            }
        }
        catch(\Exception $e){
            return false;
        }
    }

    // Process task function for dropoff address
    private function processTask2($request_data){
        // Checking location and contact if exists
        try{
            $response = $this->isXmlOrderValid2($request_data['location'],$request_data['sprint'],$request_data['contact']);

            if($response){
                return $response;
            }
            else{
                $response = json_encode(array("status" => 400, "message" => "Invalid address or contact info."));
                return $response;
            }
        }
        catch(\Exception $e){
            return false;
        }
    }

    // Checking if xml order is valid
    private function isXmlOrderValid($location,$sprint,$contact) {
        try{
            return array($this->initializeLocation($location),$this->iniSprint($sprint),$this->initializeContact($contact),true);
            // return true;
        }catch(\Exception $e){
            return false;
        }
    }

    // Checking if xml order is valid for dropoff address
    private function isXmlOrderValid2($location,$sprint,$contact) {
        try{
            return array($this->initializeLocation($location),$this->iniSprint2($sprint),$this->initializeContact($contact),true);
            // return true;
        }catch(\Exception $e){
            return false;
        }
    }

    // Checking location is valid
    private function initializeLocation($location) {
        if (empty($location)) {
            if (isset($location['id'])) {
                $location = Location::find($location['id']);
            } else {
                $location = new Location();
            }
        }
        return $location;
    }

    // Function for sprint
    private function iniSprint($sprint){
        // check if sprint is null
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
            4020 =>477503,
            4716=>477518,
            3008=>477607,
            1040=>477609,
			3009=>477587,
			477621=>477621,
            1205=>477589,
            3131=>477631,
            1031=>477629,
        );

        $stores = array_flip($store);
        if(in_array($sprint['creator_id'],$stores)){
            $store_id = $stores[$sprint['creator_id']];
        }

        if ($sprint === null) {
            if (isset($sprint['id'])) {
                $sprint['id'] = Sprint::find($sprint['id']);
                if(isset($store_id)){
                    Sprint::where('id','=',$sprint['id'])->update(['store_num'=> $store_id]);
                }
            }
        }
        else{
            $sprint['status_id'] = 61;
            $sprint['deleted_at'] = null;
            $sprint['checked_out_at'] = date('Y-m-d H:i:s');
            $sprint['is_hub'] = 1;
            $sprint['optimize_route'] = 0;
            $sprint['is_cc_preauthorized'] = 0;

            if (!array_key_exists('is_sameday', $sprint)) {
                $sprint['is_sameday'] = 0;
            }

            if (!array_key_exists('merchant_order_num', $sprint)) {
                $sprint['merchant_order_num'] = 0;
            }
            $sprint = Sprint::create($sprint);
            if(isset($store_id)){
                Sprint::where('id','=',$sprint['id'])->update(['store_num'=> $store_id]);
            }
            $this->global_sprint_obj = $sprint;
            return $this->global_sprint_obj;
        }
    }

    // Function for sprint for drop off address
    private function iniSprint2($sprint){
        // check if sprint is null
        if ($sprint !== null) {
            if (isset($sprint['id'])) {
                $sprint['id'] = Sprint::find($sprint['id']);
            }
            return array("sprint" => $sprint['id']);
        }
        else{
            $sprint['status_id'] = 61;
            $sprint['deleted_at'] = null;
            $sprint['is_cc_preauthorized'] = 0;

            if (!array_key_exists('is_sameday', $sprint)) {
                $sprint['is_sameday'] = 0;
            }

            if (!array_key_exists('merchant_order_num', $sprint)) {
                $sprint['merchant_order_num'] = 0;
            }
            //$sprint = Sprint::create($sprint);
            return $sprint;
        }
    }

    // Checking contact is valid
    private function initializeContact($contact){
        if (empty($contact)) {
            if (isset($contact['id'])) {
                $contact = ContactEnc::find($contact['id']);
            } else {
                $contact = new ContactEnc();
            }
        }
        return $contact;
    }

    // check valid address
    private function isAddressValid($address){
        try {

            $client = new \GuzzleHttp\Client();

            $geocoder = new Geocoder($client);

            $geocoder->setApiKey(config('app.google_api_key_prod'));

            if(empty($address)){
                return false;
            }

            $address = $geocoder->getCoordinatesForAddress($address);

        }
        catch (\Exception $e) {
            return false;
        }
        return true;
    }

    private function dropoffTask($dropoffData,$weight,$weight_unit){
        $dropoffData['type'] = 'dropoff';
        if(!isset($dropoffData['location']['city'])){
            $dropoffData['location']['city'] = (isset($dropoffData['location']['administrative_area_level_3'])) ? $dropoffData['location']['administrative_area_level_3']: $dropoffData['location']['political'];
        }
        // checking if dropoff is being failed
        if($dropoffData['is_failed'] == 0){
            $dropOffResponse = $this->processTask2($dropoffData);
            $task = new SprintTasks();
            $task->pin = $dropoffData['sprint']['pin'];
            $task->sprint_id = $dropoffData['sprint']['id'];
            $task->status_id = 61;
            $task->ordinal = 2;
            $task->type = 'dropoff';
            $task->due_time = strtotime(date('Y-m-d H:i:s'));
            $task->eta_time = strtotime(date('Y-m-d H:i:s'));
            $task->etc_time = strtotime(date('Y-m-d H:i:s'));
            $task->save();
            // $task = SprintTasks::create();// creating sprint Task
            $state = State::where('code','=',$dropoffData['location']['division'])->first();
            if(empty($state)){
                $state_id = DB::table('states')->insertGetId([
                    'country_id' => '43',
                    'tax_id'=> '1',
                    'name' => $dropoffData['location']['division'],
                    'code' => $dropoffData['location']['division'],
                 ]);
            }
            else{
                $state_id = State::where('code','=',$dropoffData['location']['division'])->first()->id;
            }
            $city = City::where('name','=',$dropoffData['location']['city'])->first();
            if(empty($city)){
                $city_id= DB::table('cities')->insertGetId([
                    'country_id' =>'43',
                    'state_id' => $state_id,
                    'name' =>$dropoffData['location']['city']
                ]);
            }
            else{
                $city_id = City::where('name','=',$dropoffData['location']['city'])->first()->id;
            }
            $key = 'c9e92bb1ffd642abc4ceef9f4c6b1b3aaae8f5291e4ac127d58f4ae29272d79d903dfdb7c7eb6e487b979001c1658bb0a3e5c09a94d6ae90f7242c1a4cac60663f9cbc36ba4fe4b33e735fb6a23184d32be5cfd9aa5744f68af48cbbce805328bab49c99b708e44598a4efe765d75d7e48370ad1cb8f916e239cbb8ddfdfe3fe';
            $iv ='f13c9f69097a462be81995330c7c68f754f0c6026720c16ad2c1f5f316452ee000ce71d64ed065145afdd99b43c0d632b1703fc6a6754284f5d19b82dc3697d664dc9f66147f374d46c94cf23a78f14f0c6823d1cbaa19c157b4cb81e106b79b11593dcddf675951bc07f54528fc8c03cf66e9c437595d1cac658a737ab1183f';
            $task['pin'] = (string) rand(1000, 9999);
            //$task['sprint_id'] = $dropOffResponse[1]['sprint']->id;
            $task['sprint_id'] = $this->global_sprint_obj->id;
            $task['ordinal'] = 2;
            $latitude_int = str_replace(".", "", $dropoffData['location']['latitude']);;
            $longitude_int = $dropoffData['location']['longitude']* 1000000;

            if(strlen($latitude_int) > 8){
                $latitude_int = (int)substr($latitude_int, 0, 8);
            }
            else{
                $diff = 8 - strlen($latitude_int);
                $fixed_lat = $latitude_int;
                while($diff>0){
                    $fixed_lat .= "0";
                    $diff--;
                }
                $latitude_int = $fixed_lat;
            }

            if(strlen($longitude_int) > 9){
                $longitude_int = (int)substr($longitude_int, 0, 9);
            }
            else{
                $diff = 9 - strlen($longitude_int);
                $fixed_lng = $longitude_int;
                while($diff>0){
                    $fixed_lng .= "0";
                    $diff--;
                }
                $longitude_int = $fixed_lng;
            }

            $task['location_id'] = Location::where('latitude','=', $dropoffData['location']['latitude'])->where('longitude',$dropoffData['location']['longitude'])->first(); // getting location id

            if($task['location_id'] === null ){
                // create location if not exists
                $task['location_id'] = Location::create(["address"=> $dropoffData['location']['address'],
                    "city_id" =>$city_id,
                    "state_id"=>$state_id,
                    "country_id"=>43,
                    "postal_code"=>$dropoffData['location']['postal_code'],
                    "buzzer"=>"",
                    "suite"=>"",
                    "latitude"=> (int) $latitude_int ,
                    "longitude"=> (int) $longitude_int,
                    "location_type"=>'',
                    ]);

                $dropoffData['location']['address'] = str_replace("'", '', $dropoffData['location']['address']);
                $dropoffData['location']['address'] = str_replace('"', '', $dropoffData['location']['address']);
                $enc_location = DB::table('locations_enc')->insert(
                    array(
                        'id' => $task['location_id']->id,
                        'address' => DB::raw("AES_ENCRYPT('".$dropoffData['location']['address']."', '".$key."', '".$iv."')"),
                        'city_id' => $city_id,
                        'state_id' => $state_id,
                        'country_id' => '43',
                        'postal_code' => DB::raw("AES_ENCRYPT('".$dropoffData['location']['postal_code']."', '".$key."', '".$iv."')"),
                        'latitude' => DB::raw("AES_ENCRYPT('".(int) $latitude_int ."', '".$key."', '".$iv."')"),
                        'longitude' => DB::raw("AES_ENCRYPT('". (int) $longitude_int."', '".$key."', '".$iv."')"),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                        'type' => $dropoffData['type']
                        )
                );
            }


            // if email and phone empty
            if(empty($dropoffData['contact']['email']) && empty($dropoffData['contact']['phone'])){
                $task['contact_id'] = SprintContact::create($dropoffData['contact']);
                $enc_contact = DB::table('contacts_enc')->insert(
                    array(
                        'id' => $task['contact_id']->id,
                        'name' => DB::raw("AES_ENCRYPT('".$dropoffData['contact']['name']."', '".$key."', '".$iv."')"),
                        'email' => DB::raw("AES_ENCRYPT('".$dropoffData['contact']['email']."', '".$key."', '".$iv."')"),
                        'phone' => DB::raw("AES_ENCRYPT('".$dropoffData['contact']['phone']."', '".$key."', '".$iv."')"),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    )
                );
            }
            else{
                //checking email
                $task['contact_id'] = SprintContact::where('email','=',$dropoffData['contact']['email'])
                ->where('name','=',$dropoffData['contact']['name'])
                ->where('phone','=',$dropoffData['contact']['phone'])
                ->first(); // getting contact id
                // if email is null
                if($task['contact_id']===null){
                    // creating new contact  and return
                    $task['contact_id'] = SprintContact::create($dropoffData['contact']);
                    $enc_contact = DB::table('contacts_enc')->insert(
                        array(
                            'id' => $task['contact_id']->id,
                            'name' => DB::raw("AES_ENCRYPT('".$dropoffData['contact']['name']."', '".$key."', '".$iv."')"),
                            'email' => DB::raw("AES_ENCRYPT('".$dropoffData['contact']['email']."', '".$key."', '".$iv."')"),
                            'phone' => DB::raw("AES_ENCRYPT('".$dropoffData['contact']['phone']."', '".$key."', '".$iv."')"),
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        )
                    );
                }
                else{
                    $task['contact_id']->id = $task['contact_id']->id; // getting contact id
                }
            }

            $updatingSprintId = SprintTasks::where('id', '=', $task->id)
                ->update(array
                    (
                        'location_id' =>  $task['location_id']->id,
                        'contact_id' => $task['contact_id']->id,
                    )
                );

            MerchantsIds::create(['task_id' => $task->id,
                'merchant_order_num' => isset($dropoffData['sprint']['merchant_order_number']) ? $dropoffData['sprint']['merchant_order_number'] : null,
                'end_time' => isset($dropoffData['sprint']['end_time']) ? $dropoffData['sprint']['end_time'] : null,
                'start_time' => isset($dropoffData['sprint']['start_time']) ? $dropoffData['sprint']['start_time'] : null,
                'tracking_id' => isset($dropoffData['sprint']['tracking_id']) ? $dropoffData['sprint']['tracking_id'] : null,
                'weight' => isset($weight) ? $weight : null,
                'weight_unit' => isset($weight_unit) ? $weight_unit : null,
                'address_line2' => isset($dropoffData['location']['address']) ? $dropoffData['location']['address'] : null]);

            $dropOffResponse['task'] = $task;
            return $dropOffResponse;

        }

    }

    private function updateTime($sprintId, $due){

        $sprint = Sprint::find($sprintId);

        //$current = strtotime(date('y-m-d 21:00:00'));

        //$dueTime = new \DateTime();
        //$dueTime->setTimestamp($current);
        $dueTime = new \DateTime();
        $dueTime->setTimestamp(strtotime($due->format('Y-m-d H:i:s')));
        $sprint->setDueTime($dueTime);
        $sprint->refreshCharges();
        $sprint->save();
        $sprint->saveTasks();

    }

    // Read xml file and create order
    public function post_xml_order(){
        $files = array_diff(File::files(public_path()."/shipmentdata/walmart/manifest/"), array('.', '..'));
        shuffle($files);
        $fileResp = [];
//        $walMartVendorIds = [477621,477587,477607,477589,477641,477631,477629];
        //looping through files provided in the folder
        foreach ($files as $file) {
            $file = str_replace('\\', '/', $file);
            // $exists = ProcessedXmlFiles::where('file_name','=',$file)->first();
            $exists = ProcessedXmlFiles::where('file_name','=',$file)->first();

            //if files not present in db, inserting files in db
            if( empty($exists) ){

                ProcessedXmlFiles::create([ 'file_name' => $file ]);
                #Read the file
                $fp = fopen($file, "r") or die("Couldn't Open"); //Open the file
                $FoundXmlTagStep = 0;
                $FoundEndXMLTagStep = 0;
                $curXML = "";
                $count  = 0;
                $firstXMLTagRead = true;
                while(!feof($fp)) //Loop through the file, read it till the end.
                {
                    $data = fgets($fp, 2);
                    if($FoundXmlTagStep==0 && $data == "<"){
                        $FoundXmlTagStep=1;
                    }
                    else if ($FoundXmlTagStep==1 && $data == "?"){
                        $FoundXmlTagStep=2;
                    }
                    else if ($FoundXmlTagStep==2 && $data == "x"){
                        $FoundXmlTagStep=3;
                    }
                    else if ($FoundXmlTagStep==3 && $data == "m"){
                        $FoundXmlTagStep=4;
                    }
                    else if ($FoundXmlTagStep==4 && $data == "l"){
                        $FoundXmlTagStep=5;
                    }
                    else if ($FoundXmlTagStep!=5){
                        $FoundXmlTagStep=0;
                    }
                    if ($FoundXmlTagStep==5){
                        if ($firstXMLTagRead)
                        {
                            $firstXMLTagRead = false;
                            $curXML = "<?xm";
                        }
                        if($data == '&'){
                            $data = "";
                        }

                        $curXML .= $data;
                    }
                }
                fclose($fp); //Close file

                $xml = null;
                try{
                    // $xml = simplexml_load_file($file);
                    $xml = simplexml_load_string($curXML);
                    unlink($file);
                }
                catch(\Exception $e){
                    continue;
                }

                if(empty($xml->message) ){
                    continue;
                }

                try{
                    $mainfestData = [
                        'vendor_id' => $xml->message->manifestHeader->carrierInternalID,
                        'xsi' => $xml['xsi:noNamespaceSchemaLocation'],
                        'sendingPartyID' => $xml['sendingPartyID'],
                        'receivingPartyID' => $xml['receivingPartyID'],
                        'warehouseLocationID' => $xml->message->manifestHeader->warehouseLocationID,
                        'manifestCreateDateTime' => $xml->message->manifestHeader->manifestCreateDateTime,
                        'carrierInternalID' => $xml->message->manifestHeader->carrierInternalID,
                        'manifestNumber' => $xml->message->manifestHeader->manifestNumber,
                        'carrierAccountID' => $xml->message->manifestHeader->carrierAccountID,
                        'shipmentDate' => $xml->message->manifestHeader->shipmentDate,
                        'currencyCode' => $xml->message->manifestHeader->currencyCode,
                        'shipFromAddressType' => $xml->message->manifestHeader->shipFromAddress['AddressType'],
                        'shipFromAddressName' => $xml->message->manifestHeader->shipFromAddress->name,
                        'shipFromAddressLine1' => $xml->message->manifestHeader->shipFromAddress->addressLine1,
                        'shipFromAddressCity' => $xml->message->manifestHeader->shipFromAddress->city,
                        'shipFromAddressStateProvince' => preg_replace('/[^A-Za-z0-9]/', '',$xml->message->manifestHeader->shipFromAddress->stateChoice->stateProvince),
                        'shipFromAddressZip' => $xml->message->manifestHeader->shipFromAddress->zip,
                        'shipFromAddressCountryCode' => $xml->message->manifestHeader->shipFromAddress->countryCode,
                        'shipFromAddressCountryName' => $xml->message->manifestHeader->shipFromAddress->countryName,
                        'amazonTaxID' => $xml->message->manifestHeader->shipperInformation->amazonTaxID,
                        'totalShipmentQuantity' => $xml->message->manifestSummary->totalShipmentQuantity->quantity,
                        'totalShipmentQuantityUnitOfMeasure' => $xml->message->manifestSummary->totalShipmentQuantity->quantity['unitOfMeasure'],
                        'totalShipmentValue' => $xml->message->manifestSummary->totalShipmentValue->monetaryAmount,
                        'totalShipmentValueCurrencyISOCode' => $xml->message->manifestSummary->totalShipmentValue->monetaryAmount['currencyISOCode'],
                        'totalDeclaredGrossWeight' => $xml->message->manifestSummary->totalDeclaredGrossWeight->weightValue,
                        'totalDeclaredGrossWeightUnitOfMeasure' => $xml->message->manifestSummary->totalDeclaredGrossWeight->weightValue['unitOfMeasure'],
                        'totalActualGrossWeight' => $xml->message->manifestSummary->totalActualGrossWeight->weightValue,
                        'totalActualGrossWeightUnitOfMeasure' => $xml->message->manifestSummary->totalActualGrossWeight->weightValue['unitOfMeasure']

                    ];

                }
                catch(\Exception $e){
                    continue;
                }
                $vendor_id_xml = $xml->message->manifestHeader->carrierInternalID;
                $vendor = Vendor::find($vendor_id_xml);
                $startTime = empty($vendor->attributes['order_start_time']) ? time() : $vendor->attributes['order_start_time'];
                // date_default_timezone_set("America/Toronto");
                $due = strtotime( date("Y-m-d "."09:00:00" ) );
                $dueTime = new \DateTime();
                $dueTime->setTimestamp($due);
                $dueTime->modify("+1 day");
                $response = [];
                $total_count = 0;
                $count_successful = 0;
                $count_failed = 0;
                $count_duplicate = 0;

                $hub = ZoneVendorRelationship::join('hub_zones', 'zone_vendor_relationship.zone_id', 'hub_zones.zone_id')
                    ->join('hubs', 'hub_zones.hub_id', 'hubs.id')
                    ->join('vendors','zone_vendor_relationship.vendor_id','vendors.id')
                    ->where('zone_vendor_relationship.vendor_id',$vendor_id_xml)
                    ->whereNull('hubs.deleted_at')
                    ->first();

                //checking hub exists
                if( empty($hub) ){
                    return response('Hub address not found',  400);
                }
                $pickupAddress = $this->canadian_address($hub->address);
                // checking postal code
                if(!isset($pickupAddress['postal_code'])){

                    return response("Postal code is required in hub address field.", 400);

                }

                foreach ($xml->message->manifestDetail->shipmentDetail as $shipment) {
                    $mainfestData['customerOrderNumber'] = $shipment->customerOrderNumber;
                    $mainfestData['consigneeAddressType'] = $shipment->consigneeAddress['AddressType'];
                    $mainfestData['consigneeAddressName'] = preg_replace('/[^A-Za-z0-9]/', ' ',$shipment->consigneeAddress->name);
                    $mainfestData['consigneeAddressLine1'] = $shipment->consigneeAddress->addressLine1;
                    $mainfestData['consigneeAddressLine2'] = $shipment->consigneeAddress->addressLine2;
                    $mainfestData['consigneeAddressLine3'] = $shipment->consigneeAddress->addressLine3;
                    // $mainfestData['consigneeAddressCity'] = $shipment->consigneeAddress->city;
                    $mainfestData['consigneeAddressCity'] = preg_replace('/[^A-Za-z0-9]/', '',$shipment->consigneeAddress->city);
                    $mainfestData['consigneeAddressStateProvince'] = preg_replace('/[^A-Za-z0-9]/', '',$shipment->consigneeAddress->stateChoice->stateProvince);
                    //$mainfestData['consigneeAddressZip'] = $shipment->consigneeAddress->zip;
                    $mainfestData['consigneeAddressZip'] = str_replace(' ', '', $shipment->consigneeAddress->zip);
                    $mainfestData['consigneeAddressCountryCode'] = $shipment->consigneeAddress->countryCode;
                    $mainfestData['consigneeAddressCountryName'] = $shipment->consigneeAddress->countryName;
                    $mainfestData['consigneeAddressContactPhone'] = $shipment->consigneeAddress->contactPhone;
                    $mainfestData['consigneeAddressContactEmail'] = $shipment->consigneeAddress->contactEmail;
                    $mainfestData['AmzShipAddressUsage'] = $shipment->consigneeAddress->amzShipAddressUsage;
                    $mainfestData['AddressType'] = $shipment->deliveryPreferences->addressType;
                    $mainfestData['SafePlace'] = $shipment->deliveryPreferences->SafePlace;
                    $mainfestData['DeliverToCustOnly'] = $shipment->deliveryPreferences->DeliverToCustOnly;
                    $mainfestData['IsSignatureRequired'] = $shipment->deliveryPreferences->IsSignatureRequired;
                    $mainfestData['AgeVerificationRequired'] = $shipment->deliveryPreferences->AgeVerificationRequired;
                    $mainfestData['encryptedShipmentID'] = $shipment->shipmentPackageInfo->cartonID->encryptedShipmentID;
                    $mainfestData['packageID'] = $shipment->shipmentPackageInfo->cartonID->packageID;
                    $mainfestData['trackingID'] = $shipment->shipmentPackageInfo->cartonID->trackingID;
                    $mainfestData['batteryStatements'] = $shipment->shipmentPackageInfo->batteryStatements;
                    $mainfestData['amazonTechnicalName'] = $shipment->shipmentPackageInfo->packageShipmentMethod->amazonTechnicalName;
                    $mainfestData['shipZone'] = $shipment->shipmentPackageInfo->shipZone;
                    $mainfestData['shipSort'] = $shipment->shipmentPackageInfo->shipSort;
                    $mainfestData['scheduledDeliveryDate'] = $shipment->shipmentPackageInfo->scheduledDeliveryDate;
                    $mainfestData['valueOfGoodsChargeOrAllowance'] = $shipment->shipmentPackageInfo->valueOfGoods->chargeOrAllowance;
                    $mainfestData['valueOfGoodsMonetaryAmount'] = $shipment->shipmentPackageInfo->valueOfGoods->monetaryAmount;
                    //$mainfestData['valueOfGoodsCurrencyISOCode'] = $shipment->shipmentPackageInfo->valueOfGoods->monetaryAmount['currencyISOCode'];
                    $mainfestData['packageCostChargeOrAllowance'] = $shipment->shipmentPackageInfo->packageCost->chargeOrAllowance;
                    $mainfestData['packageCostMonetaryAmount'] = $shipment->shipmentPackageInfo->packageCost->monetaryAmount;
                    //$mainfestData['packageCostCurrencyISOCode'] = $shipment->shipmentPackageInfo->packageCost->monetaryAmount['currencyISOCode'];
                    $mainfestData['declaredWeightValue'] = $shipment->shipmentPackageInfo->shipmentPackageActualGrossWeight->weightValue;
                    $mainfestData['declaredUnitOfMeasure'] = $shipment->shipmentPackageInfo->shipmentPackageActualGrossWeight->weightValue['unitOfMeasure'];
                    $mainfestData['actualWeightValue'] = $shipment->shipmentPackageInfo->shipmentPackageActualGrossWeight->weightValue;
                    //$mainfestData['actualUnitOfMeasure'] = $shipment->shipmentPackageInfo->shipmentPackageActualGrossWeight->weightValue['unitOfMeasure'];
                    $mainfestData['lengthValue'] = $shipment->shipmentPackageInfo->shipmentPackageDimensions->lengthValue;
                    // $mainfestData['lengthUnitOfMeasure'] = $shipment->shipmentPackageInfo->shipmentPackageDimensions->lengthValue['unitOfMeasure'];
                    $mainfestData['heightValue'] = $shipment->shipmentPackageInfo->shipmentPackageDimensions->heightValue;
                    //$mainfestData['heightUnitOfMeasure'] = $shipment->shipmentPackageInfo->shipmentPackageDimensions->heightValue['unitOfMeasure'];
                    $mainfestData['widthValue'] = $shipment->shipmentPackageInfo->shipmentPackageDimensions->widthValue;
                    //$mainfestData['widthUnitOfMeasure'] = $shipment->shipmentPackageInfo->shipmentPackageDimensions->widthValue['unitOfMeasure'];
                    $merchantOrderNum = (string)$shipment->customerOrderNumber;
                    $trackingId = (string)$shipment->shipmentPackageInfo->cartonID->trackingID;
                    $endTime = $shipment->consigneeAddress->amzShipAddressUsage == 'R' ? date('H:i',strtotime("21:00:00") ) : date('H:i',strtotime("21:00:00") );
                    //** Continue work from here this function */



                    // looping through multiple drop off
                    foreach ($shipment->shipmentPackageInfo->shipmentPackageItemDetail->itemDetails as $item){
                        $failed_address = (string)$item->itemconsigneeAddress1->addressLine1.', '.(string)$item->itemconsigneeAddress1->city.', '.(string)$item->itemconsigneeAddress1->zip.', '.(string)$item->itemconsigneeAddress1->countryCode;

                        if(!empty($item->cartonID->trackingID)){
                            $trackingId = (string) $item->cartonID->trackingID;
                        }
                        // checking if order is created or not
                        $order_exists = BorderlessDashboard::where('tracking_id','=',$trackingId)->whereNull('deleted_at')->first();
                        $order_exists_failed = BorderlessFailedOrders::where('tracking_num','=',$trackingId)->whereNull('deleted_at')->first();
                        $check_failed = 0;
                        if(!empty($order_exists_failed)){
                            $check_failed = 1;
                        }
                        if(empty($order_exists)) {
                            $pickUpTaskResponse = $this->pickupTask($vendor_id_xml, $pickupAddress);
                            //if response has some errors then return
                            if ($pickUpTaskResponse instanceof Laravel\Response) {

                                $response['failed_orders'][] = array(
                                    "tracking_id" => $trackingId,
                                    "merchant_order_num" => $merchantOrderNum,
                                    "reason" => json_encode($pickUpTaskResponse)
                                );

                                $count_failed++;
                                if($check_failed == 0){
                                    BorderlessFailedOrders::create(
                                        ['vendor_id' => $vendor_id_xml, 'customer_name' => $item->itemconsigneeAddress1->name,
                                            'customer_number' => $mainfestData['consigneeAddressContactPhone'],
                                            'tracking_num' => $trackingId,
                                            'merchant_order_number' => $merchantOrderNum,
                                            'customer_email' => $mainfestData['consigneeAddressContactEmail'],
                                            'address' => (string)$item->itemconsigneeAddress1->addressLine1,
                                            'address_line_2' => (string)$item->itemconsigneeAddress1->addressLine2,
                                            'weight' =>  $item->itemshipmentPackageActualGrossWeight1->weightValue . ' ' . strtolower($item->itemshipmentPackageActualGrossWeight1->weightValue['unitOfMeasure']),
                                            'response' => json_encode($pickUpTaskResponse)
                                        ]);
                                    MainfestFields::create($mainfestData);
                                }
                                else{
                                    BorderlessFailedOrders::where('tracking_num',$trackingId)
                                    ->update(['updated_at'=>date('Y-m-d H:i:s'), 'address' => (string)$item->itemconsigneeAddress1->addressLine1]);
                                }
                                continue;
                            } else {
                                // plucking sprintid
                                // $sprintId = $pickUpTaskResponse['sprint']['id'];
                                $sprintId = $pickUpTaskResponse[1]->id;
                                $pin = $pickUpTaskResponse['task']->pin;
                            }
                            //Log::info((string)$item->itemconsigneeAddress1->addressLine1.\Carbon\Carbon::now());
                            $postalCode = substr($shipment->consigneeAddress->zip,0,3);

                            $sprint = array(
                                'merchant_order_number' => $merchantOrderNum,
                                // 'start_time' => substr($startTime, 0, 5),
                                'start_time' => date('H:i',$startTime),
                                'end_time' => $endTime,
                                'tracking_id' => $trackingId,
                                'id'=> $sprintId,
                                'pin'=> $pin
                            );

                            $name = str_replace(['"',"'"], "", $item->itemconsigneeAddress1->name);
                            $email = str_replace(['"',"'"], "", $item->itemconsigneeAddress1->email);
                            $phone = str_replace(['"',"'"], "", $item->itemconsigneeAddress1->phone);



                            $contact = array(
                                'name' => (string)$name,
                                'email' => (string)$email,
                                'phone' => (string)$phone
                            );

                            //validate phone number using regex function defined in application helper
                            // $validate_number = validate_phone($phone);

                            $dropoffAddress = null;
                            $is_failed = 0;
                            $addressString=null;
                            $addressString = (string)$item->itemconsigneeAddress1->addressLine1.', '.(string)$item->itemconsigneeAddress1->city.', '.(string)$item->itemconsigneeAddress1->zip;

                            $condition=true;
                            while($condition){
                                $address = $this->addressParser($addressString); // function defined in address parser
                                if($address == $addressString){
                                    $condition=false;
                                }
                                else{
                                    $addressString = $address;
                                }
                            }

                            //validate dropoff address
                            if( !$this->isAddressValid($addressString) ){

                                $addressString = (string)$item->itemconsigneeAddress1->addressLine2;
                                $condition=true;

                                while($condition){
                                    $address = $this->addressParser($addressString);
                                    if($address == $addressString){
                                        $condition=false;
                                    }
                                    else{
                                        $addressString = $address;
                                    }
                                }

                                if( !$this->isAddressValid($addressString) ){

                                    $addressString = (string)$item->itemconsigneeAddress1->addressLine3;
                                    $condition=true;
                                    while($condition){
                                        $address = $this->addressParser($addressString);
                                        if($address == $addressString){
                                            $condition=false;
                                        }
                                        else{
                                            $addressString = $address;
                                        }
                                    }

                                    if( !$this->isAddressValid($addressString) ){
                                        $is_failed = 1;
                                        //none of the address fields are valid
                                        $response['failed_orders'][] = array(
                                            "tracking_id" => $trackingId,
                                            "merchant_order_num" => $merchantOrderNum,
                                            "reason" => 'Invalid dropoff address!.'
                                        );
                                        $count_failed ++;
                                        if($check_failed == 0){
                                            BorderlessFailedOrders::create(['vendor_id' => $vendor_id_xml, 'customer_name' => $item->itemconsigneeAddress1->name, 'customer_number' => $item->itemconsigneeAddress1->phone,
                                            'tracking_num' => $trackingId, 'merchant_order_number' => $merchantOrderNum, 'customer_email' => $item->itemconsigneeAddress1->email,
                                            'address' => (string)$item->itemconsigneeAddress1->addressLine1, 'address_line_2' =>(string) $item->itemconsigneeAddress1->addressline2,
                                            'weight' =>  $item->itemshipmentPackageActualGrossWeight1->weightValue . ' ' . strtolower($item->itemshipmentPackageActualGrossWeight1->weightValue['unitOfMeasure']),
                                            'sprint_id' => $sprintId,
                                            'response' => 'Invalid dropoff address!.']);
                                            MainfestFields::create($mainfestData);
                                        }
                                        else{
                                            BorderlessFailedOrders::where('tracking_num',$trackingId)
                                            ->update(['updated_at'=>date('Y-m-d H:i:s'), 'address' => (string)$item->itemconsigneeAddress1->addressLine1]);
                                        }
                                        continue;
                                    }
                                    else{
                                        $dropoffAddress = $addressString;
                                    }
                                }
                                else{
                                    $dropoffAddress =$addressString;
                                }
                            }

                            else{
                                $dropoffAddress = $addressString;
                            }

                            $city = (string)$item->itemconsigneeAddress1->city;
                            $zip = (string)$item->itemconsigneeAddress1->zip;

                            // $location=$this->google_address($dropoffAddress);
//                            $location=$this->google_address($dropoffAddress,str_replace(" ","", $zip));


                            if(isset($shipment->consigneeAddress->addressLine1) && !empty($shipment->consigneeAddress->addressLine1)){
                                $xmlAddressLine = preg_replace('/[^A-Za-z0-9]/', ' ',$shipment->consigneeAddress->addressLine1);
                            }
                            if(isset($shipment->consigneeAddress->addressLine2) && !empty($shipment->consigneeAddress->addressLine2)){
                                $xmlAddressLine = preg_replace('/[^A-Za-z0-9]/', ' ',$shipment->consigneeAddress->addressLine2);
                            }
                            if(isset($shipment->consigneeAddress->addressLine3) && !empty($shipment->consigneeAddress->addressLine3)){
                                $xmlAddressLine = preg_replace('/[^A-Za-z0-9]/', ' ',$shipment->consigneeAddress->addressLine3);
                            }

                            $zipCode = preg_replace('/[^A-Za-z0-9]/', ' ',$shipment->consigneeAddress->zip);
                            $xmlCity = preg_replace('/[^A-Za-z0-9]/', '',$shipment->consigneeAddress->city);
                            $xmlState = preg_replace('/[^A-Za-z0-9]/', '',$shipment->consigneeAddress->stateChoice->stateProvince);
                            $xmlCountryCode = preg_replace('/[^A-Za-z0-9]/', '',$shipment->consigneeAddress->countryCode);

                            $location = $this->address_validation($xmlAddressLine,$zipCode,$xmlCity,$xmlState,$xmlCountryCode);

                            if($location == 0){
                                if($location == 0){
                                    BorderlessFailedOrders::create(['vendor_id' => $vendor_id_xml, 'customer_name' => $item->itemconsigneeAddress1->name, 'customer_number' => $item->itemconsigneeAddress1->phone,
                                        'tracking_num' => $trackingId, 'merchant_order_number' => $merchantOrderNum, 'customer_email' => $item->itemconsigneeAddress1->email,
                                        'address' => (string)$failed_address, 'address_line_2' => (string)$item->itemconsigneeAddress1->addressline2,
                                        'weight' =>  $item->itemshipmentPackageActualGrossWeight1->weightValue . ' ' . strtolower($item->itemshipmentPackageActualGrossWeight1->weightValue['unitOfMeasure']),
                                        'sprint_id' => $sprintId,
                                        'response' => 'Invalid dropoff address! Google unable to fetch location.']);
                                    MainfestFields::create($mainfestData);
                                }
                                else{
                                    BorderlessFailedOrders::where('tracking_num',$trackingId)
                                        ->update(['updated_at'=>date('Y-m-d H:i:s'), 'address' => (string)$failed_address]);
                                }
                                continue;
                            }

                            $zip = rtrim(chunk_split($zip, 3, ' '));
                            $location['postal_code'] = $zip;

                            if(empty($location)){
                                $is_failed = 1;
                                $response['failed_orders'][] = array(
                                    "tracking_id" => $trackingId,
                                    "merchant_order_num" => $merchantOrderNum,
                                    "reason" => 'Invalid dropoff address! Google unable to fetch location.'
                                );
                                $count_failed ++;
                                if($check_failed == 0){
                                    BorderlessFailedOrders::create(['vendor_id' => $vendor_id_xml, 'customer_name' => $item->itemconsigneeAddress1->name, 'customer_number' => $item->itemconsigneeAddress1->phone,
                                    'tracking_num' => $trackingId, 'merchant_order_number' => $merchantOrderNum, 'customer_email' => $item->itemconsigneeAddress1->email,
                                    'address' => (string)$failed_address, 'address_line_2' => (string)$item->itemconsigneeAddress1->addressline2,
                                    'weight' =>  $item->itemshipmentPackageActualGrossWeight1->weightValue . ' ' . strtolower($item->itemshipmentPackageActualGrossWeight1->weightValue['unitOfMeasure']),
                                    'sprint_id' => $sprintId,
                                    'response' => 'Invalid dropoff address! Google unable to fetch location.']);
                                    MainfestFields::create($mainfestData);
                                }
                                else{
                                    BorderlessFailedOrders::where('tracking_num',$trackingId)
                                    ->update(['updated_at'=>date('Y-m-d H:i:s'), 'address' => (string)$failed_address]);
                                }
                                continue;
                            }

                            if(!isset($location['postal_code'])){
                                $is_failed = 1;
                                $response['failed_orders'][] = array(
                                    "tracking_id" => $trackingId,
                                    "merchant_order_num" => $merchantOrderNum,
                                    "reason" => 'Invalid dropoff address! Postal code.'
                                );
                                $count_failed ++;
                                if($check_failed == 0){
                                    BorderlessFailedOrders::create(['vendor_id' => $vendor_id_xml, 'customer_name' => $item->itemconsigneeAddress1->name, 'customer_number' => $item->itemconsigneeAddress1->phone,
                                    'tracking_num' => $trackingId, 'merchant_order_number' => $merchantOrderNum, 'customer_email' => $item->itemconsigneeAddress1->email,
                                    'address' => (string)$failed_address, 'address_line_2' => (string)$item->itemconsigneeAddress1->addressline2,                                    'weight' =>  $item->itemshipmentPackageActualGrossWeight1->weightValue . ' ' . strtolower($item->itemshipmentPackageActualGrossWeight1->weightValue['unitOfMeasure']),
                                    'sprint_id' => $sprintId,
                                    'response' => 'Invalid dropoff address! Postal code.']);
                                    MainfestFields::create($mainfestData);

                                }
                                else{
                                    BorderlessFailedOrders::where('tracking_num',$trackingId)
                                    ->update(['updated_at'=>date('Y-m-d H:i:s'), 'address' => (string)$failed_address]);
                                }
                                continue;
                            }
                            if(isset($location['postal_code']) && $location['country']!="CA"){
                                $is_failed = 1;
                                $response['failed_orders'][] = array(
                                    "tracking_id" => $trackingId,
                                    "merchant_order_num" => $merchantOrderNum,
                                    "reason" => 'Invalid dropoff address! Country not Canada.'
                                );
                                $count_failed ++;
                                if($check_failed == 0){
                                    BorderlessFailedOrders::create(['vendor_id' => $vendor_id_xml, 'customer_name' => $item->itemconsigneeAddress1->name, 'customer_number' => $item->itemconsigneeAddress1->phone,
                                    'tracking_num' => $trackingId, 'merchant_order_number' => $merchantOrderNum, 'customer_email' => $item->itemconsigneeAddress1->email,
                                    'address' => (string)$failed_address, 'address_line_2' => (string)$item->itemconsigneeAddress1->addressline2,
                                    'weight' =>  $item->itemshipmentPackageActualGrossWeight1->weightValue . ' ' . strtolower($item->itemshipmentPackageActualGrossWeight1->weightValue['unitOfMeasure']),
                                    'sprint_id' => $sprintId,
                                    'response' => 'Invalid dropoff address! Country not canada.']);
                                    MainfestFields::create($mainfestData);
                                }
                                else{
                                    BorderlessFailedOrders::where('tracking_num',$trackingId)
                                    ->update(['updated_at'=>date('Y-m-d H:i:s'), 'address' => (string)$failed_address]);
                                }
                                continue;
                            }

                            $location['creator_id'] = $vendor_id_xml;

                            //$this->updateTime($sprintId,$dueTime);

                            //$checkout = $this->checkOut($sprintId);

                            $mainfestData['sprint_id'] = $sprintId;
                            $dist = $this->calculate_distance($pickupAddress,$location,"K");
                            //checking distance and calculation
                            if(!empty($dist)){
                                $d1=($dist)*0.001;
                                if($d1>130){
                                    $is_failed = 1;
                                    $response['failed_orders'][] = array(
                                        "tracking_id" => $trackingId,
                                        "merchant_order_num" => $merchantOrderNum,
                                        "reason" => 'Distance between pickup and drop address is greater than 100 km'
                                    );
                                    $count_failed ++;
                                    if($check_failed == 0){
                                        BorderlessFailedOrders::create(['vendor_id' => $vendor_id_xml, 'customer_name' => $item->itemconsigneeAddress1->name, 'customer_number' => $item->itemconsigneeAddress1->phone,
                                        'tracking_num' => $trackingId, 'merchant_order_number' => $merchantOrderNum, 'customer_email' => $item->itemconsigneeAddress1->email,
                                        'address' => (string)$failed_address, 'address_line_2' => (string) $item->itemconsigneeAddress1->addressLine2,
                                        'weight' =>  $item->itemshipmentPackageActualGrossWeight1->weightValue . ' ' . strtolower($item->itemshipmentPackageActualGrossWeight1->weightValue['unitOfMeasure']),
                                        'sprint_id' => $sprintId,
                                        'response' => 'Distance between pickup and drop address is greater than 100 km']);

                                        MainfestFields::create($mainfestData);
                                    }
                                    else{
                                        BorderlessFailedOrders::where('tracking_num',$trackingId)
                                        ->update(['updated_at'=>date('Y-m-d H:i:s'), 'address' => (string)$failed_address]);
                                    }
                                    //Sprint::where('id','=',$sprintId)->delete();

                                    MerchantsIds::where('tracking_id','=',$trackingId)->update(['tracking_id'=>""]);
                                }
                                else{
                                    $data=array(
                                        'sprint'=> $sprint,
                                        'contact' => $contact,
                                        'location' => $location,
                                        'is_failed' => $is_failed,
                                        'notification_method' => 'none'
                                    );
                                    $this->dropoffTask($data,$item->itemshipmentPackageActualGrossWeight1->weightValue,strtolower($item->itemshipmentPackageActualGrossWeight1->weightValue['unitOfMeasure']));
                                    //amazon dashbaord changes

                                    $task = SprintTasks::where('sprint_id','=',$sprintId)->where('ordinal','=',2)->first(['id','status_id','contact_id']);
                                    $store_name = Vendor::where('id','=',$vendor_id_xml)->first();
                                    $amazonorder['sprint_id'] = $sprintId;
                                    $amazonorder['task_id'] = $task->id;
                                    $amazonorder['task_status_id'] = $task->status_id;
                                    $amazonorder['creator_id'] = $vendor_id_xml;
                                    $amazonorder['customer_name'] = $name;
                                    $amazonorder['store_name'] = $store_name->name;
                                    $amazonorder['weight'] = $item->itemshipmentPackageActualGrossWeight1->weightValue . ' ' . strtolower($item->itemshipmentPackageActualGrossWeight1->weightValue['unitOfMeasure']);
                                    $amazonorder['tracking_id'] =  $trackingId;
                                    $amazonorder['address_line_1'] = (string)$item->itemconsigneeAddress1->addressLine1.', '.(string)$item->itemconsigneeAddress1->city.', '.(string)$item->itemconsigneeAddress1->zip.', ';
                                    $amazonorder['address_line_2'] = (string)$item->itemconsigneeAddress1->addressLine2;
                                    $amazonorder['address_line_3'] = (string)$item->itemconsigneeAddress1->addressLine3;

                                    // checking current time
                                    $current_time = date('Y-m-d H:i:s');
                                    if($current_time > date('Y-m-d').' '.'13:00:00'){
                                        $amazonorder['eta_time'] = strtotime(date('Y-m-d H:i:s', strtotime('21:00:00 +1 day')));
                                    }
                                    else{
                                        $amazonorder['eta_time'] = strtotime(date('Y-m-d').' '.'21:00:00');
                                    }

                                    BorderlessDashboard::create($amazonorder);
                                    $mainfestData['sprint_id']=$sprintId;

                                    MainfestFields::create($mainfestData);
                                    $count_successful++;
                                    // updating distance only when dropoff is created
                                    Sprint::where('id','=',$sprintId)->update(['distance'=>$dist]);

//                                    // send messages to customer for created order
//                                    $sprintForWM = Sprint::where('id', $sprintId)->first();
//                                    if(in_array($sprintForWM->creator_id,$walMartVendorIds)){
//                                        try{
//
//                                            $contact = SprintContact::where('id', $task->contact_id)->first();
//                                            $receiverNumber = $contact->phone;
//                                            $message = 'hello create order';
//
//                                            $account_sid = "ACb414b973404343e8895b05d5be3cc056";
//                                            $auth_token = "c135f0fc91ff9fdd0fcb805a6bdf3108";
//                                            $twilio_number = "+16479316176";
//
//                                            $client = new Client($account_sid, $auth_token);
//                                            $client->messages->create($receiverNumber, [
//                                                'from' => $twilio_number,
//                                                'body' => $message]);
//                                        }catch(\Exception $e){
//
//                                        }
//                                    }
                                }
                            }
                        }
                        else{
                            $response['duplicate_orders'][] = array(
                                "tracking_id" => $trackingId,
                                "merchant_order_num" => $merchantOrderNum,
                                "reason" => 'Order already created'
                            );
                            $count_duplicate++;
                        }
                    }
                    $total_count++;
                }

                $response ['count_total_orders'][] = "Total : ".sprintf("%02d", $count_successful+$count_failed+$count_duplicate);
                $response ['count_successful_orders'][] = "Total :  ".sprintf("%02d", $count_successful);
                $response ['count_failed_orders'][] = "Total : ".sprintf("%02d", $count_failed);
                $response ['count_duplicate_orders'][] = "Total : ".sprintf("%02d", $count_duplicate);
                $fileResp[] = $response;
                // Terminate loop after the first file has been processed from the list.
                ProcessedXmlFiles::where('file_name',$file)->update([ 'is_completed' => 1 ]);
                // break;
            }
            else{
                $fileResp[] = [ $file . " already processd!"];
                if(File::exists($file)){
                    // File::delete($file);
                    unlink($file);
                }
            }
        }
        if(empty($fileResp)){
            $arr_message = array('message' => 'Invalid file format');
            $fileResp = json_encode($arr_message);
            return response($fileResp, 400);
        }
        return response($fileResp, 201);
    }
    // function to validate address
    function addressParser($address){
        $pattern[] = "/^PH[0-9]+/";
        $pattern[] = "/^[0-9]+\s*-\s*[0-9]+/";
        $pattern[] = "/\sApt[0-9]+/";
        $pattern[] = "/^[0-9]+\s[0-9]+/";
        $pattern[] = "/(Apt|Apartment|Unit)\#\s*[0-9]+/";

        $result= preg_replace($pattern[0],'', $address);
        if($result!= $address){
            return $result;
        }

        $result= preg_replace($pattern[2],'', $address);
        if($result!= $address){
            return $result;
        }

        $result= preg_replace($pattern[4],'', $address);

        if($result!= $address){
            return $result;
        }

        if(preg_match($pattern[1],$address)){
            $result= preg_replace("/^[0-9]+\s*-\s*/",'', $address);
            return $result;
        }

        if(preg_match($pattern[3],$address)){
            $result= preg_replace("/^[0-9]+\s/",'', $address);
            return $result;
        }

        return $address;

    }

    private function calculate_distance($addressFrom, $addressTo,$unit){
        // $apiKey = config('app.google_api_key_prod');

        // Change address format
        // $formattedAddrFrom    = str_replace(' ', '+', $addressFrom);
        // $formattedAddrFrom = urlencode($addressFrom);
        // $formattedAddrTo     = str_replace(' ', '+', $addressTo);
        // $formattedAddrTo = urlencode($addressTo);
        // Geocoding API request with start address
        // $geocodeFrom = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?address='.$formattedAddrFrom.'&sensor=false&key='.$apiKey);
        // $outputFrom = json_decode($geocodeFrom);
        // if(!empty($outputFrom->error_message)){
        //     return $outputFrom->error_message;
        // }

        // // Geocoding API request with end address
        // $geocodeTo = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?address='.$formattedAddrTo.'&sensor=false&key='.$apiKey);
        // $outputTo = json_decode($geocodeTo);
        // if(!empty($outputTo->error_message)){
        //     return $outputTo->error_message;
        // }

        // Get latitude and longitude from the geodata
        // $latitudeFrom    = $outputFrom->results[0]->geometry->location->lat;
        // $longitudeFrom    = $outputFrom->results[0]->geometry->location->lng;
        // $latitudeTo        = $outputTo->results[0]->geometry->location->lat;
        // $longitudeTo    = $outputTo->results[0]->geometry->location->lng;

        // Get latitude and longitude from the geodata
        $latitudeFrom    = $addressFrom['lat'];
        $longitudeFrom    = $addressFrom['lng'];
        $latitudeTo        = $addressTo['latitude'];
        $longitudeTo    = $addressTo['longitude'];

        // Calculate distance between latitude and longitude
        $theta    = $longitudeFrom - $longitudeTo;
        $dist    = sin(deg2rad($latitudeFrom)) * sin(deg2rad($latitudeTo)) +  cos(deg2rad($latitudeFrom)) * cos(deg2rad($latitudeTo)) * cos(deg2rad($theta));
        $dist    = acos($dist);
        $dist    = rad2deg($dist);
        $miles    = $dist * 60 * 1.1515;

        // returning distance in meteres
        return round($miles * 1.609344 * 1000, 2);

    }

    public function address_validation($xmlAddressLine1, $postalCode, $cityCode, $stateCode, $countryCode){

        $url = "https://addressvalidation.googleapis.com/v1:validateAddress?key=AIzaSyDTK4viphUKcrJBSuoidDqRhVA4AWnHOo0";
        $data_array= ["address"=>[
            "revision"=> 0,
            "regionCode"=> $countryCode,
            "languageCode"=> "en",
            "postal_code"=> $postalCode,
            "administrativeArea"=> $stateCode,
            "locality"=> $cityCode,
            "addressLines"=> [
                $xmlAddressLine1
            ]]];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data_array));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type:application/json',
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        $resp = json_decode($response, true);

        $completeAddress = [];

//        try{
            if($resp['responseId']){
                if(isset($resp['result']['address']['postalAddress']['addressLines']))
                {
                    $completeAddress['route']=$resp['result']['address']['postalAddress']['addressLines'][0];
                }
                if(isset($resp['result']['address']['postalAddress']['locality']))
                {
                    $completeAddress['city']=$resp['result']['address']['postalAddress']['locality'];
                }
                if(isset($resp['result']['address']['postalAddress']['administrativeArea']))
                {
                    $completeAddress['division']=$resp['result']['address']['postalAddress']['administrativeArea'];
                }
                if(isset($resp['result']['address']['postalAddress']['regionCode']))
                {
                    $completeAddress['country']=$resp['result']['address']['postalAddress']['regionCode'];
                }
                if(isset($resp['result']['address']['postalAddress']['postalCode']))
                {
                    $completeAddress['postal_code']=$resp['result']['address']['postalAddress']['postalCode'];
                }

                if(isset($resp['result']['address']['addressComponents'][0]['componentName']['text']))
                {
                    $completeAddress['suite']=$resp['result']['address']['addressComponents'][0]['componentName']['text'];
                }
                if(isset($resp['result']['address']['formattedAddress'])) {
                    $completeAddress['address'] = $resp['result']['address']['formattedAddress'];
                }
                if(isset($resp['result']['geocode']['location']))
                {
                    $completeAddress['latitude']=$resp['result']['geocode']['location']['latitude'];
                    $completeAddress['longitude']=$resp['result']['geocode']['location']['longitude'];
                }
                return $completeAddress;
            }
            else{
                return 0;
            }
//        }catch (\Exception $e){
//            return 0;
//        }


    }

}

?>
