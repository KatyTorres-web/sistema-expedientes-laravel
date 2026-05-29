<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'prevent-back' => \App\Http\Middleware\PreventBackHistory::class,
            'first.login' => \App\Http\Middleware\CheckFirstLogin::class,
        ]);
            $middleware->trustProxies(at: '*'); // Confía en el proxy de Render
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
