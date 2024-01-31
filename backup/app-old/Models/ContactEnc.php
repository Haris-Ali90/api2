<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class ContactEnc extends Model
{

    private $key;
    private $cipher;

    public function __construct()
    {
        // getting keys
        $system_constants = config('system-constant');
        $this->key = $system_constants['AES_DB_KEY'];
        $this->cipher = $system_constants['AES_DB_CIPHER'];
    }


    public $table = 'contacts_enc';

    use SoftDeletes;//,Notifiable;

    /**
     * The attributes that are guarded.
     *
     * @var array
     */
    protected $guarded = [
    ];


    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
    ];





    // DB "AES ENCRYPT DECRYPTION" functions
    protected $encrypted_columns = [
        'name',
        'phone',
        'email'
    ];

    public function scopegetDecrypted($query)
    {
        // getting sql string
        $queryString = $query->toSql();
        // getting bindings
        $queryBindings = $query->getBindings();

        // breaking query string
        $BreakQuery = explode('*',$queryString,2);
        //query first part
        $BreakQueryFirstPart = $BreakQuery[0];
        //query second part
        $BreakQuerySeondPart = $BreakQuery[1];

        // creating adding query
        $addingQuery = "";
        foreach($this->encrypted_columns as $encrypted_column)
        {
            $addingQuery.='AES_DECRYPT('.$encrypted_column.',"'.$this->key.'","'.$this->iv.'") AS '.$encrypted_column.'_decrypted , ';
        }
        $addingQuery.= $this->table.'.*';
        // adding decryption query
        //$newQuery =  $BreakQueryFirstPart.' '.$addingQuery.' '.$this->table.'.* '.$BreakQuerySeondPart;

        //$data = self::select($newQuery,$queryBindings)->get();

        $data = $query->get(DB::raw($addingQuery));

        return $data;
    }


    public function scopefirstDecrypted($query)
    {
        // creating adding query
        $addingQuery = "";
        foreach($this->encrypted_columns as $encrypted_column)
        {
            $addingQuery.='AES_DECRYPT('.$encrypted_column.',"'.$this->key.'","'.$this->iv.'") AS '.$encrypted_column.'_decrypted , ';
        }
        $addingQuery.= $this->table.'.*';

        $data = $query->first(DB::raw($addingQuery));

        return $data;
    }

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = DB::raw("AES_ENCRYPT('".$value."', '".$this->key."', '".$this->cipher."')");
    }
    public function setPhoneAttribute($value)
    {
        $this->attributes['phone'] = DB::raw("AES_ENCRYPT('".$value."', '".$this->key."', '".$this->cipher."')");
    }
    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = DB::raw("AES_ENCRYPT('".$value."', '".$this->key."', '".$this->cipher."')");
    }
    // public function setSuiteAttribute($value)
    // {
    //     $this->attributes['suite'] = DB::raw("AES_ENCRYPT('".$value."', '".$this->key."', '".$this->cipher."')");
    // }
    // public function setLatitudeAttribute($value)
    // {
    //     $this->attributes['latitude'] = DB::raw("AES_ENCRYPT('".$value."', '".$this->key."', '".$this->cipher."')");
    // }
    // public function setLongitudeAttribute($value)
    // {
    //     $this->attributes['longitude'] = DB::raw("AES_ENCRYPT('".$value."', '".$this->key."', '".$this->cipher."')");
    // }




}
