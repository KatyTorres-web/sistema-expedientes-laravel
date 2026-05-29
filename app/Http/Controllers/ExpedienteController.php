<?php

namespace App\Http\Controllers;

use App\Models\Expediente;
use App\Models\EstadoExpediente;
use App\Models\SubdivisionArea;
use App\Models\User; 
use App\Models\HistorialCambio;
use App\Services\InternalMLService;
use App\Models\DerivacionExpediente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Exports\ExpedienteExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;


class ExpedienteController extends Controller
{
    public function index(Request $request)
    {
        if ($request->filled(['fecha_desde', 'fecha_hasta'])) {
        if (Carbon::parse($request->fecha_desde)->gt(Carbon::parse($request->fecha_hasta))) {
            return back()
                ->with('error_modal', 'LA FECHA "DESDE" NO PUEDE SER MAYOR A LA FECHA "HASTA".')
                ->withInput();
        }
    }
        
        // Años para el filtro
        $anios = Expediente::select('anio_expediente')->distinct()->orderBy('anio_expediente', 'desc')->pluck('anio_expediente');
        
        // Año por defecto
        $anioFiltro = $request->get(
            'anio',
            Expediente::max('anio_expediente') ?? date('Y')
        );

        if($anios->isEmpty()) { $anios = collect([date('Y')]); }

        $estados = EstadoExpediente::all();
        $subdivisiones = SubdivisionArea::where('estado_subdiv_area', 1)->get(); 

        $query = Expediente::with(['estado', 'derivaciones.usuario.subdivision', 'historialCambios']);
            
        if (auth()->user()->rol_id == 2) {
            $userId = auth()->id();

            $query->where(function ($q) use ($userId) {
                // 1. Está DERIVADO al analista y es el dueño actual de la tarea
                $q->where('id_estado_expediente', 2)
                    ->whereHas('ultimaDerivacion', function ($q) use ($userId) {
                        $q->where('id_usuario', $userId)
                        ->whereNull('fecha_atencion');
                    });

                // 2. O si lo ATENDIÓ y el Admin aún no ha intervenido
                $q->orWhere(function ($sub) use ($userId) {
                    $sub->where('id_estado_expediente', 3)
                        ->whereHas('historialCambios', function ($h) use ($userId) {
                            $h->where('accion', 'ATENCIÓN')
                            ->where('id_usuario', $userId)
                            // IMPORTANTE: Aseguramos que la última acción del expediente sea la del analista
                            ->whereRaw('id_historial = (SELECT MAX(h2.id_historial) FROM historial_cambios h2 WHERE h2.id_expediente = historial_cambios.id_expediente)');
                        });
                });

                // 3. O si lo DEVOLVIÓ (RECHAZÓ) para dejar huella aunque luego se reasigne o procese de nuevo
                $q->orWhereHas('historialCambios', function ($h) use ($userId) {
                    $h->where('accion', 'DEVOLUCIÓN')
                      ->where('id_usuario', $userId);
                });

                // 4. O si lo ATENDIÓ y luego un Admin canceló esa atención (reproceso)
                $q->orWhere(function ($sub) use ($userId) {
                    $sub->whereHas('historialCambios', function ($h) use ($userId) {
                        $h->where('accion', 'ATENCIÓN')
                          ->where('id_usuario', $userId);
                    })
                    ->whereHas('historialCambios', function ($h) {
                        $h->where('accion', 'DEVOLUCIÓN')
                          ->where('campo_modificado', 'LIKE', '%ATENCIÓN CANCELADA%');
                    });
                });
            });
        }

        // FILTRO OBLIGATORIO POR AÑO (Por defecto el vigente)
        $query->where('anio_expediente', $anioFiltro);

        // Otros filtros
        if ($request->filled('buscar')) {
            $query->where(function($q) use ($request) {
                $q->where(function ($sub) use ($request) {
                    $sub->where('apellidos_y_nombres', 'ILIKE', '%' . $request->buscar . '%')
                        ->orWhere('razon_social', 'ILIKE', '%' . $request->buscar . '%');
                }); 
            });
        }
        if ($request->filled('num_exp')) $query->where('num_expediente', 'ILIKE', '%' . $request->num_exp . '%');
        if ($request->filled('fecha_desde')) $query->whereDate('fecha_recepcion', '>=', $request->fecha_desde);
        if ($request->filled('fecha_hasta')) $query->whereDate('fecha_recepcion', '<=', $request->fecha_hasta);
        if ($request->filled('id_estado')) $query->where('id_estado_expediente', $request->id_estado);
        
        if ($request->filled('id_subdivision')) {
            $query->where('area_actual', $request->id_subdivision);
        }

        $perPage = (auth()->user()->rol_id == 1 && ($request->has('registro') || $request->has('mostrar_filtros') ||$request->get('filtrado') == '1')) ? 4 : 10;
        
        $expedientes = $query->orderBy('correlativo_anual', 'desc')->paginate($perPage)->withQueryString();

        $expedientes->setCollection(
            $expedientes->getCollection()->reverse()->values()
        );

        return view('expedientes.index', compact('expedientes', 'anios', 'estados', 'subdivisiones'));
    }

