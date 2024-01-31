<?php

namespace App\Models;

use App\Models\FinanceVendorCity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Manager extends Model
{
    use SoftDeletes;
    protected $table = 'dashboard_managers';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    public function financeVendorCity()
    {
        return $this->belongsTo(FinanceVendorCity::class, 'hub_id','id');
    }

}
