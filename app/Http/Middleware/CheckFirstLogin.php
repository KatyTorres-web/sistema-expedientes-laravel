<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckFirstLogin
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && Auth::user()->primer_login) {
            // Excluimos la ruta de proximoId de la redirección
            if (!$request->routeIs('perfil') && 
                !$request->routeIs('perfil.password') && 
                !$request->routeIs('expedientes.proximoId')) { // <--- AGREGA ESTO
                return redirect()->route('perfil')->with('cambio_obligatorio', true);
            }
        }
        return $next($request);
    }
}