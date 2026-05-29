@extends('layouts.principal')

@section('content')
<div class="container-fluid p-0">
    <!-- ENCABEZADO -->
    <div class="d-flex justify-content-between align-items-center mb-2">
        <a href="{{ route('usuarios.index') }}" class="text-dark fs-3 text-decoration-none">
            <i class="bi bi-arrow-left-circle"></i>
        </a>
        <h2 class="fw-bold mb-0">Detalles del usuario</h2>
        <a href="{{ route('usuarios.edit', $usuario->id) }}" class="btn btn-primary-custom shadow-sm px-4 rounded-3 fw-bold">
            EDITAR
        </a>
    </div>

    <!-- CUADRO PRINCIPAL (Marco Azul) -->
    <div class="card border-blue-custom rounded-4 shadow-sm">
        <div class="card-body p-4">
            
            <!-- IDENTIFICADOR SUPERIOR -->
            <div class="row mb-4 text-center">
                <div class="col-md-12">
                    <label class="label-small d-block text-secondary mb-1 text-uppercase">Usuario</label>
                    <div class="badge-header-blue py-2 shadow-sm fs-4">{{ $usuario->username }}</div>
                </div>
            </div>

            <!-- SECCIÓN: DATOS PERSONALES -->
            <div class="mb-5">
                <h6 class="fw-bold border-bottom border-primary border-2 d-inline-block pb-1 mb-4 text-uppercase">Información Personal</h6>
                
                <div class="row g-3">
                    <div class="col-md-6 d-flex align-items-center">
                        <label class="fw-bold label-small" style="width: 150px;">NOMBRES:</label>
                        <input type="text" class="form-control form-thin bg-light border-0" value="{{ $usuario->nombre }}" readonly>
                    </div>
                    <div class="col-md-6 d-flex align-items-center">
                        <label class="fw-bold label-small text-end me-3" style="width: 150px;">APELLIDOS:</label>
                        <input type="text" class="form-control form-thin bg-light border-0" value="{{ $usuario->apellido }}" readonly>
                    </div>

                    <div class="col-md-6 d-flex align-items-center">
                        <label class="fw-bold label-small" style="width: 150px;">TELÉFONO:</label>
                        <input type="text" class="form-control form-thin bg-light border-0" value="{{ $usuario->celular ?? '---' }}" readonly>
                    </div>
                    <div class="col-md-6 d-flex align-items-center">
                        <label class="fw-bold label-small text-end me-3" style="width: 150px;">CORREO ELECTRÓNICO:</label>
                        <input type="text" class="form-control form-thin bg-light border-0" value="{{ $usuario->email }}" readonly style="text-transform: none;">
                    </div>
                </div>
            </div>

            <!-- SECCIÓN: DATOS LABORALES -->
            <div class="mb-4">
                <h6 class="fw-bold border-bottom border-primary border-2 d-inline-block pb-1 mb-4 text-uppercase">
                    Asignación Laboral
                </h6>

                <div class="row g-3">

                    <!-- AREA -->
                    <div class="col-md-12 d-flex align-items-center">
                        <label class="fw-bold label-small" style="width: 180px;">ÁREA / OFICINA:</label>
                        <input type="text" class="form-control form-thin bg-light border-0"
                            value="{{ $usuario->subdivision->nombre_subdivision }} ({{ $usuario->subdivision->cod_subdivision }})"
                            readonly>
                    </div>

                    <!-- ROL -->
                    <div class="col-md-6 d-flex align-items-center mt-3">
                        <label class="fw-bold label-small" style="width: 180px;">ROL:</label>

                        @php
                            $rolClass = $usuario->rol_id == 1 ? 'badge-registrado' : 'badge-devuelto';
                        @endphp

                        <div class="badge-status {{ $rolClass }} w-100 py-2 text-center">
                            {{ $usuario->rol->nombre_rol }}
                        </div>
                    </div>

                    <!-- ESTADO -->
                    <div class="col-md-6 d-flex align-items-center mt-3">
                        <label class="fw-bold label-small text-end me-3" style="width: 180px;">ESTADO:</label>

                        @php
                            $estadoClass = $usuario->estado_usuario_id == 1 ? 'badge-atendido' : 'badge-derivado';
                        @endphp

                        <div class="badge-status {{ $estadoClass }} w-100 py-2 text-center">
                            {{ $usuario->estado->estado_usuario }}
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>
@endsection