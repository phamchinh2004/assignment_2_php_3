<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function index()
    {
        //View trang đăng ký
        return view('login');
    }
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => [
                'required',
                'string',
                'max:50',
                'regex:/^\S+$/', // Không cho phép khoảng trắng (cả đầu, giữa và cuối)
            ],
            'password' => [
                'required',
                'string',
                'min:6', // Mật khẩu phải có ít nhất 6 ký tự
                'regex:/^\S+$/', // Không cho phép khoảng trắng trong mật khẩu
            ],
        ], [
            'username.required' => 'Vui lòng nhập tên đăng nhập!',
            'username.string' => 'Tên đăng nhập phải là chuỗi ký tự hợp lệ!',
            'username.max' => 'Tên đăng nhập không được vượt quá 50 ký tự!',
            'username.regex' => 'Tên đăng nhập không được chứa khoảng trắng!',
            'password.required' => 'Vui lòng nhập mật khẩu!',
            'password.string' => 'Mật khẩu phải là chuỗi ký tự!',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự!',
            'password.regex' => 'Mật khẩu không được chứa khoảng trắng!',
        ]);
        $get_user_from_username = User::where('username', $request->username)->first();
        if (!$get_user_from_username) {
            return back()->with('error', 'Sai tên đăng nhập hoặc mật khẩu!');
        } else {
            if (Auth::attempt($credentials, $request->remember_password)) {
                if ($get_user_from_username->status == "activated") {
                    $request->session()->regenerate();
                    if ($get_user_from_username->role == "member") {
                        return redirect()->route('home')->with('success', 'Đăng nhập thành công!');
                    } else {
                        return redirect()->route('chat-panel')->with('success', 'Đăng nhập thành công!');
                    }
                } elseif ($get_user_from_username->status == "inactivated") {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    return back()->with('error', 'Tài khoản chưa được kích hoạt, vui lòng liên hệ CSKH!');
                } else {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    return back()->with('error', 'Tài khoản đã bị khóa, vui lòng liên hệ CSKH!');
                }
            } else {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return back()->with('error', 'Mật khẩu không chính xác!');
            }
        }
    }
    public function check_username()
    {
        $username = request()->input('username');
        if ($username) {
            $get_user_by_username = User::where('username', $username)->first();
            if ($get_user_by_username) {
                if ($get_user_by_username->status === "inactivated") {
                    $response = [
                        'success' => true,
                        'message' => 'Vui lòng liên hệ CSKH để kích hoạt tài khoản!',
                        'refresh' => true
                    ];
                } elseif ($get_user_by_username->status === "banned") {
                    $response = [
                        'success' => true,
                        'message' => 'Tài khoản bị khóa, vui lòng liên hệ CSKH!',
                        'refresh' => true
                    ];
                } else {
                    $response = [
                        'success' => true,
                        'message' => 'Người dùng hợp lệ!',
                        'refresh' => false
                    ];
                }
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Người dùng không tồn tại!',
                    'refresh' => true
                ];
            }
            return response()->json($response);
        }
    }
    public function check_phone()
    {
        $phone = request()->input('phone');
        if ($phone) {
            $get_user_by_phone = User::where('phone', $phone)->first();
            if ($get_user_by_phone) {
                $response = [
                    'success' => true,
                    'message' => 'Người dùng hợp lệ!',
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Không thể kiểm tra người dùng!',
                ];
            }
            return response()->json($response);
        }
    }
    public function log_out(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->with('success', 'Đăng xuất thành công!');
    }
    public function log_out_by_locked(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->with('warning', 'Bạn đã bị khóa tài khoản!');
    }
    public function change_password()
    {
        $present_password = request()->input('present_password');
        $new_password = request()->input('new_password');
        if (Hash::check($present_password, Auth::user()->password)) {
            $user = User::find(Auth::user()->id);
            if ($user) {
                $user->password = Hash::make($new_password);
                $user->save();
                return response()->json([
                    'status' => 200,
                    'message' => "Đổi mật khẩu thành công!"
                ]);
            } else {
                return response()->json([
                    'status' => 403,
                    'message' => "Không tìm thấy người dùng!"
                ]);
            }
        } else {
            return response()->json([
                'status' => 400,
                'message' => "Mật khẩu hiện tại không chính xác!"
            ]);
        }
    }
}
