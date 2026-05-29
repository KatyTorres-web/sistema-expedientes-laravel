@extends('layouts.principal')

@section('content')
<div class="container-fluid p-0">
    <!-- BOTONES SUPERIORES -->
    <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex gap-2">
                @if(auth()->user()->rol_id == 1)
                    <button class="btn btn-primary-custom shadow-sm text-white" type="button" id="btnNuevoExpediente">
                        NUEVO EXPEDIENTE
                    </button>
                @endif
                <button class="btn btn-outline-secondary-custom shadow-sm" type="button" id="btnMostrarFiltros">
                    MOSTRAR FILTROS
                </button>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="text-secondary fw-bold small">VIENDO EXPEDIENTES DEL:</span>
                <span class="badge bg-danger shadow-sm p-2 fs-6">
                    {{ request('anio', date('Y')) }}
                </span>
            </div>
            @if(auth()->user()->rol_id == 1)
                <a href="{{ route('expedientes.export', request()->all()) }}" class="btn btn-export-excel shadow-sm text-decoration-none">
                    <i class="bi bi-file-earmark-excel me-1"></i> EXPORTAR EXCEL
                </a>
            @endif
        </div>

    <!-- CUADRO DE REGISTRO (Colapsable) -->
    <div class="collapse mb-4 {{ (session('success') || session('error_modal') || request('registro')) ? 'show' : '' }}" id="createExpedienteForm">
        <div class="card border-blue-custom shadow-sm rounded-4">
            <div class="card-body p-4">
                <form action="{{ route('expedientes.store') }}" method="POST" id="formRegistro">
                    @csrf
                    <div class="row g-4 align-items-center">
                        <!-- Columna Izquierda -->
                        <div class="col-md-6">
                            <div class="row mb-2 align-items-center">
                                <label class="col-sm-4 label-small text-end fw-bold">ID EXPEDIENTE:</label>
                                <div class="col-sm-8">
                                    <input type="text" id="inputProximoId" class="form-control form-thin bg-white border-0 fw-bold text-primary shadow-sm" placeholder="GENERANDO..." readonly>
                                </div>
                            </div>
                            <div class="row mb-2 align-items-center">
                                <label class="col-sm-4 label-small text-end fw-bold">FECHA RECEPCIÓN:</label>
                                <div class="col-sm-8">
                                    <input type="date" name="fecha_recepcion" id="reg_fecha_recepcion" class="form-control form-thin bg-light border-0" value="{{ date('Y-m-d') }}" min="2025-01-01" required>
                                </div>
                            </div>
                            <div class="row mb-2 align-items-center">
                                <label class="col-sm-4 label-small text-end fw-bold">TIPO DE PRESENTANTE:</label>
                                <div class="col-sm-8">
                                    <select name="tipo_presentante" id="reg_tipo_presentante" class="form-select form-thin bg-light border-0" required>
                                        <option value="NATURAL" selected>NATURAL</option>
                                        <option value="JURIDICA">JURÍDICA</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-2 align-items-center">
                                <label class="col-sm-4 label-small text-end fw-bold">DESCRIPCIÓN DEL DOCUMENTO:</label>
                                <div class="col-sm-8" style="position: relative;">
                                    <input type="text" name="descripcion" id="reg_descripcion" class="form-control form-thin bg-light border-0" required placeholder=" ">
                                    
                                    <div id="ia-suggestion-wrapper" class="mt-1 p-2 border rounded-3 bg-white shadow-lg" 
                                        style="display: none; position: absolute; z-index: 1000; width: 100%; left: 0; top: 100%; max-height: 200px; overflow-y: auto;">
                                        <small class="text-muted d-block mb-2 px-2" style="font-size: 0.65rem; font-weight: 800; border-bottom: 1px solid #eee; padding-bottom: 5px;">
                                            <i class="bi bi-robot text-info"></i> SUGERENCIAS
                                        </small>
                                        <div id="ia-badges-container" class="d-flex flex-column gap-1">
                                            <!-- Aquí se cargarán las categorías -->
                                        </div>
                                        <input type="hidden" name="categoria_sugerida" id="input_categoria_sugerida">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Columna Derecha -->
                        <div class="col-md-6">
                            <div class="row mb-2 align-items-center">
                                <label class="col-sm-5 label-small text-end fw-bold">N° EXPEDIENTE:</label>
                                <div class="col-sm-7">
                                    <input type="text" name="num_expediente" id="num_expediente_focus" class="form-control form-thin bg-light border-0 fw-bold" required placeholder=" ">
                                </div>
                            </div>
                            <div class="row mb-2 align-items-center">
                                <label class="col-sm-5 label-small text-end fw-bold">FECHA REGISTRO:</label>
                                <div class="col-sm-7">
                                    <input type="text" class="form-control form-thin bg-light border-0" value="{{ date('d/m/Y') }}" disabled>
                                </div>
                            </div>
                            <div class="row mb-2 align-items-center">
                                <label class="col-sm-5 label-small text-end fw-bold">NOMBRE / RAZÓN SOCIAL:</label>
                                <div class="col-sm-7">
                                    <input type="text" name="apellidos_y_nombres_razon" class="form-control form-thin bg-light border-0" required placeholder=" ">
                                </div>
                            </div>
                            <div class="row mb-2 align-items-center">
                                <label class="col-sm-5 label-small text-end fw-bold">N° FOLIOS:</label>
                                <div class="col-sm-7">
                                    <input type="number" name="folios_actual" class="form-control form-thin bg-light border-0" required min="1" oninput="this.value = Math.abs(this.value)">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-end mt-3">
                        <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm fw-bold">REGISTRAR EXPEDIENTE</button>
                        <button type="button" class="btn btn-secondary btn-sm px-4 ms-2 shadow-sm" id="btnCerrarRegistro">CANCELAR</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- SECCIÓN DE FILTROS (Colapsable) -->
    <div class="collapse mb-4 {{ (request()->has('mostrar_filtros') || request('filtrado') == '1') ? 'show' : '' }}" id="filterSection">
        <div class="card card-filter border-pink shadow-sm">
            <div class="card-body p-4">
                <form id="formFiltros" action="{{ route('expedientes.index') }}" method="GET">
                    <input type="hidden" name="filtrado" value="1">
                    <div class="row g-3">
                        <!-- Año -->
                        <div class="col-md-2">
                            <label class="form-label fw-bold small">AÑO:</label>
                            <select name="anio" id="selectAnio" class="form-select form-select-sm">
                                @foreach($anios as $anio)
                                    <option value="{{ $anio }}" {{ request('anio', date('Y')) == $anio ? 'selected' : '' }}>{{ $anio }}</option>
                                @endforeach
                            </select>
                        </div>
                        <!-- Búsqueda Nombre -->
                        <div class="col-md-5">
                            <label class="form-label fw-bold small">NOMBRE / RAZÓN SOCIAL:</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                <input type="text" name="buscar" class="form-control form-thin" value="{{ request('buscar') }}" placeholder="Buscar...">
                            </div>
                        </div>
                        <!-- Búsqueda N° Exp -->
                        <div class="col-md-5">
                            <label class="form-label fw-bold small">N° EXPEDIENTE:</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                <input type="text" name="num_exp" class="form-control form-thin" value="{{ request('num_exp') }}" placeholder="">
                            </div>
                        </div>
                        <!-- Fechas -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">FECHA RECEPCIÓN (RANGO):</label>
                            <div class="d-flex gap-2">
                                <input type="date" name="fecha_desde" id="fecha_desde" class="form-control form-control-sm" value="{{ request('fecha_desde') }}">
                                <input type="date" name="fecha_hasta" id="fecha_hasta" class="form-control form-control-sm" value="{{ request('fecha_hasta') }}">
                            </div>
                        </div>
                        <!-- Estado -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">ESTADO ACTUAL:</label>
                            <select name="id_estado" class="form-select form-select-sm">
                                <option value="">TODOS LOS ESTADOS</option>
                                @foreach($estados as $est)
                                    <option value="{{ $est->id_estado_expediente }}" {{ request('id_estado') == $est->id_estado_expediente ? 'selected' : '' }}>{{ $est->nombre_estado }}</option>
                                @endforeach
                            </select>
                        </div>
                        <!-- Área -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">ÁREA ACTUAL:</label>
                            <select name="id_subdivision" class="form-select form-select-sm">
                                <option value="">TODAS LAS ÁREAS</option>
                                @foreach($subdivisiones->where('estado_subdiv_area', 1) as $sub)
                                    <option value="{{ $sub->id_subdivision }}" {{ request('id_subdivision') == $sub->id_subdivision ? 'selected' : '' }}>
                                        {{ $sub->nombre_subdivision }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="text-end mt-3">
                            <button type="button" id="btnSubmitFiltros" class="btn btn-primary btn-sm px-4 shadow-sm fw-bold">FILTRAR</button>
                            <button type="button" class="btn btn-secondary btn-sm px-4 ms-2 shadow-sm" id="btnCerrarFiltros">CERRAR</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- TABLA-->
    @include('expedientes.partials.tabla')
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. SELECTORES DE ELEMENTOS
    const elRegistro = document.getElementById('createExpedienteForm');
    const elFiltros = document.getElementById('filterSection');
    const formRegistro = document.getElementById('formRegistro');
    const inputFocus = document.getElementById('num_expediente_focus');
    const regFecha = document.getElementById('reg_fecha_recepcion');
    const inputDisplayId = document.getElementById('inputProximoId');
    
    const btnNuevo = document.getElementById('btnNuevoExpediente');
    const btnFiltros = document.getElementById('btnMostrarFiltros');
    const btnCerrarReg = document.getElementById('btnCerrarRegistro');
    const btnCerrarFil = document.getElementById('btnCerrarFiltros');
    const btnSubmitFiltros = document.getElementById('btnSubmitFiltros');

    const inputNumExp = document.getElementById('num_expediente_focus');
    if (inputNumExp) {
        inputNumExp.addEventListener('input', function() {
            // Reemplaza cualquier espacio en blanco por nada inmediatamente
            this.value = this.value.replace(/\s+/g, '');
        });
    }
    // --- FUNCIÓN PARA ADVERTENCIAS DISCRETAS (TIPO BANNER TEMPORAL) ---
    function mostrarAvisoDiscreto(mensaje) {
        let container = document.getElementById('aviso-discreto-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'aviso-discreto-container';
            container.style.cssText = 'position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 9999; width: auto; min-width: 300px;';
            document.body.appendChild(container);
        }

        const aviso = document.createElement('div');
        aviso.className = 'alert alert-warning shadow-sm border-0 py-2 px-4 mb-2 d-flex align-items-center justify-content-between animate__animated animate__fadeInDown';
        aviso.style.cssText = 'background-color: #fff3cd; color: #856404; border-radius: 12px; font-size: 0.9rem; font-weight: 600; border-left: 5px solid #ffc107 !important;';
        aviso.innerHTML = `<span><i class="bi bi-exclamation-circle-fill me-2"></i> ${mensaje}</span>`;
        container.appendChild(aviso);

        // Desvanecer y eliminar después de 3 segundos
        setTimeout(() => {
            aviso.style.opacity = '0';
            aviso.style.transition = 'opacity 0.5s ease';
            setTimeout(() => aviso.remove(), 500);
        }, 3000);
    }

    // --- LÓGICA DE NAVEGACIÓN CON ENTER ---
    if (formRegistro) {
        const ordenCampos = [
            'num_expediente',
            'apellidos_y_nombres_razon',
            'descripcion',
            'folios_actual'
        ];

        formRegistro.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                const nombreActual = e.target.getAttribute('name');
                const indiceActual = ordenCampos.indexOf(nombreActual);

                // Si el campo está en nuestra lista y NO es el último (folios_actual)
                if (indiceActual !== -1 && indiceActual < ordenCampos.length - 1) {
                    e.preventDefault(); // Evita que se registre el formulario
                    
                    // Buscamos el siguiente campo por su atributo name
                    const siguienteCampo = formRegistro.querySelector(`[name="${ordenCampos[indiceActual + 1]}"]`);
                    if (siguienteCampo) {
                        siguienteCampo.focus();
                    }
                }
                // Si es 'folios_actual', no hacemos preventDefault y el Enter registrará el formulario.
            }
        });
    }
    // 2. DETECCIÓN DE ESTADO (URL y Sesión)
    const params = new URLSearchParams(window.location.search);
    const tieneFiltrosActivos = params.has('filtrado') || params.has('mostrar_filtros') || params.has('buscar') || params.has('num_exp');
    const tieneRegistroActivo = params.has('registro') || @json(session('error_modal') || session('registrado_ok') ? true : false);

    // 3. FUNCIONES DE APOYO (No alteradas)
    function setFocusExpediente() {
        setTimeout(() => { if(inputFocus) inputFocus.focus(); }, 400);
    }

    function cargarProximoId() {
        if (!regFecha || !inputDisplayId) return;
        const fecha = regFecha.value;
        if (!fecha) return;

        fetch(`{{ route('expedientes.proximoId') }}?fecha=${fecha}`)
            .then(res => res.json())
            .then(data => { 
                if(inputDisplayId) inputDisplayId.value = data.id; 
            })
            .catch(err => {
                console.error("Error ID:", err);
                if(inputDisplayId) inputDisplayId.value = "ERROR";
            });
    }

    // 4. CARGA INICIAL
    cargarProximoId();

    if(tieneRegistroActivo && elRegistro){
        cargarProximoId();
        setFocusExpediente();
    }

    // 5. BOTÓN NUEVO (Apertura fluida con aviso discreto)
    if (btnNuevo) {
        btnNuevo.addEventListener('click', () => {
            if (elFiltros && elFiltros.classList.contains('show')) {
                mostrarAvisoDiscreto("CIERRE EL PANEL DE FILTROS ANTES DE REGISTRAR.");
            } else if (elRegistro) {
                bootstrap.Collapse.getOrCreateInstance(elRegistro).show();
                cargarProximoId();
                setFocusExpediente();
            }
        });
    }

    // 6. BOTÓN MOSTRAR FILTROS (Apertura fluida con aviso discreto)
    if (btnFiltros) {
        btnFiltros.addEventListener('click', () => {
            if (elRegistro && elRegistro.classList.contains('show')) {
                mostrarAvisoDiscreto("CIERRE EL PANEL DE REGISTRO ANTES DE FILTRAR.");
            } else if (elFiltros) {
                bootstrap.Collapse.getOrCreateInstance(elFiltros).show();
            }
        });
    }

    // 7. ACTUALIZACIÓN ID (Con aviso discreto para error de año)
    if(regFecha) {
        regFecha.addEventListener('change', function() {
            const year = this.value.split('-')[0];
            if (year && year.length > 4) {
                mostrarAvisoDiscreto("EL AÑO INGRESADO NO ES VÁLIDO.");
                this.value = "{{ date('Y-m-d') }}";
            }
            cargarProximoId();
        });
    }

    // 8. CIERRE DE REGISTRO
    if (btnCerrarReg) {
        btnCerrarReg.addEventListener('click', () => {
            let formSucio = false;
            const inputs = formRegistro.querySelectorAll('input[name="num_expediente"], input[name="descripcion"], input[name="apellidos_y_nombres_razon"]');
            inputs.forEach(i => { if(i.value.trim() !== "") formSucio = true; });

            const realizarCierre = () => {
                if (tieneRegistroActivo) {
                    window.location.href = window.location.pathname;
                } else {
                    bootstrap.Collapse.getOrCreateInstance(elRegistro).hide();
                    formRegistro.reset();
                    cargarProximoId();
                }
            };

            if (formSucio) {
                showModalConfirm("¿ESTÁ SEGURO DE CERRAR? SE PERDERÁN LOS DATOS INGRESADOS.", realizarCierre);
            } else {
                realizarCierre();
            }
        });
    }

    // 9. CIERRE DE FILTROS
    if (btnCerrarFil) {
        btnCerrarFil.addEventListener('click', () => {
            if (tieneFiltrosActivos) {
                window.location.href = window.location.pathname;
            } else {
                bootstrap.Collapse.getOrCreateInstance(elFiltros).hide();
            }
        });
    }

    // 10. VALIDACIÓN DE FILTROS (Avisos discretos)
    if(btnSubmitFiltros){
        btnSubmitFiltros.addEventListener('click', () => {
            const fDesde = document.getElementById('fecha_desde');
            const fHasta = document.getElementById('fecha_hasta');
            const selAnio = document.getElementById('selectAnio');

            if (fDesde.value || fHasta.value) {
                if (!fDesde.value || !fHasta.value) {
                    mostrarAvisoDiscreto("DEBE COMPLETAR AMBAS FECHAS.");
                    return;
                }
                if (fDesde.value.split('-')[0] !== selAnio.value || fHasta.value.split('-')[0] !== selAnio.value) {
                    mostrarAvisoDiscreto("LAS FECHAS DEBEN SER DEL AÑO " + selAnio.value);
                    return;
                }
                if (fDesde.value > fHasta.value) {
                    mostrarAvisoDiscreto("RANGO DE FECHAS INVÁLIDO.");
                    return;
                }
            }
            document.getElementById('formFiltros').submit();
        });
    }

    const descInput = document.getElementById('reg_descripcion');