    public function getProximoId(Request $request)
    {
        if (!$request->fecha) {return response()->json(['id' => '']);}
        return response()->json(['id' => Expediente::proximoIdPorFecha($request->fecha)]);
    }

    public function store(Request $request, InternalMLService $mlService)
    {
        $request->validate([
        'num_expediente' => 'required|regex:/^\S*$/',
        'fecha_recepcion' => 'required|date',
        'tipo_presentante' => 'required',
        'apellidos_y_nombres_razon' => 'required',
        'descripcion' => 'required',
        'folios_actual' => 'required|numeric|min:1',
    ]);

    $numManual = mb_strtoupper($request->num_expediente);
    $fechaRecepcion = $request->fecha_recepcion;
    $anio = date('Y', strtotime($fechaRecepcion));

    // VALIDACIÓN DE DUPLICADO
    $existe = Expediente::where('num_expediente', $numManual)
        ->where('anio_expediente', $anio)
        ->exists();

    if ($existe) {
        return redirect()->back()
            ->withInput()
            ->with('error_modal', "EL NÚMERO DE EXPEDIENTE '{$numManual}' YA SE ENCUENTRA REGISTRADO EN EL AÑO {$anio}. POR FAVOR VERIFIQUE.");
    }

    try {
        $nuevoExpediente = DB::transaction(function () use ($request, $anio, $numManual, $mlService) {
            
            // --- CORRECCIÓN POSTGRESQL AQUÍ ---
            // Obtenemos el último registro físico del año y lo bloqueamos
            $ultimoRegistro = Expediente::where('anio_expediente', $anio)
                ->orderBy('correlativo_anual', 'desc')
                ->lockForUpdate()
                ->first();

            $nuevoCorrelativo = ($ultimoRegistro) ? $ultimoRegistro->correlativo_anual + 1 : 1;

            // 1. Crear el expediente usando el correlativo calculado una sola vez
            $exp = Expediente::create([
                'id_usuario' => auth()->id(),
                'id_estado_expediente' => 1,
                'correlativo_anual' => $nuevoCorrelativo, 
                'num_expediente' => $numManual,
                'anio_expediente' => $anio,
                'fecha_registro' => now(),
                'descripcion' => $request->descripcion,
                'folios_actual' => $request->folios_actual,
                'tipo_presentante' => $request->tipo_presentante,
                'apellidos_y_nombres' => $request->tipo_presentante == 'NATURAL' ? mb_strtoupper($request->apellidos_y_nombres_razon) : null,
                'razon_social' => $request->tipo_presentante == 'JURIDICA' ? mb_strtoupper($request->apellidos_y_nombres_razon) : null,
                'fecha_recepcion' => $request->fecha_recepcion,
                'area_actual' => auth()->user()->subdivision_id,
            ]);

            // 2. IA para clasificar
            $categoria = $mlService->sugerirCategoria($request->descripcion);
            $exp->update(['categoria_final' => $categoria]);

            // 3. Historial
            HistorialCambio::create([
                'accion' => 'CREACIÓN',
                'valor_anterior' => null,
                'valor_nuevo' => $numManual,
                'campo_modificado' => 'REGISTRO DE EXPEDIENTE',
                'fecha_cambio' => now(),
                'id_usuario' => auth()->id(),
                'id_expediente' => $exp->id_expediente
            ]);

            return $exp;
        });

        return redirect()->route('expedientes.index', ['anio' => $anio, 'registro' => 1])
                ->with('registrado_ok', 'EXPEDIENTE ' . $numManual . ' GUARDADO Y CLASIFICADO POR IA.');


        } catch (\Exception $e) {
            // return redirect()->back()
            //     ->withInput()
            //     ->with('error_modal', 'OCURRIÓ UN ERROR INESPERADO AL GUARDAR EL EXPEDIENTE.');
            dd($e->getMessage()); //en caso de que no sepamos de donde viene el error si es de la BD
        }


    }

