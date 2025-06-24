<?php

use App\Models\Order;
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
        Schema::create('frozen_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->comment('Người dùng bị áp dụng giá giả');
            $table->foreignIdFor(Order::class)->comment('Đơn hàng bị đóng băng');
            $table->double('custom_price')->nullable()->comment('Giá giả dùng để đóng băng đơn hàng');
            $table->boolean('is_frozen')->default(true)->comment('Trạng thái đơn hàng này có đang bị đóng băng với user hay không');
            $table->boolean('spun')->default(false)->comment('Đã quay đến đơn hàng này chưa');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('frozen_orders');
    }
};
