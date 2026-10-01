<?php

use App\Services\PermissionRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $definitions = collect(app(PermissionRegistry::class)->permissions())
                ->filter(fn (array $definition) => $definition['module'] === 'bug_reports');

            if ($definitions->isEmpty()) {
                throw new RuntimeException('Missing bug report permission definitions.');
            }

            $groupCode = 'permission-group.bug_reports';
            $group = DB::table('manager_settings')->where('manager_code', $groupCode)->first();

            if ($group && $group->parent_manager_setting_id !== null) {
                throw new RuntimeException('Permission group must be a root: '.$groupCode);
            }

            $groupId = $group?->id ?? DB::table('manager_settings')->insertGetId([
                'manager_code' => $groupCode,
                'manager_name' => $definitions->first()['module_label'],
                'parent_manager_setting_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($definitions as $definition) {
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
                    continue;
                }

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
        // Preserve permission assignments made after these permissions were introduced.
    }
};
