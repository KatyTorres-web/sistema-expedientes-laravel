<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('derivacion_expedientes', 'id_derivacion'), COALESCE((SELECT MAX(id_derivacion) FROM derivacion_expedientes), 0), true)");
        }
    }

    public function down(): void
    {
        // No reversible.
    }
};
