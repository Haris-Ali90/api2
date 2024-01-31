<?php

namespace App\Events\Api;


use Illuminate\Queue\SerializesModels;

class ResendCodeEvent
{
    use SerializesModels;

    public $user;

    public $code;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($user, $code)
    {
        $this->user = $user;
        $this->code = $code;
    }
}
