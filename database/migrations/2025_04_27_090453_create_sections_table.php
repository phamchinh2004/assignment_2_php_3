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
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->comment('Tên section!');
            $table->string('code')->nullable()->comment('Mã section!');
            $table->string('content')->nullable()->comment('Nội dung section');
            $table->boolean(column: 'status')->default(1)->comment('Trạng thái kích hoạt, mặc định là 1 (đã được kích hoạt), 0 là bị khóa');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
