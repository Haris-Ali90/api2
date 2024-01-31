<?php

namespace App\Http\Controllers\Api;

use App\Classes\RestAPI;
use App\Events\Api\UserCreateEvent;
use App\Http\Requests\Api\JoeySignUpRequest;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\ManagerUpdateProfileRequest;
use App\Http\Resources\LoginResource;
use App\Http\Resources\ManagerResource;
use App\Http\Resources\UserResource;
use App\Models\ManagerDashboard;
use App\Models\Roles;
use App\Repositories\Interfaces\ManagerRepositoryInterface;
use Carbon\Carbon;
use App\Models\Vehicle;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Requests\Api\StoreUserRequest;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\JoeyFlagLoginValidations;


use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Config;
use stdClass;
use Illuminate\Support\Facades\Validator;

class ManagerLoginApiController extends ApiBaseController
{


    private $managerRepository;
    use SendsPasswordResetEmails;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(ManagerRepositoryInterface $managerRepository)
    {
        $this->managerRepository = $managerRepository;
    }


    /**
     * Customer SignIn Api
     *
     */
    public function login(LoginRequest $request)
    {

        DB::beginTransaction();
        try {

            $check= ManagerDashboard::where('email',$request->get('email'))->where('type','manager')->whereIn('role_id',[2,5])->first();

            if (!$check) {
                return RestAPI::response('Email not exist. try again!.', false);
            }

            $credentials = $request->only(['email', 'password']) + ['role_id' => $check->role_id] + ['type' => $check->type];

            if (!$token = jwt_manger()->attempt($credentials)) {
                return RestAPI::response('Invalid credentials, please try again.', false);
            }

            $user = jwt_manger()->user();

            $user->addDevice($request->get('device_token'), $request->get('device_type'), $token);
            $user['_token'] = $token;

            DB::commit();
        } catch (\App\Exceptions\UserNotAllowedToLogin $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, $e->getResolvedErrorCode());
        }
        $response = new ManagerResource($user, $token);

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

            $user = jwt_manger()->user();
            $header =substr($request->header('Authorization'), 7);

           $user->removeDevice($header);
            jwt_manger()->logout();

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
            $user = ManagerDashboard::where('email', $request->get('email'))->where('role_id',2)->first();
            if (!$user) {
                return RestAPI::response('Email not exist. try again!.', false);
            }
            else {
                $token_validate = \Illuminate\Support\Facades\DB::table('password_resets')
                    ->where('email', $request->get('email'))
                    ->where('role_id', 2)
                    ->first();
                $token = hash('ripemd160', uniqid(rand(), true));

                if ($token_validate == null) {
                    \Illuminate\Support\Facades\DB::table('password_resets')
                        ->insert(['email' => $request->get('email'), 'role_id' => 2, 'token' => $token]);
                } else {
                    \Illuminate\Support\Facades\DB::table('password_resets')->where('email', $request->get('email'))
                        ->where('role_id', 2)
                        ->update(['token' => $token]);
                }

                $email = base64_encode($request->get('email'));
                $user->sendPasswordResetEmailToManager($email, $user->full_name, $token, 2);

                DB::commit();
                return RestAPI::response(new stdClass(), true, 'We have sent your password reset link on email, Please check Junk/Spam folder as well!');
            }
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
    }


    /**
     * Get Customer Profile Api
     *
     */
    public function profile(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = $this->managerRepository->find(jwt_manger()->user()->id);
            $response = new ManagerResource($user, jwt_manger()->fromUser($user));
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "Manager Profile Successfully");
    }

    /**
     * Update Customer Profile Api
     *
     */
    public function update(ManagerUpdateProfileRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->all();

//            if ($request->hasFile('profile_picture')) {

//                $slug = Str::slug($data['full_name'], '-');
//                $file = $request->file('profile_picture');
//                $extension = $file->getClientOriginalExtension(); // getting image extension
//                $filename = $slug . '-' . time() . '.' . $extension;
//                $file->move(backendUserFile(), $filename);
////                $data['profile_picture'] = url(backendUserFile() . $filename);
//                $img = file_get_contents(url(backendUserFile() . $filename));
//                // Encode the image string data into base64
//                $imageBase64 = base64_encode($img);
                if(isset($data['profile_picture'])){
                    $image = ['image' => $data['profile_picture']];
//
                    $response =  $this->sendData('POST', '/',  $image );

                    if(!isset($response->url)){
                        return RestAPI::response('error', true, "File cannot be uploaded due to server error!");
                    }

                    $attachment_path =   $response->url;
                    $data['profile_picture'] = $attachment_path;
                }else{
                    $user = $this->managerRepository->find(jwt_manger()->user()->id);
                    $data['profile_picture'] = $user->profile_picture;
                }




//            }
            /*else{
                $imageName="default.png";
                $postData['profile_picture'] = url('/').'/images/profile_images/'.$imageName;
            }*/

            $this->managerRepository->update(jwt_manger()->user()->id, $data);

            $user = $this->managerRepository->find(jwt_manger()->user()->id);
            $response = new ManagerResource($user, jwt_manger()->fromUser($user));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "Manager profile updated successfully");
    }



    /**
     * Customer Change Password Api
     *
     */
    public function changePassword(Request $request)
    {
        DB::beginTransaction();
        try {
            if (!(Hash::check($request->get('current_password'), jwt_manger()->user()->password))) {
                return RestAPI::response('Your current password does not matches with the password you provided. Please try again.', false);
            }

            if (strcmp($request->get('current_password'), $request->get('new_password')) == 0) {
                return RestAPI::response('New Password cannot be same as your current password. Please choose a different password.', false);
            }

            $validator = Validator::make($request->all(), [
                'current_password' => 'required',
                'new_password' => 'required|string|min:6|max:8|confirmed',
            ]);

            if ($validator->fails()) {
                return RestAPI::response('New Password should match with confirm password & it\'s lenght must be between 6 to 8 digits. Please try again.', false);
            }

            $user = jwt_manger()->user();

            $user->password = bcrypt($request->get('new_password'));
            $user->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response(new \stdClass(), true, 'Your Password has changed successfully');
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


//         var_dump([$this->originalResponse,$error]);
//         exit();
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
        // else{
        //     dd(['error'=> $error,'response'=>$this->originalResponse]);
        // }

        return $this->response;
    }

    public function mac256($ent,$key)
    {
        $res = hash_hmac('sha256', $ent, $key, true);
        return $res;
    }

    public function encodeBase64($data)
    {
        $data = base64_encode($data);
        return $data;
    }

}
