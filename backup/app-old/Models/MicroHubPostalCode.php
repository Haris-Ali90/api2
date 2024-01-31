<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Hub;

class MicroHubPostalCode extends Model
{
    use SoftDeletes;
    public $table = 'micro_hub_postal_codes';

    protected $fillable = [
        'hub_id', 'postal_code'
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

}

