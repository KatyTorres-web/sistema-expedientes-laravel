@extends('layouts.principal')

@section('content')
<div class="container-fluid p-0">
    {{-- Mantener los errores de validación de Laravel --}}
    @if ($errors->any())
        <div class="alert alert-danger shadow-sm rounded-3">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li><i class="bi bi-exclamation-triangle me-2"></i>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('perfil.update') }}" method="POST" id="formPerfilPrincipal">
        @csrf
        @method('PUT')
        
        <!-- ENCABEZADO -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="fw-bold mb-0">Mi Perfil</h2>
            <div class="d-flex gap-2">
                <!-- Botones Modo Lectura (Visibles por defecto) -->
                <div id="group-read" class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-danger shadow-sm px-4 rounded-3 fw-bold" data-bs-toggle="modal" data-bs-target="#modalPassword">
                        <i class="bi bi-shield-lock me-1"></i> CAMBIAR CONTRASEÑA
                    </button>
                    <button type="button" id="btn-habilitar-edicion" class="btn btn-primary-custom shadow-sm px-4 rounded-3 fw-bold">
                        <i class="bi bi-pencil-square me-1"></i> EDITAR DATOS
                    </button>
                </div>

                <!-- Botones Modo Edición (Ocultos por defecto) -->
                <div id="group-edit" class="d-flex gap-2 d-none">
                    <button type="button" id="btn-cancelar-edicion" class="btn btn-secondary-custom shadow-sm px-4 rounded-3 fw-bold">CANCELAR</button>
                    <button type="submit" class="btn btn-primary shadow-sm px-4 rounded-3 fw-bold" style="background-color: #4da3ff; border:none;">GUARDAR CAMBIOS</button>
                </div>
            </div>
        </div>

        <div class="scroll-expediente pe-2">
            <!-- TARJETA 1: INFORMACIÓN PERSONAL -->
            <div class="card border-blue-custom rounded-4 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold border-bottom border-primary border-2 d-inline-block pb-1 mb-4 text-uppercase">Información Personal</h6>
                    <div class="row g-3">
                        <div class="col-md-6 d-flex align-items-center">
                            <label class="fw-bold label-small" style="width: 150px;">NOMBRES:</label>
                            <input type="text" name="nombre" class="form-control form-thin editable-input bg-light border-0" value="{{ $usuario->nombre }}" readonly required>
                        </div>
                        <div class="col-md-6 d-flex align-items-center">
                            <label class="fw-bold label-small text-end me-3" style="width: 150px;">APELLIDOS:</label>
                            <input type="text" name="apellido" class="form-control form-thin editable-input bg-light border-0" value="{{ $usuario->apellido }}" readonly required>
                        </div>
                        <div class="col-md-6 d-flex align-items-center">
                            <label class="fw-bold label-small" style="width: 150px;">TELÉFONO:</label>
                            <input type="text" name="celular" id="celular_perfil" 
                                class="form-control form-thin editable-input bg-light border-0" 
                                value="{{ $usuario->celular }}" maxlength="9" readonly>
                        </div>
                        <div class="col-md-6 d-flex align-items-center">
                            <label class="fw-bold label-small text-end me-3" style="width: 150px;">CORREO:</label>
                            <input type="email" name="email" 
                                class="form-control form-thin editable-input bg-light border-0" 
                                value="{{ $usuario->email }}" readonly required 
                                style="text-transform: none;"> <!-- Cambiado a 'none' para ver mayúsculas/minúsculas -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- TARJETA 2: INFORMACIÓN DEL SISTEMA (SIEMPRE LECTURA) -->
            <div class="card border-blue-custom rounded-4 shadow-sm mb-4" style="border-color: #d1c4e9 !important;">
                <div class="card-body p-4">
                    <h6 class="fw-bold border-bottom border-primary border-2 d-inline-block pb-1 mb-4 text-uppercase" style="border-color: #6a1b9a !important;">Información del Sistema</h6>
                    <div class="row g-3">
                        <div class="col-md-6 d-flex align-items-center">
                            <label class="fw-bold label-small" style="width: 150px;">USUARIO:</label>
                            <input type="text" class="form-control form-thin bg-light border-0 fw-bold" value="{{ $usuario->username }}" readonly>
                        </div>
                        <div class="col-md-6 d-flex align-items-center">
                            <label class="fw-bold label-small text-end me-3" style="width: 150px;">ÁREA:</label>
                            <input type="text" class="form-control form-thin bg-light border-0" value="{{ $usuario->subdivision->nombre_subdivision }}" readonly>
                        </div>
                        <div class="col-md-6 d-flex align-items-center">
                            <label class="fw-bold label-small" style="width: 150px;">ROL:</label>
                            <div class="badge-status {{ $usuario->rol_id == 1 ? 'badge-registrado' : 'badge-devuelto' }} w-100 py-1 text-center border-0 text-uppercase">{{ $usuario->rol->nombre_rol }}</div>
                        </div>
                        <div class="col-md-6 d-flex align-items-center">
                            <label class="fw-bold label-small text-end me-3" style="width: 150px;">ESTADO:</label>
                            <div class="badge-status {{ $usuario->estado_usuario_id == 1 ? 'badge-atendido' : 'badge-derivado' }} w-100 py-1 text-center border-0 text-uppercase">{{ $usuario->estado->estado_usuario }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- MODAL PASSWORD (Se mantiene igual, funcional) -->
    <div class="modal fade" id="modalPassword" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('perfil.password') }}" method="POST" id="formPassword" class="w-100">
                @csrf
                @method('PUT')
                <div class="modal-content modal-pro">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="fw-bold text-danger text-uppercase"><i class="bi bi-shield-lock me-2"></i> CAMBIAR CONTRASEÑA</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="fw-bold label-small mb-1">NUEVA CONTRASEÑA:</label>
                            <div class="position-relative">
                                <input type="password" name="password" id="pass1" class="form-control form-thin pe-5 input-password-pro" placeholder="Mínimo 6 caracteres" required>
                                <i class="bi bi-eye-slash-fill toggle-password position-absolute" data-target="pass1"></i>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="fw-bold label-small mb-1">CONFIRMAR CONTRASEÑA:</label>
                            <div class="position-relative">
                                <input type="password" name="password_confirmation" id="pass2" class="form-control form-thin pe-5 input-password-pro" placeholder="Repita la contraseña" required>
                                <i class="bi bi-eye-slash-fill toggle-password position-absolute" data-target="pass2"></i>
                            </div>
                            <div id="passwordError" class="text-danger small mt-1 fw-bold d-none">
                                <i class="bi bi-exclamation-circle me-1"></i> Las contraseñas no coinciden
                            </div>
                        </div>
                        <div class="mt-2" id="password-requirements" style="font-size: 0.75rem; text-align: left;">
                            <div id="req-length" class="text-danger mb-1"><i class="bi bi-exclamation-circle"></i> Mínimo 6 caracteres</div>
                            <div id="req-upper" class="text-danger mb-1"><i class="bi bi-exclamation-circle"></i> Al menos una Mayúscula</div>
                            <div id="req-lower" class="text-danger mb-1"><i class="bi bi-exclamation-circle"></i> Al menos una Minúscula</div>
                            <div id="req-number" class="text-danger mb-1"><i class="bi bi-exclamation-circle"></i> Al menos un Número</div>
                            <div id="req-special" class="text-danger mb-1"><i class="bi bi-exclamation-circle"></i> Al menos un caracter especial (@$!%*?&)</div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light px-4 fw-bold rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-danger px-4 fw-bold" id="btnEnviarPasswordYa">ACTUALIZAR AHORA</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .toggle-password { top: 50%; right: 15px; transform: translateY(-50%); cursor: pointer; color: #ff4d4d; font-size: 1.2rem; z-index: 10; }
    .modal-pro { border-radius: 18px; border: 2px solid #ff4d4d; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
    /* Animación suave para la aparición de botones */
    .d-none { display: none !important; }
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // --- LÓGICA DE EDICIÓN SIN RECARGA ---
    const btnHabilitar = document.getElementById('btn-habilitar-edicion');
    const btnCancelar = document.getElementById('btn-cancelar-edicion');
    const groupRead = document.getElementById('group-read');
    const groupEdit = document.getElementById('group-edit');
    const inputsEditables = document.querySelectorAll('.editable-input');

    // Al hacer clic en Editar
    btnHabilitar.addEventListener('click', () => {
        groupRead.classList.add('d-none');
        groupEdit.classList.remove('d-none');
        inputsEditables.forEach(input => {
            input.readOnly = false;
            input.classList.remove('bg-light', 'border-0');
            input.classList.add('bg-white');
        });
    });

    // Al hacer clic en Cancelar
    btnCancelar.addEventListener('click', () => {
        groupRead.classList.remove('d-none');
        groupEdit.classList.add('d-none');
        inputsEditables.forEach(input => {
            input.readOnly = true;
            input.classList.add('bg-light', 'border-0');
            input.classList.remove('bg-white');
            // Opcional: Resetear valores si el usuario escribió algo y canceló
            input.value = input.defaultValue; 
        });
    });

    // --- LÓGICA DE PASSWORD Y MODALES (Resto de tus funciones originales) ---
    @if(session('cambio_obligatorio'))
        var modalElement = document.getElementById('modalPassword');
        if(modalElement) {
            var modalPassword = new bootstrap.Modal(modalElement, { backdrop: 'static', keyboard: false });
            modalPassword.show();
            modalElement.querySelector('.btn-light')?.remove();
            modalElement.querySelector('.btn-close')?.remove();
        }
    @endif

    function limpiarModales() {
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
    }

    // Toggle Password Visibility
    document.querySelectorAll('.toggle-password').forEach(btn => {
        btn.addEventListener('click', function() {
            const input = document.getElementById(this.dataset.target);
            const isPass = input.type === "password";
            input.type = isPass ? "text" : "password";
            this.classList.toggle('bi-eye-slash-fill', !isPass);
            this.classList.toggle('bi-eye-fill', isPass);
        });
    });

    const pass1 = document.getElementById('pass1');
    const pass2 = document.getElementById('pass2');
    const errorDiv = document.getElementById('passwordError');
    function validateRequirements() {
        const val = pass1.value;
        const checks = {
            'req-length': val.length >= 6,
            'req-upper': /[A-Z]/.test(val),
            'req-lower': /[a-z]/.test(val),
            'req-number': /[0-9]/.test(val),
            'req-special': /[@$!%*?&]/.test(val) 
        };

        let allValid = true;
        Object.keys(checks).forEach(id => {
            const el = document.getElementById(id);
            if (checks[id]) {
                el.style.display = 'none';
            } else {
                el.style.display = 'block';
                allValid = false;
            }
        });
        return allValid;
    }
    const validatePasswords = () => {
        if (pass2.value === "") { errorDiv.classList.add('d-none'); return; }
        errorDiv.classList.toggle('d-none', pass1.value === pass2.value);
    }
    pass1.addEventListener('input', () => {
        validateRequirements();
        validatePasswords();
    });
    
    pass2.addEventListener('input', validatePasswords);
    document.getElementById('btnEnviarPasswordYa').addEventListener('click', function() {
        if (pass1.value.length < 6) return showModalAlert("LA CONTRASEÑA DEBE TENER AL MENOS 6 CARACTERES");
        if (pass1.value !== pass2.value) return showModalAlert("LAS CONTRASEÑAS NO COINCIDEN");
        limpiarModales();
        document.getElementById('formPassword').submit();
    });

    const celularPerfil = document.getElementById('celular_perfil');
    
    if (celularPerfil) {
        celularPerfil.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length > 9) {
                this.value = this.value.slice(0, 9);
            }
        });

        celularPerfil.addEventListener('paste', function(e) {
            let pasteData = (e.clipboardData || window.clipboardData).getData('text');
            if (!/^\d+$/.test(pasteData)) {
                e.preventDefault();
            }
        });
    }
    @if(session('perfil_actualizado'))
        limpiarModales();
        showModalSuccess("{{ session('password_actualizado') ? 'CONTRASEÑA ACTUALIZADA EXITOSAMENTE' : 'PERFIL ACTUALIZADO CORRECTAMENTE' }}");
    @endif
});
</script>
@endpush
@endsection