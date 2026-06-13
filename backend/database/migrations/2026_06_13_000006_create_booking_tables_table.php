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
            $table->string('order_id', 20)->unique();
            $table->foreign('order_id')->references('order_id')->on('orders')->onDelete('cascade');
            $table->foreignId('table_type_id')->constrained('table_types', 'table_type_id');
            $table->integer('quantity')->default(1);
            $table->date('booking_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('booking_status', ['waiting_info', 'waiting_confirmation', 'waiting_payment', 'completed', 'cancelled'])->default('waiting_info');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_tables');
    }
};
