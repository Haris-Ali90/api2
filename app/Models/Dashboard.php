<?php

namespace App\Models;


use App\Models\Roles;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Config;
use App\Review;
use Illuminate\Support\Facades\Mail;

class Dashboard extends Authenticatable
{
    protected $table = 'dashboard_users';
    use SoftDeletes;
//
	 public static $likeUserId = 0;
     const ROLE_ADMIN                = 3;
     const ROLE_ROUTING                = 3;
     const ROLE_USER                 = 0;
     const ROLE_SERVICE_PROVIDER     = 1;
//
//    const STATUS_INACTIVE           = 0;
//    const STATUS_ACTIVE             = 1;
//
//    const DEVICE_TYPE_IOS           = "ios";
//    const DEVICE_TYPE_ANDROID       = "android";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'is_email','is_scan','id','full_name','user_name','email', 'address','password','city','country','role_id','status','phone','device_token','device_type','push_status','profile_picture','is_verify','verify_token','rights','permissions','micro_sub_admin','hub_id','role_type'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
//    protected $hidden = [
//         'getCityName'
//    ];

	protected $casts = ['role_id' => 'integer','status' => 'integer','push_status' => 'integer','is_verify' => 'integer'];

    protected $visible = ['id','full_name','user_name','email','city','country','role_id','phone','device_token','device_type','profile_picture','profile_image','address','role_type'];

    protected $appends = ['profile_image'];

    public function getProfileImageAttribute() {

        $profileImage = asset(Config::get('constants.front.dir.getprofilePicPath') . ($this->profile_picture ?: Config::get('constants.front.default.profilePic')));
        return $profileImage;
    }
    public function getThumbImageAttribute() {

        $thumbImage = asset(Config::get('constants.front.dir.getthumbnailVideoPath') . ($this->thumbnail_image ?: Config::get('constants.front.default.profilePic')));
        return $thumbImage;
    }
    public function getPromoFileAttribute() {

        $promoFile = asset(Config::get('constants.front.dir.getpromoVideoPath') . ($this->promo_video ?: Config::get('constants.front.default.promoVideo')));


        return $promoFile;
    }


    public function getIsLikeAttribute(){
        if(self::$likeUserId) {
            $instance = $this->FavoriteDetails()->getQuery()->where('user_id',self::$likeUserId)->first();
            return ($instance) ? 1 : 0;
        }
        return 0;
    }

    public function scopeWithIsLike($query, $user_id=0){
        self::$likeUserId = $user_id;
        return $query;
    }

    public function DocumentImages()
    {
        return $this->hasMany('App\UserDocuments','user_id');
    }

    public function FavoriteDetails()
    {
        return $this->hasMany('App\Favorite','doctor_id');
    }

    public function qualifications()
    {
        return $this->hasMany('App\Qualification','user_id');
    }
//    public function qualifications1()
//    {
//        return $this->belongsToMany('App\Category', 'user_categories', 'user_id', 'category_id');
//    }

    public function role()
    {
        dd($this->id);
        return $this->belongsTo(\App\Models\Roles::class, 'role_type','id');
    }

    public function categories()
    {
//        return $this->hasMany('App\UserCategory','user_id');
        return $this->belongsToMany('App\Category', 'user_categories', 'user_id', 'category_id');
    }
    public function hospitals()
    {
        return $this->hasMany('App\Hospital','user_id');
    }

    public function hospital()
    {
        return $this->hasMany('App\Hospital','user_id');
    }

    public function usercategory()
    {
        return $this->hasMany('App\UserCategory','user_id');
    }

    public function isAdmin() {
        return (bool) (intval($this->attributes['role_id']) === self::ROLE_ADMIN);
    }


    public function getAverageRateAttribute() {

        $doctor_id  = isset($_REQUEST['doctor_id']) ? $_REQUEST['doctor_id'] : 0;
        $AverageRate = Review::where('doctor_id',$doctor_id)->avg('rate');
        return $AverageRate;
    }
    public function getTotalFollowersAttribute()
    {
        $doctor_id  = isset($_REQUEST['doctor_id']) ? $_REQUEST['doctor_id'] : 0;
        $totalfavoritecount = Favorite::where('doctor_id',$doctor_id)->count();
        return $totalfavoritecount;
    }
    public function deactivate()
    {
        $this->status  = 0;
        $this->save();
    }

