<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\PermissionRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AuthorizationPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = app(PermissionRegistry::class)->permissions();
        $permissionIds = [];

        foreach ($permissions as $code => $permission) {
            $id = DB::table('manager_settings')->where('manager_code', $code)->value('id');

            if ($id) {
                DB::table('manager_settings')->where('id', $id)->update([
                    'manager_name' => $permission['label'],
                    'updated_at' => now(),
                ]);
                $permissionIds[] = (int) $id;
                continue;
            }

            $permissionIds[] = (int) DB::table('manager_settings')->insertGetId([
                'manager_name' => $permission['label'],
                'manager_code' => $code,
                'parent_manager_setting_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $users = DB::table('users')
            ->whereIn('role', [User::ROLE_OWNER, User::ROLE_ADMIN, User::ROLE_STAFF])
            ->get(['id', 'role']);

        foreach ($users as $user) {
            foreach ($permissionIds as $permissionId) {
                $assignment = DB::table('user_manager_settings')
                    ->where('user_id', $user->id)
                    ->where('manager_setting_id', $permissionId)
                    ->first();

                if (!$assignment) {
                    DB::table('user_manager_settings')->insert([
                        'user_id' => $user->id,
                        'manager_setting_id' => $permissionId,
                        'is_active' => in_array($user->role, [User::ROLE_OWNER, User::ROLE_ADMIN], true) ? 1 : 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    continue;
                }

                if (in_array($user->role, [User::ROLE_OWNER, User::ROLE_ADMIN], true) && !$assignment->is_active) {
                    DB::table('user_manager_settings')
                        ->where('id', $assignment->id)
                        ->update(['is_active' => 1, 'updated_at' => now()]);
                }
            }
        }
    }
}
