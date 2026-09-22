<?php

namespace App\Listeners;

use App\Events\UserSentMessage;
use App\Models\User;
use App\Notifications\ChatMessageNotification;
use App\Services\ManagementRecipientResolver;

class HandleUserSentMessage
{
    /**
     * Create the event listener.
     */
    public function __construct(private ManagementRecipientResolver $recipients)
    {
    }

    /**
     * Handle the event.
     */
    public function handle(UserSentMessage $event): void
    {
        if (!$event->userId) {
            return;
        }

        $user = User::with(['referrer', 'conversation'])->find($event->userId);
        if (!$user || !$user->conversation) {
            return;
        }

        foreach ($this->recipients->forUser($user) as $recipient) {
            $recipient->notify(new ChatMessageNotification(
                $user->conversation->id,
                (string) $event->full_name,
                (string) $event->message
            ));
        }
    }
}
