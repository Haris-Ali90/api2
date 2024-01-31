<?php

namespace App\Http\Controllers\Api;

use App\Classes\RestAPI;

use App\Http\Resources\ComplaintResource;
use App\Models\Joey;
use App\Models\Sprint;
use App\Repositories\Interfaces\ComplaintRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class ComplaintController extends ApiBaseController
{

    private $complaintRepository;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(ComplaintRepositoryInterface $complaintRepository)
    {
        $this->complaintRepository = $complaintRepository;
    }

    /**
     * Get Vehicle list
     *
     */
    public function new(Request $request)
    {

        $data = $request->all();

        DB::beginTransaction();
        try {


          //  $checkOrder=Sprint::where('id', $data['order_id'])->exists();

            if(Sprint::where('id', $data['order_id'])->doesntExist()) {
                return RestAPI::response('Order Id doesnot exists!', false);
            }

            if(Joey::where('id', $data['joey_id'])->doesntExist()) {
                return RestAPI::response('joey does not exists', false);
            }

                $complaintRecord = [
                    'order_id' => $data['order_id'],
                    'joey_id' => $data['joey_id'],
                    'type' => $data['type'],
                    'description' => $data['description'],
                    'status' => 0
                ];


                $complaint = $this->complaintRepository->create($complaintRecord);

               DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
      $response = new ComplaintResource($complaint);
        //dd($response);
        return RestAPI::response($response, true, 'Your Complaint has been registered successfully,Action will be Taken soon ');

    }
    public function complaintTypes()
    {

        DB::beginTransaction();
        try {
          
            $types=['Finance','Technical','Vendor Side','Address Issue','Other'];

            $response=$types;

               DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
    //   $response = new ComplaintResource($complaint);
        //dd($response);
        return RestAPI::response($response, true, 'Complaint Types');
    }
}
