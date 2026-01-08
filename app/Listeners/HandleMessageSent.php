<?php

namespace App\Listeners;

use App\Events\MessageSent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandleMessageSent
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
    public function handle(MessageSent $event): void
    {
        // Reload relationships sau khi deserialize từ queue
        // Đảm bảo relationships tồn tại khi broadcast
        if ($event->message && !$event->message->relationLoaded('sender')) {
            $event->message->load('sender');
        }
        if ($event->message && !$event->message->relationLoaded('conversation')) {
            $event->message->load('conversation');
        }
    }
}
