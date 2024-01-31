<?php

namespace App\Http\Controllers\Api;

use App\Classes\RestAPI;

use App\Http\Resources\CategoryTrainingResource;
use App\Http\Resources\ComplaintResource;
use App\Http\Resources\FaqsResource;
use App\Http\Resources\JoeyOrderCategoryListResource;
use App\Http\Resources\OrderCategoryTrainingResource;
use App\Http\Resources\VendorFaqsResource;
use App\Http\Resources\VendorListResource;
use App\Http\Resources\VendorTrainingResource;
use App\Models\Faqs;
use App\Models\JoeyOrderCategory;
use App\Models\JoeyTrainingSeen;
use App\Models\OrderCategory;
use App\Models\Sprint;
use App\Models\Training;
use App\Models\Vendor;
use App\Repositories\Interfaces\ComplaintRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class TrainingController extends ApiBaseController
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
    public function orderCategoriesAndVendorsList(Request $request)
    {

        $data = $request->all();

        DB::beginTransaction();
        try {


            $joey = $this->userRepository->find(auth()->user()->id);


            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }


            $joeyOrderCategories = OrderCategory::all();
            $vendors =  Vendor::where('is_registered', 1)->whereIn('id', [475580,477491,476734,475761,476610,476734,476850,476867,476933,476867,476967,476968,476969,476970,477006,477068,477069,477078,477123,477124,477150,477153,477154,477157,477192,477267,477268,477279,476761,477194,477195,477205,477281,477464,477465,477466,477467,477468,477469,477470,477471,477472,477473,477474,477475])->orderBy('name')->get();


            $JoeyOrderCategoryResource = JoeyOrderCategoryListResource::collection($joeyOrderCategories);
            $vendorsResource = VendorListResource::collection($vendors);


            $response [] = [
                'Joey_order_category_list' =>  $JoeyOrderCategoryResource,
                'Vendor_list' => $vendorsResource
                ];



               DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "Order Categories Vendor list");

    }





    public function order_category_trainings(Request $request)
    {


        $data = $request->all();

        DB::beginTransaction();
        try {

    $order_Category_Trainings = array();
            $joey = $this->userRepository->find(auth()->user()->id);


            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }
            $orderCategoryTrainings = Training::where('order_category_id',$data['order_category_id'])->whereNull('deleted_at')->get();

                foreach($orderCategoryTrainings as $order_Category_Trainings){
                                    if($order_Category_Trainings->type=='video'){
                                         $seenCount = JoeyTrainingSeen::where('joey_id',$joey->id)->where('training_id',$order_Category_Trainings->id)->get()->count();
                                            if($seenCount>1)
                                            {
                                                $order_Category_Trainings['seen']=1;
                                            }
                                            else
                                            {
                                                $order_Category_Trainings['seen']=0;

                                            }


                                    }else{
                                        $seenCount = JoeyTrainingSeen::where('joey_id',$joey->id)->where('training_id',$order_Category_Trainings->id)->get()->count();
                                        if($seenCount>1)
                                            {
                                                $order_Category_Trainings['seen']=1;
                                            }
                                            else
                                            {
                                                $order_Category_Trainings['seen']=0;
                                            }

                                    }


                }


            $response =new OrderCategoryTrainingResource($order_Category_Trainings);




               DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "Order Category Training");

    }


    public function vendor_trainings(Request $request)
    {


        $data = $request->all();

        DB::beginTransaction();
        try {

           $vendor_Trainings = array();
            $joey = $this->userRepository->find(auth()->user()->id);

            $response = [];
            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }
            if ($data['type'] == 'category') {
                if($data['file_type'] =='document'){
                    $trainings = Training::where('order_category_id', $data['id'])->whereIn('type', ['document','image/jpeg','application/pdf','image/png','image/jpg'])->whereNull('deleted_at')->get();
                }
                else{
                    $trainings = Training::where('order_category_id', $data['id'])->where('type', 'video/mp4')->whereNull('deleted_at')->get();
                }
            }
            else{
                if($data['file_type'] =='document'){
                    $trainings = Training::where('vendors_id', $data['id'])->whereIn('type', ['document','image/jpeg','image/png','image/jpg'])->whereNull('deleted_at')->get();
                }
                else {
                    $trainings = Training::where('vendors_id', $data['id'])->where('type', 'video/mp4')->whereNull('deleted_at')->get();
                }

            }

            if (!empty($trainings)) {
                $response = VendorTrainingResource::collection($trainings);
            }

               DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "Vendor Training");

    }











    public function training_seen(Request $request)
    {


        $data = $request->all();

        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);


            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }


            $joeyTrainingSeenData=[
                'joey_id' => $joey->id,
                'training_id' => $data['training_id']

            ];

            JoeyTrainingSeen::insert($joeyTrainingSeenData);



               DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response(new \stdClass(), true, 'Seen Captured Successfully.');

    }





    public function categoryTraining(Request $request)
    {


        $data = $request->all();

        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);


            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }


            $orderCategoryList=OrderCategory::whereNUll('user_type')->whereNull('deleted_at')->orderBy('type','ASC')->get();


            if (!empty($orderCategoryList)) {
                $response = CategoryTrainingResource::collection($orderCategoryList);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, "Category Training");
    }



}
