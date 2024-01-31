<?php

namespace App\Http\Controllers\Api;

use App\Classes\RestAPI;

use App\Models\Dispatch;

use App\Models\JoeysZoneSchedule;
use App\Models\Sprint;
use App\Models\SprintConfirmation;
use App\Models\SprintSprintHistory;
use App\Models\SprintTaskHistory;
use App\Models\SprintTasks;

use App\Models\VendorTransaction;
use App\Models\FinancialTransactions;
use App\Models\Joey;
use App\Models\User;
use App\Models\JoeyTransactions;
use App\Models\FlaggedOrder;
use App\Repositories\Interfaces\JoeyRouteRepositoryInterface;
use App\Repositories\Interfaces\SprintRepositoryInterface;

use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\StatusMap;
use App\Repositories\Interfaces\SprintTaskRepositoryInterface;


class ConfirmationController extends ApiBaseController
{

        private $userRepository;
         private $joeyrouteRepository;
         private $sprintTaskRepository;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(UserRepositoryInterface $userRepository,SprintRepositoryInterface $sprintRepository,JoeyRouteRepositoryInterface $joeyrouteRepository,
    SprintTaskRepositoryInterface  $sprintTaskRepository)
    {

        $this->userRepository = $userRepository;
        $this->sprintRepository = $sprintRepository;
        $this->joeyrouteRepository=$joeyrouteRepository;
        $this->sprintTaskRepository=$sprintTaskRepository;

    }

/*
 * task confirmation
 */
    public function task(Request $request)
    {

      //  $data = $request->all();
        $data = $request->validate([
            'confirmation_num' => 'required',
            'pin' => '',
            'image' => '']);

     //   DB::beginTransaction();


        try {
            $joey = $this->userRepository->find(auth()->user()->id);

            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }

            /*
             * getting the record from sprint confirmation with povided confirmation number
             */
            $confirmation= SprintConfirmation::where('id',$data['confirmation_num'])->first();


            /*
             *  getting the record against confirmation task id from sprint task
             */
            $task=SprintTasks::where('id',$confirmation->task_id)->first();

            $taskCount=0;

           /* checking against pin
           */
           if($confirmation->name=='pin'){
			    if($confirmation->confirmed == 1){
                    return RestAPI::response('Pin Already Confirmed', true);
                }
             if(!empty($data['pin'])){

                    if($task->pin==$data['pin']){
                        $confirmation->update(['confirmed' => 1]);
                        $responseValue='Pin Confirmed Successfully';
                    }
                    else{
                        return RestAPI::response('Pin Mis-Matched', false);
                    }
                     }
                    else{
                            return RestAPI::response('Pin parameter is required', false);
                    }
              }


           /*
          * checking against signature
          */
            elseif($confirmation->name=='signature'){
				if($confirmation->confirmed == 1){
                    return RestAPI::response('Signature Already Confirmed', true);
                }
                    if(!empty($data['image'])){


                   $path=  $this->upload($data['image']);

                        if(!isset($path)){
                            return RestAPI::response('File cannot be uploaded due to server error!', false);
                        }

                 SprintConfirmation::where('id','=',$data['confirmation_num'])->update(['confirmed'=>1,'attachment_path'=>$path]);
                        //upload image in assets folder in laravl 3
                        //attachment path save url in confirmation
                             $responseValue=' Signature Confirmed Successfully';
                    }else{
                    return RestAPI::response('Signature  is required', false);
               }
            }
                        /*
                     * checking against image
                     */
                elseif($confirmation->name=='image'){
					if($confirmation->confirmed == 1){
						return RestAPI::response('Image Already Confirmed', true);
					}

                    if(!empty($data['image'])){
                     $path=  $this->upload($data['image']);

                        if(!isset($path)){
                            return RestAPI::response('File cannot be uploaded due to server error!', false);
                        }

                     SprintConfirmation::where('id','=',$data['confirmation_num'])->update(['confirmed'=>1,'attachment_path'=>$path]);
                        $responseValue='Image Confirmed Successfully';
                    }else{
                    return RestAPI::response('Image is required', false);
                     }
                }
                     /*
                     * checking against seal
                     */
                elseif($confirmation->name=='seal'){
					if($confirmation->confirmed == 1){
						return RestAPI::response('Seal Already Confirmed', true);
					}
                    if(!empty($data['image'])){

                     $path=  $this->upload($data['image']);

                        if(!isset($path)){
                            return RestAPI::response('File cannot be uploaded due to server error!', false);
                        }

                     SprintConfirmation::where('id','=',$data['confirmation_num'])->update(['confirmed'=>1,'attachment_path'=>$path]);
                             $responseValue='Confirmed Successfully';
                         }else{
                            return RestAPI::response('Seal is required', false);
                      }
                }
                 /*
                     * if no value then by the ddfault will be present
                     */
                elseif($confirmation->name=='default'){
					if($confirmation->confirmed == 1){
						return RestAPI::response('Task Already Confirmed', true);
					}
                    SprintConfirmation::where('id','=',$data['confirmation_num'])->update(['confirmed'=>1]);
                    $responseValue='Confirmed Successfully';
                }


                $sprint_rec = Sprint::where('id',$task->sprint_id)->first();

            if(isset($task)) {
                $check = SprintConfirmation::where('task_id', $task->id);
                $confirmedCount = $check->where('confirmed', 0)->count();
            }


                if($task->type=='pickup'){
                    if($confirmedCount==0) {
                        $task->update(['active' => 1, 'status_id' => 15]);
                        Sprint::where('id', $task->sprint_id)->update(['status_id' => 15]);
                        $statusDescription = StatusMap::getDescription(15);

                        Dispatch::where('sprint_id', $task->sprint_id)->update(['status' => 15, 'status_copy' => $statusDescription]);


                        $taskHistoryRecord = [
                            'sprint__tasks_id' => $task->id,
                            'sprint_id' => $task->sprint_id,
                            'status_id' => 15,
                            'date' => date('Y-m-d H:i:s'),
                            'created_at' => date('Y-m-d H:i:s'),
                            'resolve_time' => date('Y-m-d H:i:s')
                        ];

                        $sprintHistoryRecord = [
                            'sprint__sprints_id' => $task->sprint_id,
                            'status_id' => 15,
                            'date' => date('Y-m-d H:i:s'),
                            'created_at' => date('Y-m-d H:i:s'),
                            'vehicle_id' => $sprint_rec->vehicle_id
                        ];

                        SprintTaskHistory::insert($taskHistoryRecord);
                        SprintSprintHistory::insert($sprintHistoryRecord);

                    }

                }

                elseif($task->type=='dropoff'){
                	$joey_pay=0;
                    $joey_tax_pay =0;
                    $joeyco_pay=$task->charge;
                    if($confirmedCount==0 && $this->isFlagged($task->sprint_id)==false){
						 	// new work

                    			// get task pay
	                            $getTaskPay=$this->getTaskPay($joey,$task);
	                            $joey_pay=$getTaskPay['joey_pay'];
	                            $joeyco_pay=$getTaskPay['joeyco_pay'];
	                            $task->update(['active'=>1,'joey_pay'=>$joey_pay,'joeyco_pay'=>$joeyco_pay]);
	                            // get task pay

	                            // get sprint pay
		                            $total_joey_pay=0;
		                            $total_joeyco_pay=0;
		                            $all_tasks=$sprint_rec->sprintTask;
		                            $getSprintPay=$this->getSprintPay($all_tasks, $task);
		                            $total_joey_pay=$getSprintPay['total_joey_pay'];
		                            $total_joeyco_pay=$getSprintPay['total_joeyco_pay'];
                                    $joey_tax_pay = $getSprintPay['joey_tax_pay'];
								// get sprint pay
	                            Sprint::where('id',$task->sprint_id)->update(['joey_pay'=>$total_joey_pay,'joeyco_pay'=>$total_joeyco_pay,'joey_tax_pay'=>$joey_tax_pay]);

                                // --------------------------------Summary Work-----------------------------
                                //multi drop task duration set 2022-06-02
									$multiDropTasksIds = SprintTasks::where('sprint_id', $task->sprint_id)->pluck('id');

									if(isset($task)) {
										$multiDropConfirmations = SprintConfirmation::whereIn('task_id', $multiDropTasksIds);
										$taskConfirmedCount = $multiDropConfirmations->where('confirmed', 0)->count();
									}


									if($taskConfirmedCount == 0){
										$this->recordJoeyPayment($task,$total_joey_pay,$joey_tax_pay);
										$totalVendorPay=$total_joey_pay+$total_joeyco_pay+$task->sprintsSprints->tax;
										$this->recordVendorPayment($task,$totalVendorPay);
                                        Sprint::where('id',$task->sprint_id)->update(['joey_pay'=>$total_joey_pay+$task->sprintsSprints->tip,'joeyco_pay'=>$total_joeyco_pay,'joey_tax_pay'=>$joey_tax_pay,'merchant_charge'=>$totalVendorPay+$task->sprintsSprints->tip]);
									}
								//multi drop task duration set 2022-06-02
                               //---------------------------------------------------------------------------

                  }
                }
                elseif($task->type=='return'){
                    if($confirmedCount==0 && $this->isFlagged($task->sprint_id)==false){
                        $joey_tax_pay=0;
                        $getDropPay = $this->getDropTaskPay($joey,$task);

                        $all_drop_tasks = $sprint_rec->sprintDropTask;

                        $getSprintdropPay=$this->getDropSprintPay($all_drop_tasks, $task);
                        $totaldrop_joey_pay=$getSprintdropPay['total_joey_pay'];
                        $totaldrop_joeyco_pay=$getSprintdropPay['total_joeyco_pay'];
                        $totaldrop_joey_tax_pay = $getSprintdropPay['joey_tax_pay'];

                        // --------------------------------------------------------------------------
                            $this->recordJoeyPayment($task,$totaldrop_joey_pay,$totaldrop_joey_tax_pay);
                            $totalVendorPay=$totaldrop_joey_pay+$totaldrop_joeyco_pay;
                            $this->recordVendorPayment($task,$totalVendorPay);
                        //-------------------------------------------------------------------------

                        // get task pay
                        $getTaskPay=$this->getTaskPay($joey,$task);

                        $joey_pay=$getTaskPay['joey_pay'];
                        $joeyco_pay=$getTaskPay['joeyco_pay'];
                        $task->update(['active'=>1,'status_id'=>145,'joey_pay'=>$joey_pay,'joeyco_pay'=>$joeyco_pay]);
                        // get task pay
                        // get sprint pay
                            $total_joey_pay=0;
                            $total_joeyco_pay=0;
                            $all_tasks=$sprint_rec->sprintTask;
                            $getSprintPay=$this->getSprintPay($all_tasks, $task);
                            $total_joey_pay=$getSprintPay['total_joey_pay'];
                            $total_joeyco_pay=$getSprintPay['total_joeyco_pay'];
                        // get sprint pay
                        Sprint::where('id',$task->sprint_id)->update(['status_id'=>145,'joey_pay'=>$total_joey_pay+$task->sprintsSprints->tip,'joeyco_pay'=>$total_joeyco_pay,'joey_tax_pay'=>$joey_tax_pay,'merchant_charge'=>$task->sprintsSprints->total]);

                        // --------------------------------------------------------------------------
                                    $this->recordJoeyPaymentReturn($task,$joey_pay);
                                    $totalVendorPay=$joey_pay+$joeyco_pay;
                                    $percentTotalVendorPay=($totalVendorPay*13)/100;
                                    $returnTotalVendorPay = $percentTotalVendorPay+$totalVendorPay;
                                    $this->recordVendorPayment($task,$returnTotalVendorPay);
                        //---------------------------------------------------------------------------
                        $statusDescription= StatusMap::getDescription(145);

                        Dispatch::where('sprint_id',$task->sprint_id)->update(['status'=>145,'status_copy'=>$statusDescription]);
                        $taskHistoryRecord = [
                            'sprint__tasks_id' => $task->id,
                            'sprint_id' =>$task->sprint_id,
                            'status_id' => 145,
                            'date' => date('Y-m-d H:i:s'),
                            'created_at' => date('Y-m-d H:i:s'),
                            'resolve_time' => date('Y-m-d H:i:s')
                        ];

                        $sprintHistoryRecord = [
                            'sprint__sprints_id' => $task->sprint_id,
                            'status_id' => 145,
                            'date' => date('Y-m-d H:i:s'),
							'created_at' => date('Y-m-d H:i:s'),
                            'vehicle_id' => $sprint_rec->vehicle_id
                        ];

                        SprintTaskHistory::insert($taskHistoryRecord);
                        SprintSprintHistory::insert($sprintHistoryRecord);
                    }
                }

        // DB::commit();
        } catch (\Exception $e) {
     //       DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response(new \stdClass(), true, $responseValue);


    }
  // end of funtion task



// BELOW fUNCTION FOR IMAGE UPLOAD IN ASSETS
    public function upload($base64Data) {

        //dd($base64Data);

     //   $request = new Image_JsonRequest();
       $data = ['image' => $base64Data];
        $response =  $this->sendData('POST', '/',  $data );
        if(!isset($response->url)) {
            return null;
        }
        return $response->url;

    }




