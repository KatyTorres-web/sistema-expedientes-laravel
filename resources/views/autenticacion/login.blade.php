<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">  
</head>
<body>

    <!-- TITULO SUPERIOR -->
    <div class="top-section">
        <h3>Bienvenido</h3>
        <h1>INICIAR SESION</h1>
    </div>

    <!-- CONTENIDO -->
    <div class="container login-container">
        <div class="row align-items-center w-100">

            <!-- IMAGEN -->
            <div class="col-lg-6 text-center">
                <img src="{{ asset('images/ilustracion-login.svg') }}" 
                    class="illustration" 
                    alt="Ilustración">
            </div>

            <!-- FORMULARIO -->
            <div class="col-lg-6 d-flex justify-content-center">
                <div class="login-card">

                    <h4>Ingresa tus credenciales</h4>

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <!-- MENSAJE DE ERROR GENERAL -->
                        @if ($errors->any())
                            <div class="alert alert-danger" id="error-container">
                                {{ $errors->first() }}
                                
                                {{-- Si existe bloqueo, mostramos el contador dinámico --}}
                                @if (session('lockout_time'))
                                    <br>
                                    <span class="fw-bold">
                                        Podrá intentar de nuevo en: 
                                        <span id="countdown-timer" style="color: #a40000; font-size: 1.1rem;">
                                            {{ session('lockout_time') }}
                                        </span> segundos.
                                    </span>
                                @endif
                            </div>
                        @endif

                        <!-- USUARIO -->
                        <div class="mb-3">
                            <label class="form-label">Usuario</label>
                            <input 
                                type="text" 
                                name="username" 
                                class="form-control @error('username') is-invalid @enderror"
                                placeholder="Ingresa tu usuario"
                                value="{{ old('username') }}"
                                required
                            >

                            @error('username')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <!-- CONTRASEÑA -->
                        <div class="mb-3">
                            <label class="form-label">Contraseña</label>

                            <div class="position-relative">
                                <input 
                                    type="password" 
                                    name="password" 
                                    id="password"
                                    class="form-control pe-5 @error('password') is-invalid @enderror"
                                    placeholder="************"
                                    required
                                >

                                <i class="bi bi-eye-slash-fill position-absolute"
                                id="togglePassword"
                                style="top: 50%; right: 15px; transform: translateY(-50%);
                                        cursor: pointer; color: #ff4d4d; font-size: 1.2rem;">
                                </i>
                            </div>

                            @error('password')
                                <div class="invalid-feedback d-block">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <!-- LINK RESTABLECER -->
                        <div class="text-end mb-3">
                            <a href="{{ route('password.request') }}" class="forgot-link">
                                ¿Olvidaste tu contraseña?
                            </a>
                        </div>

                        <!-- BOTON -->
                        <div class="d-grid">
                            <button type="submit" class="btn-login">
                                Ingresar
                            </button>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>
<script>
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');

    togglePassword.addEventListener('click', function () {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);

        this.classList.toggle('bi-eye-fill');
        this.classList.toggle('bi-eye-slash-fill');
    });
    document.addEventListener('DOMContentLoaded', function () {
        const timerElement = document.getElementById('countdown-timer');
        
        if (timerElement) {
            let seconds = parseInt(timerElement.textContent);
            const submitBtn = document.querySelector('.btn-login');
            const errorContainer = document.getElementById('error-container');

            // Deshabilitar botón de ingreso mientras dure el bloqueo
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.6';
                submitBtn.style.cursor = 'not-allowed';
            }

            const interval = setInterval(() => {
                seconds--;
                timerElement.textContent = seconds;

                // Cuando el tiempo llega a cero
                if (seconds <= 0) {
                    clearInterval(interval);
                    
                    // Ocultar el mensaje de error de bloqueo
                    if (errorContainer) {
                        errorContainer.style.transition = 'opacity 0.5s ease';
                        errorContainer.style.opacity = '0';
                        setTimeout(() => errorContainer.style.display = 'none', 500);
                    }

                    // Habilitar el botón de ingreso
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.style.opacity = '1';
                        submitBtn.style.cursor = 'pointer';
                    }
                }
            }, 1000); // Se ejecuta cada segundo
        }
    });
</script>
</body>
</html>