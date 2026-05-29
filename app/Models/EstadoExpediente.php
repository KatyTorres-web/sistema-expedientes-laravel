<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadoExpediente extends Model
{
    protected $table = 'estado_expedientes';
    protected $primaryKey = 'id_estado_expediente';
    public $timestamps = false;

    protected $fillable = ['nombre_estado'];

    public function expedientes()
    {
        return $this->hasMany(Expediente::class, 'id_estado_expediente');
    }
}
