<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BoradlessDashboard extends Model
{

    use SoftDeletes;

    protected $table = 'boradless_dashboard';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];


}
