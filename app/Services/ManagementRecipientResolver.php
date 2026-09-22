<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class ManagementRecipientResolver
{
    public function forUser(User $user): Collection
    {
        $referrer = $user->referrer;

        $adminIds = User::query()
            ->where('role', User::ROLE_ADMIN)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $ownerIds = User::query()
            ->where('role', User::ROLE_OWNER)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $ids = $referrer
            ? $this->idsForReferrer($referrer, $adminIds, $ownerIds)
            : $this->idsForLegacyFallback($user->conversation?->staff, $adminIds);

        return User::query()->whereIn('id', $ids)->get()
            ->sortBy(fn (User $recipient) => array_search((int) $recipient->id, $ids, true))
            ->values();
    }

    public function idsForReferrer(User $referrer, array $adminIds, array $ownerIds): array
    {
        $ids = match ($referrer->role) {
            User::ROLE_STAFF => [(int) $referrer->id, ...$adminIds, ...$ownerIds],
            User::ROLE_ADMIN => [(int) $referrer->id, ...$ownerIds],
            default => [],
        };

        return array_values(array_unique(array_map('intval', $ids)));
    }

    public function idsForLegacyFallback(?User $assignedManager, array $adminIds): array
    {
        $ids = [
            ...($assignedManager ? [(int) $assignedManager->id] : []),
            ...$adminIds,
        ];

        return array_values(array_unique(array_map('intval', $ids)));
    }
}
