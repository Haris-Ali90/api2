<?php

namespace App\Models;

use App\Models\Interfaces\SystemParametersInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;



class SystemParameters extends Model implements SystemParametersInterface
{

    use SoftDeletes;
    /**
     * Table name.
     *
     * @var array
     */
    public $table = 'system_parameters';

    /**
     * The attributes that are guarded.
     *
     * @var array
     */
    protected $guarded = [
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'name',
        'key',
        'value',

    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [];

    /**
     * The attributes setters.
     *
     * @var array
     */

    public function setValueAttribute($value)
    {
        $this->attributes['value'] = trim(strtolower($value));
    }


    public function getKeyValue($key)
    {

    }
}