    public function show($id)
    {
        $expediente = Expediente::with(['estado', 'usuario.rol', 'usuario.subdivision', 'derivaciones.usuario.subdivision', 'historialCambios'])
            ->findOrFail($id);
        if ($this->esExpedienteCongeladoParaAnalista($expediente)) {
            return redirect()->route('expedientes.index')
                ->with('error_modal', 'Este expediente ya fue devuelto y no puede abrir sus detalles.');
        }
        $analistas = collect(); 
    
        return view('expedientes.form_expediente', compact('expediente', 'analistas'));
    }

    public function edit($id)
    {
        $expediente = Expediente::with(['estado', 'usuario', 'derivaciones.usuario', 'historialCambios'])->findOrFail($id);
        
        if ($this->esExpedienteCongeladoParaAnalista($expediente)) {
            return redirect()->route('expedientes.index')->with('error_modal', 'EXPEDIENTE CONGELADO.');
        }

        // 1. Obtener la categoría sugerida por la IA
        $categoriaIA = 'OTROS';
        try {
            $responseIA = Http::post("https://ia-expedientes-python.onrender.com/ml/predict-category", [
                'text' => $expediente->descripcion
            ]);
            if ($responseIA->successful()) {
                $categoriaIA = $responseIA->json()['categoria'];
            }
        } catch (\Exception $e) { }

        // 2. DICCIONARIO DE BÚSQUEDA (Mapea la etiqueta de la IA a tu descripción del Seeder)
        // Esto evita usar la columna inexistente 'categoria_final'
        $mapaBusqueda = [
            'INSCRIPCION Y REGISTRO'    => 'INSCRIP',
            'CONSTANCIA Y CERTIFICADOS' => 'CONSTANCIA',
            'TRIBUTOS Y DESCARGOS'      => 'DESCARGO',
            'TRAMITES GENERALES'        => 'INFORMACION',
            'OTROS'                     => '---'
        ];

        // Buscamos el término clave para el SQL basado en lo que dijo la IA
        $terminoABuscar = $mapaBusqueda[$categoriaIA] ?? $categoriaIA;

        // 3. Calcular experiencia usando la descripción existente
        $analistas = User::where('rol_id', 2)
            ->where('estado_usuario_id', 1)
            ->withCount(['derivaciones as carga_actual' => function($q) {
                $q->whereNull('fecha_atencion'); 
            }])
            ->get()
            ->map(function($analista) use ($categoriaIA, $terminoABuscar) {
                
                // Contamos cuántos expedientes ha atendido que contengan la palabra clave
                $experiencia = DB::table('derivacion_expedientes as d')
                    ->join('expedientes as e', 'd.id_expediente', '=', 'e.id_expediente')
                    ->where('d.id_usuario', $analista->id)
                    ->whereNotNull('d.fecha_atencion')
                    ->where('e.descripcion', 'ILIKE', '%' . $terminoABuscar . '%')
                    ->count();

                // Scoring IA
                $analista->score_ia = ($experiencia * 10) - ($analista->carga_actual * 5);
                
                // Mensaje Visual
                if ($experiencia > 0) {
                    $analista->motivo_ia = "Especialista en " . $categoriaIA . " (" . $experiencia . " casos).";
                } else {
                    $analista->motivo_ia = "Sugerido por disponibilidad (Baja carga).";
                }
                
                return $analista;
            })
            ->sortByDesc('score_ia');
            

        $recomendadoId = $analistas->first()?->id;

        return view('expedientes.form_expediente', compact('expediente', 'analistas', 'recomendadoId', 'categoriaIA'));
    }

