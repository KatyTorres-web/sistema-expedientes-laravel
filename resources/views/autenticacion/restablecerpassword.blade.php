<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer Contraseña</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>

    <div class="top-section">
        <h1>RESTABLECER CONTRASEÑA</h1>
    </div>

    <div class="container login-container">
        <div class="login-card reset-card">

            <p>
                Ingresa tu correo electrónico para restablecer tu contraseña
            </p>

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                @if (session('status'))
                    <div class="alert alert-success">
                        {{ session('status') }}
                    </div>
                @endif

                @error('email')
                    <div class="alert alert-danger">
                        {{ $message }}
                    </div>
                @enderror

                <input type="email" 
                       name="email" 
                       class="form-control mb-3" 
                       placeholder="Ingresa tu correo electrónico..."
                       required>

                <div class="d-grid">
                    <button type="submit" class="btn-login">
                        Restablecer contraseña
                    </button>
                </div>

                <div class="text-center mt-3">
                    <a href="{{ route('login') }}" class="btn-cancelar text-decoration-none">
                        Cancelar
                    </a>
                </div>

            </form>
        </div>
    </div>

</body>
</html>