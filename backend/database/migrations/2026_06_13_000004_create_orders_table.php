<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->string('order_id', 20)->primary();
            $table->foreignId('user_id')->constrained('users', 'user_id');
            $table->enum('order_type', ['booking', 'delivery']);
            $table->enum('order_status', ['editing', 'confirmed', 'completed', 'cancelled'])->default('editing');
            $table->decimal('subtotal_price', 12, 2)->default(0);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
