<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\ApproximateLocationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class UpdateApproximateLocation
{
    public function __construct(
        private readonly ApproximateLocationService $approximateLocationService,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()
            && Auth::user()->role === User::ROLE_MEMBER
            && !$request->routeIs('location.approximate.update')) {
            $user = Auth::user();
            $cacheKey = 'approx-location-request-refresh:' . $user->id;
            $forceRefreshKey = 'approx-location-force-refresh:' . $user->id;
            $forceRefresh = Cache::has($forceRefreshKey);

            if ($forceRefresh || !Cache::has($cacheKey)) {
                try {
                    $resolved = $this->approximateLocationService->refresh($user, $request, $forceRefresh);

                    if ($forceRefresh && $resolved) {
                        Cache::forget($forceRefreshKey);
                    }
                } catch (\Throwable) {
                    // Approximate location must never prevent the user from using the site.
                }

                if (!$forceRefresh) {
                    Cache::put(
                        $cacheKey,
                        true,
                        now()->addMinutes(ApproximateLocationService::REFRESH_MINUTES)
                    );
                }
            }
        }

        return $next($request);
    }
}
