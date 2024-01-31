<?php

namespace App\Models;
use App\Models\Interfaces\CityInterface;
use App\Models\Interfaces\CountryInterface;
use App\Models\Interfaces\LocationInterface;
use App\Models\Interfaces\SprintConfirmationInterface;
use App\Models\Interfaces\SprintContactInterface;
use App\Models\Interfaces\SprintInterface;

use App\Models\Interfaces\StateInterface;
use DB;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;


class State extends Model implements StateInterface

{

    public $table = 'states';


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','country_id','tax_id','name',
        'code'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
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
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }



}
