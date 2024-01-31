<?php

namespace App\Models;
use App\Models\Interfaces\JoeyDepositInterface;

use App\Models\Interfaces\JoeyDocumentInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JoeyDocument extends Model implements JoeyDocumentInterface
{

    public $table = 'joey_documents';

    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
//    protected $fillable = [
//        'id','joey_id' ,'document_data', 'exp_date', 'document_type'
//
//    ];

    protected $guarded = [];

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
    public function joeyMandatoryDocuments()
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id','id')->where('is_optional',0);
    }

















}
