<?php

namespace App\Listeners;

use App\Events\MessageSent;

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
        // Event MessageSent giờ chỉ truyền messageId thay vì message model
        // Relationship đã được tự động load trong broadcastOn() và broadcastWith() của event
        // Không cần xử lý gì ở đây nữa
    }
}
