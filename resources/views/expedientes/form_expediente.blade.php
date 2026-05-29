@extends('layouts.principal')

@section('content')
<div class="container-fluid p-0">
    @php
        $isEdit = Route::currentRouteName() == 'expedientes.edit';
        $isDetail = !$isEdit;

        $rolUsuario = auth()->user()->rol_id;
        $estadoNom = strtoupper($expediente->estado->nombre_estado ?? '');
        $ultimaDeriv = $expediente->derivaciones->last();
        $esDueno = ($ultimaDeriv && $ultimaDeriv->id_usuario == auth()->id());

        // 1. Obtenemos la ÚLTIMA devolución registrada en el historial
        $ultimaDevolucion = $expediente->historialCambios()
                            ->where('accion', 'DEVOLUCIÓN')
                            ->latest('id_historial')
                            ->first();

        $rolQueDevolvio = $ultimaDevolucion?->usuario?->rol_id;
        
        // 2. Verificamos si esa última devolución fue específicamente una cancelación de atención
        $fueCanceladoPorAdmin = ($ultimaDevolucion && str_contains($ultimaDevolucion->campo_modificado, 'ATENCIÓN CANCELADA'));

        $labelEstado = $estadoNom;
        if ($estadoNom == 'DEVUELTO' && $fueCanceladoPorAdmin) {
            $labelEstado = 'DEVUELTO (REPROCESO)';
        }
        
        $registroAtencion = null;
        $atendidoPorAdmin = false;
        $atendidoPorAnalista = false;
        if ($estadoNom == 'ATENDIDO') {
            $registroAtencion = $expediente->historialCambios()
                ->where('accion', 'ATENCIÓN')
                ->latest('id_historial')
                ->first();
            if ($registroAtencion && $registroAtencion->usuario->rol_id == 2) {
                $atendidoPorAnalista = true;
            }
        }

        $rolAtencion = $registroAtencion?->usuario?->rol_id;
        
            if ($rolAtencion == 1) {
                $atendidoPorAdmin = true;
            } elseif ($rolAtencion == 2) {
                $atendidoPorAnalista = true;
            }

        $esDevolucionProcesable = false;
        if ($estadoNom == 'DEVUELTO') {
            if ($rolQueDevolvio == 2) {
                $esDevolucionProcesable = true; // Lo devolvió el analista
            } elseif ($fueCanceladoPorAdmin) {
                $esDevolucionProcesable = true; // El admin canceló y está listo para reasignar
            }
        }
        
        $puedeAdminActuar = ($rolUsuario == 1 && ($estadoNom == 'REGISTRADO' || $esDevolucionProcesable));

        $readonlyGeneral = ($isDetail || $rolUsuario == 2) ? 'readonly' : '';
        $disabledGeneral = ($isDetail || $rolUsuario == 2) ? 'disabled' : '';
        $readonlyObs = $isDetail ? 'readonly' : '';
        $docsEncontrados = null;
        if (str_contains($expediente->observaciones, '| DOC:')) {
            $partesDoc = explode('| DOC:', $expediente->observaciones);
            $docsEncontrados = trim($partesDoc[1] ?? '');
        }
    @endphp

    @if(isset($alertaAuditoria))
        <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-3 animate__animated animate__headShake d-flex align-items-center gap-3">
            <i class="bi bi-exclamation-octagon-fill fs-2"></i>
            <div>
                <strong class="d-block">ALERTA DEL ASISTENTE DE AUDITORÍA ML</strong>
                <span>{{ $alertaAuditoria }}</span>
            </div>
        </div>
    @endif

    <form action="{{ route('expedientes.update', $expediente->id_expediente) }}" method="POST" id="formExpediente">
        @csrf
        @method('PUT')
        
        @include('expedientes.partials.secciones', ['section' => 'encabezado'])

        <div class="card border-blue-custom rounded-4 shadow-sm mb-3">
            <div class="card-body p-4 {{ $isDetail ? '' : 'scroll-expediente' }}">
                @include('expedientes.partials.secciones', ['section' => 'secciones'])
                @include('expedientes.partials.botones', ['section' => 'form_expediente'])
            </div>
        </div>

        @if($isEdit)
            <!-- MODAL SELECCIONAR ANALISTA -->
            <div class="modal fade" id="modalSeleccionarAnalista" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content rounded-4 border-0 shadow-lg">
                        <div class="modal-header bg-primary text-white border-0">
                            <h5 class="modal-title fw-bold"><i class="bi bi-cpu-fill me-2"></i>RECOMENDACIÓN ML</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4 text-center">
                            <label class="small fw-bold text-secondary mb-2 text-uppercase">Analistas disponibles:</label>
                            <select name="id_analista" id="selectAnalista" class="form-select form-thin bg-light border-0 mb-3">
                                <option value="" selected disabled>-- Seleccione un analista --</option>
                                @foreach($analistas as $analista)
                                    <option value="{{ $analista->id }}" 
                                            data-area="{{ $analista->subdivision->nombre_subdivision }}"
                                            data-motivo="{{ $analista->motivo_ia }}"
                                            {{ $analista->id == $recomendadoId ? 'selected' : '' }}>
                                        {{ $analista->nombre }} {{ $analista->apellido }} 
                                        @if($analista->id == $recomendadoId) 🌟 [SUGERIDO] @endif
                                    </option>
                                @endforeach
                            </select>

                            <div id="ia-insight" class="p-3 rounded-3 border-info border bg-info-subtle shadow-sm text-start" style="display: none;">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-robot text-primary fs-3"></i>
                                    <div class="small">
                                        <strong class="text-primary d-block">Por qué este analista:</strong>
                                        <span id="ia-reason" class="text-dark fw-bold"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 d-flex gap-2">
                                <button type="button" class="btn btn-light w-100 fw-bold rounded-pill" data-bs-dismiss="modal">CANCELAR</button>
                                <button type="button" id="confirmarDerivar" class="btn btn-primary w-100 fw-bold rounded-pill shadow">CONFIRMAR</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- MODAL MOTIVO DEVOLUCIÓN -->
            <div class="modal fade" id="modalDevolverConfirm" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg rounded-4">
                        <div class="modal-header bg-danger text-white border-0">
                            <h5 class="modal-title fw-bold"><i class="bi bi-shield-exclamation me-2"></i>ASISTENTE DE RECHAZO ML</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="fw-bold small mb-1 text-uppercase text-secondary">Observación de la devolución:</label>
                                <textarea id="motivoDevolucion" class="form-control bg-light border-0" rows="3" placeholder="Ej: No adjuntó copia de DNI..."></textarea>
                            </div>

                            <div class="text-center mb-3">
                                <button type="button" id="btnClasificarRechazo" class="btn btn-dark btn-sm rounded-pill px-4 shadow-sm fw-bold">
                                    <i class="bi bi-magic me-1"></i> CLASIFICAR MOTIVO CON ML
                                </button>
                            </div>

                            <div id="contenedorSugerenciaIA" style="display: none;" class="animate__animated animate__fadeIn">
                                <div class="p-3 rounded-4 border-warning border bg-warning-subtle mb-3 shadow-sm text-center">
                                    <label class="fw-bold small text-dark d-block mb-1">CATEGORÍA DETECTADA POR ML:</label>
                                    <span id="ia-cat-rechazo" class="badge bg-danger fs-6 py-2 px-3 rounded-pill text-uppercase"></span>
                                    <input type="hidden" id="input-ia-cat-rechazo">
                                </div>
                            </div>

                            <button type="button" id="confirmarDevolucionModal" class="btn btn-danger w-100 fw-bold py-2 rounded-pill shadow">
                                CONFIRMAR DEVOLUCIÓN
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </form>
</div>

