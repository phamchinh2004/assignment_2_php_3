<?php

namespace Tests\Feature;

use App\Models\Frozen_order;
use App\Models\Order;
use App\Models\User;
use App\Models\User_spin_progress;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminFrozenOrderNotificationTest extends TestCase
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
            $table->string('username');
            $table->string('avatar')->nullable();
            $table->string('role');
            $table->string('status')->default('activated');
            $table->unsignedBigInteger('referrer_id')->nullable();
            $table->unsignedBigInteger('rank_id')->nullable();
            $table->timestamp('last_seen')->nullable();
            $table->timestamps();
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rank_id');
            $table->unsignedBigInteger('partner_id')->nullable();
            $table->unsignedInteger('index');
            $table->string('order_code');
            $table->string('name');
            $table->decimal('price', 16, 6)->default(10);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('commission_percentage', 8, 3)->default(5);
            $table->timestamps();
        });
        Schema::create('frozen_orders', function (Blueprint $table) {
            $table->id();
            // Include the snapshot fields written and serialized by the real model.
            foreach ((new Frozen_order)->getFillable() as $column) {
                if (in_array($column, ['spun', 'is_frozen', 'commission_paid'], true)) {
                    $table->boolean($column)->default(false);
                } else {
                    $table->string($column)->nullable();
                }
            }
            $table->timestamps();
        });
        Schema::create('user_spin_progresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('rank_id');
            $table->unsignedInteger('current_spin')->default(1);
            $table->timestamps();
        });
        Schema::create('frozen_order_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('processing_time_limit');
            $table->unsignedInteger('notification_1_remaining_time');
            $table->unsignedInteger('notification_2_remaining_time');
            $table->timestamps();
        });
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
        });
        Schema::create('statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('display_name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('status_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('frozen_order_id');
            $table->unsignedBigInteger('status_id');
            $table->unsignedBigInteger('changed_by');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('manager_settings', function (Blueprint $table) {
            $table->id();
            $table->string('manager_code');
            $table->string('manager_name')->nullable();
            $table->unsignedBigInteger('parent_manager_setting_id')->nullable();
            $table->timestamps();
        });
        Schema::create('user_manager_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('manager_setting_id');
            $table->boolean('is_active');
            $table->timestamps();
        });
        DB::table('statuses')->insert(['name' => 'pending', 'display_name' => 'Chờ xử lý']);
    }

    public function test_success_result_survives_a_background_request_between_post_and_redirect(): void
    {
        [$actor, $member, $order] = $this->fixture();
        $url = route('user.frozen.order.interface', $member);
        $response = $this->actingAs($actor)->post($url, $this->payload($order), [
            'X-React-Navigation' => '1', 'Accept' => 'text/html,application/xhtml+xml', 'Referer' => $url,
        ]);
        $this->assertSame(1, Frozen_order::count());
        // Background admin requests share the same session as the form request.
        $this->getJson(route('authorization.state'))->assertOk();
        if ($response->isRedirect()) {
            $response = $this->getJson($response->headers->get('Location'), ['X-React-Navigation' => '1']);
        }
        $response->assertOk()->assertJsonPath('props.flash.success', 'Đóng băng thành công 1 đơn hàng!');
        $response->assertJsonCount(1, 'props.frozenOrders');
    }

    public function test_regular_form_preserves_redirect_and_success_flash(): void
    {
        [$actor, $member, $order] = $this->fixture();
        $url = route('user.frozen.order.interface', $member);
        $this->actingAs($actor)->from($url)->post($url, $this->payload($order))
            ->assertRedirect($url)->assertSessionHas('success', 'Đóng băng thành công 1 đơn hàng!');
    }

    public function test_partial_success_and_duplicate_errors_are_returned_with_current_orders(): void
    {
        [$actor, $member, $order] = $this->fixture();
        $url = route('user.frozen.order', $member);
        $headers = ['X-React-Navigation' => '1', 'Accept' => 'text/html,application/xhtml+xml'];
        $this->actingAs($actor)->post($url, $this->payload($order), $headers)->assertOk();
        $other = Order::create(['rank_id' => 1, 'index' => 3, 'order_code' => 'FREEZE-3', 'name' => 'Other order', 'price' => 10, 'quantity' => 1]);
        $payload = $this->payload($order);
        $payload['order_ids'][] = $other->id;
        $payload['order_data'][$other->id] = $this->payload($other)['order_data'][$other->id];

        $response = $this->post($url, $payload, $headers)->assertOk()->assertJsonCount(2, 'props.frozenOrders');
        $this->assertStringContainsString('Đóng băng thành công 1 đơn hàng. Lỗi:', $response->json('props.flash.warning'));
        $response = $this->post($url, $this->payload($order), $headers)->assertOk()->assertJsonCount(2, 'props.frozenOrders');
        $this->assertStringContainsString('Không đóng băng được đơn hàng nào.', $response->json('props.flash.error'));
        $this->assertSame(2, Frozen_order::count());
    }

    public function test_freezing_requires_active_permission_and_customer_scope(): void
    {
        foreach ([User::ROLE_STAFF, User::ROLE_ADMIN] as $role) {
            foreach ([false, true] as $active) {
                [$actor, $member, $order] = $this->fixture($active, $role);
                if ($active) {
                    $member->update(['referrer_id' => null]);
                }
                $this->actingAs($actor)->postJson(route('user.frozen.order', $member), $this->payload($order), ['X-React-Navigation' => '1'])
                    ->assertForbidden();
            }
        }
        $this->assertSame(0, Frozen_order::count());
    }

    private function fixture(bool $active = true, string $role = User::ROLE_STAFF): array
    {
        $actor = User::create(['username' => 'operator', 'role' => $role, 'status' => 'activated']);
        $member = User::create(['username' => 'customer', 'role' => User::ROLE_MEMBER, 'status' => 'activated', 'referrer_id' => $actor->id, 'rank_id' => 1]);
        $permission = DB::table('manager_settings')->insertGetId(['manager_code' => config('authorization.capabilities.customers_manage_frozen_orders')]);
        DB::table('user_manager_settings')->insert(['user_id' => $actor->id, 'manager_setting_id' => $permission, 'is_active' => $active]);
        User_spin_progress::create(['user_id' => $member->id, 'rank_id' => 1, 'current_spin' => 1]);
        $order = Order::create(['rank_id' => 1, 'index' => 2, 'order_code' => 'FREEZE-2', 'name' => 'Test order', 'price' => 10, 'quantity' => 1]);
        return [$actor, $member, $order];
    }

    private function payload(Order $order): array
    {
        return ['order_ids' => [$order->id], 'order_data' => [$order->id => [
            'order_id' => $order->id, 'custom_price' => 100, 'commission_percentage' => 10,
            'processing_time_limit' => 24, 'notification_1_remaining_time' => 12, 'notification_2_remaining_time' => 1,
        ]]];
    }
}
