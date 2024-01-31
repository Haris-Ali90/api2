<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClaimReason extends Model
{
    // use HasFactory;
    use SoftDeletes;
    public $table = 'claim_reasons';

    protected $fillable = [
        "id",
        "title",
        "slug",
    ];

    public function claim()
    {
        return $this->hasMany(Claim::class);
    }
}
