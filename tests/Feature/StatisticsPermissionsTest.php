<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Manager_setting;
use App\Models\User_manager_setting;
use App\Http\Middleware\CheckPermission;
use Illuminate\Http\Request;
use Tests\TestCase;

class StatisticsPermissionsTest extends TestCase
{
    public function test_statistics_routes_require_their_own_permission_and_reject_other_permissions(): void
    {
        $cases = [
            'tong.doanh.thu' => 'overview',
            'doanh.thu.theo.nhan.vien' => 'staff',
            'doanh.thu.tu.khach.hang' => 'customers',
            'doanh.thu.ban.than' => 'personal',
            'api.statistical.revenue' => 'overview',
            'api.admin.statistical.revenue' => 'overview',
            'api.revenue.by.staff' => 'staff',
            'api.revenue.customer-detail' => 'customers',
        ];
        foreach ($cases as $name => $category) {
            $route = app('router')->getRoutes()->getByName($name);
            $code = 'statistics.view-'.$category;
            $this->assertContains('permission:'.$code, $route->gatherMiddleware(), $name);
            foreach (['overview', 'staff', 'customers', 'personal', 'system'] as $granted) {
                $user = new User(['role' => 'staff']);
                $assignment = new User_manager_setting(['is_active' => true]);
                $assignment->setRelation('manager_setting', new Manager_setting(['manager_code' => 'statistics.view-'.$granted]));
                $user->setRelation('user_manager_settings', collect([$assignment]));
                $request = Request::create('/test');
                $request->setUserResolver(fn () => $user);
                $response = app(CheckPermission::class)->handle($request, fn () => response('allowed'), $code);
                $this->assertSame($granted === $category ? 200 : 302, $response->getStatusCode(), $name.' / '.$granted);
            }
        }
    }
}
