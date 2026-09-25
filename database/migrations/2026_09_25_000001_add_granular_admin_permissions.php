<?php

use App\Models\User;
use App\Services\PermissionRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $registry = app(PermissionRegistry::class);
        $permissions = $registry->permissions();
        $permissionIds = [];

        foreach ($permissions as $code => $permission) {
            $permissionIds[$code] = $this->upsertPermission($code, $permission['label']);
        }

        $managementUsers = DB::table('users')
            ->whereIn('role', [User::ROLE_OWNER, User::ROLE_ADMIN, User::ROLE_STAFF])
            ->get(['id', 'role']);

        foreach ($managementUsers as $user) {
            foreach ($permissionIds as $permissionId) {
                $this->ensureAssignment(
                    (int) $user->id,
                    (int) $permissionId,
                    in_array($user->role, [User::ROLE_OWNER, User::ROLE_ADMIN], true)
                );
            }
        }

        // Preserve explicit staff access from the old coarse-grained system.
        // New capabilities that did not previously exist are intentionally not
        // granted to staff.
        // Historical conversion belongs to this migration, not runtime configuration.
        $legacyMappings = require database_path('data/legacy_permission_mappings.php');
        $legacyMappings['xem_thong_ke_he_thong'] = [
            'statistics.view-overview', 'statistics.view-staff', 'statistics.view-customers', 'statistics.export',
        ];
        foreach ($legacyMappings as $legacyCode => $newCodes) {
            $staffIds = $this->staffIdsWithActiveLegacyPermission($legacyCode);

            foreach ($staffIds as $staffId) {
                foreach ($newCodes as $newCode) {
                    if (isset($permissionIds[$newCode])) {
                        $this->ensureAssignment((int) $staffId, (int) $permissionIds[$newCode], true);
                    }
                }
            }
        }

        // Order distribution transitions previously required both the
        // distribution permission and the order-management permission.
        $transitionStaffIds = array_values(array_intersect(
            $this->staffIdsWithActiveLegacyPermission('quan_ly_phan_phoi_don_hang'),
            $this->staffIdsWithActiveLegacyPermission('quan_ly_don_hang')
        ));
        foreach ($transitionStaffIds as $staffId) {
            $this->ensureAssignment(
                (int) $staffId,
                (int) $permissionIds['order-distributions.transition'],
                true
            );
        }

        // Completing a distributed order additionally touched financial data.
        $completeStaffIds = array_values(array_intersect(
            $transitionStaffIds,
            $this->staffIdsWithActiveLegacyPermission('quan_ly_tat_ca_giao_dich_nguoi_dung')
        ));
        foreach ($completeStaffIds as $staffId) {
            $this->ensureAssignment(
                (int) $staffId,
                (int) $permissionIds['order-distributions.complete'],
                true
            );
        }
    }

    public function down(): void
    {
        $codes = app(PermissionRegistry::class)->codes();
        $permissionIds = DB::table('manager_settings')
            ->whereIn('manager_code', $codes)
            ->pluck('id');

        DB::table('user_manager_settings')
            ->whereIn('manager_setting_id', $permissionIds)
            ->delete();

        DB::table('manager_settings')
            ->whereIn('manager_code', $codes)
            ->delete();
    }

    private function upsertPermission(string $code, string $label): int
    {
        $id = DB::table('manager_settings')
            ->where('manager_code', $code)
            ->value('id');

        if ($id) {
            DB::table('manager_settings')
                ->where('id', $id)
                ->update([
                    'manager_name' => $label,
                    'updated_at' => now(),
                ]);

            return (int) $id;
        }

        return (int) DB::table('manager_settings')->insertGetId([
            'manager_name' => $label,
            'manager_code' => $code,
            'parent_manager_setting_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function ensureAssignment(int $userId, int $permissionId, bool $active): void
    {
        $assignmentId = DB::table('user_manager_settings')
            ->where('user_id', $userId)
            ->where('manager_setting_id', $permissionId)
            ->value('id');

        if ($assignmentId) {
            if ($active) {
                DB::table('user_manager_settings')
                    ->where('id', $assignmentId)
                    ->update(['is_active' => 1, 'updated_at' => now()]);
            }

            return;
        }

        DB::table('user_manager_settings')->insert([
            'user_id' => $userId,
            'manager_setting_id' => $permissionId,
            'is_active' => $active ? 1 : 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function staffIdsWithActiveLegacyPermission(string $legacyCode): array
    {
        return DB::table('user_manager_settings as ums')
            ->join('manager_settings as ms', 'ms.id', '=', 'ums.manager_setting_id')
            ->join('users as u', 'u.id', '=', 'ums.user_id')
            ->where('u.role', User::ROLE_STAFF)
            ->where('ums.is_active', 1)
            ->where('ms.manager_code', $legacyCode)
            ->pluck('u.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
};
