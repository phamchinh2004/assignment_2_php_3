<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PERMISSIONS = [
        'xem_thong_bao_tinh_nang' => 'Xem thông báo tính năng',
        'tao_thong_bao_tinh_nang' => 'Tạo thông báo tính năng',
        'cap_nhat_thong_bao_tinh_nang' => 'Cập nhật thông báo tính năng',
        'xoa_thong_bao_tinh_nang' => 'Xóa thông báo tính năng',
        'xem_bao_cao_thong_bao_tinh_nang' => 'Xem báo cáo thông báo tính năng',
    ];

    public function up(): void
    {
        Schema::create('feature_announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title', 180);
            $table->text('content');
            $table->string('priority', 20)->default('normal')->index();
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('version')->default(1);
            $table->json('target_roles');
            $table->string('action_text', 120)->nullable();
            $table->string('action_url', 2048)->nullable();
            $table->string('image_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'starts_at'], 'feature_announcements_active_start_idx');
        });

        Schema::create('feature_announcement_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained('feature_announcements')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('announcement_version');
            $table->dateTime('acknowledged_at');
            $table->timestamps();

            $table->unique(
                ['announcement_id', 'user_id', 'announcement_version'],
                'feature_announcement_reads_unique'
            );
            $table->index(['user_id', 'announcement_version'], 'feature_announcement_reads_user_version_idx');
        });

        foreach (self::PERMISSIONS as $code => $name) {
            DB::table('manager_settings')->updateOrInsert(
                ['manager_code' => $code],
                ['manager_name' => $name, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        $permissionIds = DB::table('manager_settings')
            ->whereIn('manager_code', array_keys(self::PERMISSIONS))
            ->pluck('id');
        $adminIds = DB::table('users')->where('role', User::ROLE_ADMIN)->pluck('id');

        foreach ($adminIds as $adminId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('user_manager_settings')->updateOrInsert(
                    [
                        'user_id' => $adminId,
                        'manager_setting_id' => $permissionId,
                    ],
                    [
                        'is_active' => 1,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('manager_settings')
            ->whereIn('manager_code', array_keys(self::PERMISSIONS))
            ->pluck('id');

        DB::table('user_manager_settings')
            ->whereIn('manager_setting_id', $permissionIds)
            ->delete();
        DB::table('manager_settings')
            ->whereIn('manager_code', array_keys(self::PERMISSIONS))
            ->delete();

        Schema::dropIfExists('feature_announcement_reads');
        Schema::dropIfExists('feature_announcements');
    }
};
