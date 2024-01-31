<?php

namespace App\Events\Api;


use Illuminate\Queue\SerializesModels;

class UserForgotPassword
{
    use SerializesModels;

    public $user;

    public $pass;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($user, $pass)
    {
        $this->user = $user;
        $this->pass = $pass;
    }
}
