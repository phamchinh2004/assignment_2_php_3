<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('member','staff','admin','own') NOT NULL DEFAULT 'member' COMMENT 'Vai trò: member, staff, admin, own; own là cấp cao nhất'");
        }

        $newCapabilities = [
            'quan_ly_nhan_vien' => 'Quản lý nhân viên',
            'phan_quyen_nhan_vien' => 'Phân quyền nhân viên',
            'xem_thong_ke_he_thong' => 'Xem thống kê hệ thống',
            'quan_ly_tat_ca_tin_nhan' => 'Quản lý tất cả tin nhắn',
        ];

        foreach ($newCapabilities as $code => $name) {
            DB::table('manager_settings')->updateOrInsert(
                ['manager_code' => $code],
                ['manager_name' => $name, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        $permissionIds = DB::table('manager_settings')->pluck('id');
        $adminIds = DB::table('users')->where('role', User::ROLE_ADMIN)->pluck('id');

        foreach ($adminIds as $adminId) {
            foreach ($permissionIds as $permissionId) {
                $assignment = DB::table('user_manager_settings')
                    ->where('user_id', $adminId)
                    ->where('manager_setting_id', $permissionId)
                    ->first();

                if ($assignment) {
                    DB::table('user_manager_settings')
                        ->where('id', $assignment->id)
                        ->update(['is_active' => 1, 'updated_at' => now()]);
                } else {
                    DB::table('user_manager_settings')->insert([
                        'user_id' => $adminId,
                        'manager_setting_id' => $permissionId,
                        'is_active' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        if (DB::table('users')->where('role', User::ROLE_OWNER)->exists()) {
            throw new RuntimeException('Cannot remove the own role while owner accounts still exist.');
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('member','staff','admin') NOT NULL DEFAULT 'member' COMMENT 'Vai trò: member, staff, admin'");
        }
    }
};
