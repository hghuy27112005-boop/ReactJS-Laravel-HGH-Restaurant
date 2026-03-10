<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('booking_tables', function (Blueprint $table) {
            $table->date('booking_date')->nullable()->after('table_number');
            $table->string('arrival_time', 50)->nullable()->after('booking_date');
            $table->string('finish_time', 50)->nullable()->after('arrival_time');
            $table->integer('table_type')->nullable()->after('finish_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_tables', function (Blueprint $table) {
            $table->dropColumn(['booking_date', 'arrival_time', 'finish_time', 'table_type']);
        });
    }
};
