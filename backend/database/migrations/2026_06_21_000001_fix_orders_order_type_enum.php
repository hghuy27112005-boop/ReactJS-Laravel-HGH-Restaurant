<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // PostgreSQL: drop the old check constraint and add new one
        DB::statement("ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_order_type_check");
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_order_type_check CHECK (order_type IN ('booking', 'delivery', 'booking_table'))");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_order_type_check");
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_order_type_check CHECK (order_type IN ('booking', 'delivery'))");
    }
};
