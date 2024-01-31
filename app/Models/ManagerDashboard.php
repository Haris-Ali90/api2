<?php

namespace App\Models;


use App\Models\Interfaces\ManagerDashboardInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;

class ManagerDashboard extends Authenticatable implements ManagerDashboardInterface,JWTSubject
{

    const ROLE_ADMIN                = 2;
    const ROLE_USER                 = 0;
    const ROLE_SERVICE_PROVIDER     = 1;
    /**
     * Table name.
     *
     * @var array
     */
    public $table = 'dashboard_users';

    /**
     * The attributes that are guarded.
     *
     * @var array
     */
    protected $guarded = [
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


    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [];

    public function addDevice($deviceToken, $deviceType, $authToken)
    {

//        UserDevice::whereDeviceToken($deviceToken)->update(['deleted_at' => now()]);

        return $this->devices()->create([
            'user_type'    => 'manager',
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

    public function role()
    {
//        dd($this);
        return $this->belongsTo(Roles::class, 'role_type','id');
    }

    public function removeDevice($authToken)
    {
        $record = $this->devices()->whereAuthToken($authToken)->limit(1)->first();

        if ($record) {
            $record->update(['deleted_at' => now()]);
        }
    }
    public function devices()
    {
        return $this->hasMany(UserDevice::class,'user_id', 'id');
    }

    public function sendPasswordResetEmailToManager($email, $full_name, $token, $role_id)
    {
        $bg_img = 'background-image:url(' . url("/images/joeyco_icon_water.png") . ');';
        $bg_img = trim($bg_img);
        $style = "font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif,'Apple Color Emoji','Segoe UI Emoji','Segoe UI Symbol';color: black !important;";
        $style1 = "font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif,'Apple Color Emoji','Segoe UI Emoji','Segoe UI Symbol';";
        $body = '<div class="row" style=" width: 32%;margin: 0 AUTO;">
                <div style="text-align: center;
    background-color: lightgrey;"><img src="' . url('/') . '/images/abc.png" alt="Web Builder" class="img-responsive" style="margin:0 auto; width:150px;" /></div>
                <div style="' . $bg_img . '
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;">
                  <h1 style="'.$style.'">Hi, ' . $full_name . '!</h1>

                <p style="'.$style.'">You are receiving this email because we received a password reset request for your account.</p>
                <div style="text-align: center;'.$style.'"><a class="btn btn-link" href="https://dashboard.joeyco.com/password/reset/'.$email.'/'.$token.'/'.$role_id.'" class="btn btn-primary" ><button style="background-color: #E36D28;border: 0px;border-radius: 6px;">Reset Password</button></a></div>
                 <p style="'.$style.'">If you did not request a password reset, no further action is required.</p>
                <br/>
                <p style="'.$style.'"> If you’re having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser:
                <a style="word-break: break-all; '.$style1.'" href="https://dashboard.joeyco.com/password/reset/'.$email.'/'.$token.'/'.$role_id.'" >https://dashboard.joeyco.com/password/reset/'.$email.'/'.$token.'/'.$role_id.'"</a></p>
                <br/>
                <br/>

                </div>
                 <div style="background-color: lightgrey;padding: 5px;">
        <p style="padding-bottom: -1px;margin: 0px;margin-left: 20px;'.$style.'">JoeyCo Inc.</p>
        <p style="margin-top: 0x;margin: 0px;margin-left: 20px;'.$style.'">16 Four Seasons Pl., Etobicoke, ON M9B 6E5</p>
        <p style="margin: 0px;margin-left: 20px;'.$style.'">+1 (855) 556-3926 · support@joeyco.com </p>
    </div>
                </div>
                ';
        $subject = "Reset Password Link";
        $email = base64_decode($email);
        Mail::send(array(), array(), function ($m) use ($email, $subject, $body) {
            $m->to($email)
                ->subject($subject)
                ->from('noreply@joeyco.com')
                ->setBody($body, 'text/html');
        });
    }
}
