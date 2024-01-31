<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Hub;
use App\Models\Sprint;

class CurrentHubOrder extends Model
{
    use SoftDeletes;
    public $table = 'orders_hub_history';

    protected $fillable = [
        'hub_id', 'sprint_id', 'joey_id', 'is_actual_hub'
    ];

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

    public function joey()
    {
        return $this->belongsTo(Joey::class, 'joey_id', 'id');
    }

}

