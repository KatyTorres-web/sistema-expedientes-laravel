<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SubdivisionArea;

class SubdivisionAreaSeeder extends Seeder
{
    public function run(): void
    {
        SubdivisionArea::insert([
            [
                'cod_subdivision' => 'RYR',
                'nombre_subdivision' => 'REGISTRO Y RECAUDACION',
                'estado_subdiv_area' => 1
            ],
        ]);
    }
}