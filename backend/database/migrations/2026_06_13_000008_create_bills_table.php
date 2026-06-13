<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->string('bill_id', 20)->primary();
            $table->string('order_id', 20)->unique();
            $table->foreign('order_id')->references('order_id')->on('orders')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users', 'user_id');
            $table->decimal('total_price', 12, 2);
            $table->string('payment_method', 50)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
