<?php
namespace App\Exports;

use App\Models\Expediente;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ExpedienteYearSheet implements FromQuery, WithTitle, WithHeadings, WithMapping, ShouldAutoSize
{
    private $anio;
    private $request;

    public function __construct(int $anio, $request)
    {
        $this->anio = $anio;
        $this->request = $request;
    }

    public function query()
    {
        // Reutilizamos la lógica de filtrado del controlador
        $query = Expediente::query()->with(['estado', 'derivaciones.usuario.subdivision', 'usuario'])
            ->where('anio_expediente', $this->anio);

        if ($this->request->filled('buscar')) {
            $buscar = $this->request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->where('apellidos_y_nombres', 'LIKE', "%$buscar%")
                ->orWhere('razon_social', 'LIKE', "%$buscar%");
            });
        }
        if ($this->request->filled('num_exp')) $query->where('num_expediente', 'LIKE', '%' . $this->request->num_exp . '%');
        if ($this->request->filled('id_estado')) $query->where('id_estado_expediente', $this->request->id_estado);
        if ($this->request->filled('id_subdivision')) $query->where('area_actual', $this->request->id_subdivision);
        if ($this->request->filled('fecha_desde')) $query->whereDate('fecha_recepcion', '>=', $this->request->fecha_desde);
        if ($this->request->filled('fecha_hasta')) $query->whereDate('fecha_recepcion', '<=', $this->request->fecha_hasta);

        return $query->orderBy('correlativo_anual', 'asc');
    }

    public function title(): string
    {
        return 'Año ' . $this->anio;
    }

    public function headings(): array
    {
        return ["ID", "FECHA", "N° EXPEDIENTE", "NOMBRES / RAZÓN SOCIAL", "DESCRIPCIÓN", "FOLIOS", "DERIVADO A", "FECHA ESTADO", "ESTADO"];
    }

    public function map($exp): array
    {
        $deriv = $exp->derivaciones->last();
        return [
            str_pad($exp->correlativo_anual, 4, '0', STR_PAD_LEFT),
            \Carbon\Carbon::parse($exp->fecha_recepcion)->format('d/m/Y'),
            $exp->num_expediente,
            $exp->tipo_presentante == 'NATURAL' ? $exp->apellidos_y_nombres : $exp->razon_social,
            $exp->descripcion,
            $exp->folios_actual,
            $deriv ? $deriv->usuario->nombre . ' (' . ($deriv->usuario->subdivision->cod_subdivision ?? '') . ')' : '-',
            \Carbon\Carbon::parse($exp->fecha_registro)->format('d/m/Y'),
            strtoupper($exp->estado->nombre_estado)
        ];
    }
}