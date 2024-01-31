<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrackingImageHistory extends Model
{

    protected $table = 'tracking_image_history';
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


    public function reason()
    {
        return $this->belongsTo(Reason::class,'reason_id','id');
    }


    public function user()
    {
        return $this->belongsTo(User::class,'user_id','id');
    }


}
