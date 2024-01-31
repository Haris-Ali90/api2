<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GenerateToken extends Model
{
    protected $fillable =['token','expired_at'];
}
