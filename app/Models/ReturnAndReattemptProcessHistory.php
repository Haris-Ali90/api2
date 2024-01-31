<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;

class ReturnAndReattemptProcessHistory extends Model
{
    protected $table = 'return_and_reattempt_process_history';

    /**
     * The attributes that are guarded.
     *
     * @var array
     */
    protected $guarded = [
    ];

    /**
     * Scope a query to only include customer support.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCustomerSupport($query)
    {
        return $query->where('process_type', '=', 'customer_support')->where('deleted_at',null);
    }

}
