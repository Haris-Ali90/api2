<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\FlagResource;
use App\Models\FlagHistory;
use App\Models\JoeyPerformanceHistory;
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


class FlagController extends ApiBaseController
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

    public function flagList(Request $request)
    {
        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);
            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }

            $flags = FlagHistory::with('flagDetail')
                ->whereJoeyId($joey->id)
                ->where('unflaged_by', 0)
                ->where('is_approved', $request->get('status'))
                ->orderBy('id', 'DESC')
//                ->whereNull('deleted_at')
                ->paginate($request->get('limit')??10);

            if (count($flags) > 0) {
                $response = FlagResource::collection($flags);
                return RestAPI::setPagination($flags)->response($flags->items(), true, 'Flag list.');
//                return RestAPI::response($response, true, 'Flag list');
            } else {
                return RestAPI::response('No record found', true);
            }

        } catch (\Exception $exception) {
            DB::rollback();
            return RestAPI::response($exception->getMessage(), false, 'error_exception');
        }
    }

    public function flagCounts()
    {
        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);
            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }

            $flags = FlagHistory::whereJoeyId($joey->id)->where('unflaged_by', 0)->get();
            $approved = $flags->where('is_approved', 1)->count();
            $notApproved = $flags->where('is_approved', 0)->count();

            $response['approved'] = $approved;
            $response['not_approved'] = $notApproved;
            if (!empty($response)) {
                return RestAPI::response($response, true, 'Flag Counts');
            } else {
                return RestAPI::response('No record found', true);
            }

        } catch (\Exception $exception) {
            DB::rollback();
            return RestAPI::response($exception->getMessage(), false, 'error_exception');
        }
    }

}







