<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Hub;
use App\Models\Sprint;

class MicroHubOrder extends Model
{
    use SoftDeletes;
    public $table = 'orders_actual_hub';

    protected $fillable = [
        'hub_id', 'sprint_id', 'is_my_hub', 'bundle_id'
    ];

//    protected $hidden = ['created_at','updated_at'];

    /**
     * The attributes that should be append to toArray.
     *
     * @var array
     */
    protected $appends = [];

    public function hub()
    {
        return $this->belongsTo(Hub::class, 'hub_id', 'id');
    }

    public function sprint()
    {
        return $this->belongsTo(Sprint::class, 'sprint_id', 'id');
    }

}

