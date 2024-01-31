<?php

namespace App\Http\Controllers\Api;

use App\Classes\RestAPI;

use App\Http\Resources\ComplaintResource;
use App\Http\Resources\FaqsResource;
use App\Http\Resources\VendorFaqsResource;
use App\Models\Faqs;
use App\Models\Sprint;
use App\Models\Vendor;
use App\Repositories\Interfaces\ComplaintRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class FaqController extends ApiBaseController
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
     * Get Vehicle list
     *
     */
    public function faq_vendors(Request $request)
    {

        $data = $request->all();

        DB::beginTransaction();
        try {


            $joey = $this->userRepository->find(auth()->user()->id);


            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }


               $faqs = Vendor::whereNull('deleted_at')->whereIn('id',[477255])->get();

            $response = VendorFaqsResource::collection($faqs);

               DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "Vendor Faqs Details");

    }


    public function faqs(Request $request)
    {

        $data = $request->all();

        DB::beginTransaction();
        try {


            $joey = $this->userRepository->find(auth()->user()->id);


            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }


            if(Faqs::where('vendor_id',$data['vendor_id'])->doesntExist()){
                return RestAPI::response('This vendor does not exists', false);
            }

               $faqs = Faqs::where('vendor_id',$data['vendor_id'])->get();
            $response = FaqsResource::collection($faqs);

               DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "Faqs Details");

    }
}
