<?php

use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\{
        AuthController, DashboardController, AdminController, UnidadMedidaController,
        TipoProductoController, ProductoController, ProductoProcesoController, ProcesoProduccionController,
        FormulaController, OperacionProduccionController, CentroTrabajoController,
    TrabajadorController, ProveedorController, ActividadProduccionController,
    MoldeController, ColorController, UsuarioController, CompraController,
    InventarioController, AlmacenController, RolController,
    OrdenProduccionController, OrdenProcesoController, ReporteController,
    ParametroSistemaController, GuiaRemisionCompraController,
    RequerimientoMaterialController, DespachoRequerimientoController,
    RutasProduccionController, TrazabilidadController, MapeoTerceroController,
    NotificacionController,
    PerfilController
};
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Web Routes - Leon Plast Producción
|--------------------------------------------------------------------------
*/

// Rutas para usuarios NO autenticados
Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::view('/forgot-password', 'auth.forgot-password')->name('password.request');
});

// Rutas Protegidas (Requieren Login)
Route::middleware('auth')->group(function () {
    
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Módulo de Notificaciones
    Route::get('/notificaciones', [NotificacionController::class, 'index'])->name('notificaciones.index');
    Route::get('/notificaciones/api/unread', [NotificacionController::class, 'getUnreadApi'])->name('notificaciones.api.unread');
    Route::get('/notificaciones/{id}', [NotificacionController::class, 'show'])->name('notificaciones.show');
    Route::post('/notificaciones/{id}/marcar-leida', [NotificacionController::class, 'marcarLeida'])->name('notificaciones.marcar_leida');
    Route::post('/notificaciones/marcar-todas-leidas', [NotificacionController::class, 'marcarTodasLeidas'])->name('notificaciones.marcar_todas_leidas');
    Route::delete('/notificaciones/{id}', [NotificacionController::class, 'destroy'])->name('notificaciones.destroy');

    // Módulo de Perfil de Usuario
    Route::get('/perfil', [PerfilController::class, 'index'])->name('perfil.index');
    Route::post('/perfil/password', [PerfilController::class, 'updatePassword'])->name('perfil.update_password');
    Route::post('/perfil/reportar-problema', [PerfilController::class, 'storeReporteProblema'])->name('perfil.store_reporte');

    Route::get('/productos/search-ajax', [ProductoController::class, 'searchAjax'])->name('api.productos.search');

    // =========================================================
    // MODULO ADMINISTRATIVO (Tablas Maestras)
    // =========================================================
    Route::middleware('role:Administrador')->prefix('admin')->group(function () {
        
        Route::get('/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');

        // Unidades de Medida
        Route::resource('unidades-medida', UnidadMedidaController::class)->names('unidades_medida')->parameters(['unidades-medida' => 'codigo']);
        
        // Tipos de Producto
        Route::resource('tipos-producto', TipoProductoController::class)->names('tipos_producto')->parameters(['tipos-producto' => 'codigo']);
        
        // Productos
        Route::resource('productos', ProductoController::class)->names('productos')->parameters(['productos' => 'codigo']);

        // Productos de Proceso (PEP)
        Route::resource('productos-proceso', ProductoProcesoController::class)->names('productos_proceso')->parameters(['productos-proceso' => 'codigo']);
        
        // Procesos de Producción
        Route::resource('procesos-produccion', ProcesoProduccionController::class)->names('procesos_produccion')->parameters(['procesos-produccion' => 'codigo']);
        
        // Fórmulas
        Route::resource('formulas', FormulaController::class)->names('formulas')->parameters(['formulas' => 'codigo']);
        Route::get('formulas/{codigo}/composicion', [FormulaController::class, 'composicion'])->name('formulas.composicion');
        Route::post('formulas/{codigo}/composicion', [FormulaController::class, 'storeComposicion'])->name('formulas.storeComposicion');

        // Otros Maestros
        Route::resource('operaciones-produccion', OperacionProduccionController::class)->names('operaciones_produccion');
        Route::resource('centros-trabajo', CentroTrabajoController::class)->names('centros_trabajo');
        
        // Mapeo a Terceros
        Route::get('mapeo-terceros', [\App\Http\Controllers\MapeoTerceroController::class, 'index'])->name('mapeo-terceros.index');
        Route::get('mapeo-terceros/create', [\App\Http\Controllers\MapeoTerceroController::class, 'create'])->name('mapeo-terceros.create');
        Route::post('mapeo-terceros', [\App\Http\Controllers\MapeoTerceroController::class, 'store'])->name('mapeo-terceros.store');
        Route::delete('mapeo-terceros/{id}', [\App\Http\Controllers\MapeoTerceroController::class, 'destroy'])->name('mapeo-terceros.destroy');
        Route::resource('trabajadores', TrabajadorController::class)->names('trabajadores');
        Route::resource('actividades', ActividadProduccionController::class)->names('actividades');
        Route::resource('moldes', MoldeController::class)->names('moldes');
        Route::resource('colores', ColorController::class)->names('colores');
        Route::resource('usuarios', UsuarioController::class)->names('usuarios');
        Route::resource('roles', RolController::class)->names('roles');
        
        // Rutas de Producción
        Route::get('/rutas-produccion', [RutasProduccionController::class, 'index'])->name('admin.rutas_produccion.index');
        Route::post('/rutas-produccion', [RutasProduccionController::class, 'store'])->name('admin.rutas_produccion.store');
        
        // Rutas de Parámetros del Sistema
        Route::get('/parametros', [ParametroSistemaController::class, 'index'])->name('parametros.index');
        Route::post('/parametros', [ParametroSistemaController::class, 'store'])->name('parametros.store');
        Route::post('/parametros/update-bulk', [ParametroSistemaController::class, 'updateBulk'])->name('parametros.updateBulk');
        Route::post('/parametros/fetch-tipo-cambio', [ParametroSistemaController::class, 'fetchTipoCambio'])->name('parametros.fetchTipoCambio');
        Route::post('/parametros/limpiar-db', [ParametroSistemaController::class, 'limpiarDB'])->name('parametros.limpiar_db');

        // Proveedores
        Route::resource('proveedores', ProveedorController::class)->names('proveedores');

        // Almacenes
        Route::resource('almacenes', AlmacenController::class)->names('almacenes')->parameters(['almacenes' => 'codigo']);
    });

    // =========================================================
    // MÓDULOS DE COMPRAS (Administrador y Supervisor)
    // =========================================================
    Route::middleware('role:Administrador,Supervisor')->prefix('admin')->group(function () {
        Route::resource('compras', CompraController::class)->names('compras');
        Route::post('compras/{id}/anular', [CompraController::class, 'anular'])->name('compras.anular');
        Route::post('compras/api/guias-multi', [CompraController::class, 'getGuiasMultiAjax'])->name('compras.api.guias_multi');
        Route::get('compras/api/guia/{id}', [CompraController::class, 'getGuiaAjax'])->name('compras.api.guia');
        
        Route::post('guia_compras/{id}/deshacer-ubicacion', [GuiaRemisionCompraController::class, 'deshacerUbicacion'])->name('guia_compras.deshacer_ubicacion');
        Route::resource('guia_compras', GuiaRemisionCompraController::class)->names('guia_compras')->except(['destroy']);
        
        Route::post('proveedores/ajax', [ProveedorController::class, 'storeAjax'])->name('proveedores.storeAjax');
    });

    // =========================================================
    // MÓDULO DE REQUERIMIENTOS DE MATERIALES
    // =========================================================
    Route::middleware('role:Administrador,Supervisor,Especialista')
        ->prefix('admin')
        ->name('requerimientos_materiales.')
        ->group(function () {

        Route::get('requerimientos-materiales', [RequerimientoMaterialController::class, 'index'])->name('index');
        Route::get('requerimientos-materiales/create', [RequerimientoMaterialController::class, 'create'])->name('create');
        Route::post('requerimientos-materiales', [RequerimientoMaterialController::class, 'store'])->name('store');
        Route::get('requerimientos-materiales/{id}', [RequerimientoMaterialController::class, 'show'])->name('show');
        Route::get('requerimientos-materiales/{id}/edit', [RequerimientoMaterialController::class, 'edit'])->name('edit');
        Route::put('requerimientos-materiales/{id}', [RequerimientoMaterialController::class, 'update'])->name('update');

        Route::post('requerimientos-materiales/{id}/enviar', [RequerimientoMaterialController::class, 'enviar'])
            ->name('enviar')->middleware('role:Administrador,Supervisor,Especialista');
        Route::post('requerimientos-materiales/{id}/aprobar', [RequerimientoMaterialController::class, 'aprobar'])
            ->name('aprobar')->middleware('role:Administrador');
        Route::post('requerimientos-materiales/{id}/rechazar', [RequerimientoMaterialController::class, 'rechazar'])
            ->name('rechazar')->middleware('role:Administrador');
        Route::post('requerimientos-materiales/{id}/anular', [RequerimientoMaterialController::class, 'anular'])
            ->name('anular')->middleware('role:Administrador');
    });

    // =========================================================
    // MÓDULO DE INVENTARIO SEPARADO (Consolidado y sin errores)
    // =========================================================
    Route::prefix('admin/inventario')->group(function () {
        
        // 1. Stock Actual (Disponible para todos los autenticados)
        Route::get('/', [InventarioController::class, 'index'])->name('inventario.index');

        // 2. Kardex / Historial de Movimientos
        Route::get('/kardex', [InventarioController::class, 'kardex'])->name('inventario.kardex');
        Route::get('/kardex/{id}/desglose-costo', [InventarioController::class, 'kardexDesgloseCosto'])->name('inventario.kardex.desgloseCosto');
        Route::get('/kardex/exportar/excel', [InventarioController::class, 'exportarKardexExcel'])->name('inventario.kardex.exportar.excel');
        Route::get('/kardex/exportar/pdf', [InventarioController::class, 'exportarKardexPdf'])->name('inventario.kardex.exportar.pdf');

        // 3. Acciones de Almacén (Restringidas a Administrador y Supervisor)
        Route::middleware('role:Administrador,Supervisor')->group(function () {
            // Recepciones
            Route::get('/recepciones', [InventarioController::class, 'recepciones'])->name('inventario.recepciones');
            Route::get('/recepciones/historial', [InventarioController::class, 'recepcionesHistorial'])->name('inventario.recepciones.historial');
            Route::post('/recibir/{id}', [InventarioController::class, 'procesarRecepcion'])->name('inventario.procesar_recepcion');

            // Alertas de Stock
            Route::get('/alertas-stock', [InventarioController::class, 'alertasStock'])->name('inventario.alertas_stock');
            Route::post('/actualizar-stock-minimo', [InventarioController::class, 'actualizarStockMinimo'])->name('inventario.actualizar_stock_minimo');
            Route::post('/recibir-produccion/{id}', [InventarioController::class, 'procesarRecepcionProduccion'])->name('inventario.procesar_recepcion_produccion');
            Route::post('/recibir-produccion/global/{idop}/{codigo_producto}', [InventarioController::class, 'procesarRecepcionProduccionGlobal'])->name('inventario.procesar_recepcion_produccion_global');
            Route::post('/ubicar-guia/{id}', [InventarioController::class, 'procesarUbicacionGuia'])->name('inventario.procesar_ubicacion_guia');

            // Ajustes Manuales
            Route::get('/ajuste', [InventarioController::class, 'ajuste'])->name('inventario.ajuste');
            Route::post('/ajuste', [InventarioController::class, 'storeAjuste'])->name('inventario.store_ajuste');
            Route::get('/lotes-ajax', [InventarioController::class, 'getLotesAjax'])->name('inventario.lotes_ajax');

            // Bandeja de Ajustes (CRUD)
            Route::get('/ajuste/lista', [InventarioController::class, 'ajustesIndex'])->name('inventario.ajuste.lista');
            Route::get('/ajuste/{kardex}', [InventarioController::class, 'showAjuste'])->name('inventario.ajuste.show');
            Route::get('/ajuste/{kardex}/editar', [InventarioController::class, 'editAjuste'])->name('inventario.ajuste.edit');
            Route::put('/ajuste/{kardex}', [InventarioController::class, 'updateAjuste'])->name('inventario.ajuste.update');
            Route::delete('/ajuste/{kardex}', [InventarioController::class, 'destroyAjuste'])->name('inventario.ajuste.destroy');

            // TRANSFERENCIAS ENTRE ALMACENES
            Route::get('/transferencias', [\App\Http\Controllers\TransferenciaAlmacenController::class, 'index'])->name('inventario.transferencias.index');
            Route::get('/transferencias/create', [\App\Http\Controllers\TransferenciaAlmacenController::class, 'create'])->name('inventario.transferencias.create');
            Route::post('/transferencias', [\App\Http\Controllers\TransferenciaAlmacenController::class, 'store'])->name('inventario.transferencias.store');
            Route::get('/transferencias/{id}', [\App\Http\Controllers\TransferenciaAlmacenController::class, 'show'])->name('inventario.transferencias.show');
            Route::post('/transferencias/{id}/anular', [\App\Http\Controllers\TransferenciaAlmacenController::class, 'anular'])->name('inventario.transferencias.anular');
            Route::get('/transferencias-api/lotes', [\App\Http\Controllers\TransferenciaAlmacenController::class, 'buscarLotes'])->name('inventario.transferencias.buscar_lotes');
        });

        // 4. Extornos y Auditoría (SOLO Administrador)
        Route::middleware('role:Administrador')->group(function () {
            Route::get('/extornos', [InventarioController::class, 'extornos'])->name('inventario.extornos');
            Route::post('/extornos/procesar/{id}', [InventarioController::class, 'procesarExtorno'])->name('inventario.procesar_extorno');
        });

        // 5. Despachos (Atención de Requerimientos)
        Route::middleware('role:Administrador,Supervisor,ALMACEN')->group(function () {
            Route::get('/despachos', [DespachoRequerimientoController::class, 'index'])->name('inventario.despachos.index');
            Route::get('/despachos/{id}/atender', [DespachoRequerimientoController::class, 'atender'])->name('inventario.despachos.atender');
            Route::post('/despachos/{id}/store-atender', [DespachoRequerimientoController::class, 'storeAtender'])->name('inventario.despachos.store_atender');
        });
    });

    // =========================================================
    // MÓDULO DE PRODUCCIÓN
    // =========================================================
    Route::middleware('role:Administrador,Supervisor,Produccion,Especialista')->prefix('produccion')->group(function () {
        // Ingresos de Producción (PEP)
        Route::get('ingresos', [\App\Http\Controllers\InventarioController::class, 'recepcionesProduccion'])->name('produccion.ingresos');

        // Órdenes de Producción
        Route::resource('ordenes', OrdenProduccionController::class)->names([
            'index' => 'produccion.ordenes.index',
            'create' => 'produccion.ordenes.create',
            'store' => 'produccion.ordenes.store',
            'destroy' => 'produccion.ordenes.destroy',
        ])->except(['show', 'edit', 'update']);
        Route::post('ordenes/{orden}/finalizar', [OrdenProduccionController::class, 'finalizar'])->name('produccion.ordenes.finalizar');
        Route::get('ordenes/{orden}/procesos-ajax', [OrdenProduccionController::class, 'getProcesos'])->name('produccion.ordenes.procesos_ajax');
        
        // Procesos de la Orden
        Route::get('ordenes/{orden}/procesos', [OrdenProcesoController::class, 'index'])->name('ordenes.procesos.index');
        Route::get('ordenes/{orden}/procesos/create', [OrdenProcesoController::class, 'create'])->name('ordenes.procesos.create');
        Route::post('ordenes/{orden}/procesos', [OrdenProcesoController::class, 'store'])->name('ordenes.procesos.store');
        Route::delete('procesos/{proceso}', [OrdenProcesoController::class, 'destroy'])->name('ordenes.procesos.destroy');
        Route::get('ordenes/{orden}/procesos/{proceso}/ejecutar', [OrdenProcesoController::class, 'ejecutar'])->name('ordenes.procesos.ejecutar');
        Route::post('ordenes/{orden}/procesos/{proceso}/componentes', [OrdenProcesoController::class, 'storeComponentes'])->name('ordenes.procesos.componentes.store');
        Route::post('ordenes/{orden}/procesos/{proceso}/ejecucion-agrupada', [OrdenProcesoController::class, 'storeEjecucionAgrupada'])->name('ordenes.procesos.ejecucion_agrupada.store');
        Route::delete('ordenes/{orden}/procesos/{proceso}/componentes/{componente}', [OrdenProcesoController::class, 'destroyComponente'])->name('ordenes.procesos.componentes.destroy');
        Route::put('ordenes/{orden}/procesos/{proceso}/componentes/{componente}', [OrdenProcesoController::class, 'updateComponente'])->name('ordenes.procesos.componentes.update');
        Route::post('ordenes/{orden}/procesos/{proceso}/finalizar', [OrdenProcesoController::class, 'finalizar'])->name('ordenes.procesos.finalizar');
        Route::get('api/formulas/composicion', [OrdenProcesoController::class, 'getFormulaComponents'])->name('api.formulas.composicion');
        Route::get('api/verificar-stock', [OrdenProcesoController::class, 'verificarStock'])->name('api.verificar_stock');
        
        // Mermas y Scrap
        Route::get('mermas/opciones', [\App\Http\Controllers\MermaController::class, 'opciones'])->name('mermas.opciones');
        Route::get('mermas/ajax/procesos-por-op', [\App\Http\Controllers\MermaController::class, 'getProcesosPorOP'])->name('mermas.procesos_por_op');
        Route::get('mermas/ajax/productos-por-op', [\App\Http\Controllers\MermaController::class, 'getProductosPorOP'])->name('mermas.productos_por_op');
        Route::get('mermas/ajax/componentes-ensamblado', [\App\Http\Controllers\MermaController::class, 'getComponentesEnsamblado'])->name('mermas.componentes_ensamblado');
        Route::resource('mermas', \App\Http\Controllers\MermaController::class)->only(['index', 'create', 'store']);
        Route::get('mermas/{id}/detalle', [\App\Http\Controllers\MermaController::class, 'detalle'])->name('mermas.detalle');
        Route::get('mermas/reporte-pdf/{idop}', [\App\Http\Controllers\MermaController::class, 'reportePdf'])->name('mermas.reporte_pdf');
        Route::post('mermas/{id}/anular', [\App\Http\Controllers\MermaController::class, 'anular'])->name('mermas.anular');

        // Transferencias de Producción (Consumo Totalizado y FIFO por Lotes)
        Route::get('transferencias', [\App\Http\Controllers\ProduccionTransferenciaController::class, 'index'])->name('produccion.transferencias.index');
        Route::get('transferencias/create', [\App\Http\Controllers\ProduccionTransferenciaController::class, 'create'])->name('produccion.transferencias.create');
        Route::post('transferencias', [\App\Http\Controllers\ProduccionTransferenciaController::class, 'store'])->name('produccion.transferencias.store');
        Route::get('transferencias/{id}', [\App\Http\Controllers\ProduccionTransferenciaController::class, 'show'])->name('produccion.transferencias.show');
        Route::post('transferencias/{id}/anular', [\App\Http\Controllers\ProduccionTransferenciaController::class, 'anular'])->name('produccion.transferencias.anular');
        Route::get('transferencias-api/productos', [\App\Http\Controllers\ProduccionTransferenciaController::class, 'buscarProductos'])->name('produccion.transferencias.buscar_productos');
    });

    // =========================================================
    // OTROS ROLES (Dashboards específicos)
    // =========================================================
    Route::get('/supervisor/dashboard', function () {
        $ordenesActivas = DB::table('orden_produccion_global')
            ->where('activo', 1)
            ->whereIn('estado', ['PENDIENTE', 'EN_PROCESO'])
            ->count();

        $pendientesValidar = DB::table('produccion_ingresos_proceso')
            ->where('estado', 'PENDIENTE')
            ->count();

        $produccionDia = DB::table('componentes_orden_produccion_global')
            ->whereDate('fecha_creacion', now()->toDateString())
            ->where('estado', 1)
            ->sum('cantidad');

        $ordenesRecientes = DB::table('orden_produccion_global')
            ->where('activo', 1)
            ->orderBy('fecha', 'desc')
            ->limit(5)
            ->get();

        $recepcionesPendientes = DB::table('produccion_ingresos_proceso')
            ->join('producto', 'produccion_ingresos_proceso.codigo_producto_proceso', '=', 'producto.codigo')
            ->select('produccion_ingresos_proceso.*', 'producto.descripcion as descripcion_producto_proceso')
            ->where('produccion_ingresos_proceso.estado', 'PENDIENTE')
            ->orderBy('produccion_ingresos_proceso.fecha_ingreso', 'desc')
            ->limit(5)
            ->get();

        return view('supervisor.dashboard', compact('ordenesActivas', 'pendientesValidar', 'produccionDia', 'ordenesRecientes', 'recepcionesPendientes'));
    })->name('supervisor.dashboard')->middleware('role:Supervisor');

    Route::get('/especialista/dashboard', function () {
        $totalFormulas = DB::table('formula_produccion')->where('estado', 1)->count();
        $totalComposiciones = DB::table('composicion_formula')->count();
        $totalProductos = DB::table('producto')->where('estado', 1)->count();
        $totalProcesos = DB::table('proceso_produccion')->where('estado', 1)->count();

        $ultimasFormulas = DB::table('formula_produccion')
            ->where('estado', 1)
            ->orderBy('fecha_creacion', 'desc')
            ->limit(5)
            ->get();

        $composicionesCount = DB::table('composicion_formula')
            ->whereIn('codigo_formula', $ultimasFormulas->pluck('codigo'))
            ->selectRaw('codigo_formula, COUNT(*) as total')
            ->groupBy('codigo_formula')
            ->pluck('total', 'codigo_formula');

        return view('especialista.dashboard', compact('totalFormulas', 'totalComposiciones', 'totalProductos', 'totalProcesos', 'ultimasFormulas', 'composicionesCount'));
    })->name('especialista.dashboard')->middleware('role:Especialista');

    Route::get('/almacen/dashboard', function () {
        $reqsPendientes = DB::table('requerimientos_materiales')
            ->whereIn('estado', ['APROBADO', 'ATENDIDO_PARCIAL'])
            ->count();

        $alertasStock = DB::table('inventario')
            ->where('stock_actual', '<', DB::raw('stock_minimo'))
            ->count();

        $recepcionesPend = DB::table('produccion_ingresos_proceso')
            ->where('estado', 'PENDIENTE')
            ->count();

        $ultimosReqs = DB::table('requerimientos_materiales')
            ->whereIn('estado', ['APROBADO', 'ATENDIDO_PARCIAL', 'ATENDIDO_TOTAL'])
            ->orderBy('fecha_requerimiento', 'desc')
            ->limit(5)
            ->get();

        $ultimosKardex = DB::table('kardex')
            ->join('producto', 'kardex.codigo_producto', '=', 'producto.codigo')
            ->select('kardex.*', 'producto.descripcion as nombre_producto')
            ->orderBy('fecha_movimiento', 'desc')
            ->limit(5)
            ->get();

        return view('almacen.dashboard', compact('reqsPendientes', 'alertasStock', 'recepcionesPend', 'ultimosReqs', 'ultimosKardex'));
    })->name('almacen.dashboard')->middleware('role:ALMACEN');

    // =========================================================
    // MÓDULO DE TERCEROS Y REPORTES
    // =========================================================
    Route::middleware('role:Administrador,Supervisor')->prefix('terceros')->name('terceros.')->group(function () {
        Route::get('/salidas', [\App\Http\Controllers\GuiaTercerosSalidaController::class, 'index'])->name('salidas.index');
        Route::get('/salidas/create', [\App\Http\Controllers\GuiaTercerosSalidaController::class, 'create'])->name('salidas.create');
        Route::get('/salidas/productos-con-stock', [\App\Http\Controllers\GuiaTercerosSalidaController::class, 'getProductosConStock'])->name('salidas.productos_con_stock');
        Route::post('/salidas', [\App\Http\Controllers\GuiaTercerosSalidaController::class, 'store'])->name('salidas.store');
        Route::get('/salidas/{id}', [\App\Http\Controllers\GuiaTercerosSalidaController::class, 'show'])->name('salidas.show');
        Route::get('/liquidacion', [\App\Http\Controllers\TercerosLiquidacionController::class, 'index'])->name('liquidacion.index');
        Route::post('/liquidacion/{id}/cerrar', [\App\Http\Controllers\TercerosLiquidacionController::class, 'cerrarConMerma'])->name('liquidacion.cerrar');
    });

    Route::middleware('role:Administrador,Supervisor,Especialista,Almacen,Contabilidad')->prefix('reportes')->name('reportes.')->group(function () {
        Route::get('/', [ReporteController::class, 'index'])->name('index');
        Route::get('/produccion', [ReporteController::class, 'produccion'])->name('produccion');
        Route::get('/inventario', [ReporteController::class, 'inventario'])->name('inventario');
        Route::get('/trazabilidad', [TrazabilidadController::class, 'index'])->name('trazabilidad');
    });

});