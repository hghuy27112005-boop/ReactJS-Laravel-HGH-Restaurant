<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * 
     * Statistics Table - Thống kê khách hàng cho dashboard
     * Ghi lại thông tin thống kê về mỗi user: doanh thu, membership, điểm, tần suất đặt hàng...
     */
    public function up(): void
    {
        Schema::create('statistics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('membership', 20)->comment('Bronze, Silver, Gold, Platinum, Diamond');
            $table->unsignedBigInteger('points_id')->nullable();
            $table->integer('total_orders')->default(0)->comment('Tổng số đơn hàng');
            $table->integer('booking_orders')->default(0)->comment('Số đơn booking');
            $table->integer('delivery_orders')->default(0)->comment('Số đơn delivery');
            $table->decimal('total_spent', 12, 2)->default(0)->comment('Tổng tiền đã chi');
            $table->decimal('total_discount', 12, 2)->default(0)->comment('Tổng tiền giảm giá');
            $table->bigInteger('total_points', false, true)->default(0)->comment('Tổng điểm tích lũy');
            $table->timestamp('last_order_date')->nullable()->comment('Ngày đặt hàng cuối cùng');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('points_id')->references('points_id')->on('points')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statistics');
    }
};
