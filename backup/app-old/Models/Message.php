<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Interfaces\MessageInterface;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model implements MessageInterface
{
    //
    use SoftDeletes;
    
    protected $fillable = [
        'id','sender_id','body','message_type',
        'user_agent','thread_id'
    ];
    public function messageable()
    {
        return $this->morphTo();
    }
    public function messageFile()
    {
        return $this->hasMany(MessageFile::class,'message_id');
    }
    public function userMessages()
    {
        return $this->hasMany(UserMessages::class,'message_id');
    }
}
