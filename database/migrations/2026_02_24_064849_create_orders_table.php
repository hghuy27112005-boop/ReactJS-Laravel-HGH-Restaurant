<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. dish_types
        Schema::create('dish_types', function (Blueprint $table) {
            $table->id('type_id');
            $table->string('type_name', 100);
        });

        // 2. dishes
        Schema::create('dishes', function (Blueprint $table) {
            $table->id('dish_id');
            $table->string('dish_name', 255);
            $table->decimal('price', 10, 2);
            $table->string('image_url', 255)->nullable();
            $table->unsignedBigInteger('type_id')->nullable();
            $table->boolean('is_bestseller')->default(false);
            $table->text('description')->nullable();

            $table->foreign('type_id')->references('type_id')->on('dish_types')->onDelete('set null');
        });

        // 3. bills
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->string('bill_code', 20)->unique()->nullable();
            $table->integer('order_in_day')->nullable();
            $table->string('customer_name', 255)->default('Khách hàng');
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('order_type', 50)->nullable();
            $table->text('address')->nullable();
            $table->string('table_number', 255)->nullable(); // Changed to string as requested
            $table->date('booking_date')->nullable();
            $table->string('arrival_time', 50)->nullable();
            $table->string('finish_time', 50)->nullable();
            $table->string('status', 50)->default('pending');
            $table->boolean('is_paid')->default(false);
            $table->string('payment_method', 100)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // 4. bill_details
        Schema::create('bill_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bill_id');
            $table->unsignedBigInteger('dish_id');
            $table->integer('quantity')->default(1);
            $table->decimal('price_at_time', 10, 2)->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('bill_id')->references('id')->on('bills')->onDelete('cascade');
            $table->foreign('dish_id')->references('dish_id')->on('dishes')->onDelete('cascade');
        });

        // 5. booking_tables
        Schema::create('booking_tables', function (Blueprint $table) {
            $table->id();
            $table->integer('table_number');
            $table->string('customer_name', 255)->nullable();
            $table->string('customer_phone', 15)->nullable();
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->unsignedBigInteger('bill_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('bill_id')->references('id')->on('bills')->onDelete('set null');
        });

        // Add check constraints via raw SQL
        DB::statement('ALTER TABLE booking_tables ADD CONSTRAINT check_table_number CHECK (table_number BETWEEN 1 AND 50)');
        DB::statement('ALTER TABLE booking_tables ADD CONSTRAINT check_times CHECK (end_time > start_time)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_tables');
        Schema::dropIfExists('bill_details');
        Schema::dropIfExists('bills');
        Schema::dropIfExists('dishes');
        Schema::dropIfExists('dish_types');
    }
};