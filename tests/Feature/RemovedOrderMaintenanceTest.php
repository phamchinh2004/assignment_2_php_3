<?php

namespace Tests\Feature;

use App\Models\Manager_setting;
use App\Models\User;
use App\Models\User_manager_setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RemovedOrderMaintenanceTest extends TestCase
{
    public function test_old_urls_return_404_with_or_without_the_old_permission(): void
    {
        $paths = [
            '/admin/order/add-customer-info',
            '/admin/order/update-status-history',
            '/admin/order/update-commission-paid',
            '/admin/order/update-frozen-commission-percentage',
        ];

        foreach ([User::ROLE_ADMIN, User::ROLE_STAFF] as $role) {
            foreach ([true, false] as $granted) {
                $permission = (new Manager_setting)->forceFill(['id' => 1, 'manager_code' => 'orders.maintenance']);
                $assignment = (new User_manager_setting)->forceFill(['is_active' => $granted]);
                $assignment->setRelation('manager_setting', $permission);
                $user = (new User)->forceFill(['id' => 1, 'role' => $role, 'status' => 'activated']);
                $user->setRelation('user_manager_settings', collect([$assignment]));
                $this->actingAs($user);

                foreach ($paths as $path) {
                    $this->getJson($path)->assertNotFound();
                }
            }
        }
    }

    public function test_normal_order_routes_still_resolve(): void
    {
        foreach ([
            '/admin/order' => 'order.index',
            '/admin/order/create' => 'order.create',
            '/admin/order/123' => 'order.show',
            '/admin/order/123/edit' => 'order.edit',
        ] as $path => $name) {
            $this->assertSame($name, Route::getRoutes()->match(Request::create($path))->getName());
        }
    }
}