    public function sendData($method, $uri, $data=[] ) {



        $host ='assets.joeyco.com';
     // $host ='localhost:8300';

       $json_data = json_encode($data);
        //dd( $data['image']->getClientOriginalName());
     //dd($json_data);
       // $this->reset();


        // if (json_last_error() != JSON_ERROR_NONE) {
        //     throw new \Exception('Bad Request', 400);
        // }

        // $locale = \JoeyCo\Locale::getInstance();

        $headers = [
            'Accept-Encoding: utf-8',
            'Accept: application/json; charset=UTF-8',
            'Content-Type: application/json; charset=UTF-8',
           // 'Accept-Language: ' . $locale->getLangCode(),
            'User-Agent: JoeyCo',
            'Host: ' . $host,
        ];


       // if (!empty($data) && $method !== 'GET') {

        if (!empty($json_data) ) {

            $headers[] = 'Content-Length: ' . strlen($json_data);
           // dd($headers);
        }

        // if (in_array($host, ['api.nightly.joeyco.com', 'api.staging.joeyco.com'])) {

        //     $headers[] = 'Authorization: Basic ' . base64_encode('api:api1243');
        // }

        // $this->signRequest($method, $uri, $headers);
         $url = 'https://' . $host . $uri;
     //   $url = $host . $uri;


        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if (strlen($json_data) > 2) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        }

