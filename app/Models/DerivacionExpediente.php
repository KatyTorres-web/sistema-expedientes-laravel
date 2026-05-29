<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DerivacionExpediente extends Model
{
    protected $table = 'derivacion_expedientes';
    protected $primaryKey = 'id_derivacion';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'fecha_derivacion',
        'fecha_atencion',
        'id_usuario',
        'id_expediente'
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function expediente()
    {
        return $this->belongsTo(Expediente::class, 'id_expediente');
    }
}
