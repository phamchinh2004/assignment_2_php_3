<?php

namespace App\Listeners;

use App\Events\MoneyDeposited;

class HandleMoneyDeposited
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
    public function handle(MoneyDeposited $event): void
    {
        //
    }
}
