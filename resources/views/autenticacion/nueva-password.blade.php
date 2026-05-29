<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva Contraseña</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>

    <!-- TITULO SUPERIOR -->
    <div class="top-section">
        <h1>CAMBIAR CONTRASEÑA</h1>
    </div>

    <!-- CONTENIDO -->
    <div class="container login-container">

        <div class="login-card reset-card">

            <p>
                Ingresa tu nueva contraseña y confírmala
            </p>

            <form method="POST" action="{{ url()->full() }}">
                @csrf

                <!-- MENSAJES DE ERROR -->
                @if ($errors->any())
                    <div class="alert alert-danger">
                        {{ $errors->first() }}
                    </div>
                @endif

                <!-- EMAIL -->
                <div class="mb-3">
                    <input 
                        type="email" 
                        name="email"
                        class="form-control"
                        value="{{ request()->email }}"
                        readonly
                    >
                </div>

                <!-- NUEVA CONTRASEÑA -->
                <div class="mb-3">
                    <div class="position-relative">
                        <input 
                            type="password" 
                            name="password"
                            id="password"
                            class="form-control pe-5"
                            placeholder="Nueva contraseña"
                            required
                        >

                        <i class="bi bi-eye-slash-fill position-absolute"
                        id="togglePassword"
                        style="top: 50%; right: 15px; transform: translateY(-50%);
                                cursor: pointer; color: #ff4d4d; font-size: 1.2rem;">
                        </i>
                    </div>
                </div>

                <!-- CONFIRMAR CONTRASEÑA -->
                <div class="mb-3">
                    <div class="position-relative">
                        <input 
                            type="password" 
                            name="password_confirmation"
                            id="password_confirmation"
                            class="form-control pe-5"
                            placeholder="Confirmar nueva contraseña"
                            required
                        >

                        <i class="bi bi-eye-slash-fill position-absolute"
                        id="togglePasswordConfirm"
                        style="top: 50%; right: 15px; transform: translateY(-50%);
                                cursor: pointer; color: #ff4d4d; font-size: 1.2rem; transition: right 0.2s ease;">
                        </i>
                    </div>

                    <div id="passwordError" class="text-danger small mt-1" style="display: none;">
                        Las contraseñas no coinciden
                    </div>
                </div>

                <div class="mb-3" id="password-requirements" style="font-size: 0.85rem; text-align: left;">
                    <div id="req-length" class="text-danger mb-1"><i class="bi bi-exclamation-circle"></i> Mínimo 6 caracteres</div>
                    <div id="req-upper" class="text-danger mb-1"><i class="bi bi-exclamation-circle"></i> Al menos una Mayúscula</div>
                    <div id="req-lower" class="text-danger mb-1"><i class="bi bi-exclamation-circle"></i> Al menos una Minúscula</div>
                    <div id="req-number" class="text-danger mb-1"><i class="bi bi-exclamation-circle"></i> Al menos un Número</div>
                    <div id="req-special" class="text-danger mb-1"><i class="bi bi-exclamation-circle"></i> Al menos un caracter especial (@$!%*?&)</div>
                </div>

                <!-- BOTON -->
                <div class="d-grid">
                    <button type="submit" class="btn-login">
                        Guardar nueva contraseña
                    </button>
                </div>

                <!-- VOLVER A LOGIN -->
                <div class="text-center mt-3">
                    <a href="{{ route('login') }}" class="btn-cancelar text-decoration-none">
                        Cancelar
                    </a>
                </div>

            </form>

        </div>

    </div>
<script>
    const togglePassword = document.querySelector('#togglePassword');
    const togglePasswordConfirm = document.querySelector('#togglePasswordConfirm');
    const passwordInput = document.querySelector('#password');
    const confirmInput = document.querySelector('#password_confirmation');
    const errorDiv = document.querySelector('#passwordError');
    const toggleConfirmIcon = document.querySelector('#togglePasswordConfirm');

    // TOGGLE VISIBILIDAD NUEVA CONTRASEÑA
    togglePassword.addEventListener('click', function () {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.classList.toggle('bi-eye-fill');
        this.classList.toggle('bi-eye-slash-fill');
    });

    // TOGGLE VISIBILIDAD CONFIRMACIÓN
    togglePasswordConfirm.addEventListener('click', function () {
        const type = confirmInput.getAttribute('type') === 'password' ? 'text' : 'password';
        confirmInput.setAttribute('type', type);
        this.classList.toggle('bi-eye-fill');
        this.classList.toggle('bi-eye-slash-fill');
    });

    // FUNCIÓN: VALIDAR COINCIDENCIA
    function validatePasswords() {
        if (confirmInput.value === "") {
            errorDiv.style.display = "none";
            confirmInput.classList.remove('is-invalid');
            toggleConfirmIcon.style.right = "15px";
            return;
        }

        if (passwordInput.value !== confirmInput.value) {
            errorDiv.style.display = "block";
            confirmInput.classList.add('is-invalid');
            toggleConfirmIcon.style.right = "40px"; 
        } else {
            errorDiv.style.display = "none";
            confirmInput.classList.remove('is-invalid');
            toggleConfirmIcon.style.right = "15px";
        }
    }

    // FUNCIÓN: VALIDAR REQUISITOS (MAYUS, MINUS, NUMEROS)
    function validateRequirements() {
        const val = passwordInput.value;
        
        const checks = {
            'req-length': val.length >= 6,
            'req-upper': /[A-Z]/.test(val),
            'req-lower': /[a-z]/.test(val),
            'req-number': /[0-9]/.test(val),
            'req-special': /[@$!%*?&]/.test(val)
        };

        Object.keys(checks).forEach(id => {
            const el = document.getElementById(id);
            if (checks[id]) {
                // Si cumple, desaparece suavemente
                el.style.display = 'none';
            } else {
                // Si no cumple, se muestra
                el.style.display = 'block';
            }
        });
    }

    // AYUDANTE UI REQUISITOS
    function updateRequirementUI(id, isValid) {
        const el = document.getElementById(id);
        if (isValid) {
            el.classList.remove('text-danger');
            el.classList.add('text-success');
            el.querySelector('i').classList.replace('bi-x-circle', 'bi-check-circle-fill');
        } else {
            el.classList.remove('text-success');
            el.classList.add('text-danger');
            el.querySelector('i').classList.replace('bi-check-circle-fill', 'bi-x-circle');
        }
    }

    // LISTENERS
    passwordInput.addEventListener('keyup', () => {
        validateRequirements();
        validatePasswords();
    });
    confirmInput.addEventListener('keyup', validatePasswords);
</script>
</body>
</html>