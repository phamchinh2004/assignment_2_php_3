<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class ChatAutoReplyService
{
    public static function shouldSendAutoReply(
        bool $isFirstCustomerMessage,
        ?Carbon $lastStaffMessageAt,
        ?Carbon $lastAutoReplyAt,
        float $repeatAfterHours = 1.0
    ): bool {
        if ($isFirstCustomerMessage) {
            return true;
        }

        if ($lastAutoReplyAt && $lastAutoReplyAt->diffInHours(now(), false) < $repeatAfterHours) {
            return false;
        }

        if ($lastStaffMessageAt && $lastStaffMessageAt->diffInHours(now(), false) < $repeatAfterHours) {
            return false;
        }

        return true;
    }

    public static function getEscalationRecipients(User $user): array
    {
        return app(ManagementRecipientResolver::class)
            ->forUser($user)
            ->pluck('email')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
