<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrookerUser extends Model
{

    protected $table = 'brookers_users';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    // protected $fillable = [
    //     'id', 'name', 'created_at','updated_at','deleted_at',
    // ];

    /**
     * The attributes that are guarded.
     *
     * @var array
     */
    protected $guarded = [
    ];

    public function BrookerJoeys()
    {
        return $this->hasMany( BrookerJoey::class,'brooker_id', 'id');
    }


}
