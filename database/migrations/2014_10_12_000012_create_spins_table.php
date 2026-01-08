<?php

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('spins')) {
            Schema::create('spins', function (Blueprint $table) {
                $table->id();
                $table->foreignIdFor(User::class)->comment('Người dùng quay');
                $table->foreignIdFor(Order::class)->nullable()->comment('Đơn hàng được quay');
                $table->unsignedBigInteger('frozen_order_id')->nullable()->comment('ID frozen order (nếu có)');
                $table->timestamps();

                // Foreign key cho frozen_order_id (sẽ được thêm sau khi bảng frozen_orders được tạo)
                // Tạm thời không thêm foreign key constraint ở đây vì bảng frozen_orders có thể chưa tồn tại
            });
        }
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spins');
    }
};
