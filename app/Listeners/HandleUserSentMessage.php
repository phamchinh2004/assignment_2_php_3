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
        // Event giờ chỉ truyền userId, không cần xử lý ở đây
        // Relationship đã được load trong broadcastOn() của event UserSentMessage
        // Listener này có thể để trống hoặc thêm logic khác nếu cần
    }
}
