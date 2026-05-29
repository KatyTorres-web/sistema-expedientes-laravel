<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class SubdivisionArea extends Model
{
    protected $table = 'subdivision_areas';
    protected $primaryKey = 'id_subdivision';
    public $timestamps = false;

    protected $fillable = [
        'cod_subdivision',
        'nombre_subdivision',
        'estado_subdiv_area'
    ];

    public function usuarios()
    {
        return $this->hasMany(User::class, 'subdivision_id', 'id_subdivision');
    }

    protected function nombreSubdivision(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => mb_strtoupper($value, 'UTF-8')
        );
    }

    public function scopeActiva($query)
    {
        return $query->where('estado_subdiv_area', 1);
    }
    protected function codSubdivision(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => mb_strtoupper(str_replace(' ', '', $value))
        );
    }
}