@extends('layouts.principal')

@section('content')
<div class="dashboard-full animate__animated animate__fadeIn">
    
    <!-- 1. CABECERA: BIENVENIDA, FILTRO Y ALERTA DE DEMANDA -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-1" style="color: #333;">Panel del Administrador</h2>
            <p class="text-secondary fs-6 mb-0">Supervisión general de gestión — Gestión {{ $anioFiltro }}</p>
        </div>

        <button onclick="location.reload()" class="btn btn-light border-pink rounded-pill px-3 fw-bold small shadow-sm text-secondary me-2">
                <i class="bi bi-arrow-clockwise me-1"></i> 
            </button>

        <div class="d-flex align-items-center gap-2">
            <!-- SELECTOR DE AÑO AMIGABLE -->
            <div class="dropdown">
                <button class="btn bg-white px-3 py-2 rounded-pill shadow-sm border border-pink dropdown-toggle fw-bold text-primary small text-uppercase" 
                        type="button" id="dropdownAnio" data-bs-toggle="dropdown" aria-expanded="false" style="min-width: 140px;">
                    <i class="bi bi-calendar-event me-2"></i> Año {{ $anioFiltro }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4" aria-labelledby="dropdownAnio">
                    @foreach($anios as $anio)
                        <li>
                            <a class="dropdown-item py-2 {{ $anio == $anioFiltro ? 'active bg-primary text-white' : '' }}" 
                                href="#" onclick="cambiarAnio({{ $anio }})">
                                <i class="bi bi-chevron-right me-2 small"></i> Gestión {{ $anio }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <form id="formCambiarAnio" action="{{ route('dashboard.admin') }}" method="GET" style="display:none;">
                <input type="hidden" name="anio" id="inputAnioHidden">
            </form>
        </div>
    </div>

    <!-- 2. CÁPSULA TOTAL GENERAL -->
    <div class="d-flex justify-content-center mb-4">
        <div class="bg-white px-4 py-1 rounded-pill shadow-sm border border-pink">
            <span class="text-secondary small fw-bold text-uppercase">
                <i class="bi bi-folder-fill text-primary me-1"></i> Total Ingresados {{ $anioFiltro }}: 
                <span class="text-dark fs-5 ms-1">{{ $stats->total ?? 0 }}</span>
            </span>
        </div>
    </div>

    <!-- 3. KPIs DE ESTADO Y TIEMPO (Criterio 1, 2 y 4) -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card-stat green shadow-sm h-100 py-3">
                <div class="stat-title small">Atendidos</div>
                <div class="stat-number fs-2">{{ $stats->atendidos ?? 0 }}</div>
                <div class="badge-status badge-atendido py-0 px-2 mt-1" style="font-size: 0.65rem;">
                    {{ $stats->total > 0 ? round(($stats->atendidos / $stats->total) * 100, 1) : 0 }}% ÉXITO
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card-stat blue shadow-sm h-100 py-3">
                <div class="stat-title small">En Proceso</div>
                <div class="stat-number fs-2">{{ $stats->derivados ?? 0 }}</div>
                <div class="stat-label italic small">Derivados</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card-stat yellow shadow-sm h-100 py-3" style="border-color: #ffe082;">
                <div class="stat-title small" style="color:#856404;">Registrados</div>
                <div class="stat-number fs-2" style="color:#856404;">{{ $stats->registrados ?? 0 }}</div>
                <div class="stat-label italic small">Sin asignar</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card-stat purple shadow-sm h-100 py-3">
                <div class="stat-title small">Devueltos</div>
                <div class="stat-number fs-2">{{ $stats->devueltos ?? 0 }}</div>
                <div class="stat-label italic small">Re-evaluación</div>
            </div>
        </div>
        <div class="col-12 col-md-8 col-lg-4">
            <div class="card border-pink shadow-sm rounded-4 bg-white p-3 h-100 d-flex flex-column justify-content-center border-start border-4 border-primary">
                <div class="text-secondary small fw-bold text-uppercase mb-1">Tiempo Promedio de Resolución</div>
                <div class="d-flex align-items-baseline gap-2">
                    <span class="display-6 fw-bold text-primary">{{ number_format($tiempoPromedio, 1) }}</span>
                    <span class="text-secondary fw-bold">DÍAS / EXP.</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. GRÁFICOS DINÁMICOS -->
    <div class="row g-4 mb-4">
        <div class="col-md-12 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                <h6 class="text-secondary fw-bold mb-4 border-bottom pb-2">DISTRIBUCIÓN DE CARGA</h6>
                <div class="d-flex align-items-center gap-4">
                    <div style="width: 130px; height: 130px; flex-shrink: 0;"><canvas id="donutChart"></canvas></div>
                    <div class="flex-grow-1 small">
                        <div class="d-flex justify-content-between mb-1"><span>Atendidos</span><span class="fw-bold text-success">{{ $stats->atendidos }}</span></div>
                        <div class="d-flex justify-content-between mb-1"><span>Derivados</span><span class="fw-bold text-primary">{{ $stats->derivados }}</span></div>
                        <div class="d-flex justify-content-between mb-1"><span>Pendientes</span><span class="fw-bold text-warning">{{ $stats->registrados }}</span></div>
                        <div class="d-flex justify-content-between"><span>Devueltos</span><span class="fw-bold text-danger">{{ $stats->devueltos }}</span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12 col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                <h6 class="text-secondary fw-bold mb-4 border-bottom pb-2">FLUJO MENSUAL {{ $anioFiltro }}</h6>
                <div style="height: 150px;"><canvas id="barChartMensual"></canvas></div>
            </div>
        </div>
    </div>

    <!-- 5. MONITOR DE CARGA E INTELIGENCIA (Criterio 3 y 6) -->
    <div class="row g-4 mb-4">
        <div class="col-md-12 col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                <h6 class="text-secondary fw-bold mb-3 border-bottom pb-2">
                    <i class="bi bi-robot me-2"></i>MONITOR DE SATURACIÓN Y REDISTRIBUCIÓN IA
                </h6>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr class="text-uppercase" style="font-size: 0.65rem;">
                                <th>Analista / Área</th>
                                <th class="text-center">Carga Actual</th>
                                <th class="text-center">Especialidad</th>
                                <th>Sugerencia de Descarga</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($analistas as $a)
                            <tr class="{{ $a->saturado ? 'table-danger' : '' }}">
                                <td>
                                    <div class="fw-bold">{{ $a->nombre }} {{ $a->apellido }}</div>
                                    <div class="text-muted" style="font-size: 0.7rem;">{{ $a->subdivision->nombre_subdivision }}</div>
                                </td>
                                <td class="text-center">
                                    <span class="fw-bold fs-6 {{ $a->saturado ? 'text-danger' : 'text-primary' }}">{{ $a->pendientes_count }}</span>
                                    <div class="text-muted" style="font-size: 0.6rem;">PENDIENTES</div>
                                </td>
                                <td class="text-center">
                                    <span class="badge border text-dark fw-bold text-uppercase" style="font-size: 0.6rem;">{{ $a->especialidad }}</span>
                                </td>
                                <td>
                                    @if($a->saturado)
                                        @if($a->mejor_apoyo)
                                            <div class="p-2 border border-danger rounded bg-white shadow-sm">
                                                <small class="text-danger fw-bold d-block"><i class="bi bi-person-check-fill"></i> REASIGNAR A:</small>
                                                <span class="fw-bold text-dark" style="font-size: 0.8rem;">{{ $a->mejor_apoyo->nombre }} {{ $a->mejor_apoyo->apellido }}</span>
                                                <small class="text-muted d-block" style="font-size: 0.65rem;">Misma especialidad / Carga baja ({{ $a->mejor_apoyo->pendientes_count }} exp.)</small>
                                            </div>
                                        @else
                                            <small class="text-muted italic">Sin analistas aptos libres.</small>
                                        @endif
                                    @else
                                        <span class="badge rounded-pill bg-success px-3">DISPONIBLE</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-12 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                <h6 class="text-secondary fw-bold mb-3 border-bottom pb-2">TEMAS RECURRENTES</h6>
                @foreach($tiposFrecuentes as $tipo)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1 small fw-bold">
                            <span class="text-truncate" style="max-width: 80%;">{{ $tipo->nombre }}</span>
                            <span>{{ $tipo->cantidad }}</span>
                        </div>
                        <div class="progress" style="height: 6px; border-radius: 10px;">
                            <div class="progress-bar" style="width: {{ ($tipo->cantidad / max($stats->total, 1)) * 100 }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- 6. ALERTAS DE NOTIFICACIÓN INTELIGENTE -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <h6 class="text-secondary fw-bold mb-3"><i class="bi bi-bell-fill me-2"></i>ASISTENTE DE GESTIÓN</h6>
        <div class="row g-3">
            <!-- ALERTA IA: PREDICCIÓN DE DEMANDA (Criterio 5) -->
            @if($alertaDemanda)
            <div class="alert alert-warning shadow-sm border-0 mb-4 animate__animated animate__pulse animate__infinite" style="border-radius: 15px; border-left: 6px solid #ffc107 !important; background-color: #fff9e6;">
                <div class="d-flex align-items-center gap-3 py-1">
                    <i class="bi bi-graph-up-arrow fs-3 text-warning"></i>
                    <div>
                        <strong class="text-dark">ALERTA: Pico de demanda detectado</strong>
                        <p class="mb-0 small text-secondary">El volumen actual de este mes supera el promedio histórico en un <strong>{{ $porcentajeSubida }}%</strong>. Considere reforzar áreas críticas.</p>
                    </div>
                </div>
            </div>
            @endif
            @if($sinDerivarCriticos > 0)
            <div class="col-md-6">
                <div class="alert alert-info border-0 shadow-sm mb-0 rounded-4 d-flex align-items-center gap-3 py-3">
                    <i class="bi bi-clock-history fs-3"></i>
                    <div>
                        <strong class="d-block">{{ $sinDerivarCriticos }} Expedientes en espera</strong>
                        <span class="small">Llevan más de 24 horas en estado "Registrado" sin ser asignados.</span>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function cambiarAnio(anio) {
    document.getElementById('inputAnioHidden').value = anio;
    document.getElementById('formCambiarAnio').submit();
}

document.addEventListener('DOMContentLoaded', function() {
    // Gráfico Donut
    const ctxDonut = document.getElementById('donutChart');
    if (ctxDonut) {
        new Chart(ctxDonut, {
            type: 'doughnut',
            data: {
                // Sincronizamos etiquetas con los datos
                labels: ['Atendidos', 'Derivados', 'Pendientes', 'Devueltos'],
                datasets: [{
                    // Usamos ?? 0 para evitar que valores nulos rompan el gráfico
                    data: [
                        {{ $stats->atendidos ?? 0 }}, 
                        {{ $stats->derivados ?? 0 }}, 
                        {{ $stats->registrados ?? 0 }}, 
                        {{ $stats->devueltos ?? 0 }}
                    ],
                    backgroundColor: [
                        '#d4ffd4', // Verde (Atendidos)
                        '#4da3ff', // Azul (Derivados)
                        '#ffeb3b', // Amarillo más fuerte (Pendientes) para que se vea bien
                        '#f3e5f5'  // Morado (Devueltos)
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                cutout: '70%', // Aumentamos un poco el grosor del anillo
                plugins: { 
                    legend: { display: false } 
                } 
            }
        });
    }
    // Gráfico de Barras
    new Chart(document.getElementById('barChartMensual'), {
        type: 'bar',
        data: {
            labels: ['E','F','M','A','M','J','J','A','S','O','N','D'],
            datasets: [{
                label: 'Volumen',
                data: @json($volumenMensual),
                backgroundColor: '#4da3ff',
                borderRadius: 5,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { display: false }, x: { grid: { display: false } } },
            plugins: { legend: { display: false } }
        }
    });
});
</script>

<style>
    .card-stat.yellow { background-color: #fff9c4; border: 2px solid #ffe082; border-radius: 20px; text-align: center; transition: transform 0.3s ease; }
    .card-stat.yellow:hover { transform: translateY(-5px); }
    .border-pink { border: 1.5px solid #ffc5c5 !important; }
    .badge-atendido { background-color: #d4ffd4; color: #166534; border: 1px solid #166534; }
    .badge-derivado { background-color: #ffcccc; color: #a40000; border: 1px solid #ffb3b3; }
    .dropdown-toggle::after { color: #ff4d4d; }
    .dropdown-item.active { background-color: #ff4d4d !important; }
    .dropdown-item:hover { background-color: #fff0f0; color: #ff4d4d; }
</style>
@endsection