<?php

namespace App\Http\Middleware;

use App\Services\AuthorizationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function __construct(private AuthorizationService $authorization)
    {
    }

    public function handle(Request $request, Closure $next, ...$arguments): Response
    {
        $user = $request->user();
        $requirement = $this->authorization->permissionRequirement($arguments);
        $allowed = $requirement['mode'] === AuthorizationService::MODE_ANY
            ? $this->authorization->canAny($user, $requirement['permissions'])
            : $this->authorization->canAll($user, $requirement['permissions']);

        if ($allowed) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(403, 'Bạn không có quyền thực hiện thao tác này.');
        }

        return redirect()
            ->route(config('authorization.fallback_route', 'chat-panel'))
            ->with('error', 'Bạn không có quyền truy cập chức năng này.');
    }
}
