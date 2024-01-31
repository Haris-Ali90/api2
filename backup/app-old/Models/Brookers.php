<?php

namespace App\Models;

use App\BrookerJoey;
use App\Models\Interfaces\BrookersInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brookers extends Model implements BrookersInterface
{

    use SoftDeletes;

    /**
     * Table name.
     *
     * @var array
     */
    public $table = 'brookers_users';

    /**
     * remove time steps
     */
    public $timestamps = false;

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
    protected $casts = [
        'value' => 'float'
    ];

    public function getPhoneFormattedAttribute()
    {
        return $this->attributes['phone'] ? phone($this->attributes['phone'])->formatNational() : '';// $this->attributes['phone'] : '';
    }

    public function managerBrokerUsers()
    {
        return $this->hasMany( BrookerJoey::class,'brooker_id', 'id');
    }
}