const iaWrapper = document.getElementById('ia-suggestion-wrapper');
const badgesContainer = document.getElementById('ia-badges-container');

let debounceTimer;
let currentSuggestions = [];
let selectedSuggestionIndex = -1;

// Función para ocultar y limpiar el estado
function hideSugerencias() {
    if (iaWrapper) {
        iaWrapper.style.display = 'none';
        selectedSuggestionIndex = -1;
    }
}

// Función para resaltar visualmente la opción activa (Google Style)
function updateActiveSuggestion() {
    const items = badgesContainer.querySelectorAll('.suggestion-item');
    items.forEach((item, index) => {
        if (index === selectedSuggestionIndex) {
            // Estilo seleccionado: Fondo azul de tu proyecto y texto blanco
            item.style.backgroundColor = '#4da3ff'; 
            item.style.color = '#ffffff';
            item.style.borderColor = '#4da3ff';
            // Asegura que la opción sea visible si hay scroll
            item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        } else {
            // Estilo normal
            item.style.backgroundColor = '#f8f9fa';
            item.style.color = '#333';
            item.style.borderColor = '#dee2e6';
        }
    });
}

// Acción de seleccionar la palabra
function seleccionarOpcion(texto) {
    descInput.value = texto;
    hideSugerencias();
    descInput.focus();
    
    // Disparar análisis de categoría y recomendación de analista
    analizarYRecomendarInicialmente(texto);
}

