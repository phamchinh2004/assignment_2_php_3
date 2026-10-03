<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\LuckyWheelRewardController;
use App\Http\Controllers\Admin\OrderReportController;
use App\Models\Frozen_order;
use App\Models\LuckyWheelSpin;
use App\Models\OrderReport;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AdminTeamScopeModulesTest extends TestCase
{
    public function createApplication(): Application
    {
        $app = require __DIR__ . '/../../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    protected function setUp(): void
    {
        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('Enable pdo_sqlite to run the isolated admin team-scope tests.');
        }

        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('database.connections.sqlite.foreign_key_constraints', false);
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('username')->unique();
            $table->string('avatar')->nullable();
            $table->string('role');
            $table->string('status')->default('activated');
            $table->unsignedBigInteger('referrer_id')->nullable();
            $table->timestamps();
        });
        Schema::create('manager_settings', function (Blueprint $table) {
            $table->id();
            $table->string('manager_name')->nullable();
            $table->string('manager_code');
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
        Schema::create('lucky_wheel_spins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('prize')->nullable();
            $table->unsignedTinyInteger('prize_index')->nullable();
            $table->string('spin_type')->nullable();
            $table->string('reward_type');
            $table->decimal('reward_amount', 15, 2)->nullable();
            $table->string('reward_status');
            $table->string('approval_method')->nullable();
            $table->unsignedBigInteger('handled_by')->nullable();
            $table->timestamp('handled_at')->nullable();
            $table->unsignedBigInteger('wallet_balance_history_id')->nullable();
            $table->date('spin_date')->nullable();
            $table->timestamps();
        });
        Schema::create('lucky_wheel_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('auto_approve_rewards')->default(false);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id')->nullable();
            $table->string('order_code')->nullable();
            $table->timestamps();
        });
        Schema::create('frozen_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->string('snapshot_order_code')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
        Schema::create('order_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('frozen_order_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('reported_by');
            $table->text('reason')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->text('resolved_note')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_lucky_wheel_rewards_are_limited_to_admin_team_while_owner_sees_all(): void
    {
        [$owner, $adminA, , $staffA, $staffB] = $this->managementTree();
        $directCustomer = $this->user(User::ROLE_MEMBER, $adminA->id);
        $staffCustomer = $this->user(User::ROLE_MEMBER, $staffA->id);
        $otherCustomer = $this->user(User::ROLE_MEMBER, $staffB->id);
        $directCustomer->forceFill(['avatar' => 'uploads/avatars/reward-customer.jpg'])->save();

        foreach ([$directCustomer, $staffCustomer, $otherCustomer] as $customer) {
            LuckyWheelSpin::query()->create([
                'user_id' => $customer->id,
                'prize' => '$2',
                'reward_type' => LuckyWheelSpin::REWARD_CASH,
                'reward_amount' => 2,
                'reward_status' => LuckyWheelSpin::STATUS_PENDING,
                'spin_date' => today(),
            ]);
        }

        $this->actingAs($adminA);
        $adminView = app(LuckyWheelRewardController::class)->index($this->requestFor($adminA));
        $adminProps = $adminView->getData()['reactPageBootstrap']['props'];
        $this->assertEqualsCanonicalizing(
            [$directCustomer->id, $staffCustomer->id],
            $adminProps['rewards']->getCollection()->pluck('user_id')->all()
        );
        $this->assertSame(2, $adminProps['counts']['total']);
        $serializedRewards = collect($adminProps['rewards']->toArray()['data'])->keyBy('user_id');
        $this->assertSame(asset('storage/uploads/avatars/reward-customer.jpg'), $serializedRewards[$directCustomer->id]['user']['avatar_url']);
        $this->assertSame(asset('images/default-avatar-gray.svg'), $serializedRewards[$staffCustomer->id]['user']['avatar_url']);

        $otherSpin = LuckyWheelSpin::query()->where('user_id', $otherCustomer->id)->firstOrFail();
        try {
            app(LuckyWheelRewardController::class)->reject($otherSpin, app(\App\Services\LuckyWheelRewardService::class));
            $this->fail('Admin must not manage another admin team reward.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->actingAs($owner);
        $ownerView = app(LuckyWheelRewardController::class)->index($this->requestFor($owner));
        $ownerProps = $ownerView->getData()['reactPageBootstrap']['props'];
        $this->assertSame(3, $ownerProps['rewards']->total());
    }

    public function test_order_reports_are_limited_to_admin_team_and_other_team_detail_is_forbidden(): void
    {
        [$owner, $adminA, , $staffA, $staffB] = $this->managementTree();
        $directCustomer = $this->user(User::ROLE_MEMBER, $adminA->id);
        $directCustomer->forceFill(['avatar' => 'uploads/avatars/report-customer.jpg'])->save();
        $staffCustomer = $this->user(User::ROLE_MEMBER, $staffA->id);
        $otherCustomer = $this->user(User::ROLE_MEMBER, $staffB->id);

        $ownReports = collect([$directCustomer, $staffCustomer])->map(fn (User $customer) => $this->reportFor($customer));
        $otherReport = $this->reportFor($otherCustomer);

        $this->actingAs($adminA);
        $adminView = app(OrderReportController::class)->index($this->requestFor($adminA));
        $adminProps = $adminView->getData()['reactPageBootstrap']['props'];
        $this->assertEqualsCanonicalizing(
            $ownReports->pluck('id')->all(),
            $adminProps['reports']->getCollection()->pluck('id')->all()
        );
        $rows = collect($adminProps['reports']->toArray()['data'])->keyBy('reported_by');
        $this->assertSame(asset('storage/uploads/avatars/report-customer.jpg'), $rows[$directCustomer->id]['reporter']['avatar_url']);
        $this->assertSame(asset('storage/uploads/avatars/report-customer.jpg'), $rows[$directCustomer->id]['frozen_order']['user']['avatar_url']);
        $this->assertSame(asset('images/default-avatar-gray.svg'), $rows[$staffCustomer->id]['reporter']['avatar_url']);

        try {
            app(OrderReportController::class)->show($otherReport);
            $this->fail('Admin must not open another admin team report.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->actingAs($owner);
        $ownerView = app(OrderReportController::class)->index($this->requestFor($owner));
        $ownerProps = $ownerView->getData()['reactPageBootstrap']['props'];
        $this->assertSame(3, $ownerProps['reports']->total());
    }

    private function managementTree(): array
    {
        $owner = $this->user(User::ROLE_OWNER);
        $adminA = $this->user(User::ROLE_ADMIN, $owner->id);
        $adminB = $this->user(User::ROLE_ADMIN, $owner->id);
        $staffA = $this->user(User::ROLE_STAFF, $adminA->id);
        $staffB = $this->user(User::ROLE_STAFF, $adminB->id);

        return [$owner, $adminA, $adminB, $staffA, $staffB];
    }

    private function user(string $role, ?int $referrerId = null): User
    {
        return User::query()->create([
            'full_name' => 'Test ' . $role,
            'username' => uniqid($role . '_', true),
            'role' => $role,
            'status' => 'activated',
            'referrer_id' => $referrerId,
        ]);
    }

    private function reportFor(User $customer): OrderReport
    {
        $frozenOrder = Frozen_order::query()->create([
            'user_id' => $customer->id,
            'snapshot_order_code' => 'TEST-' . $customer->id,
            'status' => 'pending',
        ]);

        return OrderReport::query()->create([
            'frozen_order_id' => $frozenOrder->id,
            'reported_by' => $customer->id,
            'status' => 'pending',
        ]);
    }

    private function requestFor(User $actor): Request
    {
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $actor);

        return $request;
    }
}
