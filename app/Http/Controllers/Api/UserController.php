<?php

namespace App\Http\Controllers\Api;

use App\Classes\RestAPI;
use App\Http\Requests\Api\StoreDocumentRequest;
use App\Http\Requests\Api\StoreJoeyRequest;


use App\Http\Requests\Api\UpdateProfileRequest;
use App\Http\Resources\AboutUsResource;
use App\Http\Resources\JoeyDepositResource;
use App\Http\Resources\JoeyDocumentResource;
use App\Http\Resources\JoeyVehicleResource;
use App\Http\Resources\NotificationResource;
use App\Http\Requests\Api\AgreementRequest;

use App\Http\Resources\PrivacyPolicyResource;
use App\Http\Resources\TermsConditionResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserWorkPreferenceResource;
use App\Models\Interfaces\JoeyVehiclesDetailInterface;
use App\Models\Joey;
use App\Models\Agreement;
use App\Models\AgreementUser;
use Illuminate\Support\Facades\Mail;

use App\Http\Resources\GetJoeyAgreementResource;


use App\Models\JoeyDeposit;
use App\Models\DocumentType;
use App\Models\JoeyDocument;
use App\Models\JoeyVehiclesDetail;
use App\Models\User;
use App\Models\Vehicle;
use App\Repositories\Interfaces\NotificationRepositoryInterface;
use Carbon\Carbon;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserController extends ApiBaseController
{
    private $userRepository;

    /*
        private $customerpageRepository;*/

    private $notificationRepository;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(UserRepositoryInterface $userRepository, NotificationRepositoryInterface $notificationRepository)
    {

        $this->userRepository = $userRepository;
        $this->notificationRepository = $notificationRepository;

    }

    /**
     * Get Customer Profile Api
     *
     */
    public function profile(Request $request)
    {

        DB::beginTransaction();
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

    /**
     * Update Customer Profile Api
     *
     */
    public function update(UpdateProfileRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->all();

            $updateData = [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'date_of_birth' => $data['date_of_birth'],
                'address' => $data['address'],
                'vehicle_id' => $data['vehicle_id'],

            ];

            if ($request->has('phone') && $request->get('phone', '') != '') {
                $updateData['phone'] = phoneFormat($data['phone']);
            }
            if ($request->hasfile('profile_picture')) {
                //move | upload file on server
                $slug = Str::slug($data['first_name'], '-');
                $file = $request->file('profile_picture');
                $extension = $file->getClientOriginalExtension(); // getting image extension
                $filename = $slug . '-' . time() . '.' . $extension;
                $file->move(backendUserFile(), $filename);
                $updateData['image'] = url(backendUserFile() . $filename);
            }

            $this->userRepository->update(auth()->user()->id, $updateData);

            $user = $this->userRepository->find(auth()->user()->id);
            JoeyVehiclesDetail::where('joey_id', $user->id)->update(['vehicle_id' => $data['vehicle_id']]);
            $response = new UserResource($user, jwt()->fromUser($user));


            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "User profile updated successfully");
    }


    /**
     * Customer Change Password Api
     *
     */
    public function changePassword(Request $request)
    {
        DB::beginTransaction();
        try {
            if (!(Hash::check($request->get('current_password'), auth()->user()->password))) {
                return RestAPI::response('Your current password does not matches with the password you provided. Please try again.', false);
            }

            if (strcmp($request->get('current_password'), $request->get('new_password')) == 0) {
                return RestAPI::response('New Password cannot be same as your current password. Please choose a different password.', false);
            }

            $validator = Validator::make($request->all(), [
                'current_password' => 'required',
                'new_password' => 'required|string|min:6|confirmed',
            ]);

            if ($validator->fails()) {
                return RestAPI::response('New Password should match with confirm password & it\'s lenght must be minimum 6. Please try again.', false);
            }

            $user = auth()->user();
            $user->password = bcrypt($request->get('new_password'));
            $user->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response(new \stdClass(), true, 'Your Password has changed successfully');
    }


    /**
     * Get Customer Profile Api new functions
     *
     */
    public function personalDetails(Request $request)
    {

        DB::beginTransaction();
        try {
            $user = $this->userRepository->findWithVehicle(auth()->user()->id);


//
            $result = $user->joeyFlagLoginValidation($user, $request);
//
            if (isset($result)) {
                if ($result['status'] == true) {
                    return RestAPI::response($result['message'], false, 'token_invalid');
                }
            }
            $response = new UserResource($user, jwt()->fromUser($user));
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "Get User Profile Successfully");
    }

    /**
     * Update Customer Profile Api
     *
     */

    public function updatePersonalDetails(StoreJoeyRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->all();


            $updateData = [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'address' => $data['address'],
                /* 'unit_number' => $data['unit_number'],*/

            ];


            if ($request->has('phone') && $request->get('phone', '') != '') {
                $updateData['phone'] = phoneFormat($data['phone']);
            }

//            if ($request->hasfile('profile_picture')) {
//                //move | upload file on server
//                $slug = Str::slug($data['first_name'], '-');
//                $file = $request->file('profile_picture');
//                $extension = $file->getClientOriginalExtension(); // getting image extension
//                $filename = $slug . '-' . time() . '.' . $extension;
//                $file->move(backendUserFile(), $filename);
//                $updateData['image'] = url(backendUserFile() . $filename);
//            }


            if (!empty($data['image'])) {
                $path = $this->upload($data['image']);
                if(!isset($path)){
                    return RestAPI::response('File cannot be uploaded due to server error!', false);
                }
                $updateData['image'] = $path;
            }

            $this->userRepository->update(auth()->user()->id, $updateData);

            $user = $this->userRepository->find(auth()->user()->id);
            $response = new UserResource($user, jwt()->fromUser($user));


            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "Personal Details Updated Successfully!");
    }


    /**
     * Get Vehicle information
     *
     */
    public function vehicleInformation(Request $request)
    {

        DB::beginTransaction();
        $response = new \stdClass();
        try {
            $userVehicle = JoeyVehiclesDetail::where('joey_id', auth()->user()->id)->first();


            if ($userVehicle) {
                $response = new JoeyVehicleResource($userVehicle);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "Joey Vehicle Details");
    }


    /**
     * Update vehicle information
     *
     */
    public function updateJoeyVehicle(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->all();
            $joey = $this->userRepository->find(auth()->user()->id);

            if (!empty($joey)) {
                $joey->update([
                    'vehicle_id' => $data['vehicle_id'],
                ]);
                $check_vehicle = JoeyVehiclesDetail::where('joey_id', $joey->id)->first();
                if ($check_vehicle) {
                    JoeyVehiclesDetail::where('joey_id', $joey->id)->update(['vehicle_id' => $data['vehicle_id'], 'make' => $data['make'], 'color' => $data['color'], 'model' => $data['model'], 'license_plate' => $data['license_plate']]);
                } else {
                    JoeyVehiclesDetail::create(['joey_id' => $joey->id, 'vehicle_id' => $data['vehicle_id'], 'make' => $data['make'], 'color' => $data['color'], 'model' => $data['model'], 'license_plate' => $data['license_plate']]);
                }


            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response(new \stdClass(), true, 'Vehicle Details Updated Successfully!');
    }


    /**
     * fucntion for joey deposit
     *
     */
    public function joeyDeposit(Request $request)
    {

        DB::beginTransaction();
        try {
            $joey = $this->userRepository->find(auth()->user()->id);
            $response = new JoeyDepositResource($joey);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "Joey Deposit Details");
    }


    /**
     * fucntion for joey deposit
     *
     */
    public function deposit(Request $request)
    {
        $currentDate = Carbon::now()->format('Y/m/d H:i:s');
        DB::beginTransaction();
        try {


            $data = $request->validate([
                'institution_no' => '',
                'branch_no' => '',
                'account_no' => '',
                'hst_number' => '',
                'hear_from' => '',
                'hst_company' => '',
                'image' => '',
            ]);

            $joey = $this->userRepository->find(auth()->user()->id);


            $recordForJoeyDeposit = [
                'joey_id' => $joey->id,
                'institution_no' => $data['institution_no'],
                'branch_no' => $data['branch_no'],
                'account_no' => $data['account_no'],
                'created_at' => $currentDate,
                'updated_at' => $currentDate,
            ];

            if (!empty($data['image'])) {
                $path = $this->upload($data['image']);
                if(!isset($path)){
                    return RestAPI::response('File cannot be uploaded due to server error!', false);
                }
                $recordForJoeyDeposit['image'] = $path;
            }

            $hstnumber = '';

            if (!empty($data['hst_number'])) {
                $hstnumber = $data['hst_number'];
            }

            $hear_from = '';
            if (!empty($data['hear_from'])) {
                $hear_from = $data['hear_from'];
            }


            $hst_company = '';
            if (!empty($data['hst_company'])) {
                $hst_company = $data['hst_company'];
            }


            $recordForUserTable = [
                'hst_number' => $hstnumber,
                /*      'rbc_deposit_number' => $data['rbc_deposit_number'],*/
                'hear_from' => $hear_from,
                'hst_company' => $hst_company,
                'is_active' => 1

            ];

//dd($recordForUserTable);

            $recordCheck = JoeyDeposit::where('joey_id', $joey->id)->first();

            if (!empty($recordCheck)) {

                JoeyDeposit::where('joey_id', $joey->id)->update($recordForJoeyDeposit);
            } else {
                JoeyDeposit::insert($recordForJoeyDeposit);
            }

            $this->userRepository->update(auth()->user()->id, $recordForUserTable);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response(new \stdClass(), true, 'Additional Information Updated Successfully!');
    }


    /**
     * fucntion for joey work preference
     *
     */
    public function work_preference(Request $request)
    {

        DB::beginTransaction();
        try {
            $joey = $this->userRepository->find(auth()->user()->id);

            $response = new UserWorkPreferenceResource($joey);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "Joey Work Preference Details");
    }


    /**
     * fucntion for update joey work preference
     *
     */
    public function workPreferenceUpdate(Request $request)
    {
        DB::beginTransaction();
        try {


            $data = $request->all();


            $record = [
                'work_type' => $data['work_type'],
                'contact_time' => $data['contact_time'],
                'preferred_zone' => $data['preferred_zone_id'],
                'shift_store_type' => $data['shift_store_type']
            ];

            $this->userRepository->update(auth()->user()->id, $record);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response(new \stdClass(), true, 'Work Preference Updated Successfully!');
    }


    /**
     * fucntion for joey Document update
     *
     */

    public function joeyDocument(StoreDocumentRequest $request)
    {


        $requestData = $request->all();

//     $requestData = $request->validate([
//         'driver_permit'=>'mimes:jpg,jpeg,png,pdf|max:5120',
//         'driver_license'=>'mimes:jpg,jpeg,png,pdf|max:5120',
//         'study_permit'=>'mimes:jpg,jpeg,png,pdf|max:5120',
//         'vehicle_insurance'=>'mimes:jpg,jpeg,png,pdf|max:5120',
//         'additional_document_1'=>'mimes:jpg,jpeg,png,pdf|max:5120',
//         'additional_document_2'=>'mimes:jpg,jpeg,png,pdf|max:5120',
//         'additional_document_3'=>'mimes:jpg,jpeg,png,pdf|max:5120',
//         'sin'=>'string',
//         'driver_permit_exp_date' => 'date_format:Y-m-d',
//         'driver_license_exp_date' => 'date_format:Y-m-d',
//         'study_permit_exp_date' => 'date_format:Y-m-d',
//         'vehicle_insurance_exp_date' => 'date_format:Y-m-d',
//         'additional_document_1_exp_date' => 'date_format:Y-m-d',
//         'additional_document_2_exp_date' => 'date_format:Y-m-d',
//         'additional_document_3_exp_date' => 'date_format:Y-m-d',
//         'sin_exp_date' => 'date_format:Y-m-d',
//        ]);

        $joey = $this->userRepository->find(auth()->user()->id);
        DB::beginTransaction();
        try {


            if (empty($requestData)) {
                return RestAPI::response('You are required to upload atleast one document', false);
            }

            if ($request->hasfile('driver_permit')) {
                $path = '';
                $imagedata = file_get_contents($request->file('driver_permit'));
                $base64 = base64_encode($imagedata);
                if (!empty($base64)) {
                    $path = $this->upload2($base64);
                    if (!isset($path)) {
                        return RestAPI::response('File cannot be uploaded due to server error!', false);
                    }
                }

                $joeyDocument = JoeyDocument::where('document_type', '=', "driver_permit")->where('joey_id', '=', $joey->id)->first();

                if(!empty($joeyDocument)){
                    if($joeyDocument->is_approved == 1 && $joeyDocument->exp_date > date('Y-m-d')){
                        return RestAPI::response('Driver permit already approved!', false);
                    }
                }

                JoeyDocument::where('document_type', '=', "driver_permit")->where('joey_id', '=', $joey->id)->update(['deleted_at' => date("Y-m-d h:i:s")]);
                JoeyDocument::create(['joey_id' => $joey->id, 'document_data' => $path, 'exp_date' => $request['driver_permit_exp_date'], 'document_type' => "driver_permit"]);


            } /* For Driver Permit */
            if ($request->hasfile('driver_license')) {
                $path = '';
                $imagedata = file_get_contents($request->file('driver_license'));
                $base64 = base64_encode($imagedata);
                if (!empty($base64)) {
                    $path = $this->upload2($base64);
                    if (!isset($path)) {
                        return RestAPI::response('File cannot be uploaded due to server error!', false);
                    }
                }

                $joeyDocument = JoeyDocument::where('document_type', '=', "driver_license")->where('joey_id', '=', $joey->id)->first();

                if(!empty($joeyDocument)){
                    if($joeyDocument->is_approved == 1 && $joeyDocument->exp_date > date('Y-m-d')){
                        return RestAPI::response('Driver license already approved!', false);
                    }
                }

                JoeyDocument::where('document_type', '=', "driver_license")->where('joey_id', '=', $joey->id)->update(['deleted_at' => date("Y-m-d h:i:s")]);
                JoeyDocument::create(['joey_id' => $joey->id, 'document_data' => $path, 'exp_date' => $request['driver_license_exp_date'], 'document_type' => "driver_license"]);
            }/* For Study Or Work Permit */
            if ($request->hasfile('study_permit')) {
                $path = '';
                $imagedata = file_get_contents($request->file('study_permit'));
                $base64 = base64_encode($imagedata);
                if (!empty($base64)) {
                    $path = $this->upload2($base64);
                    if (!isset($path)) {
                        return RestAPI::response('File cannot be uploaded due to server error!', false);
                    }
                }

                $joeyDocument = JoeyDocument::where('document_type', '=', "study_permit")->where('joey_id', '=', $joey->id)->first();

                if(!empty($joeyDocument)){
                    if($joeyDocument->is_approved == 1 && $joeyDocument->exp_date > date('Y-m-d')){
                        return RestAPI::response('Study permit already approved!', false);
                    }
                }

                JoeyDocument::where('document_type', '=', "study_permit")->where('joey_id', '=', $joey->id)->update(['deleted_at' => date("Y-m-d h:i:s")]);
                JoeyDocument::create(['joey_id' => $joey->id, 'document_data' => $path, 'exp_date' => $request['study_permit_exp_date'], 'document_type' => "study_permit"]);
            } /* For Vehicle Insurance */
            if ($request->hasfile('vehicle_insurance')) {
                $path = '';
                $imagedata = file_get_contents($request->file('vehicle_insurance'));
                $base64 = base64_encode($imagedata);
                if (!empty($base64)) {
                    $path = $this->upload2($base64);
                    if (!isset($path)) {
                        return RestAPI::response('File cannot be uploaded due to server error!', false);
                    }
                }

                $joeyDocument = JoeyDocument::where('document_type', '=', "vehicle_insurance")->where('joey_id', '=', $joey->id)->first();

                if(!empty($joeyDocument)){
                    if($joeyDocument->is_approved == 1 && $joeyDocument->exp_date > date('Y-m-d')){
                        return RestAPI::response('Vehicle Insurance already approved!', false);
                    }
                }

                JoeyDocument::where('document_type', '=', "vehicle_insurance")->where('joey_id', '=', $joey->id)->update(['deleted_at' => date("Y-m-d h:i:s")]);
                JoeyDocument::create(['joey_id' => $joey->id, 'document_data' => $path, 'exp_date' => $request['vehicle_insurance_exp_date'], 'document_type' => "vehicle_insurance"]);
            }/* For Additional 1 */
            if ($request->hasfile('additional_document_1')) {
                $path = '';
                $imagedata = file_get_contents($request->file('additional_document_1'));
                $base64 = base64_encode($imagedata);
                if (!empty($base64)) {
                    $path = $this->upload2($base64);
                    if (!isset($path)) {
                        return RestAPI::response('File cannot be uploaded due to server error!', false);
                    }
                }

                $joeyDocument = JoeyDocument::where('document_type', '=', "additional_document_1")->where('joey_id', '=', $joey->id)->first();

                if(!empty($joeyDocument)){
                    if($joeyDocument->is_approved == 1 && $joeyDocument->exp_date > date('Y-m-d')){
                        return RestAPI::response('additional document already approved!', false);
                    }
                }

                JoeyDocument::where('document_type', '=', "additional_document_1")->where('joey_id', '=', $joey->id)->update(['deleted_at' => date("Y-m-d h:i:s")]);
                JoeyDocument::create(['joey_id' => $joey->id, 'document_data' => $path, 'exp_date' => $request['additional_document_1_exp_date'], 'document_type' => "additional_document_1"]);
            }/* For Additional 2 */
            if ($request->hasfile('additional_document_2')) {
                $path = '';
                $imagedata = file_get_contents($request->file('additional_document_2'));
                $base64 = base64_encode($imagedata);
                if (!empty($base64)) {
                    $path = $this->upload2($base64);
                    if (!isset($path)) {
                        return RestAPI::response('File cannot be uploaded due to server error!', false);
                    }
                }

                $joeyDocument = JoeyDocument::where('document_type', '=', "additional_document_2")->where('joey_id', '=', $joey->id)->first();

                if(!empty($joeyDocument)){
                    if($joeyDocument->is_approved == 1 && $joeyDocument->exp_date > date('Y-m-d')){
                        return RestAPI::response('additional document 2 already approved!', false);
                    }
                }

                JoeyDocument::where('document_type', '=', "additional_document_2")->where('joey_id', '=', $joey->id)->update(['deleted_at' => date("Y-m-d h:i:s")]);
                JoeyDocument::create(['joey_id' => $joey->id, 'document_data' => $path, 'exp_date' => $request['additional_document_2_exp_date'], 'document_type' => "additional_document_2"]);
            }/* For Additional 3 */
            if ($request->hasfile('additional_document_3')) {
                $path = '';
                $imagedata = file_get_contents($request->file('additional_document_2'));
                $base64 = base64_encode($imagedata);
                if (!empty($base64)) {
                    $path = $this->upload2($base64);
                    if (!isset($path)) {
                        return RestAPI::response('File cannot be uploaded due to server error!', false);
                    }
                }

                $joeyDocument = JoeyDocument::where('document_type', '=', "additional_document_3")->where('joey_id', '=', $joey->id)->first();

                if(!empty($joeyDocument)){
                    if($joeyDocument->is_approved == 1 && $joeyDocument->exp_date > date('Y-m-d')){
                        return RestAPI::response('additional document 3 already approved!', false);
                    }
                }

                JoeyDocument::where('document_type', '=', "additional_document_3")->where('joey_id', '=', $joey->id)->update(['deleted_at' => date("Y-m-d h:i:s")]);
                JoeyDocument::create(['joey_id' => $joey->id, 'document_data' => $path, 'exp_date' => $request['additional_document_3_exp_date'], 'document_type' => "additional_document_3"]);
            }/* For Sin */
            if (!empty($request['sin'])) {
                JoeyDocument::where('document_type', '=', "sin")->where('joey_id', '=', $joey->id)->update(['deleted_at' => date("Y-m-d h:i:s")]);
                JoeyDocument::create(['joey_id' => $joey->id, 'document_data' => $request['sin'], 'exp_date' => $request['sin_exp_date'], 'document_type' => "sin"]);
            }


            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response(new \stdClass(), true, "Joey Document Upload");
    }


    /**
     * fucntion for joey get document types
     *
     */

     public function getdocumentTypes(Request $request)
    {

        DB::beginTransaction();
        try {
            $doctypes = DocumentType::whereNull('deleted_at')->whereNull('user_type')->get();

            $i = 0;
            foreach ($doctypes as $document) {

                $doctype = str_replace(' ', '_', $document->document_name);

                // if($document->document_type=='text'){
                //     $doctype = 'sin';
                // }

                $userdocument = JoeyDocument::where('joey_id', auth()->user()->id)->where('document_type', $document->document_name)->first();

                if (!empty($userdocument)) {

                    $response[$i]['id'] = $document->id;
                    $response[$i]['document_name'] = ucfirst($document->document_name);
                    $response[$i]['document_type'] = $doctype;
                    $response[$i]['data_type'] = $document->document_type;
                    $response[$i]['is_optional'] = $document->is_optional;
                    $response[$i]['is_expiry'] = $document->exp_date;
                    $response[$i]['max_characters_limit'] = $document->max_characters_limit;
                    $response[$i]['document_data'] = $userdocument->document_data;
                    if (!empty($userdocument->exp_date) && $userdocument->exp_date != '(NULL)') {
                        $response[$i]['exp_date'] = $userdocument->exp_date;
                    } else {
                        $response[$i]['exp_date'] = '';
                    }


                    $currentTime = Carbon::now()->format('Y-m-d h:m:s');
                    if (!empty($userdocument->exp_date) && $userdocument->exp_date != '(NULL)' && $userdocument->exp_date < $currentTime) {
                        $response[$i]['status'] = 3;
                    } else {
                        $response[$i]['status'] = ($userdocument->is_approved) ?? 0;
                    }

                    $response[$i]['is_upload'] = 1;
                } else {

                    $response[$i]['id'] = $document->id;
                    $response[$i]['document_name'] = ucfirst($document->document_name);
                    $response[$i]['document_type'] = $doctype;
                    $response[$i]['data_type'] = $document->document_type;
                    $response[$i]['is_optional'] = $document->is_optional;
                    $response[$i]['is_expiry'] = $document->exp_date;
                    $response[$i]['max_characters_limit'] = $document->max_characters_limit;
                    $response[$i]['document_data'] = '';
                    $response[$i]['exp_date'] = '';
                    $response[$i]['status'] = 0;
                    $response[$i]['is_upload'] = 0;

                }
                $i++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "Joey Document");
    }


    /**
     * fucntion for joey get document
     *
     */
    public function getJoeyDocument(Request $request)
    {

        DB::beginTransaction();
        try {

            $userdocument = JoeyDocument::where('joey_id', auth()->user()->id)->get();

            $currentTime = Carbon::now()->format('Y-m-d h:m:s');
            $response = [
                [
                    'id' => null,
                    'document_type' => 'driver_permit',
                    'document_name' => 'driver permit',
                    'document_data' => '',
                    'exp_date' => 0,
                    'is_optional' => 0,
                    'status' => 0,
                    'is_upload' => 0
                ],
                [
                    'id' => null,
                    'document_type' => 'driver_license',
                    'document_name' => 'driver license',
                    'document_data' => '',
                    'exp_date' => 0,
                    'is_optional' => 0,
                    'status' => 0,
                    'is_upload' => 0
                ],
                [
                    'id' => null,
                    'document_type' => 'study_permit',
                    'document_name' => 'study permit',
                    'document_data' => '',
                    'exp_date' => 0,
                    'is_optional' => 0,
                    'status' => 0,
                    'is_upload' => 0
                ],
                [
                    'id' => null,
                    'document_type' => 'vehicle_insurance',
                    'document_name' => 'vehicle insurance',
                    'document_data' => '',
                    'exp_date' => 0,
                    'is_optional' => 0,
                    'status' => 0,
                    'is_upload' => 0
                ],
                [
                    'id' => null,
                    'document_type' => 'additional_document_1',
                    'document_name' => 'additional document 1',
                    'document_data' => '',
                    'exp_date' => 0,
                    'is_optional' => 1,
                    'status' => 0,
                    'is_upload' => 0
                ],
                [
                    'id' => null,
                    'document_type' => 'additional_document_2',
                    'document_name' => 'additional document 2',
                    'document_data' => '',
                    'exp_date' => 0,
                    'is_optional' => 1,
                    'status' => 0,
                    'is_upload' => 0
                ],
                [
                    'id' => null,
                    'document_type' => 'additional_document_3',
                    'document_name' => 'additional document 3',
                    'document_data' => '',
                    'exp_date' => 0,
                    'is_optional' => 1,
                    'status' => 0,
                    'is_upload' => 0
                ],
                [
                    'id' => null,
                    'document_type' => 'sin',
                    'document_name' => 'sin',
                    'document_data' => '',
                    'exp_date' => 0,
                    'is_optional' => 1,
                    'status' => 0,
                    'is_upload' => 0
                ]
            ];

            //$response =  JoeyDocumentResource::collection($userdocument);
            foreach ($userdocument as $document) {
                if ($document->document_type == 'driver_permit') {


                    $response[0]['id'] = $document->id;
                    $response[0]['document_type'] = $document->document_type;
                    $response[0]['document_data'] = $document->document_data;


                    $response[0]['exp_date'] = ($document->exp_date) ? $document->exp_date : 0;

                    if ($document->exp_date < $currentTime) {
                        $response[0]['status'] = 3;
                    } else {
                        $response[0]['status'] = $document->is_approved;
                    }


                    $response[0]['is_upload'] = 1;
                }
                if ($document->document_type == 'driver_license') {
                    $response[1]['id'] = $document->id;
                    $response[1]['document_type'] = $document->document_type;
                    $response[1]['document_data'] = $document->document_data;
                    $response[1]['exp_date'] = ($document->exp_date) ? $document->exp_date : 0;
                    if ($document->exp_date < $currentTime) {
                        $response[1]['status'] = 3;
                    } else {
                        $response[1]['status'] = $document->is_approved;
                    }
                    $response[1]['is_upload'] = 1;
                }
                if ($document->document_type == 'study_permit') {
                    $response[2]['id'] = $document->id;
                    $response[2]['document_type'] = $document->document_type;
                    $response[2]['document_data'] = $document->document_data;

                    $response[2]['exp_date'] = ($document->exp_date) ? $document->exp_date : 0;


                    if ($document->exp_date < $currentTime) {

                        $response[2]['status'] = 3;
                    } else {
                        $response[2]['status'] = $document->is_approved;
                    }
                    $response[2]['is_upload'] = 1;
                }
                if ($document->document_type == 'vehicle_insurance') {
                    $response[3]['id'] = $document->id;
                    $response[3]['document_type'] = $document->document_type;
                    $response[3]['document_data'] = $document->document_data;
                    $response[3]['exp_date'] = ($document->exp_date) ? $document->exp_date : 0;
                    if ($document->exp_date < $currentTime) {
                        $response[3]['status'] = 3;
                    } else {
                        $response[3]['status'] = $document->is_approved;
                    }
                    $response[3]['is_upload'] = 1;
                }
                if ($document->document_type == 'additional_document_1') {
                    $response[4]['id'] = $document->id;
                    $response[4]['document_type'] = $document->document_type;
                    $response[4]['document_data'] = $document->document_data;
                    $response[4]['exp_date'] = ($document->exp_date) ? $document->exp_date : 0;
                    if ($document->exp_date < $currentTime) {
                        $response[4]['status'] = 3;
                    } else {
                        $response[4]['status'] = $document->is_approved;
                    }
                    $response[4]['is_upload'] = 1;
                }
                if ($document->document_type == 'additional_document_2') {
                    $response[5]['id'] = $document->id;
                    $response[5]['document_type'] = $document->document_type;
                    $response[5]['document_data'] = $document->document_data;
                    $response[5]['exp_date'] = ($document->exp_date) ? $document->exp_date : 0;
                    if ($document->exp_date < $currentTime) {
                        $response[5]['status'] = 3;
                    } else {
                        $response[5]['status'] = $document->is_approved;
                    }
                    $response[5]['is_upload'] = 1;
                }
                if ($document->document_type == 'additional_document_3') {
                    $response[6]['id'] = $document->id;
                    $response[6]['document_type'] = $document->document_type;
                    $response[6]['document_data'] = $document->document_data;
                    $response[6]['exp_date'] = ($document->exp_date) ? $document->exp_date : 0;
                    if ($document->exp_date < $currentTime) {
                        $response[6]['status'] = 3;
                    } else {
                        $response[6]['status'] = $document->is_approved;
                    }
                    $response[6]['is_upload'] = 1;
                }
                if ($document->document_type == 'sin') {
                    $response[7]['id'] = $document->id;
                    $response[7]['document_type'] = $document->document_type;
                    $response[7]['document_data'] = $document->document_data;
                    $response[7]['exp_date'] = ($document->exp_date) ? $document->exp_date : 0;
                    if ($document->exp_date < $currentTime) {
                        $response[7]['status'] = 3;
                    } else {
                        $response[7]['status'] = $document->is_approved;
                    }
                    $response[7]['is_upload'] = 1;
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "Joey Document");
    }

    /**
     * About us
     *
     */
    public function aboutUs(Request $request)
    {


        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);
            $response = new AboutUsResource($joey);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "About US");
    }


    /**
     * terms and Condition
     *
     */
    public function termsCondition(Request $request)
    {

        $joey = $this->userRepository->find(auth()->user()->id);
        DB::beginTransaction();
        try {


            $response = new TermsConditionResource($joey);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "Terms and Condition");
    }


    /**
     * Privacy Policy
     *
     */
    public function privacyPolicy(Request $request)
    {

        $joey = $this->userRepository->find(auth()->user()->id);
        DB::beginTransaction();
        try {
            $response = new PrivacyPolicyResource($joey);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "Privacy and Policy");
    }

    // get joey agreement (new work)

    public function getlatestagreement(Request $request)
    {

        $joey = $this->userRepository->find(auth()->user()->id);
        DB::beginTransaction();
        try {

            $agreements = Agreement::where('target', 'joeys')->orderBy('effective_at', 'DESC')->first();
            // print_r($agreements->copy);die;
            $response = new GetJoeyAgreementResource($agreements);
            // $response['content']=$agreements->copy;
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "Agreement");
    }

    // user agreement check and save (new work)

    public function saveAgreement(AgreementRequest $request)
    {

        $data = $request->all();
        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);
            if (empty($joey)) {
                return RestAPI::response('Joey not found', false);
            }
            if ($data['email'] != auth()->user()->email) {
                return RestAPI::response('Please provide the email from which you are signed in / Incorrect email', false);
            }
            $agreements = Agreement::where('target', 'joeys')->orderBy('effective_at', 'DESC')->first();
            $agreements_joy = AgreementUser::where('user_type', 'joeys')->where('agreement_id', $agreements->id)->where('user_id', auth()->user()->id)->first();
            // print_r($agreements);die;

            if (empty($agreements_joy)) { //empty ,insert data
                $datag = array(
                    'agreement_id' => $agreements->id,
                    'user_id' => auth()->user()->id,
                    'user_type' => 'joeys',
                    'signed_at' => date("Y-m-d H:i:s"),
                );
                AgreementUser::create($datag);
            } else { //not empty ,update data

                if ($agreements_joy->signed_at == null) {
                    $agreements_joy->signed_at = date("Y-m-d H:i:s");

                    $agreements_joy->save();
                }

            }

            $joey->is_enabled = 1;
            $joey->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }

        return RestAPI::response(new \stdClass(), true, 'Joey agreement accepted');
    }


    /**
     * Get Customer Notification List
     *
     */
    public function getNotifications(Request $request)
    {
        DB::beginTransaction();

        try {
            $results = $this->notificationRepository->getnotification(auth()->user()->id, $request->get('limit'));

            $notifications = NotificationResource::collection($results);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::setPagination($results)->response($results->items(), true, 'Listing all Notification.');
    }


// BELOW fUNCTION FOR IMAGE UPLOAD IN ASSETS
    public function upload($base64Data) {
        //   $request = new Image_JsonRequest();
        $data = ['image' => $base64Data];
        $response = $this->sendData('POST', '/',  $data );
        if(!isset($response->url)) {
            return null;

        }
        return $response->url;
    }

    public function upload2($base64Data) {
        //   $request = new Image_JsonRequest();
        $data = ['image' => $base64Data];
        $response = $this->sendData2('POST', '/',  $data );
        if(!isset($response->url)) {
            return null;

        }
        return $response->url;
    }

    public function sendData2($method, $uri, $data = [])
    {
        $host = 'ap2uploads.joeyco.com';

        $json_data = json_encode($data);

        $headers = [
            'Accept-Encoding: utf-8',
            'Accept: application/json; charset=UTF-8',
            'Content-Type: application/json; charset=UTF-8',
            'User-Agent: JoeyCo',
            'Host: ' . $host,
        ];

        if (!empty($json_data)) {

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

        curl_close($ch);

        if (empty($error)) {

            $this->response = explode("\n", $this->originalResponse);

            $code = explode(' ', $this->response[0]);
            $code = $code[1];

            $this->response = $this->response[count($this->response) - 1];
            $this->response = json_decode($this->response);

            if (json_last_error() != JSON_ERROR_NONE) {

                $this->response = (object)[
                    'copyright' => 'Copyright © ' . date('Y') . ' JoeyCo Inc. All rights reserved.',
                    'http' => (object)[
                        'code' => 500,
                        'message' => json_last_error_msg(),// \JoeyCo\Http\Code::get(500),
                    ],
                    'response' => new \stdClass()
                ];
            }
        }

        return $this->response;
    }

    public function sendData($method, $uri, $data = [])
    {


        $host = 'assets.joeyco.com';
        //  $host = Config::get('application.api_host');
        // $host ='localhost:8300';

        $json_data = json_encode($data);
        //dd( $data['image']->getClientOriginalName());
        //dd($json_data);
        // $this->reset();


        // if (json_last_error() != JSON_ERROR_NONE) {
        //     throw new \Exception('Bad Request', 400);
        // }

        // $locale = \JoeyCo\Locale::getInstance();

        $headers = [
            'Accept-Encoding: utf-8',
            'Accept: application/json; charset=UTF-8',
            'Content-Type: application/json; charset=UTF-8',
            // 'Accept-Language: ' . $locale->getLangCode(),
            'User-Agent: JoeyCo',
            'Host: ' . $host,
        ];
        // dd($json_data);

        // if (!empty($data) && $method !== 'GET') {

        if (!empty($json_data)) {

            $headers[] = 'Content-Length: ' . strlen($json_data);
            // dd($headers);
        }

        // if (in_array($host, ['api.nightly.joeyco.com', 'api.staging.joeyco.com'])) {

        //     $headers[] = 'Authorization: Basic ' . base64_encode('api:api1243');
        // }

        // $this->signRequest($method, $uri, $headers);
        $url = 'https://' . $host . $uri;
        //   $url = $host . $uri;


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


        //dd([$this->originalResponse,$error]);
        curl_close($ch);

        if (empty($error)) {


            $this->response = explode("\n", $this->originalResponse);

            $code = explode(' ', $this->response[0]);
            $code = $code[1];

            $this->response = $this->response[count($this->response) - 1];
            $this->response = json_decode($this->response);

            if (json_last_error() != JSON_ERROR_NONE) {

                $this->response = (object)[
                    'copyright' => 'Copyright © ' . date('Y') . ' JoeyCo Inc. All rights reserved.',
                    'http' => (object)[
                        'code' => 500,
                        'message' => json_last_error_msg(),//\JoeyCo\Http\Code::get(500),
                    ],
                    'response' => new \stdClass()
                ];
            }
        }

        return $this->response;
    }


    /**
     * function for joey Document Type upload
     *
     */

    public function joeyDocumentTypes(Request $request)
    {


//        $requestData =$request->all();
        $datenow = date('Y-m-d');
        $todayDate = date('Y-m-d', strtotime($datenow . ' +1 day'));

        // 'document_exp_date' => 'nullable|date_format:Y-m-d|after_or_equal:'.$todayDate,


        $requestData = $request->validate([
            'document_id' => '',
            'document' => 'mimes:jpg,jpeg,png|max:5120',
            'document_exp_date' => 'date_format:Y-m-d',
            'document_type' => '',
            'document_text' => '',

        ]);

        $joey = $this->userRepository->find(auth()->user()->id);
        DB::beginTransaction();
        try {


            if (empty($requestData)) {
                return RestAPI::response('You are required to upload atleast one document', false);
            }


            //  $documentlist=JoeyDocument::whereNull('deleted_at')->get();

            //checking file is present or not

            if (!empty($requestData['document'])) {
                if (DocumentType::where('id', '=', $requestData['document_id'])->exists()) {
                    $document = DocumentType::where('id', '=', $requestData['document_id'])->first();

                    $checkJoeyDocument = JoeyDocument::where('joey_id', $joey->id)->where('document_type', $document->document_name)->first();
                    if (!empty($checkJoeyDocument)) {

                        JoeyDocument::where('joey_id', $joey->id)->where('document_type', $document->document_name)->update(['deleted_at' => Carbon::now()->format('Y-m-d h:m:s')]);
                    }

                    if ($document->exp_date == 1) {
                        if (!empty($requestData['document_exp_date'])) {
                            if ($request->hasfile('document')) {
                                //move | upload file on server
//                                $slug = Str::slug($joey->first_name, '-');
//                                $file = $request->file('document');
//                                $extension = $file->getClientOriginalExtension(); // getting image extension
//                                $filename = $slug . '-' . rand() . '-' . time() . '.' . $extension;
//                                $file->move(backendJoeyDocumentFile(), $filename);
//                                $imageUrl = url(backendJoeyDocumentFile() . $filename);
                                $path = '';
                                $imagedata = file_get_contents($request->file('document'));
                                $base64 = base64_encode($imagedata);
                                if (!empty($base64)) {
                                    $path = $this->upload2($base64);
                                    if (!isset($path)) {
                                        return RestAPI::response('File cannot be uploaded due to server error!', false);
                                    }
                                }
                                JoeyDocument::create(['joey_id' => $joey->id, 'document_data' => $path, 'exp_date' => $request['document_exp_date'], 'document_type' => $document->document_name, 'document_type_id' => $document->id]);

                            } else {
                                return RestAPI::response('Please provide document to be uploaded', false);
                            }
                        } else {
                            return RestAPI::response('Document expiry date is required', false);
                        }

                    } else {

                        $checkJoeyDocument = JoeyDocument::where('joey_id', $joey->id)->where('document_type', $document->document_name)->first();
                        if (!empty($checkJoeyDocument)) {
                            JoeyDocument::where('joey_id', $joey->id)->where('document_type', $document->document_name)->update(['deleted_at' => Carbon::now()->format('Y-m-d h:m:s')]);
                        }

                        if ($request->hasfile('document')) {
                            //move | upload file on server
//                            $slug = Str::slug($joey->first_name, '-');
//                            $file = $request->file('document');
//                            $extension = $file->getClientOriginalExtension(); // getting image extension
//                            $filename = $slug . '-' . rand() . '-' . time() . '.' . $extension;
//                            $file->move(backendJoeyDocumentFile(), $filename);
//                            $imageUrl = url(backendJoeyDocumentFile() . $filename);

                            $path = '';
                            $imagedata = file_get_contents($request->file('document'));
                            $base64 = base64_encode($imagedata);
                            if (!empty($base64)) {
                                $path = $this->upload2($base64);
                                if (!isset($path)) {
                                    return RestAPI::response('File cannot be uploaded due to server error!', false);
                                }
                            }

                            $docdata = [
                                'joey_id' => $joey->id,
                                'document_data' => $path,
                                'document_type' => $document->document_name,
                                'document_type_id' => $document->id
                            ];

                            if (!empty($request['document_exp_date'])) {
                                $docdata['exp_date'] = $request['document_exp_date'];
                            }
                            JoeyDocument::create($docdata);

                        } else {
                            return RestAPI::response('Please provide document to be uploaded', false);
                        }
                    }

                } else {
                    return RestAPI::response('This document type does not exists', false);
                }
            } else {
                $document = DocumentType::where('id', '=', $requestData['document_id'])->first();
                if (!empty($requestData['document_text'])) {

                    $checkJoeyDocument = JoeyDocument::where('joey_id', $joey->id)->where('document_type', $document->document_name)->first();
                    if (!empty($checkJoeyDocument)) {
                        JoeyDocument::where('joey_id', $joey->id)->where('document_type', $document->document_name)->update(['deleted_at' => Carbon::now()->format('Y-m-d h:m:s')]);
                    }

                    $docdata = [
                        'joey_id' => $joey->id,
                        'document_data' => $requestData['document_text'],
                        'document_type' => $document->document_name,
                        'document_type_id' => $document->id
                    ];
                    if (!empty($request['document_exp_date'])) {
                        $docdata['exp_date'] = $request['document_exp_date'];
                    }

                    JoeyDocument::create($docdata);

                } else {
                    return RestAPI::response('Please provide with SIN Number', false);
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response(new \stdClass(), true, "Joey Document Updated Successfully");
    }


}
