<?php

namespace App\Models;
use App\Models\JoeyRoute;
use App\Models\Interfaces\VendorInterface;
use App\Models\Sprint;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model implements VendorInterface
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
    public function location()
    {
        return $this->belongsTo(Location::class,'location_id','id');
    }

    // new work
    public function vendorPlanPackage()
    {
        return $this->belongsTo(VendorPlanPackage::class,'package_id','id');
    }
    // new work
    public  function vendorPackage(){
        return $this->hasOne(VendorPackage::class,'vendor_id','id')->orderBy('id','DESC');
    }
    // new work
    public  function zoneVendorRelationship(){
        return $this->hasOne(ZoneVendorRelationship::class,'vendor_id','id');
    }

    public function getVendorOrdersCount($date,$vendorId)
    {

//        $vendorId = JoeyRoute::join('joey_route_locations','joey_route_locations.route_id' ,'=', 'joey_routes.id')
//            ->whereNull('joey_route_locations.deleted_at')
//            ->whereNull('joey_routes.deleted_at')
//            ->where('joey_routes.mile_type',1)
//            ->where('joey_routes.route_completed', 0)
//            ->where('joey_routes.id', $routeId)
//            ->where('joey_routes.date', 'LIKE', $date.'%')
////                                ->groupBy('joey_route_locations.route_id')
//            ->pluck('joey_route_locations.task_id');
//
////        $vendorId = JoeyRouteLocations::where('route_id',$routeId)->pluck('task_id');
//
//        $changeDateFormate = date("Y-m-d", strtotime($date));
//
//        $routeOrderCount = \App\Sprint::whereIn('creator_id',$vendorId)
//            ->whereIn('status_id',[24,61,111,125])
//            ->whereNotIn('status_id',[36])
//            ->whereDate('created_at', 'LIKE', $changeDateFormate.'%')
//            ->whereNull('deleted_at')
//            ->distinct()
//            ->count();

        return Sprint::where('creator_id', $vendorId)
            ->whereIn('status_id',[24,61,111])
            ->whereNotIn('status_id', [36])
            ->whereNull('deleted_at')
//            ->whereDate('created_at', 'LIKE', $date.'%')
            ->count();
    }

    public static function getVendorDropoffOrdersCount($routeId, $status)
    {
        $dropoffCount = JoeyStorePickup::where('route_id', $routeId)->whereNull('deleted_at')->count();

//        $dropoffCount = Sprint::where('status_id',$status)
//            ->whereDate('created_at', 'LIKE', $date.'%')
//            ->whereNull('deleted_at')
//            ->whereIn('creator_id', $vendorIds)
//            ->whereNotIn('status_id', [36])
//            ->count();

        return $dropoffCount;
    }

}
