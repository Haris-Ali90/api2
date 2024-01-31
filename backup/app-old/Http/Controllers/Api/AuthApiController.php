<?php

namespace App\Http\Controllers\Api;

use App\Classes\RestAPI;
use App\Events\Api\UserCreateEvent;
use App\Http\Requests\Api\JoeySignUpRequest;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Resources\LoginResource;
use App\Http\Resources\UserResource;
use App\Models\JoeyFlagLoginValidations;
use Carbon\Carbon;
use App\Models\Vehicle;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Requests\Api\StoreUserRequest;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;

use stdClass;

class AuthApiController extends ApiBaseController
{
    private $userRepository;


    use SendsPasswordResetEmails;

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
     * Customer signUp Api
     *
     */
    public function register(JoeySignUpRequest $request)
    {

        $data = $request->all();


        DB::beginTransaction();
        try {
            $activeToken = uniqid(rand(1, 20), true);
            $userRecord = [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'password' => bcrypt($data['password']),
                'email' => $data['email'],
                'is_enabled' => 1,
                'user_type' => $data['user'],
                'location_id' => $data['location_id'],
                'email_verify_token' => $activeToken
            ];

            $user = $this->userRepository->create($userRecord);

            $user = $this->userRepository->find($user->id);

            $token = jwt()->fromUser($user);

            /*User::where('id',$user->id)->update(['email_verify_token' => $activeToken]);*/
            $user->addDevice($data['device_token'], $data['device_type'], $token);
            event(new UserCreateEvent($user, $activeToken));
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        $response = new UserResource($user, $token);
        return RestAPI::response($response, true, 'You have been registered successfully,Please check your activation email in your inbox to activate your account!');
    }

    /**
     * Customer SignIn Api
     *
     */
    public function login(LoginRequest $request)
    {


        DB::beginTransaction();
        try {

            $check = User::where('email', $request->get('email'))->first();

            if (!$check) {
                return RestAPI::response('Email not exist. try again!.', false);
            }

            $credentials = $request->only(['email', 'password']);

            if (!$token = jwt()->attempt($credentials)) {
                return RestAPI::response('Invalid credentials, please try again.', false);
            }

            $user = jwt()->user();

            $user->addDevice($request->get('device_token'), $request->get('device_type'), $token);
            $user['_token'] = $token;


            $user->validateUserActiveCriteria();
            $result = $user->joeyFlagLoginValidation($user, $request);
            if(isset($result)){
                if($result['status'] == true){
                    return RestAPI::response($result['message'], false);
                }
            }

            $response = new LoginResource($user, $token);
//            if ($user->isEnabled()) {
//           $vehicle= Vehicle::first();

            //               $api = $user->getApiKeys();
//                $response=[
//                    'id' => $user->id,
//                    'first_name' => $user->first_name,
//                    'last_name' =>$user->last_name,
//                    'nickname' => $user->nickname,
//                    'email' => $user->email,
//                    'phone' => $user->phone,
//                    'about' => $user->about,
//                    'vehicle' => [
//                        'id' => $vehicle->id,
//                        'name' => $vehicle->name
//                    ],
//                    'hub_id'  =>$user->hub_id,
//                    'token'=>$token,
//                    'is_online'=> 1
//                ];
            //    return RestAPI::response($response);
//            } else {
//                return RestAPI::response('Account not activated yet',false);
//
//            }
            DB::commit();
        } catch (\App\Exceptions\UserNotAllowedToLogin $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, $e->getResolvedErrorCode());
        }
        return RestAPI::response($response, true, "Logged-In Successfully");
    }

    /**
     * Customer Logout Api
     *
     */
    public function logout(Request $request)
    {
        DB::beginTransaction();
        try {

            $user = auth()->user();
            //   dd($user);
            $header = substr($request->header('Authorization'), 7);

            $user->removeDevice($header);
            auth()->guard('api')->logout();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response(new \stdClass(), true, "You have been logged out successfully.");
    }

    /**
     * Customer Forgot Password Api
     *
     */
    public function ForgotPassword(Request $request)
    {


        DB::beginTransaction();
        try {
            $user = User::where('email', $request->get('email'))->first();

            if (!$user) {
                return RestAPI::response('Email not exist. try again!.', false);
            } else {
                $token = uniqid(rand(1, 20), true);
                $token_validate = DB::table('password_resets')
                    ->where('email', $request->email)
                    ->where('role_id', 6)
                    ->whereNull('user_type')
                    ->first();
                if ($token_validate == null) {
                    DB::table('password_resets')
                        ->insert(['email'=> $request->email,'role_id' => 6,'token' => $token]);
                }
                else
                {
                    DB::table('password_resets')
                        ->where('email', $request->email)
                        ->where('role_id', 6)->whereNull('user_type')->update(['token' => $token]);
                }

                $email = base64_encode($user->email);

                $user->sendPasswordResetEmail($email, $token);
                DB::commit();
                return RestAPI::response(new stdClass(), true, 'We have sent your password reset link on email, Please check Junk/Spam folder as well!');
            }
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
    }

}
