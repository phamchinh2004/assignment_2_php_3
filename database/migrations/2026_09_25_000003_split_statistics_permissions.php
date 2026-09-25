<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $parent = DB::table('manager_settings')->where('manager_code', 'permission-group.statistics')->value('id');
            if (! $parent) {
                $parent = DB::table('manager_settings')->insertGetId([
                    'manager_code' => 'permission-group.statistics', 'manager_name' => 'Thống kê hệ thống',
                    'parent_manager_setting_id' => null, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            $labels = [
                'statistics.view-overview' => 'Doanh thu tổng quan',
                'statistics.view-staff' => 'Doanh thu nhân viên',
                'statistics.view-customers' => 'Doanh thu từ khách hàng',
                'statistics.view-personal' => 'Doanh thu bản thân',
            ];
            $oldIds = DB::table('manager_settings')->where('manager_code', 'statistics.view-system')->pluck('id');
            $allowed = DB::table('user_manager_settings')->whereIn('manager_setting_id', $oldIds)
                ->where('is_active', 1)->pluck('user_id')->all();
            $users = DB::table('users')->whereIn('role', ['own', 'admin', 'staff'])->get(['id', 'role']);
            foreach ($labels as $code => $label) {
                $id = DB::table('manager_settings')->where('manager_code', $code)->value('id');
                if (! $id) {
                    $id = DB::table('manager_settings')->insertGetId([
                        'manager_code' => $code, 'manager_name' => $label,
                        'parent_manager_setting_id' => $parent, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                DB::table('manager_settings')->where('id', $id)->whereNull('parent_manager_setting_id')
                    ->update(['parent_manager_setting_id' => $parent]);
                foreach ($users as $user) {
                    if (DB::table('user_manager_settings')->where('user_id', $user->id)->where('manager_setting_id', $id)->exists()) {
                        continue;
                    }
                    DB::table('user_manager_settings')->insert([
                        'user_id' => $user->id, 'manager_setting_id' => $id,
                        'is_active' => $code === 'statistics.view-personal' || $user->role === 'own' || in_array($user->id, $allowed),
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        // Preserve permission records and assignments on rollback.
    }
};
