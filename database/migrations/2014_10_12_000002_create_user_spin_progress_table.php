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
        Schema::create('user_spin_progresses', function (Blueprint $table) {
            $table->id();
            $table->integer('current_spin')->default(0)->comment('Đã quay đến lượt thứ mấy của cấp này');
            $table->foreignIdFor(User::class)->comment('Xác định số lượt quay này của người dùng nào');
            $table->foreignIdFor(Rank::class)->comment('Xác định người dùng này đang ở rank nào');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_spin_progress');
    }
};
