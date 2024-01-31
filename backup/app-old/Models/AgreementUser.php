<?php

namespace App\Models;

use App\Models\Interfaces\AgreementSigner;
use App\Models\Interfaces\AgreementUserInterface;
use Illuminate\Database\Eloquent\Model;


class AgreementUser extends Model implements AgreementUserInterface
{
    public  $timestamps = true;
    private $signatureDate = null;
    /**
     * Table name.
     *
     * @var array
     */
    public $table = 'agreements_user';
    protected $fillable = ['agreement_id','user_id','user_type','signed_at','created_at','updated_at'];

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
    protected $casts = [];



    public function __construct($attributes = array(), $exists = false) {
        parent::__construct($attributes, $exists);

        $this->signatureDate = null;
    }


    public static function getSignature(AgreementSigner $signer) {

        $agreement = $signer->getLatestAgreement();

        $signature = self::where('user_id', '=', $signer->getId())
            ->where('user_type', '=', $signer->getSignatureTarget())
            ->where('agreement_id', '=', $agreement->getId())
            ->first();

        if ($signature === null) {
            $signature = new static();
            $signature->attributes['agreement_id'] = $agreement->getId();
            $signature->attributes['user_id'] = $signer->getId();
            $signature->attributes['user_type'] = $signer->getSignatureTarget();
            $signature->attributes['signed_at'] = null;
            $signature->save();
        }

        return $signature;
    }

    public function isAgreementSigned() {
        return $this->attributes['signed_at'] !== null;
    }

    public function signAgreement() {
        if (!$this->isAgreementSigned()) {
            $this->attributes['signed_at'] = date('Y-m-d H:i:s');
        }
    }

    public function getSignatureDate() {

        if ($this->isAgreementSigned() && $this->signatureDate === null) {
            $this->signatureDate = new \DateTime($this->attributes['signed_at']);
        }

        return $this->signatureDate;
    }





















}
