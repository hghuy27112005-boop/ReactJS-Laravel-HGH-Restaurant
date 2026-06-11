<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * 
     * Orders Table - Bảng trung gian để liên kết dishes với bills
     * Lưu thông tin về các món ăn được đặt trong một đơn hàng
     * Có thể có nhiều orders cho một bill
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code', 20)->unique()->comment('Format: DDMMYY + 4 chữ số');
            $table->unsignedBigInteger('bill_id');
            $table->unsignedBigInteger('dish_id');
            $table->integer('quantity')->default(1)->comment('Số lượng');
            $table->decimal('price_at_order', 10, 2)->nullable()->comment('Giá tại thời điểm đặt hàng');
            $table->string('order_type', 50)->comment('booking_table hoặc delivery');
            $table->text('note')->nullable()->comment('Ghi chú cho món ăn');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('bill_id')->references('id')->on('bills')->onDelete('cascade');
            $table->foreign('dish_id')->references('dish_id')->on('dishes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
