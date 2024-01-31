<?php

namespace App\Models;

use App\Models\Interfaces\HubZonesInterface;
use Illuminate\Database\Eloquent\Model;


class HubZones extends Model implements HubZonesInterface
{

    /**
     * Table name.
     *
     * @var array
     */
    public $table = 'hub_zones';
    // Protected $primaryKey = "id";
    protected $fillable = ['hub_id','zone_id','city__id','created_at','updated_at','deleted_at'];

    /**
     * The attributes that are guarded.
     *
     * @var array
     */
    protected $guarded = [
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [];

    // hubs  function relation
    public function hub()
    {
        return $this->belongsTo(Hub::class,'hub_id','id');
    }


}
