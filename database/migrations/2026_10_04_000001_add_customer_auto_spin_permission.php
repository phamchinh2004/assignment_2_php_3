<?php

use App\Services\PermissionRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $definition = app(PermissionRegistry::class)->permissions()['customers.auto-spin']
                ?? throw new RuntimeException('Missing permission definition: customers.auto-spin');
            $groupCode = 'permission-group.'.$definition['module'];
            $group = DB::table('manager_settings')->where('manager_code', $groupCode)->first();

            if ($group && $group->parent_manager_setting_id !== null) {
                throw new RuntimeException('Permission group must be a root: '.$groupCode);
            }

            $groupId = $group?->id ?? DB::table('manager_settings')->insertGetId([
                'manager_code' => $groupCode,
                'manager_name' => $definition['module_label'],
                'parent_manager_setting_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $permission = DB::table('manager_settings')
                ->where('manager_code', $definition['code'])
                ->first();

            if ($permission && DB::table('manager_settings')->where('parent_manager_setting_id', $permission->id)->exists()) {
                throw new RuntimeException('Permission cannot be a parent: '.$definition['code']);
            }

            if ($permission) {
                DB::table('manager_settings')->where('id', $permission->id)->update([
                    'manager_name' => $definition['label'],
                    'parent_manager_setting_id' => $groupId,
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('manager_settings')->insert([
                    'manager_code' => $definition['code'],
                    'manager_name' => $definition['label'],
                    'parent_manager_setting_id' => $groupId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        // Preserve any permission decisions made after this permission was introduced.
    }
};
