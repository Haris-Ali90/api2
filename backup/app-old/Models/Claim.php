<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Claim extends Model
{
    // use HasFactory;
    use SoftDeletes;
    public $table = 'claims';

    protected $fillable = [
        "id",
        "vendor_id",
        "user_id",
        "tracking_id",
        "sprint_id",
        "task_id",
        "route_id",
        "brooker_id",
        "joey_id",
        "sprint_status_id",
        "amount",
        "status",
        "image",
        "reason_id",
    ];

    protected $guarded = [];

    public function joey(){
        return $this->belongsTo(Joey::class,'joey_id','id');
    }

    public function brookersUsers(){
        return $this->belongsTo(BrokerUser::class,'brooker_id','id');
    }
    public function vendor(){
        return $this->belongsTo(Vendor::class,'vendor_id','id');
    }
    public function vendors(){
        return $this->belongsTo(Vendor::class,'vendor_id','id');
    }

    public function reason(){
        return $this->belongsTo(ClaimReason::class,'reason_id','id');
    }
    public function getReason(){
        return $this->belongsTo(ClaimReason::class,'reason_id','id');
    }


    public function TotalClaimValueCount($joeyId)
    {
        return $this->where('joey_id',$joeyId)->pluck('amount')->sum();
    }

    public function TotalClaimCount($joeyId)
    {
        return $this->where('joey_id',$joeyId)->pluck('joey_id')->groupBy('tracking_id')->count();
    }

    public function TotalCountOrderDelivered($joeyId,$start,$end)
    {
        return RouteHistory::where('joey_id',$joeyId)->whereBetween('created_at',[$start,$end])->where('status',2)->groupBy('task_id')->pluck('task_id')->count();
    }

    public function tasks()
    {
        return $this->belongsTo(SprintTasks::class,'task_id', 'id');
    }


}
