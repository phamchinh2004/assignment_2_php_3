<?php

use App\Services\PermissionRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $mappings = require database_path('data/legacy_permission_mappings.php');
            $mappings['cau_hinh_moc_canh_bao_frozen_order'] = ['frozen-order-settings.view', 'frozen-order-settings.update'];
            $definitions = app(PermissionRegistry::class)->permissions();
            $old = DB::table('manager_settings')->whereIn('manager_code', array_keys($mappings))->orderBy('id')->lockForUpdate()->get();
            $targets = [];
            foreach ($old as $permission) {
                foreach ($mappings[$permission->manager_code] as $code) {
                    $definition = $definitions[$code] ?? throw new RuntimeException('Missing replacement definition: '.$code);
                    $groupCode = 'permission-group.'.$definition['module'];
                    $parent = DB::table('manager_settings')->where('manager_code', $groupCode)->first();
                    if ($parent && $parent->parent_manager_setting_id !== null) {
                        throw new RuntimeException('Replacement group is not a root: '.$groupCode);
                    }
                    $parentId = $parent?->id ?? DB::table('manager_settings')->insertGetId([
                        'manager_code' => $groupCode, 'manager_name' => $definition['module_label'],
                        'parent_manager_setting_id' => null, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                    $target = DB::table('manager_settings')->where('manager_code', $code)->first();
                    $targetId = $target?->id ?? DB::table('manager_settings')->insertGetId([
                        'manager_code' => $code, 'manager_name' => $definition['label'],
                        'parent_manager_setting_id' => $parentId, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                    if ($target && $target->parent_manager_setting_id === null) {
                        if (DB::table('manager_settings')->where('parent_manager_setting_id', $targetId)->exists()) {
                            throw new RuntimeException('Replacement permission has children: '.$code);
                        }
                        DB::table('manager_settings')->where('id', $targetId)->update(['parent_manager_setting_id' => $parentId]);
                    }
                    $targets[$targetId][] = $permission->id;
                }
                // Never silently orphan custom children of a retired permission.
                if (DB::table('manager_settings')->where('parent_manager_setting_id', $permission->id)->exists()) {
                    throw new RuntimeException('Reparent children before retiring: '.$permission->manager_code);
                }
            }
            foreach ($targets as $targetId => $sourceIds) {
                $assignments = DB::table('user_manager_settings')->whereIn('manager_setting_id', $sourceIds)
                    ->select('user_id')->selectRaw('MAX(is_active) as is_active')->groupBy('user_id')->get();
                foreach ($assignments as $assignment) {
                    // Preserve current granular decisions, including explicit revocations.
                    if (! DB::table('user_manager_settings')->where('user_id', $assignment->user_id)->where('manager_setting_id', $targetId)->exists()) {
                        DB::table('user_manager_settings')->insert([
                            'user_id' => $assignment->user_id, 'manager_setting_id' => $targetId,
                            'is_active' => $assignment->is_active, 'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }
                }
            }
            DB::table('user_manager_settings')->whereIn('manager_setting_id', $old->pluck('id'))->delete();
            DB::table('manager_settings')->whereIn('id', $old->pluck('id'))->delete();
        });
    }

    public function down(): void
    {
        // Aggregate permissions cannot be reconstructed from independent grants.
    }
};
