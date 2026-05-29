<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'apellido',
        'nombre',
        'celular',
        'email',
        'username',
        'password',
        'estado_usuario_id',
        'rol_id',
        'subdivision_id',
        'primer_login',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // Usuario pertenece a un estado
    public function estado()
    {
        return $this->belongsTo(EstadoUsuario::class, 'estado_usuario_id');
    }

    // Usuario pertenece a un rol
    public function rol()
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    // Usuario pertenece a una subdivisión
    public function subdivision()
    {
        return $this->belongsTo(SubdivisionArea::class, 'subdivision_id');
    }

    // Usuario puede registrar muchos expedientes
    public function expedientes()
    {
        return $this->hasMany(Expediente::class, 'id_usuario');
    }

    // Usuario puede tener muchas derivaciones
    public function derivaciones()
    {
        return $this->hasMany(DerivacionExpediente::class, 'id_usuario');
    }

    // Usuario puede hacer muchos cambios en historial
    public function historialCambios()
    {
        return $this->hasMany(HistorialCambio::class, 'id_usuario');
    }

    protected function apellido(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => mb_strtoupper($value)
        );
    }

    protected function nombre(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => mb_strtoupper($value)
        );
    }

    public function scopeActivo($query)
    {
        return $query->where('estado_usuario_id', 1);
    }

    // Atributo para facilitar la redirección por rol
    public function getDashboardRouteNameAttribute()
    {
        return $this->rol_id == 1 ? 'dashboard.admin' : 'dashboard.analista';
    }
}