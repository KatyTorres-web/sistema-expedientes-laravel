@extends('layouts.principal')

@section('content')
<div class="container-fluid p-0">
    
    <!-- ENCABEZADO -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="{{ route('usuarios.index') }}" class="text-dark fs-3 text-decoration-none">
            <i class="bi bi-arrow-left-circle"></i>
        </a>
        <h2 class="fw-bold mb-0 text-center flex-grow-1 text-uppercase">Gestión de áreas/oficinas</h2>
        <div style="width: 40px;"></div>
    </div>

    <!-- BOTÓN NUEVA ÁREA -->
    <div class="mb-4">
        <button id="btnAbrirModalNuevo" class="btn btn-nueva-area shadow-sm px-4 py-2 d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#modalArea">
            <i class="bi bi-plus-lg me-2"></i> NUEVA ÁREA/OFICINA
        </button>
        <!-- Nota: El alert session('success') se eliminó porque el Layout lo muestra en Modal -->
    </div>

    <!-- TABLA DE ÁREAS -->
    <div class="table-responsive">
        <table class="table custom-table shadow-sm rounded-3 overflow-hidden">
            <thead>
                <tr>
                    <th style="width: 190px;">CÓDIGO</th>
                    <th style="width: 230px;">ÁREA / OFICINA</th>
                    <th style="width: 190px;">ESTADO</th>
                    <th style="width: 190px;">ACCIONES</th>
                </tr>
            </thead>
            <tbody>
                @forelse($areas as $area)
                <tr>
                    <td class="fw-bold text-primary">{{ $area->cod_subdivision }}</td>
                    <td class="text-center align-middle">{{ $area->nombre_subdivision }}</td>
                    <td>
                        <span class="badge-area-status {{ $area->estado_subdiv_area ? 'area-activa' : 'area-inactiva' }}">
                            {{ $area->estado_subdiv_area ? 'ACTIVO' : 'INACTIVO' }}
                        </span>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm px-3 rounded-pill fw-bold shadow-sm btn-editar-area" 
                                style="background-color: #ffe8f3; color: #d63384; border: 1px solid #ffc2df;"
                                data-id="{{ $area->id_subdivision }}"
                                data-nombre="{{ $area->nombre_subdivision }}"
                                data-codigo="{{ $area->cod_subdivision }}"
                                data-estado="{{ $area->estado_subdiv_area }}">
                            <i class="bi bi-pencil-square me-1"></i> Editar
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="py-5 text-center text-secondary">
                        <i class="bi bi-building-exclamation fs-1 d-block mb-2"></i> No hay áreas registradas.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL ÁREA (Formulario único para Nuevo/Editar) -->