// Función para analizar descripción y recomendar analista
async function analizarYRecomendarInicialmente(descripcion) {
    try {
        // 1. Analizar descripción con IA para obtener categoría
        const respAnalisis = await fetch('{{ route("expedientes.ia.analizar") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ descripcion: descripcion })
        });

        const dataAnalisis = await respAnalisis.json();

        if (dataAnalisis.suficientes_datos && dataAnalisis.categoria) {
            // 2. Recomendar analista basado en categoría
            const respRecom = await fetch('{{ route("expedientes.recomendar.analista") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ categoria: dataAnalisis.categoria })
            });

            const dataRecom = await respRecom.json();

            // Mostrar recomendación en consola (puedes mejorar esto con una UI visual)
            if (dataRecom.recomendado) {
                console.log('✅ RECOMENDADO:', dataRecom.recomendado.nombre, '- Carga:', dataRecom.recomendado.carga_actual, '- Experiencia:', dataRecom.recomendado.experiencia);
            }
        }
    } catch (error) {
        console.error("Error al analizar y recomendar:", error);
    }
}

// Construye la lista de sugerencias en el HTML
function mostrarSugerenciasHistorial(lista) {
    badgesContainer.innerHTML = '';
    currentSuggestions = lista;

    if (lista.length === 0) {
        hideSugerencias();
        return;
    }

    iaWrapper.style.display = 'block';
    lista.forEach((texto, index) => {
        const div = document.createElement('div');
        // Usamos clases de tu proyecto y estilos dinámicos
        div.className = 'suggestion-item p-2 mb-1 rounded-pill animate__animated animate__fadeIn';
        div.style.cssText = 'cursor: pointer; transition: 0.2s; border: 1px solid #dee2e6; font-size: 0.8rem; padding-left: 15px !important;';
        div.innerHTML = `<i class="bi bi-clock-history me-2"></i> ${texto}`;

        // Selección por clic
        div.onclick = () => seleccionarOpcion(texto);

        // Sincronizar el mouse con el teclado
        div.onmouseenter = () => {
            selectedSuggestionIndex = index;
            updateActiveSuggestion();
        };

        badgesContainer.appendChild(div);
    });

    selectedSuggestionIndex = 0; // Resaltar el primero por defecto
    updateActiveSuggestion();
}

