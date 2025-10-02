<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\SeleccionarEmpresaMiddleware;
use App\Http\Controllers\{
    AuthController,
    Dashboard,
    EmpresaLocalController,
    EmpresaExternaController,
    UsuarioExternoController,
    AsesorController,
    ArlController,
    CajaController,
    DocumentoController,
    EpsController,
    FacturaController,
    PensionController,
    PeriodoUsuarioController,
    ProductoController,
    ReciboController,
    SubtipoCotizanteController,
    RemisionController,
    UsuarioExternoImportController,
    usuarios,
    ValorController,
    ArlUsuarioController,
    PlanesController,
    IncapacidadController,
    LiquidacionesExcelController
};





/*
|--------------------------------------------------------------------------
| RUTAS PÚBLICAS
|--------------------------------------------------------------------------
*/
Route::get('/', [AuthController::class, 'index'])->name('login');
Route::post('/logear', [AuthController::class, 'logear'])->name('logear');

// Selección de empresa (antes de acceder al sistema)
Route::get('/seleccionar-empresa', [App\Http\Controllers\EmpresaController::class, 'seleccionar'])
    ->name('seleccionar.empresa');
Route::post('/seleccionar-empresa', [App\Http\Controllers\EmpresaController::class, 'guardarSeleccion'])
    ->name('guardar.empresa');

// Cambiar empresa desde el sistema
Route::get('/cambiar-empresa', function () {
    session()->forget('empresa_local_id');
    return redirect()->route('seleccionar.empresa');
})->name('cambiar.empresa');

