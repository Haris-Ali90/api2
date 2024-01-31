<?php

namespace App\Http\Controllers\Api;

use App\Classes\RestAPI;

use App\Http\Resources\MessagesResource;
use App\Http\Resources\VehicleResource;
use App\Models\CustomerSendMessages;
use App\Models\Vehicle;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use Twilio\Rest\Client;

class TwilioSMSController extends Controller
{
    /**
     * sending sms
     *
     * @return response()
     */
    public function index(Request $request)
    {

        $receiverNumber = "+12369000115";
        $requestData =$request->all();
        $message =CustomerSendMessages::where('id',$requestData['message_id'])->first('message');

        try {

            $sid = "ACb414b973404343e8895b05d5be3cc056";
            $token = "c135f0fc91ff9fdd0fcb805a6bdf3108";
            $twilio_number = "+16479316176";

            $client = new Client($sid, $token);
            $client->messages->create($receiverNumber, [
                'from' => $twilio_number,
                'body' => $message->message]);

            return RestAPI::response(new \stdClass(), true, "SMS send Successfully");

        } catch (Exception $e) {
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
    }


    /**
     * Get message list
     *
     */
    public function messagesList(Request $request)
    {

        DB::beginTransaction();
        try {

            $messages=CustomerSendMessages::get();
            $response = MessagesResource::collection($messages);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "Get Messages List Successfully");
    }


}

