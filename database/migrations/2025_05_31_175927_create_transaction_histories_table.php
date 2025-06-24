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
        Schema::create('transaction_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->comment('Xác định lịch sử này của người dùng nào!');
            $table->double('value')->comment('Bao nhiêu tiền');
            $table->enum('type', ['profit', 'order'])->comment('Loại biến động nào!');
            $table->string('note')->nullable()->comment('Ghi chú');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_histories');
    }
};
