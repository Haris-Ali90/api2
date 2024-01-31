<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ClaimResource;
use App\Models\ClaimReason;
use Validator;
use Carbon\Carbon;
use App\Models\Joey;
use App\Models\User;
use App\Models\Claim;
use App\Classes\RestAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Api\ClaimResubmitRequest;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\SprintRepositoryInterface;



class ClaimController extends ApiBaseController
{

    private $userRepository;
    private $sprintRepository;
    // private $joeyDutyHistoryRepository;
    // private $joeyTransactionRepository;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(UserRepositoryInterface $userRepository,SprintRepositoryInterface $sprintRepository)
    {

        $this->userRepository = $userRepository;
        $this->sprintRepository = $sprintRepository;
        // $this->joeyDutyHistoryRepository=$joeyDutyHistoryRepositoryInterface;
        // $this->joeyTransactionRepository=$joeyTransactionsRepository;


    }

    public function resubmitClaim(ClaimResubmitRequest $request)
    {
        $post = $request->all();
        $response ='';
        $status=true;
        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);
            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            } else if (empty($joey['on_duty'])) {
                return RestAPI::response('Joey must be on duty to resubmit claim.', false);
            }
            //    print_r($post);die;
            $order_no=$post['tracking_id_merchant_order_no'];
//               $image_base64 =  base64_encode(file_get_contents($_FILES['file']['tmp_name']));
            //    print_r($image_base64);die;
                $url = '';
                if(isset($post['image'])){
                    $data = ['image' =>  $post['image']];//$base64Data];
                    $response =  $this->sendData('POST', '/',  $data );
                    if(!isset($response->url))
                    {
                        $message = $response->http->message;
                        $response = $message;
                        $status=false;
                    }
                    $url = $response->url;

                }
                Claim::where('tracking_id', $order_no)->update(['image' => $url, 'status' => 3,'reason_id'=>$post['reason_id']]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response([], $status, 'Claim Resubmitted Successfully');

    }
    public function sendData($method, $uri, $data=[] ) {
        $host ='assets.joeyco.com';

        $json_data = json_encode($data);
        $headers = [
            'Accept-Encoding: utf-8',
            'Accept: application/json; charset=UTF-8',
            'Content-Type: application/json; charset=UTF-8',
            // 'Accept-Language: ' . $locale->getLangCode(),
            'User-Agent: JoeyCo',
            'Host: ' . $host,
        ];

        if (!empty($json_data) ) {

            $headers[] = 'Content-Length: ' . strlen($json_data);

        }


        $url = 'https://' . $host . $uri;


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


        // dd([$this->originalResponse,$error,$this->response]);
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
                        'message' => json_last_error_msg(),
                    ],
                    'response' => new \stdClass()
                ];
            }
        }
        return $this->response;
    }

    public function claimsList(Request $request)
    {
        DB::beginTransaction();
        try {
            $joey = $this->userRepository->find(auth()->user()->id);
            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }

            $claims = Claim::with('reason','tasks.merchantIds')
                ->whereJoeyId($joey->id)
                ->where('deleted_at',NULL)
                ->where('status',$request->get('status'))
                ->orderBy('id','DESC')
                ->paginate($request->get('limit')??10);

            if (count($claims) > 0) {
                $response = ClaimResource::collection($claims);
                return RestAPI::setPagination($claims)->response($claims->items(), true, 'Claim list.');
            } else {
                return RestAPI::response('No record found', true);
            }

        }catch (\Exception $exception){
            DB::rollback();
            return RestAPI::response($exception->getMessage(), false, 'error_exception');
        }

    }

    public function claimReason()
    {
        DB::beginTransaction();
        try {
            $joey = $this->userRepository->find(auth()->user()->id);
            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }

            $claimReason = ClaimReason::where('slug','Re-Submitted')->where('deleted_at',NULL)->get();
            $response = [];
            foreach($claimReason as $reason){
                $response[] = [
                    'id' => $reason->id,
                    'title' => $reason->title,
                ];
            }
            if(count($claimReason) > 0){
                return RestAPI::response($response, true, 'Claim Reason Detail');
            }else{
                return RestAPI::response('No record found', true);
            }

        }catch(\Exception $e){
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
    }

    public function claimCounts()
    {
        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);
            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }

            $approvedCount = Claim::whereJoeyId($joey->id)->where('deleted_at',NULL)->where('status',1)->count();
            $notApprovedCount = Claim::whereJoeyId($joey->id)->where('deleted_at',NULL)->where('status',2)->count();
            $resubmitCount = Claim::whereJoeyId($joey->id)->where('deleted_at',NULL)->where('status',3)->count();

            $response['approved'] = $approvedCount;
            $response['not_approved'] = $notApprovedCount;
            $response['re_submit'] = $resubmitCount;
            if (!empty($response) ) {
                return RestAPI::response($response, true, 'Claim Counts');
            } else {
                return RestAPI::response('No record found', true);
            }

        }catch (\Exception $exception){
            DB::rollback();
            return RestAPI::response($exception->getMessage(), false, 'error_exception');
        }
    }

    /**
     * to get orders
     */

    // public function Orders(Request $request)
    // {
    //     $data = $request->all();

    //     DB::beginTransaction();
    //     try {

    //         $joey = $this->userRepository->find(auth()->user()->id);
    //         if (empty($joey)) {
    //             return RestAPI::response('Joey  record not found', false);
    //         } else if (empty($joey['on_duty'])) {
    //             return RestAPI::response('Joey must be on duty to view orders.', false);
    //         }

    //         $joeylistOrder= $this->sprintRepository->findWithtask($joey->id);

    //         $response['Orders'] =  JoeySprintResource::collection($joeylistOrder);
    //         $response['Status'] =  new RouteStatusListResource($request);
    //         $response['at_location_rdaius'] = '500';
    //         DB::commit();
    //     } catch (\Exception $e) {
    //         DB::rollback();
    //         return RestAPI::response($e->getMessage(), false, 'error_exception');
    //     }

    //     return RestAPI::response($response, true, 'joey order');
    // }
    /**
     * to get order details by id agianst sprint (new work)
     */
    // public function joeyOrderDetails(Request $request)
    // {
    //     $data = $request->all();
    //     DB::beginTransaction();
    //     try {
    //         $joey = $this->userRepository->find(auth()->user()->id);
    //         if (empty($joey)) {
    //             return RestAPI::response('Joey  record not found', false);
    //         } else if (empty($joey['on_duty'])) {
    //             return RestAPI::response('Joey must be on duty to view orders.', false);
    //         }
    //     $joeylistOrder= $this->sprintRepository->findWithtaskid($data['id']);
    //     // print_r( $joeylistOrder);die;
    //     if(empty($joeylistOrder)){return RestAPI::response(new \stdClass(), true, 'joey order details');}
    //         $response['OrderDetails'] =  new JoeySprintResource($joeylistOrder);
    //         $response['Status'] =  new RouteStatusListResource($request);
    //         $response['at_location_rdaius'] = '500';

    //         DB::commit();
    //     } catch (\Exception $e) {
    //         DB::rollback();
    //         return RestAPI::response($e->getMessage(), false, 'error_exception');
    //     }
    //     return RestAPI::response($response, true, 'joey order details');

    // }

}







