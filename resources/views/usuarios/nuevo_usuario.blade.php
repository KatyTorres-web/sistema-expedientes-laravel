@extends('layouts.principal')

@section('content')
<div class="container-fluid py-4">
    <div class="text-center mb-4">
        <h1 class="text-dark-red display-4 mb-0 text-uppercase">Crear Cuenta</h1>
        <h4 class="text-dark-red fw-bold">Agregar nuevo usuario al sistema</h4>
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
    <form action="{{ route('usuarios.store') }}" method="POST" id="formNuevoUsuario" class="w-100 px-4" style="max-width: 1100px; margin: 0 auto;">
        @csrf
        
        <div class="row gx-5 gy-2">
            <!-- Usuario (Autogenerado) -->
            <div class="col-md-6">
                <div class="form-bubble shadow-sm">
                    <label class="fw-bold">Usuario</label>
                    <input type="text" name="username" id="username" class="form-control bg-white fw-bold text-primary" placeholder="Se generará automáticamente..." readonly required>
                </div>
            </div>

            <!-- Nombres -->
            <div class="col-md-6">
                <div class="form-bubble shadow-sm">
                    <label class="fw-bold">Nombres <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" id="nombre" class="form-control" placeholder="Ej: JUAN PABLO" required value="{{ old('nombre') }}" autocomplete="off">
                </div>
            </div>

            <!-- Apellidos -->
            <div class="col-md-6">
                <div class="form-bubble shadow-sm">
                    <label class="fw-bold">Apellidos <span class="text-danger">*</span></label>
                    <input type="text" name="apellido" id="apellido" class="form-control" placeholder="Ej: PEREZ GARCIA" required value="{{ old('apellido') }}" autocomplete="off">
                </div>
            </div>

            <!-- Teléfono -->
            <div class="col-md-6">
                <div class="form-bubble shadow-sm">
                    <label class="fw-bold">Teléfono / Celular</label>
                    <input type="text" name="celular" id="celular" class="form-control" placeholder="Ej: 987654321" value="{{ old('celular') }}" maxlength="9">
                </div>
            </div>

            <!-- Correo Electrónico -->
            <div class="col-md-6">
                <div class="form-bubble shadow-sm">
                    <label class="fw-bold">Correo Electrónico <span class="text-danger">*</span></label>
                    <input type="email" id="email" name="email" class="form-control" style="text-transform: none;" placeholder="usuario@institucion.gob.pe" required value="{{ old('email') }}">
                </div>
            </div>

            <!-- Área / Oficina -->
            <div class="col-md-6">
                <div class="form-bubble shadow-sm">
                    <label class="fw-bold">Área / Oficina <span class="text-danger">*</span></label>
                    <select name="subdivision_id" id="subdivision_id" class="form-select" required>
                        <option value="" disabled selected>Selecciona una dependencia...</option>
                        @foreach($subdivisiones as $sub)
                            <option value="{{ $sub->id_subdivision }}" {{ old('subdivision_id') == $sub->id_subdivision ? 'selected' : '' }}>
                                {{ $sub->nombre_subdivision }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Rol -->
            <div class="col-md-12">
                <div class="form-bubble shadow-sm">
                    <label class="mb-2 fw-bold">Rol de Usuario <span class="text-danger">*</span></label>
                    <div class="d-flex gap-4 ms-2">
                        @foreach($roles as $rol)
                        <div class="form-check d-flex align-items-center gap-2">
                            <input class="form-check-input" type="radio" name="rol_id" id="rol_{{ $rol->id_rol }}" value="{{ $rol->id_rol }}" required {{ old('rol_id') == $rol->id_rol ? 'checked' : '' }}>
                            <label class="form-check-label mb-0 fw-bold" for="rol_{{ $rol->id_rol }}">
                                {{ strtoupper($rol->nombre_rol) }}
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones Inferiores -->
        <div class="d-flex justify-content-between align-items-center mt-4 pb-4">
            <a href="{{ route('usuarios.index') }}" class="btn btn-cancelar-user shadow-sm">CANCELAR</a>
            <button type="submit" class="btn btn-registrar-user shadow px-5">REGISTRAR USUARIO</button>
            <div style="width: 100px;"></div> <!-- Equilibrio visual -->
        </div>
    </form>
</div>

<style>
    .form-bubble { background-color: rgba(255, 204, 204, 0.3); border-radius: 15px; padding: 12px 18px; margin-bottom: 10px; border: 1px solid rgba(255, 77, 77, 0.1); }
    .text-dark-red { color: #a40000; font-weight: 900; }
    .btn-registrar-user { background-color: #ff4d4d; color: white; border-radius: 30px; padding: 12px 40px; font-weight: 700; border: none; transition: 0.3s; }
    .btn-registrar-user:hover { background-color: #e60000; transform: scale(1.03); }
    .btn-cancelar-user { background-color: #ced4da; color: #333; border-radius: 30px; padding: 12px 30px; font-weight: 700; text-decoration: none; transition: 0.3s; }
    .btn-cancelar-user:hover { background-color: #b1b4b8; color: black; }
    .field-error { border: 2px solid #ff4d4d !important; background-color: #fff0f0 !important; }
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const nombreInput = document.getElementById('nombre');
    const apellidoInput = document.getElementById('apellido');
    const usernameInput = document.getElementById('username');
    const form = document.getElementById('formNuevoUsuario');

    // 1. GENERACIÓN DE USERNAME (Criterio 1)
    function generateUsername() {
        const nombreStr = nombreInput.value.trim();
        const apellidoStr = apellidoInput.value.trim();
        
        if (nombreStr === '' || apellidoStr === '') {
            usernameInput.value = '';
            return;
        }

        // Limpiar tildes y caracteres especiales
        const cleanText = (str) => str.normalize("NFD").replace(/[\u0300-\u036f]/g, "").replace(/[^a-zA-Z\s]/g, "");
        
        let nom = cleanText(nombreStr).split(' ')[0]; // Primer nombre
        let ape = cleanText(apellidoStr).split(' ')[0]; // Primer apellido

        // Lógica de generación: 2 letras de nombre + 2 letras de apellido + un random para evitar duplicados rápidos
        // Ejemplo: JUAN PEREZ -> JuPe_82
        let base = nom.substring(0, 2) + ape.substring(0, 2);
        
        // Mantener el mismo formato (ej. Mayúscula al inicio de cada sílaba)
        base = base.charAt(0).toUpperCase() + base.slice(1).toLowerCase();

        // Si el valor ya existe o estamos escribiendo, le asignamos un número fijo basado en la longitud 
        // para que no cambie en cada pulsación de tecla, pero sea único.
        let seed = (nombreStr.length + apellidoStr.length);
        usernameInput.value = base + "." + seed + Math.floor(Math.random() * 10);
    }
    const celularInput = document.getElementById('celular');

    celularInput.addEventListener('input', function() {
        // Elimina cualquier caracter que no sea número
        this.value = this.value.replace(/[^0-9]/g, '');
        
        // Si por alguna razón pega un texto largo, lo corta en 9
        if (this.value.length > 9) {
            this.value = this.value.slice(0, 9);
        }
    });

    nombreInput.addEventListener('input', generateUsername);
    apellidoInput.addEventListener('input', generateUsername);

    // 2. VALIDACIÓN Y CONFIRMACIÓN
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        let missingFields = [];
        const requiredFields = [
            { id: 'nombre', label: 'Nombres' },
            { id: 'apellido', label: 'Apellidos' },
            { id: 'email', label: 'Correo Electrónico' },
            { id: 'subdivision_id', label: 'Área / Oficina' },
            { id: 'username', label: 'Usuario' }
        ];

        document.querySelectorAll('.form-control, .form-select').forEach(el => el.classList.remove('field-error'));

        requiredFields.forEach(field => {
            const el = document.getElementById(field.id);
            if (!el || !el.value || el.value.trim() === "") {
                if (el) el.classList.add('field-error');
                missingFields.push(field.label);
            }
        });

        const roles = document.getElementsByName('rol_id');
        let roleSelected = Array.from(roles).some(r => r.checked);
        if (!roleSelected) missingFields.push("Rol de Usuario");

        if (missingFields.length > 0) {
            showModalAlert("DEBE COMPLETAR LOS SIGUIENTES CAMPOS OBLIGATORIOS:\n- " + missingFields.join("\n- "));
            return;
        }

        // El username enviado será el que el usuario ve actualmente en el input
        showModalConfirm("¿ESTÁ SEGURO DE REGISTRAR AL USUARIO '" + usernameInput.value + "' EN EL SISTEMA?", () => {
            form.submit();
        });
    });
});
</script>
@endpush
@endsection