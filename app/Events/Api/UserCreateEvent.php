<?php

namespace App\Events\Api;


use Illuminate\Queue\SerializesModels;

class UserCreateEvent
{
    use SerializesModels;

    public $user;
    public $token;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($user,$activeToken)
    {

        $this->user = $user;
        $this->token=$activeToken;
    }
}
