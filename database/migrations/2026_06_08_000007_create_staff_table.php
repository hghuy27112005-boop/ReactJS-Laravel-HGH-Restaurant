<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * 
     * Staff Table - Quản lý nhân viên
     */
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->comment('Tên nhân viên');
            $table->string('email', 100)->nullable()->unique();
            $table->string('staff_tele_number', 15)->nullable()->comment('Số điện thoại');
            $table->text('staff_avt')->nullable()->comment('Avatar URL');
            $table->string('position', 100)->nullable()->comment('Chức vụ');
            $table->string('status', 50)->default('active')->comment('active, inactive, resigned');
            $table->timestamp('hire_date')->useCurrent()->comment('Ngày tuyển dụng');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
