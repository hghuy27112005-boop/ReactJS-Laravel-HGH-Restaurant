// database/migrations/2026_06_21_000001_add_vnp_txn_ref_to_bills_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->string('vnp_txn_ref', 50)->nullable()->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropColumn('vnp_txn_ref');
        });
    }
};