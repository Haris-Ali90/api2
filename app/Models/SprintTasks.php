<?php

namespace App\Models;
use App\Models\Interfaces\SprintInterface;

use App\Models\Interfaces\SprintTaskInterface;
use DB;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Notifications\Notifiable;


class SprintTasks extends Model implements SprintTaskInterface
{

    public $table = 'sprint__tasks';

    use SoftDeletes,Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','sprint_id','ordinal','type','due_time', 'eta_time','etc_time',
        'location_id','contact_id','payment_type','payment_amount','description',
        'pin','status_id','status_copy',
        'payment_service_charge','charge','active',
        'notify_by','is_notified','merchant_charge',
        'joey_pay','joey_tax_pay','joeyco_pay','weight_charge','weight_estimate',
        'confirm_pin','confirm_signature','confirm_image',
        'confirm_seal','staging_charge','cc_number_used',
        'resolve_time'
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
    // new work
    public function sprintTaskHistoryStatus($arr=[],$taskId)
    {
        $query=SprintTaskHistory::where('sprint__tasks_id',$taskId)->whereIn('status_id',$arr)->get();
        $return=0;
        if(count($query)>0){
            $return=1;
        }
        return $return;
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
        ### for sprint task contact
    public  function sprintContact(){
        return $this->belongsTo(SprintContact::class,'contact_id','id');
    }
    ### for confirmation with sprint task
    public  function sprintConfirmation(){
        return $this->hasMany(SprintConfirmation::class,'task_id','id');
    }

    ### for lcoation with sprint task
    public  function Location(){
        return $this->belongsTo(Location::class,'location_id','id');
    }

      ### for merchantids
      public  function merchantIds(){
        return $this->belongsTo(MerchantsIds::class,'id','task_id');
    }


    ### for sprints
    public  function sprintsSprints(){
        return $this->belongsTo(Sprint::class,'sprint_id','id');
    }

    public  function sprintsSprintsForRoute(){
        return $this->belongsTo(Sprint::class,'sprint_id','id')
            ->whereNotIn('sprint__sprints.status_id',[105,111,17,113,114,116,117,118,132,138,139,144,141,36,145]);
    }


    public function getDueTimeConvertedAttribute()
    {
        return ($this->due_time != null ) ?  $this->asDateTime($this->due_time)->timezone('America/Toronto'): 'data_not_set' ;
    }


    public function getEtaTimeConvertedAttribute()
    {
        return ($this->eta_time != null ) ?  $this->asDateTime($this->eta_time)->timezone('America/Toronto'): 'data_not_set' ;
    }


    public function getPickOne(){

        return $this->hasOne(self::class,'sprint_id','sprint_id')->where('type','pickup');
    }
    ### for tracking code
    public  function sprintTaskTrackinCodes(){
        return $this->belongsTo(TrackingCodes::class,'sprint_id','order_id');
    }
    ### for task history
    public  function sprintTaskHistory(){
        return $this->belongsTo(SprintTaskHistory::class,'id','sprint__tasks_id')->whereIn('status_id',[114,117,118,132,138,139,144])
            ->orderby('created_at', 'DESC');
    }
    ### for count against sprint task and sprint confirmatuon
    public function countForConfirmationAgaintTaskId(){

        return $this->hasMany(SprintConfirmation::class,'task_id','id')->where('confirmed',0);
    }
     // new work
    ### for task history for pickedup
    public  function sprintTaskHistoryforPickup(){
        return $this->belongsTo(SprintTaskHistory::class,'id','sprint__tasks_id')->whereIn('status_id',[15,28])
            ->orderby('created_at', 'DESC');
    }
    // new work
      ### for task history for at pickup
     public  function sprintTaskHistoryforAtPickup(){
        return $this->belongsTo(SprintTaskHistory::class,'id','sprint__tasks_id')->where('status_id',67)
            ->orderby('created_at', 'DESC');
    }

    public function vendorcontact()
    {
        return $this->belongsTo(Vendor::class,'contact_id','id');
    }

     // new work
     ### for task history for at dropoff
    public  function sprintTaskHistoryforAtDropOff(){
        return $this->hasOne(SprintTaskHistory::class,'sprint__tasks_id','id')->where('status_id',68)
            ->orderby('created_at', 'DESC');
    }
    /**
     * Get Route Sprint
     */
    public function Sprints()
    {
        return $this->belongsTo( Sprint::class,'sprint_id', 'id');
    }

    ### for manager merchantids
    public  function managerMerchantIds(){
        return $this->belongsTo(MerchantsIds::class,'id','task_id')->whereNotNull('merchantids.tracking_id');
    }

    ### for manager location with sprint task
    public  function managerLocation(){
        return $this->belongsTo(Location::class,'location_id','id');
    }

    ### for manager sprints
    public  function managerSprintsSprints(){
        return $this->belongsTo(Sprint::class,'sprint_id','id')->whereNull('sprint__sprints.deleted_at');
    }

    ### for manager sprint task contact
    public  function managerSprintContact(){
        return $this->belongsTo(SprintContact::class,'contact_id','id')/*->whereNull('sprint__contacts.deleted_at')*/;
    }

    public  function managerJoeyRouteLocation(){
        return $this->hasOne(JoeyRouteLocation::class,'task_id','id');
    }

    public  function ManagerSprintTaskMultipleHistory(){
        return $this->hasMany(SprintTaskHistory::class,'sprint__tasks_id','id')->whereNotIn('status_id',[36,38,17,61])->groupBy('status_id')->orderBy('created_at', 'ASC');
    }

    public  function sprintTaskMultipleHistory(){
        return $this->hasMany(SprintTaskHistory::class,'sprint__tasks_id','id')->whereNotIn('status_id',[36,38,17,61])->groupBy('status_id')->orderBy('created_at', 'ASC');
    }
}
