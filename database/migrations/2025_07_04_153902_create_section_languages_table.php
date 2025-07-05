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
    }
};
