<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            foreach (config('authorization.modules', []) as $key => $module) {
                $code = 'permission-group.'.$key;
                $root = DB::table('manager_settings')->where('manager_code', $code)->first();
                $id = $root?->id ?? DB::table('manager_settings')->insertGetId([
                    'manager_code' => $code,
                    'manager_name' => $module['label'],
                    'parent_manager_setting_id' => null,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                if ($root && $root->parent_manager_setting_id !== null) {
                    throw new RuntimeException('Permission group must be a root: '.$code);
                }
                $parentIds = DB::table('manager_settings')->whereNotNull('parent_manager_setting_id')
                    ->pluck('parent_manager_setting_id')->all();
                DB::table('manager_settings')
                    ->whereIn('manager_code', array_column($module['permissions'], 'code'))
                    ->whereNull('parent_manager_setting_id')
                    ->whereNotIn('id', array_merge([$id], $parentIds))
                    ->update(['parent_manager_setting_id' => $id]);
            }
        });
    }

    public function down(): void
    {
        // Preserve user-maintained hierarchy and all existing permissions.
    }
};
