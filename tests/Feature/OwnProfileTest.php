<?php

namespace Tests\Feature;

use App\Models\Manager_setting;
use App\Models\User;
use App\Models\User_manager_setting;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OwnProfileTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array',
        ]);
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('username')->unique();
            $table->string('email')->nullable()->unique();
            $table->string('password')->nullable();
            $table->string('role');
            $table->string('status')->default('activated');
            $table->unsignedBigInteger('referrer_id')->nullable();
            $table->double('balance')->default(0);
            $table->timestamp('last_seen')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('manager_settings', function (Blueprint $table) {
            $table->id();
            $table->string('manager_name');
            $table->string('manager_code')->unique();
            $table->unsignedBigInteger('parent_manager_setting_id')->nullable();
            $table->timestamps();
        });
        Schema::create('user_manager_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('manager_setting_id');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    public function test_admin_and_staff_require_an_active_profile_permission(): void
    {
        foreach ([User::ROLE_ADMIN, User::ROLE_STAFF] as $role) {
            $user = $this->user($role);
            $payload = ['full_name' => 'Tên mới', 'email' => $role.'-new@example.test'];

            $this->actingAs($user)->putJson(route('admin.account.update'), $payload)->assertForbidden();
            $this->assertSame('Tên ban đầu', $user->fresh()->full_name);

            $assignment = $this->assign($user, false);
            $this->actingAs($user)->putJson(route('admin.account.update'), $payload)->assertForbidden();

            $assignment->update(['is_active' => true]);
            $user->unsetRelation('user_manager_settings');
            $this->actingAs($user)->putJson(route('admin.account.update'), $payload)
                ->assertOk()->assertJsonPath('user.full_name', 'Tên mới');
            $this->assertSame($payload['email'], $user->fresh()->email);

            $assignment->update(['is_active' => false]);
            $user->unsetRelation('user_manager_settings');
            $this->actingAs($user)->putJson(route('admin.account.update'), $payload)->assertForbidden();
        }
    }

    public function test_owner_can_update_only_their_own_name_and_email(): void
    {
        $owner = $this->user(User::ROLE_OWNER);
        $other = $this->user(User::ROLE_ADMIN);
        $original = $owner->fresh()->only(['username', 'role', 'password', 'balance', 'referrer_id', 'status']);
        $otherOriginal = $other->only(['full_name', 'email', 'role']);

        $this->actingAs($owner)->putJson(route('admin.account.update'), [
            'full_name' => '  Nguyễn Văn An  ',
            'email' => '  owner-new@example.test  ',
            'id' => $other->id,
            'user_id' => $other->id,
            'role' => User::ROLE_MEMBER,
            'username' => 'hacked',
            'password' => 'changed-password',
            'balance' => 99999,
            'referrer_id' => $other->id,
            'status' => 'banned',
        ])->assertOk()->assertExactJson([
            'message' => 'Cập nhật thông tin tài khoản thành công.',
            'user' => ['full_name' => 'Nguyễn Văn An', 'email' => 'owner-new@example.test'],
        ]);

        $this->assertSame($original, $owner->fresh()->only(array_keys($original)));
        $this->assertSame($otherOriginal, $other->fresh()->only(array_keys($otherOriginal)));
    }

    public function test_validation_rejects_missing_invalid_and_duplicate_values(): void
    {
        $owner = $this->user(User::ROLE_OWNER);
        $other = $this->user(User::ROLE_ADMIN);
        $original = $owner->only(['full_name', 'email']);
        $cases = [
            [['full_name' => '   ', 'email' => ''], ['full_name', 'email']],
            [['full_name' => 'Tên mới', 'email' => 'invalid-email'], ['email']],
            [['full_name' => str_repeat('a', 256), 'email' => 'valid@example.test'], ['full_name']],
            [['full_name' => 'Tên mới', 'email' => str_repeat('a', 250).'@example.test'], ['email']],
            [['full_name' => 'Tên mới', 'email' => $other->email, 'id' => $other->id], ['email']],
        ];

        foreach ($cases as [$payload, $errors]) {
            $this->actingAs($owner)->putJson(route('admin.account.update'), $payload)
                ->assertUnprocessable()->assertJsonValidationErrors($errors);
            $this->assertSame($original, $owner->fresh()->only(['full_name', 'email']));
        }

        $this->actingAs($owner)->postJson(route('admin.account.update'), [
            '_method' => 'PUT', 'full_name' => 'Tên mới', 'email' => $owner->email,
        ])->assertOk();
    }

    public function test_members_and_guests_cannot_update_management_profiles(): void
    {
        $payload = ['full_name' => 'Tên mới', 'email' => 'new@example.test'];
        $this->putJson(route('admin.account.update'), $payload)->assertUnauthorized();

        $member = $this->user(User::ROLE_MEMBER);
        $this->assign($member, true);
        $this->actingAs($member)->putJson(route('admin.account.update'), $payload)->assertForbidden();
        $this->assertSame('Tên ban đầu', $member->fresh()->full_name);
    }

    public function test_header_places_the_permission_controlled_button_above_change_password(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $this->actingAs($admin);
        $lockedHtml = view('admin.layouts.header')->render();
        $this->assertMatchesRegularExpression('/data-target="#updateAccountModal"\s+data-permission="account.update-profile"\s+hidden/', $lockedHtml);

        $this->assign($admin, true);
        $admin->unsetRelation('user_manager_settings');
        $enabledHtml = view('admin.layouts.header')->render();
        $this->assertDoesNotMatchRegularExpression('/data-target="#updateAccountModal"\s+data-permission="account.update-profile"\s+hidden/', $enabledHtml);
        $this->assertLessThan(strpos($enabledHtml, 'data-target="#changePasswordModal"'), strpos($enabledHtml, 'data-target="#updateAccountModal"'));
        $this->assertStringContainsString('value="'.$admin->full_name.'"', $enabledHtml);
        $this->assertStringContainsString('value="'.$admin->email.'"', $enabledHtml);
        $this->assertStringContainsString('action="'.route('admin.account.update').'"', $enabledHtml);
    }

    public function test_migration_groups_existing_permission_and_preserves_all_assignments(): void
    {
        $permission = Manager_setting::create(['manager_code' => 'account.update-profile', 'manager_name' => 'Quyền cũ']);
        $admin = $this->user(User::ROLE_ADMIN);
        $staff = $this->user(User::ROLE_STAFF);
        $this->assign($admin, true);
        $this->assign($staff, false);
        $before = DB::table('user_manager_settings')->orderBy('id')->get()->toJson();

        $migration = require base_path('database/migrations/2026_10_03_000001_register_account_profile_permission.php');
        $migration->up();
        $migration->up();
        $migration->down();

        $group = Manager_setting::where('manager_code', 'permission-group.account')->firstOrFail();
        $this->assertNull($group->parent_manager_setting_id);
        $this->assertSame($group->id, (int) $permission->fresh()->parent_manager_setting_id);
        $this->assertSame('Cập nhật họ tên và email của bản thân', $permission->fresh()->manager_name);
        $this->assertSame($before, DB::table('user_manager_settings')->orderBy('id')->get()->toJson());
        $this->assertDatabaseCount('manager_settings', 2);
    }

    public function test_permission_screen_shows_new_permission_disabled_by_default(): void
    {
        $owner = $this->user(User::ROLE_OWNER);
        $staff = $this->user(User::ROLE_STAFF);
        $migration = require base_path('database/migrations/2026_10_03_000001_register_account_profile_permission.php');
        $migration->up();

        $this->assertDatabaseCount('user_manager_settings', 0);
        $response = $this->actingAs($owner)->getJson(route('staff.edit.permissions', $staff->id), ['X-React-Navigation' => '1']);
        $response->assertOk();
        $group = collect($response->json('props.permissionGroups'))->firstWhere('label', 'Tài khoản cá nhân');
        $this->assertNotNull($group);
        $this->assertSame('account.update-profile', $group['permissions'][0]['code']);
        $this->assertFalse($group['permissions'][0]['active']);
    }

    private function user(string $role): User
    {
        $username = uniqid($role.'_');

        return User::create([
            'full_name' => 'Tên ban đầu', 'username' => $username,
            'email' => $username.'@example.test', 'role' => $role,
            'status' => 'activated', 'balance' => 123,
        ]);
    }

    private function assign(User $user, bool $active): User_manager_setting
    {
        $permission = Manager_setting::firstOrCreate(
            ['manager_code' => 'account.update-profile'],
            ['manager_name' => 'Cập nhật họ tên và email của bản thân']
        );
        $assignment = User_manager_setting::create([
            'user_id' => $user->id, 'manager_setting_id' => $permission->id, 'is_active' => $active,
        ]);
        $user->unsetRelation('user_manager_settings');

        return $assignment;
    }
}