        // $file=env('APP_ENV');
    //   dd(env('APP_ENV') === 'local');
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


        //dd([$this->originalResponse,$error]);
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




    /*
     * task confirmation offline
     */
    public function taskOffline(Request $request)
    {
        //$data = $request->all();
        $data = $request->validate([
            'confirmation_num' => 'required',
            'pin' => '',
            'image' => '',
            'created_at'=>'required'
        ]);
        $dateTime=$data['created_at'];

        DB::beginTransaction();


        try {
            $joey = $this->userRepository->find(auth()->user()->id);


            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }

            $confirmation= SprintConfirmation::where('id',$data['confirmation_num'])->first();
            $task=SprintTasks::where('id',$confirmation->task_id)->first();

            if($confirmation->name=='pin'){
				if($confirmation->confirmed == 1){
						return RestAPI::response('Pin Already Confirmed', true);
					}
                if(!empty($data['pin'])){

                    if($task->pin==$data['pin']){

                        $confirmation->update(['confirmed' => 1]);
                        $responseValue='Pin Confirmed Successfully';

                    }
                    else{
                        return RestAPI::response('Pin Mis-Matched', false);
                    }
                }
                else{
                    return RestAPI::response('Pin parameter is required', false);
                }
            }

            elseif($confirmation->name=='signature'){
					if($confirmation->confirmed == 1){
						return RestAPI::response('Signature Already Confirmed', true);
					}

                if(!empty($data['image'])){

                    $path=  $this->upload($data['image']);
                    if(!isset($path)){
                        return RestAPI::response('File cannot be uploaded due to server error!', false);
                    }
                    SprintConfirmation::where('id','=',$data['confirmation_num'])->update(['confirmed'=>1,'attachment_path'=>$path]);
                    //upload image in assets folder in laravl 3
                    //attachment path save url in confirmation
                    $responseValue=' Signature Confirmed Successfully';
                }else{
                    return RestAPI::response('Signature  is required', false);
                }
            }

            elseif($confirmation->name=='image'){
				if($confirmation->confirmed == 1){
						return RestAPI::response('Image Already Confirmed', true);
					}
                if(!empty($data['image'])){
                    $path=  $this->upload($data['image']);
                    if(!isset($path)){
                        return RestAPI::response('File cannot be uploaded due to server error!', false);
                    }
                    SprintConfirmation::where('id','=',$data['confirmation_num'])->update(['confirmed'=>1,'attachment_path'=>$path]);
                    $responseValue='Image Confirmed Successfully';
                }else{
                    return RestAPI::response('Image  is required', false);
                }
            }

            elseif($confirmation->name=='seal'){
				if($confirmation->confirmed == 1){
						return RestAPI::response('Seal Already Confirmed', true);
					}
                if(!empty($data['image'])){

                    $path=  $this->upload($data['image']);
                    if(!isset($path)){
                        return RestAPI::response('File cannot be uploaded due to server error!', false);
                    }

                    SprintConfirmation::where('id','=',$data['confirmation_num'])->update(['confirmed'=>1,'attachment_path'=>$path]);
                    $responseValue='Email send';
                }else{
                    return RestAPI::response('Seal Confirmed Successfully', false);
                }
            }

            else{
				if($confirmation->confirmed == 1){
						return RestAPI::response('Task Already Confirmed', true);
					}
                SprintConfirmation::where('id','=',$data['confirmation_num'])->update(['confirmed'=>1]);
                $responseValue='Confirmed Successfully';
            }



            $check=SprintConfirmation::where('task_id',$task->id);

            $totalCount=$check->count();
            $confirmedCount=$check->where('confirmed',0)->count();

            $sprint_rec = Sprint::where('id',$task->sprint_id)->first();

            if($task->type=='pickup'){
                    if($confirmedCount==0) {
                        $task->status_id=15;
                        $task->active=1;
                        $task->save();

                        $sprint_rec->status_id = 15;
                        $sprint_rec->active=1;
                        $sprint_rec->save();

                        $statusDescription = StatusMap::getDescription(15);

                        $disptachId=Dispatch::where('sprint_id', '=', $task->sprint_id)->first();

                        Dispatch::where('id','=',$disptachId->id)->update(['status'=>15,'status_copy'=>$statusDescription]);


                        $taskHistoryRecord = [
                            'sprint__tasks_id' => $task->id,
                            'sprint_id' => $task->sprint_id,
                            'status_id' => 15,
                            'date' => $dateTime,
                            'created_at' => $dateTime,
                            'resolve_time' => $dateTime
                        ];

                        $sprintHistoryRecord = [
                            'sprint__sprints_id' => $task->sprint_id,
                            'status_id' => 15,
                            'date' => $dateTime,
                            'created_at' => $dateTime,
                            'vehicle_id' => $sprint_rec->vehicle_id
                        ];
                        SprintTaskHistory::insert($taskHistoryRecord);
                        SprintSprintHistory::insert($sprintHistoryRecord);
                    }
            }
            elseif($task->type=='dropoff'){
            	    $joey_pay=0;
                    $joey_tax_pay=0;
                    $joeyco_pay=$task->charge;
                    if($confirmedCount==0 && $this->isFlagged($task->sprint_id)==false) {
                    	// new work
                            // echo $task->sprint_id;die;
                            // $sprint_sprint=Sprint::where('id',$task->sprint_id)->first();

                            $getTaskPay=$this->getTaskPay($joey,$task);
                            $joey_pay=$getTaskPay['joey_pay'];
                            $joeyco_pay=$getTaskPay['joeyco_pay'];

                            $task->joey_pay=$joey_pay;
                            $task->joeyco_pay=$joeyco_pay;
//                            $task->status_id=17;
                            $task->active=1;
                            $task->save();



                            $total_joey_pay=0;
                            $total_joeyco_pay=0;
                            $all_tasks=$sprint_rec->sprintTask;
                            $getSprintPay=$this->getSprintPay($all_tasks,$task);
                            $total_joey_pay=$getSprintPay['total_joey_pay'];
                            $total_joeyco_pay=$getSprintPay['total_joeyco_pay'];
                            $joey_tax_pay = $getSprintPay['joey_tax_pay'];

                            $sprint_rec->joey_pay=$total_joey_pay;
                            $sprint_rec->joey_tax_pay=$joey_tax_pay;
                            $sprint_rec->joeyco_pay=$total_joeyco_pay;
//                            $sprint_rec->status_id = 17;


                            // --------------------------------Summary Work-----------------------------
                            $multiDropTasksIds = SprintTasks::where('sprint_id', $task->sprint_id)->pluck('id');

								if(isset($task)) {
									$multiDropConfirmations = SprintConfirmation::whereIn('task_id', $multiDropTasksIds);
									$taskConfirmedCount = $multiDropConfirmations->where('confirmed', 0)->count();
								}


								if($taskConfirmedCount == 0){
									$sprint_rec->joey_pay=$total_joey_pay+$task->sprintsSprints->tip;
									$this->recordJoeyPayment($task,$total_joey_pay,$joey_tax_pay);
									$totalVendorPay=$total_joey_pay+$total_joeyco_pay+$task->sprintsSprints->tax;
									$this->recordVendorPayment($task,$totalVendorPay);
								}
                                $sprint_rec->merchant_charge = $totalVendorPay+$task->sprintsSprints->tip;
								$sprint_rec->save();
                            //---------------------------------------------------------------------------

                    }
            }

            elseif($task->type=='return'){
                if($confirmedCount==0 && $this->isFlagged($task->sprint_id)==false){
                    $joey_tax_pay=0;
                    $getDropPay = $this->getDropTaskPay($joey,$task);

                    $all_drop_tasks = $sprint_rec->sprintDropTask;

                    $getSprintdropPay=$this->getDropSprintPay($all_drop_tasks, $task);
                    $totaldrop_joey_pay=$getSprintdropPay['total_joey_pay'];
                    $totaldrop_joeyco_pay=$getSprintdropPay['total_joeyco_pay'];
                    $totaldrop_joey_tax_pay = $getSprintdropPay['joey_tax_pay'];

                    // --------------------------------------------------------------------------
                        $this->recordJoeyPayment($task,$totaldrop_joey_pay,$totaldrop_joey_tax_pay);
                        $totalVendorPay=$totaldrop_joey_pay+$totaldrop_joeyco_pay;
                        $this->recordVendorPayment($task,$totalVendorPay);
                    //-------------------------------------------------------------------------

                    // get task pay
                    $getTaskPay=$this->getTaskPay($joey,$task);
                    $joey_pay=$getTaskPay['joey_pay'];
                    $joeyco_pay=$getTaskPay['joeyco_pay'];
                    $task->update(['active'=>1,'status_id'=>145,'joey_pay'=>$joey_pay,'joeyco_pay'=>$joeyco_pay]);
                    // get task pay
                    // get sprint pay
                        $total_joey_pay=0;
                        $total_joeyco_pay=0;
                        $all_tasks=$sprint_rec->sprintTask;
                        $getSprintPay=$this->getSprintPay($all_tasks,$task);
                        $total_joey_pay=$getSprintPay['total_joey_pay'];
                        $total_joeyco_pay=$getSprintPay['total_joeyco_pay'];
                    // get sprint pay
                    Sprint::where('id',$task->sprint_id)->update(['status_id'=>145,'joey_pay'=>$total_joey_pay+$task->sprintsSprints->tip,'joeyco_pay'=>$total_joeyco_pay,'joey_tax_pay'=>$joey_tax_pay,'merchant_charge'=>$task->sprintsSprints->total]);

                    // --------------------------------------------------------------------------
                                $this->recordJoeyPaymentReturn($task,$joey_pay);
                                $totalVendorPay=$joey_pay+$joeyco_pay;
                                $percentTotalVendorPay=($totalVendorPay*13)/100;
                                $returnTotalVendorPay = $percentTotalVendorPay+$totalVendorPay;
                                $this->recordVendorPayment($task,$returnTotalVendorPay);
                    //---------------------------------------------------------------------------
                    $statusDescription= StatusMap::getDescription(145);

                    Dispatch::where('sprint_id',$task->sprint_id)->update(['status'=>145,'status_copy'=>$statusDescription]);
                    $taskHistoryRecord = [
                        'sprint__tasks_id' => $task->id,
                        'sprint_id' =>$task->sprint_id,
                        'status_id' => 145,
                        'date' => $dateTime,
                        'created_at' => $dateTime,
                        'resolve_time' => $dateTime
                    ];

                    $sprintHistoryRecord = [
                        'sprint__sprints_id' => $task->sprint_id,
                        'status_id' => 145,
                        'date' => $dateTime,
                        'created_at' => $dateTime,
                        'vehicle_id' => $sprint_rec->vehicle_id
                    ];

                    SprintTaskHistory::insert($taskHistoryRecord);
                    SprintSprintHistory::insert($sprintHistoryRecord);
                }
            }

             DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response(new \stdClass(), true, $responseValue);


    }

