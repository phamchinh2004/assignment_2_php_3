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
        Schema::create('user_bans', function (Blueprint $table) {
            $table->id();
            $table->string('reason')->comment('Lý do thay đổi trạng thái');
            $table->foreignIdFor(User::class)->comment('Xác định người dùng nào được thay đổi trạng thái')->constrained()->onDelete('cascade');
            $table->boolean('status')->comment('Loại trạng thái, có 2 loại là ban và unban, có nghĩa là để xác định bản ghi này là ban người dùng hay hủy ban người dùng');
            $table->tinyInteger(column: 'is_active')->default(1)->comment('Trạng thái hiệu lực, mặc định là 1 (có hiệu lực, 0 là không có hiệu lực)');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_bans');
    }
};
