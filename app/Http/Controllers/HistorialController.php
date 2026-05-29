<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HistorialCambio;
use App\Models\Expediente;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class HistorialController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'general');
        $limiteTiempo = now()->subHours(48);

        // 1. INICIALIZAR $candidatos (Esto evita el error de variable indefinida)
        $candidatos = HistorialCambio::where('accion', 'DEVOLUCIÓN')
            ->where('fecha_cambio', '>=', $limiteTiempo)
            ->whereHas('expediente', function($q) {
                // Solo expedientes que NO estén Atendidos (id 3)
                $q->where('id_estado_expediente', '!=', 3);
            })
            ->select('id_expediente', DB::raw('COUNT(*) as total_devoluciones'))
            ->groupBy('id_expediente')
            ->get();

        // 2. FILTRAR ALERTAS CON IA
        $alertasCriticas = $candidatos->map(function($item) {
            $primeraDev = HistorialCambio::where('id_expediente', $item->id_expediente)
                ->where('accion', 'DEVOLUCIÓN')
                ->min('fecha_cambio');
            
            $ventanaHoras = $primeraDev ? now()->diffInHours(Carbon::parse($primeraDev)) : 1;

            try {
                $response = Http::post("https://ia-expedientes-python.onrender.com/ml/check-anomaly", [
                    'total_rebotes' => (int)$item->total_devoluciones,
                    'ventana_horas' => (int)$ventanaHoras
                ]);

                if ($response->successful() && $response->json()['es_anomalia']) {
                    $item->expediente = Expediente::find($item->id_expediente);
                    $item->mensaje_ia = $response->json()['mensaje'];
                    return $item;
                }
            } catch (\Exception $e) {
                // Fallback manual si la IA falla
                if ($item->total_devoluciones >= 3) {
                    $item->expediente = Expediente::find($item->id_expediente);
                    $item->mensaje_ia = "Alerta de Auditoría (Lógica de Respaldo)";
                    return $item;
                }
            }
            return null;
        })->filter();

        // 3. CONSULTA PRINCIPAL DE LA TABLA
        $query = HistorialCambio::select([
            'id_historial', 'accion', 'valor_anterior', 'valor_nuevo', 
            'fecha_cambio', 'campo_modificado', 'id_usuario', 'id_expediente'
        ]);

        $query->with([
            'usuario' => function($q) { $q->select('id', 'nombre', 'apellido', 'rol_id', 'subdivision_id'); },
            'usuario.rol:id_rol,nombre_rol',
            'usuario.subdivision:id_subdivision,cod_subdivision',
            'expediente' => function($q) { $q->select('id_expediente', 'num_expediente', 'correlativo_anual', 'anio_expediente'); },
            'expediente.derivaciones' => function($q) {
                $q->select('id_derivacion', 'id_expediente', 'id_usuario', 'fecha_derivacion')
                ->with(['usuario:id,nombre,apellido,subdivision_id', 'usuario.subdivision:id_subdivision,cod_subdivision']);
            }
        ]);

        // Filtros de Pestaña
        $accionesDisponibles = ['CREACIÓN', 'MODIFICACIÓN', 'DERIVACIÓN', 'DEVOLUCIÓN', 'ATENCIÓN'];
        if ($tab == 'derivaciones') {
            $accionesVisibles = ['DERIVACIÓN', 'DEVOLUCIÓN', 'ATENCIÓN'];
            $query->whereIn('accion', $accionesVisibles);
        }

        // Filtros de Búsqueda
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->whereHas('expediente', fn($sq) => $sq->where('num_expediente', 'LIKE', "%$buscar%"))
                  ->orWhereHas('usuario', fn($sq) => $sq->where('nombre', 'LIKE', "%$buscar%")->orWhere('apellido', 'LIKE', "%$buscar%"));
            });
        }

        if ($request->filled('accion')) $query->where('accion', $request->accion);
        if ($request->filled('fecha_desde')) $query->whereDate('fecha_cambio', '>=', $request->fecha_desde);
        if ($request->filled('fecha_hasta')) $query->whereDate('fecha_cambio', '<=', $request->fecha_hasta);

        $datos = $query->orderBy('fecha_cambio', 'desc')->paginate(5)->withQueryString();

        return view('historial.historial', [
            'datos' => $datos,
            'tab' => $tab,
            'acciones' => $accionesDisponibles,
            'alertasCriticas' => $alertasCriticas
        ]);
    }
}