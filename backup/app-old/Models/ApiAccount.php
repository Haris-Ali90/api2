<?php

namespace App\Models;

use App\Models\Interfaces\ApiAccountInterface;

use Illuminate\Database\Eloquent\Model;

class ApiAccount extends Model implements ApiAccountInterface
{

    /**
     * Table name.
     *
     * @var array
     */
    public $table = 'api_accounts';

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
    public static function findByPublicKey($publicKey) {

        return self::where('public_key', '=', $publicKey)
            ->whereNull('deleted_at')
            ->first();
    }

    private function getAllHeaders() {
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) == "HTTP_") {
                $temp = strtolower(str_replace("_", " ", substr($key, 5)));
                $out[str_replace(" ", "-", ucwords($temp))] = $value;
            }
        }
        return $out;
    }

    private function getRequestUri() {
        $query = array();

        $parsedUrl = parse_url($_SERVER['REQUEST_URI']);
        parse_str($parsedUrl['query'], $query);
        unset($query['signature']);

        return $parsedUrl['path'] . '?' . http_build_query($query);
    }

    private function signRequest() {
        $headers = $this->getAllHeaders();

        if (\array_key_exists('CONTENT_TYPE', $_SERVER)) {
            $contentType = $_SERVER['CONTENT_TYPE'];
        } else {
            $contentType = '';
        }

        if (!isset($headers['Jco-Timestamp'])) {
            throw new \Exception('Jco-Timestamp is missing');
        }

        if (time() - $headers['Jco-Timestamp'] > 60) {
            //request was made more than a minute ago
            throw new \Exception('Request expired.');
        }

        $stringToSign = $_SERVER['REQUEST_METHOD'] . "\n"
            . $contentType . "\n"
            . $headers['Jco-Timestamp'] . "\n"
            . $this->getRequestUri();

        return \hash_hmac('sha512', $stringToSign
            , $this->attributes['private_key']);
    }

    /**
     * @param string $signature
     * @return bool
     */
    public function verifySignature($signature) {
        return $this->signRequest() == $signature;
    }

    public function getPublicKey() {
        dd('asd');
        return $this->attributes['public_key'];
    }

    public function getPrivateKey() {
        return $this->attributes['private_key'];
    }



}
