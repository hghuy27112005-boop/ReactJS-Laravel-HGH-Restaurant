<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration 
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            $col = DB::selectOne("
                select data_type
                from information_schema.columns
                where table_name = 'bills' and column_name = 'status'
                limit 1
            ");

            if ($col && ($col->data_type ?? null) === 'boolean') {
                DB::statement("
                    alter table bills
                    alter column status type varchar(50)
                    using (case when status is true then 'completed' else 'pending' end)
                ");
                DB::statement("alter table bills alter column status set default 'pending'");
            }

            return;
        }

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $col = DB::selectOne("
                select data_type
                from information_schema.columns
                where table_schema = database()
                  and table_name = 'bills'
                  and column_name = 'status'
                limit 1
            ");

            $dt = strtolower((string)($col->data_type ?? ''));
            if (in_array($dt, ['tinyint', 'bit', 'boolean'], true)) {
                DB::statement("alter table bills modify status varchar(50) not null default 'pending'");
                DB::statement("update bills set status = if(status = 1, 'completed', 'pending')");
            }

            return;
        }
    }

    public function down(): void
    {
    // Không tự động rollback về boolean vì sẽ mất thông tin status string.
    }
};
