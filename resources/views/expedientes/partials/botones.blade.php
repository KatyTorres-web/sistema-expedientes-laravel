@switch($section)
    @case('form_expediente')
        @if($isEdit)
        <div class="d-flex justify-content-end gap-3 mt-4">
            <input type="hidden" name="accion" id="inputAccion" value="">

            {{-- 1. BOTÓN CANCELAR ATENCIÓN: Solo si el Analista fue quien atendió --}}
            @if($rolUsuario == 1 && $estadoNom == 'ATENDIDO' && $atendidoPorAnalista)
                <button type="button" id="btnCancelarAtencion" class="btn btn-dark fw-bold px-4 rounded-pill">
                    <i class="bi bi-x-circle me-2"></i> CANCELAR ATENCIÓN DEL ANALISTA
                </button>
            @endif
            @if($estadoNom != 'ATENDIDO')
                
                @if($puedeAdminActuar)
                    {{-- Botón Derivar/Reasignar --}}
                    <div class="d-flex align-items-center gap-1" id="containerBtnDerivar">
                        <button type="button" class="btn btn-outline-primary fw-bold px-4 rounded-pill" data-bs-toggle="modal" data-bs-target="#modalSeleccionarAnalista">
                            <i class="bi bi-person-plus-fill me-2"></i>
                            {{ $estadoNom == 'DEVUELTO' ? 'REASIGNAR ANALISTA' : 'DERIVAR EXPEDIENTE' }}
                        </button>
                    </div>

                    {{-- Botón Atender --}}
                    <button type="button" id="btnAtender" class="btn btn-success fw-bold px-4 rounded-pill shadow-sm">
                        <i class="bi bi-check2-all me-2"></i> MARCAR COMO ATENDIDO
                    </button>

                    {{-- Botón Devolver --}}
                    <button type="button" id="btnDevolver" class="btn btn-outline-danger fw-bold px-4 rounded-pill">
                        <i class="bi bi-arrow-counterclockwise me-2"></i> DEVOLVER
                    </button>
                @endif

                {{-- Lógica para el Analista --}}
                @if($rolUsuario == 2 && $estadoNom == 'DERIVADO' && $esDueno)
                    <button type="button" id="btnAtender" class="btn btn-success fw-bold px-4 rounded-pill shadow-sm">
                        <i class="bi bi-check2-all me-2"></i> MARCAR COMO ATENDIDO
                    </button>
                    <button type="button" id="btnDevolver" class="btn btn-outline-danger fw-bold px-4 rounded-pill">
                        <i class="bi bi-arrow-counterclockwise me-2"></i> DEVOLVER
                    </button>
                @endif

            @endif

            <button type="button" id="btnCancelarAccion" class="btn btn-outline-danger rounded-circle p-0 d-none" style="width: 32px; height: 32px;">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        @endif
        </div>
        </div>
        </div>

        <!-- BOTONES INFERIORES -->
        <div class="d-flex justify-content-between align-items-center px-2">
            @if($isEdit)
            <a href="{{ route('expedientes.show', $expediente->id_expediente) }}" class="btn btn-secondary-custom shadow-sm px-4 py-2 fw-bold">Cancelar</a>
            <button type="submit" class="btn btn-primary shadow-sm px-5 py-2 fw-bold" style="border-radius: 12px; background-color: #4da3ff; border: none;">Guardar Cambios</button>
            @endif
        </div>
    @break
    @case('boton_index')
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
    @break
@endswitch