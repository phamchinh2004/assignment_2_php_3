<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\StatisticalController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\User\HomeController;
use App\Models\Conversation;
use App\Models\Rank;
use App\Models\User;
use App\Models\User_spin_progress;
use App\Models\Wallet_balance_history;
use App\Services\UserDepositService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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
            $table->string('avatar')->nullable();
            $table->string('password');
            $table->unsignedInteger('referral_code')->nullable()->unique();
            $table->string('role')->default(User::ROLE_MEMBER);
            $table->string('status')->default('inactivated');
            $table->foreignId('referrer_id')->nullable();
            $table->foreignId('rank_id')->nullable();
            $table->decimal('balance', 16, 6)->default(0);
            $table->decimal('frozen_balance', 16, 6)->default(0);
            $table->unsignedInteger('count_withdrawals')->default(0);
            $table->string('username_bank')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('transaction_password')->nullable();
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
            $table->decimal('initial_balance', 16, 6)->default(0);
            $table->decimal('balance_before', 16, 6)->nullable();
            $table->decimal('balance_after', 16, 6)->nullable();
            $table->string('type');
            $table->string('status')->default('processing');
            $table->string('transaction_type')->default('normal');
            $table->foreignId('by_user_id')->nullable();
            $table->foreignId('assigned_staff_id')->nullable();
            $table->string('username_bank')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->timestamps();
        });

        Schema::create('ranks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->double('commission_percentage')->default(0);
            $table->double('upgrade_fee')->default(0);
            $table->integer('spin_count')->default(1);
            $table->double('value')->default(0);
            $table->integer('maximum_number_of_withdrawals')->default(5);
            $table->double('maximum_withdrawal_amount')->default(1000);
            $table->timestamps();
        });

        Schema::create('user_spin_progresses', function (Blueprint $table) {
            $table->id();
            $table->integer('current_spin')->default(0);
            $table->foreignId('user_id');
            $table->foreignId('rank_id');
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
        $this->assertSame(1, $statistics['summary']['active_staff']);

        $this->actingAs($manager);
        $personal = app(StatisticalController::class)->getPersonalRevenueStats(
            Request::create('/test', 'GET', ['time_range' => '7_days'])
        )->getData(true);

        $this->assertTrue($personal['success']);
        $this->assertEquals(600, $personal['data']['overview_stats']['total_revenue']);
        $this->assertSame(3, $personal['data']['overview_stats']['deposit_count']);
    }

    public function test_deposit_revenue_is_locked_to_staff_assigned_when_each_deposit_is_created(): void
    {
        Event::fake();
        Queue::fake();

        $owner = $this->user(User::ROLE_OWNER, 100000, 'owner');
        $staffB = $this->user(User::ROLE_STAFF, 100001, 'staff_b');
        $staffC = $this->user(User::ROLE_STAFF, 100002, 'staff_c');
        $member = $this->user(User::ROLE_MEMBER, 200001, 'member', [
            'referrer_id' => $staffB->id,
        ]);

        $conversation = new Conversation([
            'user_id' => $member->id,
            'staff_id' => $staffB->id,
        ]);
        $conversation->public_id = (string) Str::uuid();
        $conversation->save();

        $depositService = app(UserDepositService::class);
        $depositService->deposit($member, 100, 'normal', $owner);

        $conversation->update(['staff_id' => $staffC->id]);
        $depositService->deposit($member, 200, 'normal', $owner);

        $histories = Wallet_balance_history::query()->orderBy('id')->get();
        $this->assertSame([$staffB->id, $staffC->id], $histories->pluck('assigned_staff_id')->all());
        $this->assertSame([$owner->id, $owner->id], $histories->pluck('by_user_id')->all());
        $this->assertSame($staffB->id, $member->fresh()->referrer_id);

        $statistics = app(StatisticalController::class)->getRevenueByStaff(Request::create('/test', 'GET', [
            'date_from' => now()->subDay()->format('Y-m-d'),
            'date_to' => now()->addDay()->format('Y-m-d'),
        ]))->getData(true);

        $byStaff = collect($statistics['table_data'])->keyBy('staff_id');
        $this->assertEquals(100, $byStaff[$staffB->id]['total_revenue']);
        $this->assertEquals(200, $byStaff[$staffC->id]['total_revenue']);
        $this->assertSame(1, $byStaff[$staffB->id]['total_transactions']);
        $this->assertSame(1, $byStaff[$staffC->id]['total_transactions']);
        $this->assertSame(2, $statistics['summary']['active_staff']);
        $this->assertSame(0, $statistics['summary']['legacy_transactions']);

        $detailB = app(StatisticalController::class)->getRevenueDetail(Request::create('/test', 'GET', [
            'staff_id' => $staffB->id,
            'date_from' => now()->subDay()->format('Y-m-d'),
            'date_to' => now()->addDay()->format('Y-m-d'),
        ]))->getData(true);
        $this->assertEquals(100, $detailB['statistics']['total_revenue']);
        $this->assertSame(1, $detailB['statistics']['total_transactions']);

        $chartC = app(StatisticalController::class)->getRevenueChart(Request::create('/test', 'GET', [
            'staff_id' => $staffC->id,
            'date_from' => now()->subDay()->format('Y-m-d'),
            'date_to' => now()->addDay()->format('Y-m-d'),
        ]))->getData(true);
        $this->assertEquals(200, $chartC['chart_data'][0]['total_revenue']);

        $this->actingAs($staffB);
        $personalB = app(StatisticalController::class)->getPersonalRevenueStats(
            Request::create('/test', 'GET', ['time_range' => '7_days'])
        )->getData(true);
        $this->assertEquals(100, $personalB['data']['overview_stats']['total_revenue']);

        $this->actingAs($staffC);
        $personalC = app(StatisticalController::class)->getPersonalRevenueStats(
            Request::create('/test', 'GET', ['time_range' => '7_days'])
        )->getData(true);
        $this->assertEquals(200, $personalC['data']['overview_stats']['total_revenue']);
    }

    public function test_withdrawal_is_locked_to_staff_assigned_when_request_is_created(): void
    {
        $staffB = $this->user(User::ROLE_STAFF, 100001, 'staff_b');
        $staffC = $this->user(User::ROLE_STAFF, 100002, 'staff_c');
        $rank = Rank::query()->create([
            'name' => 'Test',
            'commission_percentage' => 0,
            'spin_count' => 1,
            'value' => 0,
            'maximum_number_of_withdrawals' => 5,
            'maximum_withdrawal_amount' => 1000,
        ]);
        $member = $this->user(User::ROLE_MEMBER, 200001, 'member', [
            'referrer_id' => $staffB->id,
            'rank_id' => $rank->id,
            'balance' => 1000,
            'count_withdrawals' => 0,
            'username_bank' => 'Member',
            'bank_name' => 'Test Bank',
            'account_number' => '123456',
            'transaction_password' => password_hash('secret', PASSWORD_DEFAULT),
        ]);
        User_spin_progress::query()->create([
            'user_id' => $member->id,
            'rank_id' => $rank->id,
            'current_spin' => 2,
        ]);

        $conversation = new Conversation([
            'user_id' => $member->id,
            'staff_id' => $staffB->id,
        ]);
        $conversation->public_id = (string) Str::uuid();
        $conversation->save();

        $this->actingAs($member);
        $this->app->instance('request', Request::create('/handle-withdraw', 'POST', [
            'amount' => 50,
            'username_bank' => 'Member',
            'bank_name' => 'Test Bank',
            'account_number' => '123456',
            'transaction_password' => 'secret',
        ]));
        $firstResponse = app(HomeController::class)->handle_withdraw();
        $this->assertSame(200, $firstResponse->getData(true)['status']);

        $firstWithdrawal = Wallet_balance_history::query()->where('type', 'withdraw')->firstOrFail();
        $this->assertSame($staffB->id, $firstWithdrawal->assigned_staff_id);
        $firstWithdrawal->update(['status' => 'completed']);

        $conversation->update(['staff_id' => $staffC->id]);
        $this->app->instance('request', Request::create('/handle-withdraw', 'POST', [
            'amount' => 60,
            'username_bank' => 'Member',
            'bank_name' => 'Test Bank',
            'account_number' => '123456',
            'transaction_password' => 'secret',
        ]));
        $secondResponse = app(HomeController::class)->handle_withdraw();
        $this->assertSame(200, $secondResponse->getData(true)['status']);

        $secondWithdrawal = Wallet_balance_history::query()
            ->where('type', 'withdraw')
            ->orderByDesc('id')
            ->firstOrFail();
        $this->assertSame($staffC->id, $secondWithdrawal->assigned_staff_id);
        $secondWithdrawal->update(['status' => 'completed']);

        $this->actingAs($staffB);
        $personalB = app(StatisticalController::class)->getPersonalRevenueStats(
            Request::create('/test', 'GET', ['time_range' => '7_days'])
        )->getData(true);
        $this->assertEquals(50, $personalB['data']['overview_stats']['total_withdraw']);

        $this->actingAs($staffC);
        $personalC = app(StatisticalController::class)->getPersonalRevenueStats(
            Request::create('/test', 'GET', ['time_range' => '7_days'])
        )->getData(true);
        $this->assertEquals(60, $personalC['data']['overview_stats']['total_withdraw']);
    }

    public function test_customer_revenue_distribution_excludes_clone_accounts_from_other_slice(): void
    {
        $manager = $this->user(User::ROLE_STAFF, 100001, 'manager');
        $member = $this->user(User::ROLE_MEMBER, 200001, 'member', ['referrer_id' => $manager->id]);
        $clone = $this->user(User::ROLE_MEMBER, 200002, 'clone', [
            'referrer_id' => $manager->id,
            'clone_account' => true,
        ]);

        $this->deposit($member, 100);
        $this->deposit($clone, 900);

        $distribution = app(StatisticalController::class)->revenueDistribution(
            Request::create('/test', 'GET', [
                'start_date' => now()->subDay()->format('Y-m-d'),
                'end_date' => now()->addDay()->format('Y-m-d'),
            ])
        )->getData(true);

        $this->assertTrue($distribution['success']);
        $this->assertEquals(100, array_sum($distribution['data']['values']));
        $this->assertNotContains('Khác', $distribution['data']['labels']);
    }

    public function test_personal_revenue_seven_day_preset_contains_exactly_seven_calendar_days(): void
    {
        $staff = $this->user(User::ROLE_STAFF, 100001, 'staff');
        $this->actingAs($staff);

        $personal = app(StatisticalController::class)->getPersonalRevenueStats(
            Request::create('/test', 'GET', ['time_range' => '7_days'])
        )->getData(true);

        $this->assertCount(7, $personal['data']['daily_revenue']);

        $thirtyDays = app(StatisticalController::class)->getPersonalRevenueStats(
            Request::create('/test', 'GET', ['time_range' => '30_days'])
        )->getData(true);

        $this->assertCount(30, $thirtyDays['data']['daily_revenue']);
    }

    public function test_staff_management_deposit_totals_include_direct_team_revenue_for_admin_only(): void
    {
        $owner = $this->user(User::ROLE_OWNER, 100000, 'owner');
        $admin = $this->user(User::ROLE_ADMIN, 100001, 'admin', [
            'referrer_id' => $owner->id,
        ]);
        $staff = $this->user(User::ROLE_STAFF, 100002, 'staff', [
            'referrer_id' => $admin->id,
        ]);
        $otherStaff = $this->user(User::ROLE_STAFF, 100003, 'other_staff', [
            'referrer_id' => $owner->id,
        ]);

        $adminMember = $this->user(User::ROLE_MEMBER, 200001, 'admin_member', [
            'referrer_id' => $admin->id,
        ]);
        $staffMember = $this->user(User::ROLE_MEMBER, 200002, 'staff_member', [
            'referrer_id' => $staff->id,
        ]);
        $otherMember = $this->user(User::ROLE_MEMBER, 200003, 'other_member', [
            'referrer_id' => $otherStaff->id,
        ]);

        $this->deposit($adminMember, 25);
        $this->deposit($staffMember, 100);
        $this->deposit($otherMember, 70);

        $method = new \ReflectionMethod(StaffController::class, 'depositTotalsByStaff');
        $totals = $method->invoke(app(StaffController::class), collect([
            $admin->id,
            $staff->id,
            $otherStaff->id,
        ]));

        $this->assertEquals(125, $totals->get($admin->id));
        $this->assertEquals(100, $totals->get($staff->id));
        $this->assertEquals(70, $totals->get($otherStaff->id));
    }

    public function test_admin_statistics_only_include_own_and_direct_staff_customers_while_owner_sees_all(): void
    {
        $owner = $this->user(User::ROLE_OWNER, 110000, 'scope_owner');
        $adminA = $this->user(User::ROLE_ADMIN, 110001, 'scope_admin_a', ['referrer_id' => $owner->id]);
        $adminB = $this->user(User::ROLE_ADMIN, 110002, 'scope_admin_b', ['referrer_id' => $owner->id]);
        $staffA = $this->user(User::ROLE_STAFF, 110003, 'scope_staff_a', ['referrer_id' => $adminA->id]);
        $staffB = $this->user(User::ROLE_STAFF, 110004, 'scope_staff_b', ['referrer_id' => $adminB->id]);

        $adminCustomer = $this->user(User::ROLE_MEMBER, 210001, 'scope_admin_customer', ['referrer_id' => $adminA->id]);
        $staffCustomer = $this->user(User::ROLE_MEMBER, 210002, 'scope_staff_customer', ['referrer_id' => $staffA->id]);
        $otherCustomer = $this->user(User::ROLE_MEMBER, 210003, 'scope_other_customer', ['referrer_id' => $staffB->id]);

        $this->deposit($adminCustomer, 10);
        $this->deposit($staffCustomer, 20);
        $this->deposit($otherCustomer, 100);

        $period = [
            'start_date' => now()->subDay()->format('Y-m-d'),
            'end_date' => now()->addDay()->format('Y-m-d'),
        ];

        $this->actingAs($adminA);
        $overview = app(StatisticalController::class)
            ->revenueOverview(Request::create('/test', 'GET', $period))
            ->getData(true);
        $this->assertTrue($overview['success']);
        $this->assertEquals(30, $overview['data']['total_revenue']);
        $this->assertSame(2, $overview['data']['total_customers']);

        $staffList = app(StatisticalController::class)->getStaffList()->getData(true);
        $this->assertSame([$staffA->id], collect($staffList['data'])->pluck('id')->all());

        $byStaff = app(StatisticalController::class)
            ->getRevenueByStaff(Request::create('/test', 'GET', [
                'date_from' => $period['start_date'],
                'date_to' => $period['end_date'],
            ]))
            ->getData(true);
        $this->assertSame([$staffA->id], collect($byStaff['table_data'])->pluck('staff_id')->all());
        $this->assertEquals(20, $byStaff['table_data'][0]['total_revenue']);

        $this->actingAs($owner);
        $ownerOverview = app(StatisticalController::class)
            ->revenueOverview(Request::create('/test', 'GET', $period))
            ->getData(true);
        $this->assertEquals(130, $ownerOverview['data']['total_revenue']);
        $this->assertSame(3, $ownerOverview['data']['total_customers']);
    }

    public function test_customer_avatars_are_consistent_across_statistics_and_remain_team_scoped(): void
    {
        $owner = $this->user(User::ROLE_OWNER, 120000, 'avatar_owner');
        $admin = $this->user(User::ROLE_ADMIN, 120001, 'avatar_admin', ['referrer_id' => $owner->id]);
        $staff = $this->user(User::ROLE_STAFF, 120002, 'avatar_staff', ['referrer_id' => $admin->id]);
        $otherStaff = $this->user(User::ROLE_STAFF, 120003, 'avatar_other_staff');
        $customer = $this->user(User::ROLE_MEMBER, 220001, 'avatar_customer', [
            'referrer_id' => $staff->id,
        ]);
        $customer->forceFill(['avatar' => 'uploads/avatars/statistics.jpg'])->save();
        $defaultCustomer = $this->user(User::ROLE_MEMBER, 220002, 'avatar_default', ['referrer_id' => $staff->id]);
        $hiddenCustomer = $this->user(User::ROLE_MEMBER, 220003, 'avatar_hidden', [
            'referrer_id' => $otherStaff->id,
        ]);
        $hiddenCustomer->forceFill(['avatar' => 'uploads/avatars/hidden.jpg'])->save();
        $this->deposit($customer, 200);
        $this->deposit($defaultCustomer, 100);
        $this->deposit($hiddenCustomer, 900);
        $period = ['start_date' => now()->subDay()->format('Y-m-d'), 'end_date' => now()->addDay()->format('Y-m-d')];
        $controller = app(StatisticalController::class);
        $this->actingAs($admin);

        $expected = [
            $customer->id => asset('storage/uploads/avatars/statistics.jpg'),
            $defaultCustomer->id => asset('images/default-avatar-gray.svg'),
        ];
        foreach (['topCustomers', 'revenueDistribution'] as $method) {
            $response = $controller->$method(Request::create('/test', 'GET', $period))->getData(true);
            $this->assertTrue($response['success']);
            $this->assertSame($expected, collect($response['data']['customers'])->pluck('avatar_url', 'id')->all());
            $this->assertEquals([200, 100], $response['data']['values']);
        }
        $overview = $controller->revenueOverview(Request::create('/test', 'GET', $period))->getData(true);
        $this->assertSame($expected[$customer->id], $overview['data']['top_customer']['avatar_url']);
        $this->assertEquals(300, $overview['data']['total_revenue']);
        $detail = $controller->customerRevenueDetail(Request::create('/test', 'GET', $period))->getData(true);
        $this->assertSame($expected, collect($detail['data'])->pluck('avatar_url', 'user_id')->all());
        $userStats = $controller->getUserRevenueStats(Request::create('/test', 'GET', $period))->getData(true);
        $this->assertSame($expected, collect($userStats['data']['data'])->pluck('avatar_url', 'id')->all());
        $recent = (new \ReflectionMethod(StatisticalController::class, 'getRecentTransactions'))
            ->invoke($controller, now()->subDay(), now()->addDay());
        $this->assertEqualsCanonicalizing($expected, $recent->pluck('user.avatar_url', 'user.id')->all());
        $staffDetail = $controller->getRevenueDetail(Request::create('/test', 'GET', [
            'staff_id' => $staff->id, 'date_from' => $period['start_date'], 'date_to' => $period['end_date'],
        ]))->getData(true);
        $this->assertTrue($staffDetail['success']);
        $this->assertEqualsCanonicalizing($expected, collect($staffDetail['transactions'])->pluck('user.avatar_url', 'user.id')->all());

        $this->actingAs($staff);
        $personal = $controller->getPersonalTransactions(Request::create('/test'))->getData(true);
        $this->assertEqualsCanonicalizing($expected, collect($personal['data']['data'])->pluck('user.avatar_url', 'user.id')->all());
        $this->actingAs($owner);
        $all = $controller->topCustomers(Request::create('/test', 'GET', $period))->getData(true);
        $this->assertSame(asset('storage/uploads/avatars/hidden.jpg'), $all['data']['customers'][0]['avatar_url']);
        $this->assertCount(3, $all['data']['customers']);
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
            'assigned_staff_id' => $user->latestConversation()->value('staff_id') ?? $user->referrer_id,
        ]);
    }
}
