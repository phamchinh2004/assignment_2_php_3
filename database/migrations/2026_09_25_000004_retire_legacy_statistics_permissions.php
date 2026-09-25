<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $mappings = [
                'xem_thong_ke_he_thong' => ['statistics.view-overview', 'statistics.view-staff', 'statistics.view-customers', 'statistics.export'],
                'statistics.view-system' => ['statistics.view-overview', 'statistics.view-staff', 'statistics.view-customers'],
            ];
            $old = DB::table('manager_settings')->whereIn('manager_code', array_keys($mappings))->lockForUpdate()->get();
            if ($old->isEmpty()) {
                return;
            }
            $parent = DB::table('manager_settings')->where('manager_code', 'permission-group.statistics')->value('id');
            if (! $parent) {
                throw new RuntimeException('Statistics group is missing. Run the split migration first.');
            }
            $permissions = DB::table('manager_settings')->whereIn('manager_code', array_unique(array_merge(...array_values($mappings))))->pluck('id', 'manager_code');
            foreach ($old as $permission) {
                foreach ($mappings[$permission->manager_code] as $code) {
                    if (! isset($permissions[$code])) {
                        throw new RuntimeException('Missing replacement permission: '.$code);
                    }
                    $assignments = DB::table('user_manager_settings')->where('manager_setting_id', $permission->id)->get();
                    foreach ($assignments as $assignment) {
                        // Existing assignments, including explicit denials, are authoritative.
                        if (! DB::table('user_manager_settings')->where('user_id', $assignment->user_id)->where('manager_setting_id', $permissions[$code])->exists()) {
                            DB::table('user_manager_settings')->insert([
                                'user_id' => $assignment->user_id, 'manager_setting_id' => $permissions[$code],
                                'is_active' => $assignment->is_active,
                                'created_at' => now(), 'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }
            DB::table('manager_settings')->whereIn('parent_manager_setting_id', $old->pluck('id'))->update(['parent_manager_setting_id' => $parent]);
            DB::table('user_manager_settings')->whereIn('manager_setting_id', $old->pluck('id'))->delete();
            DB::table('manager_settings')->whereIn('id', $old->pluck('id'))->delete();
        });
    }

    public function down(): void
    {
        // Retired aggregate grants cannot be reconstructed from independent permissions.
    }
};
