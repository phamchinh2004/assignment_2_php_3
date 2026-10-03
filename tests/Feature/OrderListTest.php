<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrderListTest extends TestCase
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
            $table->string('role');
            $table->string('status')->default('activated');
            $table->timestamp('last_seen')->nullable();
            $table->timestamps();
        });
        Schema::create('manager_settings', function (Blueprint $table) {
            $table->id();
            $table->string('manager_code');
        });
        Schema::create('user_manager_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('manager_setting_id');
            $table->boolean('is_active');
        });
        Schema::create('ranks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rank_id');
            $table->unsignedBigInteger('partner_id')->nullable();
            $table->string('status')->nullable();
            $table->decimal('price', 16, 6)->nullable();
        });
        DB::table('ranks')->insert(['id' => 1, 'name' => 'Rank A']);
    }

    public function test_list_statistics_preserve_all_statuses_and_use_one_aggregate_query(): void
    {
        DB::table('orders')->insert([
            ['rank_id' => 1, 'status' => '1', 'price' => 12.5],
            ['rank_id' => 1, 'status' => '0', 'price' => 7.25],
            ['rank_id' => 1, 'status' => null, 'price' => null],
        ]);
        $this->actingAs($this->operator(true));
        DB::enableQueryLog();
        try {
            $response = $this->getJson(route('order.index'), ['X-React-Navigation' => '1']);
            $orderQueries = collect(DB::getQueryLog())->filter(
                fn ($query) => str_contains($query['query'], 'from "orders"')
                    && !str_contains($query['query'], 'from "ranks"')
            );
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
        $response->assertOk()->assertJsonPath('props.stats.total', 3)
            ->assertJsonPath('props.stats.active', 1)->assertJsonPath('props.stats.inactive', 1)
            ->assertJsonPath('props.ranks.0.orders_count', 3)
            ->assertJsonPath('props.permissions.create', false);
        $this->assertEquals(19.75, $response->json('props.stats.totalValue'));
        $this->assertCount(1, $orderQueries);
    }

    public function test_empty_list_has_zero_totals(): void
    {
        $this->actingAs($this->operator(true))->getJson(route('order.index'), ['X-React-Navigation' => '1'])
            ->assertOk()->assertJsonPath('props.stats.total', 0)
            ->assertJsonPath('props.stats.active', 0)->assertJsonPath('props.stats.inactive', 0)
            ->assertJsonPath('props.stats.totalValue', 0);
    }

    public function test_filtering_preserves_rows_and_does_not_load_rank_statistics(): void
    {
        DB::table('orders')->insert([
            ['rank_id' => 1, 'status' => '1', 'price' => 12.5],
            ['rank_id' => 1, 'status' => '0', 'price' => 7.25],
        ]);
        $this->actingAs($this->operator(true));
        DB::enableQueryLog();
        try {
            $response = $this->getJson(route('order.index', ['status' => '0', 'rank' => 1]));
            $rankQueries = collect(DB::getQueryLog())->filter(
                fn ($query) => str_contains($query['query'], 'from "ranks"')
            );
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.status', '0')
            ->assertJsonPath('data.0.rank.name', 'Rank A');
        $this->assertCount(1, $rankQueries);
    }

    public function test_list_and_filter_require_an_active_permission(): void
    {
        foreach ([User::ROLE_ADMIN, User::ROLE_STAFF] as $role) {
            $operator = $this->operator(false, $role);
            foreach ([[], ['status' => '1']] as $filter) {
                $this->actingAs($operator)->getJson(route('order.index', $filter))->assertForbidden();
            }
        }
    }

    private function operator(bool $active, string $role = User::ROLE_STAFF): User
    {
        $user = User::create(['full_name' => 'Operator', 'role' => $role]);
        $permissionId = DB::table('manager_settings')->insertGetId(['manager_code' => 'orders.view']);
        DB::table('user_manager_settings')->insert([
            'user_id' => $user->id, 'manager_setting_id' => $permissionId, 'is_active' => $active,
        ]);

        return $user;
    }
}
