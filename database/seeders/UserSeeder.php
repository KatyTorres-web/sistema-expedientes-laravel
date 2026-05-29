<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // User::create([
        //     'apellido' => 'Admin',
        //     'nombre' => 'Sistema',
        //     'celular' => null,
        //     'email' => 'kmtorresa23@gmail.com',
        //     'username' => 'admin',
        //     'password' => Hash::make('123456'),
        //     'estado_usuario_id' => 1, // Activo
        //     'rol_id' => 1, // Administrador
        //     'subdivision_id' => 1, // Registro y Recaudacion
        // ]);
    }
}