<?php

namespace App\Http\Controllers\Api;

use App\Classes\ECommercePayoutCalculationByRoute;
use App\Classes\JoeyPayoutCalculation;
use App\Http\Resources\JoeyPayoutReportDetailResource;
use App\Http\Resources\JoeyPayoutReportResource;
use App\Models\JoeyPayoutHistory;
use App\Models\JoeyPlan;
use App\Models\RouteHistory;
use App\Repositories\Interfaces\SystemParametersRepositoryInterface;
use Validator;
use App\Classes\RestAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;



class PayoutCalculationController extends ApiBaseController
{

    private $SystemParametersRepository;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(SystemParametersRepositoryInterface $SystemParametersRepositoryInterface)
    {
        $this->SystemParametersRepository = $SystemParametersRepositoryInterface;

    }

    public function markCompleteRoute(Request $request , ECommercePayoutCalculationByRoute $ECommercePayoutCalculationByRoute)
    {
        //getting data from request
        $data = $request->all();
        $auth = auth()->user();
        $emptyOblect = new \stdClass();
        DB::beginTransaction();
        try {
            //getting route ids
            $route_ids = explode(',',$data['route_id']);
            //calling class for sending request
            $payoutCalculation = $ECommercePayoutCalculationByRoute;

            // setting marked by
            $payoutCalculation->setMarkedBy($auth->id);

            //sending data for set route ids
            $payoutCalculation->setRequestRoutes($route_ids);

            //Validate route ids
            $payoutCalculation->validateRequestForCompletion();
            $payoutCalculation->markCompleteRouteAndSaveCalculation();

            DB::commit();

        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($emptyOblect, true, 'Your Requested Route has been marked completed successfully');
    }

    /**
     * for  joey payout report
     *
     */
    public function joeyPayoutReport(Request $request)
    {
        $data = $request->all();
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => ['required','date','after_or_equal:start_date'],
            'limit' => 'required'
        ]);
        // setting start date and end date variables
        $start_date = $data['start_date'].' 00:00:00';
        $end_date = $data['end_date'].' 23:59:59';
        $limit = $data['limit'];
        //convert time zone
        //convert time zone
        $start_date_converted = ConvertTimeZone($start_date,'America/Toronto','UTC');
        $end_date_converted = ConvertTimeZone($end_date,'America/Toronto','UTC');
        $auth = auth()->user();
        DB::beginTransaction();
        try {
            //$users = $this->managerRepository->find(auth()->user());
            if (empty($auth)) {
                return RestAPI::response('Joey  record not found', true);
            }
            $joeyPayoutReports = JoeyPayoutHistory::where('joey_id',$auth->id)->whereBetween('created_at',[$start_date_converted,$end_date_converted])->paginate($limit);
            if($joeyPayoutReports->isEmpty()){
                return RestAPI::response('No record', false);
            }
            $response = JoeyPayoutReportResource::collection($joeyPayoutReports);
            DB::commit();
            return RestAPI::setPagination($joeyPayoutReports)->response($response, true, 'Joey Payout Report');
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
    }
    /**
     * for joey payout report detail
     *
     */
    /*public function joeyPayoutReportDetail(Request $request)
    {
        $data = $request->all();

        $route_id = $data['route_id'];

        DB::beginTransaction();
        try {
            if(empty($route_id)){

                return RestAPI::response('No record found against this route Id', false);
            }
            $routeDetail = JoeyPayoutHistory::where('route_id',$route_id)->first();
            if(!empty($routeDetail)){

                $response = new JoeyPayoutReportDetailResource($routeDetail);

            }
            else{
                return RestAPI::response('No record found against this Tracking Id', false);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response($response, true, 'Joey Payout Report Details');
    }*/


}