{{-- Contenedor de datos para JS evitando variables globales sueltas --}}
<div id="dataExpediente" 
    data-obs="{{ $expediente->observaciones }}"
    data-area-orig="{{ $ultimaDeriv->usuario->subdivision->nombre_subdivision ?? 'SIN DERIVAR' }}"
    data-fecha-orig="{{ $ultimaDeriv ? \Carbon\Carbon::parse($ultimaDeriv->fecha_derivacion)->format('d/m/Y H:i:s') : '--' }}"
    data-rol="{{ $rolUsuario }}"
    data-nombre-auth="{{ auth()->user()->nombre }} {{ auth()->user()->apellido }}"
    data-area-auth="{{ auth()->user()->subdivision?->nombre_subdivision ?? 'S/A' }}">
</div>

<style>
    .btn-atender-verde { background-color: #41982C !important; border: none !important; color: white !important; box-shadow: 0 4px 10px rgba(65, 152, 44, 0.3); }
    .banner-atendido-exito { background-color: #e6f7e9; color: #41982C; border: 1.2px solid #41982C; padding: 4px 15px; border-radius: 20px; font-size: 0.85rem; display: inline-block; font-weight: 600; }
    /* Estilo base para el textarea */
/* ESTILO DINÁMICO OBSERVACIONES */
    .textarea-obs {
        border: none;
        border-radius: 13px;
        padding: 0.2rem 35px 0.2rem 12px !important; /* Espacio para la flecha */
        font-size: 0.85rem !important;
        min-height: 32px;
        line-height: 1.5;
        resize: none;
        overflow: hidden;
        box-sizing: border-box;
        transition: height 0.2s ease, background-color 0.2s;
    }

    .textarea-obs:focus:not(.collapsed) {
        border: 1px solid #4da3ff;
        box-shadow: 0 0 0 0.15rem rgba(77, 163, 255, 0.25);
        outline: none;
    }
    .textarea-obs.edit-mode {
        background-color: #ffffff !important;
        border: 1px solid #ced4da;
    }
    .textarea-obs.detail-mode {
        background-color: #f8f9fa !important;
        color: #495057;
    }

    .textarea-obs.collapsed {
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        height: 32px !important;
        cursor: pointer;
    }

    .btn-expand {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        background: transparent;
        border: none;
        color: #cfcfcf;
        padding: 0 5px;
        cursor: pointer;
        z-index: 10;
        transition: transform 0.3s ease;
        display: flex;
        align-items: center;
    }

    .btn-expand.active {
        transform: translateY(-50%) rotate(180deg);
    }
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const isDetail = {{ $isDetail ? 'true' : 'false' }};
    const isEdit = {{ $isEdit ? 'true' : 'false' }};

    // --- INTEGRACIÓN LÓGICA RECOMENDACIÓN IA ---
    if (isEdit) {
        // --- 1. LÓGICA RECOMENDACIÓN ML (ANALISTA) ---
        const selectAnalista = document.getElementById('selectAnalista');
        const iaInsight = document.getElementById('ia-insight');
        const iaReason = document.getElementById('ia-reason');

        function actualizarMotivoML() {
            const selectedOption = selectAnalista.options[selectAnalista.selectedIndex];
            const motivo = selectedOption.getAttribute('data-motivo'); // Aquí llega: "Experto en INSCRIPCION..."
            if (motivo) {
                iaReason.innerText = motivo;
                iaInsight.style.display = 'block';
            } else {
                iaInsight.style.display = 'none';
            }
        }
        
    document.getElementById('modalSeleccionarAnalista').addEventListener('shown.bs.modal', actualizarMotivoML);
        selectAnalista.addEventListener('change', actualizarMotivoML);
        const btnClasificar = document.getElementById('btnClasificarRechazo');
        const motivoInput = document.getElementById('motivoDevolucion');
        const contenedorIA = document.getElementById('contenedorSugerenciaIA');
        const labelCat = document.getElementById('ia-cat-rechazo');
        const hiddenCat = document.getElementById('input-ia-cat-rechazo');

        btnClasificar.addEventListener('click', async () => {
            const obs = motivoInput.value.trim();
            if (obs.length < 5) {
                showModalAlert("Escriba un motivo más detallado para que el modelo ML pueda clasificarlo.");
                return;
            }

            btnClasificar.disabled = true;
            btnClasificar.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>PROCESANDO...`;

            try {
                const response = await fetch("{{ route('expedientes.ia.rechazo', $expediente->id_expediente) }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ motivo_breve: obs })
                });

                const data = await response.json();
                if (data.categoria_rechazo) {
                    labelCat.innerText = data.categoria_rechazo;
                    hiddenCat.value = data.categoria_rechazo;
                    contenedorIA.style.display = 'block';
                }
            } catch (error) {
                showModalAlert("No se pudo conectar con el servicio de IA local.");
            } finally {
                btnClasificar.disabled = false;
                btnClasificar.innerHTML = `<i class="bi bi-magic me-1"></i> RE-CLASIFICAR CON ML`;
            }
        });

        // Confirmación de Devolución
        document.getElementById('confirmarDevolucionModal').addEventListener('click', () => {
            const obsValue = motivoInput.value.trim();
            const catValue = hiddenCat.value || 'OTROS';
            if (!obsValue) { showModalAlert("Debe ingresar una observación."); return; }

            // Guardamos el motivo estandarizado en el campo de observaciones antes de enviar
            document.getElementById('inputObservaciones').value = `[ML: ${catValue}] ${obsValue.toUpperCase()}`;
            document.getElementById('inputAccion').value = 'devolver';
            document.getElementById('formExpediente').submit();
        });
    }
    // 1. REUTILIZACIÓN DE LÓGICA (AUTO-RESIZE Y EXPANSIÓN)
    const textareaPairs = [
        { input: document.getElementById('inputObservaciones'), btn: document.getElementById('btnExpandObs') },
        { input: document.getElementById('inputDocumentos'), btn: document.getElementById('btnExpandDocs') }
    ];

    textareaPairs.forEach(pair => {
        if (!pair.input) return;

        const autoResize = () => {
            // CORRECCIÓN: Usar pair.input en lugar de obs
            if (pair.input.classList.contains('collapsed')) {
                pair.input.style.height = '34px';
                return;
            }
            pair.input.style.height = 'auto'; 
            pair.input.style.height = pair.input.scrollHeight + 'px';
        };

        const checkOverflow = () => {
            if (!pair.btn) return;
            const temp = document.createElement('div');
            temp.style.cssText = `position:absolute; visibility:hidden; width:${pair.input.clientWidth}px; font:${window.getComputedStyle(pair.input).font}; white-space:pre-wrap; padding:${window.getComputedStyle(pair.input).padding};`;
            temp.innerText = pair.input.value || pair.input.placeholder;
            document.body.appendChild(temp);
            const needsExpand = temp.scrollHeight > 36;
            document.body.removeChild(temp);

            if (needsExpand || pair.input.value.length > 80) pair.btn.classList.remove('d-none');
            else pair.btn.classList.add('d-none');
        };

        pair.input.addEventListener('input', () => { 
            pair.input.classList.remove('collapsed');
            autoResize(); 
            checkOverflow(); 
        });

        if (pair.btn) {
            pair.btn.addEventListener('click', function() {
                const isCollapsed = pair.input.classList.contains('collapsed');
                if (isCollapsed) {
                    pair.input.classList.remove('collapsed');
                    pair.input.style.whiteSpace = 'normal';
                    autoResize();
                    this.classList.add('active');
                } else {
                    pair.input.classList.add('collapsed');
                    pair.input.style.whiteSpace = 'nowrap';
                    pair.input.style.height = '34px';
                    this.classList.remove('active');
                }
            });
        }

        setTimeout(() => {
            checkOverflow();
            if (isEdit && pair.input.value.trim() === "") {
                pair.input.classList.remove('collapsed');
                autoResize();
            } else {
                pair.input.style.height = '34px';
            }
        }, 400);
    });

    // 2. BOTÓN AZUL TOGGLE (Documentos Adjuntos)
    const btnToggleDocs = document.getElementById('btnToggleDocs');
    const inputDocs = document.getElementById('inputDocumentos');
    const iconToggle = document.getElementById('iconToggleDocs');

    if (btnToggleDocs && inputDocs) {
        btnToggleDocs.addEventListener('click', function() {
            const isHidden = inputDocs.classList.contains('d-none');
            if (isHidden) {
                inputDocs.classList.remove('d-none', 'collapsed');
                iconToggle.classList.replace('bi-circle', 'bi-check-circle-fill');
                inputDocs.focus();
                inputDocs.style.height = 'auto';
                inputDocs.style.height = inputDocs.scrollHeight + 'px';
            } else {
                inputDocs.classList.add('d-none');
                inputDocs.value = '';
                iconToggle.classList.replace('bi-check-circle-fill', 'bi-circle');
                if(document.getElementById('btnExpandDocs')) document.getElementById('btnExpandDocs').classList.add('d-none');
            }
        });
    }

    @if($isEdit)
        // 1. SELECTORES UNIFICADOS
        const elements = {
            form: document.getElementById('formExpediente'),
            inputAccion: document.getElementById('inputAccion'),
            selectAnalista: document.getElementById('selectAnalista'),
            contenedor: document.getElementById('contenedorDerivacion'),
            inputObs: document.getElementById('inputObservaciones'),
            btnX: document.getElementById('btnCancelarAccion'),
            btnDevolver: document.getElementById('btnDevolver'),
            btnAtender: document.getElementById('btnAtender'),
            btnCancelarAtencion: document.getElementById('btnCancelarAtencion'),
            containerDerivar: document.getElementById('containerBtnDerivar'),
            data: document.getElementById('dataExpediente').dataset
        };

        // Modales de Bootstrap
        const modalAnalista = elements.selectAnalista ? bootstrap.Modal.getOrCreateInstance(document.getElementById('modalSeleccionarAnalista')) : null;
        const modalDevolver = document.getElementById('modalDevolverConfirm') ? bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDevolverConfirm')) : null;

        // Guardar el estado original para el botón X (Reset)
        const contenidoOriginal = elements.contenedor.innerHTML;

        // 2. FUNCIÓN DE RESET (Vuelve todo a la normalidad)
        const resetFormulario = () => {
            elements.inputAccion.value = '';
            elements.inputObs.value = elements.data.obs;
            elements.contenedor.innerHTML = contenidoOriginal; 
            
            // Mostrar botones originales
            if (elements.containerDerivar) elements.containerDerivar.classList.remove('d-none');
            
            if (elements.btnDevolver) {
                elements.btnDevolver.classList.remove('d-none', 'btn-danger');
                elements.btnDevolver.classList.add('btn-outline-danger');
                elements.btnDevolver.innerHTML = `<i class="bi bi-arrow-counterclockwise me-2"></i> DEVOLVER`;
            }
            
            if (elements.btnAtender) {
                elements.btnAtender.classList.remove('d-none', 'btn-atender-verde');
                elements.btnAtender.classList.add('btn-success');
                elements.btnAtender.innerHTML = `<i class="bi bi-check2-all me-2"></i> MARCAR COMO ATENDIDO`;
            }

            if (elements.btnCancelarAtencion) {
                elements.btnCancelarAtencion.classList.remove('d-none', 'btn-danger');
                elements.btnCancelarAtencion.classList.add('btn-dark');
                elements.btnCancelarAtencion.innerHTML = `<i class="bi bi-x-circle me-2"></i> CANCELAR ATENCIÓN REALIZADA`;
            }

            elements.btnX.classList.add('d-none');
            if (typeof collapse === "function") collapse(); 
        };

        if (elements.btnX) elements.btnX.addEventListener('click', resetFormulario);

        // 3. ACCIÓN: DERIVAR
        const btnConfirmarDerivar = document.getElementById('confirmarDerivar');
        if (btnConfirmarDerivar) {
            btnConfirmarDerivar.addEventListener('click', () => {
                const sel = elements.selectAnalista;
                if (!sel.value) {
                    showModalAlert("POR FAVOR, SELECCIONE UN ANALISTA.");
                    return;
                }

                const opt = sel.options[sel.selectedIndex];
                if (!opt.dataset.area) {
                    showModalAlert("NO SE PUEDE DERIVAR PORQUE EL ANALISTA SE ENCUENTRA EN UNA OFICINA QUE NO ESTÁ ACTIVA.");
                    return;
                }
                elements.inputAccion.value = 'derivar';

                elements.contenedor.innerHTML = `
                    <div class="col-md-12"><strong>Estado actual:</strong> <span class="text-primary fw-bold">LISTO PARA DERIVAR</span></div>
                    <div class="col-md-6">
                        <div><strong>Derivado a:</strong> <span class="text-secondary ms-1">${opt.dataset.area}</span></div>
                        <div class="mt-2"><strong>Analista:</strong> <span class="text-primary fw-bold"><i class="bi bi-check-circle-fill"></i> ${opt.text.split('(')[0].trim()}</span></div>
                    </div>
                    <div class="col-md-6 text-end">
                        <div><strong>Fecha derivación:</strong> <span class="text-secondary">HOY (PREPARADO)</span></div>
                    </div>`;

                if (elements.btnAtender) elements.btnAtender.classList.add('d-none');
                if (elements.btnDevolver) elements.btnDevolver.classList.add('d-none');
                if (elements.btnCancelarAtencion) elements.btnCancelarAtencion.classList.add('d-none');
                elements.btnX.classList.remove('d-none');
                modalAnalista.hide();
            });
        }

        // 4. ACCIÓN: ATENDER
        if (elements.btnAtender) {
            elements.btnAtender.addEventListener('click', () => {
                elements.inputAccion.value = 'atender';
                const hoy = new Date().toLocaleDateString('es-ES');

                if (elements.data.rol === "1") {
                    elements.contenedor.innerHTML = `
                        <div class="col-md-6">
                            <div><strong>Estado actual:</strong> <span class="fw-bold ms-1" style="color: #41982C;">ATENDIDO</span></div>
                            <div class="mt-2"><strong>Atendido por:</strong> <span class="text-secondary ms-1 fw-bold">${elements.data.nombreAuth}</span></div>
                            <div><strong>Área:</strong> <span class="text-secondary ms-1">${elements.data.areaAuth}</span></div>
                        </div>
                        <div class="col-md-6 text-end">
                            <div class="banner-atendido-exito mb-2">Expediente atendido exitosamente</div>
                            <div><strong>Fecha atención:</strong> <span class="fw-bold ms-1" style="color: #41982C;">${hoy} (PREPARADO)</span></div>
                        </div>`;
                } else {
                    elements.contenedor.innerHTML = `
                        <div class="col-md-6">
                            <div><strong>Derivado a:</strong> <span class="text-secondary ms-1">${elements.data.areaOrig}</span></div>
                            <div class="mt-2"><strong>Analista:</strong> <span class="text-secondary ms-1 fw-bold">${elements.data.nombreAuth}</span></div>
                        </div>
                        <div class="col-md-6 text-end">
                            <div><strong>Fecha derivación:</strong> <span class="text-secondary ms-1">${elements.data.fechaOrig}</span></div>
                            <div class="mt-2"><strong>Fecha atención:</strong> <span class="fw-bold ms-1" style="color: #41982C;">${hoy} (PREPARADO)</span></div>
                        </div>`;
                }

                elements.btnAtender.innerHTML = `<i class="bi bi-check-lg"></i> LISTO PARA ATENDER`;
                elements.btnAtender.classList.replace('btn-success', 'btn-atender-verde');
                
                if (elements.btnDevolver) elements.btnDevolver.classList.add('d-none');
                if (elements.containerDerivar) elements.containerDerivar.classList.add('d-none');
                if (elements.btnCancelarAtencion) elements.btnCancelarAtencion.classList.add('d-none');
                elements.btnX.classList.remove('d-none');
            });
        }

        // 5. ACCIÓN: DEVOLVER
        if (elements.btnDevolver) {
            elements.btnDevolver.addEventListener('click', () => modalDevolver.show());
            const btnConfirmarDev = document.getElementById('confirmarDevolucionModal');
            const btnSugerirIA = document.getElementById('btnSugerirIA');
            const contenedorIA = document.getElementById('contenedorSugerenciaIA');
            const motivoBreveInput = document.getElementById('motivoDevolucion');

            if (btnSugerirIA) {
                btnSugerirIA.addEventListener('click', async () => {
                    const motivo = motivoBreveInput.value.trim();
                    if (motivo.length < 5) {
                        showModalAlert("POR FAVOR, ESCRIBA UN MOTIVO BREVE PARA QUE LA IA PUEDA ANALIZARLO.");
                        return;
                    }

                    btnSugerirIA.disabled = true;
                    btnSugerirIA.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>ANALIZANDO...`;

                    try {
                        const response = await fetch("{{ route('expedientes.ia.rechazo', $expediente->id_expediente) }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ motivo_breve: motivo })
                        });

                        const data = await response.json();

                        if (data) {
                            document.getElementById('ia-texto-formal').value = data.texto_formal;
                            document.getElementById('ia-cat-rechazo').innerText = data.categoria_rechazo.toUpperCase();
                            document.getElementById('input-ia-cat-rechazo').value = data.categoria_rechazo;
                            if (contenedorIA) contenedorIA.style.display = 'block';
                        }
                    } catch (error) {
                        showModalAlert("EL SERVICIO DE IA NO ESTÁ DISPONIBLE EN ESTE MOMENTO.");
                    } finally {
                        btnSugerirIA.disabled = false;
                        btnSugerirIA.innerHTML = `<i class="bi bi-magic me-1"></i> RE-GENERAR CON IA`;
                    }
                });
            }

            btnConfirmarDev.addEventListener('click', () => {
                const motivo = motivoBreveInput.value.trim();
                const textoFinal = document.getElementById('ia-texto-formal').value || motivo;
                const catRechazo = document.getElementById('input-ia-cat-rechazo').value;
                const miRol = elements.data.rol;

                if (!textoFinal) { showModalAlert("DEBE INGRESAR UN MOTIVO."); return; }

                elements.inputObs.value = `MOTIVO RECHAZO: ${textoFinal.toUpperCase()}`;

                if (catRechazo) {
                    let inputCat = document.createElement('input');
                    inputCat.type = 'hidden';
                    inputCat.name = 'ia_categoria_rechazo';
                    inputCat.value = catRechazo;
                    elements.form.appendChild(inputCat);
                }

                elements.inputAccion.value = 'devolver';
                const hoy = new Date();
                const fechaHoraDevuelto = hoy.toLocaleString('es-ES', { hour12: false });
                const etiquetaRechazo = (miRol == "1") ? "RECHAZADO POR ADMINISTRADOR" : "RECHAZADO POR ANALISTA";

                elements.contenedor.innerHTML = `
                    <div class="col-md-6">
                        <div><strong>Estado actual:</strong> <span class="text-danger fw-bold ms-1">DEVUELTO</span></div>
                        <div class="mt-2"><strong>Motivo:</strong> <span class="text-danger fw-bold ms-1">${etiquetaRechazo}</span></div>
                    </div>
                    <div class="col-md-6 text-end">
                        <div><strong>Fecha devolución:</strong> <span class="text-danger fw-bold ms-1">${fechaHoraDevuelto} (PREPARADO)</span></div>
                    </div>`;

                elements.btnDevolver.innerText = "PREPARADO PARA DEVOLVER";
                elements.btnDevolver.classList.replace('btn-outline-danger', 'btn-danger');
                if (elements.containerDerivar) elements.containerDerivar.classList.add('d-none');
                if (elements.btnAtender) elements.btnAtender.classList.add('d-none');
                elements.btnX.classList.remove('d-none');
                modalDevolver.hide();
            });
        }

        // 6. ACCIÓN: CANCELAR ATENCIÓN (Admin)
        if (elements.btnCancelarAtencion) {
            elements.btnCancelarAtencion.addEventListener('click', () => {
                showModalConfirm(
                    "¿DESEA ANULAR LA ATENCIÓN DEL ANALISTA? EL EXPEDIENTE QUEDARÁ COMO 'DEVUELTO' PARA SU REPROCESAMIENTO.", 
                    function() {
                        elements.inputAccion.value = 'cancelar_atencion';
                        
                        elements.contenedor.innerHTML = `
                            <div class="col-md-12 text-center py-2">
                                <span class="badge bg-danger p-2 fs-6">
                                    <i class="bi bi-exclamation-octagon me-2"></i> ATENCIÓN ANULADA - LISTO PARA REPROCESAR (PRESIONE GUARDAR)
                                </span>
                            </div>`;
                        
                        elements.btnCancelarAtencion.classList.replace('btn-dark', 'btn-danger');
                        elements.btnCancelarAtencion.innerText = "ATENCIÓN ANULADA";
                        elements.btnX.classList.remove('d-none');
                    }
                );
            });
        }
    @endif
});
</script>
@endpush
@endsection