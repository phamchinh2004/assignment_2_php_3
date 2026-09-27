<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\StatisticalController;
use App\Models\Conversation;
use App\Models\User;
use App\Models\Wallet_balance_history;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReferralRevenueChainTest extends TestCase
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
            $table->string('username')->unique();
            $table->string('email')->unique()->nullable();
            $table->string('phone')->nullable();
            $table->string('password');
            $table->unsignedInteger('referral_code')->nullable()->unique();
            $table->string('role')->default(User::ROLE_MEMBER);
            $table->string('status')->default('inactivated');
            $table->foreignId('referrer_id')->nullable();
            $table->string('register_ip')->nullable();
            $table->boolean('clone_account')->default(false);
            $table->string('location_permission')->nullable();
            $table->decimal('location_latitude', 10, 7)->nullable();
            $table->decimal('location_longitude', 10, 7)->nullable();
            $table->decimal('location_accuracy', 12, 2)->nullable();
            $table->string('location_country_code', 2)->nullable();
            $table->string('location_country')->nullable();
            $table->string('location_city')->nullable();
            $table->timestamp('location_updated_at')->nullable();
            $table->string('approx_location_country_code', 2)->nullable();
            $table->string('approx_location_country')->nullable();
            $table->timestamp('approx_location_updated_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('user_id');
            $table->foreignId('staff_id')->nullable();
            $table->string('status')->default('open');
            $table->timestamps();
        });

        Schema::create('wallet_balance_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->decimal('value', 16, 6);
            $table->string('type');
            $table->string('status');
            $table->string('transaction_type')->default('normal');
            $table->foreignId('by_user_id')->nullable();
            $table->timestamps();
        });

        $this->withoutMiddleware(\App\Http\Middleware\VerifyTurnstile::class);
    }

    public function test_member_referral_chain_keeps_one_manager_and_rolls_revenue_up_to_that_manager(): void
    {
        $manager = $this->user(User::ROLE_STAFF, 100001, 'manager');
        $memberA = $this->user(User::ROLE_MEMBER, 200001, 'member_a', [
            'referrer_id' => $manager->id,
        ]);

        $this->post(route('check_referral_code'), ['referral_code' => $memberA->referral_code])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->post(route('registerdone'), $this->registrationData('member_b', 'memberb@example.com', '0911111111', $memberA->referral_code))
            ->assertRedirect(route('home'));

        $memberB = User::where('username', 'member_b')->firstOrFail();
        $this->assertSame($manager->id, $memberB->referrer_id);
        $this->assertSame($manager->id, Conversation::where('user_id', $memberB->id)->value('staff_id'));

        auth()->logout();

        $this->post(route('registerdone'), $this->registrationData('member_c', 'memberc@example.com', '0922222222', $memberB->referral_code))
            ->assertRedirect(route('home'));

        $memberC = User::where('username', 'member_c')->firstOrFail();
        $this->assertSame($manager->id, $memberC->referrer_id);
        $this->assertSame($manager->id, Conversation::where('user_id', $memberC->id)->value('staff_id'));

        $this->deposit($memberA, 100);
        $this->deposit($memberB, 200);
        $this->deposit($memberC, 300);

        $statistics = app(StatisticalController::class)->getRevenueByStaff(Request::create('/test', 'GET', [
            'staff_id' => $manager->id,
            'date_from' => now()->subDay()->format('Y-m-d'),
            'date_to' => now()->addDay()->format('Y-m-d'),
        ]))->getData(true);

        $this->assertTrue($statistics['success']);
        $this->assertSame(3, $statistics['table_data'][0]['invited_users']);
        $this->assertSame(3, $statistics['table_data'][0]['total_transactions']);
        $this->assertEquals(600, $statistics['table_data'][0]['total_revenue']);

        $this->actingAs($manager);
        $personal = app(StatisticalController::class)->getPersonalRevenueStats(
            Request::create('/test', 'GET', ['time_range' => '7_days'])
        )->getData(true);

        $this->assertTrue($personal['success']);
        $this->assertEquals(600, $personal['data']['overview_stats']['total_revenue']);
        $this->assertSame(3, $personal['data']['overview_stats']['deposit_count']);
    }

    private function user(string $role, int $referralCode, string $username, array $attributes = []): User
    {
        return User::query()->create(array_merge([
            'full_name' => $username,
            'username' => $username,
            'email' => $username . '@example.com',
            'phone' => '0900000000',
            'password' => 'password',
            'referral_code' => $referralCode,
            'role' => $role,
            'status' => 'activated',
        ], $attributes));
    }

    private function registrationData(string $username, string $email, string $phone, int $referralCode): array
    {
        return [
            'full_name' => $username,
            'username' => $username,
            'email' => $email,
            'phone' => $phone,
            'password' => 'secret123',
            'referral_code' => $referralCode,
            'location_permission' => 'denied',
        ];
    }

    private function deposit(User $user, float $value): void
    {
        Wallet_balance_history::query()->create([
            'user_id' => $user->id,
            'value' => $value,
            'type' => 'deposit',
            'status' => 'completed',
            'transaction_type' => 'normal',
        ]);
    }
}