// Evento al escribir en el campo
descInput.addEventListener('input', function() {
    clearTimeout(debounceTimer);
    const busqueda = this.value.trim();

    if (busqueda.length < 3) {
        hideSugerencias();
        return;
    }

    debounceTimer = setTimeout(async () => {
        try {
            const response = await fetch(`{{ route('expedientes.sugerir.descripciones') }}?q=${encodeURIComponent(busqueda)}`);
            const result = await response.json();

            if (result.ready && result.data.length > 0) {
                mostrarSugerenciasHistorial(result.data);
            } else {
                hideSugerencias();
            }
        } catch (error) {
            console.error("Error historial:", error);
        }
    }, 300);
});

// Lógica de navegación por teclado (Google Style)
descInput.addEventListener('keydown', function(e) {
    // Si el menú no está visible, no hacemos nada especial
    if (iaWrapper.style.display !== 'block' || currentSuggestions.length === 0) return;

    if (e.key === 'ArrowDown') {
        e.preventDefault(); // Evita mover el cursor del texto
        selectedSuggestionIndex = (selectedSuggestionIndex + 1) % currentSuggestions.length;
        updateActiveSuggestion();
    } 
    else if (e.key === 'ArrowUp') {
        e.preventDefault();
        selectedSuggestionIndex = (selectedSuggestionIndex - 1 + currentSuggestions.length) % currentSuggestions.length;
        updateActiveSuggestion();
    } 
    else if (e.key === 'Enter') {
        if (selectedSuggestionIndex >= 0) {
            e.preventDefault(); // EVITA que el formulario se envíe al elegir
            seleccionarOpcion(currentSuggestions[selectedSuggestionIndex]);
        }
    } 
    else if (e.key === 'Escape') {
        hideSugerencias();
    }
    else if (e.key === 'Tab') {
        // Al presionar Tab también seleccionamos la actual
        if (selectedSuggestionIndex >= 0) {
            seleccionarOpcion(currentSuggestions[selectedSuggestionIndex]);
        }
    }
});

// Cerrar las sugerencias si el usuario hace clic en cualquier otro lado
document.addEventListener('mousedown', (e) => {
    if (!descInput.contains(e.target) && !iaWrapper.contains(e.target)) {
        hideSugerencias();
    }
});

});
</script>
@endpush
@endsection