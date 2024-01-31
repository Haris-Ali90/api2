<?php

namespace App\Models;



use App\Models\Interfaces\CategoriesInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Categories extends Model implements CategoriesInterface
{

    public $table = 'categories';

    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'category_id',
        'vendor_id',
        'name',
        'start_at',
        'end_at',
        'sun_start',
        'sun_end',
        'mon_start',
        'mon_end',
        'tue_start',
        'tue_end',
        'wed_start',
        'wed_end',
        'thu_start',
        'thu_end',
        'fri_start',
        'fri_end',
        'sat_start',
        'sat_end',
        'sun_mode',
        'mon_mode',
        'tue_mode',
        'wed_mode',
        'thu_mode',
        'fri_mode',
        'sat_mode',
        'ordinal',


    ];


    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
    ];


    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */


}
