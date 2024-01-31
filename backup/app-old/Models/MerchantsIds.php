<?php

namespace App\Models;
use App\JoeyRouteLocations;
use App\Models\Interfaces\MerchantIdsInterface;

use Illuminate\Database\Eloquent\Model;

class MerchantsIds extends Model implements MerchantIdsInterface
{

    public $table = 'merchantids';



    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','task_id','merchant_order_num','item_count','package_count',
        'additional_info','end_time',
        'start_time','tracking_id','address_line2','scheduled_duetime',
             'weight','actual_address',
        'weight_unit'

    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [

    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
    ];




    ### for sprint task
    public  function taskids(){
        return $this->belongsTo(SprintTasks::class,'task_id','id');
    }

    public  function notes(){
        return $this->hasMany(TrackingNote::class,'tracking_id','tracking_id')->orderBy('created_at','ASC');
    }

    public function managerJoeyRouteLocation()
    {
        return $this->hasOne(JoeyRouteLocation::class,'task_id','task_id')->orderBy('id','desc');
    }
}
