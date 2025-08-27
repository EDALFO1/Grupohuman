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
    ->withMiddleware(function () {
        return [
            \App\Http\Middleware\SeleccionarEmpresaMiddleware::class,
        ];
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })

    ->withCommands([
    // puedes registrar más comandos aquí
    \App\Console\Commands\BackfillPeriodoUsuarios::class,
    ])

    ->create();
    
    