    public function update(Request $request, $id)
    {
        $expediente = Expediente::findOrFail($id);
        if ($this->esExpedienteCongeladoParaAnalista($expediente)) {
            return redirect()->route('expedientes.index')
                ->with('error_modal', 'No puede modificar un expediente que ya fue devuelto.');
        }
        $rolUsuario = auth()->user()->rol_id;
        if ($rolUsuario == 1) {
            $request->validate([
                'num_expediente' => [
                    'required',
                    'string',
                    'regex:/^\S*$/',
                    \Illuminate\Validation\Rule::unique('expedientes', 'num_expediente')
                        ->ignore($id, 'id_expediente')
                        ->where('anio_expediente', $expediente->anio_expediente)
                ],
                    'folios_actual' => 'required|numeric|min:1',
                ], [
                    'num_expediente.unique' => 'ADVERTENCIA: EL NÚMERO DE EXPEDIENTE YA EXISTE EN ESTE AÑO.',
                    'num_expediente.required' => 'EL NÚMERO DE EXPEDIENTE ES OBLIGATORIO.',
                    'folios_actual.min' => 'EL NÚMERO DE FOLIOS DEBE SER AL MENOS 1.',
                    ]);
        }
        try {
            DB::transaction(function () use ($request, $expediente, $rolUsuario, $id) {
                
                // 1. Definimos qué campos vamos a intentar actualizar
                $datosParaActualizar = [];

                if ($rolUsuario == 1) {
                    // SI ES ADMINISTRADOR: Puede actualizar todo
                    $datosParaActualizar = [
                        'num_expediente'      => mb_strtoupper($request->num_expediente),
                        'tipo_presentante'    => $request->tipo_presentante,
                        'apellidos_y_nombres' => $request->tipo_presentante == 'NATURAL' ? mb_strtoupper($request->apellidos_y_nombres_razon) : null,
                        'razon_social'        => $request->tipo_presentante == 'JURIDICA' ? mb_strtoupper($request->apellidos_y_nombres_razon) : null,
                        'fecha_recepcion'     => $request->fecha_recepcion,
                        'folios_actual'       => $request->folios_actual,
                        'descripcion'         => $request->descripcion,
                        'observaciones'       => mb_strtoupper($request->observaciones),
                        'documentos_adjuntos' => mb_strtoupper($request->documentos_adjuntos),
                    ];
                } else {
                    $datosParaActualizar = [
                        'observaciones' => mb_strtoupper($request->observaciones),
                        'documentos_adjuntos' => mb_strtoupper($request->documentos_adjuntos),
                    ];
                }

                // 2. Cargamos los datos (Eloquent solo marcará como "sucios" los que cambien)
                $expediente->fill($datosParaActualizar);

                // 3. Auditoría de cambios (Historial)
                $camposAuditables = [
                    'num_expediente'      => 'NÚMERO DE EXPEDIENTE',
                    'folios_actual'       => 'FOLIOS',
                    'fecha_recepcion'     => 'FECHA RECEPCIÓN',
                    'tipo_presentante'    => 'TIPO PRESENTANTE',
                    'descripcion'         => 'DESCRIPCIÓN',
                    'observaciones'       => 'OBSERVACIONES',
                    'apellidos_y_nombres' => 'NOMBRE/APELLIDOS',
                    'razon_social'        => 'RAZÓN SOCIAL',
                    'documentos_adjuntos' => 'DOCUMENTOS ADJUNTOS',
                ];

                foreach ($camposAuditables as $campoDb => $nombreHumano) {
                    if ($expediente->isDirty($campoDb)) {
                        $valorAnterior = $expediente->getOriginal($campoDb);
                        $valorNuevo = $expediente->$campoDb;
                        
                        if ($valorAnterior == $valorNuevo) continue;

                        HistorialCambio::create([
                            'accion'           => 'MODIFICACIÓN',
                            'valor_anterior'   => $valorAnterior ?? '--',
                            'valor_nuevo'      => $valorNuevo ?? '--',
                            'campo_modificado' => $nombreHumano,
                            'fecha_cambio'     => now(),
                            'id_usuario'       => auth()->id(),
                            'id_expediente'    => $expediente->id_expediente
                        ]);
                    }
                }
                $esReprocesado = false;

                if (
                    $expediente->estado->nombre_estado == 'DEVUELTO' &&
                    str_contains(strtoupper($expediente->observaciones ?? ''), 'CANCELADA')
                ) {
                    $esReprocesado = true;
                }
                if ($request->accion == 'derivar' && $request->id_analista) {
                    $analista = User::find($request->id_analista);
                    if (!$analista || !$analista->subdivision || $analista->subdivision->estado_subdiv_area == 0) {
                        throw new \Exception("EL ANALISTA SE ENCUENTRA EN UNA OFICINA QUE NO ESTÁ ACTIVA. NO SE PUEDE REALIZAR LA DERIVACIÓN.");
                    }
                    $expediente->id_estado_expediente = 2; 
                    $expediente->area_actual = $analista->subdivision_id;

                    DerivacionExpediente::create([
                        'id_expediente'    => $expediente->id_expediente,
                        'id_usuario'       => $analista->id,
                        'fecha_derivacion' => now(),
                    ]);

                    HistorialCambio::create([
                        'accion'           => 'DERIVACIÓN',
                        'valor_anterior' => $esReprocesado ? 'DEVUELTO (RP)' : 'REGISTRADO',
                        'valor_nuevo'      => 'DERIVADO',
                        'campo_modificado' => 'ESTADO',
                        'fecha_cambio'     => now(),
                        'id_usuario'       => auth()->id(),
                        'id_expediente'    => $expediente->id_expediente
                    ]);

                } elseif ($request->accion == 'devolver') {
                    $expediente->id_estado_expediente = 4; 
                    if($rolUsuario == 2) { $expediente->area_actual = 1; }
                    
                    HistorialCambio::create([
                        'accion'           => 'DEVOLUCIÓN',
                        'valor_anterior' => $esReprocesado ? 'DEVUELTO (RP)' : 'DERIVADO',
                        'valor_nuevo'      => 'DEVUELTO',
                        'campo_modificado' => 'ESTADO',
                        'fecha_cambio'     => now(),
                        'id_usuario'       => auth()->id(),
                        'id_expediente'    => $expediente->id_expediente
                    ]);
                } elseif ($request->accion == 'atender') {
                    $expediente->id_estado_expediente = 3; 
                    
                    $ultimaDerivacion = $expediente->derivaciones()
                        ->latest('fecha_derivacion')
                        ->first();

                    if ($ultimaDerivacion) {
                        $ultimaDerivacion->update(['fecha_atencion' => now()]);
                    }

                    HistorialCambio::create([
                        'accion' => 'ATENCIÓN',
                        'valor_anterior' => $esReprocesado ? 'DEVUELTO (RP)' : 'DERIVADO',
                        'valor_nuevo' => 'ATENDIDO',
                        'campo_modificado' => 'ESTADO',
                        'fecha_cambio' => now(),
                        'id_usuario' => auth()->id(),
                        'id_expediente' => $expediente->id_expediente
                    ]);
                } elseif ($request->accion == 'cancelar_atencion') {
                    $expediente->id_estado_expediente = 4; 
                    $expediente->area_actual = 1; 

                    HistorialCambio::create([
                        'accion'           => 'DEVOLUCIÓN', 
                        'valor_anterior'   => 'ATENDIDO (C)',
                        'valor_nuevo'      => 'DEVUELTO (RP)',
                        'campo_modificado' => 'ESTADO (ATENCIÓN CANCELADA POR ADMIN)',
                        'fecha_cambio'     => now(),
                        'id_usuario'       => auth()->id(),
                        'id_expediente'    => $expediente->id_expediente
                    ]);
                }

                // 5. Guardar cambios finales
                if ($request->categoria_final) {
                    $expediente->categoria_final = $request->categoria_final;
                }
                $expediente->save();
            });

            return redirect()->route('expedientes.show', $id)->with('success', 'CAMBIOS GUARDADOS.');

        } catch (\Exception $e) {
            // return redirect()->back()->with('error_modal', 'ERROR: ' . $e->getMessage());
            dd($e->getMessage());
        }
    }

