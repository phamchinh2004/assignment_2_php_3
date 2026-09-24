<?php

namespace Tests\Unit;

use App\Models\FeatureAnnouncement;
use App\Models\User;
use App\Services\FeatureAnnouncementService;
use Tests\TestCase;

class FeatureAnnouncementServiceTest extends TestCase
{
    public function test_user_can_receive_only_current_active_announcement_for_their_role(): void
    {
        $service = new FeatureAnnouncementService();
        $user = new User();
        $user->role = User::ROLE_STAFF;

        $announcement = new FeatureAnnouncement([
            'is_active' => true,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addMinute(),
            'target_roles' => [User::ROLE_STAFF],
        ]);

        $this->assertTrue($service->userCanReceiveAnnouncement($user, $announcement));

        $announcement->starts_at = now()->addMinute();
        $this->assertFalse($service->userCanReceiveAnnouncement($user, $announcement));

        $announcement->starts_at = now()->subMinutes(2);
        $announcement->ends_at = now()->subMinute();
        $this->assertFalse($service->userCanReceiveAnnouncement($user, $announcement));

        $announcement->ends_at = null;
        $announcement->is_active = false;
        $this->assertFalse($service->userCanReceiveAnnouncement($user, $announcement));

        $announcement->is_active = true;
        $announcement->target_roles = [User::ROLE_ADMIN];
        $this->assertFalse($service->userCanReceiveAnnouncement($user, $announcement));
    }
}
