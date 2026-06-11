<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * 
     * Sale Off Events Table - Quản lý các sự kiện khuyến mãi/giảm giá
     */
    public function up(): void
    {
        Schema::create('sale_off_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_name', 255)->comment('Tên sự kiện khuyến mãi');
            $table->decimal('sale_off_percentage', 5, 2)->comment('Phần trăm giảm giá (0-100)');
            $table->text('description')->nullable()->comment('Mô tả sự kiện');
            $table->timestamp('sale_off_start_time')->comment('Thời gian bắt đầu');
            $table->timestamp('sale_off_end_time')->comment('Thời gian kết thúc');
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_off_events');
    }
};
