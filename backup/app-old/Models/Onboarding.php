<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use App\Models\Interfaces\OnboardingInterface;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Onboarding extends Authenticatable implements OnboardingInterface ,JWTSubject
{
  
    //
    public $table = 'onboarding_users';

    use SoftDeletes;

    protected $fillable = [
        'id','full_name','user_name','email', 'address','password','city','country','role_id','status','phone','device_token','device_type','push_status','profile_picture','is_verify','verify_token','rights','permissions',
        'userType','first_name','last_name','education_type','emergency_contact','guardian_name','guardian_phone','notification_status','mobile_no','location_country','location_latitude','location_longitude','bio','social_media_id',
        'dob','is_verified','last_login','verify_token','role_type'
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

    public function message()
    {
        return $this->morphOne(Message::class, 'messageable');
    }
}