	public function getTaskPay($joey=[],$task=[])
    {
            $joey_pay=0;
            $joeyco_pay=$task->charge;
            $joeyZone_shift_check=[];
            $from = date('Y-m-d').' 00:00:00';
            $to = date('Y-m-d').' 23:59:59';

            $joeyZone_shift_check=JoeysZoneSchedule::where('start_time','<=',$to)
                                            ->whereNull('end_time')
                                            ->whereBetween('start_time', [$from, $to])
                                            ->where('joey_id',$joey->id)->first();

            if($joeyZone_shift_check!=null){ //on shift

                if(($joeyZone_shift_check->ZoneSchedule)!=null){
                    if ($joeyZone_shift_check->ZoneSchedule->commission!=null) {
                            // $joey_pay=number_format((float)($joeyZone_shift_check->ZoneSchedule->commission/100), 2, '.', '')*$sprint_rec->subtotal;
                            // $joeyco_pay=number_format((float)(($sprint_rec->subtotal)-$joey_pay),1, '.', '');
                            $joey_pay=number_format((float)($joeyZone_shift_check->ZoneSchedule->commission/100), 2, '.', '')*$task->charge;
                            $joeyco_pay=number_format((float)(($task->charge)-$joey_pay),1, '.', '');

                    }else{
                        if($joey->getPlan!=null) {
                            if($joey->getPlan->scheduled_commission!=null){
                                $joey_pay=number_format((float)($joey->getPlan->scheduled_commission/100), 2, '.', '')*$task->charge;
                                $joeyco_pay=number_format((float)(($task->charge)-$joey_pay),1, '.', '');

                            }
                        }
                    }
                }
            }
            else{ //off shift
                if($joey->getPlan!=null) {
                    if($joey->getPlan->unscheduled_commission!=null){
                        $joey_pay=number_format((float)($joey->getPlan->unscheduled_commission/100), 2, '.', '')*$task->charge;
                        $joeyco_pay=number_format((float)(($task->charge)-$joey_pay),1, '.', '');

                    }
                }
            }
            $return['joey_pay']= $joey_pay;
            $return['joeyco_pay']=$joeyco_pay;

            return $return;
    }

