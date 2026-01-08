<?php

namespace App\Listeners;

use App\Events\UserSentMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandleUserSentMessage
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
    public function handle(UserSentMessage $event): void
    {
        // Reload relationship conversation sau khi deserialize từ queue
        // Đảm bảo relationship tồn tại khi broadcast
        if ($event->user && !$event->user->relationLoaded('conversation')) {
            $event->user->load('conversation');
        }
    }
}
