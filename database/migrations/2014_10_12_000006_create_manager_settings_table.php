<?php

use App\Models\Manager_setting;
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
        Schema::create('manager_settings', function (Blueprint $table) {
            $table->id();
            $table->string('manager_name')->comment('Tên chức năng quản lý');
            $table->string('manager_code')->comment('Mã chức năng quản lý');
            $table->foreignId('parent_manager_setting_id')->nullable()->comment('Xác định chức năng quản trị cha');
            $table->timestamps();
        });

        // Tạo bảng user_manager_settings
        Schema::create('user_manager_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->comment('Xác định người dùng nào được quyền quản lý chức năng này');
            $table->foreignIdFor(Manager_setting::class)->comment('Xác định chức năng nào');
            $table->tinyInteger(column: 'is_active')->default(1)->comment('Trạng thái kích hoạt, mặc định là 1 (đã được kích hoạt)');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_manager_settings');
        Schema::dropIfExists('manager_settings');
    }
};