<div class="modal fade" id="modalArea" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-blue-custom rounded-4 shadow-lg">
            <div class="modal-body p-4">
                <div class="text-center mb-4">
                    <h3 class="fw-bold text-uppercase" id="modalTitulo">Nueva área/oficina</h3>
                    <p class="text-muted small">Complete los datos para la dependencia.</p>
                </div>

                <form action="{{ route('usuarios.areas.store') }}" method="POST" id="formArea">
                    @csrf
                    <div id="methodField"></div>
                    
                    <div class="row mb-3 align-items-center">
                        <label class="col-sm-3 fw-bold label-small">NOMBRE:</label>
                        <div class="col-sm-9">
                            <input type="text" name="nombre_subdivision" id="inputNombreArea" 
                                class="form-control form-thin bg-light border-0 shadow-sm" 
                                placeholder="INGRESE NOMBRE" required autocomplete="off">
                        </div>
                    </div>

                    <div class="row mb-3 align-items-center">
                        <label class="col-sm-3 fw-bold label-small">CÓDIGO:</label>
                        <div class="col-sm-9">
                            <input type="text" name="cod_subdivision" id="inputCodigoArea" 
                                class="form-control form-thin bg-white border-0 fw-bold text-primary shadow-sm" 
                                placeholder="AUTOMÁTICO" readonly required>
                        </div>
                    </div>

                    <div class="row mb-4 align-items-center">
                        <label class="col-sm-3 fw-bold label-small">ESTADO:</label>
                        <div class="col-sm-9">
                            <input type="hidden" name="estado_subdiv_area" id="inputEstadoArea" value="1">
                            <button type="button" id="btnToggleEstado" class="badge-area-status area-activa border-0 shadow-sm px-4 py-2">
                                <span id="textEstado">ACTIVO</span>
                            </button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <button type="submit" class="btn btn-primary-custom px-4 shadow-sm" id="btnGuardar">GUARDAR ÁREA</button>
                        <button type="button" class="btn btn-secondary-custom px-4" data-bs-dismiss="modal">CANCELAR</button> 
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalElement = document.getElementById('modalArea');
    const form = document.getElementById('formArea');
    const inputNombre = document.getElementById('inputNombreArea');
    const inputCodigo = document.getElementById('inputCodigoArea');
    const inputEstado = document.getElementById('inputEstadoArea');
    const btnToggleEstado = document.getElementById('btnToggleEstado');
    const textEstado = document.getElementById('textEstado');
    const modalTitulo = document.getElementById('modalTitulo');
    const btnGuardar = document.getElementById('btnGuardar');
    const methodField = document.getElementById('methodField');

    const existentes = @json($codigosExistentes);
    const nombresExistentes = @json($areas->pluck('nombre_subdivision'));

    let modoEdicion = false;
    function limpiarErroresValidacion() {
        inputNombre.classList.remove('is-invalid');
        const errorDiv = document.getElementById('errorNombre');
        if (errorDiv) errorDiv.remove();
    }

    // 1. ABRIR MODAL NUEVO
    document.getElementById('btnAbrirModalNuevo').addEventListener('click', () => {
        modoEdicion = false;
        form.reset();
        form.action = "{{ route('usuarios.areas.store') }}";
        methodField.innerHTML = "";
        modalTitulo.innerText = "NUEVA ÁREA/OFICINA";
        btnGuardar.innerText = "GUARDAR ÁREA";
        inputCodigo.value = "";
        inputEstado.value = "1";
        btnToggleEstado.disabled = true;
        actualizarEstadoUI(true);
    });

    // 2. ABRIR MODAL EDITAR
    document.querySelectorAll('.btn-editar-area').forEach(btn => {
        btn.addEventListener('click', function () {
            modoEdicion = true;
            const data = this.dataset;
            form.action = `/usuarios/areas/${data.id}`;
            methodField.innerHTML = '@method("PUT")';
            modalTitulo.innerText = "EDITAR ÁREA/OFICINA";
            btnGuardar.innerText = "GUARDAR CAMBIOS";
            inputNombre.value = data.nombre;
            inputCodigo.value = data.codigo;
            inputEstado.value = data.estado;
            btnToggleEstado.disabled = false;
            actualizarEstadoUI(data.estado == "1");
            new bootstrap.Modal(modalElement).show();
        });
    });

    // 3. TOGGLE ESTADO
    btnToggleEstado.addEventListener('click', () => {
        if (!modoEdicion) return;
        let activo = inputEstado.value === "1";
        actualizarEstadoUI(!activo);
        inputEstado.value = activo ? "0" : "1";
    });

    function actualizarEstadoUI(activo) {
        btnToggleEstado.className = `badge-area-status ${activo ? 'area-activa' : 'area-inactiva'} border-0 shadow-sm px-4 py-2`;
        textEstado.innerText = activo ? "ACTIVO" : "INACTIVO";
    }

    // 4. GENERAR CÓDIGO Y VALIDACIÓN TIEMPO REAL
    inputNombre.addEventListener('input', function () {

        let nombre = this.value.toUpperCase().trim();
        const errorMsgId = 'errorNombre';

        // =========================================================
        // LIMPIAR ESPACIOS EXTRA
        // =========================================================
        nombre = nombre.replace(/\s+/g, ' ');

        // =========================================================
        // SI ESTÁ VACÍO
        // =========================================================
        if (nombre.length === 0) {
            inputCodigo.value = "";

            this.classList.remove('is-invalid');

            let error = document.getElementById(errorMsgId);
            if (error) error.remove();

            return;
        }

        // =========================================================
        // VALIDACIÓN DE DUPLICADO
        // =========================================================
        if (!modoEdicion && nombresExistentes.includes(nombre)) {

            this.classList.add('is-invalid');

            if (!document.getElementById(errorMsgId)) {

                let div = document.createElement('div');

                div.id = errorMsgId;
                div.className = 'invalid-feedback fw-bold';

                div.innerText = 'ESTA ÁREA YA ESTÁ REGISTRADA.';

                this.parentNode.appendChild(div);
            }

            inputCodigo.value = "";
            return;

        } else {

            this.classList.remove('is-invalid');

            let error = document.getElementById(errorMsgId);

            if (error) error.remove();
        }

        // =========================================================
        // SI ES EDICIÓN, NO AUTOGENERAR
        // =========================================================
        if (modoEdicion) return;

        // =========================================================
        // CONECTORES A IGNORAR
        // =========================================================
        const conectores = [
            'DE', 'DEL', 'Y', 'E',
            'EL', 'LA', 'LOS', 'LAS',
            'EN', 'PARA', 'POR',
            'CON', 'SIN'
        ];

        // =========================================================
        // LIMPIAR CARACTERES ESPECIALES
        // =========================================================
        nombre = nombre.replace(/[^A-ZÁÉÍÓÚÑ0-9\s]/gi, '');

        // =========================================================
        // DIVIDIR PALABRAS
        // =========================================================
        let palabras = nombre
            .split(/\s+/)
            .filter(p =>
                p.length > 0 &&
                !conectores.includes(p)
            );

        // =========================================================
        // EVITAR RESULTADO VACÍO
        // =========================================================
        if (palabras.length === 0) {
            inputCodigo.value = "";
            return;
        }

        // =========================================================
        // GENERAR SIGLAS
        let codigoFinal = '';

        // SOLO UNA PALABRA
        if (palabras.length === 1) {

            codigoFinal = palabras[0].substring(0, 1);

        } else {

            codigoFinal = palabras
                .map(p => p.charAt(0))
                .join('');
        }
        // MANEJO DE COLISIONES
        if (existentes.includes(codigoFinal)) {

            // UNA PALABRA
            if (palabras.length === 1) {

                let base = palabras[0].substring(0, 3);
                let i = 2;

                codigoFinal = base;

                while (existentes.includes(codigoFinal)) {
                    codigoFinal = base + i;
                    i++;
                }

            } else {

                let base = codigoFinal;
                let i = 2;

                while (existentes.includes(codigoFinal)) {
                    codigoFinal = base + i;
                    i++;
                }
            }
        }

        // =========================================================
        // ASIGNAR CÓDIGO
        // =========================================================
        inputCodigo.value = codigoFinal;

    });

    // 5. VALIDACIÓN AL ENVIAR (Uso de Modal Global de Alerta)
    form.addEventListener('submit', function(e){
        let nombre = inputNombre.value.toUpperCase().trim();
        if (!modoEdicion && nombresExistentes.includes(nombre)){
            e.preventDefault();
            showModalAlert("ESTA ÁREA YA EXISTE EN EL SISTEMA.");
        }
    });

    // 6. LIMPIAR AL CERRAR
    modalElement.addEventListener('hidden.bs.modal', () => {
        form.reset(); // Resetea los valores de los inputs
        limpiarErroresValidacion(); // Elimina el texto rojo y la clase is-invalid
        
        if (!modoEdicion) {
            inputCodigo.value = "";
        }
        
        console.log("Modal cerrado: Limpieza de validación ejecutada.");
    });
});
</script>
@endpush