<?php

namespace App\Http\Middleware;

use App\Models\Manager_setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $permission): Response
    {
        if (Auth::user()->role == 'admin') {
            return $next($request);
        }
        $managerSetting = Manager_setting::where('manager_code', $permission)->first();
        if (!$managerSetting) {
            abort(403, 'Chức năng quản lý không tồn tại.');
        }
        $userHasPermission = Auth::user()->user_manager_settings()
            ->where('manager_setting_id', $managerSetting->id)
            ->where('is_active', 1) // Chỉ lấy những quyền đang active
            ->exists();

        if (!$userHasPermission) {
            return redirect()->route('admin.dashboard')->with(['error' => 'Bạn không có quyền truy cập chức năng này.']);
        }
        return $next($request);
    }
}
