<?php

namespace App\Models;
use App\Models\Interfaces\BasicVendorInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BasicVendor extends Model implements BasicVendorInterface
{

    public $table = 'basic_vendor';

    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'vendor_id',
        'deleted_at',
        'created_at',
        'updated_at',

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

    public function vendor() {
        return $this->belongsTo(Vendors::class,'vendor_id','id');
    }
}
