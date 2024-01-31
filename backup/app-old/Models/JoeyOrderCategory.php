<?php

namespace App\Models;

use App\Models\Interfaces\JoeyOrderCategoryInterface;

use Illuminate\Database\Eloquent\Model;

class JoeyOrderCategory extends Model implements JoeyOrderCategoryInterface
{

    public $table = 'joey_order_category';



    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','order_category_id','joey_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [

    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
    ];


















}
