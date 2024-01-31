<?php

namespace App\Listeners\Api;



use App\Events\Api\AttendanceUserCreateEvent;
use App\Events\Api\UserCreateEvent;
use App\Notifications\Api\AttendanceUserCreateNotification;
use App\Notifications\Api\UserCreateNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class AttendanceUserCreateListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function handle(AttendanceUserCreateEvent $event)
    {
        $user = $event->user;
        $code = $event->code;

            $user->notify(new AttendanceUserCreateNotification($code));
    }
}
