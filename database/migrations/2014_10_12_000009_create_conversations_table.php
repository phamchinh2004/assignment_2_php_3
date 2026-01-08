<?php

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
        if (!Schema::hasTable('conversations')) {
            Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();   // người dùng
            $table->foreignId('staff_id')->nullable()->constrained('users'); // nhân viên
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamps();
        });

        }

        // Tạo bảng messages
        if (!Schema::hasTable('messages')) {
            Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained();
            $table->foreignId('sender_id')->constrained('users');
            $table->text('message')->nullable();
            $table->string('image_path')->nullable()->comment('Đường dẫn hình ảnh');
            $table->enum('type', ['text', 'image'])->default('text');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
    }
};
