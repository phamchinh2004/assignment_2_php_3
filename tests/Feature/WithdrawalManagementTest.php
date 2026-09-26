<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet_balance_history;
use App\Services\UserDepositService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WithdrawalManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('role');
            $table->string('status')->default('activated');
            $table->string('full_name')->nullable();
            $table->string('username')->nullable();
            $table->string('email')->nullable();
            $table->decimal('balance', 16, 6)->default(0);
            $table->decimal('frozen_balance', 16, 6)->default(0);
            $table->foreignId('referrer_id')->nullable();
            $table->timestamp('last_seen')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('manager_settings', function (Blueprint $table) {
            $table->id();
            $table->string('manager_name')->nullable();
            $table->string('manager_code');
            $table->foreignId('parent_manager_setting_id')->nullable();
            $table->timestamps();
        });

        Schema::create('user_manager_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('manager_setting_id');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('wallet_balance_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->decimal('value', 16, 6);
            $table->decimal('initial_balance', 16, 6)->default(0);
            $table->decimal('balance_before', 18, 6)->nullable();
            $table->decimal('balance_after', 18, 6)->nullable();
            $table->string('type');
            $table->string('status')->default('processing');
            $table->foreignId('by_user_id')->nullable();
            $table->string('username_bank')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('transaction_type')->default('normal');
            $table->timestamps();
        });

        Schema::create('frozen_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->decimal('custom_price', 16, 6)->nullable();
            $table->boolean('is_frozen')->default(false);
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    public function test_confirmation_requires_post_and_an_explicit_transaction_type(): void
    {
        $owner = $this->user(User::ROLE_OWNER);
        $withdrawal = $this->withdrawal($this->user(User::ROLE_MEMBER));

        $this->actingAs($owner)
            ->get(route('confirm.withdraw', $withdrawal))
            ->assertMethodNotAllowed();

        $this->actingAs($owner)
            ->post(route('confirm.withdraw', $withdrawal))
            ->assertSessionHasErrors('transaction_type');

        $this->assertSame('processing', $withdrawal->fresh()->status);
    }

    public function test_authorized_user_can_confirm_a_withdrawal_once(): void
    {
        $owner = $this->user(User::ROLE_OWNER);
        $withdrawal = $this->withdrawal($this->user(User::ROLE_MEMBER));

        $this->actingAs($owner)
            ->post(route('confirm.withdraw', $withdrawal), ['transaction_type' => 'normal'])
            ->assertSessionHas('success');

        $withdrawal->refresh();
        $this->assertSame('completed', $withdrawal->status);
        $this->assertSame('normal', $withdrawal->transaction_type);
        $this->assertSame($owner->id, $withdrawal->by_user_id);

        $this->actingAs($owner)
            ->post(route('confirm.withdraw', $withdrawal), ['transaction_type' => 'virtual_withdraw'])
            ->assertSessionHas('error');

        $this->assertSame('normal', $withdrawal->fresh()->transaction_type);
    }

    public function test_user_without_confirm_permission_cannot_confirm(): void
    {
        $staff = $this->user(User::ROLE_STAFF);
        $withdrawal = $this->withdrawal($this->user(User::ROLE_MEMBER, ['referrer_id' => $staff->id]));

        $this->actingAs($staff)
            ->post(route('confirm.withdraw', $withdrawal), ['transaction_type' => 'normal'])
            ->assertRedirect();

        $this->assertSame('processing', $withdrawal->fresh()->status);
    }

    public function test_cancelling_twice_refunds_the_balance_only_once(): void
    {
        $owner = $this->user(User::ROLE_OWNER);
        $member = $this->user(User::ROLE_MEMBER, ['balance' => 25]);
        $withdrawal = $this->withdrawal($member, ['value' => 100]);

        $this->actingAs($owner)
            ->post(route('cancel.withdraw', $withdrawal))
            ->assertSessionHas('success');

        $this->actingAs($owner)
            ->post(route('cancel.withdraw', $withdrawal))
            ->assertSessionHas('error');

        $this->assertEquals(125, $member->fresh()->balance);
        $this->assertSame('cancelled', $withdrawal->fresh()->status);
        $this->assertSame($owner->id, $withdrawal->fresh()->by_user_id);
    }

    public function test_deposit_records_immutable_balance_snapshots(): void
    {
        $actor = $this->user(User::ROLE_OWNER);
        $member = $this->user(User::ROLE_MEMBER, ['balance' => 75, 'frozen_balance' => 25]);

        $result = app(UserDepositService::class)->deposit($member, 50, 'normal', $actor);

        $this->assertSame(125.0, (float) $member->fresh()->balance);
        $this->assertSame(100.0, $result['history']->balance_before);
        $this->assertSame(150.0, $result['history']->balance_after);
    }

    private function user(string $role, array $attributes = []): User
    {
        return User::query()->create(array_merge([
            'role' => $role,
            'status' => 'activated',
            'balance' => 0,
        ], $attributes));
    }

    private function withdrawal(User $user, array $attributes = []): Wallet_balance_history
    {
        return Wallet_balance_history::query()->create(array_merge([
            'user_id' => $user->id,
            'value' => 50,
            'initial_balance' => $user->balance,
            'type' => 'withdraw',
            'status' => 'processing',
            'transaction_type' => 'normal',
        ], $attributes));
    }
}
