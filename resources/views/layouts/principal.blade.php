<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Expedientes - Administracion Tributaria</title>

    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Estilos locales -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <!-- <link rel="stylesheet" href="{{ asset('css/style2.css') }}"> -->

    <style>
        .modal { background: rgba(0,0,0,0.1); }
        .text-uppercase-custom { text-transform: uppercase; }
        body { transition: opacity 0.2s ease-in; }
    </style>
</head>
<body>

<div class="container-fluid main-container">
    <div class="row g-3 w-100 m-0 h-100">

        <!-- SIDEBAR -->
        <aside class="sidebar">
            @auth
                @php $rolId = auth()->user()->rol_id; @endphp

                @if($rolId == 1)
                    @include('layouts.partials._menu_admin')
                @elseif($rolId == 2)
                    @include('layouts.partials._menu_analista')
                @endif
                
                <form action="{{ route('logout') }}" method="POST" class="mt-auto">
                    @csrf
                    <button type="submit" class="menu-item w-100 border-0 bg-transparent text-danger">
                        <i class="bi bi-box-arrow-right"></i>
                        <span class="menu-text">Salir</span>
                    </button>
                </form>
            @endauth
        </aside>

        <!-- CONTENIDO DERECHO -->
        <div class="col d-flex flex-column overflow-hidden">

            <!-- HEADER -->
            <header class="header-box d-flex justify-content-end align-items-center">
                <div class="text-end me-3">
                    <strong class="text-danger d-block text-uppercase-custom" style="font-size: 1.1rem;">
                        {{ auth()->user()->rol->nombre_rol }}
                    </strong>
                    <span class="text-secondary small">
                        {{ auth()->user()->nombre }} {{ auth()->user()->apellido }}
                    </span>
                </div>
                <div class="user-avatar">
                    <img src="https://cdn-icons-png.flaticon.com/512/149/149071.png" width="50" height="50" alt="Avatar">
                </div>
            </header>

            <!-- CONTENIDO DINÁMICO -->
            <main class="content-box flex-grow-1 overflow-auto">
                @yield('content')
            </main>

        </div>
    </div>
</div>

<!-- MODALES REUTILIZABLES -->
<div class="modal fade" id="modalSuccess" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-body text-center p-5">
                <div class="mb-4">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                </div>
                <h2 class="fw-bold text-dark mb-2 text-uppercase" id="tituloSuccess">¡ÉXITO!</h2>
                <p class="text-secondary fs-5" id="mensajeSuccess"></p>
                <div class="mt-4">
                    <button type="button" class="btn btn-primary px-5 py-2 rounded-pill fw-bold" 
                            data-bs-dismiss="modal" style="background-color: #4da3ff; border:none;">
                        ACEPTAR
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAlerta" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-danger text-white border-0 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>ATENCIÓN</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <p id="mensajeAlerta" class="fs-5 text-secondary fw-bold"></p>
                <button type="button" class="btn btn-secondary px-4 rounded-pill" data-bs-dismiss="modal">ENTENDIDO</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalConfirmar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-body text-center p-5">
                <i class="bi bi-question-circle-fill text-warning mb-3" style="font-size: 4rem;"></i>
                <h3 class="fw-bold text-uppercase" id="tituloConfirmar">¿ESTÁS SEGURO?</h3>
                <p class="text-secondary" id="mensajeConfirmar"></p>
                <div class="d-flex gap-2 mt-4">
                    <button type="button" class="btn btn-light w-100 py-2 fw-bold rounded-pill" data-bs-dismiss="modal">CANCELAR</button>
                    <button type="button" id="btnConfirmarSi" class="btn btn-danger w-100 py-2 fw-bold rounded-pill">SÍ, CONTINUAR</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SCRIPTS (Carga diferida para no bloquear el renderizado) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Variables globales para instancias de modales
    let bsModalSuccess, bsModalAlerta, bsModalConfirmar;

    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar instancias una sola vez
        bsModalSuccess = new bootstrap.Modal(document.getElementById('modalSuccess'));
        bsModalAlerta = new bootstrap.Modal(document.getElementById('modalAlerta'));
        bsModalConfirmar = new bootstrap.Modal(document.getElementById('modalConfirmar'));

        // Disparadores automáticos de Laravel
        @if(session('success'))
            showModalSuccess("{!! session('success') !!}");
        @endif
        @if(session('error_modal'))
            showModalAlert("{!! session('error_modal') !!}");
        @endif
    });

    function showModalSuccess(mensaje, titulo = "¡ÉXITO!") {
        document.getElementById('tituloSuccess').innerText = titulo;
        document.getElementById('mensajeSuccess').innerHTML = mensaje;
        bsModalSuccess.show();
    }

    function showModalAlert(mensaje) {
        document.getElementById('mensajeAlerta').innerText = mensaje;
        bsModalAlerta.show();
    }

    function showModalConfirm(mensaje, callback) {
        document.getElementById('mensajeConfirmar').innerText = mensaje;
        const btnSi = document.getElementById('btnConfirmarSi');
        
        // Limpieza eficiente de eventos previos
        const newBtnSi = btnSi.cloneNode(true);
        btnSi.parentNode.replaceChild(newBtnSi, btnSi);
        
        newBtnSi.addEventListener('click', () => {
            bsModalConfirmar.hide();
            callback();
        });
        
        bsModalConfirmar.show();
    }
    // Busca todos los formularios y les pone autocomplete="off"
    document.querySelectorAll('form').forEach(form => {
        form.setAttribute('autocomplete', 'off');
    });

    // Como refuerzo, busca todos los inputs de texto y áreas de texto
    document.querySelectorAll('input, textarea').forEach(input => {
        input.setAttribute('autocomplete', 'off');
    });
</script>
@stack('scripts')
</body>
</html>