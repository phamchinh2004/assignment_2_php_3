<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\ManagementRecipientResolver;
use PHPUnit\Framework\TestCase;

class ManagementRecipientResolverTest extends TestCase
{
    private ManagementRecipientResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new ManagementRecipientResolver();
    }

    public function test_staff_referrer_targets_staff_all_admins_and_all_owners_without_duplicates(): void
    {
        $staff = new User();
        $staff->id = 10;
        $staff->role = User::ROLE_STAFF;

        $this->assertSame(
            [10, 20, 21, 30, 31],
            $this->resolver->idsForReferrer($staff, [20, 21, 10], [30, 31, 20])
        );
    }

    public function test_admin_referrer_targets_only_that_admin_and_all_owners_without_duplicates(): void
    {
        $admin = new User();
        $admin->id = 20;
        $admin->role = User::ROLE_ADMIN;

        $this->assertSame(
            [20, 30, 31],
            $this->resolver->idsForReferrer($admin, [20, 21, 22], [30, 31, 20])
        );
    }

    public function test_missing_referrer_preserves_legacy_assigned_manager_and_all_admins(): void
    {
        $assignedManager = new User();
        $assignedManager->id = 10;

        $this->assertSame(
            [10, 20, 21],
            $this->resolver->idsForLegacyFallback($assignedManager, [20, 21, 10])
        );
    }

    public function test_missing_referrer_without_assigned_manager_falls_back_to_all_admins(): void
    {
        $this->assertSame(
            [20, 21],
            $this->resolver->idsForLegacyFallback(null, [20, 21, 20])
        );
    }

    public function test_conversation_manager_targets_manager_all_admins_and_all_owners_without_duplicates(): void
    {
        $staff = new User();
        $staff->id = 10;
        $staff->role = User::ROLE_STAFF;

        $admin = new User();
        $admin->id = 20;
        $admin->role = User::ROLE_ADMIN;

        $owner = new User();
        $owner->id = 30;
        $owner->role = User::ROLE_OWNER;

        $this->assertSame(
            [10, 20, 21, 30, 31],
            $this->resolver->idsForConversationManager($staff, [20, 21, 10], [30, 31, 20])
        );

        $this->assertSame(
            [20, 21, 30, 31],
            $this->resolver->idsForConversationManager($admin, [20, 21], [30, 31, 20])
        );

        $this->assertSame(
            [30, 20, 21, 31],
            $this->resolver->idsForConversationManager($owner, [20, 21], [30, 31, 30])
        );
    }

}
