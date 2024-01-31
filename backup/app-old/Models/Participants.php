<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Interfaces\ParticipantsInterface;
use Illuminate\Database\Eloquent\SoftDeletes;

class Participants extends Model implements ParticipantsInterface
{
    //

    use SoftDeletes;
    protected $fillable = [
        
        'id','thread_id','user_id'
    ];
}
