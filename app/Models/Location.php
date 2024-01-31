<?php

namespace App\Models;
use App\Models\Interfaces\LocationInterface;
use App\Models\Interfaces\SprintConfirmationInterface;
use App\Models\Interfaces\SprintContactInterface;
use App\Models\Interfaces\SprintInterface;

use DB;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;


class Location extends Model implements LocationInterface
//extends Model implements JoeyRoutesInterface
{

    public $table = 'locations';

    use SoftDeletes,Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','address','city_id','state_id',
        'country_id','postal_code','buzzer','suite',
        'latitude','longitude','location_type'
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

    ### for city
    public  function City(){
        return $this->belongsTo(City::class,'city_id','id');
    }

    ### for state
    public  function State(){
        return $this->belongsTo(State::class,'state_id','id');
    }

    ### for country
    public  function Country(){
        return $this->belongsTo(Country::class,'country_id','id');
    }




}