    public function verificarAuditoriaIA($id_expediente) {
        // Criterio: >3 devoluciones en 48 horas
        $limite = now()->subHours(48);
        
        $devoluciones = HistorialCambio::where('id_expediente', $id_expediente)
            ->where('accion', 'DEVOLUCIÓN')
            ->where('fecha_cambio', '>=', $limite)
            ->count();

        if ($devoluciones >= 3) {
            return [
                'alerta' => true,
                'mensaje' => "ALERTA DE AUDITORÍA: El expediente ha rebotado $devoluciones veces en menos de 48h."
            ];
        }
        return ['alerta' => false];
    }

    private function esExpedienteCongeladoParaAnalista(Expediente $expediente): bool
{
    if (auth()->user()->rol_id != 2) return false;

    $userId = auth()->id();
    
    // 1. ¿Es el dueño actual? (Está derivado a él y aún no lo atiende)
    $ultimaDeriv = $expediente->ultimaDerivacion;
    if ($expediente->id_estado_expediente == 2 && $ultimaDeriv && $ultimaDeriv->id_usuario == $userId && is_null($ultimaDeriv->fecha_atencion)) {
        return false; 
    }

    // 2. ¿Fue el último en actuar? 
    // Obtenemos la última acción registrada en el historial para este expediente
    $ultimaAccion = $expediente->historialCambios()->latest('id_historial')->first();

    if ($ultimaAccion && $ultimaAccion->id_usuario == $userId) {
        // Si mi última acción fue Atender o Devolver, puedo ver los detalles (pero no editar si ya terminó)
        if (in_array($ultimaAccion->accion, ['ATENCIÓN', 'DEVOLUCIÓN'])) {
            return false; // NO está congelado para ver detalles
        }
    }

    return true; // En cualquier otro caso, está congelado
}

