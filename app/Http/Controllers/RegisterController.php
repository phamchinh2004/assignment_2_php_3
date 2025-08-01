<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Rank;
use App\Models\User;
use App\Models\User_spin_progress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $ip = $request->ip();

        // Kiểm tra số tài khoản đã tạo từ IP này trong 1 giờ qua
        $count = User::where('register_ip', $ip)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($count >= 3) {
            return back()->with('error', 'Bạn đã tạo quá nhiều tài khoản trong thời gian ngắn. Vui lòng thử lại sau!');
        }

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
            'email' => [
                'required',
                'string',
                'email',
                'max:100',
                'unique:users,email',
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
            'email.required' => 'Email không được để trống!',
            'email.email' => 'Email không đúng định dạng!',
            'email.max' => 'Email không được vượt quá 100 ký tự!',
            'email.unique' => 'Email đã tồn tại trong hệ thống!',
        ]);

        $request->session()->put('registration_data', $data);
        $user = new User();
        if ($request->referral_code) {
            $get_user = User::where('referral_code', $request->referral_code)
                ->whereIn('role', ['admin', 'staff'])
                ->first();

            if (!$get_user) {
                return back()->with('error', 'Mã mời không hợp lệ, vui lòng thử lại!');
            }
            $user->referrer_id = $get_user->id;
            $user->status = "activated";
        }
        $user->full_name = $request->full_name ? $request->full_name : 'Chưa đặt tên';
        $user->username = $request->username;
        $user->phone = $request->phone;
        $user->email = $request->email;
        $user->referral_code = $this->return_random_referral_code();
        $user->password = Hash::make($request->password);
        $user->register_ip = $ip;
        $user->save();
        session()->forget('registration_data');
        if ($user->referrer_id) {
            $get_user = User::where('referral_code', $request->referral_code)->first();
            Conversation::create([
                'staff_id' => $get_user->id,
                'user_id' => $user->id
            ]);
            Auth::login($user);
            $request->session()->regenerate();
            return redirect()->route('home')->with('success', 'Đăng nhập thành công!');
        } else {
            $get_admin = User::where('role', 'admin')->first();
            if($get_admin){
                Conversation::create([
                    'staff_id' => $get_admin->id,
                    'user_id' => $user->id
                ]);
            }
            return redirect()->route('login')->with('success', 'Tạo tài khoản thành công, vui lòng liên hệ CSKH để kích hoạt tài khoản!');
        }
    }

    public function check_referral_code()
    {
        $referral_code = request()->input('referral_code');
        if ($referral_code) {
            $get_user_by_referral_code = User::where('referral_code', $referral_code)->whereIn('role', ['admin', 'staff'])->first();
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
        } else {
            $response = [
                'success' => false,
                'message' => 'Không lấy được dữ liệu người dùng!',
            ];
            return response()->json($response);
        }
    }
    public function check_email()
    {
        $email = request()->input('email');
        if ($email) {
            $get_user_by_email = User::where('email', $email)->first();
            if ($get_user_by_email) {
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
        } else {
            $response = [
                'success' => false,
                'message' => 'Không lấy được dữ liệu người dùng!',
            ];
            return response()->json($response);
        }
    }
}
