<?php

use App\Models\Language;
use App\Models\Section;
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
            $table->text('content')->nullable()->comment('Nội dung section');
            $table->boolean(column: 'status')->default(1)->comment('Trạng thái kích hoạt, mặc định là 1 (đã được kích hoạt), 0 là bị khóa');
            $table->timestamps();
        });

        // Tạo bảng languages
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Tên đầy đủ của ngôn ngữ');
            $table->string('code')->unique()->comment('Mã ngôn ngữ');
            $table->string('image')->nullable()->comment('Hình ảnh');
            $table->timestamps();
        });

        // Tạo bảng section_languages
        Schema::create('section_languages', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Section::class)->comment('Xác định là sections nào!');
            $table->foreignIdFor(Language::class)->comment('Xác định ngôn ngữ nào!');
            $table->text('content')->comment('Nội dung của ngôn ngữ này!');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('section_languages');
        Schema::dropIfExists('languages');
        Schema::dropIfExists('sections');
    }
};
