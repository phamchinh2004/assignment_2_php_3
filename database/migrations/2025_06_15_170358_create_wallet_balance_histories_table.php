<?php

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
        Schema::create('wallet_balance_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->comment('Xác định giao dịch này của người dùng nào!');
            $table->double('value')->comment('Giá trị số tiền là bao nhiêu');
            $table->double('initial_balance')->comment('Số dư ban đầu');
            $table->enum('type', ['deposit', 'withdraw'])->comment('Rút tiền hay nạp tiền');
            $table->enum('status', ['processing', 'completed', 'cancelled'])->default('processing')->comment('Trạng thái của giao dịch');
            $table->foreignIdFor(User::class, 'by_user_id')->nullable()->comment('Nhân viên nào đã xác nhận hoặc hủy');
            $table->string('username_bank')->nullable()->comment('Tên chủ tài khoản');
            $table->string('bank_name')->nullable()->comment('Tên ngân hàng');
            $table->string('account_number')->nullable()->comment('Số tài khoản');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_balance_histories');
    }
};
