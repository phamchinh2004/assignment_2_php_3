<?php

namespace App\Listeners;

use App\Events\PermissionRevoked;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandlePermissionRevoked
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
    public function handle(PermissionRevoked $event): void
    {
        //
    }
}