    public function export(Request $request) 
    {
        $nombreArchivo = 'Expedientes_Export_' . now()->format('d-m-Y_H-i') . '.xlsx';
        return Excel::download(new ExpedienteExport($request), $nombreArchivo);
    }

    public function obtenerSugerenciaRechazo(Request $request, $id, InternalMLService $mlService)
    {
        // Llamada correcta al endpoint de Python
        $response = Http::post("https://ia-expedientes-python.onrender.com/ml/predict-rejection", [
            'text' => $request->motivo_breve
        ]);

        if ($response->successful()) {
            return response()->json([
                'categoria_rechazo' => $response->json()['motivo_ia'],
                'texto_formal' => 'Analizado por Inteligencia Artificial'
            ]);
        }

        return response()->json(['categoria_rechazo' => 'OTROS', 'error' => 'IA Offline'], 500);
    }

    public function analizarDescripcionIA(Request $request, InternalMLService $mlService)
    {
        if (!$request->descripcion || strlen($request->descripcion) < 10) {
            return response()->json(['error' => 'Descripción muy corta'], 400);
        }

        // Contar total de expedientes registrados
        $totalExpedientes = Expediente::count();
        
        // Solo sugerir si hay 30+ registros
        if ($totalExpedientes < 30) {
            return response()->json([
                'suficientes_datos' => false,
                'total_expedientes' => $totalExpedientes,
                'mensaje' => 'Base de datos insuficiente. Se necesitan 30 registros para sugerencias.'
            ]);
        }

        // Usar modelo ML para predecir categoría
        $categoria = $mlService->sugerirCategoria($request->descripcion);

        return response()->json([
            'suficientes_datos' => true,
            'categoria' => $categoria
        ]);
    }

    public function sugerirDescripciones(Request $request)
    {
        $totalRegistros = Expediente::count();

        // FASE 1: Menos de 30 registros -> No hay base suficiente
        if ($totalRegistros < 30) {
            return response()->json(['ready' => false, 'data' => []]);
        }

        // FASE 2: Ya hay base -> Buscamos en el historial (SQL Server LIKE)
        $query = strtoupper($request->get('q', ''));
        
        $sugerencias = Expediente::where('descripcion', 'ILIKE', "%{$query}%")
            ->select('descripcion', DB::raw('COUNT(*) as frecuencia'))
            ->groupBy('descripcion')
            ->orderByDesc('frecuencia')
            ->limit(5)
            ->get();

        return response()->json([
            'ready' => true,
            'data' => $sugerencias->pluck('descripcion')
        ]);
    }

