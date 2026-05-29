<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EstadoUsuario;

class EstadoUsuarioSeeder extends Seeder
{
    public function run(): void
    {
        EstadoUsuario::insert([
            ['estado_usuario' => 'ACTIVO'],
            ['estado_usuario' => 'INACTIVO'],
        ]);
    }
}