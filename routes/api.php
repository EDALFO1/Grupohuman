<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Puedes agregar tus rutas API aquí si las necesitas
Route::get('/ping', function () {
    return response()->json(['message' => 'pong']);
});
