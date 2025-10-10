<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

use App\Http\Middleware\SeleccionarEmpresaMiddleware;
use App\Http\Middleware\RolMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        // 🧱 Middleware global (se aplica a todas las rutas)
        $middleware->append([
            SeleccionarEmpresaMiddleware::class,
        ]);

        // 🏷️ Alias para middlewares personalizados
        $middleware->alias([
            'rol' => RolMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Aquí puedes personalizar la gestión de excepciones si lo necesitas
    })
    ->withCommands([
        // Puedes registrar más comandos aquí
        \App\Console\Commands\BackfillPeriodoUsuarios::class,
    ])
    ->create();