    public function getDropTaskPay($joey=[],$task=[])
    {
            $joey_pay=0;
            $joeyco_pay=$task->charge;
            $totalJoeyPay=0;
            $totalJoeycoPay=0;
            $joeyZone_shift_check=[];
            $from = date('Y-m-d').' 00:00:00';
            $to = date('Y-m-d').' 23:59:59';

            $joeyZone_shift_check=JoeysZoneSchedule::where('start_time','<=',$to)
            ->whereNull('end_time')
            ->whereBetween('start_time', [$from, $to])
            ->where('joey_id',$joey->id)->first();

            $sprint_tasks=SprintTasks::where('sprint_id',$task->sprint_id)->whereIn('type',['pickup','dropoff'])->orderBy('ordinal','ASC')->get();

            foreach($sprint_tasks as $droptask){

                if($joeyZone_shift_check!=null){ //on shift

                    if(($joeyZone_shift_check->ZoneSchedule)!=null){
                        if ($joeyZone_shift_check->ZoneSchedule->commission!=null) {
                                $joey_pay=number_format((float)($joeyZone_shift_check->ZoneSchedule->commission/100), 2, '.', '')*$droptask->charge;
                                $joeyco_pay=number_format((float)(($droptask->charge)-$joey_pay),1, '.', '');
                        }else{
                            if($joey->getPlan!=null) {
                                if($joey->getPlan->scheduled_commission!=null){
                                    $joey_pay=number_format((float)($joey->getPlan->scheduled_commission/100), 2, '.', '')*$droptask->charge;
                                    $joeyco_pay=number_format((float)(($droptask->charge)-$joey_pay),1, '.', '');
                                }
                            }
                        }
                    }
                }
                else{ //off shift
                    if($joey->getPlan!=null) {
                        if($joey->getPlan->unscheduled_commission!=null){
                            $joey_pay=number_format((float)($joey->getPlan->unscheduled_commission/100), 2, '.', '')*$droptask->charge;
                            $joeyco_pay=number_format((float)(($droptask->charge)-$joey_pay),1, '.', '');
                        }
                    }
                }

                if($joey_pay > 0){
                    $droptask->joey_pay = $joey_pay;
                    $droptask->joeyco_pay = $joeyco_pay;
                    $droptask->merchant_charge = $droptask->charge;
                    $droptask->save();
                    $totalJoeyPay += $joey_pay;
                    $totalJoeycoPay += $joeyco_pay;
                }
            }


            $return['joey_pay']= $totalJoeyPay;
            $return['joeyco_pay']=$totalJoeycoPay;

            return $return;
    }

//    public function getSprintPay($all_tasks=[], $currentTask)
//    {
//        $total_joey_pay=0;
//        $total_joeyco_pay=0;
//        $joey_tax_pay=0;
//        if(count($all_tasks)>0){
//
//            $lastTask = $all_tasks->toArray();
//            $lastDropOff = end($lastTask);
//
//            if($currentTask->ordinal == $lastDropOff['ordinal']){
//
//	            $joey_pay=0;
//	            $joeyco_pay=0;
//	            $joeyZone_shift_check=[];
//                $from = date('Y-m-d').' 00:00:00';
//	            $to = date('Y-m-d').' 23:59:59';
//
//                $joey = User::find(auth()->user()->id);
//
//                $sprint = Sprint::find($lastDropOff['sprint_id']);
//
//                $totalCharge = $sprint->task_total+$sprint->distance_charge;
//
//                $taxcharges = 0;
//                $joey_tax_pay=0;
//                if(!empty($joey->hst_number) || $joey->hst_number != NUll || $joey->hst_number != ''){
//                    $taxcharges = $sprint->tax;
//                }
//
//                $joeyZone_shift_check=JoeysZoneSchedule::where('start_time','<=',$to)
//                ->whereNull('end_time')
//                ->whereBetween('start_time', [$from, $to])
//                ->where('joey_id',$joey->id)->first();
//
//                if($joeyZone_shift_check!=null){ //on shift
//                    if(($joeyZone_shift_check->ZoneSchedule)!=null){
//                        if ($joeyZone_shift_check->ZoneSchedule->commission!=null) {
//                            $joey_pay=number_format((float)($joeyZone_shift_check->ZoneSchedule->commission/100), 2, '.', '')*$totalCharge;
//
//                            if($taxcharges > 0){
//                                $joey_tax_pay=number_format((float)($joeyZone_shift_check->ZoneSchedule->commission/100), 2, '.', '')*$taxcharges;
//                            }
//
//                            $joeyco_pay=number_format((float)(($totalCharge)-$joey_pay),1, '.', '');
//
//                        }else{
//                            if($joey->getPlan!=null) {
//                                if($joey->getPlan->scheduled_commission!=null){
//                                    $joey_pay=number_format((float)($joey->getPlan->scheduled_commission/100), 2, '.', '')*$totalCharge;
//
//                                    if($taxcharges > 0){
//                                        $joey_tax_pay=number_format((float)($joey->getPlan->scheduled_commission/100), 2, '.', '')*$taxcharges;
//                                    }
//
//                                    $joeyco_pay=number_format((float)(($totalCharge)-$joey_pay),1, '.', '');
//
//                                }
//                            }
//                        }
//                    }
//                }
//                else{ //off shift
//                    if($joey->getPlan!=null) {
//                        if($joey->getPlan->unscheduled_commission!=null){
//                            $joey_pay=number_format((float)($joey->getPlan->unscheduled_commission/100), 2, '.', '')*$totalCharge;
//
//                            if($taxcharges > 0){
//                                $joey_tax_pay=number_format((float)($joey->getPlan->unscheduled_commission/100), 2, '.', '')*$taxcharges;
//                            }
//
//                            $joeyco_pay=number_format((float)(($totalCharge)-$joey_pay),1, '.', '');
//
//                        }
//                    }
//                }
//                $return['total_joey_pay']= $joey_pay;
//                $return['joey_tax_pay']=$joey_tax_pay;
//                $return['total_joeyco_pay']=$joeyco_pay;
//                return $return;
//            }
//
//            foreach ($all_tasks as $singleTask) {
//            $total_joey_pay+=$singleTask->joey_pay;
//            $total_joeyco_pay+=$singleTask->joeyco_pay;
//            }
//        }
//            $return['total_joey_pay']= $total_joey_pay;
//            $return['joey_tax_pay']=$joey_tax_pay;
//            $return['total_joeyco_pay']=$total_joeyco_pay;
//            return $return;
//    }

