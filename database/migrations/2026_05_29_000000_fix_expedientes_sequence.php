<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ajusta la secuencia de PostgreSQL para que el siguiente id_expediente sea mayor
        // que el máximo actual de la tabla. Esto previene errores de llave duplicada
        // después de importaciones o cambios manuales en la base de datos.
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('expedientes', 'id_expediente'), COALESCE((SELECT MAX(id_expediente) FROM expedientes), 0), true)");
        }
    }

    public function down(): void
    {
        // No es necesaria reversión para esta corrección de secuencia.
    }
};
