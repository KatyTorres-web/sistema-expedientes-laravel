<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadoUsuario extends Model
{
    protected $table = 'estado_usuarios';
    protected $primaryKey = 'id_estado_usuario';
    public $timestamps = false;

    protected $fillable = [
        'estado_usuario'
    ];

    // Relación: un estado tiene muchos usuarios
    public function usuarios()
    {
        return $this->hasMany(User::class, 'estado_usuario_id');
    }
}