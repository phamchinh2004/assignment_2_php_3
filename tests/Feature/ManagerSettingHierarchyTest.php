<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ManagerSettingController;
use App\Models\Manager_setting;
use App\Models\User_manager_setting;
use App\Rules\ManagerSettingParent;
use App\Services\PermissionRegistry;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ManagerSettingHierarchyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('manager_settings', function (Blueprint $table) {
            $table->id();
            $table->string('manager_name');
            $table->string('manager_code');
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
    }

    private function setting(string $code, ?Manager_setting $parent = null): Manager_setting
    {
        return Manager_setting::create(['manager_name' => $code, 'manager_code' => $code,
            'parent_manager_setting_id' => $parent?->id]);
    }

    public function test_statistics_migration_preserves_grants_and_existing_denials(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('role');
        });
        DB::table('users')->insert([['id' => 1, 'role' => 'staff'], ['id' => 2, 'role' => 'admin']]);
        $old = $this->setting('statistics.view-system');
        DB::table('user_manager_settings')->insert([
            ['user_id' => 1, 'manager_setting_id' => $old->id, 'is_active' => 1],
            ['user_id' => 2, 'manager_setting_id' => $old->id, 'is_active' => 0],
        ]);
        $migration = require database_path('migrations/2026_09_25_000003_split_statistics_permissions.php');
        $migration->up();
        foreach (['overview', 'staff', 'customers', 'personal'] as $type) {
            $id = Manager_setting::where('manager_code', 'statistics.view-'.$type)->value('id');
            $this->assertEquals(1, DB::table('user_manager_settings')->where('user_id', 1)->where('manager_setting_id', $id)->value('is_active'));
            $this->assertEquals($type === 'personal' ? 1 : 0, DB::table('user_manager_settings')->where('user_id', 2)->where('manager_setting_id', $id)->value('is_active'));
        }
        $overview = Manager_setting::where('manager_code', 'statistics.view-overview')->value('id');
        DB::table('user_manager_settings')->where('user_id', 1)->where('manager_setting_id', $overview)->update(['is_active' => 0]);
        $migration->up();
        $this->assertEquals(0, DB::table('user_manager_settings')->where('user_id', 1)->where('manager_setting_id', $overview)->value('is_active'));
        $this->assertNotNull($old->fresh());
    }

    public function test_retiring_statistics_preserves_new_grants_and_custom_children(): void
    {
        $root = $this->setting('permission-group.statistics');
        $old = $this->setting('xem_thong_ke_he_thong');
        $aggregate = $this->setting('statistics.view-system', $root);
        $custom = $this->setting('custom-report', $old);
        foreach (['overview', 'staff', 'customers', 'personal'] as $type) {
            $this->setting('statistics.view-'.$type, $root);
        }
        $this->setting('statistics.export', $root);
        $overview = Manager_setting::where('manager_code', 'statistics.view-overview')->value('id');
        DB::table('user_manager_settings')->insert([
            ['user_id' => 1, 'manager_setting_id' => $old->id, 'is_active' => 1],
            ['user_id' => 1, 'manager_setting_id' => $overview, 'is_active' => 0],
            ['user_id' => 2, 'manager_setting_id' => $aggregate->id, 'is_active' => 1],
        ]);
        $migration = require database_path('migrations/2026_09_25_000004_retire_legacy_statistics_permissions.php');
        $migration->up();
        $migration->up();
        $this->assertNull($old->fresh());
        $this->assertNull($aggregate->fresh());
        $this->assertEquals($root->id, $custom->fresh()->parent_manager_setting_id);
        $this->assertEquals(0, DB::table('user_manager_settings')->where('user_id', 1)->where('manager_setting_id', $overview)->value('is_active'));
        $this->assertEquals(1, DB::table('user_manager_settings')->where('user_id', 2)->where('manager_setting_id', $overview)->value('is_active'));
        $export = Manager_setting::where('manager_code', 'statistics.export')->value('id');
        $this->assertFalse(DB::table('user_manager_settings')->where('user_id', 2)->where('manager_setting_id', $export)->exists());
        $this->assertSame(0, DB::table('user_manager_settings')->whereIn('manager_setting_id', [$old->id, $aggregate->id])->count());
    }

    public function test_existing_statistics_views_are_attached_without_changing_grants(): void
    {
        $root = $this->setting('permission-group.statistics');
        $this->setting('statistics.export', $root);
        foreach (['overview', 'staff', 'customers', 'personal'] as $type) {
            $permission = $this->setting('statistics.view-'.$type);
            DB::table('user_manager_settings')->insert(['user_id' => 1, 'manager_setting_id' => $permission->id, 'is_active' => 0]);
        }
        $before = DB::table('user_manager_settings')->get()->toJson();
        $migration = require database_path('migrations/2026_09_25_000005_attach_statistics_view_permissions.php');
        $migration->up();
        $migration->up();
        $this->assertCount(5, $root->fresh()->children);
        $this->assertCount(1, app(PermissionRegistry::class)->settingGroups());
        $this->assertSame($before, DB::table('user_manager_settings')->get()->toJson());
    }

    public function test_legacy_cleanup_creates_missing_replacements_and_preserves_current_decisions(): void
    {
        $old = $this->setting('quan_ly_tat_ca_tin_nhan');
        $frozen = $this->setting('cau_hinh_moc_canh_bao_frozen_order');
        $target = $this->setting('chats.view-all');
        DB::table('user_manager_settings')->insert([
            ['user_id' => 1, 'manager_setting_id' => $old->id, 'is_active' => 1],
            ['user_id' => 1, 'manager_setting_id' => $target->id, 'is_active' => 0],
            ['user_id' => 2, 'manager_setting_id' => $old->id, 'is_active' => 1],
            ['user_id' => 2, 'manager_setting_id' => $frozen->id, 'is_active' => 1],
        ]);
        $migration = require database_path('migrations/2026_09_25_000006_retire_legacy_permissions.php');
        $migration->up();
        $migration->up();
        $this->assertNull($old->fresh());
        $this->assertNull($frozen->fresh());
        $this->assertEquals(0, DB::table('user_manager_settings')->where('user_id', 1)->where('manager_setting_id', $target->id)->value('is_active'));
        $this->assertEquals(1, DB::table('user_manager_settings')->where('user_id', 2)->where('manager_setting_id', $target->id)->value('is_active'));
        foreach (['frozen-order-settings.view', 'frozen-order-settings.update', 'chats.view-all'] as $code) {
            $permission = Manager_setting::where('manager_code', $code)->firstOrFail();
            $this->assertNotNull($permission->parent_manager_setting_id);
            $this->assertEquals(1, DB::table('user_manager_settings')->where('user_id', 2)->where('manager_setting_id', $permission->id)->value('is_active'));
        }
        $this->assertSame(0, DB::table('user_manager_settings')->whereIn('manager_setting_id', [$old->id, $frozen->id])->count());
    }

    public function test_parent_validation_prevents_self_cycles_and_third_level(): void
    {
        $root = $this->setting('root');
        $child = $this->setting('child', $root);
        $other = $this->setting('other');
        foreach ([[$root, $root->id], [$root, $child->id], [$other, $child->id], [$root, $other->id], [$child, 999]] as [$setting, $parent]) {
            $this->assertTrue(Validator::make(['parent' => $parent], ['parent' => new ManagerSettingParent($setting)])->fails());
        }
        $this->assertFalse(Validator::make(['parent' => $other->id], ['parent' => new ManagerSettingParent($child)])->fails());
        $this->assertEquals($root->id, $child->parent->id);
        $this->assertEquals([$child->id], $root->children->modelKeys());
    }

    public function test_http_create_update_and_invalid_parent_submission(): void
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\Authenticate::class,
            \App\Http\Middleware\CheckRole::class,
            \App\Http\Middleware\CheckUserBanned::class,
            \App\Http\Middleware\AuthorizationContext::class,
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\UpdateLastSeen::class,
            \App\Http\Middleware\UpdateApproximateLocation::class,
        ]);
        $root = $this->setting('root');
        $other = $this->setting('other');
        $this->post(route('manager_setting.store'), ['manager_name' => 'New child', 'parent_manager_setting_id' => $root->id])->assertRedirect(route('manager_setting.index'));
        $child = Manager_setting::where('manager_code', 'new_child')->firstOrFail();
        $this->assertEquals($root->id, $child->parent_manager_setting_id);
        $this->put(route('manager_setting.update', $child), ['manager_name' => 'Renamed', 'parent_manager_setting_id' => $other->id])->assertRedirect(route('manager_setting.index'));
        $this->assertEquals($other->id, $child->fresh()->parent_manager_setting_id);
        $this->assertSame('new_child', $child->fresh()->manager_code);
        $this->putJson(route('manager_setting.update', $other), ['manager_name' => 'Other', 'parent_manager_setting_id' => $child->id])->assertUnprocessable()->assertJsonValidationErrors('parent_manager_setting_id');
        $this->put(route('manager_setting.update', $child), ['manager_name' => 'Renamed', 'parent_manager_setting_id' => ''])->assertRedirect();
        $this->assertNull($child->fresh()->parent_manager_setting_id);
        $this->postJson(route('manager_setting.store'), ['manager_name' => 'Invalid', 'parent_manager_setting_id' => 999])->assertUnprocessable();
    }

    public function test_delete_blocks_parent_and_cleans_only_deleted_leaf_assignments(): void
    {
        $root = $this->setting('root');
        $child = $this->setting('child', $root);
        DB::table('user_manager_settings')->insert(['user_id' => 1, 'manager_setting_id' => $child->id, 'is_active' => true]);
        $controller = app(ManagerSettingController::class);
        $controller->destroy($root);
        $this->assertTrue($root->fresh()->exists);
        $this->assertSame(1, DB::table('user_manager_settings')->count());
        $controller->destroy($child);
        $this->assertNull($child->fresh());
        $this->assertSame(0, DB::table('user_manager_settings')->count());
        $this->assertNotNull($root->fresh());
    }

    public function test_migration_preserves_codes_and_grants_and_both_pages_share_grouping(): void
    {
        config(['authorization.modules' => ['orders' => ['label' => 'Orders', 'permissions' => [
            ['code' => 'orders.view', 'label' => 'View'],
        ]]]]);
        $permission = $this->setting('orders.view');
        DB::table('user_manager_settings')->insert(['user_id' => 1, 'manager_setting_id' => $permission->id, 'is_active' => false]);
        $before = DB::table('user_manager_settings')->get()->toJson();
        $migration = require database_path('migrations/2026_09_25_000002_group_manager_settings.php');
        $migration->up();
        $migration->up();
        $this->assertSame($before, DB::table('user_manager_settings')->get()->toJson());
        $this->assertSame('orders.view', $permission->fresh()->manager_code);
        $this->assertSame(2, Manager_setting::count());
        $registry = app(PermissionRegistry::class);
        $groups = $registry->groups(User_manager_setting::with('manager_setting')->get());
        $this->assertSame($registry->settingGroups()[0]['key'], $groups[0]['key']);
        $this->assertSame('Orders', $groups[0]['label']);
        $this->assertFalse($groups[0]['permissions'][0]['active']);
        $this->assertSame('orders.view', $groups[0]['permissions'][0]['label']);
    }
}
