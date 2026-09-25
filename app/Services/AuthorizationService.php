<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Routing\Route;
use Illuminate\Support\Arr;

class AuthorizationService
{
    public const MODE_ALL = 'all';
    public const MODE_ANY = 'any';

    public function permissionCodes(User $user): array
    {
        if ($this->isSuperuser($user)) {
            return [];
        }

        $user->loadMissing('user_manager_settings.manager_setting');

        return $user->user_manager_settings
            ->filter(fn ($assignment) => (bool) $assignment->is_active && $assignment->manager_setting)
            ->pluck('manager_setting.manager_code')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function can(User $user, string $permission): bool
    {
        if ($this->isSuperuser($user)) {
            return true;
        }

        return in_array($permission, $this->permissionCodes($user), true);
    }

    public function canAny(User $user, array $permissions): bool
    {
        $permissions = $this->cleanPermissions($permissions);

        return $permissions === []
            || $this->isSuperuser($user)
            || count(array_intersect($permissions, $this->permissionCodes($user))) > 0;
    }

    public function canAll(User $user, array $permissions): bool
    {
        $permissions = $this->cleanPermissions($permissions);

        return $permissions === []
            || $this->isSuperuser($user)
            || count(array_diff($permissions, $this->permissionCodes($user))) === 0;
    }

    public function state(User $user, ?Route $route = null): array
    {
        $permissions = $this->permissionCodes($user);

        return [
            'role' => $user->role,
            'is_superuser' => $this->isSuperuser($user),
            'permissions' => $permissions,
            'version' => hash('sha256', $user->role.'|'.implode('|', $permissions)),
            'route' => $this->routeState($user, $route),
        ];
    }

    public function routeState(User $user, ?Route $route): array
    {
        if (!$route) {
            return [
                'requirements' => [],
                'refresh_permissions' => [],
                'can_access' => true,
            ];
        }

        $requirements = [];
        $refreshPermissions = [];
        $roleAllowed = true;

        foreach ($route->gatherMiddleware() as $middleware) {
            if (!is_string($middleware)) {
                continue;
            }

            [$name, $parameters] = array_pad(explode(':', $middleware, 2), 2, null);

            if (in_array($name, ['permission', 'checkPermission'], true) && $parameters !== null) {
                $arguments = array_values(array_filter(explode(',', $parameters), fn ($value) => $value !== ''));
                $requirements[] = $this->permissionRequirement($arguments);
                continue;
            }

            if ($name === 'authorization.context' && $parameters !== null) {
                $refreshPermissions = array_merge($refreshPermissions, explode(',', $parameters));
                continue;
            }

            if ($name === 'role' && $parameters !== null) {
                $roles = array_filter(explode('|', $parameters));
                $roleAllowed = in_array($user->role, $roles, true);
            }
        }

        $requirements = array_values(array_filter(
            $requirements,
            fn (array $requirement) => $requirement['permissions'] !== []
        ));

        $canAccessPermissions = collect($requirements)->every(
            fn (array $requirement) => $requirement['mode'] === self::MODE_ANY
                ? $this->canAny($user, $requirement['permissions'])
                : $this->canAll($user, $requirement['permissions'])
        );

        return [
            'requirements' => $requirements,
            'refresh_permissions' => $this->cleanPermissions($refreshPermissions),
            'can_access' => $roleAllowed && $canAccessPermissions,
        ];
    }

    public function permissionRequirement(array $arguments): array
    {
        $mode = self::MODE_ALL;

        if (isset($arguments[0]) && in_array($arguments[0], [self::MODE_ALL, self::MODE_ANY], true)) {
            $mode = array_shift($arguments);
        }

        return [
            'mode' => $mode,
            'permissions' => $this->cleanPermissions($arguments),
        ];
    }

    public function isSuperuser(User $user): bool
    {
        return $user->role === User::ROLE_OWNER;
    }

    public function isManagementUser(User $user): bool
    {
        return in_array($user->role, User::MANAGEMENT_ROLES, true);
    }

    public function canDeleteChatMessages(User $user): bool
    {
        return $this->isSuperuser($user);
    }

    public function canViewOperatorChats(User $actor, User $operator): bool
    {
        if ($this->isSuperuser($actor)) {
            return in_array($operator->role, [User::ROLE_ADMIN, User::ROLE_STAFF], true)
                || ($operator->role === User::ROLE_OWNER && (int) $operator->id === (int) $actor->id);
        }

        if ($actor->role === User::ROLE_ADMIN) {
            if ((int) $actor->id === (int) $operator->id) {
                return true;
            }

            return $operator->role === User::ROLE_STAFF
                && $this->can($actor, config('authorization.capabilities.chats_view_all'));
        }

        return $actor->role === User::ROLE_STAFF
            && $operator->role === User::ROLE_STAFF
            && (int) $actor->id === (int) $operator->id;
    }

    public function canViewConversation(User $actor, Conversation $conversation): bool
    {
        if ($actor->role === User::ROLE_MEMBER) {
            return (int) $conversation->user_id === (int) $actor->id;
        }

        $conversation->loadMissing('staff:id,role');

        return $conversation->staff
            ? $this->canViewOperatorChats($actor, $conversation->staff)
            : false;
    }

    public function canDispatchConversation(User $actor, Conversation $conversation): bool
    {
        return in_array($actor->role, [User::ROLE_ADMIN, User::ROLE_OWNER], true)
            && $this->canViewConversation($actor, $conversation);
    }

    public function canReceiveDispatchedConversation(User $actor, User $target): bool
    {
        return $target->status === 'activated' && (
            $target->role === User::ROLE_STAFF
                && in_array($actor->role, [User::ROLE_ADMIN, User::ROLE_OWNER], true)
            || $target->role === User::ROLE_ADMIN && $actor->role === User::ROLE_OWNER
        );
    }

    public function visibleTeamChatRoles(User $actor): array
    {
        if ($this->isSuperuser($actor)) {
            return [User::ROLE_STAFF, User::ROLE_ADMIN];
        }

        if (
            $actor->role === User::ROLE_ADMIN
            && $this->can($actor, config('authorization.capabilities.chats_view_all'))
        ) {
            return [User::ROLE_STAFF];
        }

        return [];
    }

    public function canManageOperator(User $actor, User $target): bool
    {
        if ($this->isSuperuser($actor)) {
            return in_array($target->role, [User::ROLE_ADMIN, User::ROLE_STAFF], true);
        }

        return $actor->role === User::ROLE_ADMIN
            && $target->role === User::ROLE_STAFF;
    }

    public function canManageOperatorPermissions(User $actor, User $target): bool
    {
        return $this->canManageOperator($actor, $target);
    }

    public function manageableOperatorRoles(User $actor): array
    {
        return $this->isSuperuser($actor)
            ? [User::ROLE_ADMIN, User::ROLE_STAFF]
            : [User::ROLE_STAFF];
    }

    private function cleanPermissions(array $permissions): array
    {
        return collect(Arr::flatten($permissions))
            ->filter(fn ($permission) => is_string($permission) && trim($permission) !== '')
            ->map(fn ($permission) => trim($permission))
            ->unique()
            ->values()
            ->all();
    }
}
