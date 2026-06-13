<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id('stock_id');
            $table->foreignId('dish_id')->unique()->constrained('dishes', 'dish_id');
            $table->integer('quantity_start')->default(200);
            $table->integer('quantity_left')->default(200);
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
