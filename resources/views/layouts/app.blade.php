<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - Leon Plast Producción</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .swal2-popup { font-size: 0.85rem !important; padding: 1.25rem !important; border-radius: 1rem !important; }
        .swal2-title { font-size: 1.15rem !important; font-weight: 700 !important; }
        .swal2-html-container { font-size: 0.85rem !important; line-height: 1.5 !important; margin: 0.75em 1em 0.5em 1em !important; }
        .swal2-styled.swal2-confirm, .swal2-styled.swal2-cancel { font-size: 0.8rem !important; padding: 0.4rem 1.25rem !important; border-radius: 0.5rem !important; }
    </style>
    @stack('styles')
</head>
<body class="font-sans leading-normal tracking-normal text-slate-800">

    <!-- Global Page Loader -->
    <div id="global-loader" class="fixed inset-0 z-9999 bg-slate-900/50 backdrop-blur-sm items-center justify-center hidden">
        <div class="bg-white p-6 rounded-2xl shadow-2xl flex flex-col items-center">
            <i class="fas fa-spinner fa-spin text-4xl text-emerald-600 mb-4"></i>
            <p class="text-slate-700 font-semibold animate-pulse">Cargando...</p>
        </div>
    </div>

    <div class="flex h-screen overflow-hidden">

        <div id="sidebar" class="fixed md:relative z-50 inset-y-0 left-0 w-64 h-full bg-linear-to-b from-slate-900 via-slate-900 to-slate-950 text-slate-300 flex flex-col shrink-0 shadow-2xl border-r border-slate-800/80 -translate-x-full md:translate-x-0 transition-transform duration-200 ease-out">
            <div class="h-20 flex items-center justify-center border-b border-slate-800/80 shrink-0 bg-white/95 px-6">
                <img src="{{ asset('img/logo.png') }}" alt="LEON PLAST" class="h-11 w-auto object-contain">
            </div>

            <nav class="flex-1 mt-4 px-3.5 pb-4 overflow-y-auto scrollbar-thin space-y-6">
                <div class="space-y-1">
                    @php
                        $routeDash = '#';
                        if(Auth::user()->rol == 'Administrador') $routeDash = route('admin.dashboard');
                        elseif(Auth::user()->rol == 'Supervisor') $routeDash = route('supervisor.dashboard');
                        elseif(Auth::user()->rol == 'Especialista') $routeDash = route('especialista.dashboard');
                        elseif(Auth::user()->rol == 'ALMACEN') $routeDash = route('almacen.dashboard');
                        else $routeDash = route('dashboard');
                    @endphp
                    <a href="{{ $routeDash }}" class="flex items-center p-3 text-sm font-semibold rounded-xl {{ request()->routeIs('*.dashboard') ? 'bg-linear-to-r from-emerald-600 to-emerald-700 text-white shadow-lg shadow-emerald-950/50' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }} transition-all duration-150">
                        <i class="fas fa-chart-line w-6 text-base"></i>
                        <span>Dashboard</span>
                    </a>

                    @if(Auth::user()->hasAnyAccess(['procesos_produccion.index', 'formulas.index', 'productos.index', 'productos_proceso.index', 'tipos_producto.index', 'unidades_medida.index', 'operaciones_produccion.index', 'centros_trabajo.index', 'trabajadores.index', 'proveedores.index', 'actividades.index', 'moldes.index', 'colores.index']))
                    <div>
                        <button type="button" id="btnTablasMaestras" class="w-full flex items-center justify-between p-3 text-sm font-semibold rounded-xl text-slate-400 hover:bg-slate-800/60 hover:text-white transition-all duration-150 focus:outline-none">
                            <div class="flex items-center">
                                <i class="fas fa-database w-6 text-base"></i>
                                <span>Tablas Maestras</span>
                            </div>
                            <i id="iconTablasMaestras" class="fas fa-chevron-down text-xs transition-transform duration-200"></i>
                        </button>

                        <div id="menuTablasMaestras" class="hidden flex-col mt-1 pl-9 pr-1 space-y-1">
                            @if(Auth::user()->hasAccess('procesos_produccion.index'))<a href="{{ route('procesos_produccion.index') }}" class="block p-2 text-xs font-semibold {{ request()->routeIs('procesos_produccion.*') ? 'bg-emerald-600/20 text-emerald-400 font-bold border-l-2 border-emerald-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }} rounded-r-lg transition-all">Procesos de producción</a>@endif
                            @if(Auth::user()->hasAccess('formulas.index'))<a href="{{ route('formulas.index') }}" class="block p-2 text-xs font-semibold {{ request()->routeIs('formulas.*') ? 'bg-emerald-600/20 text-emerald-400 font-bold border-l-2 border-emerald-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }} rounded-r-lg transition-all">Fórmulas</a>@endif
                            @if(Auth::user()->hasAccess('productos.index'))<a href="{{ route('productos.index') }}" class="block p-2 text-xs font-semibold {{ request()->routeIs('productos.*') ? 'bg-emerald-600/20 text-emerald-400 font-bold border-l-2 border-emerald-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }} rounded-r-lg transition-all">Productos</a>@endif
                            @if(Auth::user()->hasAccess('productos_proceso.index'))<a href="{{ route('productos_proceso.index') }}" class="block p-2 text-xs font-semibold {{ request()->routeIs('productos_proceso.*') ? 'bg-emerald-600/20 text-emerald-400 font-bold border-l-2 border-emerald-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }} rounded-r-lg transition-all">Productos de proceso</a>@endif
                            @if(Auth::user()->hasAccess('tipos_producto.index'))<a href="{{ route('tipos_producto.index') }}" class="block p-2 text-xs font-semibold {{ request()->routeIs('tipos_producto.*') ? 'bg-emerald-600/20 text-emerald-400 font-bold border-l-2 border-emerald-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }} rounded-r-lg transition-all">Tipo de productos</a>@endif
                            @if(Auth::user()->hasAccess('unidades_medida.index'))<a href="{{ route('unidades_medida.index') }}" class="block p-2 text-xs font-semibold {{ request()->routeIs('unidades_medida.*') ? 'bg-emerald-600/20 text-emerald-400 font-bold border-l-2 border-emerald-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }} rounded-r-lg transition-all">Unidad de medida</a>@endif
                            @if(Auth::user()->hasAccess('operaciones_produccion.index'))<a href="{{ route('operaciones_produccion.index') }}" class="block p-2 text-xs font-semibold {{ request()->routeIs('operaciones_produccion.*') ? 'bg-emerald-600/20 text-emerald-400 font-bold border-l-2 border-emerald-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }} rounded-r-lg transition-all">Operaciones</a>@endif
                            @if(Auth::user()->hasAccess('centros_trabajo.index'))<a href="{{ route('centros_trabajo.index') }}" class="block p-2 text-xs font-semibold {{ request()->routeIs('centros_trabajo.*') ? 'bg-emerald-600/20 text-emerald-400 font-bold border-l-2 border-emerald-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }} rounded-r-lg transition-all">Centros de trabajo</a>@endif
                            @if(Auth::user()->rol == 'Administrador')<a href="{{ route('mapeo-terceros.index') }}" class="block p-2 text-xs font-semibold {{ request()->routeIs('mapeo-terceros.*') ? 'bg-emerald-600/20 text-emerald-400 font-bold border-l-2 border-emerald-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }} rounded-r-lg transition-all">Mapeo a Terceros</a>@endif
                            @if(Auth::user()->hasAccess('trabajadores.index'))<a href="{{ route('trabajadores.index') }}" class="block p-2 text-xs font-semibold {{ request()->routeIs('trabajadores.*') ? 'bg-emerald-600/20 text-emerald-400 font-bold border-l-2 border-emerald-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }} rounded-r-lg transition-all">Trabajadores</a>@endif
                            @if(Auth::user()->hasAccess('actividades.index'))<a href="{{ route('actividades.index') }}" class="block p-2 text-xs font-semibold {{ request()->routeIs('actividades.*') ? 'bg-emerald-600/20 text-emerald-400 font-bold border-l-2 border-emerald-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }} rounded-r-lg transition-all">Actividades</a>@endif
                            @if(Auth::user()->hasAccess('moldes.index'))<a href="{{ route('moldes.index') }}" class="block p-2 text-xs font-semibold {{ request()->routeIs('moldes.*') ? 'bg-emerald-600/20 text-emerald-400 font-bold border-l-2 border-emerald-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }} rounded-r-lg transition-all">Moldes</a>@endif
                            @if(Auth::user()->hasAccess('colores.index'))<a href="{{ route('colores.index') }}" class="block p-2 text-xs font-semibold {{ request()->routeIs('colores.*') ? 'bg-emerald-600/20 text-emerald-400 font-bold border-l-2 border-emerald-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }} rounded-r-lg transition-all">Color</a>@endif
                            @if(Auth::user()->rol == 'Administrador')<a href="{{ route('parametros.index') }}" class="block p-2 text-xs font-semibold {{ request()->routeIs('parametros.*') ? 'bg-emerald-600/20 text-emerald-400 font-bold border-l-2 border-emerald-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }} rounded-r-lg transition-all">Parámetros del Sistema</a>@endif
                        </div>
                    </div>
                    @endif

                    @if(Auth::user()->hasAnyAccess(['compras.index', 'proveedores.index']))
                    <div>
                        <button type="button" id="btnCuentasPagar" class="w-full flex items-center justify-between p-3 text-sm font-semibold rounded-xl text-slate-400 hover:bg-slate-800/60 hover:text-white transition-all duration-150 focus:outline-none">
                            <div class="flex items-center">
                                <i class="fas fa-file-invoice-dollar w-6 text-base"></i>
                                <span>Cuentas por Pagar</span>
                            </div>
                            <i id="iconCuentasPagar" class="fas fa-chevron-down text-xs transition-transform duration-200"></i>
                        </button>

                        <div id="menuCuentasPagar" class="hidden flex-col mt-1 pl-9 pr-1 space-y-1">
                            @if(Auth::user()->hasAccess('proveedores.index'))
                            <a href="{{ route('proveedores.index') }}" class="block p-2 text-xs font-semibold {{ request()->routeIs('proveedores.*') ? 'bg-emerald-600/20 text-emerald-400 font-bold border-l-2 border-emerald-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }} rounded-r-lg transition-all"><i class="fas fa-truck w-4 mr-1"></i> Proveedores</a>
                            @endif

                            @if(Auth::user()->hasAccess('compras.index'))
                            <a href="{{ route('guia_compras.index') }}" class="block p-2 text-xs font-semibold {{ request()->routeIs('guia_compras.*') ? 'bg-emerald-600/20 text-emerald-400 font-bold border-l-2 border-emerald-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }} rounded-r-lg transition-all"><i class="fas fa-shipping-fast w-4 mr-1"></i> Guías de Remisión</a>
                            <a href="{{ route('compras.index') }}" class="block p-2 text-xs font-semibold {{ request()->routeIs('compras.*') ? 'bg-emerald-600/20 text-emerald-400 font-bold border-l-2 border-emerald-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }} rounded-r-lg transition-all"><i class="fas fa-shopping-cart w-4 mr-1"></i> Compras</a>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if(Auth::user()->hasAnyAccess(['inventario.index', 'inventario.recepciones', 'inventario.kardex', 'inventario.ajuste', 'inventario.extornos']))
                    <div>
                        <button type="button" id="btnInventario" class="w-full flex items-center justify-between p-3 text-sm font-semibold rounded-xl text-slate-400 hover:bg-slate-800/60 hover:text-white transition-all duration-150 focus:outline-none">
                            <div class="flex items-center">
                                <i class="fas fa-boxes w-6 text-base"></i>
                                <span>Inventario</span>
                            </div>
                            <i id="iconInventario" class="fas fa-chevron-down text-xs transition-transform duration-200"></i>
                        </button>

                        <div id="menuInventario" class="hidden flex-col mt-1 pl-9 pr-1 space-y-1">
                            @if(Auth::user()->hasAccess('requerimientos_materiales.atender') || Auth::user()->hasAccess('inventario.despachos'))
                            <a href="{{ route('inventario.despachos.index') }}" class="block p-2 text-xs font-semibold {{ request()->routeIs('inventario.despachos*') ? 'bg-emerald-600/20 text-emerald-400 font-bold border-l-2 border-emerald-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }} rounded-r-lg transition-all"><i class="fas fa-box-open mr-1 text-indigo-300"></i> Despachos</a>
                            @endif

                            @if(Auth::user()->hasAccess('inventario.index'))<a href="{{ route('inventario.index') }}" class="block p-2 text-xs font-semibold {{ request()->routeIs('inventario.index') ? 'bg-emerald-600/20 text-emerald-400 font-bold border-l-2 border-emerald-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }} rounded-r-lg transition-all"><i class="fas fa-boxes mr-1.5"></i>Existencias</a>@endif

                            @if(Auth::user()->hasAccess('inventario.alertas_stock'))<a href="{{ route('inventario.alertas_stock') }}" class="block p-2 text-xs font-semibold {{ request()->routeIs('inventario.alertas*') ? 'bg-emerald-600/20 text-emerald-400 font-bold border-l-2 border-emerald-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }} rounded-r-lg transition-all"><i class="fas fa-exclamation-triangle text-amber-400 mr-1.5"></i>Alertas Stock</a>@endif

                            @if(Auth::user()->hasAccess('inventario.recepciones'))<a href="{{ route('inventario.recepciones') }}" class="block p-2 text-xs font-semibold {{ request()->routeIs('inventario.recepciones') ? 'bg-emerald-600/20 text-emerald-400 font-bold border-l-2 border-emerald-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }} rounded-r-lg transition-all"><i class="fas fa-box mr-1.5"></i>Recepciones</a>@endif

                            @if(Auth::user()->hasAccess('inventario.ajuste'))<a href="{{ route('inventario.ajuste.lista') }}" class="block p-2 text-xs font-semibold {{ request()->routeIs('inventario.ajuste.lista') ? 'bg-emerald-600/20 text-emerald-400 font-bold border-l-2 border-emerald-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }} rounded-r-lg transition-all"><i class="fas fa-edit mr-1.5"></i>Ajustes</a>@endif

                            @if(Auth::user()->hasAccess('inventario.extornos'))<a href="{{ route('inventario.extornos') }}" class="block p-2 text-xs font-semibold {{ request()->routeIs('inventario.extornos') ? 'bg-emerald-600/20 text-emerald-400 font-bold border-l-2 border-emerald-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }} rounded-r-lg transition-all"><i class="fas fa-exchange-alt mr-1.5"></i>Extornos</a>@endif

                            @if(Auth::user()->hasAccess('inventario.transferencias.index'))
                            <a href="{{ route('inventario.transferencias.index') }}" class="block p-2 text-xs font-semibold {{ request()->routeIs('inventario.transferencias*') ? 'bg-emerald-600/20 text-emerald-400 font-bold border-l-2 border-emerald-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }} rounded-r-lg transition-all"><i class="fas fa-exchange-alt mr-1 text-blue-300"></i> Transferencias</a>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if(Auth::user()->hasAnyAccess(['inventario.kardex']))
                    <div>
                        <button type="button" id="btnContabilidad" class="w-full flex items-center justify-between p-3 text-sm font-semibold rounded-xl text-slate-400 hover:bg-slate-800/60 hover:text-white transition-all duration-150 focus:outline-none">
                            <div class="flex items-center">
                                <i class="fas fa-book w-6 text-base"></i>
                                <span>Contabilidad</span>
                            </div>
                            <i id="iconContabilidad" class="fas fa-chevron-down text-xs transition-transform duration-200"></i>
                        </button>

                        <div id="menuContabilidad" class="hidden flex-col mt-1 pl-9 pr-1 space-y-1">
                            @if(Auth::user()->hasAccess('inventario.kardex'))
                            <a href="{{ route('inventario.kardex') }}" class="block p-2 text-xs font-semibold {{ request()->routeIs('inventario.kardex') ? 'bg-emerald-600/20 text-emerald-400 font-bold border-l-2 border-emerald-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }} rounded-r-lg transition-all"><i class="fas fa-book-open mr-1 text-emerald-400"></i> Kardex Movimientos</a>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if(Auth::user()->hasAnyAccess(['produccion.ordenes.index', 'mermas.index', 'produccion.ingresos', 'requerimientos_materiales.index', 'produccion.transferencias.index']))
                    <div>
                        <button type="button" id="btnProduccion" class="w-full flex items-center justify-between p-3 text-sm font-semibold rounded-xl text-slate-400 hover:bg-slate-800/60 hover:text-white transition-all duration-150 focus:outline-none">
                            <div class="flex items-center">
                                <i class="fas fa-industry w-6 text-base"></i>
                                <span>Producción</span>
                            </div>
                            <i id="iconProduccion" class="fas fa-chevron-down text-xs transition-transform duration-200"></i>
                        </button>

                        <div id="menuProduccion" class="hidden flex-col mt-1 pl-9 pr-1 space-y-1">
                            @if(Auth::user()->hasAccess('requerimientos_materiales.index'))
                            <a href="{{ route('requerimientos_materiales.index') }}" class="block p-2 text-xs font-semibold {{ request()->routeIs('requerimientos_materiales*') ? 'bg-emerald-600/20 text-emerald-400 font-bold border-l-2 border-emerald-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }} rounded-r-lg transition-all"><i class="fas fa-clipboard-list w-4 mr-1"></i> Requerimientos</a>
                            @endif
                            @if(Auth::user()->hasAccess('produccion.ordenes.index'))
                            <a href="{{ route('produccion.ordenes.index') }}" class="block p-2 text-xs font-semibold {{ (request()->routeIs('produccion.ordenes*') || request()->routeIs('ordenes.procesos*')) ? 'bg-emerald-600/20 text-emerald-400 font-bold border-l-2 border-emerald-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }} rounded-r-lg transition-all"><i class="fas fa-tasks w-4 mr-1"></i> Órdenes de Producción</a>
                            @endif
                            @if(Auth::user()->hasAccess('produccion.transferencias.index'))
                            <a href="{{ route('produccion.transferencias.index') }}" class="block p-2 text-xs font-semibold {{ request()->routeIs('produccion.transferencias*') ? 'bg-emerald-600/20 text-emerald-400 font-bold border-l-2 border-emerald-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }} rounded-r-lg transition-all"><i class="fas fa-dolly-flatbed w-4 mr-1 text-cyan-300"></i> Transferencias Producción</a>
                            @endif
                            @if(Auth::user()->hasAccess('admin.rutas_produccion.index'))
                            <a href="{{ route('admin.rutas_produccion.index') }}" class="block p-2 text-xs font-semibold {{ request()->routeIs('admin.rutas_produccion.*') ? 'bg-emerald-600/20 text-emerald-400 font-bold border-l-2 border-emerald-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }} rounded-r-lg transition-all"><i class="fas fa-route w-4 mr-1"></i> Rutas de Producción</a>
                            @endif
                            @if(Auth::user()->hasAccess('mermas.index'))
                            <a href="{{ route('mermas.index') }}" class="block p-2 text-xs font-semibold {{ request()->routeIs('mermas.*') ? 'bg-emerald-600/20 text-emerald-400 font-bold border-l-2 border-emerald-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }} rounded-r-lg transition-all"><i class="fas fa-recycle mr-1 text-slate-300"></i> Mermas y Recuperados</a>
                            @endif
                            @if(Auth::user()->hasAccess('produccion.ingresos'))
                            <a href="{{ route('produccion.ingresos') }}" class="block p-2 text-xs font-semibold {{ request()->routeIs('produccion.ingresos') ? 'bg-emerald-600/20 text-emerald-400 font-bold border-l-2 border-emerald-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }} rounded-r-lg transition-all"><i class="fas fa-industry w-4 mr-1 text-emerald-300"></i> Ingresos Producción</a>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if(Auth::user()->hasAccess('almacenes.index'))
                    <a href="{{ route('almacenes.index') }}" class="flex items-center p-3 text-sm font-semibold rounded-xl {{ request()->routeIs('almacenes.*') ? 'bg-linear-to-r from-emerald-600 to-emerald-700 text-white shadow-lg shadow-emerald-950/50' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }} transition-all duration-150">
                        <i class="fas fa-warehouse w-6 text-base"></i>
                        <span>Almacén</span>
                    </a>
                    @endif

                    @if(Auth::user()->hasAccess('terceros.salidas.index'))
                    <a href="{{ route('terceros.salidas.index') }}" class="flex items-center p-3 text-sm font-semibold rounded-xl {{ request()->routeIs('terceros.salidas.*') ? 'bg-linear-to-r from-emerald-600 to-emerald-700 text-white shadow-lg shadow-emerald-950/50' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }} transition-all duration-150">
                        <i class="fas fa-truck-loading w-6 text-base"></i>
                        <span>Terceros - Envíos</span>
                    </a>
                    @endif
                    @if(Auth::user()->hasAccess('terceros.liquidacion.index'))
                    <a href="{{ route('terceros.liquidacion.index') }}" class="flex items-center p-3 text-sm font-semibold rounded-xl {{ request()->routeIs('terceros.liquidacion.*') ? 'bg-linear-to-r from-emerald-600 to-emerald-700 text-white shadow-lg shadow-emerald-950/50' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }} transition-all duration-150">
                        <i class="fas fa-balance-scale w-6 text-base"></i>
                        <span>Terceros</span>
                    </a>
                    @endif

                    @if(Auth::user()->hasAccess('reportes.index'))
                    <a href="{{ route('reportes.index') }}" class="flex items-center p-3 text-sm font-semibold rounded-xl {{ request()->routeIs('reportes.index') || request()->routeIs('reportes.produccion') || request()->routeIs('reportes.inventario') ? 'bg-linear-to-r from-emerald-600 to-emerald-700 text-white shadow-lg shadow-emerald-950/50' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }} transition-all duration-150">
                        <i class="fas fa-file-invoice-dollar w-6 text-base"></i>
                        <span>Reportes</span>
                    </a>
                    @endif
                    @if(Auth::user()->hasAccess('reportes.trazabilidad'))
                    <a href="{{ route('reportes.trazabilidad') }}" class="flex items-center p-3 text-sm font-semibold rounded-xl {{ request()->routeIs('reportes.trazabilidad') ? 'bg-linear-to-r from-emerald-600 to-emerald-700 text-white shadow-lg shadow-emerald-950/50' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }} transition-all duration-150">
                        <i class="fas fa-project-diagram w-6 text-base"></i>
                        <span>Trazabilidad</span>
                    </a>
                    @endif

                    @if(Auth::user()->hasAccess('usuarios.index'))
                    <a href="{{ route('usuarios.index') }}" class="flex items-center p-3 text-sm font-semibold rounded-xl {{ request()->routeIs('usuarios.*') ? 'bg-linear-to-r from-emerald-600 to-emerald-700 text-white shadow-lg shadow-emerald-950/50' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }} transition-all duration-150">
                        <i class="fas fa-users-cog w-6 text-base"></i>
                        <span>Usuarios</span>
                    </a>
                    @endif

                    @if(Auth::user()->hasAccess('roles.index'))
                    <a href="{{ route('roles.index') }}" class="flex items-center p-3 text-sm font-semibold rounded-xl {{ request()->routeIs('roles.*') ? 'bg-linear-to-r from-emerald-600 to-emerald-700 text-white shadow-lg shadow-emerald-950/50' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }} transition-all duration-150">
                        <i class="fas fa-user-shield w-6 text-base"></i>
                        <span>Roles y Permisos</span>
                    </a>
                    @endif
                </div>

                <div class="mt-4 pt-4 border-t border-slate-800/80 shrink-0">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center p-3 text-sm font-semibold rounded-xl text-red-400 hover:bg-red-500/10 transition-all duration-150">
                            <i class="fas fa-sign-out-alt w-6 text-base"></i>
                            <span>Cerrar Sesión</span>
                        </button>
                    </form>
                </div>
            </nav>
        </div>

        <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
            <div id="overlay" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden"></div>

            <header class="bg-white/95 backdrop-blur-md border-b border-slate-200/80 h-20 shrink-0 flex items-center justify-between px-6 sm:px-8 z-30 shadow-xs">
                <div class="flex items-center space-x-4">
                    <button id="menuBtn" class="md:hidden text-slate-600 p-2.5 rounded-xl hover:bg-slate-100 transition-colors">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                </div>

                <div class="flex items-center space-x-5 ml-auto">
                    <!-- Gestor de Notificaciones (Campanita) -->
                    <div class="relative" id="notification-bell-wrapper">
                        <button id="btnNotificaciones" class="relative p-2.5 text-slate-500 hover:text-slate-900 hover:bg-slate-100 rounded-full transition-all focus:outline-none" title="Notificaciones">
                            <i class="fas fa-bell text-xl"></i>
                            <span id="notification-badge" class="hidden absolute top-0.5 right-0.5 items-center justify-center px-1.5 py-0.5 text-[10px] font-extrabold leading-none text-white bg-red-600 rounded-full shadow-md animate-pulse">
                                0
                            </span>
                        </button>

                        <!-- Menú Desplegable de Notificaciones -->
                        <div id="notification-dropdown" class="hidden absolute right-0 mt-3 w-80 sm:w-96 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden transition-all duration-200">
                            <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <i class="fas fa-bell text-emerald-600 text-base"></i>
                                    <span class="font-bold text-slate-800 text-sm">Notificaciones</span>
                                </div>
                                <button id="btnMarcarTodasLeidasHeader" class="text-xs text-emerald-600 hover:text-emerald-800 hover:underline font-semibold hidden">
                                    Marcar todas leídas
                                </button>
                            </div>
                            <div id="notification-list" class="max-h-80 overflow-y-auto divide-y divide-slate-100 scrollbar-thin">
                                <div class="p-4 text-center text-slate-400 text-sm">Cargando...</div>
                            </div>
                            <div class="p-3 bg-slate-50 border-t border-slate-200 text-center">
                                <a href="{{ route('notificaciones.index') }}" class="text-xs text-emerald-700 hover:text-emerald-900 font-bold inline-flex items-center space-x-1">
                                    <span>Ver todas las notificaciones</span>
                                    <i class="fas fa-chevron-right text-[10px]"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="h-8 w-px bg-slate-200 hidden sm:block"></div>

                    <!-- Usuario Logueado (Lado Derecho con Dropdown) -->
                    <div class="relative" id="user-dropdown-container">
                        <button type="button" id="user-dropdown-btn" class="flex items-center space-x-3.5 pl-1 cursor-pointer hover:opacity-80 transition-opacity focus:outline-none">
                            <div class="text-right hidden sm:block">
                                <p class="text-sm font-bold text-slate-800 leading-tight">{{ Auth::user()->nombre_usuario }}</p>
                                <div class="flex items-center justify-end space-x-1.5 mt-0.5">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span class="text-[11px] text-slate-500 uppercase tracking-wider font-semibold">{{ Auth::user()->rol }}</span>
                                </div>
                            </div>
                            <div class="h-10 w-10 rounded-full bg-linear-to-tr from-emerald-600 to-teal-500 flex items-center justify-center text-white font-black text-base shadow-md shadow-emerald-900/20 uppercase shrink-0 ring-2 ring-emerald-500/30">
                                {{ substr(Auth::user()->nombre_usuario, 0, 1) }}
                            </div>
                        </button>

                        <!-- Dropdown Menu -->
                        <div id="user-dropdown-menu" class="hidden absolute right-0 mt-3 w-56 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden transition-all duration-200">
                            <div class="p-3 bg-slate-50 border-b border-slate-200">
                                <p class="text-sm font-bold text-slate-800">{{ Auth::user()->nombre_usuario }}</p>
                                <p class="text-xs text-slate-500">{{ Auth::user()->email ?? Auth::user()->rol }}</p>
                            </div>
                            <div class="py-1">
                                <a href="{{ route('perfil.index') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition-colors">
                                    <i class="fas fa-user-circle text-slate-400 w-5 text-center"></i>
                                    <span class="font-medium">Mi Perfil</span>
                                </a>
                                <a href="{{ route('perfil.index', ['tab' => 'password']) }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition-colors">
                                    <i class="fas fa-key text-slate-400 w-5 text-center"></i>
                                    <span class="font-medium">Cambiar Contraseña</span>
                                </a>
                                <a href="{{ route('perfil.index', ['tab' => 'reporte']) }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-amber-50 hover:text-amber-700 transition-colors">
                                    <i class="fas fa-bug text-slate-400 w-5 text-center"></i>
                                    <span class="font-medium">Reportar Problema</span>
                                </a>
                            </div>
                            <div class="border-t border-slate-200 py-1">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                        <i class="fas fa-sign-out-alt w-5 text-center"></i>
                                        <span class="font-medium">Cerrar Sesión</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 p-4 md:p-6 lg:p-8 overflow-y-auto scrollbar-thin">
                @yield('content')
            </main>

            <footer class="bg-white border-t border-slate-200 p-3 shrink-0 z-30 no-print">
                <p class="text-center text-xs text-slate-400">
                    &copy; {{ date('Y') }} Leon Plast — Sistema de Control de Producción.
                </p>
            </footer>
        </div>
    </div>

    <div id="toast-container" class="toast-container">
        @if(session('success'))
            <x-toast type="success" :message="session('success')" />
        @endif
        @if(session('error'))
            <x-toast type="error" :message="session('error')" />
        @endif
        @if(session('warning'))
            <x-toast type="warning" :message="session('warning')" />
        @endif
    </div>

    @stack('scripts')

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.toast').forEach(function (el) {
                var autohide = parseInt(el.dataset.autohide) || 4000;
                var timer = setTimeout(function () {
                    el.classList.add('toast-hiding');
                    setTimeout(function () { el.remove(); }, 300);
                }, autohide);
                el.querySelector('.toast-close')?.addEventListener('click', function () {
                    clearTimeout(timer);
                    el.classList.add('toast-hiding');
                    setTimeout(function () { el.remove(); }, 300);
                });
            });
        });

        window.toast = function (message, type) {
            type = type || 'success';
            var icons = {
                success: 'fa-check-circle', error: 'fa-exclamation-circle',
                warning: 'fa-exclamation-triangle', info: 'fa-info-circle'
            };
            var colors = {
                success: '#059669', error: '#dc2626',
                warning: '#d97706', info: '#0284c7'
            };
            var icon = icons[type] || icons.success;
            var bg = colors[type] || colors.success;
            var toast = document.createElement('div');
            toast.className = 'toast';
            toast.style.backgroundColor = bg;
            toast.setAttribute('data-autohide', '4000');
            toast.innerHTML = '<i class="fas ' + icon + '"></i><span>' + message + '</span><button class="toast-close">&times;</button>';
            document.getElementById('toast-container')?.appendChild(toast);
            var timer = setTimeout(function () {
                toast.classList.add('toast-hiding');
                setTimeout(function () { toast.remove(); }, 300);
            }, 4000);
            toast.querySelector('.toast-close').addEventListener('click', function () {
                clearTimeout(timer);
                toast.classList.add('toast-hiding');
                setTimeout(function () { toast.remove(); }, 300);
            });
        };

        const menuBtn = document.getElementById('menuBtn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');

        function toggleMenu() {
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }

        menuBtn?.addEventListener('click', toggleMenu);
        overlay?.addEventListener('click', toggleMenu);

        // Tablas Maestras
        const btnTablasMaestras = document.getElementById('btnTablasMaestras');
        const menuTablasMaestras = document.getElementById('menuTablasMaestras');
        const iconTablasMaestras = document.getElementById('iconTablasMaestras');

        const currentUrl = window.location.href;

        const maestrasSlugs = [
            'unidades-medida', 'tipos-producto', 'productos', 'productos-proceso',
            'procesos-produccion', 'formulas', 'operaciones-produccion',
            'centros-trabajo', 'trabajadores',
            'actividades', 'moldes', 'colores', 'parametros', 'mapeo-terceros'
        ];

        if (maestrasSlugs.some(slug => currentUrl.includes(slug))) {
            menuTablasMaestras?.classList.remove('hidden');
            menuTablasMaestras?.classList.add('flex');
            iconTablasMaestras?.classList.add('rotate-180');
        }

        btnTablasMaestras?.addEventListener('click', () => {
            menuTablasMaestras.classList.toggle('hidden');
            menuTablasMaestras.classList.toggle('flex');
            iconTablasMaestras.classList.toggle('rotate-180');
        });

        // Inventario
        const btnInventario = document.getElementById('btnInventario');
        const menuInventario = document.getElementById('menuInventario');
        const iconInventario = document.getElementById('iconInventario');

        const inventarioSlugs = ['recepciones', 'ajuste', 'extornos', 'alertas', 'despachos'];
        const isInventarioUrl = (
            inventarioSlugs.some(slug => currentUrl.includes(slug)) ||
            (currentUrl.includes('transferencias') && !currentUrl.includes('produccion')) ||
            (currentUrl.includes('/inventario') && !currentUrl.includes('kardex'))
        ) && !currentUrl.includes('/produccion') && !currentUrl.includes('produccion/');

        if (isInventarioUrl) {
            menuInventario?.classList.remove('hidden');
            menuInventario?.classList.add('flex');
            iconInventario?.classList.add('rotate-180');
        }

        if (btnInventario) {
            btnInventario.addEventListener('click', () => {
                menuInventario.classList.toggle('hidden');
                menuInventario.classList.toggle('flex');
                iconInventario.classList.toggle('rotate-180');
            });
        }

        // Produccion
        const btnProduccion = document.getElementById('btnProduccion');
        const menuProduccion = document.getElementById('menuProduccion');
        const iconProduccion = document.getElementById('iconProduccion');

        const produccionSlugs = ['ordenes', 'requerimientos-materiales', 'rutas-produccion', 'mermas', 'ingresos'];
        const isProduccionUrl = currentUrl.includes('/produccion') || currentUrl.includes('produccion/') || produccionSlugs.some(slug => currentUrl.includes(slug));

        if (isProduccionUrl) {
            menuProduccion?.classList.remove('hidden');
            menuProduccion?.classList.add('flex');
            iconProduccion?.classList.add('rotate-180');
        }

        if (btnProduccion) {
            btnProduccion.addEventListener('click', () => {
                menuProduccion.classList.toggle('hidden');
                menuProduccion.classList.toggle('flex');
                iconProduccion.classList.toggle('rotate-180');
            });
        }

        // Cuentas por Pagar
        const btnCuentasPagar = document.getElementById('btnCuentasPagar');
        const menuCuentasPagar = document.getElementById('menuCuentasPagar');
        const iconCuentasPagar = document.getElementById('iconCuentasPagar');

        const cuentasPagarSlugs = ['proveedores', 'compras', 'guia-compras'];

        if (cuentasPagarSlugs.some(slug => currentUrl.includes(slug))) {
            menuCuentasPagar?.classList.remove('hidden');
            menuCuentasPagar?.classList.add('flex');
            iconCuentasPagar?.classList.add('rotate-180');
        }

        if (btnCuentasPagar) {
            btnCuentasPagar.addEventListener('click', () => {
                menuCuentasPagar.classList.toggle('hidden');
                menuCuentasPagar.classList.toggle('flex');
                iconCuentasPagar.classList.toggle('rotate-180');
            });
        }

        // Contabilidad
        const btnContabilidad = document.getElementById('btnContabilidad');
        const menuContabilidad = document.getElementById('menuContabilidad');
        const iconContabilidad = document.getElementById('iconContabilidad');

        const contabilidadSlugs = ['kardex'];

        if (contabilidadSlugs.some(slug => currentUrl.includes(slug))) {
            menuContabilidad?.classList.remove('hidden');
            menuContabilidad?.classList.add('flex');
            iconContabilidad?.classList.add('rotate-180');
        }

        if (btnContabilidad) {
            btnContabilidad.addEventListener('click', () => {
                menuContabilidad.classList.toggle('hidden');
                menuContabilidad.classList.toggle('flex');
                iconContabilidad.classList.toggle('rotate-180');
            });
        }

        // Global Uppercase Converter
        document.addEventListener('input', function(e) {
            if (e.target.tagName.toLowerCase() === 'input' || e.target.tagName.toLowerCase() === 'textarea') {
                const type = e.target.type ? e.target.type.toLowerCase() : '';
                // Exclude fields where uppercase might cause issues
                if (['password', 'email', 'hidden', 'number', 'date', 'time', 'color', 'file'].includes(type)) return;
                
                // Allow bypassing with a class if needed
                if (e.target.classList.contains('no-uppercase')) return;

                // Save cursor position
                let start = e.target.selectionStart;
                let end = e.target.selectionEnd;
                
                e.target.value = e.target.value.toUpperCase();
                
                // Restore cursor position to avoid jumping to the end
                if (start !== null && end !== null) {
                    try { e.target.setSelectionRange(start, end); } catch (err) {}
                }
            }
        });
        // Global Double Submit Prevention
        document.addEventListener('submit', function(e) {
            const form = e.target;
            if (form.classList.contains('allow-double-submit')) return;
            
            if (form.checkValidity && !form.checkValidity()) return;

            const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
            submitButtons.forEach(btn => {
                if (btn.disabled) return;
                // Add a small delay before disabling to ensure the form submission goes through
                setTimeout(() => {
                    btn.disabled = true;
                    if (btn.tagName.toLowerCase() === 'button' && !btn.querySelector('.fa-spinner')) {
                        const originalHtml = btn.innerHTML;
                        btn.dataset.originalHtml = originalHtml;
                        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Procesando...';
                        btn.classList.add('opacity-80', 'cursor-not-allowed');
                    }
                }, 10);
            });
        });

        // ==========================================
        // Lógica del Gestor de Notificaciones Header
        // ==========================================
        const btnNotif = document.getElementById('btnNotificaciones');
        const dropdownNotif = document.getElementById('notification-dropdown');
        const badgeNotif = document.getElementById('notification-badge');
        const listNotif = document.getElementById('notification-list');
        const btnMarcarTodasHeader = document.getElementById('btnMarcarTodasLeidasHeader');

        function fetchNotificaciones() {
            fetch('{{ route("notificaciones.api.unread") }}', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    if (data.unread_count > 0) {
                        badgeNotif.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                        badgeNotif.classList.remove('hidden');
                        badgeNotif.classList.add('inline-flex');
                        if (btnMarcarTodasHeader) btnMarcarTodasHeader.classList.remove('hidden');
                    } else {
                        badgeNotif.classList.add('hidden');
                        badgeNotif.classList.remove('inline-flex');
                        if (btnMarcarTodasHeader) btnMarcarTodasHeader.classList.add('hidden');
                    }

                    if (data.notifications.length === 0) {
                        listNotif.innerHTML = `
                            <div class="p-6 text-center text-slate-400 text-xs">
                                <i class="fas fa-bell-slash text-2xl mb-2 text-slate-300 block"></i>
                                No tienes notificaciones por ahora.
                            </div>`;
                        return;
                    }

                    let html = '';
                    data.notifications.forEach(n => {
                        const iconMap = {
                            warning: 'fa-exclamation-triangle text-amber-500 bg-amber-50',
                            danger: 'fa-exclamation-circle text-red-500 bg-red-50',
                            success: 'fa-check-circle text-emerald-500 bg-emerald-50',
                            info: 'fa-info-circle text-blue-500 bg-blue-50'
                        };
                        const iconClass = iconMap[n.tipo] || iconMap.info;
                        const bgUnread = !n.leido ? 'bg-emerald-50/40 font-medium' : '';
                        // Normalize links: extract path if host doesn't match current
                        let normalizedLink = n.link;
                        if (normalizedLink) {
                            try {
                                const linkUrl = new URL(normalizedLink);
                                if (linkUrl.host !== window.location.host) {
                                    normalizedLink = linkUrl.pathname + linkUrl.search;
                                }
                            } catch(e) { /* keep as-is if not a valid URL */ }
                        }
                        const href = normalizedLink ? `href="${normalizedLink}"` : 'href="javascript:void(0)"';

                        html += `
                            <div class="p-3 text-xs hover:bg-slate-50 transition-colors flex items-start space-x-3 ${bgUnread}">
                                <div class="h-7 w-7 rounded-full shrink-0 flex items-center justify-center ${iconClass} text-xs mt-0.5">
                                    <i class="fas ${iconClass.split(' ')[0]}"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <a ${href} onclick="marcarLeidaHeader(event, ${n.id}, '${normalizedLink || ''}')" class="block">
                                        <p class="font-bold text-slate-800 hover:text-emerald-700 truncate">${n.titulo}</p>
                                        <p class="text-slate-600 mt-0.5 line-clamp-2">${n.mensaje}</p>
                                        <p class="text-[10px] text-slate-400 mt-1">${n.tiempo_hace}</p>
                                    </a>
                                </div>
                            </div>`;
                    });
                    listNotif.innerHTML = html;
                }
            })
            .catch(() => {});
        }

        window.marcarLeidaHeader = function(event, id, link) {
            // Normalize link for redirect
            var redirectLink = link;
            if (redirectLink) {
                try {
                    var linkUrl = new URL(redirectLink, window.location.origin);
                    if (linkUrl.host !== window.location.host) {
                        redirectLink = linkUrl.pathname + linkUrl.search;
                    }
                } catch(e) { /* keep as-is */ }
            }
            fetch(`/notificaciones/${id}/marcar-leida`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(() => {
                if (redirectLink && redirectLink !== 'javascript:void(0)') {
                    window.location.href = redirectLink;
                } else {
                    fetchNotificaciones();
                }
            });
        };

        if (btnNotif) {
            btnNotif.addEventListener('click', (e) => {
                e.stopPropagation();
                dropdownNotif.classList.toggle('hidden');
                fetchNotificaciones();
            });
        }

        if (btnMarcarTodasHeader) {
            btnMarcarTodasHeader.addEventListener('click', () => {
                fetch('{{ route("notificaciones.marcar_todas_leidas") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }).then(() => fetchNotificaciones());
            });
        }

        document.addEventListener('click', (e) => {
            if (dropdownNotif && !dropdownNotif.contains(e.target) && !btnNotif?.contains(e.target)) {
                dropdownNotif.classList.add('hidden');
            }
            // Close user dropdown when clicking outside
            const userDropdownMenu = document.getElementById('user-dropdown-menu');
            const userDropdownBtn = document.getElementById('user-dropdown-btn');
            if (userDropdownMenu && !userDropdownMenu.contains(e.target) && !userDropdownBtn?.contains(e.target)) {
                userDropdownMenu.classList.add('hidden');
            }
        });

        // ==========================================
        // User Profile Dropdown Toggle
        // ==========================================
        const userDropdownBtn = document.getElementById('user-dropdown-btn');
        const userDropdownMenu = document.getElementById('user-dropdown-menu');
        if (userDropdownBtn && userDropdownMenu) {
            userDropdownBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                userDropdownMenu.classList.toggle('hidden');
                // Close notification dropdown if open
                if (dropdownNotif) dropdownNotif.classList.add('hidden');
            });
        }

        // Polling de notificaciones adaptativo con Visibility API
        let notifInterval = null;
        let lastFetchTime = 0;

        function startNotifPolling() {
            if (!notifInterval) {
                // Si pasaron más de 30s desde la última consulta, refrescar inmediatamente
                if (Date.now() - lastFetchTime >= 30000) {
                    fetchNotificaciones();
                    lastFetchTime = Date.now();
                }
                notifInterval = setInterval(() => {
                    if (!document.hidden) {
                        fetchNotificaciones();
                        lastFetchTime = Date.now();
                    }
                }, 30000);
            }
        }

        function stopNotifPolling() {
            if (notifInterval) {
                clearInterval(notifInterval);
                notifInterval = null;
            }
        }

        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                stopNotifPolling();
            } else {
                startNotifPolling();
            }
        });

        // Inicializar polling inicial
        fetchNotificaciones();
        lastFetchTime = Date.now();
        startNotifPolling();

        // ==========================================
        // Global Page Loader for Navigation
        // ==========================================
        document.addEventListener('click', function(e) {
            const link = e.target.closest('a');
            if (!link) return;
            
            const href = link.getAttribute('href');
            // Check if it's a valid internal link for navigation
            if (!e.defaultPrevented && href && !href.startsWith('#') && !href.startsWith('javascript:') && link.getAttribute('target') !== '_blank') {
                const loader = document.getElementById('global-loader');
                if (loader) {
                    loader.classList.remove('hidden');
                    loader.classList.add('flex');
                }
            }
        });
    </script>
</body>
</html>