    public function getDropSprintPay($all_tasks=[], $currentTask)
    {
        $total_joey_pay=0;
        $total_joeyco_pay=0;
        $joey_tax_pay=0;
        if(count($all_tasks)>0){

            $lastTask = $all_tasks->toArray();
            $lastDropOff = end($lastTask);

	            $joey_pay=0;
	            $joeyco_pay=0;
	            $joeyZone_shift_check=[];
                $from = date('Y-m-d').' 00:00:00';
	            $to = date('Y-m-d').' 23:59:59';

                $joey = User::find(auth()->user()->id);

                $sprint = Sprint::find($lastDropOff['sprint_id']);

                $totalCharge = $sprint->task_total+$sprint->distance_charge;

                $taxcharges = 0;
                $joey_tax_pay=0;
                if(!empty($joey->hst_number) || $joey->hst_number != NUll || $joey->hst_number != ''){
                    $taxcharges = $sprint->tax;
                }

                $joeyZone_shift_check=JoeysZoneSchedule::where('start_time','<=',$to)
                ->whereNull('end_time')
                ->whereBetween('start_time', [$from, $to])
                ->where('joey_id',$joey->id)->first();

                if($joeyZone_shift_check!=null){ //on shift
                    if(($joeyZone_shift_check->ZoneSchedule)!=null){
                        if ($joeyZone_shift_check->ZoneSchedule->commission!=null) {
                            $joey_pay=number_format((float)($joeyZone_shift_check->ZoneSchedule->commission/100), 2, '.', '')*$totalCharge;

                            if($taxcharges > 0){
                                $joey_tax_pay=number_format((float)($joeyZone_shift_check->ZoneSchedule->commission/100), 2, '.', '')*$taxcharges;
                            }

                            $joeyco_pay=number_format((float)(($totalCharge)-$joey_pay),1, '.', '');

                        }else{
                            if($joey->getPlan!=null) {
                                if($joey->getPlan->scheduled_commission!=null){
                                    $joey_pay=number_format((float)($joey->getPlan->scheduled_commission/100), 2, '.', '')*$totalCharge;

                                    if($taxcharges > 0){
                                        $joey_tax_pay=number_format((float)($joey->getPlan->scheduled_commission/100), 2, '.', '')*$taxcharges;
                                    }

                                    $joeyco_pay=number_format((float)(($totalCharge)-$joey_pay),1, '.', '');

                                }
                            }
                        }
                    }
                }
                else{ //off shift
                    if($joey->getPlan!=null) {
                        if($joey->getPlan->unscheduled_commission!=null){
                            $joey_pay=number_format((float)($joey->getPlan->unscheduled_commission/100), 2, '.', '')*$totalCharge;

                            if($taxcharges > 0){
                                $joey_tax_pay=number_format((float)($joey->getPlan->unscheduled_commission/100), 2, '.', '')*$taxcharges;
                            }

                            $joeyco_pay=number_format((float)(($totalCharge)-$joey_pay),1, '.', '');

                        }
                    }
                }
                $return['total_joey_pay']= $joey_pay;
                $return['joey_tax_pay']=$joey_tax_pay;
                $return['total_joeyco_pay']=$joeyco_pay;
                return $return;


            foreach ($all_tasks as $singleTask) {
            $total_joey_pay+=$singleTask->joey_pay;
            $total_joeyco_pay+=$singleTask->joeyco_pay;
            }
        }
            $return['total_joey_pay']= $total_joey_pay;
            $return['joey_tax_pay']=$joey_tax_pay;
            $return['total_joeyco_pay']=$total_joeyco_pay;
            return $return;
    }

