@extends('layouts.principal')

@section('content')
<div class="container-fluid p-0">
    <h2 class="fw-bold mb-3">Historial</h2>

    @if($alertasCriticas->count() > 0)
    <div class="card border-0 shadow-sm rounded-4 mb-4" style="border-left: 6px solid #dc3545 !important;">
        <div class="card-header bg-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center animate__animated animate__pulse animate__infinite" style="width: 45px; height: 45px;">
                        <i class="bi bi-robot fs-4"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-danger mb-0">ALERTA: {{ $alertasCriticas->count() }} CASO(S) DETECTADOS</h5>
                        <small class="text-secondary text-uppercase fw-bold">Detección de anomalías</small>
                    </div>
                </div>
                <!-- BOTÓN PARA DESPLEGAR/REDUCIR TODO EL BLOQUE -->
                <button class="btn btn-outline-danger btn-sm rounded-pill fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#contenedorAlertasIA">
                    <i class="bi bi-chevron-expand me-1"></i> VER / OCULTAR ALERTAS
                </button>
            </div>
        </div>

        <div class="collapse show" id="contenedorAlertasIA">
            <div class="card-body pt-0 px-4 pb-4">
                <div class="row g-3">
                    @foreach($alertasCriticas as $alerta)
                    <div class="col-md-6">
                        <div class="p-3 rounded-4 border border-danger-subtle bg-light shadow-sm">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-danger">ANOMALÍA CRÍTICA</span>
                                <button class="btn btn-link btn-sm p-0 text-danger" type="button" data-bs-toggle="collapse" data-bs-target="#infoIA{{ $alerta->id_expediente }}">
                                    <i class="bi bi-info-circle-fill"></i> Detalles
                                </button>
                            </div>
                            <p class="mb-2">Expediente: <strong class="text-primary">{{ $alerta->expediente->num_expediente }}</strong></p>
                            
                            <!-- BLOQUE DESPLEGABLE INDIVIDUAL -->
                            <div class="collapse" id="infoIA{{ $alerta->id_expediente }}">
                                <div class="mt-2 p-2 rounded-3 bg-white border-start border-4 border-danger">
                                    <small class="text-danger fw-bold d-block mb-1">INFERENCIA DE LA IA:</small>
                                    <span class="small text-dark italic">
                                        "{{ $alerta->mensaje_ia }}. Se recomienda inspeccionar la carga del analista actual ya que el expediente presenta un estancamiento fuera de los parámetros normales."
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- SECCIÓN DE FILTROS -->
    <div class="mb-4">
        <div class="card card-filter border-pink shadow-sm">
            <div class="card-body p-4">
                <form id="formFiltros" action="{{ route('historial.index') }}" method="GET">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-uppercase">Expediente / Usuario:</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                <input type="text" name="buscar" class="form-control form-thin" value="{{ request('buscar') }}" placeholder="Buscar...">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-uppercase">Rango de Fechas:</label>
                            <div class="d-flex gap-2">
                                <input type="date" name="fecha_desde" id="fecha_desde" class="form-control form-control-sm" value="{{ request('fecha_desde') }}">
                                <input type="date" name="fecha_hasta" id="fecha_hasta" class="form-control form-control-sm" value="{{ request('fecha_hasta') }}">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-uppercase">Filtrar por Acción:</label>
                            <select name="accion" class="form-select form-select-sm">
                                <option value="">TODAS LAS ACCIONES</option>
                                @foreach($acciones as $acc)
                                    <option value="{{ $acc }}" {{ request('accion') == $acc ? 'selected' : '' }}>{{ $acc }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2 text-end">
                            <button type="button" id="btnSubmitFiltros" class="btn btn-primary-custom btn-sm w-100 mb-1">APLICAR</button>
                            <a href="{{ route('historial.index', ['tab' => $tab]) }}" class="btn btn-secondary btn-sm w-100">LIMPIAR</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- TABS -->
    <div class="d-flex gap-4 mb-3 border-bottom border-2">
        <a href="{{ route('historial.index', ['tab' => 'general']) }}" class="pb-2 text-decoration-none fw-bold {{ $tab == 'general' ? 'text-danger border-bottom border-danger border-3' : 'text-secondary' }}">GENERAL</a>
        <a href="{{ route('historial.index', ['tab' => 'derivaciones']) }}" class="pb-2 text-decoration-none fw-bold {{ $tab == 'derivaciones' ? 'text-danger border-bottom border-danger border-3' : 'text-secondary' }}">DERIVACIONES</a>
    </div>

    <!-- TABLA UNIFICADA -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center" style="table-layout: fixed; min-width: 900px;">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 12%;">FECHA</th>
                            <th style="width: 15%;">EXPEDIENTE</th>
                            <th style="width: 18%;">USUARIO ORIGEN</th>
                            @if($tab == 'derivaciones') 
                                <th style="width: 18%;">USUARIO DESTINO</th> 
                            @endif
                            <th style="width: 13%;">ACCIÓN</th>
                            <th style="width: 12%;">ANTES</th>
                            <th style="width: 12%;">DESPUÉS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $estilosAccion = [
                                'DERIVACIÓN'  => 'text-info',
                                'DEVOLUCIÓN'  => 'text-danger',
                                'ATENCIÓN'    => 'text-success',
                                'CREACIÓN'    => 'text-primary',
                                'MODIFICACIÓN'=> 'text-warning'
                            ];
                        @endphp

                        @forelse($datos as $item)
                        @php
                            $fechaObj = \Carbon\Carbon::parse($item->fecha_cambio);
                            $userOrig = $item->usuario;
                            $exp      = $item->expediente;
                            
                            // Formateo de nombre rápido sin función anónima
                            $nombreOrig = "S/U";
                            if($userOrig) {
                                $n = explode(' ', trim($userOrig->nombre))[0];
                                $a = explode(' ', trim($userOrig->apellido))[0];
                                $nombreOrig = mb_convert_case("$n $a", MB_CASE_TITLE, "UTF-8");
                            }

                            $colorAccion = $estilosAccion[$item->accion] ?? 'text-secondary';

                            $destinoHtml = '--';
                            if($tab == 'derivaciones') {
                                if($item->accion == 'DERIVACIÓN') {
                                    $deriv = $exp->derivaciones
                                        ->where('fecha_derivacion', '>=', $fechaObj->copy()->subSeconds(5))
                                        ->first();
                                    if($deriv && $uDest = $deriv->usuario) {
                                        $nD = explode(' ', trim($uDest->nombre))[0];
                                        $aD = explode(' ', trim($uDest->apellido))[0];
                                        $nombreDest = mb_convert_case("$nD $aD", MB_CASE_TITLE, "UTF-8");
                                        $destinoHtml = '<div class="fw-bold">'.$nombreDest.'</div><small class="text-muted text-uppercase" style="font-size:0.65rem">ANALISTA · '.($uDest->subdivision->cod_subdivision ?? "OF").'</small>';
                                    }
                                } elseif($item->accion == 'DEVOLUCIÓN' && optional($userOrig)->rol_id == 2) {
                                    $destinoHtml = '<div class="fw-bold text-primary">ADMINISTRADOR</div>';
                                }
                            }
                        @endphp
                        <tr>
                            <td class="small text-secondary">
                                {{ $fechaObj->format('d/m/Y') }}<br>
                                <span class="fw-bold">{{ $fechaObj->format('H:i') }}</span>
                            </td>
                            <td>
                                <div class="fw-bold text-primary">{{ $exp->num_expediente ?? 'N/A' }}</div>
                                <div class="text-muted small" style="font-size: 0.75rem;">{{ $exp->correlativo_anual ?? '0' }}-{{ $exp->anio_expediente ?? '0' }}</div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark" style="font-size: 0.85rem;">{{ $nombreOrig }}</div>
                                <small class="text-uppercase text-muted" style="font-size: 0.65rem;">
                                    {{ optional($userOrig->rol)->nombre_rol }} · {{ optional($userOrig->subdivision)->cod_subdivision ?? 'OF' }}
                                </small>
                            </td>

                            @if($tab == 'derivaciones')
                            <td style="font-size: 0.85rem;">{!! $destinoHtml !!}</td>
                            @endif

                            <td>
                                <span class="fw-bold {{ $colorAccion }}" style="font-size: 0.8rem;">{{ $item->accion }}</span>
                                <br><small class="text-muted" style="font-size: 0.65rem;">{{ $item->campo_modificado }}</small>
                            </td>

                            <td class="text-muted small" style="font-size: 0.8rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <div class="cell-expandable" data-full="{{ $item->valor_anterior ?? '--' }}">
                                    @if(str_contains($item->valor_anterior, '(REPROCESO)'))
                                        <strong>DEVUELTO</strong> (RP)
                                    @elseif($item->accion == 'DEVOLUCIÓN' && str_contains($item->campo_modificado, 'CANCELADA'))
                                        <strong>ATENDIDO</strong> (C)
                                    @else
                                        {{ $item->valor_anterior ?? '--' }}
                                    @endif
                                </div>
                            </td>

                            <td class="fw-bold text-success" style="font-size: 0.8rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <div class="cell-expandable" data-full="{{ $item->valor_nuevo }}">
                                {{ $item->valor_nuevo }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ $tab == 'derivaciones' ? 7 : 6 }}" class="text-center py-5 text-muted italic">
                                No se encontraron movimientos registrados.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    

    <!-- PAGINACIÓN -->
    @if($datos->hasPages())
        <div class="mt-3">
            @include('layouts.partials.pagination', ['paginator' => $datos])
        </div>
    @endif
