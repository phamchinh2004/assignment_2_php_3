<?php

namespace App\Services;

use Illuminate\Support\Collection;

class PermissionRegistry
{
    public function modules(): array
    {
        return config('authorization.modules', []);
    }

    public function permissions(): array
    {
        $permissions = [];

        foreach ($this->modules() as $moduleKey => $module) {
            foreach ($module['permissions'] ?? [] as $actionKey => $permission) {
                $permissions[$permission['code']] = [
                    'code' => $permission['code'],
                    'label' => $permission['label'],
                    'module' => $moduleKey,
                    'module_label' => $module['label'],
                    'action' => $actionKey,
                ];
            }
        }

        return $permissions;
    }

    public function codes(): array
    {
        return array_keys($this->permissions());
    }

    public function contains(string $code): bool
    {
        return isset($this->permissions()[$code]);
    }

    public function settingGroups(): array
    {
        $retired = config('authorization.retired_permissions', []);
        $groups = \App\Models\Manager_setting::whereNull('parent_manager_setting_id')
            ->whereNotIn('manager_code', $retired)
            ->with(['children' => fn ($query) => $query->whereNotIn('manager_code', $retired)])
            ->orderBy('id')->get()->map(fn ($root) => [
                'key' => 'setting-'.$root->id,
                'label' => $root->manager_name,
                'root' => $root,
                'settings' => collect([$root])->concat($root->children),
            ])->all();

        return array_values($groups);
    }

    public function groups(Collection $assignments): array
    {
        $byId = $assignments->keyBy('manager_setting_id');
        $groups = [];
        foreach ($this->settingGroups() as $group) {
            $permissions = [];
            foreach ($group['settings'] as $setting) {
                $assignment = $byId->get($setting->id);
                if (! $assignment) {
                    continue;
                }
                $permissions[] = [
                    'code' => $setting->manager_code,
                    'label' => $setting->manager_name,
                    'assignment' => $assignment,
                    'active' => (bool) $assignment->is_active,
                ];
            }
            if ($permissions !== []) {
                $groups[] = ['key' => $group['key'], 'label' => $group['label'], 'permissions' => $permissions];
            }
        }

        return $groups;
    }
}
