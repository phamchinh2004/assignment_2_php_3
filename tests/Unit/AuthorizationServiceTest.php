<?php

namespace Tests\Unit;

use App\Models\Conversation;
use App\Models\Manager_setting;
use App\Models\User;
use App\Models\User_manager_setting;
use App\Services\AuthorizationService;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class AuthorizationServiceTest extends TestCase
{
    private AuthorizationService $authorization;

    protected function setUp(): void
    {
        parent::setUp();

        Container::getInstance()->instance('config', new Repository([
            'authorization' => [
                'capabilities' => [
                    'chats_view_all' => 'chats.view-all',
                ],
            ],
        ]));

        $this->authorization = new AuthorizationService();
    }

    public function test_owner_can_manage_admin_permissions_without_allowing_admin_escalation(): void
    {
        $owner = new User(['role' => User::ROLE_OWNER]);
        $admin = new User(['role' => User::ROLE_ADMIN]);
        $staff = new User(['role' => User::ROLE_STAFF]);
        $member = new User(['role' => User::ROLE_MEMBER]);
        $this->assertTrue($this->authorization->canManageOperatorPermissions($owner, $admin));
        $this->assertTrue($this->authorization->canManageOperatorPermissions($owner, $staff));
        $this->assertTrue($this->authorization->canManageOperatorPermissions($admin, $staff));
        $this->assertFalse($this->authorization->canManageOperatorPermissions($admin, $admin));
        $this->assertFalse($this->authorization->canManageOperatorPermissions($admin, $owner));
        $this->assertFalse($this->authorization->canManageOperatorPermissions($owner, $owner));
        $this->assertFalse($this->authorization->canManageOperatorPermissions($owner, $member));
        $this->assertFalse($this->authorization->canManageOperatorPermissions($staff, $admin));
    }

    public function test_staff_permissions_are_resolved_from_active_assignments(): void
    {
        $staff = $this->staffWithPermissions(['orders.manage'], ['orders.delete']);

        $this->assertTrue($this->authorization->can($staff, 'orders.manage'));
        $this->assertFalse($this->authorization->can($staff, 'orders.delete'));
        $this->assertTrue($this->authorization->canAny($staff, ['orders.delete', 'orders.manage']));
        $this->assertFalse($this->authorization->canAll($staff, ['orders.manage', 'orders.delete']));
    }

    public function test_admin_without_assignments_is_not_superuser(): void
    {
        $admin = new User();
        $admin->role = User::ROLE_ADMIN;
        $admin->setRelation('user_manager_settings', collect());

        $this->assertFalse($this->authorization->isSuperuser($admin));
        $this->assertFalse($this->authorization->can($admin, 'anything'));
        $this->assertFalse($this->authorization->canAll($admin, ['a', 'b']));
        $this->assertFalse($this->authorization->canAny($admin, ['a', 'b']));
        $this->assertFalse($this->authorization->canDeleteChatMessages($admin));
    }

    public function test_admin_permissions_are_resolved_like_staff_permissions(): void
    {
        $admin = $this->userWithPermissions(User::ROLE_ADMIN, ['orders.manage'], ['orders.delete']);

        $this->assertTrue($this->authorization->can($admin, 'orders.manage'));
        $this->assertFalse($this->authorization->can($admin, 'orders.delete'));
    }

    public function test_owner_is_the_only_superuser_and_can_delete_chat_messages(): void
    {
        $owner = new User();
        $owner->role = User::ROLE_OWNER;

        $this->assertTrue($this->authorization->isSuperuser($owner));
        $this->assertTrue($this->authorization->can($owner, 'anything'));
        $this->assertTrue($this->authorization->canAll($owner, ['a', 'b']));
        $this->assertTrue($this->authorization->canAny($owner, ['a', 'b']));
        $this->assertTrue($this->authorization->canDeleteChatMessages($owner));
    }

    public function test_admin_with_chats_view_all_can_view_staff_and_own_chats_but_not_other_admin_chats(): void
    {
        $admin = $this->userWithPermissions(
            User::ROLE_ADMIN,
            [config('authorization.capabilities.chats_view_all')]
        );
        $admin->id = 10;

        $staff = new User();
        $staff->id = 20;
        $staff->role = User::ROLE_STAFF;

        $otherAdmin = new User();
        $otherAdmin->id = 30;
        $otherAdmin->role = User::ROLE_ADMIN;

        $this->assertTrue($this->authorization->canViewOperatorChats($admin, $admin));
        $this->assertTrue($this->authorization->canViewOperatorChats($admin, $staff));
        $this->assertFalse($this->authorization->canViewOperatorChats($admin, $otherAdmin));
        $this->assertSame([User::ROLE_STAFF], $this->authorization->visibleTeamChatRoles($admin));
    }

    public function test_owner_can_view_both_staff_and_admin_chat_groups(): void
    {
        $owner = new User();
        $owner->id = 1;
        $owner->role = User::ROLE_OWNER;

        $staff = new User();
        $staff->id = 20;
        $staff->role = User::ROLE_STAFF;

        $admin = new User();
        $admin->id = 30;
        $admin->role = User::ROLE_ADMIN;

        $this->assertTrue($this->authorization->canViewOperatorChats($owner, $staff));
        $this->assertTrue($this->authorization->canViewOperatorChats($owner, $admin));
        $this->assertTrue($this->authorization->canViewOperatorChats($owner, $owner));
        $this->assertSame(
            [User::ROLE_STAFF, User::ROLE_ADMIN],
            $this->authorization->visibleTeamChatRoles($owner)
        );
    }

    public function test_chat_dispatch_requires_management_role_and_conversation_access(): void
    {
        $staff = $this->staffWithPermissions([]);
        $staff->id = 20;
        $admin = $this->userWithPermissions(User::ROLE_ADMIN, [config('authorization.capabilities.chats_view_all')]);
        $admin->id = 10;
        $limitedAdmin = $this->userWithPermissions(User::ROLE_ADMIN, []);
        $limitedAdmin->id = 30;
        $owner = new User();
        $owner->id = 40;
        $owner->role = User::ROLE_OWNER;

        $conversation = new Conversation();
        $conversation->staff_id = $staff->id;
        $conversation->setRelation('staff', $staff);

        $this->assertFalse($this->authorization->canDispatchConversation($staff, $conversation));
        $this->assertTrue($this->authorization->canDispatchConversation($admin, $conversation));
        $this->assertFalse($this->authorization->canDispatchConversation($limitedAdmin, $conversation));
        $this->assertTrue($this->authorization->canDispatchConversation($owner, $conversation));

        $conversation->staff_id = $limitedAdmin->id;
        $conversation->setRelation('staff', $limitedAdmin);
        $this->assertTrue($this->authorization->canDispatchConversation($limitedAdmin, $conversation));
        $this->assertFalse($this->authorization->canDispatchConversation($admin, $conversation));

        $conversation->staff_id = $admin->id;
        $conversation->setRelation('staff', $admin);

        $this->assertTrue($this->authorization->canDispatchConversation($admin, $conversation));
        $this->assertFalse($this->authorization->canDispatchConversation($limitedAdmin, $conversation));
        $this->assertTrue($this->authorization->canDispatchConversation($owner, $conversation));
    }

    public function test_chat_dispatch_recipients_are_restricted_by_role_and_status(): void
    {
        $staff = new User();
        $staff->role = User::ROLE_STAFF;
        $staff->status = 'activated';
        $admin = new User();
        $admin->role = User::ROLE_ADMIN;
        $admin->status = 'activated';
        $owner = new User();
        $owner->role = User::ROLE_OWNER;
        $owner->status = 'activated';

        $this->assertTrue($this->authorization->canReceiveDispatchedConversation($admin, $staff));
        $this->assertFalse($this->authorization->canReceiveDispatchedConversation($admin, $admin));
        $this->assertTrue($this->authorization->canReceiveDispatchedConversation($owner, $staff));
        $this->assertTrue($this->authorization->canReceiveDispatchedConversation($owner, $admin));
        $this->assertFalse($this->authorization->canReceiveDispatchedConversation($staff, $staff));
        $this->assertFalse($this->authorization->canReceiveDispatchedConversation($owner, $owner));

        $staff->status = 'banned';
        $this->assertFalse($this->authorization->canReceiveDispatchedConversation($owner, $staff));
    }

    public function test_permission_middleware_supports_any_and_all_modes(): void
    {
        $any = $this->authorization->permissionRequirement(['any', 'orders.view', 'orders.manage']);
        $all = $this->authorization->permissionRequirement(['all', 'orders.view', 'orders.manage']);
        $default = $this->authorization->permissionRequirement(['orders.manage']);

        $this->assertSame('any', $any['mode']);
        $this->assertSame(['orders.view', 'orders.manage'], $any['permissions']);
        $this->assertSame('all', $all['mode']);
        $this->assertSame('all', $default['mode']);
    }

    public function test_route_state_is_derived_from_route_metadata(): void
    {
        $staff = $this->staffWithPermissions(['orders.manage']);
        $route = new Route(['GET'], 'admin/orders', fn () => null);
        $route->middleware([
            'role:staff|admin|own',
            'permission:orders.manage',
            'authorization.context:users.manage_all',
        ]);

        $state = $this->authorization->routeState($staff, $route);

        $this->assertTrue($state['can_access']);
        $this->assertSame(
            [['mode' => 'all', 'permissions' => ['orders.manage']]],
            $state['requirements']
        );
        $this->assertSame(['users.manage_all'], $state['refresh_permissions']);
    }

    public function test_route_state_denies_when_required_permission_is_missing(): void
    {
        $staff = $this->staffWithPermissions([]);
        $route = new Route(['GET'], 'admin/orders', fn () => null);
        $route->middleware(['role:staff|admin|own', 'permission:orders.manage']);

        $this->assertFalse($this->authorization->routeState($staff, $route)['can_access']);
    }

    private function staffWithPermissions(array $active, array $inactive = []): User
    {
        return $this->userWithPermissions(User::ROLE_STAFF, $active, $inactive);
    }

    private function userWithPermissions(string $role, array $active, array $inactive = []): User
    {
        $staff = new User();
        $staff->role = $role;

        $assignments = collect();
        foreach ($active as $code) {
            $assignments->push($this->assignment($code, true));
        }
        foreach ($inactive as $code) {
            $assignments->push($this->assignment($code, false));
        }

        $staff->setRelation('user_manager_settings', $assignments);

        return $staff;
    }

    private function assignment(string $code, bool $active): User_manager_setting
    {
        $permission = new Manager_setting();
        $permission->manager_code = $code;

        $assignment = new User_manager_setting();
        $assignment->is_active = $active;
        $assignment->setRelation('manager_setting', $permission);

        return $assignment;
    }
}
