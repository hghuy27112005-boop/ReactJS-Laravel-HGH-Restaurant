<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->string('delivery_id', 20)->primary();
            $table->string('order_id', 20)->unique();
            $table->foreign('order_id')->references('order_id')->on('orders')->onDelete('cascade');
            $table->text('address');
            $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid');
            $table->enum('delivery_status', ['waiting_confirmation', 'waiting_approval', 'shipping', 'completed', 'cancelled'])->default('waiting_confirmation');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
