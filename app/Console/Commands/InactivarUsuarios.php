<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Carbon\Carbon;

class InactivarUsuarios extends Command
{
    protected $signature = 'usuarios:inactivar';
    protected $description = 'Inactiva usuarios con más de 30 días sin iniciar sesión';

    public function handle()
    {
        $fechaLimite = Carbon::now()->subDays(30);

        User::where('estado_usuario_id', 1)
            ->where('ultimo_login', '<', $fechaLimite)
            ->update(['estado_usuario_id' => 2]);

        $this->info('Usuarios inactivos actualizados correctamente.');
    }
}