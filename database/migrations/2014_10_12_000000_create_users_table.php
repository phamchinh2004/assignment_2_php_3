<?php

use App\Models\Rank;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('full_name')->comment('Họ và tên');
            $table->string('username')->unique()->comment('Tên đăng nhập');
            $table->string('email')->unique()->nullable()->comment('Email');
            $table->string('phone')->comment('Số điện thoại');
            $table->string('password')->comment('Mật khẩu');
            $table->integer('referral_code')->nullable()->comment('Mã giới thiệu');
            $table->string('username_bank')->nullable()->comment('Tên tài khoản ngân hàng');
            $table->string('bank_name')->nullable()->comment('Tên ngân hàng');
            $table->string('account_number')->nullable()->comment('Số tài khoản');
            $table->double('balance')->default(0)->comment('Số dư tài khoản');
            $table->string('transaction_password')->nullable()->comment('Mật khẩu giao dịch');
            $table->integer('distribution_today')->nullable()->default(0)->comment('Phân phối hôm nay');
            $table->double('todays_discount')->nullable()->default(0)->comment('Chiếu khấu hôm nay');
            $table->integer('count_withdrawals')->default(0)->comment('Hôm nay rút mấy lần rồi');
            $table->enum('role', allowed: ['member', 'staff', 'admin'])->default('member')
                ->comment('Vai trò của người dùng, ở đây có 3 vai trò: người dùng, nhân viên và admin, admin lớn nhất');
            $table->enum('status', ['inactivated', 'activated', 'banned'])->default('inactivated')->comment('Trạng thái của tài khoản');
            $table->foreignIdFor(User::class, 'referrer_id')->nullable()->comment('Được giới thiệu bởi ai');
            $table->foreignIdFor(Rank::class)->nullable()->comment('Cấp độ người dùng');
            $table->timestamp('email_verified_at')->nullable()->comment('Email đã được xác minh lúc nào');
            $table->string('register_ip', 100)->nullable()->comment('Lưu địa chỉ ip');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
