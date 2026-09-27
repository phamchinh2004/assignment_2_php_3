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
