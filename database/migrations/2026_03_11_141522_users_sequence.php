<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration 
{
    public function up(): void
    {
        DB::statement("SELECT setval('users_user_id_seq', (SELECT MAX(user_id) FROM users))");
    }

    public function down(): void
    {

    }
};