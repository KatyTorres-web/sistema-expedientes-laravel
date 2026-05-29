<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\Password; 
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;


class AuthController extends Controller
{
    // ===== LOGIN =====
    public function showLogin()
    {
        return view('autenticacion.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string'
        ]);

        $throttleKey = Str::lower($request->username) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            return back()->withErrors(['username' => 'Demasiados intentos fallidos.'])
                        ->with('lockout_time', RateLimiter::availableIn($throttleKey))
                        ->withInput();
        }

        // Criterio 1 y 2: Validar credenciales y estado ACTIVO (ID: 1)
        if (Auth::attempt([
            'username' => $request->username, 
            'password' => $request->password, 
            'estado_usuario_id' => 1 
        ])) {
            RateLimiter::clear($throttleKey);
            
            $user = Auth::user();
            
            // Criterio 3: Registrar fecha y hora de login
            $user->update(['ultimo_login' => now()]);

            $request->session()->regenerate();

            // Criterio: Redirigir al dashboard según su rol
            return redirect()->intended(route($user->dashboard_route_name));
        }

        RateLimiter::hit($throttleKey, 60);
        $intentos = RateLimiter::remaining($throttleKey, 3);

        return back()->withErrors([
            'username' => "Credenciales incorrectas o usuario inactivo. Intentos restantes: $intentos"
        ])->withInput();
    }

    // ===== MOSTRAR FORMULARIO RESTABLECER =====
    public function showResetPassword()
    {
        return view('autenticacion.restablecerpassword');
    }

    // ===== ENVIAR ENLACE SIMPLE =====
    public function sendResetLinkSimple(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $user = User::where('email', $request->email)
                ->where('estado_usuario_id', 1) 
                ->first();
        if (!$user) {
            return back()->withErrors([
                'email' => 'No se encuentra ningun usuario registrado con ese correo o se encuentra inactivo. Intente nuevamente'
            ]);
        }

        // Enlace directo al formulario de nueva contraseña
        $enlace = URL::temporarySignedRoute(
            'password.nueva', // Nombre de la ruta
            now()->addMinutes(10), // Tiempo de vida
            ['email' => $user->email] // Parámetro
        );
        // Enviar mensaje directo
        Mail::raw("Hola, haz clic en el siguiente enlace para cambiar tu contraseña: $enlace. Este enlace expirará en 10 minutos.", function($message) use ($user){
            $message->to($user->email)->subject('Recuperación de contraseña');
        });

        return back()->with('status', 'Se envió el enlace al correo registrado. Recuerda que solo es válido por 10 minutos.');
    }

    public function showNewPasswordForm(Request $request)
    {
        // Valida que la firma sea correcta y no haya expirado
        if (! $request->hasValidSignature()) {
            return redirect()->route('password.request')->withErrors([
                'email' => 'El enlace ha expirado o es inválido. Por favor, solicita uno nuevo.'
            ]);
        }

        return view('autenticacion.nueva-password');
    }

    // ===== ACTUALIZAR NUEVA CONTRASEÑA =====
    public function updatePassword(Request $request)
    {
        // Validar firma nuevamente en el POST
        if (! $request->hasValidSignature()) {
            return redirect()->route('password.request')->withErrors([
                'email' => 'El tiempo ha expirado. Debes solicitar un nuevo enlace.'
            ]);
        }

        $request->validate([
            'email' => 'required|email',
            'password' => [
                'required',
                'confirmed',
                Password::min(6) // Mínimo 6 caracteres
                    ->letters()   // Debe tener al menos una letra
                    ->mixedCase() // Debe tener mayúsculas y minúsculas
                    ->numbers()   // Debe tener al menos un número
                    ->symbols() // Opcional: añade esto si quieres pedir caracteres especiales (@$!%*?)
            ],
        ], [
            // Mensajes personalizados en español
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password' => 'La contraseña debe tener al menos 6 caracteres, incluir mayúsculas, minúsculas y números.'
        ]);

        $user = User::where('email', $request->email)
                ->where('estado_usuario_id', 1)
                ->first();

        if (!$user) {
            return back()->withErrors(['email' => 'No se puede actualizar la contraseña: el correo no existe o el usuario está inactivo.']);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        Auth::login($user);

        return redirect()->route('principal')->with('status', 'Contraseña actualizada correctamente.');
    }

    // ===== LOGOUT =====
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}