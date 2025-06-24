<?php

namespace App\Http\Middleware;

use App\Models\User_ban;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserBanned
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

            // Kiểm tra trạng thái "banned"
            if ($user->status === 'banned') {
                // Tìm thông tin ban của người dùng
                $bannedUser = User_ban::where('user_id', $user->id)
                    ->where('is_active', 1)
                    ->first();

                $reason = $bannedUser ? 'Lý do: ' . $bannedUser->reason : 'Không có lý do cụ thể.';

                // Đăng xuất người dùng
                Auth::logout();

                // Gửi thông báo Toastr
                session()->flash('statusError', 'Tài khoản của bạn đã bị khóa! ' . $reason);

                // Chuyển hướng về trang đăng nhập hoặc trang chủ
                return redirect()->route('login');
            }
        }
        return $next($request);
    }
}
