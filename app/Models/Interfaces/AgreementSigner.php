<?php

namespace App\Models\Interfaces;

interface AgreementSigner {

    public function signAgreement();

    public function isLatestAgreementSigned();

    public function getLatestAgreement();

    public function getAgreementSignature();

    public function getSignatureTarget();

}
