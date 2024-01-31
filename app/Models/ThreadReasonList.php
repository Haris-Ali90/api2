<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThreadReasonList extends Model
{
    //
    public function threadReason()
    {
        return $this->hasMany(ThreadReasonList::class);
    }
}
