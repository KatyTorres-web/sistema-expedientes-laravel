<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Rol extends Model
{
    protected $table = 'roles';
    protected $primaryKey = 'id_rol';
    public $timestamps = false;

    protected $fillable = ['nombre_rol'];

    public function usuarios()
    {
        return $this->hasMany(User::class, 'rol_id');
    }

    protected function nombreRol(): Attribute {
    return Attribute::make(
        set: fn ($value) => mb_strtoupper($value),
    );
}
}
