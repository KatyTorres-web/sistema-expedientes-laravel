@extends('layouts.principal')

@section('content')
<div class="container-fluid p-0">
    
    <!-- ENCABEZADO -->
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="fw-bold mb-0">Editar usuario</h2>
    </div>
    @if ($errors->any())
    <div class="alert alert-danger shadow-sm rounded-3">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li><i class="bi bi-exclamation-triangle me-2"></i> {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form action="{{ route('usuarios.update', $usuario->id) }}" method="POST" id="formEditarUsuario">
        @csrf
        @method('PUT')

        <!-- CUADRO PRINCIPAL -->
        <div class="card border-blue-custom rounded-4 shadow-sm">
            <div class="card-body p-4">

                <!-- USERNAME-->
                <div class="row mb-4 text-center">
                    <div class="col-md-12">
                        <label class="label-small d-block text-secondary mb-1 text-uppercase">Usuario</label>
                        <div class="badge-header-blue py-2 shadow-sm fs-4">
                            {{ $usuario->username }}
                        </div>
                    </div>
                </div>

                <!-- INFORMACIÓN PERSONAL -->
                <div class="mb-5">
                    <h6 class="fw-bold border-bottom border-primary border-2 d-inline-block pb-1 mb-4 text-uppercase">
                        Información Personal
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6 d-flex align-items-center">
                            <label class="fw-bold label-small" style="width: 150px;">NOMBRES:</label>
                            <input type="text" name="nombre" class="form-control form-thin" value="{{ old('nombre', $usuario->nombre) }}" required>
                        </div>
                        <div class="col-md-6 d-flex align-items-center">
                            <label class="fw-bold label-small text-end me-3" style="width: 150px;">APELLIDOS:</label>
                            <input type="text" name="apellido" class="form-control form-thin" value="{{ old('apellido', $usuario->apellido) }}" required>
                        </div>
                        <div class="col-md-6 d-flex align-items-center">
                            <label class="fw-bold label-small" style="width: 150px;">TELÉFONO:</label>
                            <input type="text" name="celular" id="celular_edit" 
                                class="form-control form-thin" 
                                value="{{ old('celular', $usuario->celular) }}" 
                                maxlength="9" autocomplete="off">
                        </div>
                        <div class="col-md-6 d-flex align-items-center">
                            <label class="fw-bold label-small text-end me-3" style="width: 150px;">CORREO:</label>
                            <input type="email" name="email" class="form-control form-thin" value="{{ old('email', $usuario->email) }}" style="text-transform: none;" required>
                        </div>
                    </div>
                </div>

                <!-- ASIGNACIÓN LABORAL -->
                <div class="mb-4">
                    <h6 class="fw-bold border-bottom border-primary border-2 d-inline-block pb-1 mb-4 text-uppercase">
                        Asignación Laboral
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-12 d-flex align-items-center">
                            <label class="fw-bold label-small" style="width: 180px;">ÁREA / OFICINA:</label>
                            <select name="subdivision_id" class="form-select form-thin">
                                @foreach($subdivisiones as $area)
                                    <option value="{{ $area->id_subdivision }}" {{ $usuario->subdivision_id == $area->id_subdivision ? 'selected' : '' }}>
                                        {{ $area->nombre_subdivision }} ({{ $area->cod_subdivision }})
                                        {{ $area->estado_subdiv_area == 0 ? '(INACTIVA)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-center mt-3">
                            <label class="fw-bold label-small" style="width: 180px;">ROL:</label>
                            <select name="rol_id" id="rolSelect" class="form-select form-thin">
                                @foreach($roles as $rol)
                                    <option value="{{ $rol->id_rol }}" {{ $usuario->rol_id == $rol->id_rol ? 'selected' : '' }}>
                                        {{ $rol->nombre_rol }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-center mt-3">
                            <label class="fw-bold label-small text-end me-3" style="width: 180px;">ESTADO:</label>
                            <select name="estado_usuario_id" id="estadoSelect" class="form-select form-thin">
                                @foreach($estados as $estado)
                                    <option value="{{ $estado->id_estado_usuario }}" {{ $usuario->estado_usuario_id == $estado->id_estado_usuario ? 'selected' : '' }}>
                                        {{ $estado->estado_usuario }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- BOTONES -->
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('usuarios.show', $usuario->id) }}" class="btn btn-secondary px-4 rounded-pill fw-bold">
                        CANCELAR
                    </a>
                    <button type="submit" class="btn btn-primary-custom px-4 fw-bold shadow-sm">
                        GUARDAR CAMBIOS
                    </button>
                </div>

            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('formEditarUsuario');
    const rolSelect = document.getElementById('rolSelect');
    const estadoSelect = document.getElementById('estadoSelect');
    
    // Datos del usuario para validación de seguridad (self-edit)
    const usuarioId = {{ $usuario->id }};
    const usuarioLogeado = {{ auth()->id() }};
    const rolActual = {{ $usuario->rol_id }};
    const estadoActual = {{ $usuario->estado_usuario_id }};

    // 1. Validar cambio de ROL propio
    rolSelect.addEventListener('change', function () {
        if (usuarioId === usuarioLogeado && rolActual === 1 && this.value != 1) {
            showModalAlert("NO PUEDES CAMBIAR TU PROPIO ROL A ANALISTA.");
            this.value = 1; // Restaurar a Administrador
        }
    });

    // 2. Validar cambio de ESTADO propio (Autodesactivación)
    estadoSelect.addEventListener('change', function () {
        if (usuarioId === usuarioLogeado && this.value != 1) {
            showModalAlert("NO PUEDES DESACTIVAR TU PROPIA CUENTA.");
            this.value = 1; // Restaurar a Activo
        }
    });

    // 3. Confirmación antes de guardar
    form.addEventListener('submit', function (e) {
        e.preventDefault(); // Detener envío normal
        
        showModalConfirm("¿ESTÁ SEGURO DE QUE DESEA GUARDAR LOS CAMBIOS EN ESTE USUARIO?", () => {
            form.submit(); // Ejecutar envío si el usuario confirma
        });
    });

    const celularEdit = document.getElementById('celular_edit');

    celularEdit.addEventListener('input', function() {
        // Bloquea cualquier cosa que no sea número
        this.value = this.value.replace(/[^0-9]/g, '');
        
        // Corta el texto si supera los 9 caracteres (medida de seguridad extra)
        if (this.value.length > 9) {
            this.value = this.value.slice(0, 9);
        }
    });

    // También bloqueamos que se peguen letras
    celularEdit.addEventListener('paste', function(e) {
        let pasteData = (e.clipboardData || window.clipboardData).getData('text');
        if (!/^\d+$/.test(pasteData)) {
            e.preventDefault();
        }
    });
});
</script>
@endpush