<?php

namespace App\Models;

use App\Mail\WelcomeMail;
use App\Models\Interfaces\JoeyInterface;
use App\Models\Interfaces\UserInterface;
use App\Notifications\Backend\AdminResetPasswordNotification;
use Carbon\Carbon;
use DB;
use http\Url;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Facades\Mail;

class Joey extends Authenticatable implements JoeyInterface, JWTSubject
{

    public $table = 'joeys';

    use SoftDeletes,Notifiable;
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
        'id','plan_id','first_name','last_name','nickname', 'display_name','email',
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
        'can_create_order'

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

    public function isDeleted() {
        return $this->attributes['deleted_at'] !== null;
    }



    public function getApiKeys() {

        return ApiAccount::findByPublicKey($this->attributes['api_key']);
    }


    //Making Relation For Complete Route Payout Calculation
    /**
     * Get joey brooker
     */
    public function Brooker()
    {
        return $this->belongsToMany(Brookers::class, 'brooker_joey', 'joey_id', 'brooker_id');
    }

    public function ManagerJoeyBrooker(){
        return $this->belongsTo(BrookerJoey::class,'joey_id','id');
    }
    /**
     * Get joey plan.
     */
    public function plan()
    {
        return $this->belongsTo(joeyPlan::class,'plan_id', 'id');
    }

    /**
     * Get the user's full name.
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }


}
