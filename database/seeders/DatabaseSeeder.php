<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            EstadoUsuarioSeeder::class,
            RolSeeder::class,
            EstadoExpedienteSeeder::class,
            SubdivisionAreaSeeder::class,
            UserSeeder::class,
        ]);

    if (DB::connection()->getDriverName() === 'pgsql') {
            
            // Tabla: expedientes
            DB::statement("SELECT setval(pg_get_serial_sequence('expedientes', 'id_expediente'), COALESCE((SELECT MAX(id_expediente) FROM expedientes), 0) + 1, false)");
            
            // Tabla: historial_cambios
            DB::statement("SELECT setval(pg_get_serial_sequence('historial_cambios', 'id_historial'), COALESCE((SELECT MAX(id_historial) FROM historial_cambios), 0) + 1, false)");
            
            // Tabla: derivacion_expedientes
            DB::statement("SELECT setval(pg_get_serial_sequence('derivacion_expedientes', 'id_derivacion'), COALESCE((SELECT MAX(id_derivacion) FROM derivacion_expedientes), 0) + 1, false)");
            
            // Opcional: Tabla users (si también forzaste IDs en UserSeeder)
            DB::statement("SELECT setval(pg_get_serial_sequence('users', 'id'), COALESCE((SELECT MAX(id) FROM users), 0) + 1, false)");
        }
    }    
    
}