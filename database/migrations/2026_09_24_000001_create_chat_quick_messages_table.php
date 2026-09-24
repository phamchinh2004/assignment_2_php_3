<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('chat_quick_messages')) {
            return;
        }

        Schema::create('chat_quick_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('message_key', 64);
            $table->text('content');
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'message_key'], 'chat_quick_messages_user_key_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_quick_messages');
    }
};
