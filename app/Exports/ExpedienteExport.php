<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Models\Expediente;

class ExpedienteExport implements WithMultipleSheets
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function sheets(): array
    {
        $sheets = [];

        // Si el usuario filtró por un año específico en la vista
        if ($this->request->filled('anio')) {
            $sheets[] = new ExpedienteYearSheet($this->request->anio, $this->request);
        } else {
            // Si no hay filtro de año, buscamos todos los años registrados para crear hojas
            $anios = Expediente::select('anio_expediente')->distinct()->pluck('anio_expediente');
            foreach ($anios as $anio) {
                $sheets[] = new ExpedienteYearSheet($anio, $this->request);
            }
        }

        return $sheets;
    }
}