/*
|--------------------------------------------------------------------------
| RUTAS AUTENTICADAS + EMPRESA SELECCIONADA
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', SeleccionarEmpresaMiddleware::class])->group(function () {

    Route::get('/home', [Dashboard::class, 'index'])->name('home');
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

    // ==== EXCEL (Liquidaciones y usuarios vigentes)
    Route::get('/excel/liquidaciones', [LiquidacionesExcelController::class, 'descargar'])
        ->name('excel.liquidaciones.descargar');

    Route::get('/excel/usuarios-vigentes', [\App\Http\Controllers\UsuariosVigentesExcelController::class, 'descargar'])
        ->name('excel.usuarios_vigentes.descargar');

    // ==== APIs de búsqueda rápidas
    Route::get('/remisiones/buscar-usuario/{numero}', [RemisionController::class, 'buscarUsuario']);
    Route::get('/recibos/buscar-usuario/{numero?}', [ReciboController::class, 'buscarUsuario']);

    // ==== Periodos
    Route::get('/periodos', [PeriodoUsuarioController::class, 'index'])->name('periodos.index');
    Route::get('/periodos/pendientes', [PeriodoUsuarioController::class, 'pendientes'])->name('periodos.pendientes');

    // ==== Recibos: retiros masivos (form + export)
    Route::prefix('recibos')->group(function () {
        Route::get('/retiros-masivos', [ReciboController::class, 'retirosMasivosForm'])
            ->name('recibos.retirosMasivos.form');
        Route::post('/retiros-masivos', [ReciboController::class, 'retirosMasivosExport'])
            ->name('recibos.retirosMasivos.export');
    });
     
     
    // ============================================================
    // ========== EXPORTACIONES (RUTAS CORRECTAS) =================
    // ============================================================
    // Ver historial
    Route::get('/exportaciones', [\App\Http\Controllers\ExportBatchController::class, 'index'])
        ->name('exportaciones.index');

    // Descargar Excel desde un lote existente
    Route::get('/exportaciones/{batch}/descargar', [LiquidacionesExcelController::class, 'descargarLote'])
        ->name('exportaciones.descargar');

    // Descargar ZIP por caja desde un lote existente
    Route::get('/exportaciones/{batch}/descargar-por-caja', [LiquidacionesExcelController::class, 'descargarPorCajaLote'])
        ->name('exportaciones.descargarPorCajaLote');

    // Preparar (crear lote y marcar recibos; NO descarga)
    Route::post('/exportaciones/preparar-por-caja', [LiquidacionesExcelController::class, 'prepararExportacionPorCaja'])
        ->name('exportaciones.prepararPorCaja');

    // Descargar por caja directo (crea lote, marca y descarga ZIP)
    Route::post('/exportaciones/descargar-por-caja', [LiquidacionesExcelController::class, 'descargarPorCaja'])
        ->name('exportaciones.descargarPorCaja');
    // ============================================================

    /*
    |--------------------------------------------------------------------------
    | USUARIOS
    |--------------------------------------------------------------------------
    */
    Route::prefix('usuarios')->group(function () {
        Route::get('/', [usuarios::class, 'index'])->name('usuarios');
        Route::get('/create', [usuarios::class, 'create'])->name('usuarios.create');
        Route::post('/store', [usuarios::class, 'store'])->name('usuarios.store');
        Route::get('/show/{id}', [usuarios::class, 'show'])->name('usuarios.show');
        Route::delete('/destroy/{id}', [usuarios::class, 'destroy'])->name('usuarios.destroy');
        Route::get('/edit/{id}', [usuarios::class, 'edit'])->name('usuarios.edit');
        Route::put('/update/{id}', [usuarios::class, 'update'])->name('usuarios.update');
        Route::get('/tbody', [usuarios::class, 'tbody'])->name('usuarios.tbody');
        Route::get('/cambiar-estado/{id}/{estado}', [usuarios::class, 'estado'])->name('usuarios.estado');
        Route::get('/cambiar-password/{id}/{password}', [usuarios::class, 'cambio_password'])->name('usuarios.password');
    });

    /*
    |--------------------------------------------------------------------------
    | DOCUMENTOS
    |--------------------------------------------------------------------------
    */
    Route::prefix('documentos')->group(function () {
        Route::get('/', [DocumentoController::class, 'index'])->name('documentos');
        Route::get('/create', [DocumentoController::class, 'create'])->name('documentos.create');
        Route::post('/store', [DocumentoController::class, 'store'])->name('documentos.store');
        Route::get('/show/{documento}', [DocumentoController::class, 'show'])->name('documentos.show');
        Route::delete('/destroy/{documento}', [DocumentoController::class, 'destroy'])->name('documentos.destroy');
        Route::get('/edit/{documento}', [DocumentoController::class, 'edit'])->name('documentos.edit');
        Route::put('/update/{documento}', [DocumentoController::class, 'update'])->name('documentos.update');
    });

    /*
    |--------------------------------------------------------------------------
    | EMPRESA LOCAL
    |--------------------------------------------------------------------------
    */
    Route::prefix('empresa_local')->group(function () {
        Route::get('/', [EmpresaLocalController::class, 'index'])->name('empresa_local');
        Route::get('/create', [EmpresaLocalController::class, 'create'])->name('empresa_local.create');
        Route::post('/store', [EmpresaLocalController::class, 'store'])->name('empresa_local.store');
        Route::get('/show/{empresa}', [EmpresaLocalController::class, 'show'])->name('empresa_local.show');
        Route::delete('/destroy/{empresa}', [EmpresaLocalController::class, 'destroy'])->name('empresa_local.destroy');
        Route::get('/edit/{empresa}', [EmpresaLocalController::class, 'edit'])->name('empresa_local.edit');
        Route::put('/update/{empresa}', [EmpresaLocalController::class, 'update'])->name('empresa_local.update');
    });

    /*
    |--------------------------------------------------------------------------
    | ARLS
    |--------------------------------------------------------------------------
    */
    Route::prefix('arls')->group(function () {
        Route::get('/', [ArlController::class, 'index'])->name('arls');
        Route::get('/create', [ArlController::class, 'create'])->name('arls.create');
        Route::post('/store', [ArlController::class, 'store'])->name('arls.store');
        Route::get('/show/{arl}', [ArlController::class, 'show'])->name('arls.show');
        Route::delete('/destroy/{arl}', [ArlController::class, 'destroy'])->name('arls.destroy');
        Route::get('/edit/{arl}', [ArlController::class, 'edit'])->name('arls.edit');
        Route::put('/update/{arl}', [ArlController::class, 'update'])->name('arls.update');
    });

    /*
    |--------------------------------------------------------------------------
    | EPS
    |--------------------------------------------------------------------------
    */
    Route::prefix('eps')->group(function () {
        Route::get('/', [EpsController::class, 'index'])->name('eps');
        Route::get('/create', [EpsController::class, 'create'])->name('eps.create');
        Route::post('/store', [EpsController::class, 'store'])->name('eps.store');
        Route::get('/show/{eps}', [EpsController::class, 'show'])->name('eps.show');
        Route::delete('/destroy/{eps}', [EpsController::class, 'destroy'])->name('eps.destroy');
        Route::get('/edit/{eps}', [EpsController::class, 'edit'])->name('eps.edit');
        Route::put('/update/{eps}', [EpsController::class, 'update'])->name('eps.update');
    });

    /*
    |--------------------------------------------------------------------------
    | PENSIONES
    |--------------------------------------------------------------------------
    */
    Route::prefix('pensions')->group(function () {
        Route::get('/', [PensionController::class, 'index'])->name('pensions');
        Route::get('/create', [PensionController::class, 'create'])->name('pensions.create');
        Route::post('/store', [PensionController::class, 'store'])->name('pensions.store');
        Route::get('/show/{pension}', [PensionController::class, 'show'])->name('pensions.show');
        Route::delete('/destroy/{pension}', [PensionController::class, 'destroy'])->name('pensions.destroy');
        Route::get('/edit/{pension}', [PensionController::class, 'edit'])->name('pensions.edit');
        Route::put('/update/{pension}', [PensionController::class, 'update'])->name('pensions.update');
    });

    /*
    |--------------------------------------------------------------------------
    | CAJAS
    |--------------------------------------------------------------------------
    */
    Route::prefix('cajas')->group(function () {
        Route::get('/', [CajaController::class, 'index'])->name('cajas');
        Route::get('/create', [CajaController::class, 'create'])->name('cajas.create');
        Route::post('/store', [CajaController::class, 'store'])->name('cajas.store');
        Route::get('/edit/{caja}', [CajaController::class, 'edit'])->name('cajas.edit');
        Route::put('/update/{caja}', [CajaController::class, 'update'])->name('cajas.update');
        Route::delete('/destroy/{caja}', [CajaController::class, 'destroy'])->name('cajas.destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | EMPRESAS EXTERNAS
    |--------------------------------------------------------------------------
    */
    Route::prefix('empresa_externas')->group(function () {
        Route::get('/', [EmpresaExternaController::class, 'index'])->name('empresa_externas');
        Route::get('/create', [EmpresaExternaController::class, 'create'])->name('empresa_externas.create');
        Route::post('/store', [EmpresaExternaController::class, 'store'])->name('empresa_externas.store');
        Route::get('/show/{empresa_externa}', [EmpresaExternaController::class, 'show'])->name('empresa_externas.show');
        Route::delete('/destroy/{empresa_externa}', [EmpresaExternaController::class, 'destroy'])->name('empresa_externas.destroy');
        Route::get('/edit/{empresa_externa}', [EmpresaExternaController::class, 'edit'])->name('empresa_externas.edit');
        Route::put('/update/{empresa_externa}', [EmpresaExternaController::class, 'update'])->name('empresa_externas.update');
    });

    /*
    |--------------------------------------------------------------------------
    | USUARIOS EXTERNOS
    |--------------------------------------------------------------------------
    */
    Route::prefix('usuario_externos')->group(function () {
        Route::get('/', [UsuarioExternoController::class, 'index'])->name('usuario_externos');
        Route::get('/create', [UsuarioExternoController::class, 'create'])->name('usuario_externos.create');
        Route::post('/store', [UsuarioExternoController::class, 'store'])->name('usuario_externos.store');
        Route::get('/show/{usuario_externo}', [UsuarioExternoController::class, 'show'])->name('usuario_externos.show');
        Route::get('/edit/{usuario_externo}', [UsuarioExternoController::class, 'edit'])->name('usuario_externos.edit');
        Route::put('/update/{usuario_externo}', [UsuarioExternoController::class, 'update'])->name('usuario_externos.update');
        Route::delete('/destroy/{usuario_externo}', [UsuarioExternoController::class, 'destroy'])->name('usuario_externos.destroy');

        Route::get('/activos', [UsuarioExternoController::class, 'activos'])->name('usuario_externos.activos');
        Route::post('/import-csv', [UsuarioExternoController::class, 'importCsv'])->name('usuario_externos.importCsv');

        // Descargar plantilla de importación
        Route::get('/plantilla', [UsuarioExternoImportController::class, 'downloadTemplate'])
            ->name('usuario_externos.template');

        Route::get('/usuario_externos/import', [UsuarioExternoImportController::class, 'showForm'])
    ->name('usuario_externos.import');
Route::post('/usuario_externos/import', [UsuarioExternoImportController::class, 'import'])
    ->name('usuario_externos.import.do');
    
    });

    /*
    |--------------------------------------------------------------------------
    | REMISIONES
    |--------------------------------------------------------------------------
    */
    Route::prefix('remisiones')->group(function () {
        Route::get('/', [RemisionController::class, 'index'])->name('remisiones');
        Route::get('/create', [RemisionController::class, 'create'])->name('remisiones.create');
        Route::post('/store', [RemisionController::class, 'store'])->name('remisiones.store');
        Route::get('/show/{remision}', [RemisionController::class, 'show'])->name('remisiones.show');
        Route::delete('/destroy/{remision}', [RemisionController::class, 'destroy'])->name('remisiones.destroy');
        Route::get('/edit/{remision}', [RemisionController::class, 'edit'])->name('remisiones.edit');
        Route::put('/update/{remision}', [RemisionController::class, 'update'])->name('remisiones.update');
        Route::get('/{id}/imprimir', [RemisionController::class, 'imprimir'])->name('remisiones.imprimir');
        Route::get('/api/por-periodo', [RemisionController::class, 'apiListByPeriod'])
            ->name('remisiones.api.period');
    });

    /*
    |--------------------------------------------------------------------------
    | FACTURAS
    |--------------------------------------------------------------------------
    */
    Route::prefix('facturas')->group(function () {
        Route::get('/', [FacturaController::class, 'index'])->name('facturas');
        Route::get('/create', [FacturaController::class, 'create'])->name('facturas.create');
        Route::post('/store', [FacturaController::class, 'store'])->name('facturas.store');
        Route::get('/show/{factura}', [FacturaController::class, 'show'])->name('facturas.show');
        Route::get('/edit/{factura}', [FacturaController::class, 'edit'])->name('facturas.edit');
        Route::put('/update/{factura}', [FacturaController::class, 'update'])->name('facturas.update');
        Route::delete('/destroy/{factura}', [FacturaController::class, 'destroy'])->name('facturas.destroy');
        Route::get('/{id}/imprimir', [FacturaController::class, 'imprimir'])->name('facturas.imprimir');
    });

    /*
    |--------------------------------------------------------------------------
    | PRODUCTOS
    |--------------------------------------------------------------------------
    */
    Route::prefix('productos')->group(function () {
        Route::get('/', [ProductoController::class, 'index'])->name('productos');
        Route::get('/create', [ProductoController::class, 'create'])->name('productos.create');
        Route::post('/store', [ProductoController::class, 'store'])->name('productos.store');
        Route::get('/show/{producto}', [ProductoController::class, 'show'])->name('productos.show');
        Route::get('/edit/{producto}', [ProductoController::class, 'edit'])->name('productos.edit');
        Route::put('/update/{producto}', [ProductoController::class, 'update'])->name('productos.update');
        Route::delete('/destroy/{producto}', [ProductoController::class, 'destroy'])->name('productos.destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | RECIBOS (CRUD principal)
    |--------------------------------------------------------------------------
    */
    Route::prefix('recibos')->group(function () {
        Route::get('/', [ReciboController::class, 'index'])->name('recibos');
        Route::get('/create', [ReciboController::class, 'create'])->name('recibos.create');
        Route::post('/store', [ReciboController::class, 'store'])->name('recibos.store');
        Route::get('/show/{recibo}', [ReciboController::class, 'show'])->name('recibos.show');
        Route::get('/edit/{recibo}', [ReciboController::class, 'edit'])->name('recibos.edit');
        Route::put('/update/{recibo}', [ReciboController::class, 'update'])->name('recibos.update');
        Route::delete('/destroy/{recibo}', [ReciboController::class, 'destroy'])->name('recibos.destroy');
        Route::get('/{id}/imprimir', [ReciboController::class, 'imprimir'])->name('recibos.imprimir');

        // (Si aún los usas)
        Route::post('/exportar-pendientes', [ReciboController::class, 'exportarPendientes'])->name('recibos.exportarPendientes');
        Route::get('/exportar-lote/{batch}', [ReciboController::class, 'descargarLote'])->name('recibos.descargarLote');
        Route::get('/recibos/pendientes', [ReciboController::class, 'pendientes'])->name('recibos.pendientes');
    });

    /*
    |--------------------------------------------------------------------------
    | VALORES
    |--------------------------------------------------------------------------
    */
    Route::prefix('valores')->group(function () {
        Route::get('/valores', [ValorController::class, 'index'])->name('valores.index');
        Route::get('/valores/crear', [ValorController::class, 'create'])->name('valores.create');
        Route::post('/valores', [ValorController::class, 'store'])->name('valores.store');
        Route::get('/valores/{valor}/editar', [ValorController::class, 'edit'])->name('valores.edit');
        Route::put('/valores/{valor}', [ValorController::class, 'update'])->name('valores.update');
        Route::delete('/valores/{valor}', [ValorController::class, 'destroy'])->name('valores.destroy');
    });
    Route::prefix('asesores')->group(function () {
    Route::get('/asesores', [AsesorController::class, 'index'])->name('asesores');
    Route::get('/asesores/create', [AsesorController::class, 'create'])->name('asesores.create');
    Route::post('/asesores', [AsesorController::class, 'store'])->name('asesores.store');
    Route::get('/asesores/{asesor}/edit', [AsesorController::class, 'edit'])->name('asesores.edit');
    Route::put('/asesores/{asesor}', [AsesorController::class, 'update'])->name('asesores.update');
    Route::delete('/asesores/{asesor}', [AsesorController::class, 'destroy'])->name('asesores.destroy');
});
Route::prefix('subtipos')->group(function () {
    Route::get('/', [SubtipoCotizanteController::class, 'index'])->name('subtipo_cotizantes');
    Route::get('/create', [SubtipoCotizanteController::class, 'create'])->name('subtipo_cotizantes.create');
    Route::post('/', [SubtipoCotizanteController::class, 'store'])->name('subtipo_cotizantes.store');
    Route::get('/{subtipo_cotizante}/edit', [SubtipoCotizanteController::class, 'edit'])->name('subtipo_cotizantes.edit');
    Route::put('/{subtipo_cotizante}', [SubtipoCotizanteController::class, 'update'])->name('subtipo_cotizantes.update');
    Route::delete('/{subtipo_cotizante}', [SubtipoCotizanteController::class, 'destroy'])->name('subtipo_cotizantes.destroy');
});

// Incapacidades
Route::prefix('incapacidades')->name('incapacidades.')->group(function () {
    Route::get('/', [IncapacidadController::class, 'index'])->name('index');
    Route::get('/crear', [IncapacidadController::class, 'create'])->name('create');
    Route::post('/guardar', [IncapacidadController::class, 'store'])->name('store');
    Route::get('/{incapacidad}/editar', [IncapacidadController::class, 'edit'])->name('edit');
    Route::put('/{incapacidad}', [IncapacidadController::class, 'update'])->name('update');
    Route::delete('/{incapacidad}', [IncapacidadController::class, 'destroy'])->name('destroy');

    // Acciones adicionales
    Route::post('/{incapacidad}/cerrar', [IncapacidadController::class, 'cerrar'])->name('cerrar');
    Route::post('/buscar-usuario', [IncapacidadController::class, 'buscarUsuario'])->name('buscarUsuario');
    Route::post('/{incapacidad}/observaciones', [IncapacidadController::class, 'agregarObservacion'])->name('observaciones.agregar');
});

Route::get('/planes', [PlanesController::class, 'index'])->name('planes.index');



Route::prefix('arl-usuarios')
    ->as('arl-usuarios.')
    ->group(function () {
        Route::get('/',               [ArlUsuarioController::class, 'index'])->name('index');
        Route::get('/create',         [ArlUsuarioController::class, 'create'])->name('create');
        Route::post('/',              [ArlUsuarioController::class, 'store'])->name('store');
        Route::get('/{arlUsuario}/edit', [ArlUsuarioController::class, 'edit'])->name('edit');
        Route::put('/{arlUsuario}',   [ArlUsuarioController::class, 'update'])->name('update');
        Route::delete('/{arlUsuario}',[ArlUsuarioController::class, 'destroy'])->name('destroy');
    });


});
