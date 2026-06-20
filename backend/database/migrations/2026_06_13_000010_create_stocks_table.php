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
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
        DB::unprepared('
            CREATE OR REPLACE FUNCTION fn_refill_stock_automatically()
            RETURNS TRIGGER AS $$
            BEGIN
                IF NEW.quantity_left <= 15 THEN
                    NEW.quantity_start := NEW.quantity_start + 200;
                    NEW.quantity_left := NEW.quantity_left + 200;
                    NEW.updated_at := CURRENT_TIMESTAMP;
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER trg_on_update_refill_stock
            BEFORE UPDATE ON stocks
            FOR EACH ROW
            EXECUTE FUNCTION fn_refill_stock_automatically();
        ');
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_on_update_refill_stock ON stocks');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_refill_stock_automatically');
        Schema::dropIfExists('stocks');
    }
};
