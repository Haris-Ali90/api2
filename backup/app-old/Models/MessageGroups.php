<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
class MessageGroups extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'id','name','user_id','creator_type','message_group_pic'  

    ];

    public function getGroupMember()
    {
        return $this->hasMany(Participants::class,'message_group_id');
    }
    public function messages()
    {
        return $this->hasMany(Message::class,'group_id');
    }
    public function messagesCount()
    {
        return $this->hasMany(Message::class,'group_id')->count();
    }
}
