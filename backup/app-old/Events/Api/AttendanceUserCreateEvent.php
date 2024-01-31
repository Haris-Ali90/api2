<?php

namespace App\Events\Api;


use Illuminate\Queue\SerializesModels;

class AttendanceUserCreateEvent
{
    use SerializesModels;

    public $user;

    public $code;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($user)
    {
        $this->user = $user;
    }
}
