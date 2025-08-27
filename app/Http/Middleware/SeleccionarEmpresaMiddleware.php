<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SeleccionarEmpresaMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Si no se ha seleccionado empresa y no está accediendo a la pantalla de selección
        if (!session()->has('empresa_local_id') && !$request->is('seleccionar-empresa*')) {
            return redirect('/seleccionar-empresa'); // ✅ Corregido: evita usar el helper route()
        }

        return $next($request);
    }
}
