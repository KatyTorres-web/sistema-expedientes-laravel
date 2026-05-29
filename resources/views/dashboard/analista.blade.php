@extends('layouts.principal')

@section('content')
<div class="dashboard-full animate__animated animate__fadeIn">
    
    <!-- CABECERA: BIENVENIDA Y RENDIMIENTO HOY -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-1" style="color: #333;">¡Bienvenido {{ auth()->user()->nombre }}!</h2>
            <p class="text-secondary fs-6 mb-0">Resumen personal de gestión — Periodo {{ date('Y') }}</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="bg-white px-4 py-2 rounded-pill shadow-sm border border-pink d-inline-flex align-items-center gap-3">
                <div class="text-start">
                    <small class="text-muted fw-bold d-block text-uppercase" style="font-size: 0.65rem; line-height: 1;">Rendimiento Hoy</small>
                    <span class="fs-5 fw-bold {{ $atendidosHoy >= $promedioDiario ? 'text-success' : 'text-primary' }}">
                        {{ $atendidosHoy }} <small class="text-muted fw-normal">/ {{ $promedioDiario }} prom.</small>
                    </span>
                </div>
                <i class="bi {{ $atendidosHoy >= $promedioDiario ? 'bi-graph-up-arrow text-success' : 'bi-dash-circle text-muted' }} fs-4"></i>
            </div>
        </div>
    </div>

    <!-- CÁPSULA TOTAL (Estilo Admin) -->
    <div class="d-flex justify-content-center mb-4">
        <div class="bg-white px-4 py-1 rounded-pill shadow-sm border border-pink">
            <span class="text-secondary small fw-bold text-uppercase">
                <i class="bi bi-person-badge-fill text-primary me-1"></i> Total Expedientes Asignados: 
                <span class="text-dark fs-5 ms-1">{{ $derivados }}</span>
            </span>
        </div>
    </div>

    <!-- KPIs PRINCIPALES (4 Bloques Consistentes) -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card-stat blue shadow-sm h-100 py-3">
                <div class="stat-title small">Pendientes</div>
                <div class="stat-number fs-2 text-primary">{{ $pendientes }}</div>
                <div class="stat-label italic small">Por procesar</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card-stat green shadow-sm h-100 py-3">
                <div class="stat-title small">Atendidos</div>
                <div class="stat-number fs-2 text-success">{{ $atendidos }}</div>
                <div class="badge-status badge-atendido py-0 px-2 mt-1" style="font-size: 0.65rem;">
                    {{ $derivados > 0 ? round(($atendidos / $derivados) * 100, 1) : 0 }}% ATENDIDOS
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card-stat purple shadow-sm h-100 py-3">
                <div class="stat-title small">Gestionados</div>
                <div class="stat-number fs-2" style="color: #6f42c1;">{{ $derivados - $pendientes }}</div>
                <div class="stat-label italic small">Total expedientes resueltos</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card-stat red shadow-sm h-100 py-3" style="border-color: #ffc5c5;">
                <div class="stat-title small" style="color: #dc3545;">Devueltos</div>
                <div class="stat-number fs-2" style="color: #dc3545;">{{ $devueltos }}</div>
                <div class="stat-label italic small">Re-evaluación</div>
            </div>
        </div>
    </div>

    <!-- SECCIÓN GRÁFICOS DINÁMICOS -->
    <div class="row g-4 mb-4">
        <!-- Gráfico Distribución -->
        <div class="col-md-12 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                <h6 class="text-secondary fw-bold mb-4 border-bottom pb-2">DISTRIBUCIÓN DE MI CARGA</h6>
                <div class="d-flex align-items-center gap-4">
                    <div style="width: 130px; height: 130px; flex-shrink: 0;"><canvas id="estadoChart"></canvas></div>
                    <div class="flex-grow-1 small">
                        <div class="d-flex justify-content-between mb-2">
                            <span><i class="bi bi-circle-fill text-success me-2"></i>Atendidos</span>
                            <span class="fw-bold">{{ $atendidos }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span><i class="bi bi-circle-fill text-primary me-2"></i>Pendientes</span>
                            <span class="fw-bold">{{ $pendientes }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span><i class="bi bi-circle-fill text-danger me-2"></i>Devueltos</span>
                            <span class="fw-bold">{{ $devueltos }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráfico Comparativo Hoy vs Promedio -->
        <div class="col-md-12 col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                <h6 class="text-secondary fw-bold mb-4 border-bottom pb-2">COMPARATIVA: HOY vs PROMEDIO DIARIO</h6>
                <div style="height: 150px;">
                    <canvas id="comparativaChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- ÚLTIMOS 3 EXPEDIENTES DERIVADOS -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                    <h6 class="text-secondary fw-bold m-0"><i class="bi bi-clock-history me-2"></i>ÚLTIMOS 3 EXPEDIENTES DERIVADOS</h6>
                    <span class="badge bg-light text-secondary border">Recientes</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead>
                            <tr class="text-secondary text-uppercase" style="font-size: 0.7rem;">
                                <th>N° Expediente</th>
                                <th>Descripción</th>
                                <th>Derivado</th>
                                <th class="text-end">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ultimosDerivados as $exp)
                            @php
                                $userId = auth()->id();
                                $ultimaDerivGlobal = $exp->derivaciones->last();
                                
                                // ¿Es el analista el dueño actual del expediente?
                                $esDuenioActual = ($ultimaDerivGlobal->id_usuario == $userId && is_null($ultimaDerivGlobal->fecha_atencion));

                                if ($esDuenioActual) {
                                    // --- VISTA ACTIVA ---
                                    // Para el analista siempre es "DERIVADO" si lo tiene en su poder.
                                    $labelTabla = 'DERIVADO';
                                    $class = 'badge-derivado';
                                } else {
                                    // --- VISTA CONGELADA (HUELLA) ---
                                    // Buscamos lo ÚLTIMO que hizo este analista específicamente
                                    $ultimaAccionPropia = $exp->historialCambios
                                        ->where('id_usuario', $userId)
                                        ->whereIn('accion', ['ATENCIÓN', 'DEVOLUCIÓN'])
                                        ->last();

                                    if ($ultimaAccionPropia) {
                                        if ($ultimaAccionPropia->accion == 'ATENCIÓN') {
                                            $labelTabla = 'ATENDIDO'; // Cambiamos "ATENCIÓN" por "ATENDIDO"
                                            $class = 'badge-atendido';
                                        } else {
                                            $labelTabla = 'DEVUELTO'; // Cambiamos "DEVOLUCIÓN" por "DEVUELTO"
                                            $class = 'badge-devuelto';
                                        }
                                    } else {
                                        // Caso raro: Si el admin le quitó el expediente antes de que hiciera nada
                                        $labelTabla = 'TRANSFERIDO';
                                        $class = 'bg-secondary text-white';
                                    }
                                }
                            @endphp
                            <tr style="{{ !$esDuenioActual ? 'opacity: 0.8; background-color: #fcfcfc;' : '' }}">
                                <td class="fw-bold text-primary">{{ $exp->num_expediente }}</td>
                                <td>{{ Str::limit($exp->descripcion, 60) }}</td>
                                <td>{{ date('d/m/Y - H:i', strtotime($exp->mi_fecha_recepcion)) }}</td>
                                <td class="text-end">
                                    <span class="badge-status {{ $class }}" style="font-size: 0.7rem; padding: 4px 12px;">
                                        {{ $labelTabla }}
                                    </span>
                                    @if(!$esDuenioActual)
                                        <i class="bi bi-lock-fill text-muted ms-1" title="Resultado de su gestión (Congelado)"></i>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No hay expedientes derivados recientemente.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- FILA OPERATIVA: TABLA PENDIENTES -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                    <h6 class="text-secondary fw-bold m-0"><i class="bi bi-list-check me-2"></i>BANDEJA DE ATENCIÓN PRIORITARIA (PENDIENTES)</h6>
                    <span class="badge bg-light text-secondary border">Antigüedad máxima: 25 días</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead>
                            <tr class="text-secondary text-uppercase" style="font-size: 0.7rem;">
                                <th>Prioridad</th>
                                <th>N° Expediente</th>
                                <th>Descripción</th>
                                <th class="text-end">Días sin atención</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recientes as $exp)
                            <tr style="{{ $exp->dias_sin_atencion > 3 ? 'background-color: #fff5f5; border-left: 4px solid #dc3545;' : '' }}">
                                <td>
                                    @if($exp->dias_sin_atencion > 25)
                                        <span class="badge bg-danger animate__animated animate__pulse animate__infinite"><i class="bi bi-fire me-1"></i>URGENTE</span>
                                    @else
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">NORMAL</span>
                                    @endif
                                </td>
                                <td class="fw-bold text-primary">{{ $exp->num_expediente }}</td>
                                <td>
                                    {{-- Resumen de descripción --}}
                                    <span class="text-dark fw-medium">
                                        {{ Str::limit($exp->descripcion, 50) }}
                                    </span>
                                </td>
                                <td class="text-end fw-bold text-secondary">{{ $exp->dias_sin_atencion }} días</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No tienes expedientes pendientes de atención.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 text-center">
                    <a href="{{ route('expedientes.index') }}" class="btn btn-outline-primary btn-sm rounded-pill px-4 fw-bold shadow-sm">VER TODA MI BANDEJA ACTIVA ↗</a>
                </div>
            </div>
        </div>
    </div>

    <!-- ACCESOS RÁPIDOS INFERIORES -->
    <div class="mt-auto pt-4 border-top">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <h6 class="text-secondary fw-bold mb-0" style="letter-spacing: 1px;">GESTIÓN:</h6>
            <a href="{{ route('expedientes.index') }}" class="btn btn-quick-access btn-yellow thinner shadow-sm">
                <i class="bi bi-collection-play me-2"></i>Procesar expedientes
            </a>
            <a href="{{ route('perfil') }}" class="btn btn-quick-access bg-white border border-pink thinner shadow-sm text-secondary">
                <i class="bi bi-person-circle me-2"></i>Mi Perfil
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Gráfico de Estado (Donut)
    new Chart(document.getElementById('estadoChart'), {
        type: 'doughnut',
        data: {
            labels: ['Atendidos','Pendientes','Devueltos'],
            datasets: [{
                data: [{{ $atendidos }}, {{ $pendientes }}, {{ $devueltos }}],
                backgroundColor: ['#d4ffd4','#cce6ff','#ffd1d1'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '75%', plugins: { legend: { display: false } } }
    });

    // 2. Gráfico Comparativo (Barras)
    new Chart(document.getElementById('comparativaChart'), {
        type: 'bar',
        data: {
            labels: ['RENDIMIENTO DE HOY', 'PROMEDIO DIARIO'],
            datasets: [{
                data: [{{ $atendidosHoy }}, {{ $promedioDiario }}],
                backgroundColor: ['#28a745', '#4da3ff'],
                borderRadius: 8,
                barThickness: 50
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y', // Barras horizontales para mejor lectura
            scales: { x: { display: false }, y: { grid: { display: false }, ticks: { font: { weight: 'bold' } } } },
            plugins: { legend: { display: false } }
        }
    });
});
</script>

<style>
    .border-pink { border: 1.5px solid #ffc5c5 !important; }
    .badge-atendido { background-color: #d4ffd4; color: #166534; border: 1px solid #166534; }
</style>
@endsection