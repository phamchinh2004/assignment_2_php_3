<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AuthorizationController extends Controller
{
    public function state(Request $request, AuthorizationService $authorization, Router $router)
    {
        $user = $request->user();
        $path = $request->query('path');
        $route = $request->route();

        if (is_string($path) && str_starts_with($path, '/admin')) {
            try {
                $probe = Request::create($path, 'GET');
                $probe->setUserResolver(fn () => $user);
                $route = $router->getRoutes()->match($probe);
            } catch (NotFoundHttpException) {
                $route = null;
            }
        }

        return response()->json($authorization->state($user, $route));
    }
}
