<?php
// app/Http/Controllers/DashboardController.php (versión PostgreSQL)

namespace App\Http\Controllers;

use App\Models\Expediente;
use App\Models\EstadoExpediente;
use App\Models\User;
use App\Models\HistorialCambio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\Models\DerivacionExpediente;

class DashboardController extends Controller
{
    public function admin(Request $request)
    {
        $anioActual = Carbon::now()->year;
        $mesActual = Carbon::now()->month;
        $anioFiltro = $request->get('anio', $anioActual);
        $umbralSaturacion = 60; 

        $anios = Expediente::select('anio_expediente')->distinct()->orderBy('anio_expediente', 'desc')->pluck('anio_expediente');
        if ($anios->isEmpty()) { $anios = collect([$anioActual]); }

        // 1. KPIs Generales
        $stats = Expediente::where('anio_expediente', $anioFiltro)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN id_estado_expediente = 1 THEN 1 ELSE 0 END) as registrados,
                SUM(CASE WHEN id_estado_expediente = 2 THEN 1 ELSE 0 END) as derivados,
                SUM(CASE WHEN id_estado_expediente = 3 THEN 1 ELSE 0 END) as atendidos,
                SUM(CASE WHEN id_estado_expediente = 4 THEN 1 ELSE 0 END) as devueltos
            ")->first();

        // 2. Tiempo Promedio - MIGRADO A POSTGRESQL
        $tiempoPromedio = DB::table('expedientes as e')
            ->join('derivacion_expedientes as d', 'e.id_expediente', '=', 'd.id_expediente')
            ->where('e.anio_expediente', $anioFiltro)
            ->where('e.id_estado_expediente', 3)
            ->whereNotNull('d.fecha_atencion')
            ->selectRaw("AVG(EXTRACT(DAY FROM (d.fecha_atencion - e.fecha_registro))) as promedio")
            ->value('promedio') ?? 0;

        // 3. Gráfico Mensual
        $mesesData = Expediente::where('anio_expediente', $anioFiltro)
            ->selectRaw("EXTRACT(MONTH FROM fecha_registro) as mes, COUNT(*) as cantidad")
            ->groupBy(DB::raw("EXTRACT(MONTH FROM fecha_registro)"))
            ->pluck('cantidad', 'mes')->toArray();
        
        $volumenMensual = [];
        for ($i = 1; $i <= 12; $i++) { $volumenMensual[] = $mesesData[$i] ?? 0; }

        // 4. ANÁLISIS DE DEMANDA CON IA
        $volumenMesActual = Expediente::whereYear('fecha_registro', $anioActual)->whereMonth('fecha_registro', $mesActual)->count();
        
        // En PostgreSQL, usamos COUNT(DISTINCT) diferente
        $mesesTotalesHistoria = Expediente::selectRaw("COUNT(DISTINCT TO_CHAR(fecha_registro, 'YYYY-MM')) as total")
            ->value('total');

        $alertaDemanda = false;
        $porcentajeSubida = 0;
        $promedioHistorico = 0;

        try {
            $mlServiceUrl = env('ML_SERVICE_URL', 'https://ia-expedientes-python.onrender.com');
            $resDemand = Http::timeout(5)->post("{$mlServiceUrl}/ml/check-demand", [
                'mes_actual_volumen' => (int)$volumenMesActual,
                'meses_totales_historia' => (int)$mesesTotalesHistoria
            ]);
            
            if ($resDemand->successful()) {
                $iaData = $resDemand->json();
                $alertaDemanda = $iaData['alerta_demanda'];
                $porcentajeSubida = round($iaData['porcentaje_variacion'] * 100);
                $promedioHistorico = $iaData['promedio_historico'];
            }
        } catch (\Exception $e) {
            $promedioHistorico = Expediente::count() / max($mesesTotalesHistoria, 1);
            $alertaDemanda = $volumenMesActual > ($promedioHistorico * 1.15);
        }

        // 5. MONITOR DE ANALISTAS
        $mapaEspecialidades = [
            'INSCRIPCION Y REGISTRO'    => 'INSCRIP',
            'CONSTANCIAS' => 'CONSTANCIA',
            'TRIBUTOS Y DESCARGOS'      => 'DESCARGO',
        ];

        $analistas = User::where('rol_id', 2)
            ->where('estado_usuario_id', 1)
            ->with(['subdivision'])
            ->withCount(['derivaciones as pendientes_count' => function($q) {
                $q->whereNull('fecha_atencion')->whereHas('expediente', fn($query) => $query->where('id_estado_expediente', 2));
            }])->get()
            ->map(function($user) use ($umbralSaturacion, $mapaEspecialidades) {
                $user->saturado = $user->pendientes_count >= $umbralSaturacion;
                
                $user->especialidad = 'GENERAL';
                $maxCount = 0;

                foreach ($mapaEspecialidades as $labelIA => $keyword) {
                    $cnt = DB::table('derivacion_expedientes as d')
                        ->join('expedientes as e', 'd.id_expediente', '=', 'e.id_expediente')
                        ->where('d.id_usuario', $user->id)
                        ->whereNotNull('d.fecha_atencion')
                        ->where('e.descripcion', 'ILIKE', '%' . $keyword . '%') // ILIKE en PostgreSQL para case-insensitive
                        ->count();
                    
                    if ($cnt > $maxCount) {
                        $maxCount = $cnt;
                        $user->especialidad = $labelIA;
                    }
                }
                return $user;
            });

