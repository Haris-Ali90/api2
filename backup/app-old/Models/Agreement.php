<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;


class Agreement extends Model
{

    /**
     * Table name.
     *
     * @var array
     */
    public $table = 'agreements';
    // Protected $primaryKey = "id";
    protected $fillable = ['target','copy','effective_at','created_at','updated_at'];

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
    protected $casts = [];




}
