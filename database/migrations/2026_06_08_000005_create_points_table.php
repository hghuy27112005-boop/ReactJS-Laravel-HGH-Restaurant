<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * 
     * Points Table - Quản lý điểm tích lũy từ các đơn hàng
     * Dùng để tính doanh thu theo khách hàng, xếp hạng, tích lũy điểm
     */
    public function up(): void
    {
        Schema::create('points', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('bill_id')->nullable()->comment('Liên kết đơn hàng booking');
            $table->unsignedBigInteger('delivery_id')->nullable()->comment('Liên kết đơn hàng delivery');
            $table->decimal('booking_total_price', 12, 2)->default(0)->comment('Tổng giá từ booking order');
            $table->decimal('delivery_total_price', 12, 2)->default(0)->comment('Tổng giá từ delivery order');
            $table->bigInteger('points_earned', false, true)->default(0)->comment('Điểm kiếm được từ đơn hàng');
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('bill_id')->references('id')->on('bills')->onDelete('set null');
            $table->foreign('delivery_id')->references('id')->on('deliveries')->onDelete('set null');
            
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('points');
    }
};
