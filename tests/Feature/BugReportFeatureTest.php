<?php

namespace Tests\Feature;

use App\Models\BugReport;
use App\Models\Manager_setting;
use App\Models\User;
use App\Models\User_manager_setting;
use App\Notifications\BugReportSubmittedNotification;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class BugReportFeatureTest extends TestCase
{
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    protected function setUp(): void
    {
        if (! in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('Enable pdo_sqlite to run the bug report feature tests.');
        }

        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('database.connections.sqlite.foreign_key_constraints', false);
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('full_name')->nullable();
            $table->string('username')->unique();
            $table->string('password')->nullable();
            $table->string('role');
            $table->string('status')->default('activated');
            $table->unsignedBigInteger('referrer_id')->nullable();
            $table->timestamp('last_seen')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('manager_settings', function (Blueprint $table) {
            $table->id();
            $table->string('manager_name')->nullable();
            $table->string('manager_code')->unique();
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

        Schema::create('bug_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reported_by')->nullable();
            $table->string('title', 160);
            $table->text('description');
            $table->string('page_url', 2048)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('images')->nullable();
            $table->string('status', 20)->default(BugReport::STATUS_PENDING);
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->text('resolved_note')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_admin_and_staff_need_create_permission_to_submit_bug_reports(): void
    {
        Notification::fake();
        $owner = $this->user(User::ROLE_OWNER);

        foreach ([User::ROLE_ADMIN, User::ROLE_STAFF] as $role) {
            $reporter = $this->user($role);
            $payload = [
                'title' => 'Không thể lưu dữ liệu',
                'description' => 'Bấm lưu nhưng màn hình không phản hồi.',
                'page_url' => 'http://localhost/admin/test',
                'user_agent' => 'Test browser',
            ];

            $this->actingAs($reporter)
                ->postJson(route('bug_reports.store'), $payload)
                ->assertForbidden();

            $this->grant($reporter, config('authorization.capabilities.bug_reports_create'));
            $reporter->unsetRelation('user_manager_settings');

            $this->actingAs($reporter)
                ->postJson(route('bug_reports.store'), $payload)
                ->assertCreated()
                ->assertJsonPath('message', 'Đã gửi báo lỗi tới chủ hệ thống.');
        }

        $this->assertSame(2, BugReport::query()->count());
        Notification::assertSentTo($owner, BugReportSubmittedNotification::class, 2);
    }

    public function test_receiving_routes_are_owner_only_and_permission_protected(): void
    {
        $index = Route::getRoutes()->getByName('bug_reports.index');
        $show = Route::getRoutes()->getByName('bug_reports.show');
        $resolve = Route::getRoutes()->getByName('bug_reports.resolve');

        $this->assertNotNull($index);
        $this->assertContains('role:own', $index->gatherMiddleware());
        $this->assertContains('permission:'.config('authorization.capabilities.bug_reports_view'), $index->gatherMiddleware());
        $this->assertContains('role:own', $show->gatherMiddleware());
        $this->assertContains('permission:'.config('authorization.capabilities.bug_reports_view_detail'), $show->gatherMiddleware());
        $this->assertContains('role:own', $resolve->gatherMiddleware());
        $this->assertContains('permission:'.config('authorization.capabilities.bug_reports_resolve'), $resolve->gatherMiddleware());
    }

    public function test_header_keeps_bug_report_action_visible_for_management_roles(): void
    {
        $owner = $this->user(User::ROLE_OWNER);
        $this->actingAs($owner);
        $ownerHtml = view('admin.layouts.header')->render();
        $this->assertStringContainsString(route('bug_reports.index'), $ownerHtml);
        $this->assertStringContainsString('Báo lỗi', $ownerHtml);

        $admin = $this->user(User::ROLE_ADMIN);
        $this->actingAs($admin);
        $lockedHtml = view('admin.layouts.header')->render();
        $this->assertStringContainsString('admin-topbar__report-button', $lockedHtml);
        $this->assertStringContainsString('disabled', $lockedHtml);

        $this->grant($admin, config('authorization.capabilities.bug_reports_create'));
        $admin->unsetRelation('user_manager_settings');
        $this->actingAs($admin);
        $enabledHtml = view('admin.layouts.header')->render();
        $this->assertStringContainsString('data-target="#bugReportModal"', $enabledHtml);
        $this->assertStringContainsString('name="images[]"', $enabledHtml);
    }

    public function test_bug_report_can_include_images(): void
    {
        Storage::fake('public');
        Notification::fake();

        $this->user(User::ROLE_OWNER);
        $reporter = $this->user(User::ROLE_ADMIN);
        $this->grant($reporter, config('authorization.capabilities.bug_reports_create'));
        $reporter->unsetRelation('user_manager_settings');

        $response = $this->actingAs($reporter)->postJson(route('bug_reports.store'), [
            'title' => 'Lỗi giao diện',
            'description' => 'Nút xác nhận bị lệch.',
            'images' => [
                $this->fakePng('screen-1.png'),
                $this->fakePng('screen-2.png'),
            ],
        ]);

        $response->assertCreated();
        $report = BugReport::query()->firstOrFail();

        $this->assertCount(2, $report->images);
        foreach ($report->images as $path) {
            Storage::disk('public')->assertExists($path);
        }
    }

    private function fakePng(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true)
        );
    }

    public function test_permission_migration_registers_group_and_existing_permissions_without_granting_them(): void
    {
        Manager_setting::query()->create([
            'manager_name' => 'Quyền cũ',
            'manager_code' => config('authorization.capabilities.bug_reports_create'),
            'parent_manager_setting_id' => null,
        ]);

        $migration = require base_path('database/migrations/2026_10_01_000002_register_bug_report_permissions.php');
        $migration->up();

        $group = Manager_setting::query()
            ->where('manager_code', 'permission-group.bug_reports')
            ->firstOrFail();

        $this->assertNull($group->parent_manager_setting_id);

        foreach (config('authorization.modules.bug_reports.permissions') as $definition) {
            $permission = Manager_setting::query()
                ->where('manager_code', $definition['code'])
                ->firstOrFail();

            $this->assertSame($group->id, (int) $permission->parent_manager_setting_id);
            $this->assertSame($definition['label'], $permission->manager_name);
        }

        $this->assertDatabaseCount('user_manager_settings', 0);
    }

    private function user(string $role): User
    {
        return User::query()->create([
            'full_name' => 'Test '.$role,
            'username' => uniqid($role.'_', true),
            'role' => $role,
            'status' => 'activated',
        ]);
    }

    private function grant(User $user, string $permission): void
    {
        $group = Manager_setting::query()->firstOrCreate([
            'manager_code' => 'permission-group.bug_reports',
        ], [
            'manager_name' => 'Báo lỗi hệ thống',
        ]);

        $setting = Manager_setting::query()->firstOrCreate([
            'manager_code' => $permission,
        ], [
            'manager_name' => 'Gửi báo lỗi',
            'parent_manager_setting_id' => $group->id,
        ]);

        User_manager_setting::query()->updateOrCreate([
            'user_id' => $user->id,
            'manager_setting_id' => $setting->id,
        ], [
            'is_active' => true,
        ]);
    }
}
