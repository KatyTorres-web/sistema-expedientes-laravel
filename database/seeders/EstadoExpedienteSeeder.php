<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EstadoExpediente;

class EstadoExpedienteSeeder extends Seeder
{
    public function run(): void
    {
        EstadoExpediente::insert([
            ['nombre_estado' => 'REGISTRADO'],
            ['nombre_estado' => 'DERIVADO'],
            ['nombre_estado' => 'ATENDIDO'],
            ['nombre_estado' => 'DEVUELTO'],
        ]);
    }
}