<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->string('order_id', 20)->primary();
            $table->foreignId('user_id')->constrained('users', 'user_id');
            $table->enum('order_type', ['booking', 'delivery', 'booking_table']);
            $table->decimal('subtotal_price', 12, 2)->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });

        // PostgreSQL: đảm bảo constraint đúng
        DB::statement("ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_order_type_check");
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_order_type_check CHECK (order_type IN ('booking', 'delivery', 'booking_table'))");
    }

    public function down(): void
    {
        // Xóa constraint trước
        DB::statement("ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_order_type_check");

        Schema::dropIfExists('orders');
    }
};
