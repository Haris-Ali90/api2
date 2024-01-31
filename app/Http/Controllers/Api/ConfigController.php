<?php

namespace App\Http\Controllers\Api;

use App\Classes\RestAPI;
use App\Http\Requests\Api\ContactUsRequest;
use App\Http\Resources\CustomerPageResource;
use App\Http\Resources\NotificationResource;
use App\Http\Resources\UserResource;
use App\Repositories\Interfaces\PageRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConfigController extends ApiBaseController
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
     * Get Metadeta
     *
     */
    public function meta(Request $request)
    {

        DB::beginTransaction();
        $response = [];
        $platform = $request->get('platform', 'customer');
        $userId = $request->get('user_id');

        if (!in_array($platform, ['customer', 'merchant', 'joey'])) {
            $platform = 'customer';
        }


        $url=config('application')[$platform.'_base_url'];

        $response['links'] = [
            'home' =>config('application')[$platform.'_base_url'] . '/',
            'signup' =>config('application')[$platform.'_base_url']. '/signup',
            'privacy' =>config('application')[$platform.'_base_url'] . '/privacy',
            'terms' =>config('application')[$platform.'_base_url'] . '/terms'
        ];


        try {
            $user = $this->userRepository->find(auth()->user()->id);
            $response = new UserResource($user, jwt()->fromUser($user));
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "Get User Profile Successfully");
    }



}