</div>
<style>
    /* Celda con texto truncado */
.cell-expandable {
    cursor: pointer;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    position: relative;
    transition: color 0.2s;
}

.cell-expandable:hover {
    color: #4da3ff;
}

/* El cuadro flotante "discreto" */
#floating-detail-box {
    position: fixed;
    z-index: 9999;
    background: rgba(255, 255, 255, 0.8); /* Fondo semi-transparente */
    backdrop-filter: blur(8px); /* Efecto de desenfoque */
    -webkit-backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 12px;
    padding: 12px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    color: #333;
    font-size: 0.85rem;
    max-width: 300px;
    word-wrap: break-word;
    display: none; /* Oculto por defecto */
    pointer-events: none; /* No interfiere con otros clics */
    animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fDesde = document.getElementById('fecha_desde');
    const fHasta = document.getElementById('fecha_hasta');
    const form = document.getElementById('formFiltros');

    document.getElementById('btnSubmitFiltros').addEventListener('click', function() {
        const valDesde = fDesde.value;
        const valHasta = fHasta.value;

        if ((valDesde && !valHasta) || (!valDesde && valHasta)) {
            showModalAlert("DEBE COMPLETAR AMBAS FECHAS PARA EL RANGO.");
            return;
        }
        if (valDesde && valHasta && valDesde > valHasta) {
            showModalAlert("LA FECHA 'DESDE' NO PUEDE SER MAYOR A LA FECHA 'HASTA'.");
            return;
        }
        form.submit();
    });

    let floatBox = document.createElement('div');
    floatBox.id = 'floating-detail-box';
    document.body.appendChild(floatBox);

    // 2. Escuchar clics en las celdas expandibles
    document.querySelectorAll('.cell-expandable').forEach(cell => {
        cell.addEventListener('click', function(e) {
            e.stopPropagation();
            const fullText = this.getAttribute('data-full');
            
            if (!fullText || fullText === '--') return;

            floatBox.innerText = fullText;
            floatBox.style.display = 'block';

            // Posicionamiento inteligente
            const rect = this.getBoundingClientRect();
            floatBox.style.left = rect.left + 'px';
            floatBox.style.top = (rect.top - floatBox.offsetHeight - 10) + 'px';

            // Si se sale por arriba, mostrar abajo
            if (rect.top - floatBox.offsetHeight < 0) {
                floatBox.style.top = (rect.bottom + 10) + 'px';
            }
        });
    });

    // 3. Cerrar al hacer clic en cualquier otro lado
    document.addEventListener('click', () => {
        floatBox.style.display = 'none';
    });
});
</script>
@endpush
@endsection