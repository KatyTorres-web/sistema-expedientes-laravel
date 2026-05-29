<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class InternalMLService
{
    protected $baseUrl = 'http://127.0.0.1:8001'; 

    // Criterio 5: Sugerir Categoría automáticamente
    public function sugerirCategoria($descripcion)
    {
        try {
            $response = Http::post("{$this->baseUrl}/predict-category", ['text' => $descripcion]);
            return $response->json()['category'] ?? 'GENERAL';
        } catch (\Exception $e) {
            return 'GENERAL';
        }
    }

    public function edit($id)
    {
        $expediente = Expediente::with(['estado', 'usuario', 'derivaciones.usuario', 'historialCambios'])->findOrFail($id);

        if ($this->esExpedienteCongeladoParaAnalista($expediente)) {
            return redirect()->route('expedientes.index')->with('error_modal', 'EXPEDIENTE CONGELADO.');
        }

        $mlService = new InternalMLService();
        $recomendacion = $mlService->recomendarAnalista($expediente->descripcion);

        $recomendadoId = $recomendacion['id_analista'] ?? null;
        $motivoIA = $recomendacion['motivo'] ?? 'Sugerido por disponibilidad.';

        $analistas = User::where('rol_id', 2)
            ->where('estado_usuario_id', 1)
            ->get()
            ->map(function($u) use ($recomendadoId, $motivoIA) {
                $u->motivo_ia = ($u->id == $recomendadoId) ? $motivoIA : null;
                return $u;
            });

        return view('expedientes.form_expediente', compact('expediente', 'analistas', 'recomendadoId'));
    }

    // Criterio 4: Recomendación Inteligente de Analista
    public function recomendarAnalista($categoriaExpediente)
    {
        // 1. Obtener analistas activos con su carga actual (Menor carga primero)
        $analistas = DB::table('users as u')
            ->leftJoin('derivacion_expedientes as d', function($join) {
                $join->on('u.id', '=', 'd.id_usuario')
                        ->whereNull('d.fecha_atencion');
            })
            ->where('u.rol_id', 2)
            ->where('u.estado_usuario_id', 1)
            ->select('u.id', 'u.nombre', DB::raw('COUNT(d.id_derivacion) as carga'))
            ->groupBy('u.id', 'u.nombre')
            ->orderBy('carga', 'asc')
            ->get();

        foreach ($analistas as $analista) {
            // 2. Verificar Experiencia: 50+ Atendidos en esa categoría
            $experiencia = DB::table('derivacion_expedientes as d')
                ->join('expedientes as e', 'd.id_expediente', '=', 'e.id_expediente')
                ->where('d.id_usuario', $analista->id)
                ->where('e.id_estado_expediente', 3) // ATENDIDO
                ->where('e.categoria_final', $categoriaExpediente)
                ->count();

            if ($experiencia >= 10) { // Se bajó a 10 para pruebas, idealmente sería 50
                return [
                    'id' => $analista->id,
                    'nombre' => $analista->nombre,
                    'motivo' => "Recomendado por experiencia en $categoriaExpediente (" . $experiencia . " casos) y baja carga actual (" . $analista->carga . ")."
                ];
            }
        }

        return null; // Si nadie cumple los 50, el controlador usará el de menos carga
    }

    // Criterio Reclazo: Categorizar motivo
    public function categorizarRechazo($observacion)
    {
        $response = Http::post("{$this->baseUrl}/predict-rejection", ['text' => $observacion]);
        return $response->json()['reason'] ?? 'OTROS';
    }
}