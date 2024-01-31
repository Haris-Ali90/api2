<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Interfaces\ThreadInterface;
use Illuminate\Database\Eloquent\SoftDeletes;

class Thread extends Model implements ThreadInterface
{
    //
     use SoftDeletes;
    public const Joey_Type = 'joey';
    public const Onboarding_Type = 'onboarding';
    public const Dashboard_Type = 'dashboard';
    public const Merchant_Type = 'merchant';
    public const Guest_Type = 'guest';

    protected $fillable = [
        'id','user_id','creator_type','thread_type','other_user_id','other_user_type',
        'order_id',"thread_reason_id",'department'
    ];

    public function lastchatMessage()
    {
        return $this->hasone(Message::class,'thread_id')->orderBy('id','desc');
    }
    public function chatMessage()
    {
        return $this->hasMany(Message::class,'thread_id');
    }
    public function chatUnseenMessage()
    {
        return $this->hasMany(Message::class,'thread_id')->where('is_read',0);
    }
    public function threadParticipants()
    {
        return $this->hasMany(Participants::class,'thread_id');
    }
    public function threadReasonList()
    {
        return $this->belongsTo(ThreadReasonList::class,'thread_reason_id');
    }
    public function threadReasonListParent()
    {
        
        $ThreadReasonList=ThreadReasonList::where('id',$this->thread_reason_id)->first();

        if(!empty($ThreadReasonList)){
            return ThreadReasonList::where('id',$ThreadReasonList->thread_reason_list_id)->first();
        }
        return [];
    }
}
