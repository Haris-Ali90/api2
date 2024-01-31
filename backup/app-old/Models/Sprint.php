<?php

namespace App\Models;
use App\Models\Interfaces\SprintInterface;

use DB;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;


class Sprint extends Model implements SprintInterface
{

    public $table = 'sprint__sprints';

    use SoftDeletes,Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','joey_id','creator_id','creator_type','checked_out_at', 'vehicle_id','route_json',
        'distance','distance_allowance','distance_charge','task_total','subtotal',
        'tax','tip','credit_amount',
        'total','status_id','status_copy',
        'active','merchant_charge','joey_pay',
        'joey_tax_pay','joeyco_pay','make_payment_total','collect_payment_total','push_at',
        'broadcast_location_id','level','visibility',
        'optimize_route','only_this_vehicle','timezone',
        'credit_card_id','last_eta_update','min_score','last_task_id','is_sameday',
        'rbc_deposit_number','cash_on_hand','timezone',
        'is_cc_preauthorized','is_hook','is_updated',
        'merchant_order_num','store_num','is_hub','direct_pickup_from_hub','in_hub_route',
        'date_updated'
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
        ### for multiple task from sprint id
    public  function sprintTask(){
        return $this->hasMany(SprintTasks::class,'sprint_id','id')->orderBy('ordinal','ASC');
    }

    ### for multiple drop task from sprint id
    public  function sprintDropTask(){
        return $this->hasMany(SprintTasks::class,'sprint_id','id')->whereIn('type',['pickup','dropoff'])->orderBy('ordinal','ASC');
    }

    // optimize order in order detail
    public  function sprintTaskAscId(){
        return $this->hasMany(SprintTasks::class,'sprint_id','id')->orderBy('id','ASC');
    }

    public function getSerialNumber($taskId)
    {
        $tasks = $this->sprintTaskAscId->pluck('id');
        return array_search($taskId, $tasks->toArray());
    }

    ### for task from sprint id
    public  function task(){
        return $this->belongsTo(SprintTasks::class,'id','sprint_id');
    }

    ### for sprint task history
    public function sprintHistory(){

        return $this->hasMany(SprintTaskHistory::class,'sprint_id','id')->groupBy('status_id')->orderBy('date');
    }

    ### for sprint task history
    public function sprintHistoryByStatus($status_ids){
       return $this->hasOne(SprintTaskHistory::class,'sprint_id','id')->where('status_id',$status_ids);

    }

    public function sprintPickupTask(){
        return $this->hasOne(SprintTasks::class,'sprint_id','id')->where('type','pickup');
    }


    public function sprintLastDropOffTask(){
        return $this->hasOne(SprintTasks::class,'sprint_id','id')->where('type','dropoff')->orderBy('ordinal', 'desc');
    }
    public function mulipleSprintLastDropOffTask(){
        return $this->hasMany(SprintTasks::class,'sprint_id','id')->where('type','dropoff')->orderBy('ordinal', 'desc');
    }

    public function joey(){
        return $this->belongsTo(Joey::class,'joey_id','id');
    }

    public function vendor(){
        return $this->belongsTo(Vendor::class,'creator_id','id');
    }
     // new work
    public function sprintLastTask(){
        return $this->hasOne(SprintTasks::class,'sprint_id','id')->orderBy('ordinal', 'desc');
    }
    //new work
    public function getSecondLastTask()
    {
        return $this->hasOne(SprintTasks::class,'sprint_id','id')->orderBy('ordinal', 'desc')->skip(1);
    }


    //new work
    public function vehicle(){
        return $this->belongsTo(Vehicle::class,'vehicle_id','id');
    }
    // new work
    public function sprintFirstPickupTask(){
        return $this->hasOne(SprintTasks::class,'sprint_id','id')->where('type','pickup')->orderBy('ordinal');
    }
    // new work
    public function sprintFirstDropoffTask(){
        return $this->hasOne(SprintTasks::class,'sprint_id','id')->where('type','dropoff')->orderBy('ordinal');
    }
     // new work
     public function dispatch(){
        return $this->hasOne(Dispatch::class,'sprint_id','id');
    }

}
