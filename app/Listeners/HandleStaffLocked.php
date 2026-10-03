<?php

namespace App\Listeners;

use App\Events\StaffLocked;

class HandleStaffLocked
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
    public function handle(StaffLocked $event): void
    {
        //
    }
}
