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
        Schema::create('lucky_wheel_spins', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->onDelete('cascade');
            $table->string('prize')->comment('Phần thưởng nhận được');
            $table->date('spin_date')->comment('Ngày quay')->index();
            $table->timestamps();
            
            // Đảm bảo mỗi user chỉ quay 1 lần mỗi ngày
            $table->unique(['user_id', 'spin_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lucky_wheel_spins');
    }
};

