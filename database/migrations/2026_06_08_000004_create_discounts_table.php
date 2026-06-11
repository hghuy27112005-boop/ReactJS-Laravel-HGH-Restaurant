<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * 
     * Discounts Table - Quản lý giảm giá theo membership/user
     * Dựa trên membership level để tính discount
     */
    public function up(): void
    {
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->comment('NULL nếu là discount theo membership');
            $table->string('membership', 20)->nullable()->comment('Bronze, Silver, Gold, Platinum, Diamond');
            $table->decimal('discount_percentage', 5, 2)->default(0)->comment('Phần trăm giảm giá');
            $table->timestamp('discount_start_time')->comment('Thời gian bắt đầu');
            $table->timestamp('discount_end_time')->comment('Thời gian kết thúc');
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
