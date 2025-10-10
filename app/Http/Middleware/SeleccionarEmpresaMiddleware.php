<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth; // ✅ IMPORTANTE

class SeleccionarEmpresaMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Rutas que no deben forzar selección de empresa
        $excepciones = [
            'login',
            'logout',
            'register',
            'password/*',
            'seleccionar-empresa*'
        ];

        // Permitir acceso si la ruta actual está en las excepciones
        foreach ($excepciones as $ruta) {
            if ($request->is($ruta)) {
                return $next($request);
            }
        }

        // Si el usuario está autenticado pero no ha seleccionado empresa
        if (Auth::check() && !session()->has('empresa_local_id')) {
            return redirect('/seleccionar-empresa');
        }

        // Si todo está correcto, continúa con la solicitud
        return $next($request);
    }
}
