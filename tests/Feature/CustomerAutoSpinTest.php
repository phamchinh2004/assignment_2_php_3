<?php

namespace Tests\Feature;

use App\Jobs\PrepareOrder;
use App\Models\Frozen_order;
use App\Models\Order;
use App\Models\User;
use App\Models\User_spin_progress;
use App\Services\CustomerAutoSpinService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerAutoSpinTest extends TestCase
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
            $table->boolean('is_frozen')->default(true);
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
            $table->string('manager_code')->unique();
            $table->string('manager_name')->nullable();
            $table->unsignedBigInteger('parent_manager_setting_id')->nullable();
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

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('rank_id')->nullable();
            $table->unsignedInteger('distribution_today')->default(0);
            $table->boolean('clone_account')->default(false);
        });
        Schema::create('ranks', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Rank');
            $table->unsignedInteger('spin_count')->default(60);
        });
        Schema::create('user_spin_progresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('rank_id');
            $table->unsignedInteger('current_spin')->default(0);
            $table->timestamps();
        });
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
        });
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('staff_id')->nullable();
            $table->string('public_id')->nullable();
            $table->timestamps();
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('rank_id');
            $table->unsignedBigInteger('partner_id')->nullable();
            $table->unsignedInteger('index');
            $table->string('name')->default('Product');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('price', 16, 6)->default(10);
            $table->decimal('commission_percentage', 8, 3)->default(5);
        });
        Schema::table('frozen_orders', function (Blueprint $table) {
            $table->unsignedInteger('snapshot_order_index')->nullable();
            $table->unsignedInteger('snapshot_quantity')->nullable();
            $table->decimal('snapshot_unit_price', 16, 6)->nullable();
            foreach (['snapshot_payment_method', 'snapshot_partner_name', 'snapshot_api',
                'snapshot_customer_name', 'snapshot_customer_phone', 'snapshot_customer_address',
                'snapshot_customer_note', 'snapshot_source'] as $column) {
                $table->string($column)->nullable();
            }
            $table->boolean('snapshot_is_paid')->nullable();
            $table->timestamp('snapshot_captured_at')->nullable();
            $table->timestamp('order_date')->nullable();
            $table->timestamp('processing_started_at')->nullable();
            $table->unsignedInteger('processing_time_limit')->default(24);
        });
        DB::table('ranks')->insert(['id' => 1, 'spin_count' => 60]);
        for ($index = 1; $index <= 60; $index++) {
            DB::table('orders')->insert(['rank_id' => 1, 'index' => $index, 'order_code' => 'ORDER-'.$index]);
        }
        Queue::fake();

        foreach (['pending', 'confirmed', 'preparing', 'transit', 'shipping', 'delivered', 'completed', 'cancelled'] as $position => $status) {
            DB::table('statuses')->insert(['name' => $status, 'display_name' => $status, 'sort_order' => $position]);
        }
    }

    public function test_runs_from_five_to_forty_with_real_snapshots_debits_history_and_jobs(): void
    {
        $actor = $this->operator();
        $member = $this->member($actor);
        $this->actingAs($actor);

        for ($spin = 5; $spin < 40; $spin++) {
            $this->postJson(route('user.auto-spin.step', $member), $this->payload($member, 40))
                ->assertOk()->assertJsonPath('current_spin', $spin + 1)
                ->assertJsonPath('done', $spin === 39)->assertJsonPath('pending_order_id', null);
        }

        $this->assertEquals(650, $member->fresh()->balance);
        $this->assertEquals(35, $member->fresh()->distribution_today);
        $this->assertSame(35, Frozen_order::where('user_id', $member->id)->count());
        $this->assertSame(35, DB::table('transaction_histories')->where('user_id', $member->id)->count());
        $this->assertSame(70, DB::table('status_orders')->where('changed_by', $member->id)->count());
        $this->assertSame(0, DB::table('status_orders')->where('changed_by', $actor->id)->count());
        $this->assertSame(35, DB::table('status_orders')->where('notes', 'Người dùng nhận đơn hàng')->count());
        $this->assertSame(35, DB::table('status_orders')->where('notes', 'Người dùng xác nhận đơn hàng')->count());
        $last = Frozen_order::where('user_id', $member->id)->latest('id')->first();
        $this->assertSame('confirmed', $last->status);
        $this->assertSame(40, (int) $last->snapshot_order_index);
        $this->assertSame('ORDER-40', $last->snapshot_order_code);
        $this->assertEquals(10, $last->snapshot_order_value);
        $this->assertEquals(0.5, $last->snapshot_commission_value);
        $this->assertFalse((bool) $last->commission_paid);
        Queue::assertPushed(PrepareOrder::class, 35);
        Queue::assertPushed(PrepareOrder::class, fn ($job) => $job->delay->isFuture());

        // A retry at the target is a no-op, with no order 41 and no additional debit.
        $this->postJson(route('user.auto-spin.step', $member), $this->payload($member, 40))
            ->assertOk()->assertJsonPath('done', true);
        $this->assertSame(35, Frozen_order::count());
        $this->assertEquals(650, $member->fresh()->balance);
    }

    public function test_user_manual_flow_still_uses_the_same_receive_and_confirmation(): void
    {
        $member = $this->member($this->operator());
        $received = $this->actingAs($member)->getJson(route('check_frozen_order'))
            ->assertOk()->assertJsonPath('status', 200);
        $id = $received->json('frozen_id');
        $this->postJson(route('order.confirm', $id))->assertOk()->assertJsonPath('status', 200);
        $this->assertSame('confirmed', Frozen_order::find($id)->status);
        $this->assertEquals(990, $member->fresh()->balance);
        $this->assertSame(2, DB::table('status_orders')->where('changed_by', $member->id)->count());
        $this->assertDatabaseHas('status_orders', ['frozen_order_id' => $id, 'changed_by' => $member->id, 'notes' => 'Người dùng nhận đơn hàng']);
        $this->assertDatabaseHas('status_orders', ['frozen_order_id' => $id, 'changed_by' => $member->id, 'notes' => 'Người dùng xác nhận đơn hàng']);
        Queue::assertPushed(PrepareOrder::class, 1);
        $this->postJson(route('order.confirm', $id))->assertJsonPath('status', 400);
        $this->assertSame(1, DB::table('transaction_histories')->count());
    }

    public function test_admin_and_staff_without_active_permission_cannot_read_or_run(): void
    {
        foreach ([User::ROLE_ADMIN, User::ROLE_STAFF] as $role) {
            $actor = $this->operator(false, $role);
            $member = $this->member($actor);
            $this->actingAs($actor)->getJson(route('user.auto-spin.state', $member))->assertForbidden();
            $this->postJson(route('user.auto-spin.step', $member), $this->payload($member))->assertForbidden();
        }
        $this->assertSame(0, Frozen_order::count());
    }

    public function test_view_all_does_not_allow_automation_outside_own_customer_scope(): void
    {
        $actor = $this->operator(true, User::ROLE_ADMIN);
        $member = $this->member($this->operator(true, User::ROLE_ADMIN));
        $this->grant($actor, 'customers.view-all');
        $this->actingAs($actor)->getJson(route('user.auto-spin.state', $member))->assertForbidden();
        $this->postJson(route('user.auto-spin.step', $member), $this->payload($member))->assertForbidden();
        $this->assertSame(0, Frozen_order::count());
    }

    public function test_customer_list_exposes_auto_spin_only_with_permission(): void
    {
        foreach ([false, true] as $active) {
            $actor = $this->operator($active);
            $member = $this->member($actor);
            $this->grant($actor, 'customers.view');
            $this->actingAs($actor)->getJson(route('user.index'), ['X-React-Navigation' => '1'])
                ->assertOk()->assertJsonPath('props.permissions.autoSpin', $active)
                ->assertJsonPath('props.users.0.can_auto_spin', $active)
                ->assertJsonPath('props.users.0.current_spin', 5)
                ->assertJsonPath('props.users.0.total_spins', 60);
        }
    }

    public function test_customer_list_progress_uses_current_rank_and_handles_missing_progress(): void
    {
        $actor = $this->operator();
        $member = $this->member($actor);
        $this->grant($actor, 'customers.view');
        DB::table('ranks')->where('id', 1)->update(['spin_count' => 50]);
        User_spin_progress::where('user_id', $member->id)->update(['current_spin' => 4]);

        $this->actingAs($actor)->getJson(route('user.index'), ['X-React-Navigation' => '1'])
            ->assertOk()->assertJsonPath('props.users.0.current_spin', 4)
            ->assertJsonPath('props.users.0.total_spins', 50);

        User_spin_progress::where('user_id', $member->id)->delete();
        $this->getJson(route('user.index'), ['X-React-Navigation' => '1'])
            ->assertOk()->assertJsonPath('props.users.0.current_spin', 0)
            ->assertJsonPath('props.users.0.total_spins', 50);

        $member->update(['rank_id' => null]);
        $this->getJson(route('user.index'), ['X-React-Navigation' => '1'])
            ->assertOk()->assertJsonPath('props.users.0.current_spin', 0)
            ->assertJsonPath('props.users.0.total_spins', 0);
    }

    public function test_customer_list_exposes_whether_customer_has_an_active_penalized_order(): void
    {
        $actor = $this->operator();
        $member = $this->member($actor);
        $this->grant($actor, 'customers.view');

        $frozenOrderId = DB::table('frozen_orders')->insertGetId([
            'user_id' => $member->id,
            'order_id' => 1,
            'custom_price' => 200,
            'penalty_amount' => 25,
            'is_frozen' => false,
            'spun' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($actor)->getJson(route('user.index'), ['X-React-Navigation' => '1'])
            ->assertOk()
            ->assertJsonPath('props.users.0.has_penalized_order', false);

        DB::table('frozen_orders')->where('id', $frozenOrderId)->update(['is_frozen' => true]);

        $this->getJson(route('user.index'), ['X-React-Navigation' => '1'])
            ->assertOk()
            ->assertJsonPath('props.users.0.has_penalized_order', true);
    }

    public function test_state_is_read_only_and_automation_only_targets_customers(): void
    {
        $actor = $this->operator();
        $member = $this->member($actor);
        $this->actingAs($actor)->getJson(route('user.auto-spin.state', $member))->assertOk()
            ->assertJsonPath('current_spin', 5)->assertJsonPath('total_spins', 60)
            ->assertJsonPath('pending_order_id', null);
        $this->getJson(route('user.auto-spin.state', $actor))->assertNotFound();
        $this->postJson(route('user.auto-spin.step', $actor), $this->payload($member))->assertNotFound();
        $this->assertSame(0, Frozen_order::count());
        $this->assertEquals(1000, $member->fresh()->balance);
    }

    public function test_new_permission_is_registered_without_assigning_it_to_admin_or_staff(): void
    {
        User::create(['role' => User::ROLE_ADMIN, 'status' => 'activated']);
        User::create(['role' => User::ROLE_STAFF, 'status' => 'activated']);
        $migration = require database_path('migrations/2026_10_04_000001_add_customer_auto_spin_permission.php');
        $migration->up();
        $permission = DB::table('manager_settings')->where('manager_code', 'customers.auto-spin')->first();
        $this->assertNotNull($permission);
        $this->assertSame(0, DB::table('user_manager_settings')->count());
        $group = collect(app(\App\Services\PermissionRegistry::class)->settingGroups())
            ->first(fn ($group) => $group['root']->manager_code === 'permission-group.customers');
        $this->assertTrue($group['settings']->contains(fn ($setting) => $setting->manager_code === 'customers.auto-spin'));
    }

    public function test_target_must_be_an_integer_in_current_rank_range(): void
    {
        $actor = $this->operator();
        $member = $this->member($actor);
        foreach ([4, 61, 6.5, 0] as $target) {
            $this->actingAs($actor)->postJson(route('user.auto-spin.step', $member), $this->payload($member, $target))
                ->assertUnprocessable()->assertJsonValidationErrors('target_spin');
        }
        $this->assertSame(0, Frozen_order::count());
        $this->assertEquals(5, $member->user_spin_progress->current_spin);
    }

    public function test_insufficient_funds_stops_at_received_order_then_retries_without_an_extra_spin(): void
    {
        $actor = $this->operator();
        $member = $this->member($actor, ['balance' => 15]);
        $this->actingAs($actor);
        $this->postJson(route('user.auto-spin.step', $member), $this->payload($member, 7))->assertOk();
        $this->postJson(route('user.auto-spin.step', $member), $this->payload($member, 7))
            ->assertStatus(400)->assertJsonPath('current_spin', 7)->assertJsonPath('done', false);
        $pending = Frozen_order::where('status', 'pending')->first();
        $this->assertEquals(5, $member->fresh()->balance);
        $this->assertSame(2, Frozen_order::count());
        $this->assertSame(1, DB::table('transaction_histories')->count());
        $member->update(['balance' => 50]);
        $this->postJson(route('user.auto-spin.step', $member), $this->payload($member, 7))
            ->assertOk()->assertJsonPath('done', true)->assertJsonPath('confirmed_order_id', $pending->id);
        $this->assertSame(2, Frozen_order::count());
        $this->assertEquals(40, $member->fresh()->balance);
    }

    public function test_high_value_order_keeps_frozen_wallet_and_stops_when_funds_are_insufficient(): void
    {
        $actor = $this->operator();
        $member = $this->member($actor, ['balance' => 100]);
        $high = Frozen_order::snapshotFromOrder(Order::where('index', 6)->first(), [
            'user_id' => $member->id, 'custom_price' => 200, 'commission_percentage' => 10,
            'spun' => false, 'status' => null, 'assignment_source' => 'admin',
        ]);
        $this->actingAs($actor)->postJson(route('user.auto-spin.step', $member), $this->payload($member, 40))
            ->assertStatus(400)->assertJsonPath('current_spin', 6)->assertJsonPath('pending_order_id', $high->id);
        $this->assertEquals(0, $member->fresh()->balance);
        $this->assertEquals(100, $member->fresh()->frozen_balance);
        $this->assertSame(1, Frozen_order::count());
        $this->assertDatabaseHas('status_orders', ['frozen_order_id' => $high->id, 'changed_by' => $member->id, 'notes' => 'Người dùng nhận đơn hàng']);
        Queue::assertNothingPushed();
        $member->update(['frozen_balance' => 250]);
        $this->postJson(route('user.auto-spin.step', $member), $this->payload($member, 6))
            ->assertOk()->assertJsonPath('done', true);
        $this->assertEquals(50, $member->fresh()->balance);
        $this->assertEquals(0, $member->fresh()->frozen_balance);
        $this->assertEquals(200, DB::table('transaction_histories')->value('value'));
        $this->assertDatabaseHas('status_orders', ['frozen_order_id' => $high->id, 'changed_by' => $member->id, 'notes' => 'Người dùng xác nhận đơn hàng']);
    }

    public function test_high_value_snapshot_uses_custom_total_divided_by_quantity(): void
    {
        $member = $this->member($this->operator());
        $order = Order::where('index', 6)->first();
        $order->update(['quantity' => 7, 'price' => 0.68]);
        $high = Frozen_order::snapshotFromOrder($order, [
            'user_id' => $member->id, 'custom_price' => 2000, 'commission_percentage' => 10,
            'spun' => false, 'assignment_source' => 'admin',
        ])->fresh();

        $this->assertEquals(285.714286, $high->snapshot_unit_price);
        $this->assertSame(7, (int) $high->snapshot_quantity);
        $this->assertEquals(2000, $high->snapshot_order_value);
        $this->assertEquals(200, $high->snapshot_commission_value);
        $this->assertSame('complete', $high->snapshot_state);

        $regular = Frozen_order::snapshotFromOrder($order, ['user_id' => $member->id])->fresh();
        $this->assertEquals(0.68, $regular->snapshot_unit_price);
        $this->assertEquals(4.76, $regular->snapshot_order_value);
    }

    public function test_high_value_receipt_captures_quantity_once_and_returns_snapshot_price(): void
    {
        $member = $this->member($this->operator());
        $order = Order::where('index', 6)->first();
        $order->update(['quantity' => 6, 'price' => 0.68]);
        $high = Frozen_order::snapshotFromOrder($order, [
            'user_id' => $member->id, 'custom_price' => 2000, 'commission_percentage' => 10,
            'spun' => false, 'status' => 'pending', 'assignment_source' => 'admin',
        ]);
        $order->update(['quantity' => 7]);

        $response = app(\App\Services\OrderSpinService::class)->receive($member->id)->getData(true);
        $this->assertSame(200, $response['status']);
        $high->refresh();
        $this->assertSame(7, (int) $high->snapshot_quantity);
        $this->assertEquals(285.714286, $high->snapshot_unit_price);
        $this->assertSame(7, $response['order_quantity']);
        $this->assertEquals(285.714286, $response['unit_price']);
        $this->assertDatabaseHas('status_orders', [
            'frozen_order_id' => $high->id, 'changed_by' => $member->id,
            'notes' => 'Người dùng nhận đơn hàng',
        ]);

        $order->update(['quantity' => 9, 'price' => 100]);
        app(\App\Services\OrderSpinService::class)->receive($member->id);
        $this->assertSame(7, (int) $high->fresh()->snapshot_quantity);
        $this->assertEquals(285.714286, $high->fresh()->display_unit_price);
        $this->assertSame(1, DB::table('status_orders')->where('frozen_order_id', $high->id)
            ->where('notes', 'Người dùng nhận đơn hàng')->count());
    }

    public function test_customer_high_value_history_renders_receipt_without_changing_admin_audit(): void
    {
        $actor = $this->operator();
        $actor->update(['full_name' => 'own']);
        $member = $this->member($actor);
        $high = Frozen_order::snapshotFromOrder(Order::where('index', 6)->first(), [
            'user_id' => $member->id, 'custom_price' => 2000, 'commission_percentage' => 10,
            'spun' => true, 'status' => 'pending', 'assignment_source' => 'admin',
        ]);
        \App\Services\OrderStatusService::changeStatus($high, 'pending', 'Quản trị viên phân phối đơn hàng', $actor->id);

        $html = $this->customerOrderDetailHtml($high);
        $this->assertStringContainsString('Người dùng nhận đơn hàng', $html);
        $this->assertStringContainsString($member->full_name, $html);
        $this->assertStringNotContainsString('Quản trị viên phân phối đơn hàng', $html);
        $this->assertDoesNotMatchRegularExpression('/<\/time>\s*·\s*own\s*<\/div>/u', $html);
        $this->assertDatabaseHas('status_orders', [
            'frozen_order_id' => $high->id, 'changed_by' => $actor->id,
            'notes' => 'Quản trị viên phân phối đơn hàng',
        ]);

        \App\Services\OrderStatusService::changeStatus($high, 'pending', 'Người dùng nhận đơn hàng', $member->id);
        $history = \App\Services\OrderStatusService::getCustomerStatusHistory($high);
        $this->assertCount(1, $history);
        $this->assertSame($member->id, $history->first()->changed_by);
        $this->assertSame(2, \App\Services\OrderStatusService::getStatusHistory($high->id)->count());

        \App\Services\OrderStatusService::changeStatus($high, 'confirmed', 'Xác nhận bởi quản trị viên', $actor->id);
        $history = \App\Services\OrderStatusService::getCustomerStatusHistory($high);
        $this->assertCount(2, $history);
        $this->assertSame('Xác nhận bởi quản trị viên', $history->last()->notes);
        $this->assertSame($actor->id, $history->last()->changed_by);
    }

    public function test_customer_history_preserves_regular_receipts_and_hides_unreceived_assignments(): void
    {
        $actor = $this->operator();
        $member = $this->member($actor);
        $order = Order::where('index', 6)->first();
        $regular = Frozen_order::snapshotFromOrder($order, ['user_id' => $member->id, 'spun' => true]);
        \App\Services\OrderStatusService::changeStatus($regular, 'pending', 'Người dùng nhận đơn hàng', $member->id);
        $history = \App\Services\OrderStatusService::getCustomerStatusHistory($regular);
        $this->assertCount(1, $history);
        $this->assertSame('Người dùng nhận đơn hàng', $history->first()->notes);
        $this->assertSame($member->id, $history->first()->changed_by);

        $high = Frozen_order::snapshotFromOrder($order, [
            'user_id' => $member->id, 'custom_price' => 2000, 'spun' => false,
        ]);
        \App\Services\OrderStatusService::changeStatus($high, 'pending', 'Quản trị viên phân phối đơn hàng', $actor->id);
        $this->assertCount(0, \App\Services\OrderStatusService::getCustomerStatusHistory($high));
        $this->assertCount(1, \App\Services\OrderStatusService::getStatusHistory($high->id));
    }

    public function test_order_detail_still_rejects_another_customer(): void
    {
        $actor = $this->operator();
        $member = $this->member($actor);
        $high = Frozen_order::snapshotFromOrder(Order::where('index', 6)->first(), [
            'user_id' => $member->id, 'custom_price' => 2000, 'spun' => true,
        ]);
        $otherMember = $this->member($actor);
        $this->actingAs($otherMember)->get('/order/'.$high->id)->assertForbidden();
    }

    private function customerOrderDetailHtml(Frozen_order $order): string
    {
        Schema::create('order_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('frozen_order_id');
            $table->unsignedBigInteger('reported_by')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
        });

        return $this->actingAs($order->user)
            ->getJson('/order/'.$order->id, ['X-React-Navigation' => '1'])
            ->assertOk()->assertJsonPath('page', 'user.order_detail')->json('props.html');
    }

    public function test_updating_high_value_total_synchronizes_financial_snapshot(): void
    {
        $member = $this->member($this->operator());
        $order = Order::where('index', 6)->first();
        $order->update(['quantity' => 7, 'price' => 0.68]);
        $high = Frozen_order::snapshotFromOrder($order, [
            'user_id' => $member->id, 'custom_price' => 2000, 'commission_percentage' => 10,
            'spun' => false, 'assignment_source' => 'admin',
        ]);
        $high->update(['custom_price' => 2100]);
        $high->refresh();

        $this->assertEquals(2100, $high->snapshot_order_amount);
        $this->assertEquals(300, $high->snapshot_unit_price);
        $this->assertEquals(210, $high->snapshot_commission_value);
        $this->assertSame('complete', $high->snapshot_state);
    }

    public function test_high_value_price_repair_uses_only_stored_quantity_and_preserves_history(): void
    {
        $member = $this->member($this->operator());
        $order = Order::where('index', 6)->first();
        $order->update(['quantity' => 6, 'price' => 0.68]);
        $high = Frozen_order::snapshotFromOrder($order, [
            'user_id' => $member->id, 'custom_price' => 2000, 'commission_percentage' => 10,
            'spun' => true, 'status' => 'completed', 'settled_order_amount' => 2000,
            'settled_commission_amount' => 200, 'settled_refund_amount' => 2200,
        ]);
        $regular = Frozen_order::snapshotFromOrder($order, ['user_id' => $member->id]);
        $legacy = Frozen_order::create(['user_id' => $member->id, 'order_id' => $order->id, 'custom_price' => 500]);
        DB::table('frozen_orders')->where('id', $high->id)->update(['snapshot_unit_price' => 0.68]);
        $high->refresh();
        $before = $high->getRawOriginal();
        $this->assertSame('invalid', $high->snapshot_state);
        $order->update(['quantity' => 7, 'price' => 100]);

        $migration = require database_path('migrations/2026_10_06_000001_correct_high_value_snapshot_unit_prices.php');
        $migration->up();
        $migration->up();
        $high->refresh();

        $this->assertEquals(333.333333, $high->snapshot_unit_price);
        $this->assertSame('complete', $high->snapshot_state);
        $after = $high->getRawOriginal();
        unset($before['snapshot_unit_price'], $after['snapshot_unit_price']);
        $this->assertEquals($before, $after);
        $this->assertEquals(0.68, $regular->fresh()->snapshot_unit_price);
        $this->assertNull($legacy->fresh()->snapshot_unit_price);
        $this->assertNull($legacy->fresh()->snapshot_quantity);
    }

    public function test_order_received_before_scheduled_high_value_order_records_customer_in_history(): void
    {
        $actor = $this->operator();
        $member = $this->member($actor);
        Frozen_order::snapshotFromOrder(Order::where('index', 8)->first(), [
            'user_id' => $member->id, 'custom_price' => 200, 'commission_percentage' => 10,
            'spun' => false, 'status' => 'pending', 'assignment_source' => 'admin',
        ]);

        $response = $this->actingAs($actor)->postJson(route('user.auto-spin.step', $member), $this->payload($member, 6))
            ->assertOk()->assertJsonPath('done', true);
        $id = $response->json('confirmed_order_id');
        $this->assertDatabaseHas('status_orders', ['frozen_order_id' => $id, 'changed_by' => $member->id, 'notes' => 'Người dùng nhận đơn hàng']);
        $this->assertDatabaseHas('status_orders', ['frozen_order_id' => $id, 'changed_by' => $member->id, 'notes' => 'Người dùng xác nhận đơn hàng']);
        $this->assertSame(0, DB::table('status_orders')->where('changed_by', $actor->id)->count());
    }

    public function test_replaying_stale_request_does_not_spin_or_debit_again(): void
    {
        $actor = $this->operator();
        $member = $this->member($actor);
        $payload = $this->payload($member);
        $this->actingAs($actor)->postJson(route('user.auto-spin.step', $member), $payload)->assertOk();
        $this->postJson(route('user.auto-spin.step', $member), $payload)->assertConflict();
        $this->assertSame(1, Frozen_order::count());
        $this->assertEquals(990, $member->fresh()->balance);
    }

    public function test_stale_pending_order_request_does_not_create_a_new_order(): void
    {
        $actor = $this->operator();
        $member = $this->member($actor);
        $pending = Frozen_order::snapshotFromOrder(Order::where('index', 5)->first(), [
            'user_id' => $member->id, 'spun' => true, 'status' => 'pending',
        ]);
        $payload = $this->payload($member, 6);
        $this->actingAs($actor)->postJson(route('user.auto-spin.step', $member), $payload)->assertOk();
        $this->postJson(route('user.auto-spin.step', $member), $payload)->assertConflict();
        $this->assertSame('confirmed', $pending->fresh()->status);
        $this->assertSame(1, Frozen_order::count());
    }

    public function test_legacy_order_without_snapshot_is_not_invented_or_confirmed(): void
    {
        $actor = $this->operator();
        $member = $this->member($actor);
        Frozen_order::create(['user_id' => $member->id, 'order_id' => 5, 'spun' => true, 'status' => 'pending']);
        $this->actingAs($actor)->postJson(route('user.auto-spin.step', $member), $this->payload($member))
            ->assertConflict();
        $this->assertEquals(1000, $member->fresh()->balance);
        $this->assertSame(0, DB::table('transaction_histories')->count());
    }

    public function test_banned_account_and_missing_next_order_stop_without_advancing(): void
    {
        $actor = $this->operator();
        $member = $this->member($actor, ['status' => 'banned']);
        $this->actingAs($actor)->postJson(route('user.auto-spin.step', $member), $this->payload($member))
            ->assertConflict();
        $member->update(['status' => 'activated']);
        DB::table('orders')->where('index', 6)->delete();
        $this->postJson(route('user.auto-spin.step', $member), $this->payload($member))->assertStatus(500);
        $this->assertEquals(5, $member->user_spin_progress->current_spin);
        $this->assertSame(0, Frozen_order::count());
    }

    public function test_permission_migration_repairs_group_and_preserves_existing_grants(): void
    {
        $allowed = $this->operator();
        $denied = $this->operator(false, User::ROLE_ADMIN);
        $permissionId = DB::table('manager_settings')->where('manager_code', 'customers.auto-spin')->value('id');
        $before = DB::table('user_manager_settings')->get()->toArray();
        $migration = require database_path('migrations/2026_10_04_000001_add_customer_auto_spin_permission.php');
        $migration->up();
        $migration->up();
        $migration->down();
        $groupId = DB::table('manager_settings')->where('manager_code', 'permission-group.customers')->value('id');
        $this->assertNotNull($groupId);
        $this->assertEquals($groupId, DB::table('manager_settings')->where('id', $permissionId)->value('parent_manager_setting_id'));
        $this->assertEquals($before, DB::table('user_manager_settings')->get()->toArray());
    }

    private function operator(bool $active = true, string $role = User::ROLE_STAFF): User
    {
        $user = User::create(['full_name' => 'Operator', 'username' => 'operator', 'role' => $role, 'status' => 'activated']);
        $this->grant($user, 'customers.auto-spin', $active);
        return $user;
    }

    private function grant(User $user, string $code, bool $active = true): void
    {
        $permissionId = DB::table('manager_settings')->where('manager_code', $code)->value('id')
            ?? DB::table('manager_settings')->insertGetId(['manager_code' => $code]);
        DB::table('user_manager_settings')->insert([
            'user_id' => $user->id, 'manager_setting_id' => $permissionId, 'is_active' => $active,
        ]);
    }

    private function member(User $actor, array $attributes = []): User
    {
        $member = User::create(array_merge([
            'full_name' => 'Clone A', 'username' => 'clone-a', 'role' => User::ROLE_MEMBER,
            'status' => 'activated', 'referrer_id' => $actor->id, 'rank_id' => 1,
            'balance' => 1000, 'clone_account' => true,
        ], $attributes));
        User_spin_progress::create(['user_id' => $member->id, 'rank_id' => 1, 'current_spin' => 5]);
        return $member;
    }

    private function payload(User $member, mixed $target = 40): array
    {
        $state = app(CustomerAutoSpinService::class)->state($member->fresh());
        return [
            'target_spin' => $target,
            'expected_spin' => $state['current_spin'],
            'expected_pending_order_id' => $state['pending_order_id'],
        ];
    }
}
