<?php

namespace App\Listeners;

use App\Events\MessageRead;

class HandleMessageRead
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
    public function handle(MessageRead $event): void
    {
        //
    }
}
