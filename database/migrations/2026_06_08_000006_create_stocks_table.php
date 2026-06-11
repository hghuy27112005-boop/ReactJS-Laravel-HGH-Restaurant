<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * 
     * Stock Table - Quản lý kho hàng
     * Chứa số lượng món nhiều hơn số món trong menu để thay thế
     * - Khi quantity_left <= 15: cần cập nhật lại trong kho
     * - Khi quantity_left >= 90 qua ngày: nên thay món khác
     * - Dùng để tính doanh thu theo món ăn (start - left) * price
     */
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dish_id');
            $table->integer('quantity_start')->default(100)->comment('Số lượng khoảng đầu tiên');
            $table->integer('quantity_left')->default(100)->comment('Số lượng còn lại');
            $table->date('stock_date')->useCurrent()->comment('Ngày nhập kho');
            $table->string('status', 50)->default('active')->comment('active, low_stock, expired, replaced');
            $table->text('note')->nullable();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('dish_id')->references('dish_id')->on('dishes')->onDelete('cascade');
            
            // Index để tìm kiếm nhanh các món hết hàng
            $table->index('status');
            $table->index('quantity_left');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
