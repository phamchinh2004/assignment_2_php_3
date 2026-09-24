<?php

namespace App\Services;

use App\Models\FeatureAnnouncement;
use App\Models\FeatureAnnouncementRead;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FeatureAnnouncementService
{
    public function getUnreadAnnouncements(User $user): Collection
    {
        if (!in_array($user->role, FeatureAnnouncement::TARGET_ROLES, true)) {
            return collect();
        }

        return FeatureAnnouncement::query()
            ->currentlyVisible()
            ->forUser($user)
            ->whereDoesntHave('reads', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->whereColumn(
                        'feature_announcement_reads.announcement_version',
                        'feature_announcements.version'
                    );
            })
            ->orderByRaw(
                "CASE priority WHEN 'critical' THEN 3 WHEN 'important' THEN 2 ELSE 1 END DESC"
            )
            ->orderBy('starts_at')
            ->orderBy('id')
            ->get();
    }

    public function acknowledge(User $user, FeatureAnnouncement $announcement): FeatureAnnouncementRead
    {
        abort_unless(
            $this->userCanReceiveAnnouncement($user, $announcement),
            409,
            'Thông báo này không còn khả dụng cho tài khoản của bạn.'
        );

        $now = now();

        DB::table('feature_announcement_reads')->insertOrIgnore([
            'announcement_id' => $announcement->id,
            'user_id' => $user->id,
            'announcement_version' => $announcement->version,
            'acknowledged_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return FeatureAnnouncementRead::query()
            ->where('announcement_id', $announcement->id)
            ->where('user_id', $user->id)
            ->where('announcement_version', $announcement->version)
            ->firstOrFail();
    }

    public function userCanReceiveAnnouncement(User $user, FeatureAnnouncement $announcement): bool
    {
        if (!in_array($user->role, FeatureAnnouncement::TARGET_ROLES, true)) {
            return false;
        }

        if (!$announcement->is_active) {
            return false;
        }

        $now = now();

        if (!$announcement->starts_at || $announcement->starts_at->isAfter($now)) {
            return false;
        }

        if ($announcement->ends_at && $announcement->ends_at->isBefore($now)) {
            return false;
        }

        if ($announcement->target_type === FeatureAnnouncement::TARGET_TYPE_USERS) {
            return $announcement->targetedUsers()
                ->where('users.id', $user->id)
                ->exists();
        }

        return in_array($user->role, $announcement->target_roles ?? [], true);
    }

    public function getStatsForAnnouncements(Collection $announcements): array
    {
        if ($announcements->isEmpty()) {
            return [];
        }

        $announcements->loadMissing('targetedUsers:id,role');

        $roleCounts = User::query()
            ->whereIn('role', FeatureAnnouncement::TARGET_ROLES)
            ->selectRaw('role, COUNT(*) as aggregate')
            ->groupBy('role')
            ->pluck('aggregate', 'role')
            ->map(fn ($count) => (int) $count);

        $reads = FeatureAnnouncementRead::query()
            ->with('user:id,role')
            ->where(function ($query) use ($announcements) {
                foreach ($announcements as $announcement) {
                    $query->orWhere(function ($query) use ($announcement) {
                        $query->where('announcement_id', $announcement->id)
                            ->where('announcement_version', $announcement->version);
                    });
                }
            })
            ->get()
            ->groupBy('announcement_id');

        return $announcements->mapWithKeys(function (FeatureAnnouncement $announcement) use ($roleCounts, $reads) {
            $targetRoles = $announcement->target_roles ?? [];
            $targetUserIds = $announcement->targetedUsers
                ->whereIn('role', FeatureAnnouncement::TARGET_ROLES)
                ->pluck('id');

            if ($announcement->target_type === FeatureAnnouncement::TARGET_TYPE_USERS) {
                $targetCount = $targetUserIds->count();
                $acknowledgedCount = $reads->get($announcement->id, collect())
                    ->whereIn('user_id', $targetUserIds)
                    ->pluck('user_id')
                    ->unique()
                    ->count();
            } else {
                $targetCount = collect($targetRoles)->sum(fn ($role) => $roleCounts->get($role, 0));
                $acknowledgedCount = $reads->get($announcement->id, collect())
                    ->filter(fn (FeatureAnnouncementRead $read) => $read->user
                        && in_array($read->user->role, $targetRoles, true))
                    ->pluck('user_id')
                    ->unique()
                    ->count();
            }

            return [
                $announcement->id => [
                    'target_count' => $targetCount,
                    'acknowledged_count' => $acknowledgedCount,
                ],
            ];
        })->all();
    }

    public function getAnnouncementStats(FeatureAnnouncement $announcement): array
    {
        $targetUsers = $announcement->target_type === FeatureAnnouncement::TARGET_TYPE_USERS
            ? $announcement->targetedUsers()
                ->whereIn('role', FeatureAnnouncement::TARGET_ROLES)
                ->select(['users.id', 'full_name', 'username', 'email', 'role'])
                ->orderBy('role')
                ->orderBy('full_name')
                ->get()
            : User::query()
                ->whereIn('role', $announcement->target_roles ?? [])
                ->select(['id', 'full_name', 'username', 'email', 'role'])
                ->orderBy('role')
                ->orderBy('full_name')
                ->get();

        $reads = FeatureAnnouncementRead::query()
            ->where('announcement_id', $announcement->id)
            ->where('announcement_version', $announcement->version)
            ->whereIn('user_id', $targetUsers->pluck('id'))
            ->get()
            ->keyBy('user_id');

        $users = $targetUsers->map(function (User $user) use ($reads) {
            return [
                'user' => $user,
                'acknowledged_at' => $reads->get($user->id)?->acknowledged_at,
            ];
        });

        return [
            'target_count' => $targetUsers->count(),
            'acknowledged_count' => $users->whereNotNull('acknowledged_at')->count(),
            'users' => $users,
        ];
    }
}
