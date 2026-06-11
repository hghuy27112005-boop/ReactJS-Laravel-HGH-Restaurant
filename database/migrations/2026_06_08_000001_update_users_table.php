<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Thêm cột membership nếu chưa có
            if (!Schema::hasColumn('users', 'membership')) {
                $table->string('membership', 20)->default('Bronze')->comment('Bronze, Silver, Gold, Platinum, Diamond, Administrator');
            }
            // Cập nhật cột role -> authority
            if (!Schema::hasColumn('users', 'authority')) {
                $table->string('authority', 20)->default('User')->comment('Admin/User');
            }
            // Đổi tên phone thành user_tele_number nếu cần
            if (Schema::hasColumn('users', 'phone') && !Schema::hasColumn('users', 'user_tele_number')) {
                $table->renameColumn('phone', 'user_tele_number');
            }
            // Thêm cột điểm tích lũy
            if (!Schema::hasColumn('users', 'points_accumulated')) {
                $table->bigInteger('points_accumulated')->default(0)->comment('Điểm tích lũy');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'membership')) {
                $table->dropColumn('membership');
            }
            if (Schema::hasColumn('users', 'authority')) {
                $table->dropColumn('authority');
            }
            if (Schema::hasColumn('users', 'points_accumulated')) {
                $table->dropColumn('points_accumulated');
            }
        });
    }
};
