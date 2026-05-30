<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpedienteController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\HistorialController;

// ==========================================
// RUTA RAÍZ (Redirección inteligente)
// ==========================================
Route::get('/ping', function () {
    return response()->json([
    'status' => 'ok',
    'timestamp' => now()
    ]);
});

Route::get('/', function () {
    if (auth()->check()) {
        return auth()->user()->rol_id == 1 
            ? redirect()->route('dashboard.admin') 
            : redirect()->route('dashboard.analista');
    }
    return redirect()->route('login');
});

// ==========================================
// RUTAS PÚBLICAS (Solo para invitados)
// ==========================================
Route::middleware(['guest'])->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    // Recuperación de contraseña
    Route::get('/password/restablecer', [AuthController::class, 'showResetPassword'])->name('password.request');
    Route::post('/password/email', [AuthController::class, 'sendResetLinkSimple'])->name('password.email');
    Route::get('/password/nueva', [AuthController::class, 'showNewPasswordForm'])->name('password.nueva');
    Route::post('/password/nueva', [AuthController::class, 'updatePassword'])->name('password.update');
});

// ==========================================
// RUTAS PROTEGIDAS (Solo usuarios logueados)
// ==========================================
Route::middleware(['auth', 'prevent-back'])->group(function () {

    // 1. RUTAS DE PERFIL Y SALIDA (Accesibles siempre, incluso en el primer login)
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/perfil', [UsuarioController::class, 'perfil'])->name('perfil');
    Route::put('/perfil/update', [UsuarioController::class, 'updatePerfil'])->name('perfil.update');
    Route::put('/perfil/password', [UsuarioController::class, 'updatePassword'])->name('perfil.password');

    // 2. RUTAS PROTEGIDAS POR CAMBIO DE CONTRASEÑA (Middleware first.login)
    Route::middleware(['first.login'])->group(function () {
        
        // Redirección inicial según rol
        Route::get('/principal', function() {
            return auth()->user()->rol_id == 1 
                ? redirect()->route('dashboard.admin') 
                : redirect()->route('dashboard.analista');
        })->name('principal');

        // DASHBOARDS
        Route::get('/dashboard/admin', [DashboardController::class, 'admin'])->name('dashboard.admin');
        Route::get('/dashboard/analista', [DashboardController::class, 'analista'])->name('dashboard.analista');

        // EXPEDIENTES 
        // Nota: Las rutas específicas deben ir ANTES del Resource
        Route::get('/expedientes/proximo-id', [ExpedienteController::class, 'getProximoId'])->name('expedientes.proximoId');
        Route::get('/expedientes-export', [ExpedienteController::class, 'export'])->name('expedientes.export');
        Route::get('/expedientes/sugerir-descripciones', [ExpedienteController::class, 'sugerirDescripciones'])->name('expedientes.sugerir.descripciones');
        Route::post('/expedientes/recomendar-analista', [ExpedienteController::class, 'recomendarAnalista'])->name('expedientes.recomendar.analista');
        Route::post('/expedientes/categorizar-rechazo', [ExpedienteController::class, 'categorizarMotivoRechazo'])->name('expedientes.categorizar.rechazo');
        Route::get('/expedientes/{id}/auditar-devoluciones', [ExpedienteController::class, 'auditarDevoluciones'])->name('expedientes.auditar.devoluciones');
        
        // --- RUTA INTEGRADA: SUGERENCIA DE RECHAZO IA ---
        Route::post('/expedientes/{id}/ia-sugerir-rechazo', [ExpedienteController::class, 'obtenerSugerenciaRechazo'])->name('expedientes.ia.rechazo');
        Route::post('/expedientes/analizar-descripcion-ia', [ExpedienteController::class, 'analizarDescripcionIA'])->name('expedientes.ia.analizar');

        Route::resource('expedientes', ExpedienteController::class);
        
        // GESTIÓN DE ÁREAS (Usuarios)
        Route::get('/usuarios/areas', [AreaController::class, 'gestionAreas'])->name('usuarios.areas');
        Route::post('/usuarios/areas', [AreaController::class, 'storeArea'])->name('usuarios.areas.store');
        Route::put('/usuarios/areas/{id}', [AreaController::class, 'updateArea'])->name('usuarios.areas.update');
        
        // USUARIOS (Resource completo para el Admin)
        Route::resource('usuarios', UsuarioController::class);

        // HISTORIAL (Solo Administrador según tu controlador)
        Route::middleware(['can:admin-only'])->group(function () {
            Route::get('/historial', [HistorialController::class, 'index'])->name('historial.index');
        });
    });
});