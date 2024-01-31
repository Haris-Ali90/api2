<?php

namespace App\Listeners\Api;



use App\Events\Api\UserCreateEvent;
use App\Notifications\Api\UserCreateNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UserCreateListener
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

    public function handle(UserCreateEvent $event)
    {
        $user = $event->user;


            $user->notify(new UserCreateNotification());
    }
}
