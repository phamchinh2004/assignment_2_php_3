<?php

namespace Tests\Unit;

use App\Models\Manager_setting;
use App\Models\User;
use App\Models\User_manager_setting;
use App\Services\AuthorizationService;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class AuthorizationServiceTest extends TestCase
{
    private AuthorizationService $authorization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authorization = new AuthorizationService();
    }

    public function test_staff_permissions_are_resolved_from_active_assignments(): void
    {
        $staff = $this->staffWithPermissions(['orders.manage'], ['orders.delete']);

        $this->assertTrue($this->authorization->can($staff, 'orders.manage'));
        $this->assertFalse($this->authorization->can($staff, 'orders.delete'));
        $this->assertTrue($this->authorization->canAny($staff, ['orders.delete', 'orders.manage']));
        $this->assertFalse($this->authorization->canAll($staff, ['orders.manage', 'orders.delete']));
    }

    public function test_admin_is_superuser_without_assignments(): void
    {
        $admin = new User();
        $admin->role = User::ROLE_ADMIN;

        $this->assertTrue($this->authorization->can($admin, 'anything'));
        $this->assertTrue($this->authorization->canAll($admin, ['a', 'b']));
        $this->assertTrue($this->authorization->canAny($admin, ['a', 'b']));
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
            'role:staff|admin',
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
        $route->middleware(['role:staff|admin', 'permission:orders.manage']);

        $this->assertFalse($this->authorization->routeState($staff, $route)['can_access']);
    }

    private function staffWithPermissions(array $active, array $inactive = []): User
    {
        $staff = new User();
        $staff->role = User::ROLE_STAFF;

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
