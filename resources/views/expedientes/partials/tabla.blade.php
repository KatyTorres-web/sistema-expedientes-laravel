<div class="table-responsive shadow-sm rounded-4 bg-white">
    <table class="table table-hover align-middle custom-table mb-0">
        <thead>
            <tr>
                <th>ID</th>
                <th>FECHA</th>
                <th>N° EXPEDIENTE</th>
                <th>APELLIDOS Y NOMBRES / RAZON SOCIAL</th>
                <th>DESCRIPCION</th>
                <th class="text-center">FOLIOS</th>
                <th class="text-center">DERIVADO</th>
                <th>FECHA ESTADO</th>
                <th class="text-center">ESTADO</th>
                <th class="text-center">ACCIONES</th>
            </tr>
        </thead>
        <tbody>
            @forelse($expedientes as $exp)
            @php
                $userId = auth()->id();
                $isAnalista = auth()->user()->rol_id == 2;
                
                // 1. Obtenemos la última derivación (quién tiene el expediente ahora)
                $deriv = $exp->derivaciones->last();
                
                // 2. Obtenemos la ÚLTIMA acción registrada en el historial
                $ultimaAccion = $exp->historialCambios->last();

                // 3. Lógica de Acceso (Congelamiento)
                // Caso A: Es el dueño actual (Estado 2, asignado a él y sin atender)
                $esDuenioActual = ($exp->id_estado_expediente == 2 && $deriv && $deriv->id_usuario == $userId && is_null($deriv->fecha_atencion));
                
                // Caso B: Él fue el último en actuar (Acaba de Atender o Devolver)
                $fueElUltimoEnActuar = ($ultimaAccion && $ultimaAccion->id_usuario == $userId);

                // Un analista NO está congelado si es Dueño Actual O fue el Último en Actuar
                $esCongelado = $isAnalista && !($esDuenioActual || $fueElUltimoEnActuar);
                
                // 4. Lógica de etiquetas y estados
                $estadoNombre = strtoupper($exp->estado->nombre_estado);
                $esReproceso = ($ultimaAccion && str_contains($ultimaAccion->campo_modificado, 'CANCELADA'));
                
                // Definimos la fecha que se mostrará en "FECHA ESTADO"
                $fechaAMostrar = $ultimaAccion ? $ultimaAccion->fecha_cambio : $exp->fecha_registro;
            @endphp
            <tr>
                <td class="text-center fw-bold text-primary">{{ str_pad($exp->correlativo_anual, 4, '0', STR_PAD_LEFT) }}</td>
                <td>{{ \Carbon\Carbon::parse($exp->fecha_recepcion)->format('d/m/Y') }}</td>
                <td class="fw-bold">{{ $exp->num_expediente }}</td>
                <td>{{ strtoupper($exp->tipo_presentante) == 'NATURAL' ? $exp->apellidos_y_nombres : $exp->razon_social }}</td>
                <td><small>{{ str($exp->descripcion)->limit(35) }}</small></td>
                <td class="text-center">{{ $exp->folios_actual }}</td>
                <td class="text-center">
                    @if($deriv)
                        <div class="small line-height-1">
                            {{ $deriv->usuario->nombre }}<br>
                            <span class="fw-bold text-muted small">{{ $deriv->usuario->subdivision->cod_subdivision ?? '' }}</span>
                        </div>
                    @else
                        <span class="text-muted small">-</span>
                    @endif
                </td>

                {{-- Mostramos la fecha del último movimiento --}}
                <td>{{ \Carbon\Carbon::parse($fechaAMostrar)->format('d/m/Y H:i:s') }}</td>

                <td class="text-center">
                    @php
                        // Determinamos el color del badge
                        $class = match($estadoNombre) {
                            'DERIVADO'   => 'badge-derivado',
                            'ATENDIDO'   => 'badge-atendido',
                            'REGISTRADO' => 'badge-registrado',
                            'DEVUELTO'   => $esReproceso ? 'badge-reproceso' : 'badge-devuelto',
                            default      => 'bg-secondary text-white'
                        };
                    @endphp
                    <span class="badge-status {{ $class }}">
                        {{ $esReproceso && $estadoNombre == 'DEVUELTO' ? 'REPROCESO' : $exp->estado->nombre_estado }}
                    </span>
                </td>

                <td class="text-center">
                    @if($esCongelado)
                        <button class="btn btn-secondary btn-sm rounded-pill px-3 shadow-sm" disabled title="Este expediente ya no está bajo su gestión">
                            CONGELADO
                        </button>
                    @else
                        <a href="{{ route('expedientes.show', $exp->id_expediente) }}" class="btn btn-primary-custom btn-sm rounded-pill px-3 shadow-sm text-white text-decoration-none">
                            Detalles
                        </a>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="text-center py-5 text-muted">
                    No se encontraron expedientes con los filtros seleccionados.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>    
@include('layouts.partials.pagination', ['paginator' => $expedientes])