   function recordJoeyPayment($task=[],$total_joeyco_pay,$joey_tax_pay){
          $tip=0;
          $balance=0;
          $transaction=FinancialTransactions::create([
                                          'reference'=>'CR-'.$task->sprint_id,
                                          'description'=>'CR-'.$task->sprint_id.' Confirmed',
                                          'amount'=>$total_joeyco_pay,
                                          'merchant_order_num'=>($task->merchantIds!=null)?$task->merchantIds->merchant_order_num:null
                                      ]);

          $joey_id=$task->sprintsSprints->joey_id;
          $lastJoeyTransaction=JoeyTransactions::where('joey_id',$joey_id)->orderBy('transaction_id','desc')->first();


        $taskAcceptedJoey=SprintTaskHistory::where('status_id',32)->where('sprint__tasks_id',$task->id)->where('sprint_id',$task->sprint_id)->first();

        $secsDiff =0;
        $joeyzone=[];
        if($taskAcceptedJoey!=null){

            $secsDiff = time() - strtotime($taskAcceptedJoey->date);

            $joeyzone=JoeysZoneSchedule::where('joey_id',$joey_id)->where('start_time', '<=',$taskAcceptedJoey->date)->whereNull('end_time')->orderBy('id','DESC')->first();

        }
              $joeyTransactionsdata=[
                  'transaction_id'=>$transaction->id,
                  'joey_id'=>$joey_id,
                  'type'=>'sprint',
                  'payment_method'=>null,
                  'distance'=>($task->sprintsSprints!=null)?$task->sprintsSprints->distance:null,
                  'duration'=>($secsDiff)?$secsDiff:0,
                  'date_identifier'=>null,
                  'shift_id'=>($joeyzone!=null)?$joeyzone->zone_schedule_id:null,
                  'balance'=>((isset($lastJoeyTransaction->balance))?$lastJoeyTransaction->balance:0)+$total_joeyco_pay
              ];
              JoeyTransactions::insert($joeyTransactionsdata);
              $balance=$joeyTransactionsdata['balance'];

                    // Tax Transaction //

                    if($joey_tax_pay > 0){

                        $transactionTax=FinancialTransactions::create([
                            'reference'=>'CR-'.$task->sprint_id.'-Tax',
                            'description'=>'Tax for Order: CR-'.$task->sprint_id,
                            'amount'=>($joey_tax_pay)?$joey_tax_pay:null,
                            'merchant_order_num'=>($task->merchantIds!=null)?$task->merchantIds->merchant_order_num:null
                        ]);

                        $joeyTaxTransactionsdata=[
                            'transaction_id'=>$transactionTax->id,
                            'joey_id'=>$joey_id,
                            'type'=>'tax',
                            'payment_method'=>null,
                            'distance' => null,
							'duration'=> null,
                            'date_identifier'=>null,
                            'shift_id'=>($joeyzone!=null)?$joeyzone->zone_schedule_id:null,
                            'balance'=>$balance+$joey_tax_pay
                        ];
                        JoeyTransactions::insert($joeyTaxTransactionsdata);

                        $balance=$joeyTaxTransactionsdata['balance'];

                    }

                    // Tax Transaction End


            //Tip------------------------------------------------------------------------------------------------------------------

             $allTasks=$task->sprintsSprints->sprintTask;
             $lastTask=$allTasks[count($allTasks)-1];


             if($lastTask->id==$task->id){

                 $tip=($task->sprintsSprints->tip==null)?0:$task->sprintsSprints->tip;

                 if($tip > 0){
                    $transactionTip=FinancialTransactions::create([
                        'reference'=>'CR-'.$task->sprint_id.'-tip',
                        'description'=>'Tip for Order: CR-'.$task->sprint_id,
                        'amount'=>($tip)?$tip:0,
                        'merchant_order_num'=>($task->merchantIds!=null)?$task->merchantIds->merchant_order_num:null
                    ]);

                    $joeyTipTransactionsdata=[
                        'transaction_id'=>$transactionTip->id,
                        'joey_id'=>$joey_id,
                        'type'=>'tip',
                        'payment_method'=>null,
                        'distance' => null,
						'duration'=> null,
                        'date_identifier'=>null,
                        'shift_id'=>($joeyzone!=null)?$joeyzone->zone_schedule_id:null,
                        'balance'=>$balance+$tip
                    ];
                    JoeyTransactions::insert($joeyTipTransactionsdata);

                    $balance=$joeyTipTransactionsdata['balance'];
                 }


             }

            //Tip--------------------------------------------------------------------------------------------------------------------------

              Joey::where('id',$joey_id)->update(['balance'=> $balance]);




    }

