<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('historial_cambios', 'id_historial'), COALESCE((SELECT MAX(id_historial) FROM historial_cambios), 0), true)");
        }
    }

    public function down(): void
    {
        // No reversible.
    }
};
