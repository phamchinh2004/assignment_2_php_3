<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $roles): Response
    {
        $roles = explode('|', $roles);
        // Nếu route yêu cầu guest và người dùng chưa đăng nhập
        if (!Auth::check()) {
            if (in_array('guest', $roles)) {
                return $next($request);
            }
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Nếu route yêu cầu guest nhưng đã đăng nhập → chặn
        if (in_array('guest', $roles)) {
            switch ($user->role) {
                case 'member':
                    return redirect()->route('home')->with('warning', 'Bạn đã đăng nhập!');
                case 'staff':
                    return redirect()->route('admin.dashboard')->with('warning', 'Bạn đã đăng nhập!');
                case 'admin':
                    return redirect()->route('admin.dashboard')->with('warning', 'Bạn đã đăng nhập!');
                default:
                    abort(403, 'Bạn đã đăng nhập, vui lòng quay lại trang chủ.');
            }
        }

        // Nếu vai trò hợp lệ
        if (in_array($user->role, $roles)) {
            return $next($request);
        }
        // Nếu không có quyền truy cập
        switch ($user->role) {
            case 'member':
                abort(403, 'NOT FOUND.');
            case 'staff':
                return redirect()->route('admin.dashboard')->with('warning', 'Bạn không có quyền truy cập!');
            case 'admin':
                return redirect()->route('admin.dashboard');
            default:
                abort(403, 'Bạn không có quyền truy cập.');
        }
    }
}