    /**
     * CRITERIO 4: Recomendación Inteligente de Analista
     * Basada en: Menor carga de trabajo + Experiencia con categorías similares
     */
    public function recomendarAnalista(Request $request)
    {
        $categoria = strtoupper($request->get('categoria', ''));
        
        if (!$categoria) {
            return response()->json(['error' => 'Categoría requerida'], 400);
        }

        $analistas = User::where('rol_id', 2)->where('estado_usuario_id', 1)->get();
        
        $recomendaciones = [];
        
        foreach ($analistas as $analista) {
            // 1. Carga de trabajo actual (expedientes derivados no atendidos)
            $cargaActual = DerivacionExpediente::where('id_usuario', $analista->id)
                ->whereNull('fecha_atencion')
                ->count();
            
            // 2. Experiencia: expedientes atendidos (estado 3) con categoría similar (50+ = experto)
            $expedientesAtendidos = Expediente::whereHas('historialCambios', function($q) use ($analista, $categoria) {
                $q->where('id_usuario', $analista->id)
                  ->where('accion', 'ATENCIÓN')
                  ->where('valor_nuevo', 'ATENDIDO');
            })
            ->where('descripcion', 'LIKE', "%{$categoria}%")
            ->count();
            
            $experiencia = $expedientesAtendidos >= 50 ? 'EXPERTO' : ($expedientesAtendidos >= 20 ? 'INTERMEDIO' : 'PRINCIPIANTE');
            
            $recomendaciones[] = [
                'id' => $analista->id,
                'nombre' => $analista->nombre . ' ' . $analista->apellido,
                'carga_actual' => $cargaActual,
                'experiencia' => $experiencia,
                'expedientes_similares_atendidos' => $expedientesAtendidos,
                'puntuacion' => (100 - ($cargaActual * 5)) + ($expedientesAtendidos * 2)
            ];
        }
        
        // Ordenar por puntuación (mayor es mejor)
        usort($recomendaciones, fn($a, $b) => $b['puntuacion'] <=> $a['puntuacion']);
        
        return response()->json([
            'recomendado' => $recomendaciones[0] ?? null,
            'alternativas' => array_slice($recomendaciones, 1, 2)
        ]);
    }

    /**
     * CRITERIO 4b: Categorizar Motivo de Rechazo
     * Analiza el campo observación cuando se marca como DEVUELTO
     */
    public function categorizarMotivoRechazo(Request $request, InternalMLService $mlService)
    {
        $observacion = $request->get('observacion', '');
        
        try {
            $response = Http::post("https://ia-expedientes-python.onrender.com/ml/predict-rejection", [
                'text' => $observacion
            ]);

            if ($response->successful()) {
                return response()->json([
                    'categoria_rechazo' => $response->json()['motivo_ia'],
                    'confianza' => 'Alta (Modelo ML)'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'IA Offline'], 500);
        }
    }

    /**
     * CRITERIO 4c: Asistente de Auditoría
     * Alerta si hay >3 devoluciones en 48 horas
     */
    public function auditarDevoluciones($id)
    {
        $expediente = Expediente::findOrFail($id);
        $primeraDev = HistorialCambio::where('id_expediente', $id)->where('accion', 'DEVOLUCIÓN')->min('fecha_cambio');
        
        $totalRebotes = HistorialCambio::where('id_expediente', $id)
            ->where('accion', 'DEVOLUCIÓN')
            ->count();
        
        $ventanaHoras = $primeraDev ? now()->diffInHours(Carbon::parse($primeraDev)) : 0;

        // --- LLAMADA AL MODELO DE ANOMALÍAS (Python) ---
        try {
            $response = Http::post("https://ia-expedientes-python.onrender.com/ml/check-anomaly", [
                'total_rebotes' => $totalRebotes,
                'ventana_horas' => $ventanaHoras
            ]);

            if ($response->successful()) {
                return response()->json([
                    'expediente_id' => $id,
                    'alerta_activa' => $response->json()['es_anomalia'],
                    'mensaje' => $response->json()['mensaje'] . " (Analizado por IA)"
                ]);
            }
        } catch (\Exception $e) {
            // Fallback a lógica manual si el API de IA está caído
            return response()->json(['alerta_activa' => $totalRebotes > 3, 'mensaje' => 'Servicio IA offline']);
        }
    }
}
