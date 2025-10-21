<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastSeen
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Update last_seen mỗi 2 phút (tránh quá nhiều DB writes)
            $cacheKey = 'user-last-seen-' . $user->id;
            
            if (!Cache::has($cacheKey)) {
                $user->update(['last_seen' => now()]);
                
                // Cache 2 phút
                Cache::put($cacheKey, true, now()->addMinutes(2));
            }
        }
        
        return $next($request);
    }
}
