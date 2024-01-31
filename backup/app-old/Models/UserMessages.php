<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class UserMessages extends Model
{

    // use SoftDeletes;
        //
        
    protected $fillable = [
            'id','message_id','sender_id','sender_type','receiver_id','receiver_type','type','seen_status','deliver_status','message_group_id'
    
    ];

}