    public function activate()
    {
        $this->status  = 1;
        $this->save();
    }

/*    public  function sendWelcomeEmail($randomid)
    {
        $email = $this->attributes['email'];
        $full_name = $this->attributes['full_name'];
        $body = "<h1>Hi, welcome ".$full_name."!</h1>
                <br/>
                <p>Your code is ".$randomid."</p>
                ";
        $subject = "Your 6 digit code for Authentication";

        Mail::send(array(), array(), function ($m) use($email, $subject,$body) {
            $m->to($email)
                ->subject($subject)
                 ->from(config('mail.from.address'))
                ->setBody($body, 'text/html');
        });
    }*/

    public function sendPasswordResetEmail($email,$full_name,$token,$role_id)
    {
        $bg_img = 'background-image:url('.url("/images/joeyco_icon_water.png").');';
        $bg_img = trim($bg_img);
        $body = '<div class="row" style=" width: 32%;margin: 0 AUTO;">
                <div style="text-align: center;"><img src="'.url('/').'/images/abc.png" alt="Web Builder" class="img-responsive" style="margin:0 auto; width:150px;" /></div>
                <div style="'.$bg_img.'
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;">
                  <h1>Hi, '.$full_name.'!</h1>

                <p>You are receiving this email because we received a password reset request for your account.</p>
                <div style="text-align: center;"><a class="btn btn-link" href='.route('password.reset', [$email,$token,$role_id]).' class="btn btn-primary" ><button style="background-color: #E36D28;border: 0px;border-radius: 6px;">Reset Password</button></a></div>
                 <p>If you did not request a password reset, no further action is required.</p>

                 <br/>
                 <p>Regards,</p>
                <p>JoeyCO Routing</p>
                </div>
                </div>
                ';
        $subject = "Reset Password Link";
        $email = base64_decode($email);
        Mail::send(array(), array(), function ($m) use($email, $subject,$body) {
            $m->to($email)
                ->subject($subject)
                ->from(config('mail.from.address'))
                ->setBody($body, 'text/html');
        });
    }


    public function sendSubAdminPasswordResetEmail($email,$full_name,$token,$role_id)
    {
        $bg_img = 'background-image:url('.url("/images/joeyco_icon_water.png").');';
        $bg_img = trim($bg_img);
        $body = '<div class="row" style=" width: 32%;margin: 0 AUTO;">
                <div style="text-align: center;"><img src="'.url('/').'/images/abc.png" alt="Web Builder" class="img-responsive" style="margin:0 auto; width:150px;" /></div>
                <div style="'.$bg_img.'
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;">
                  <h1>Hi, '.$full_name.'!</h1>

                <p>You are receiving this email because Joeyco Routing Admin has created your account for using Joeyco Routing, kindly reset your password and login to your account.</p>
                <div style="text-align: center;"><a class="btn btn-link" href='.route('password.reset', [$email,$token,$role_id]).' class="btn btn-primary" ><button style="background-color: #E36D28;border: 0px;border-radius: 6px;">Reset Password</button></a></div>
                 <p>If you did not request a for account, no further action is required.</p>

                 <br/>
                 <p>Regards,</p>
                <p>JoeyCO Routing</p>
                </div>
                </div>
                ';
        $subject = "Reset Password Link";
        $email = base64_decode($email);
        Mail::send(array(), array(), function ($m) use($email, $subject,$body) {
            $m->to($email)
                ->subject($subject)
                ->from(config('mail.from.address'))
                ->setBody($body, 'text/html');
        });
    }

    public  function sendWelcomeEmail($randomid)
    {
        $email = $this->attributes['email'];
        $full_name = $this->attributes['full_name'];
        $bg_img = 'background-image:url('.url("/images/joeyco_icon_water.png").');';
        $bg_img = trim($bg_img);
        $body = '<div class="row" style=" width: 32%;margin: 0 AUTO;">
                <div style="text-align: center;"><img src="'.url('/').'/images/abc.png" alt="Web Builder" class="img-responsive" style="margin:0 auto; width:150px;" /></div>
                <div style="'.$bg_img.'
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;">
                  <h1>Hi, '.$full_name.'!</h1>

                 <p>You are receiving this email because we received a Two-factor authentication request for your account.</p>
                <p>Your Two-factor authentication code is <span style="background-color: #E36D28;border: 0px;">'.$randomid.'</span></p>
                 <p>If you did not request a Two-factor authentication, no further action is required.</p>

                 <br/>
                 <p>Regards,</p>
                <p>JoeyCO Routing </p>
                </div>
                </div>
                ';
        $subject = "Your 6 digit code for Authentication";
        Mail::send(array(), array(), function ($m) use($email, $subject,$body) {
            $m->to($email)
                ->subject($subject)
                ->from(config('mail.from.address'))
                ->setBody($body, 'text/html');
        });
    }

}
