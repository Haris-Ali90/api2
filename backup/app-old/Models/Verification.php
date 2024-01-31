<?php

namespace App\Models;


use App\Models\Interfaces\VerificationInterface;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;

class Verification extends Model implements VerificationInterface
{
    public $table = 'verifications';

    protected $guarded = [];

    protected $fillable = [
        'user_id',
        'email',
        'code',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $casts = [
        'user_id'          => 'integer',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = ['created_at','updated_at'];

    /**
     * The attributes that should be append to toArray.
     *
     * @var array
     */
    protected $appends = [];



    public function userDetail()
    {
        return $this->belongsTo(\App\Models\User::class);
    }


}
