<?php

namespace App\Listeners;

use App\Events\UserLocked;

class HandleUserLocked
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(UserLocked $event): void
    {
        //
    }
}
