<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;


class ZoneVendorRelationship extends Model
{

    /**
     * Table name.
     *
     * @var array
     */
    public $table = 'zone_vendor_relationship';
    // Protected $primaryKey = "id";

    /**
     * The attributes that are guarded.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [];

    // new work
    public function zones()
    {
        return $this->belongsTo(Zones::class,'zone_id','id');
    }


}
