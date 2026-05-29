<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Expediente;
use App\Models\DerivacionExpediente;
use App\Models\HistorialCambio;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExpedienteAugmentationSeeder extends Seeder
{
    protected $startDate = '2026-05-02';
    protected $endDate = '2026-05-08';
    protected $analistas = [3, 4, 5, 6, 7];
    protected $areaActualPorDefecto = 1;
    protected $areaFiscalizacion = 3;

    protected $descripciones = [
        'DESCARGO DE PREDIO', 'CONSTANCIA DE NO ADEUDO', 'PRESCRIPCION DE DEUDA TRIBUTARIA',
        'INSCRIPCION DE PREDIO', 'RECTIFICACION DE AREA', 'MODIFICACION DE DATOS', 'INSCRIPCION DE TERRENO',
        'DEDUCCION POR ADULTO MAYOR', 'BAJA DE PREDIO', 'UNIFICACION DE TERRENOS',
        'SOLICITO ACCESO A LA INFORMACION PUBLICA', 'COPIA DE AUTOVALUO',
        'DEVOLUCION DE DINERO POR COBRO INDEBIDO', 'ACTUALIZACION DE AREA',
        'DESCARGO DE PREDIO', 'DESCUENTO DE DEUDA DE IMPUESTO PREDIAL POR ADULTO MAYOR', 
        'BAJA DE PREDIO', 'UNIFICACION DE AREA'
    ];

    protected $nombres = [
        'JOSE MANUEL PEREZ GARCIA', 'MARIA ELENA QUISPE VILCA', 'LILIA ROCIO SANCHEZ MENDOZA',
        'LAZARO ENRIQUEZ VARGAS', 'SANDRA MARIA LOPEZ HUAMAN', 'JORGE LUIS MENDOZA QUISPE', 
        'ANA MARIA RIVERA CHAVEZ', 'EDUARDO ALFREDO CASTILLO HUAMAN', 'EDGARDO FERNANDO RAMOS VELA', 
        'MARTA ELIZABETH GONZALES PEREZ', 'CARLOS ANDRES HUAMAN CAJAHUARINGA', 
        'ROSA ANGELICA CRISTOBAL CARHUAMACA', 'PEDRO LUIS RAMOS ROJAS', 'ANA CECILIA GONZALES CHIHUAN',
        'MIGUEL ANGEL VILA JOAQUIN', 'LUCIA FERNANDA DE LA CRUZ MEZA',
        'EMPRESA DE SERVICIOS MULTIPLES YARICAMPA S.A.C.', 'CONSORCIO HUAROC S.A.C.',
        'INVERSIONES UMBRALES S.A.C.', 'TOPPLUM S.A.C.'
    ];

    public function run()
    {
        // 1. Obtener el MÁXIMO correlativo actual
        $maxCorrelativo = DB::table('expedientes')
            ->where('anio_expediente', 2026)
            ->max('correlativo_anual') ?? 0;
        
        $expedientesExistentes = Expediente::count();
        $this->command->info("📊 Datos existentes: {$expedientesExistentes} expedientes");
        $this->command->info("📈 Último correlativo usado: {$maxCorrelativo}");

        // 2. Obtener números existentes
        $numerosExistentes = DB::table('expedientes')
            ->where('anio_expediente', 2026)
            ->pluck('num_expediente')
            ->flip()
            ->toArray();
        
        $this->command->info("📚 Cargados " . count($numerosExistentes) . " números únicos existentes");

        // 3. Calcular días hábiles en el rango
        $fechaInicio = Carbon::parse($this->startDate);
        $fechaFin = Carbon::parse($this->endDate);
        $diasTotales = $fechaInicio->diffInDays($fechaFin) + 1;
        
        // 4. Calcular cuántos expedientes por día (entre 20-30)
        $expedientesPorDia = rand(30, 40);
        $totalEsperado = $diasTotales * $expedientesPorDia;
        
        $this->command->info("📅 Rango de fechas: {$this->startDate} al {$this->endDate} ({$diasTotales} días)");
        $this->command->info("📋 Expedientes por día: ~{$expedientesPorDia}");
        $this->command->info("🎯 Total esperado: ~{$totalEsperado} expedientes");
        
        DB::beginTransaction();
        
        $insertados = 0;
        $correlativoActual = $maxCorrelativo;
        $fechaActual = clone $fechaInicio;
        
        // Cache de números generados en esta ejecución
        $numerosGenerados = [];
        
        while ($fechaActual->lte($fechaFin) && $insertados < $totalEsperado) {
            // Determinar cuántos expedientes hoy (20-30)
            $hoy = clone $fechaActual;
            $cantidadHoy = rand(20, 30);
            
            // Horarios de registro (8am a 6pm, escalonados)
            $horasDisponibles = range(8, 17); // 8am a 5pm
            shuffle($horasDisponibles);
            
            $this->command->info("📅 Procesando fecha: {$hoy->format('d/m/Y')} - {$cantidadHoy} expedientes");
            
            for ($i = 0; $i < $cantidadHoy && $insertados < $totalEsperado; $i++) {
                // Generar número de expediente ÚNICO
                $numExpediente = $this->generarNumeroExpedienteUnico($numerosExistentes, $numerosGenerados);
                
                if (!$numExpediente) {
                    $this->command->warn("⚠️ No se pudo generar número único para fecha {$hoy->format('d/m/Y')}");
                    continue;
                }
                
                $numerosGenerados[$numExpediente] = true;
                $correlativoActual++;
                
                // Fecha de recepción = fecha actual
                $fechaRecepcion = clone $hoy;
                
                // Fecha de registro = MISMO DÍA (como solicitaste)
                // Hora aleatoria entre 8am y 6pm, distribuida
                $horaRegistro = $horasDisponibles[$i % count($horasDisponibles)];
                $minutoRegistro = rand(0, 59);
                $segundoRegistro = rand(0, 59);
                $fechaRegistro = (clone $hoy)->setTime($horaRegistro, $minutoRegistro, $segundoRegistro);
                
                // Datos aleatorios
                $tipoPresentante = rand(0, 1) ? 'NATURAL' : 'JURIDICA';
                $nombreRazon = $tipoPresentante == 'NATURAL'
                    ? $this->nombres[array_rand($this->nombres)]
                    : $this->nombres[array_rand(array_slice($this->nombres, -4))];
                
                try {
                    $expediente = Expediente::create([
                        'id_usuario' => 2,
                        'id_estado_expediente' => 1,
                        'area_actual' => $this->areaActualPorDefecto,
                        'correlativo_anual' => $correlativoActual,
                        'num_expediente' => $numExpediente,
                        'anio_expediente' => 2026,
                        'fecha_registro' => $fechaRegistro,
                        'descripcion' => $this->descripciones[array_rand($this->descripciones)],
                        'folios_actual' => rand(1, 50),
                        'observaciones' => rand(0, 3) == 0 ? 'OBS: ' . $this->generarObservacionAleatoria() : null,
                        'razon_social' => $tipoPresentante == 'JURIDICA' ? $nombreRazon : null,
                        'apellidos_y_nombres' => $tipoPresentante == 'NATURAL' ? $nombreRazon : null,
                        'fecha_recepcion' => $fechaRecepcion,
                        'tipo_presentante' => $tipoPresentante,
                        'documentos_adjuntos' => rand(0, 2) == 0 ? 'DOC: ' . $this->generarDocumentoAleatorio() : null,
                    ]);
                    
                    // Simular el flujo
                    $this->simularFlujoExpediente($expediente->id_expediente, $fechaRegistro);
                    
                    $insertados++;
                    
                    if ($insertados % 100 == 0) {
                        DB::commit();
                        DB::beginTransaction();
                        $this->command->info("✅ Generados {$insertados} expedientes... (correlativo: {$correlativoActual})");
                    }
                    
                } catch (\Exception $e) {
                    $this->command->error("❌ Error: " . $e->getMessage());
                    DB::rollBack();
                    DB::beginTransaction();
                    $correlativoActual--;
                }
            }
            
            // Avanzar al siguiente día
            $fechaActual->addDay();
        }
        
        DB::commit();

        $totalFinal = Expediente::count();
        $this->command->info("🎉 ¡COMPLETADO!");
        $this->command->info("📊 Total expedientes AHORA: {$totalFinal}");
        $this->command->info("➕ Nuevos agregados: {$insertados}");
        $this->command->info("🔢 Último correlativo: {$correlativoActual}");
        $this->command->info("📅 Última fecha procesada: {$fechaActual->subDay()->format('d/m/Y')}");
    }

    /**
     * Genera número de expediente único (evita duplicados en BD y en esta ejecución)
     */
    private function generarNumeroExpedienteUnico($existentes, $generados)
    {
        $letras = ['EXP', 'INFORME', 'PROVEIDO', 'CARTA', 'OFICIO', 'RESOLUCION', 'MEMORANDO'];
        $maxIntentos = 50;
        
        for ($intento = 1; $intento <= $maxIntentos; $intento++) {
            $tipo = $letras[array_rand($letras)];
            $numero = rand(10000, 99999);
            $candidato = $tipo . $numero;
            
            if (!isset($existentes[$candidato]) && !isset($generados[$candidato])) {
                return $candidato;
            }
        }
        
        return null;
    }

    /**
     * Simula el flujo del expediente con fechas coherentes
     */
    private function simularFlujoExpediente($expedienteId, $fechaRegistro)
    {
        $expediente = Expediente::find($expedienteId);
        if (!$expediente) return;
        
        $fechaLimite = Carbon::parse($this->endDate)->endOfDay();

        $tipoFlujo = rand(1, 100);

        if ($tipoFlujo <= 60) {
            $this->flujoNormal($expediente, $fechaRegistro, $fechaLimite);
        } elseif ($tipoFlujo <= 85) {
            $this->flujoConDevolucion($expediente, $fechaRegistro, $fechaLimite, 1);
        } elseif ($tipoFlujo <= 95) {
            $this->flujoConDevolucion($expediente, $fechaRegistro, $fechaLimite, 2);
        } else {
            $this->flujoConReproceso($expediente, $fechaRegistro, $fechaLimite);
        }
    }

    private function flujoNormal($expediente, $fechaRegistro, $fechaLimite)
    {
        // Derivación (1-5 días después del registro)
        $fechaDerivacion = $this->fechaAleatoriaEnRango(
            $fechaRegistro->copy()->addDay(), 
            $fechaRegistro->copy()->addDays(5), 
            $fechaLimite
        );
        if (!$fechaDerivacion) return;

        $analista = $this->analistas[array_rand($this->analistas)];
        $this->crearDerivacion($expediente->id_expediente, $analista, $fechaDerivacion, null);
        $this->actualizarEstadoExpediente($expediente, 2);
        $this->crearHistorialCambio($expediente->id_expediente, 2, 'DERIVACIÓN', 'REGISTRADO', 'DERIVADO', 'ESTADO', $fechaDerivacion);

        // Resolución (1-15 días después de derivación)
        $fechaResolucion = $this->fechaAleatoriaEnRango(
            $fechaDerivacion->copy()->addDay(), 
            $fechaDerivacion->copy()->addDays(15), 
            $fechaLimite
        );
        if (!$fechaResolucion) return;

        $esAtendido = rand(1, 100) <= 70;
        
        if ($esAtendido) {
            $this->marcarAtencion($expediente->id_expediente, $analista, $fechaResolucion);
            $this->actualizarEstadoExpediente($expediente, 3);
            $this->crearHistorialCambio($expediente->id_expediente, $analista, 'ATENCIÓN', 'DERIVADO', 'ATENDIDO', 'ESTADO', $fechaResolucion);
        } else {
            $this->crearHistorialCambio($expediente->id_expediente, $analista, 'DEVOLUCIÓN', 'DERIVADO', 'DEVUELTO', 'ESTADO', $fechaResolucion);
            $this->actualizarEstadoExpediente($expediente, 4);
        }
    }

    private function flujoConDevolucion($expediente, $fechaRegistro, $fechaLimite, $rebotes)
    {
        $fechaActual = clone $fechaRegistro;
        $estado = 1;

        for ($i = 0; $i <= $rebotes; $i++) {
            $fechaDerivacion = $this->fechaAleatoriaEnRango(
                $fechaActual->copy()->addDay(), 
                $fechaActual->copy()->addDays(5), 
                $fechaLimite
            );
            if (!$fechaDerivacion) break;

            $analista = $this->analistas[array_rand($this->analistas)];
            $this->crearDerivacion($expediente->id_expediente, $analista, $fechaDerivacion, null);
            $this->actualizarEstadoExpediente($expediente, 2);
            $this->crearHistorialCambio($expediente->id_expediente, 2, 'DERIVACIÓN', $estado == 1 ? 'REGISTRADO' : 'DEVUELTO', 'DERIVADO', 'ESTADO', $fechaDerivacion);

            $fechaActual = clone $fechaDerivacion;

            if ($i < $rebotes) {
                $fechaDevolucion = $this->fechaAleatoriaEnRango(
                    $fechaActual->copy()->addDay(), 
                    $fechaActual->copy()->addDays(7), 
                    $fechaLimite
                );
                if (!$fechaDevolucion) break;

                $this->crearHistorialCambio($expediente->id_expediente, $analista, 'DEVOLUCIÓN', 'DERIVADO', 'DEVUELTO', 'ESTADO', $fechaDevolucion);
                $this->actualizarEstadoExpediente($expediente, 4);
                $fechaActual = clone $fechaDevolucion;
                $estado = 4;
            } else {
                $fechaAtencion = $this->fechaAleatoriaEnRango(
                    $fechaActual->copy()->addDay(), 
                    $fechaActual->copy()->addDays(10), 
                    $fechaLimite
                );
                if (!$fechaAtencion) break;

                $this->marcarAtencion($expediente->id_expediente, $analista, $fechaAtencion);
                $this->actualizarEstadoExpediente($expediente, 3);
                $this->crearHistorialCambio($expediente->id_expediente, $analista, 'ATENCIÓN', 'DERIVADO', 'ATENDIDO', 'ESTADO', $fechaAtencion);
            }
        }
    }

    private function flujoConReproceso($expediente, $fechaRegistro, $fechaLimite)
    {
        // Primera derivación y atención
        $fechaDerivacion1 = $this->fechaAleatoriaEnRango(
            $fechaRegistro->copy()->addDay(), 
            $fechaRegistro->copy()->addDays(5), 
            $fechaLimite
        );
        if (!$fechaDerivacion1) return;

        $analista1 = $this->analistas[array_rand($this->analistas)];
        $this->crearDerivacion($expediente->id_expediente, $analista1, $fechaDerivacion1, null);
        $this->actualizarEstadoExpediente($expediente, 2);
        $this->crearHistorialCambio($expediente->id_expediente, 2, 'DERIVACIÓN', 'REGISTRADO', 'DERIVADO', 'ESTADO', $fechaDerivacion1);

        $fechaAtencion1 = $this->fechaAleatoriaEnRango(
            $fechaDerivacion1->copy()->addDay(), 
            $fechaDerivacion1->copy()->addDays(10), 
            $fechaLimite
        );
        if (!$fechaAtencion1) return;

        $this->marcarAtencion($expediente->id_expediente, $analista1, $fechaAtencion1);
        $this->actualizarEstadoExpediente($expediente, 3);
        $this->crearHistorialCambio($expediente->id_expediente, $analista1, 'ATENCIÓN', 'DERIVADO', 'ATENDIDO', 'ESTADO', $fechaAtencion1);

        // Cancelación por admin (reproceso)
        $fechaCancelacion = $this->fechaAleatoriaEnRango(
            $fechaAtencion1->copy()->addDay(), 
            $fechaAtencion1->copy()->addDays(5), 
            $fechaLimite
        );
        if (!$fechaCancelacion) return;

        $this->actualizarEstadoExpediente($expediente, 4);
        $this->crearHistorialCambio(
            $expediente->id_expediente, 
            1, 
            'DEVOLUCIÓN', 
            'ATENDIDO', 
            'DEVUELTO', 
            'ESTADO (ATENCIÓN CANCELADA POR ADMIN)', 
            $fechaCancelacion
        );

        // Nueva derivación y atención
        $fechaDerivacion2 = $this->fechaAleatoriaEnRango(
            $fechaCancelacion->copy()->addDay(), 
            $fechaCancelacion->copy()->addDays(3), 
            $fechaLimite
        );
        if (!$fechaDerivacion2) return;

        $analista2 = $this->analistas[array_rand($this->analistas)];
        $this->crearDerivacion($expediente->id_expediente, $analista2, $fechaDerivacion2, null);
        $this->actualizarEstadoExpediente($expediente, 2);
        $this->crearHistorialCambio($expediente->id_expediente, 1, 'DERIVACIÓN', 'DEVUELTO', 'DERIVADO', 'ESTADO', $fechaDerivacion2);

        $fechaAtencion2 = $this->fechaAleatoriaEnRango(
            $fechaDerivacion2->copy()->addDay(), 
            $fechaDerivacion2->copy()->addDays(10), 
            $fechaLimite
        );
        if (!$fechaAtencion2) return;

        $this->marcarAtencion($expediente->id_expediente, $analista2, $fechaAtencion2);
        $this->actualizarEstadoExpediente($expediente, 3);
        $this->crearHistorialCambio($expediente->id_expediente, 1, 'ATENCIÓN', 'DERIVADO', 'ATENDIDO', 'ESTADO', $fechaAtencion2);
    }

    // ========== MÉTODOS AUXILIARES ==========

    private function crearDerivacion($expedienteId, $analistaId, $fechaDerivacion, $fechaAtencion = null)
    {
        DerivacionExpediente::create([
            'id_expediente' => $expedienteId,
            'id_usuario' => $analistaId,
            'fecha_derivacion' => $fechaDerivacion,
            'fecha_atencion' => $fechaAtencion,
        ]);
    }

    private function marcarAtencion($expedienteId, $analistaId, $fechaAtencion)
    {
        $derivacion = DerivacionExpediente::where('id_expediente', $expedienteId)
            ->where('id_usuario', $analistaId)
            ->whereNull('fecha_atencion')
            ->latest('fecha_derivacion')
            ->first();

        if ($derivacion) {
            $derivacion->fecha_atencion = $fechaAtencion;
            $derivacion->save();
        }
    }

    private function actualizarEstadoExpediente($expediente, $estadoId)
    {
        $expediente->id_estado_expediente = $estadoId;
        if ($estadoId == 2 && $expediente->area_actual == $this->areaActualPorDefecto) {
            $expediente->area_actual = $this->areaFiscalizacion;
        }
        $expediente->save();
    }

    private function crearHistorialCambio($expedienteId, $usuarioId, $accion, $valorAnterior, $valorNuevo, $campoModificado, $fecha)
    {
        HistorialCambio::create([
            'id_expediente' => $expedienteId,
            'id_usuario' => $usuarioId,
            'accion' => $accion,
            'valor_anterior' => $valorAnterior,
            'valor_nuevo' => $valorNuevo,
            'campo_modificado' => $campoModificado,
            'fecha_cambio' => $fecha,
        ]);
    }

    private function fechaAleatoriaEnRango($inicio, $fin, $limiteSuperior)
    {
        if ($inicio->gt($limiteSuperior)) return null;
        if ($fin->gt($limiteSuperior)) $fin = clone $limiteSuperior;
        if ($inicio->gt($fin)) return null;

        $timestamp = rand($inicio->timestamp, $fin->timestamp);
        return Carbon::createFromTimestamp($timestamp);
    }

    private function generarObservacionAleatoria()
    {
        $obs = [
            'SE ADJUNTA DOCUMENTACIÓN REQUERIDA',
            'PENDIENTE DE REVISIÓN DE FIRMAS',
            'DERIVAR A FISCALIZACIÓN PARA EVALUACIÓN',
            'INFORME TÉCNICO EMITIDO',
            'SE SOLICITA SUBSANACIÓN DE REQUISITOS',
        ];
        return $obs[array_rand($obs)];
    }

    private function generarDocumentoAleatorio()
    {
        $docs = [
            'INFORME TÉCNICO N° ' . rand(1, 999),
            'CARTA N° ' . rand(1, 9999),
            'RESOLUCIÓN GERENCIAL N° ' . rand(1, 999),
            'DECLARACIÓN JURADA N° ' . rand(10000, 99999),
        ];
        return $docs[array_rand($docs)];
    }
}