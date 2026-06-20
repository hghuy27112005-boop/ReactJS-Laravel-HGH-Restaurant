<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_tables', function (Blueprint $table) {

            $table->string('booking_id', 20)->primary();

            $table->string('order_id', 20);

            $table->foreign('order_id')
                ->references('order_id')
                ->on('orders')
                ->onDelete('cascade');

            $table->integer('table_number');

            $table->foreign('table_number')
                ->references('table_number')
                ->on('restaurant_tables');

            $table->date('booking_date');

            $table->time('start_time');

            $table->time('end_time');

            $table->enum('B_payment_status', [
                'unpaid',
                'paid'
            ])->default('unpaid');

            $table->enum('booking_status', [
                'waiting_info',
                'waiting_confirmation',
                'waiting_payment',
                'completed',
                'cancelled'
            ])->default('waiting_info');

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->index('table_number');
            $table->index('booking_date');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_tables');
    }
};