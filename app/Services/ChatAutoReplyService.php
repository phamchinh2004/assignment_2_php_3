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

    public static function getEscalationRecipients(User $manager): array
    {
        $resolvedEmails = app(ManagementRecipientResolver::class)
            ->forConversationManager($manager)
            ->pluck('email')
            ->all();

        $ownerEmails = User::query()
            ->where('role', User::ROLE_OWNER)
            ->pluck('email')
            ->all();

        $managerEmail = $manager->email ? [$manager->email] : [];

        return collect(array_merge($managerEmail, $ownerEmails, $resolvedEmails))
            ->map(fn ($email) => is_string($email) ? trim($email) : '')
            ->filter(fn ($email) => $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();
    }
}
