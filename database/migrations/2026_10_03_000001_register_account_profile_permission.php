<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $module = config('authorization.modules.account');
            $definition = $module['permissions']['update_profile'];
            $groupCode = 'permission-group.account';
            $group = DB::table('manager_settings')->where('manager_code', $groupCode)->first();

            if ($group && $group->parent_manager_setting_id !== null) {
                throw new RuntimeException('Permission group must be a root: '.$groupCode);
            }

            $groupId = $group?->id ?? DB::table('manager_settings')->insertGetId([
                'manager_code' => $groupCode,
                'manager_name' => $module['label'],
                'parent_manager_setting_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $permission = DB::table('manager_settings')->where('manager_code', $definition['code'])->first();

            if ($permission && DB::table('manager_settings')->where('parent_manager_setting_id', $permission->id)->exists()) {
                throw new RuntimeException('Permission cannot be a parent: '.$definition['code']);
            }

            $values = [
                'manager_name' => $definition['label'],
                'parent_manager_setting_id' => $groupId,
                'updated_at' => now(),
            ];

            if ($permission) {
                DB::table('manager_settings')->where('id', $permission->id)->update($values);
            } else {
                DB::table('manager_settings')->insert(array_merge($values, [
                    'manager_code' => $definition['code'],
                    'created_at' => now(),
                ]));
            }
        });
    }

    public function down(): void
    {
        // Preserve existing grants and revocations, including assignments made after registration.
    }
};
