<?php

namespace App\Models;
use App\Models\Interfaces\VendorInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendors extends Model implements VendorInterface
{

    public $table = 'vendors';

    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'group_id',
        'package_id',
        'first_name',
        'last_name',
        'description',
        'email',
        'password',
        'password_expiry_token',
        'admin_password',
        'phone',
        'website',
        'name',
        'location_id',
        'contact_id',
        'business_phone',
        'business_suite',
        'business_address',
        'business_city',
        'business_state',
        'business_country',
        'business_postal_code',
        'latitude',
        'longitude',
        'order_limit',
        'monthly_order_limit',
        'shipping_policy',
        'return_policy',
        'contactus',
        'logo',
        'banner',
        'logo_old',
        'video',
        'url',
        'prep_time',
        'vehicle_id',
        'default_merchant_delivery',
        'is_enabled',
        'is_display',
        'is_registered',
        'is_online',
        'is_store_open',
        'is_newsletter',
        'is_customer_email_receipt',
        'pwd_reset_token',
        'pwd_reset_token_expiry',
        'approved_at',
        'tutorial_at',
        'deleted_at',
        'created_at',
        'updated_at',
        'email_verify_token',
        'payment_method',
        'api_key',
        'is_mediator',
        'sms_printer_number',
        'timezone',
        'is_ghost',
        'searchables',
        'tags',
        'printer_fee',
        'salesforce_id',
        'password_expires_at',
        'code',
        'code_updated',
        'forgot_code',
        'joey_order_count',
        'score',
        'quiz_limit',
        'order_end_time',
        'order_start_time',


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


         ### faqs with vendor
    public  function Faqs(){
        return $this->hasMany(Faqs::class,'vendor_id','id');
    }


}
