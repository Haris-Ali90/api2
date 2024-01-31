<?php

namespace App\Models;
use App\Models\Interfaces\ProcessedXmlFilesInterface;
use Illuminate\Database\Eloquent\Model;

class ProcessedXmlFiles extends Model implements ProcessedXmlFilesInterface
{

    public $table = 'processed_xml_files';

    /**
     * The attributes that are mass assignable.
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


    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */

}
