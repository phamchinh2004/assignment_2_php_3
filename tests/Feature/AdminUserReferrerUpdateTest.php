<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\StaffController;
use App\Models\User;
use App\Services\AuthorizationService;
use App\Services\PermissionRegistry;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminUserReferrerUpdateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('full_name')->nullable();
            $table->string('username')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('password')->nullable();
            $table->integer('referral_code')->nullable();
            $table->string('role');
            $table->string('status')->default('activated');
            $table->foreignId('referrer_id')->nullable();
            $table->foreignId('rank_id')->nullable();
            $table->decimal('balance', 18, 6)->default(0);
            $table->decimal('frozen_balance', 18, 6)->default(0);
            $table->string('username_bank')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('warehouse_area')->nullable();
            $table->text('warehouse_address')->nullable();
            $table->unsignedInteger('lucky_wheel_bonus_spins')->default(0);
            $table->boolean('clone_account')->default(false);
            $table->string('register_ip')->nullable();
            $table->timestamp('last_seen')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('manager_settings', function (Blueprint $table) {
            $table->id();
            $table->string('manager_name');
            $table->string('manager_code')->unique();
            $table->foreignId('parent_manager_setting_id')->nullable();
            $table->timestamps();
        });

        Schema::create('user_manager_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('manager_setting_id');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('user_spin_progresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('rank_id')->nullable();
            $table->unsignedInteger('current_spin')->default(0);
            $table->timestamps();
        });
    }

    public function test_user_with_change_referrer_permission_can_change_the_referrer(): void
    {
        $actor = $this->user(User::ROLE_STAFF);
        $newReferrer = $this->user(User::ROLE_STAFF);
        $member = $this->user(User::ROLE_MEMBER, ['referrer_id' => $actor->id]);
        $this->grant($actor, ['customers_update', 'customers_change_referrer']);

        $this->actingAs($actor)
            ->put(route('user.update', $member), $this->payload($member, $newReferrer->id))
            ->assertRedirect(route('user.index'));

        $this->assertSame($newReferrer->id, $member->fresh()->referrer_id);
    }

    public function test_editing_member_allows_phone_shared_with_another_user(): void
    {
        $actor = $this->user(User::ROLE_STAFF);
        $otherMember = $this->user(User::ROLE_MEMBER, ['referrer_id' => $actor->id]);
        $member = $this->user(User::ROLE_MEMBER, ['referrer_id' => $actor->id]);
        $this->grant($actor, ['customers_update']);

        $this->actingAs($actor)
            ->put(route('user.update', $member), [
                'full_name' => $member->full_name,
                'username' => $member->username,
                'email' => $member->email,
                'phone' => $otherMember->phone,
            ])
            ->assertRedirect(route('user.index'));

        $this->assertSame($otherMember->phone, $member->fresh()->phone);
    }

    public function test_user_without_change_referrer_permission_cannot_change_the_referrer(): void
    {
        $actor = $this->user(User::ROLE_STAFF);
        $newReferrer = $this->user(User::ROLE_STAFF);
        $member = $this->user(User::ROLE_MEMBER, ['referrer_id' => $actor->id]);
        $this->grant($actor, ['customers_update']);

        $this->actingAs($actor)
            ->putJson(route('user.update', $member), $this->payload($member, $newReferrer->id))
            ->assertForbidden();

        $this->assertSame($actor->id, $member->fresh()->referrer_id);
    }

    public function test_referrer_must_be_a_management_account(): void
    {
        $actor = $this->user(User::ROLE_STAFF);
        $member = $this->user(User::ROLE_MEMBER, ['referrer_id' => $actor->id]);
        $invalidReferrer = $this->user(User::ROLE_MEMBER, ['referrer_id' => $actor->id]);
        $this->grant($actor, ['customers_update', 'customers_change_referrer']);

        $this->actingAs($actor)
            ->putJson(route('user.update', $member), $this->payload($member, $invalidReferrer->id))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('referrer_id');

        $this->assertSame($actor->id, $member->fresh()->referrer_id);
    }

    public function test_admin_view_all_permission_still_cannot_edit_another_admin_team_customer(): void
    {
        $owner = $this->user(User::ROLE_OWNER);
        $adminA = $this->user(User::ROLE_ADMIN, ['referrer_id' => $owner->id]);
        $adminB = $this->user(User::ROLE_ADMIN, ['referrer_id' => $owner->id]);
        $staffA = $this->user(User::ROLE_STAFF, ['referrer_id' => $adminA->id]);
        $staffB = $this->user(User::ROLE_STAFF, ['referrer_id' => $adminB->id]);
        $ownCustomer = $this->user(User::ROLE_MEMBER, ['referrer_id' => $staffA->id]);
        $otherCustomer = $this->user(User::ROLE_MEMBER, ['referrer_id' => $staffB->id]);
        $this->grant($adminA, ['customers_update', 'customers_view_all']);

        $ownPayload = [
            'full_name' => 'Own team updated',
            'username' => $ownCustomer->username,
            'email' => $ownCustomer->email,
            'phone' => $ownCustomer->phone,
        ];
        $otherPayload = [
            'full_name' => 'Other team updated',
            'username' => $otherCustomer->username,
            'email' => $otherCustomer->email,
            'phone' => $otherCustomer->phone,
        ];

        $this->actingAs($adminA)
            ->put(route('user.update', $ownCustomer), $ownPayload)
            ->assertRedirect(route('user.index'));
        $this->assertSame('Own team updated', $ownCustomer->fresh()->full_name);

        $this->actingAs($adminA)
            ->putJson(route('user.update', $otherCustomer), $otherPayload)
            ->assertForbidden();
        $this->assertNotSame('Other team updated', $otherCustomer->fresh()->full_name);
    }

    public function test_permission_migration_groups_the_permission_without_granting_it(): void
    {
        $this->user(User::ROLE_ADMIN);
        $this->user(User::ROLE_STAFF);
        $migration = require database_path('migrations/2026_09_27_000001_add_customer_change_referrer_permission.php');

        $migration->up();
        $migration->up();

        $groupId = DB::table('manager_settings')
            ->where('manager_code', 'permission-group.customers')
            ->value('id');
        $permission = DB::table('manager_settings')
            ->where('manager_code', 'customers.change-referrer')
            ->first();

        $this->assertNotNull($groupId);
        $this->assertNotNull($permission);
        $this->assertSame((int) $groupId, (int) $permission->parent_manager_setting_id);
        $this->assertSame(
            0,
            DB::table('user_manager_settings')->where('manager_setting_id', $permission->id)->count()
        );
    }

    public function test_opening_permission_page_does_not_auto_grant_new_permission_to_existing_admin(): void
    {
        $owner = $this->user(User::ROLE_OWNER);
        $admin = $this->user(User::ROLE_ADMIN);
        $migration = require database_path('migrations/2026_09_27_000001_add_customer_change_referrer_permission.php');
        $migration->up();

        $this->actingAs($owner);
        app(StaffController::class)->edit_permissions(
            $admin->id,
            app(AuthorizationService::class),
            app(PermissionRegistry::class)
        );

        $permissionId = DB::table('manager_settings')
            ->where('manager_code', 'customers.change-referrer')
            ->value('id');

        $this->assertSame(
            0,
            (int) DB::table('user_manager_settings')
                ->where('user_id', $admin->id)
                ->where('manager_setting_id', $permissionId)
                ->value('is_active')
        );
    }

    public function test_staff_permission_page_only_shows_permissions_the_admin_can_delegate(): void
    {
        $owner = $this->user(User::ROLE_OWNER);
        $admin = $this->user(User::ROLE_ADMIN, ['referrer_id' => $owner->id]);
        $staff = $this->user(User::ROLE_STAFF, ['referrer_id' => $admin->id]);
        $this->grant($admin, [
            'staff_permissions_view',
            'staff_permissions_assign',
            'orders_view',
            'bug_reports_create',
            'chats_view_all',
        ]);

        $this->actingAs($admin);
        $view = app(StaffController::class)->edit_permissions(
            $staff->id,
            app(AuthorizationService::class),
            app(PermissionRegistry::class)
        );
        $groups = $view->getData()['reactPageBootstrap']['props']['permissionGroups'];
        $visibleCodes = collect($groups)
            ->flatMap(fn (array $group) => collect($group['permissions'])->pluck('code'))
            ->values()
            ->all();

        $this->assertContains('orders.view', $visibleCodes);
        $this->assertContains('bug-reports.create', $visibleCodes);
        $this->assertNotContains('orders.view-detail', $visibleCodes);
        $this->assertNotContains('staff-permissions.view', $visibleCodes);
        $this->assertNotContains('staff-permissions.assign', $visibleCodes);
        $this->assertNotContains('chats.view-all', $visibleCodes);
        $this->assertNotContains('bug-reports.view', $visibleCodes);
        $this->assertNotContains('bug-reports.view-detail', $visibleCodes);
        $this->assertNotContains('bug-reports.resolve', $visibleCodes);
    }

    public function test_staff_permission_endpoints_reject_permissions_the_admin_cannot_delegate(): void
    {
        $owner = $this->user(User::ROLE_OWNER);
        $admin = $this->user(User::ROLE_ADMIN, ['referrer_id' => $owner->id]);
        $staff = $this->user(User::ROLE_STAFF, ['referrer_id' => $admin->id]);
        $this->grant($admin, [
            'staff_permissions_view',
            'staff_permissions_assign',
            'orders_view',
        ]);

        $this->actingAs($admin);
        app(StaffController::class)->edit_permissions(
            $staff->id,
            app(AuthorizationService::class),
            app(PermissionRegistry::class)
        );

        $allowedId = $this->assignmentId($staff, 'orders.view');
        $missingId = $this->assignmentId($staff, 'orders.view-detail');
        $hiddenId = $this->assignmentId($staff, 'bug-reports.resolve');

        $this->postJson(route('staff.change.status.permission'), ['id' => $allowedId])
            ->assertOk()
            ->assertJson(['is_active' => true]);
        $this->assertTrue((bool) DB::table('user_manager_settings')->where('id', $allowedId)->value('is_active'));

        $this->postJson(route('staff.change.status.permission'), ['id' => $missingId])
            ->assertForbidden();
        $this->postJson(route('staff.change.status.permission'), ['id' => $hiddenId])
            ->assertForbidden();

        $this->post(route('staff.change.status.permissions'), [
            'staff_id' => $staff->id,
            'assignment_ids' => [$allowedId, $missingId],
            'is_active' => 0,
        ])->assertForbidden();

        $this->assertTrue((bool) DB::table('user_manager_settings')->where('id', $allowedId)->value('is_active'));
        $this->assertFalse((bool) DB::table('user_manager_settings')->where('id', $missingId)->value('is_active'));
        $this->assertFalse((bool) DB::table('user_manager_settings')->where('id', $hiddenId)->value('is_active'));
    }

    private function user(string $role, array $attributes = []): User
    {
        static $sequence = 0;
        $sequence++;

        return User::query()->create(array_merge([
            'full_name' => 'User '.$sequence,
            'username' => 'user'.$sequence.'name',
            'email' => 'user'.$sequence.'@example.test',
            'phone' => '090000'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
            'role' => $role,
            'status' => 'activated',
        ], $attributes));
    }

    private function grant(User $user, array $capabilities): void
    {
        foreach ($capabilities as $capability) {
            $permissionId = DB::table('manager_settings')->insertGetId([
                'manager_name' => $capability,
                'manager_code' => config('authorization.capabilities.'.$capability),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('user_manager_settings')->insert([
                'user_id' => $user->id,
                'manager_setting_id' => $permissionId,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function assignmentId(User $user, string $permissionCode): int
    {
        return (int) DB::table('user_manager_settings as ums')
            ->join('manager_settings as ms', 'ms.id', '=', 'ums.manager_setting_id')
            ->where('ums.user_id', $user->id)
            ->where('ms.manager_code', $permissionCode)
            ->value('ums.id');
    }

    private function payload(User $member, ?int $referrerId): array
    {
        return [
            'full_name' => $member->full_name,
            'username' => $member->username,
            'email' => $member->email,
            'phone' => $member->phone,
            'referrer_id' => $referrerId,
        ];
    }
}
