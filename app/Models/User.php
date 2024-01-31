<?php

namespace App\Models;

use App\Mail\WelcomeMail;
use App\Models\Interfaces\UserInterface;
use App\Notifications\Backend\AdminResetPasswordNotification;
use Carbon\Carbon;
//use DB;
use Illuminate\Support\Facades\DB;
use http\Url;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Facades\Mail;

class User extends Authenticatable implements UserInterface, JWTSubject
{


    public $table = 'joeys';

    use Notifiable;
//
//    public const ROLE_ID = '2';
//    public const ROLE_TYPE = '1';
//    public const ACTIVE = 1;


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','plan_id','first_name','role_type','last_name','nickname','user_type', 'display_name','email',
        'password','address','suite','buzzer','city_id',
        'state_id','country_id','postal_code',
        'phone','image_path','image',
        'about_yourself','about','preferred_zone',
        'hear_from','is_newsletter','is_enabled','vehicle_id','comdata_emp_num',
        'comdata_cc_num','comdata_cc_num_2','pwd_reset_token',
        'pwd_reset_token_expiry','is_busy','current_location_id',
        'email_verify_token','is_online','balance','location_id','hst_number',
        'rbc_deposit_number','cash_on_hand','timezone',
        'work_type','contact_time','interview_time',
        'has_bag','is_backcheck','on_duty','preferred_zone_id','shift_amount_due',
        'is_on_shift','api_key','is_itinerary',
        'hub_id','vendor_id','category_id',
        'merchant_id','cirminal_status','driving_licence_status','work_permit_status','driving_licence_picture',
        'driving_licence_exp_date','work_permit_image','work_permit_exp_date',
        'document_status','quiz_status','profile_status',
        'is_background_check','unit_number','hub_joey_type','has_route','shift_store_type',
        'can_create_order','unit_number','is_tax_applied','shift_store_type'

    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
    ];


    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }



    public function isEnabled() {
        // return $this->isActive() && $this->isLatestAgreementSigned() && !$this->isDeleted() && !empty($this->attributes['plan_id']);

        return $this->isActive() && !$this->isDeleted() && !empty($this->attributes['plan_id']);
    }

    public function isActive() {
        return $this->attributes['is_enabled'] == 1;
    }

    /*    public function isLatestAgreementSigned() {

            return $this->getAgreementSignature()->isAgreementSigned();
        }*/


    public function isDeleted() {
        return $this->attributes['deleted_at'] !== null;
    }



    /*   public function getAgreementSignature() {

           if ($this->agreementSignature === null) {
               $this->agreementSignature = AgreementUser::getSignature($this);
           }

           return $this->agreementSignature;
       }*/






    public function getApiKeys() {

        return ApiAccount::findByPublicKey($this->attributes['api_key']);
    }





    public function addDevice($deviceToken, $deviceType, $authToken)
    {

        UserDevice::whereUserId($this->id)->update(['is_deleted_at' => 1]);

        return $this->devices()->create([
            'user_type'    => 'joey',
            'device_token' => $deviceToken,
            'device_type'  => $deviceType,
            'auth_token'   => $authToken,
        ]);
    }

    public function updateDevice($authToken, $deviceToken, $deviceType, $userType)
    {
        $record = $this->devices()->whereAuthToken($authToken)->limit(1)->first();

        if ($record) {
            $record->user_type    = $userType;
            $record->device_token = $deviceToken;
            $record->device_type  = $deviceType;
            $record->save();
        }
    }


    public function validateUserActiveCriteria() : bool
    {
//        if((int)$this->attributes['is_enabled'] === 0){
//            throw new \App\Exceptions\UserNotAllowedToLogin('Account not activated yet');
//        }else
            if (!empty($this->attributes['email_verify_token'])){
            throw new \App\Exceptions\UserNotAllowedToLogin('Account not activated yet');
        }
        return true;

    }


    public function removeDevice($authToken)
    {
        $record = $this->devices()->whereAuthToken($authToken)->limit(1)->first();

        if ($record) {
            $record->update(['is_deleted_at' => 1]);
        }
    }
    public function devices()
    {
        return $this->hasMany(UserDevice::class);
    }
    /**
     * Send Reset Password Email
     */
    public function sendPasswordResetEmail($email,$token)
    {

        $this->notify(new AdminResetPasswordNotification($email,$token));
    }
    public function getPermissions()
    {
     //   return $this->Permissions->pluck('route_name')->toArray();
        return '';
    }

    public function getDashboardCardsPermissionsArray()
    {
        $data='';
        //$data = $this->Role->pluck('dashbaord_cards_rights');

    }
    public function Role()
    {

        return $this->belongsTo(Roles::class, 'role_type','id');
    }
    public function Vehicle()
    {

        return $this->belongsTo(JoeyVehiclesDetail::class, 'vehicle_id','id');
    }
    ### for task id
    public  function joeyRoute(){
        return $this->belongsTo(JoeyRoutes::class,'id','joey_id');
    }
    ### for isbusy Joey
    public function Busy()
    {
        return $this->belongsTo(Sprint::class, 'id','joey_id');
    }

    ### for joeyDeposit
    public function Deposit()
    {
        return $this->belongsTo(JoeyDeposit::class, 'id','joey_id');
    }


    ### for joey preffered zomne
    public function PrefferedZone()
    {
        return $this->belongsTo(Zones::class, 'preferred_zone','id');
    }

    #### joey locations //2022-05-09
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    ### for joey sprints
    public function joeySprints()
    {
        return $this->hasMany(Sprint::class, 'joey_id','id');
    }
    public function joeyDocumentsApproved()
    {
        return $this->hasMany(JoeyDocument::class, 'joey_id','id')->where('is_approved',1);
    }
    public function joeyAttemptedQuiz()
    {
        return $this->hasMany(JoeyQuiz::class, 'joey_id','id')->where('is_passed',1);
    }

     // nwew work

     public function getPlan()
     {
         return $this->belongsTo(JoeyPlan::class, 'plan_id','id');
     }


	public function joeyFlagLoginValidation($user, $request)
    {
        $timeZone = ($request->timezone == null) ? 'America/Toronto' : $request->timezone;
        $time = convertTimeZone(date('Y-m-d H:i:s'), 'UTC', $timeZone, 'Y-m-d');
        $check_block_suspended_joey=[];
        $check_block_suspended_joey=JoeyFlagLoginValidations::where('joey_id',$user->id)->orderBy('created_at','DESC')->first();

        if(!empty($check_block_suspended_joey)){


            if ($check_block_suspended_joey->window_end == null) {
                if($check_block_suspended_joey->window_start <= $time){
                    \Illuminate\Support\Facades\DB::beginTransaction();

                //$user->removeDevice($user['_token']);
                $user->removeDevice(jwt()->fromUser($user));
                    auth()->guard('api')->logout();

                    DB::commit();
                    return ['status' => true, 'message' => 'You are blocked'];
                }
            }
            if($check_block_suspended_joey->window_start <= $time){
                if($check_block_suspended_joey->window_end >= $time){
                    DB::beginTransaction();

                    //$user->removeDevice($user['_token']);
                    auth()->guard('api')->logout();

                    DB::commit();
                    $date1 = new \DateTime($time);
                    $date2 = new \DateTime($check_block_suspended_joey->window_end);
                    $interval = $date1->diff($date2);
                    $days=($interval->days)+1;
                    $dayCondition = ($days == 1) ? 'day' : 'days';
                    return ['status' => true, 'message' => "You are Suspended.You can login after $days $dayCondition"];
                }
            }
        }
    }

}
