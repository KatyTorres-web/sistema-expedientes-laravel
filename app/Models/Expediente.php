<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Expediente extends Model
{
    use HasFactory;
    protected $table = 'expedientes';
    protected $primaryKey = 'id_expediente';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'id_estado_expediente',
        'correlativo_anual',
        'num_expediente',
        'anio_expediente',
        'fecha_registro',
        'descripcion',
        'folios_actual',
        'observaciones',
        'razon_social',
        'fecha_recepcion',
        'tipo_presentante',
        'apellidos_y_nombres',
        'area_actual',
        'documentos_adjuntos',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function estado()
    {
        return $this->belongsTo(EstadoExpediente::class, 'id_estado_expediente');
    }

    public function derivaciones()
    {
        return $this->hasMany(DerivacionExpediente::class, 'id_expediente');
    }

    public function historialCambios()
    {
        return $this->hasMany(HistorialCambio::class, 'id_expediente');
    }

    protected function descripcion(): Attribute {
        return Attribute::make( set: fn ($value) => mb_strtoupper($value));
    }

    protected function numExpediente(): Attribute {
        return Attribute::make(set: fn ($value) => mb_strtoupper(str_replace(' ', '', $value)),
        );
    }

    protected function apellidosYNombres(): Attribute {
        return Attribute::make(
            set: fn ($value) => mb_strtoupper($value),
        );
    }

    protected function razonSocial(): Attribute {
        return Attribute::make(
            set: fn ($value) => mb_strtoupper($value),
        );
    }

    protected function observaciones(): Attribute {
        return Attribute::make(
            set: fn ($value) => mb_strtoupper($value),
        );
    }
    
    public static function proximoIdPorFecha($fecha)
    {
        $anio = date('Y', strtotime($fecha));

        // Cambiar max() por esta lógica más segura
        $ultimo = self::where('anio_expediente', $anio)
                    ->orderBy('correlativo_anual', 'desc')
                    ->value('correlativo_anual'); // value obtiene solo esa columna del primer registro
        
        $siguiente = ($ultimo) ? $ultimo + 1 : 1;

        return str_pad($siguiente, 4, '0', STR_PAD_LEFT) . '-' . $anio;
    }

    public function areaActual()
    {
        return $this->belongsTo(SubdivisionArea::class, 'area_actual', 'id_subdivision');
    }

    public function ultimaDerivacion()
    {
        return $this->hasOne(DerivacionExpediente::class, 'id_expediente')
            ->latestOfMany('id_derivacion');
    }

}
