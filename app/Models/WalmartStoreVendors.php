<?php

namespace App\Models;

use App\Models\Interfaces\WalmartStoreVendorsInterface;
use DB;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Notifications\Notifiable;


class WalmartStoreVendors extends Model implements WalmartStoreVendorsInterface


{

    public $table = 'walmart_store_vendors';

    use SoftDeletes,Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','store_num','vendor_id'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
    ];


    /**
     * Scope a query to only include not deleted
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotDeleted($query)
    {
        return $query->whereNull('deleted_at');
    }


}
