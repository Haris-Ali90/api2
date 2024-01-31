<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageFile extends Model
{
    protected $fillable = [
        'id',
        'file_name',
        'file_type',
        'message_id',
    ];
}
