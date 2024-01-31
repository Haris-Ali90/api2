<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use App\Http\Traits\BasicModelFunctions;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrackingNote extends Model
{
    // use BasicModelFunctions;
    use SoftDeletes;

    /**
     * Table name.
     *
     * @var array
     */
    public $table = 'tracking_notes';

    /**
     * The attributes that are guarded.
     *
     * @var array
     */
    protected $guarded = [];

}
