<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceVendorCity extends Model
{
    protected $table = 'finance_vendor_city_relations';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'city_name'
    ];


}
