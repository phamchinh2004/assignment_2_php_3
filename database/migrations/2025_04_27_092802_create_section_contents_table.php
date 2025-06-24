<?php

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
        Schema::create('section_contents', function (Blueprint $table) {
            $table->id();
            $table->string('content')->nullable()->comment('Nội dung section');
            $table->foreignIdFor(Section::class)->comment('Thuộc về section nào');
            $table->tinyInteger(column: 'status')->default(1)->comment('Trạng thái kích hoạt, mặc định là 1 (đã được kích hoạt), 0 là bị khóa');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('section_contents');
    }
};
