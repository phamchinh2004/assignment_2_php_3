<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\OrderDistributionController;
use App\Models\Frozen_order;
use App\Models\User;
use App\Services\AdminOrderTransitionService;
use App\Services\AuthorizationService;
use App\Services\OrderStatusService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrderDistributionFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('queue.default', 'sync');
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('full_name')->nullable();
            $table->string('username')->nullable();
            $table->string('avatar')->nullable();
            $table->string('role');
            $table->string('status');
            $table->foreignId('referrer_id')->nullable();
            $table->timestamp('last_seen')->nullable();
            $table->decimal('balance', 16, 6)->default(0);
            $table->decimal('frozen_balance', 16, 6)->default(0);
            $table->decimal('todays_discount', 16, 6)->default(0);
            $table->timestamps();
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code')->nullable();
            $table->timestamps();
        });
        Schema::create('frozen_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('order_id');
            $table->foreignId('assigned_by')->nullable();
            $table->string('assignment_source')->nullable();
            $table->string('status')->nullable();
            $table->string('snapshot_order_code')->nullable();
            $table->string('snapshot_name')->nullable();
            $table->string('snapshot_image')->nullable();
            $table->decimal('snapshot_order_amount', 16, 6)->nullable();
            $table->decimal('snapshot_commission_amount', 16, 6)->nullable();
            $table->decimal('commission_percentage', 8, 3)->nullable();
            $table->decimal('custom_price', 16, 6)->nullable();
            $table->boolean('commission_paid')->default(false);
            $table->boolean('spun')->default(true);
            $table->boolean('is_frozen')->default(false);
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('preparing_at')->nullable();
            $table->timestamp('transit_at')->nullable();
            $table->timestamp('shipping_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->decimal('penalty_amount', 16, 6)->nullable();
            $table->decimal('settled_order_amount', 16, 6)->nullable();
            $table->decimal('settled_commission_amount', 16, 6)->nullable();
            $table->decimal('settled_penalty_amount', 16, 6)->nullable();
            $table->decimal('settled_refund_amount', 16, 6)->nullable();
            $table->string('settled_balance_destination')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('shipping_carrier')->nullable();
            $table->timestamps();
        });
        Schema::create('statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('display_name');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('status_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('frozen_order_id');
            $table->foreignId('status_id');
            $table->foreignId('changed_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('order_status_timings', function (Blueprint $table) {
            $table->id();
            $table->string('from_status');
            $table->string('to_status');
            $table->integer('min_time');
            $table->integer('max_time');
            $table->string('time_unit')->default('minutes');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('manager_settings', function (Blueprint $table) {
            $table->id();
            $table->string('manager_code');
            $table->timestamps();
        });
        Schema::create('user_manager_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('manager_setting_id');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('transaction_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->decimal('value', 16, 6);
            $table->string('type');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        foreach (['pending', 'confirmed', 'preparing', 'transit', 'shipping', 'delivered', 'completed', 'cancelled'] as $position => $status) {
            DB::table('statuses')->insert(['name' => $status, 'display_name' => $status, 'sort_order' => $position]);
        }
    }

    public function test_combined_filters_use_assignment_and_timestamp_data(): void
    {
        $assigner = $this->user(User::ROLE_OWNER);
        $recipient = $this->user(User::ROLE_MEMBER);
        $otherRecipient = $this->user(User::ROLE_MEMBER);
        $match = $this->frozenOrder($recipient, [
            'assigned_by' => $assigner->id,
            'assignment_source' => 'admin',
            'snapshot_order_code' => 'SEARCH-A',
        ]);
        $other = $this->frozenOrder($otherRecipient, [
            'assignment_source' => 'spin',
            'snapshot_order_code' => 'SEARCH-B',
            'status' => 'pending',
        ]);
        DB::table('frozen_orders')->where('id', $match->id)->update([
            'created_at' => '2026-09-21 11:00:00', 'updated_at' => '2026-09-23 10:00:00',
        ]);
        DB::table('frozen_orders')->where('id', $other->id)->update([
            'created_at' => '2026-09-18 11:00:00', 'updated_at' => '2026-09-18 11:00:00',
        ]);

        $request = Request::create('/admin/order-distributions', 'GET', [
            'q' => 'SEARCH', 'status' => 'confirmed', 'user_id' => $recipient->id,
            'assigned_by' => $assigner->id, 'source' => 'admin',
            'from' => '2026-09-20', 'to' => '2026-09-22',
            'updated_from' => '2026-09-23', 'updated_to' => '2026-09-23',
        ]);
        $view = app(OrderDistributionController::class)->index($request);
        $props = $view->getData()['reactPageBootstrap']['props'];
        $this->assertSame([$match->id], $props['frozenOrders']->pluck('id')->all());
    }

    public function test_distribution_list_serializes_recipient_avatar_urls(): void
    {
        $this->actingAs($this->user(User::ROLE_OWNER));
        $withAvatar = $this->user(User::ROLE_MEMBER);
        $withAvatar->forceFill(['avatar' => 'uploads/avatars/recipient.jpg'])->save();
        $withoutAvatar = $this->user(User::ROLE_MEMBER);
        $distribution = $this->frozenOrder($withAvatar);
        DB::table('status_orders')->insert([
            'frozen_order_id' => $distribution->id,
            'status_id' => DB::table('statuses')->where('name', 'confirmed')->value('id'),
            'changed_by' => $withAvatar->id, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->frozenOrder($withoutAvatar);

        $view = app(OrderDistributionController::class)->index(Request::create('/admin/order-distributions', 'GET'));
        $props = $view->getData()['reactPageBootstrap']['props'];
        $rows = collect($props['frozenOrders']->toArray()['data'])->keyBy('user_id');
        $this->assertSame(asset('storage/uploads/avatars/recipient.jpg'), $rows[$withAvatar->id]['user']['avatar_url']);
        $this->assertSame(asset('images/default-avatar-gray.svg'), $rows[$withoutAvatar->id]['user']['avatar_url']);
        $detail = app(OrderDistributionController::class)->show($distribution, app(AdminOrderTransitionService::class))->getData()['reactPageBootstrap']['props'];
        $this->assertSame(asset('storage/uploads/avatars/recipient.jpg'), $detail['frozenOrder']['user']['avatar_url']);
        $this->assertSame(asset('storage/uploads/avatars/recipient.jpg'), $detail['frozenOrder']->toArray()['status_orders'][0]['changed_by']['avatar_url']);
    }

    public function test_empty_filters_are_serialized_as_an_object_for_the_react_form(): void
    {
        $request = Request::create('/admin/order-distributions', 'GET');
        $view = app(OrderDistributionController::class)->index($request);
        $filters = $view->getData()['reactPageBootstrap']['props']['filters'];

        $this->assertSame('{}', json_encode($filters));
    }

    public function test_end_date_filters_work_without_start_dates(): void
    {
        $recipient = $this->user(User::ROLE_MEMBER);
        $order = $this->frozenOrder($recipient);
        DB::table('frozen_orders')->where('id', $order->id)->update([
            'created_at' => '2026-09-21 11:00:00', 'updated_at' => '2026-09-22 11:00:00',
        ]);

        $request = Request::create('/admin/order-distributions', 'GET', [
            'to' => '2026-09-21', 'updated_to' => '2026-09-22',
        ]);
        $view = app(OrderDistributionController::class)->index($request);
        $props = $view->getData()['reactPageBootstrap']['props'];
        $this->assertSame([$order->id], $props['frozenOrders']->pluck('id')->all());
    }

    public function test_admin_with_view_detail_permission_but_without_transition_permission_cannot_advance(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $recipient = $this->user(User::ROLE_MEMBER, ['referrer_id' => $admin->id]);
        $this->grant($admin, ['order_distributions_view_detail']);
        $order = $this->frozenOrder($recipient);

        $this->actingAs($admin)->postJson(route('order_distributions.transition', $order), $this->payload($order))
            ->assertForbidden();
        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertDatabaseCount('status_orders', 0);
    }

    public function test_admin_can_open_own_staff_customer_distribution_but_not_other_admin_team(): void
    {
        $owner = $this->user(User::ROLE_OWNER);
        $adminA = $this->user(User::ROLE_ADMIN, ['referrer_id' => $owner->id]);
        $adminB = $this->user(User::ROLE_ADMIN, ['referrer_id' => $owner->id]);
        $staffA = $this->user(User::ROLE_STAFF, ['referrer_id' => $adminA->id]);
        $staffB = $this->user(User::ROLE_STAFF, ['referrer_id' => $adminB->id]);
        $ownCustomer = $this->user(User::ROLE_MEMBER, ['referrer_id' => $staffA->id]);
        $otherCustomer = $this->user(User::ROLE_MEMBER, ['referrer_id' => $staffB->id]);
        $ownDistribution = $this->frozenOrder($ownCustomer);
        $otherDistribution = $this->frozenOrder($otherCustomer);
        $this->grant($adminA, ['order_distributions_view_detail']);

        $controller = app(OrderDistributionController::class);
        $workflow = app(AdminOrderTransitionService::class);

        $this->actingAs($adminA);
        $ownView = $controller->show($ownDistribution, $workflow);
        $this->assertSame($ownDistribution->id, $ownView->getData()['reactPageBootstrap']['props']['frozenOrder']->id);

        try {
            $controller->show($otherDistribution, $workflow);
            $this->fail('Admin must not access another admin team distribution.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->actingAs($owner);
        $ownerView = $controller->show($otherDistribution, $workflow);
        $this->assertSame($otherDistribution->id, $ownerView->getData()['reactPageBootstrap']['props']['frozenOrder']->id);
    }

    public function test_show_exposes_source_order_link_when_admin_can_view_order_detail(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $recipient = $this->user(User::ROLE_MEMBER, ['referrer_id' => $admin->id]);
        $this->grant($admin, ['order_distributions_view_detail', 'orders_view_detail']);
        $orderId = DB::table('orders')->insertGetId([
            'order_code' => 'SOURCE-001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $distribution = $this->frozenOrder($recipient, ['order_id' => $orderId]);

        $this->actingAs($admin);
        $view = app(OrderDistributionController::class)->show(
            $distribution,
            app(AdminOrderTransitionService::class),
            app(AuthorizationService::class)
        );

        $this->assertTrue($view->getData()['reactPageBootstrap']['props']['permissions']['viewOrder']);
    }

    public function test_transition_advances_only_one_step_and_records_the_admin(): void
    {
        $owner = $this->user(User::ROLE_OWNER);
        $order = $this->frozenOrder($this->user(User::ROLE_MEMBER));

        $this->actingAs($owner)->postJson(route('order_distributions.transition', $order), $this->payload($order))
            ->assertOk()->assertJsonPath('status', 'preparing');

        $this->assertSame('preparing', $order->fresh()->status);
        $this->assertDatabaseHas('status_orders', [
            'frozen_order_id' => $order->id,
            'status_id' => DB::table('statuses')->where('name', 'preparing')->value('id'),
            'changed_by' => $owner->id,
        ]);
        $this->assertDatabaseCount('status_orders', 1);
    }

    public function test_stale_version_returns_conflict_without_writing_history(): void
    {
        $owner = $this->user(User::ROLE_OWNER);
        $order = $this->frozenOrder($this->user(User::ROLE_MEMBER));
        $payload = $this->payload($order);
        $payload['expected_updated_at'] = '2020-01-01T00:00:00.000000Z';

        $this->actingAs($owner)->postJson(route('order_distributions.transition', $order), $payload)
            ->assertStatus(409)->assertJsonPath('conflict', true);
        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertDatabaseCount('status_orders', 0);
    }

    public function test_timing_prevents_early_manual_transition(): void
    {
        $owner = $this->user(User::ROLE_OWNER);
        $order = $this->frozenOrder($this->user(User::ROLE_MEMBER), ['confirmed_at' => now()->subMinute()]);

        $this->actingAs($owner)->postJson(route('order_distributions.transition', $order), $this->payload($order))
            ->assertStatus(409);
        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertDatabaseCount('status_orders', 0);
    }

    public function test_stale_queued_transition_cannot_overwrite_a_newer_status(): void
    {
        $owner = $this->user(User::ROLE_OWNER);
        $order = $this->frozenOrder($this->user(User::ROLE_MEMBER));
        $staleJobOrder = Frozen_order::query()->findOrFail($order->id);
        DB::table('frozen_orders')->where('id', $order->id)->update(['status' => 'preparing']);

        $this->assertFalse(OrderStatusService::changeStatusIfCurrent(
            $staleJobOrder, 'confirmed', 'preparing', 'Stale queued job', $owner->id
        ));
        $this->assertSame('preparing', $order->fresh()->status);
        $this->assertDatabaseCount('status_orders', 0);
    }

    public function test_completion_requires_complete_permission(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $this->grant($admin, ['order_distributions_transition']);
        $order = $this->frozenOrder($this->user(User::ROLE_MEMBER, ['referrer_id' => $admin->id]), [
            'status' => 'delivered', 'delivered_at' => now()->subDays(15),
        ]);

        $this->actingAs($admin)->postJson(route('order_distributions.transition', $order), $this->payload($order))
            ->assertForbidden();
        $this->assertSame('delivered', $order->fresh()->status);
    }

    public function test_shipping_transition_keeps_tracking_details_and_one_history_entry(): void
    {
        $owner = $this->user(User::ROLE_OWNER);
        $order = $this->frozenOrder($this->user(User::ROLE_MEMBER), [
            'status' => 'transit', 'transit_at' => now()->subDays(3),
        ]);

        $this->actingAs($owner)->postJson(route('order_distributions.transition', $order), $this->payload($order))
            ->assertOk()->assertJsonPath('status', 'shipping');

        $shipped = $order->fresh();
        $this->assertSame('shipping', $shipped->status);
        $this->assertNotNull($shipped->tracking_number);
        $this->assertNotNull($shipped->shipping_carrier);
        $this->assertDatabaseCount('status_orders', 1);
    }

    public function test_completion_uses_existing_settlement_and_does_not_credit_twice(): void
    {
        $owner = $this->user(User::ROLE_OWNER);
        $recipient = $this->user(User::ROLE_MEMBER);
        $order = $this->frozenOrder($recipient, [
            'status' => 'delivered',
            'delivered_at' => now()->subDays(15),
            'snapshot_order_amount' => 100,
            'commission_percentage' => 10,
        ]);
        $payload = $this->payload($order);

        $this->actingAs($owner)->postJson(route('order_distributions.transition', $order), $payload)
            ->assertOk()->assertJsonPath('status', 'completed');

        $completed = $order->fresh();
        $this->assertTrue((bool) $completed->commission_paid);
        $this->assertNotNull($completed->settled_at);
        $this->assertEqualsWithDelta(110, (float) $recipient->fresh()->balance, 0.001);
        $this->assertEqualsWithDelta(10, (float) $recipient->fresh()->todays_discount, 0.001);
        $this->assertDatabaseHas('transaction_histories', ['user_id' => $recipient->id, 'type' => 'profit']);

        $this->postJson(route('order_distributions.transition', $order), $payload)->assertStatus(409);
        $this->assertEqualsWithDelta(110, (float) $recipient->fresh()->balance, 0.001);
        $this->assertDatabaseCount('transaction_histories', 1);
    }

    private function user(string $role, array $attributes = []): User
    {
        return User::query()->create(array_merge([
            'full_name' => 'Test ' . $role,
            'username' => uniqid($role, true),
            'role' => $role,
            'status' => 'activated',
        ], $attributes));
    }

    private function frozenOrder(User $recipient, array $attributes = []): Frozen_order
    {
        return Frozen_order::query()->create(array_merge([
            'user_id' => $recipient->id,
            'order_id' => 1,
            'status' => 'confirmed',
            'confirmed_at' => now()->subMinutes(15),
            'snapshot_order_code' => 'TEST-' . uniqid(),
        ], $attributes));
    }

    private function payload(Frozen_order $order): array
    {
        return [
            'expected_status' => $order->status,
            'expected_updated_at' => $order->updated_at->toISOString(),
        ];
    }

    private function grant(User $user, array $capabilities): void
    {
        foreach ($capabilities as $capability) {
            $id = DB::table('manager_settings')->insertGetId([
                'manager_code' => config('authorization.capabilities.' . $capability),
            ]);
            DB::table('user_manager_settings')->insert([
                'user_id' => $user->id,
                'manager_setting_id' => $id,
                'is_active' => true,
            ]);
        }
    }
}
