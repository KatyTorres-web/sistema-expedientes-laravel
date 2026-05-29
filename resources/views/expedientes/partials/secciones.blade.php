@switch($section)
    @case('encabezado')
        @if($isDetail)
        <div class="d-flex justify-content-between align-items-center mb-2">
            <a href="{{ route('expedientes.index') }}" class="text-dark fs-3 text-decoration-none">
                <i class="bi bi-arrow-left-circle"></i>
            </a>
            <h2 class="fw-bold mb-0">Detalles del expediente</h2>
            @if(!($rolUsuario == 2 && in_array($estadoNom, ['ATENDIDO', 'DEVUELTO'])))
            <a href="{{ route('expedientes.edit', $expediente->id_expediente) }}" class="btn btn-primary-custom shadow-sm px-4 rounded-3 fw-bold text-decoration-none">EDITAR</a>
            @else
            <div style="width: 100px;"></div>
            @endif
        </div>
        @else
        <div class="d-flex justify-content-center align-items-center mb-2">
            <h2 class="fw-bold mb-0 text-center">Editar expediente</h2>
        </div>
        @endif
    @break

    @case('secciones')
    <!-- IDENTIFICADORES SUPERIORES-->
    <div class="row mb-4 text-center">
        <div class="col-md-4">
            <label class="label-small d-block text-secondary mb-1">N° EXPEDIENTE</label>
            @if($isEdit && $rolUsuario == 1)
                {{-- Input para editar el número si es Admin --}}
                <input type="text" name="num_expediente" 
                    class="form-control text-center fw-bold bg-white border-primary" 
                    value="{{ $expediente->num_expediente }}" 
                    style="font-size: 1.2rem; border-radius: 10px;">
                    @error('num_expediente')
                        <small class="text-danger fw-bold d-block mt-1">{{ $message }}</small>
                    @enderror
            @else
                {{-- Badge normal en modo detalle o para el analista --}}
                <div class="badge-header-blue py-2 shadow-sm ">{{ $expediente->num_expediente }}</div>
                {{-- Input oculto si no es admin pero es modo edit para que el form no de error (aunque ya controlamos el Controller) --}}
                @if($isEdit)
                    <input type="hidden" name="num_expediente" value="{{ $expediente->num_expediente }}">
                @endif
            @endif
        </div>
        <div class="col-md-4">
            <label class="label-small d-block text-secondary mb-1">ID</label>
            <div class="badge-header-purple py-2 shadow-sm">{{ $expediente->correlativo_anual }}-{{ $expediente->anio_expediente }}</div>
        </div>
        <div class="col-md-4">
            <label class="label-small d-block text-secondary mb-1">ESTADO:</label>
            @php
            $class = match($estadoNom) {
            'DERIVADO' => 'badge-derivado',
            'ATENDIDO' => 'badge-atendido',
            'REGISTRADO' => 'badge-registrado',
            'DEVUELTO'   => ($labelEstado == 'DEVUELTO (REPROCESO)') ? 'badge-reproceso' : 'badge-devuelto',
            default      => 'bg-secondary text-white'
            };
            @endphp
            <div class="badge-status {{ $class }} fs-4 w-100 py-2 border-0 shadow-sm">{{ $labelEstado }}</div>
        </div>
    </div>

    <!-- SECCIÓN: DATOS GENERALES -->
    <div class="mb-4">
        <h6 class="fw-bold border-bottom border-primary border-2 d-inline-block pb-1 mb-4 text-uppercase">Datos Generales</h6>
        <div class="row g-3">
            <div class="col-md-6 d-flex align-items-center">
                <label class="fw-bold label-small" style="width: 200px;">Tipo de presentante:</label>
                <select name="tipo_presentante" class="form-select form-thin {{ ($isDetail || $rolUsuario == 2) ? 'bg-light border-0' : 'bg-white' }}" {{ $disabledGeneral }}>
                    <option value="NATURAL" {{ $expediente->tipo_presentante == 'NATURAL' ? 'selected' : '' }}>Natural</option>
                    <option value="JURIDICA" {{ $expediente->tipo_presentante == 'JURIDICA' ? 'selected' : '' }}>Jurídico</option>
                </select>
            </div>
            <div class="col-md-6 d-flex align-items-center">
                <label class="fw-bold label-small text-end me-3" style="width: 200px;">Fecha de recepción:</label>
                <input type="date" name="fecha_recepcion" class="form-control form-thin {{ ($isDetail || $rolUsuario == 2) ? 'bg-light border-0' : 'bg-white' }}" value="{{ $expediente->fecha_recepcion }}" {{ $readonlyGeneral }}>
            </div>
            <div class="col-md-6 d-flex align-items-center">
                <label class="fw-bold label-small" style="width: 200px;">Nombre / Razón social:</label>
                <input type="text" name="apellidos_y_nombres_razon" class="form-control form-thin {{ ($isDetail || $rolUsuario == 2) ? 'bg-light border-0' : 'bg-white' }}" value="{{ $expediente->tipo_presentante == 'NATURAL' ? $expediente->apellidos_y_nombres : $expediente->razon_social }}" {{ $readonlyGeneral }}>
            </div>
            <div class="col-md-6 d-flex align-items-center">
                <label class="fw-bold label-small text-end me-3" style="width: 200px;">N° de folios:</label>
                <input type="number" name="folios_actual" class="form-control form-thin {{ ($isDetail || $rolUsuario == 2) ? 'bg-light border-0 text-center' : 'bg-white text-center' }}" value="{{ $expediente->folios_actual }}" value="{{ $expediente->folios_actual }}"  min="1" oninput="if(this.value < 1) this.value = 1;" {{ $readonlyGeneral }}>
            </div>
            <div class="col-md-12 d-flex align-items-center mt-3">
                <label class="fw-bold label-small" style="width: 180px; min-width: 180px">Descripción del expediente:</label>
                <input type="text" name="descripcion" class="form-control form-thin {{ ($isDetail || $rolUsuario == 2) ? 'bg-light border-0' : 'bg-white' }}" value="{{ $expediente->descripcion }}" {{ $readonlyGeneral }}>
            </div>
            <div class="col-md-12 mt-2">
                <div class="d-flex align-items-start">
                    <label class="fw-bold label-small" style="width: 180px; min-width: 180px;">Observaciones:</label>
                    <div class="position-relative w-100" id="wrapperObservaciones"><textarea name="observaciones" id="inputObservaciones"
                            class="form-control textarea-obs {{ $isEdit ? 'edit-mode' : 'detail-mode' }}"
                            rows="1"
                            placeholder="INGRESE NOTAS ADICIONALES..."
                            {{ $readonlyObs }}
                            style="resize: none; overflow: hidden; transition: height 0.1s ease;">{{ $expediente->observaciones }}</textarea>
                        <button type="button" id="btnExpandObs" class="btn-expand d-none">
                                <i class="bi bi-caret-down-fill"></i>
                        </button>
                    </div>
                </div>
            </div>
            <!-- SECCIÓN: DOCUMENTOS ADJUNTOS -->
            <div class="col-md-12 mt-2 {{ ($isDetail && !$expediente->documentos_adjuntos) ? 'd-none' : '' }}">
                <div class="d-flex align-items-start">
                    <div style="width: 180px; min-width: 180px;" class="d-flex align-items-center">
                        @if($isEdit)
                        <button type="button" id="btnToggleDocs" class="btn btn-link p-0 border-0 me-2 text-decoration-none">
                                <i id="iconToggleDocs" class="bi {{ $expediente->documentos_adjuntos ? 'bi-check-circle-fill' : 'bi-circle' }} fs-5" style="color: #0d6efd;"></i>
                        </button>
                        @endif

                        <label class="fw-bold label-small" style="width: 155px; min-width: 155px;">Doc. adjuntos:</label>
                </div>
                    <div class="position-relative w-100" id="wrapperDocs">
                        <textarea name="documentos_adjuntos" id="inputDocumentos"
                            class="form-control textarea-obs {{ $isEdit ? 'edit-mode' : 'detail-mode' }} {{ ($isEdit && !$expediente->documentos_adjuntos) ? 'd-none' : '' }}"
                            rows="1"
                            placeholder="INGRESE DOCUMENTOS ADJUNTOS ..."
                            {{ $isDetail ? 'readonly' : '' }}
                            style="resize: none; overflow: hidden; transition: height 0.1s ease;">{{ $expediente->documentos_adjuntos }}</textarea>
                        
                        <button type="button" id="btnExpandDocs" class="btn-expand d-none">
                            <i class="bi bi-caret-down-fill"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SECCIÓN: REGISTRO -->
    <div class="mb-4">
        <h6 class="fw-bold border-bottom border-primary border-2 d-inline-block pb-1 mb-3 text-uppercase">Registro del Expediente</h6>
        <div class="row g-2 small">
            <div class="col-md-5"><strong>Registrado por:</strong> <span class="text-secondary ms-1">{{ $expediente->usuario->nombre }} {{ $expediente->usuario->apellido }}</span></div>
            <div class="col-md-3"><strong>Rol:</strong> <span class="text-secondary ms-1">{{ $expediente->usuario->rol->nombre_rol }}</span></div>
            <div class="col-md-4 text-end"><strong>Área/Oficina:</strong> <span class="text-secondary ms-1"> {{ $expediente->usuario->subdivision->nombre_subdivision ?? 'SIN ÁREA' }} ({{ $expediente->usuario->subdivision->cod_subdivision }})</span></div>
            <div class="col-md-12"><strong>Fecha de registro:</strong> <span class="text-secondary ms-1">{{ \Carbon\Carbon::parse($expediente->fecha_registro)->format('d/m/Y - H:i:s') }}</span></div>
        </div>
    </div>

    <!-- SECCIÓN: DERIVACIÓN -->
    <div class="mb-2">
        <h6 class="fw-bold border-bottom border-primary border-2 d-inline-block pb-1 mb-3 text-uppercase">Derivación del Expediente</h6>

        <div class="row g-2 small" id="contenedorDerivacion">
            @if($estadoNom == 'ATENDIDO')
            @if($atendidoPorAdmin)
            {{-- VISTA TIPO ADMINISTRADOR --}}
            <div class="col-md-6">
                <div><strong>Estado actual:</strong> <span class="fw-bold ms-1" style="color: #41982C;">ATENDIDO</span></div>
                <div class="mt-2"><strong>Atendido por:</strong> <span class="text-secondary ms-1 fw-bold">{{ $registroAtencion?->usuario?->nombre }} {{ $registroAtencion?->usuario?->apellido }}</span></div>
                <div><strong>Área:</strong> <span class="text-secondary ms-1">{{ $registroAtencion?->usuario?->subdivision?->nombre_subdivision ?? 'S/A' }}</span></div>
            </div>
            <div class="col-md-6 text-end">
                <div class="banner-atendido-exito mb-2">Expediente atendido exitosamente</div>
                <div><strong>Fecha atención:</strong> <span class="fw-bold ms-1" style="color: #41982C;">{{ $registroAtencion ? \Carbon\Carbon::parse($registroAtencion->fecha_cambio)->format('d/m/Y') : '--' }}</span></div>
            </div>
            @else
            {{-- VISTA TIPO ANALISTA (Como estaba antes) --}}
            <div class="col-md-6">
                <div><strong>Derivado a:</strong> <span class="text-secondary ms-1">{{ $ultimaDeriv?->usuario?->subdivision?->nombre_subdivision ?? 'S/A' }}</span></div>
                <div class="mt-2"><strong>Analista:</strong> <span class="text-secondary ms-1 fw-bold">{{ $ultimaDeriv?->usuario?->nombre ?? 'S/N' }} {{ $ultimaDeriv?->usuario?->apellido ?? '' }}</span></div>
            </div>
            <div class="col-md-6">
                <div><strong>Fecha derivación:</strong> <span class="text-secondary ms-1">{{ $ultimaDeriv ? \Carbon\Carbon::parse($ultimaDeriv->fecha_derivacion)->format('d/m/Y H:i:s') : '--' }}</span></div>
                <div class="mt-2"><strong>Fecha atención:</strong> <span class="fw-bold ms-1" style="color: #41982C;">{{ $ultimaDeriv?->fecha_atencion ? \Carbon\Carbon::parse($ultimaDeriv->fecha_atencion)->format('d/m/Y H:i:s') : '--' }}</span></div>
            </div>
            @endif
            @elseif($estadoNom == 'DEVUELTO')
            <div class="col-md-6">
            <div><strong>Estado actual:</strong> <span class="text-danger fw-bold ms-1">{{ $labelEstado }}</span></div>
            <div class="mt-2">
                <strong>Motivo:</strong> 
                <span class="text-danger fw-bold ms-1">
                    @if($fueCanceladoPorAdmin) ATENCIÓN CANCELADA POR ADMINISTRADOR 
                    @elseif($rolQueDevolvio == 1) RECHAZADO POR ADMINISTRADOR
                    @else RECHAZADO POR ANALISTA @endif
                </span>
            </div>
        </div>
            <div class="col-md-6">
                <div><strong>Fecha devolución:</strong> <span class="text-danger fw-bold ms-1">{{ $ultimaDevolucion ? \Carbon\Carbon::parse($ultimaDevolucion->fecha_cambio)->format('d/m/Y H:i:s') : '--' }}</span></div>
            </div>
            @elseif($ultimaDeriv)
            <div class="col-md-6">
                <div><strong>Derivado a:</strong> <span class="text-secondary ms-1">{{ $ultimaDeriv->usuario->subdivision->nombre_subdivision ?? 'S/A' }}</span></div>
                <div class="mt-2"><strong>Analista:</strong> <span class="text-secondary ms-1 fw-bold">{{ $ultimaDeriv->usuario->nombre }} {{ $ultimaDeriv->usuario->apellido }}</span></div>
            </div>
            <div class="col-md-6">
                <div><strong>Fecha derivación:</strong> <span class="text-secondary ms-1">{{ \Carbon\Carbon::parse($ultimaDeriv->fecha_derivacion)->format('d/m/Y H:i:s') }}</span></div>
                <div class="mt-2"><strong>Fecha atención:</strong> <span class="ms-1 {{ $ultimaDeriv->fecha_atencion ? 'text-success fw-bold' : 'text-muted italic' }}" style="color: #41982C;">{{ $ultimaDeriv->fecha_atencion ? \Carbon\Carbon::parse($ultimaDeriv->fecha_atencion)->format('d/m/Y H:i:s') : 'PENDIENTE' }}</span></div>
            </div>
            @else
            <div class="col-md-12"><strong>Estado actual:</strong> <span class="text-secondary ms-1">{{ $estadoNom }}</span></div>
            <div class="col-md-12"><strong>Derivado a:</strong> <span class="text-secondary ms-1 italic">-- SIN DERIVAR --</span></div>
            @endif
        </div>
    @break
@endswitch