    function recordJoeyPaymentReturn($task=[],$total_joeyco_pay){

        $transaction=FinancialTransactions::create([
                        'reference'=>'CR-'.$task->sprint_id.'-RE',
                        'description'=>'CR-'.$task->sprint_id.' Returned',
                        'amount'=>$total_joeyco_pay,
                        'merchant_order_num'=>($task->merchantIds!=null)?$task->merchantIds->merchant_order_num:null
                    ]);

        $joey_id=$task->sprintsSprints->joey_id;
        $lastJoeyTransaction=JoeyTransactions::where('joey_id',$joey_id)->orderBy('transaction_id','desc')->first();


        $taskAcceptedJoey=SprintTaskHistory::where('status_id',32)->where('sprint__tasks_id',$task->id)->where('sprint_id',$task->sprint_id)->first();

        $secsDiff =0;
        $joeyzone=[];
        if($taskAcceptedJoey!=null){
            $joeyzone=JoeysZoneSchedule::where('joey_id',$joey_id)->where('start_time', '<=',$taskAcceptedJoey->date)->whereNull('end_time')->orderBy('id','DESC')->first();
        }

        $joeyTransactionsdata=[
            'transaction_id'=>$transaction->id,
            'joey_id'=>$joey_id,
            'type'=>'sprint',
            'payment_method'=>null,
            'distance'=>0,
            'duration'=>0,
            'date_identifier'=>null,
            'shift_id'=>($joeyzone!=null)?$joeyzone->zone_schedule_id:null,
            'balance'=> $lastJoeyTransaction->balance+$total_joeyco_pay
        ];

        JoeyTransactions::insert($joeyTransactionsdata);
        $balance=$joeyTransactionsdata['balance'];

        Joey::where('id',$joey_id)->update(['balance'=> $balance]);
    }

    function recordVendorPayment($task=[],$total_vendor_pay){
        $transaction=FinancialTransactions::create([
                                        'reference'=>'CR-'.$task->sprint_id,
                                        'description'=>'CR-'.$task->sprint_id.' Confirmed',
                                        'amount'=>$total_vendor_pay,
                                        'merchant_order_num'=>($task->merchantIds!=null)?$task->merchantIds->merchant_order_num:null
                                    ]);

        $vendor_id=$task->sprintsSprints->creator_id;
        $lastvendorTransaction=VendorTransaction::where('vendor_id',$vendor_id)->orderBy('transaction_id','desc')->first();


		$scheduleTime=SprintTaskHistory::where('status_id',24)->where('sprint__tasks_id',$task->id)->where('sprint_id',$task->sprint_id)->orderBy('id','DESC')->first();
        $pickUpTime=SprintTaskHistory::whereIn('status_id',[28,15])->where('sprint_id',$task->sprint_id)->orderBy('id','DESC')->first();


		$secsDiff=0;
        if(isset($pickUpTime) && isset($scheduleTime)){
            if($scheduleTime!=null && $pickUpTime != null){
                $secsDiff = strtotime($pickUpTime->date) - strtotime($scheduleTime->date);
            }
        }


            $vendorTransactionsdata=[
                'transaction_id'=>$transaction->id,
                'vendor_id'=>$vendor_id,
                'type'=>'sprint',
                'payment_method'=>null,
                'distance'=>($task->sprintsSprints!=null)?$task->sprintsSprints->distance:null,
                'duration'=>$secsDiff,
                'date_identifier'=>null,
                'balance'=>((isset($lastvendorTransaction->balance))?$lastvendorTransaction->balance:0)+$total_vendor_pay
            ];
            VendorTransaction::insert($vendorTransactionsdata);

        // tip transaction of vendor

        $allTasks=$task->sprintsSprints->sprintTask;
        $lastTask=$allTasks[count($allTasks)-1];

		$lastvendorTransactionData=VendorTransaction::where('vendor_id',$vendor_id)->orderBy('transaction_id','desc')->first();

        if($lastTask->id==$task->id){
            $tip=($task->sprintsSprints->tip==null)?0:$task->sprintsSprints->tip;

            if($tip > 0){
                $transactionTip=FinancialTransactions::create([
                    'reference'=>'CR-'.$task->sprint_id.'-tip',
                    'description'=>'Tip for Order: CR-'.$task->sprint_id,
                    'amount'=>$tip,
                    'merchant_order_num'=>($task->merchantIds!=null)?$task->merchantIds->merchant_order_num:null
                ]);

                $vendorTransactionsdata=[
                    'transaction_id'=>$transactionTip->id,
                    'vendor_id'=>$vendor_id,
                    'type'=>'tip',
                    'payment_method'=>null,
                    'distance'=>null,
                    'duration'=>null,
                    'date_identifier'=>null,
                    'balance'=>((isset($lastvendorTransactionData->balance))?$lastvendorTransactionData->balance:0)+$task->sprintsSprints->tip
                ];
                VendorTransaction::insert($vendorTransactionsdata);

            }
        }

    }

    private function isFlagged($sprint_id){
        $row = FlaggedOrder::where('order_type','sprint')
                ->where('order_id',$sprint_id)
                ->whereNull('resolved_at')
                ->whereNull('deleted_at')
                ->first();

        if (!empty($row)) {
            return $row;
        }

        return false;
    }


}
