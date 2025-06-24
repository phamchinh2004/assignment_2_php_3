<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\User_spin_progress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function index()
    {
        //View trang đăng ký
        return view('register');
    }
    public function return_random_referral_code()
    {
        do {
            $random_number = random_int(100000, 999999);
            $exists = User::where('referral_code', $random_number)->exists();
        } while ($exists);

        return $random_number;
    }
    public function register(Request $request)
    {
        // Xác thực dữ liệu đầu vào với các quy tắc tương ứng
        $data = $request->validate([
            'username' => [
                'required',
                'string',
                'unique:users,username',
                'min:6', // Kiểm tra độ dài tên đăng nhập
                'max:50',
                'regex:/^[a-zA-Z0-9_]+$/', // Kiểm tra chỉ chứa chữ cái, số và dấu gạch dưới
            ],
            'phone' => [
                'required',
                'numeric',
                'digits:10', // Kiểm tra độ dài số điện thoại (10 ký tự)
            ],
            'password' => [
                'required',
                'string',
                'min:6', // Mật khẩu tối thiểu 6 ký tự
                'max:50',
                // 'confirmed', // Xác nhận mật khẩu khớp
                'regex:/^\S+$/', // Không chứa khoảng trắng
            ]
        ], [
            'username.required' => 'Tên người dùng không được để trống!',
            'username.min' => 'Tên người dùng phải từ 6 ký tự trở lên!',
            'username.unique' => 'Tên người dùng đã tồn tại!',
            'username.regex' => 'Tên người dùng chỉ được chứa chữ cái, số và dấu gạch dưới!',
            'phone.required' => 'Số điện thoại không được để trống!',
            'phone.numeric' => 'Số điện thoại phải là số!',
            'phone.digits' => 'Số điện thoại phải có 10 chữ số!',
            'password.required' => 'Mật khẩu không được để trống!',
            'password.min' => 'Mật khẩu phải từ 6 ký tự trở lên!',
            // 'password.confirmed' => 'Mật khẩu xác nhận không khớp!',
            'password.regex' => 'Mật khẩu không được chứa khoảng trắng!',
        ]);

        $request->session()->put('registration_data', $data);
        $user = new User();
        $user->full_name = $request->full_name ? $request->full_name : 'Chưa đặt tên';
        $user->referral_code = $request->referral_code ?: $request->referral_code;
        $user->username = $request->username;
        $user->phone = $request->phone;
        $user->referral_code = $this->return_random_referral_code();
        $user->password = Hash::make($request->password);
        $user->save();
        session()->forget('registration_data');
        return redirect()->route('login')->with('success', 'Tạo tài khoản thành công, vui lòng liên hệ CSKH để kích hoạt tài khoản!');
    }

    public function check_referral_code()
    {
        $referral_code = request()->input('referral_code');
        if ($referral_code) {
            $get_user_by_referral_code = User::where('referral_code', $referral_code)->first();
            if ($get_user_by_referral_code) {
                $response = [
                    'success' => true,
                    'message' => 'Lấy được dữ liệu người dùng!',
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Không lấy được dữ liệu người dùng!',
                ];
            }
            return response()->json($response);
        }
    }
}
