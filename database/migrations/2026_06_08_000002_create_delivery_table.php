<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * 
     * Delivery Order Table
     * Trạng thái: Chưa điền thông tin -> Chưa xác nhận đơn -> Chưa thanh toán -> Delivery_Payment_method 
     *            -> Chờ duyệt đơn hàng -> Đang giao hàng -> Đã giao hàng
     */
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_code', 20)->unique()->comment('Format: DDMMYY + 4 chữ số STT');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('bill_id')->nullable();
            $table->string('order_type', 50)->default('delivery')->comment('delivery type');
            $table->text('address')->comment('Địa chỉ giao hàng');
            $table->string('status', 50)->default('pending')->comment('Chưa điền thông tin, Chưa xác nhận đơn, Chưa thanh toán, Chờ duyệt đơn, Đang giao hàng, Đã giao hàng');
            $table->string('payment_method', 100)->nullable()->comment('Payment method after confirmation');
            $table->decimal('total_price', 12, 2)->default(0)->comment('Giá chưa tính phí ship');
            $table->decimal('shipping_fee', 10, 2)->default(0)->comment('Phí giao hàng');
            $table->decimal('final_price', 12, 2)->default(0)->comment('Tổng giá bao gồm phí ship');
            $table->boolean('is_paid')->default(false);
            $table->timestamp('approved_at')->nullable()->comment('Thời điểm admin duyệt đơn');
            $table->timestamp('delivery_started_at')->nullable()->comment('Thời điểm bắt đầu giao hàng');
            $table->timestamp('delivered_at')->nullable()->comment('Thời điểm giao hàng xong');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('bill_id')->references('id')->on('bills')->onDelete('set null');
            
            $table->index('delivery_code');
            $table->index('status');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