        // 6. Lógica de Apoyo
        foreach ($analistas as $a) {
            if ($a->saturado) {
                $a->mejor_apoyo = $analistas->where('id', '!=', $a->id)->where('saturado', false)->sortBy('pendientes_count')->first();
            }
        }

        // 7. Críticos sin derivar - MIGRADO A POSTGRESQL
        $sinDerivarCriticos = Expediente::where('id_estado_expediente', 1)
            ->whereRaw("EXTRACT(HOUR FROM (NOW() - fecha_registro)) > 24")
            ->count();

        // 8. Temas Frecuentes
        $tiposFrecuentes = collect([
            (object)['nombre' => 'INSCRIPCIÓN', 'cantidad' => Expediente::where('descripcion', 'ILIKE', '%INSCRIP%')->count()],
            (object)['nombre' => 'CONSTANCIAS', 'cantidad' => Expediente::where('descripcion', 'ILIKE', '%CONSTANCIA%')->count()],
            (object)['nombre' => 'DESCARGOS', 'cantidad' => Expediente::where('descripcion', 'ILIKE', '%DESCARGO%')->count()],
            (object)['nombre' => 'MULTAS', 'cantidad' => Expediente::where('descripcion', 'ILIKE', '%MULTA%')->count()],
        ])->sortByDesc('cantidad');

        return view('dashboard.administrador', compact(
            'stats', 'tiempoPromedio', 'analistas', 'alertaDemanda', 
            'volumenMesActual', 'anios', 'anioFiltro', 'volumenMensual', 
            'tiposFrecuentes', 'sinDerivarCriticos', 'porcentajeSubida', 'promedioHistorico'
        ));
    }

    public function analista()
    {
        $userId = Auth::id();
        $anioActual = Carbon::now()->year;
        $hoy = Carbon::today();

        $derivados = Expediente::whereHas('derivaciones', function ($q) use ($userId) {
            $q->where('id_usuario', $userId);
        })->where('anio_expediente', $anioActual)->count();

        $pendientes = DerivacionExpediente::where('id_usuario', $userId)
            ->whereNull('fecha_atencion')
            ->whereHas('expediente', function($q) {
                $q->where('id_estado_expediente', 2);
            })->count();

        $atendidos = HistorialCambio::where('accion', 'ATENCIÓN')->where('id_usuario', $userId)->count();
        $devueltos = HistorialCambio::where('accion', 'DEVOLUCIÓN')->where('id_usuario', $userId)->count();

        $atendidosHoy = HistorialCambio::where('accion', 'ATENCIÓN')
            ->where('id_usuario', $userId)
            ->whereDate('fecha_cambio', $hoy)
            ->count();

        $diasTrabajados = HistorialCambio::where('accion', 'ATENCIÓN')
            ->where('id_usuario', $userId)
            ->selectRaw("COUNT(DISTINCT CAST(fecha_cambio AS DATE)) as total")
            ->value('total') ?: 1;
        
        $promedioDiario = round($atendidos / $diasTrabajados, 1);

        $ultimosDerivados = Expediente::with(['estado', 'historialCambios'])
            ->join('derivacion_expedientes as d', 'expedientes.id_expediente', '=', 'd.id_expediente')
            ->where('d.id_usuario', $userId)
            ->select('expedientes.*', 'd.fecha_derivacion as mi_fecha_recepcion') 
            ->orderBy('d.fecha_derivacion', 'desc')
            ->take(3)
            ->get();

        // BANDEJA PRIORITARIA - MIGRADO A POSTGRESQL
        $recientes = Expediente::with(['estado'])
            ->join('derivacion_expedientes as d', 'expedientes.id_expediente', '=', 'd.id_expediente')
            ->where('expedientes.id_estado_expediente', 2)
            ->where('d.id_usuario', $userId)
            ->whereNull('d.fecha_atencion')
            ->select(
                'expedientes.*',
                'd.fecha_derivacion',
                DB::raw("EXTRACT(DAY FROM (NOW() - d.fecha_derivacion)) as dias_sin_atencion")
            )
            ->orderByRaw("CASE WHEN EXTRACT(DAY FROM (NOW() - d.fecha_derivacion)) > 25 THEN 0 ELSE 1 END")
            ->orderBy('d.fecha_derivacion', 'asc')
            ->take(5)
            ->get()
            ->map(function($exp) {
                $keywords = ['LEGAL', 'TRIBUTARIO', 'DEMANDA', 'MULTA', 'AUDITORIA', 'FISCALIZACION', 'JUDICIAL'];
                $exp->es_complejo = false;
                foreach($keywords as $word) {
                    if (str_contains(strtoupper($exp->descripcion), $word)) {
                        $exp->es_complejo = true;
                        break;
                    }
                }
                return $exp;
            });

        return view('dashboard.analista', compact(
            'derivados', 'pendientes', 'atendidos', 'devueltos', 
            'recientes', 'ultimosDerivados', 'atendidosHoy', 'promedioDiario'
        ));
    }
}