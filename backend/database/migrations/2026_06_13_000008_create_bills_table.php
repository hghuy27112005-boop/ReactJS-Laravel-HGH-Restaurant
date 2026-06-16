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
            $table->enum('order_type', ['booking', 'delivery']);
            $table->decimal('total_price', 12, 2);
            $table->string('payment_method', 50)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        DB::unprepared('
            CREATE OR REPLACE FUNCTION fn_apply_administrator_free_bill()
            RETURNS TRIGGER AS $$
            DECLARE
                v_membership VARCHAR(20);
            BEGIN
                SELECT membership INTO v_membership FROM users WHERE user_id = NEW.user_id;
                IF v_membership = \'administrator\' THEN
                    NEW.total_price := 0.00;
                    NEW.payment_method := \'ADMIN_TEST_FREE\';
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER trg_on_insert_bill_check_admin
            BEFORE INSERT ON bills
            FOR EACH ROW
            EXECUTE FUNCTION fn_apply_administrator_free_bill();
        ');
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_on_insert_bill_check_admin ON bills');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_apply_administrator_free_bill');
        Schema::dropIfExists('bills');
    }
};
