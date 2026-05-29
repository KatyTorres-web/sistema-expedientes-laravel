@extends('layouts.principal')

@section('content')
<div class="container-fluid p-0">
    
    <!-- BOTONES SUPERIORES -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="{{ route('usuarios.areas') }}" class="btn btn-gestion-areas shadow-sm">
            Gestión de áreas/oficinas
        </a>
        <a href="{{ route('usuarios.create') }}" class="btn btn-anadir-usuario shadow-sm">
            <i class="bi bi-person-plus-fill me-2"></i>Añadir usuario
        </a>
    </div>

    <!-- SECCIÓN DE FILTROS (Cuadro Azul) -->
    <div class="card border-blue-custom shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <form action="{{ route('usuarios.index') }}" method="GET" id="formFiltrosUsuarios">
                <input type="hidden" name="filtrado" value="1">

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-secondary">Búsqueda por nombre o apellido</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" name="buscar" class="form-control form-thin border-start-0 bg-light" placeholder="Buscar..." value="{{ request('buscar') }}">
                        </div>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label fw-bold small text-secondary">Estado:</label>
                        <select name="id_estado" class="form-select form-thin bg-light">
                            <option value="">TODOS</option>
                            @foreach($estados as $est)
                                <option value="{{ $est->id_estado_usuario }}" {{ request('id_estado') == $est->id_estado_usuario ? 'selected' : '' }}>
                                    {{ $est->estado_usuario }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-secondary">Área / Oficina:</label>
                        <select name="id_subdivision" class="form-select form-thin bg-light">
                            <option value="">TODAS LAS ÁREAS</option>
                            @foreach($subdivisiones as $sub)
                                <option value="{{ $sub->id_subdivision }}" {{ request('id_subdivision') == $sub->id_subdivision ? 'selected' : '' }}>
                                    {{ $sub->nombre_subdivision }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-secondary">Rol del usuario:</label>
                        <select name="id_rol" class="form-select form-thin bg-light">
                            <option value="">TODOS LOS ROLES</option>
                            @foreach($roles as $rol)
                                <option value="{{ $rol->id_rol }}" {{ request('id_rol') == $rol->id_rol ? 'selected' : '' }}>
                                    {{ $rol->nombre_rol }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="text-end mt-3">
                    <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm">
                        APLICAR FILTROS
                    </button>
                    <a href="{{ route('usuarios.index') }}" class="btn btn-secondary btn-sm px-4 ms-2 shadow-sm">
                        LIMPIAR FILTROS
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- TABLA DE USUARIOS -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold text-secondary mb-0">Lista de Usuarios Registrados</h5>
        @if(request()->filled('filtrado'))
            <span class="badge bg-info text-dark px-3 rounded-pill">Resultados filtrados</span>
        @endif
    </div>

    <div class="table-responsive">
        <table class="table custom-table shadow-sm rounded-3 overflow-hidden">
            <thead>
                <tr>
                    <th>NOMBRE COMPLETO</th>
                    <th>CORREO ELECTRÓNICO</th>
                    <th>ÁREA / OFICINA</th>
                    <th>ROL</th>
                    <th>ESTADO</th>
                    <th>ACCIONES</th>
                </tr>
            </thead>
            <tbody>
                @forelse($usuarios as $user)
                <tr>
                    <td class="text-start ps-5 fw-bold text-dark">
                        {{ $user->nombre }} {{ $user->apellido }}
                    </td>
                    <td style="text-transform: none;">{{ $user->email }}</td>
                    <td>
                        <span class="{{ !$user->subdivision ? 'text-muted italic' : '' }}">
                            {{ $user->subdivision->nombre_subdivision ?? 'SIN ÁREA ASIGNADA' }}
                        </span>
                    </td>
                    <td>
                        <span class="badge-rol {{ $user->rol_id == 1 ? 'rol-admin' : 'rol-analista' }}">
                            {{ $user->rol->nombre_rol }}
                        </span>
                    </td>
                    <td>
                        <span class="badge-estado-user {{ strtolower($user->estado->estado_usuario) == 'activo' ? 'user-activo' : 'user-inactivo' }}">
                            {{ $user->estado->estado_usuario }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('usuarios.show', $user->id) }}" class="btn btn-sm px-3 rounded-pill fw-bold shadow-sm" 
                        style="background-color: #ffe8f3; color: #d63384; border: 1px solid #ffc2df; transition: 0.3s;">
                            <i class="bi bi-eye-fill me-1"></i> Ver detalles
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-5 text-center">
                        <div class="d-flex flex-column align-items-center">
                            <i class="bi bi-person-exclamation text-secondary mb-2" style="font-size: 3rem;"></i>
                            <h5 class="text-secondary fw-bold">No se encontraron usuarios</h5>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('layouts.partials.pagination', ['paginator' => $usuarios])
</div>  

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    
    // Si existe la sesión de usuario_creado
    @if(session('usuario_creado'))
        let data = @json(session('usuario_creado'));
        
        // Obtenemos los elementos del modal que ya están en el Layout
        const titulo = document.getElementById('tituloSuccess');
        const mensaje = document.getElementById('mensajeSuccess');

        if(titulo && mensaje) {
            // Personalizamos el modal que el Layout está a punto de mostrar
            titulo.innerText = "¡REGISTRO EXITOSO!";
            mensaje.innerHTML = `
                <div class="bg-light p-3 rounded-3 border mt-3">
                    <p class="mb-1 text-secondary small fw-bold text-uppercase">Credenciales de acceso:</p>
                    <h5 class="mb-1"><strong>USUARIO:</strong> <span class="text-primary">${data.username}</span></h5>
                    <h5><strong>CONTRASEÑA:</strong> <span class="text-primary">${data.password}</span></h5>
                    <p class="text-danger small mt-2 mb-0 italic">* Se recomienda cambiar la contraseña en el primer inicio de sesión.</p>
                </div>
            `;
        }
    @endif
});
</script>
@endpush
@endsection