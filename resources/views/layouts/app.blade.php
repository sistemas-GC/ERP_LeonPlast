<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - Leon Plast Producción</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/vendor/select2/select2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* =========================================================
           ESTÁNDAR VISUAL GLOBAL: SELECT NATIVO CORPORATIVO
           ========================================================= */
        select:not(.no-custom-select):not(.select2-hidden-accessible) {
            appearance: none !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            background-color: #ffffff;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E") !important;
            background-repeat: no-repeat !important;
            background-position: right 0.75rem center !important;
            background-size: 0.95rem 0.95rem !important;
            padding-right: 2.25rem !important;
            border: 1px solid #cbd5e1;
            border-radius: 0.5rem;
            font-size: 0.8125rem;
            color: #1e293b;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.04);
        }
        select:not(.no-custom-select):not(.select2-hidden-accessible):focus {
            border-color: #059669 !important;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.18) !important;
            outline: none !important;
        }

        /* =========================================================
           ESTÁNDAR VISUAL GLOBAL: SELECT2 CORPORATIVO LEONPLAST
           ========================================================= */
        .select2-container {
            width: 100% !important;
            max-width: 100% !important;
        }
        .select2-container--default .select2-selection--single {
            background-color: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 0.5rem !important;
            height: 2.5rem !important;
            padding: 0.35rem 0.85rem !important;
            transition: all 0.2s ease !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.04) !important;
            display: flex !important;
            align-items: center !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #1e293b !important;
            font-size: 0.8125rem !important;
            font-weight: 500 !important;
            line-height: normal !important;
            padding-left: 0 !important;
            padding-right: 1.5rem !important;
            width: 100% !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #94a3b8 !important;
            font-size: 0.8125rem !important;
            font-weight: 400 !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 100% !important;
            right: 0.75rem !important;
            top: 0 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow b {
            border-color: #64748b transparent transparent transparent !important;
            border-width: 5px 4px 0 4px !important;
            margin-top: 0 !important;
            transition: transform 0.2s ease !important;
        }
        .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
            border-color: transparent transparent #059669 transparent !important;
            border-width: 0 4px 5px 4px !important;
        }
        .select2-container--default.select2-container--focus .select2-selection--single,
        .select2-container--default.select2-container--open .select2-selection--single {
            border-color: #059669 !important;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.18) !important;
            outline: none !important;
        }

        /* Selección Múltiple (Tags / Chips) */
        .select2-container--default .select2-selection--multiple {
            background-color: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 0.5rem !important;
            min-height: 2.5rem !important;
            padding: 0.2rem 0.5rem !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.04) !important;
            transition: all 0.2s ease !important;
        }
        .select2-container--default.select2-container--focus .select2-selection--multiple,
        .select2-container--default.select2-container--open .select2-selection--multiple {
            border-color: #059669 !important;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.18) !important;
            outline: none !important;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #ecfdf5 !important;
            border: 1px solid #a7f3d0 !important;
            color: #065f46 !important;
            border-radius: 0.375rem !important;
            padding: 0.15rem 0.5rem 0.15rem 1.25rem !important;
            font-size: 0.75rem !important;
            font-weight: 600 !important;
            margin-top: 0.25rem !important;
            position: relative !important;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: #047857 !important;
            margin-right: 0 !important;
            font-size: 0.85rem !important;
            border-right: none !important;
            position: absolute !important;
            left: 0.35rem !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
            color: #dc2626 !important;
            background: transparent !important;
        }

        /* Menú Desplegable Flotante (Dropdown) */
        .select2-dropdown {
            background-color: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 0.75rem !important;
            box-shadow: 0 12px 28px -4px rgba(15, 23, 42, 0.14), 0 4px 8px -2px rgba(15, 23, 42, 0.04) !important;
            overflow: hidden !important;
            margin-top: 4px !important;
            z-index: 999999 !important;
            font-family: inherit !important;
        }
        .select2-container--default .select2-search--dropdown {
            padding: 0.5rem 0.625rem !important;
            background-color: #f8fafc !important;
            border-bottom: 1px solid #f1f5f9 !important;
        }
        .select2-container--default .select2-search--dropdown .select2-search__field {
            background-color: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 0.5rem !important;
            padding: 0.45rem 0.75rem !important;
            font-size: 0.8125rem !important;
            color: #1e293b !important;
            outline: none !important;
            transition: all 0.2s ease !important;
            width: 100% !important;
        }
        .select2-container--default .select2-search--dropdown .select2-search__field:focus {
            border-color: #059669 !important;
            box-shadow: 0 0 0 2px rgba(5, 150, 105, 0.18) !important;
        }

        /* Opciones de la lista */
        .select2-results__options {
            max-height: 260px !important;
            padding: 0.25rem !important;
        }
        .select2-results__options::-webkit-scrollbar {
            width: 5px !important;
        }
        .select2-results__options::-webkit-scrollbar-track {
            background: #f8fafc !important;
        }
        .select2-results__options::-webkit-scrollbar-thumb {
            background: #cbd5e1 !important;
            border-radius: 9999px !important;
        }
        .select2-results__options::-webkit-scrollbar-thumb:hover {
            background: #94a3b8 !important;
        }
        .select2-container--default .select2-results__option {
            padding: 0.55rem 0.75rem !important;
            font-size: 0.8125rem !important;
            color: #334155 !important;
            border-radius: 0.375rem !important;
            margin-bottom: 1px !important;
            transition: all 0.12s ease !important;
            font-weight: 500 !important;
        }
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #ecfdf5 !important;
            color: #047857 !important;
            font-weight: 600 !important;
        }
        .select2-container--default .select2-results__option[aria-selected=true] {
            background-color: #d1fae5 !important;
            color: #065f46 !important;
            font-weight: 700 !important;
        }
        .select2-container--default .select2-results__option--disabled {
            color: #94a3b8 !important;
            background-color: transparent !important;
            font-style: italic !important;
        }

        .swal2-popup { font-size: 0.85rem !important; padding: 1.25rem !important; border-radius: 1rem !important; }
        .swal2-title { font-size: 1.15rem !important; font-weight: 700 !important; }
        .swal2-html-container { font-size: 0.85rem !important; line-height: 1.5 !important; margin: 0.75em 1em 0.5em 1em !important; }
        .swal2-styled.swal2-confirm, .swal2-styled.swal2-cancel { font-size: 0.8rem !important; padding: 0.4rem 1.25rem !important; border-radius: 0.5rem !important; }
        /* Reglas para Modo Embebido en Pestañas */
        body.is-embedded #sidebar,
        body.is-embedded header,
        body.is-embedded footer,
        body.is-embedded #overlay {
            display: none !important;
        }
        body.is-embedded {
            background-color: #f8fafc !important;
        }
        body.is-embedded main {
            padding: 0.5rem 0.75rem !important;
            overflow-x: hidden;
            width: 100% !important;
            max-width: 100% !important;
        }
        body.is-embedded .container {
            max-width: 100% !important;
            width: 100% !important;
            padding-left: 0.25rem !important;
            padding-right: 0.25rem !important;
            padding-top: 0.25rem !important;
            padding-bottom: 2rem !important;
            margin: 0 auto !important;
        }

        /* =========================================================
           SIDEBAR RESPONSIVE & MODO RIEL / MINI (TABLET / DESKTOP)
           ========================================================= */
        #sidebar-wrapper {
            width: 16rem;
            transition: width 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        #sidebar {
            width: 16rem;
            transition: width 0.25s cubic-bezier(0.4, 0, 0.2, 1), transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @media (max-width: 767px) {
            #sidebar-wrapper {
                width: 0 !important;
                display: none !important;
            }
            #sidebar {
                position: fixed !important;
            }
        }

        #sidebar-wrapper.is-mini {
            width: 4.75rem !important;
        }

        #sidebar-wrapper.is-mini #sidebar {
            width: 4.75rem !important;
        }

        #sidebar-wrapper.is-mini #sidebar .sidebar-text,
        #sidebar-wrapper.is-mini #sidebar .sidebar-chevron,
        #sidebar-wrapper.is-mini #sidebar .sidebar-submenu {
            display: none !important;
        }

        #sidebar-wrapper.is-mini #sidebar .sidebar-item-link,
        #sidebar-wrapper.is-mini #sidebar .sidebar-item-btn {
            justify-content: center !important;
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
        }

        #sidebar-wrapper.is-mini #sidebar .sidebar-item-link i,
        #sidebar-wrapper.is-mini #sidebar .sidebar-item-btn i:first-child {
            margin-right: 0 !important;
            text-align: center !important;
            font-size: 1.15rem !important;
        }

        #sidebar-wrapper.is-mini #sidebar .sidebar-logo-full {
            display: none !important;
        }

        #sidebar-wrapper.is-mini #sidebar .sidebar-logo-mini {
            display: flex !important;
        }

        /* Expansión flotante por Interacción (Hover o Touch .is-expanded) */
        #sidebar-wrapper.is-mini:hover #sidebar,
        #sidebar-wrapper.is-mini.is-expanded #sidebar {
            position: fixed !important;
            top: 0;
            bottom: 0;
            left: 0;
            width: 16rem !important;
            z-index: 60 !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.75) !important;
        }

        #sidebar-wrapper.is-mini:hover #sidebar .sidebar-text,
        #sidebar-wrapper.is-mini.is-expanded #sidebar .sidebar-text {
            display: inline-block !important;
            white-space: nowrap !important;
            animation: sidebarFadeIn 0.2s ease-in-out;
        }

        #sidebar-wrapper.is-mini:hover #sidebar .sidebar-chevron,
        #sidebar-wrapper.is-mini.is-expanded #sidebar .sidebar-chevron {
            display: inline-block !important;
        }

        #sidebar-wrapper.is-mini:hover #sidebar .sidebar-submenu.flex,
        #sidebar-wrapper.is-mini.is-expanded #sidebar .sidebar-submenu.flex {
            display: flex !important;
        }

        #sidebar-wrapper.is-mini:hover #sidebar .sidebar-item-link,
        #sidebar-wrapper.is-mini.is-expanded #sidebar .sidebar-item-link,
        #sidebar-wrapper.is-mini:hover #sidebar .sidebar-item-btn,
        #sidebar-wrapper.is-mini.is-expanded #sidebar .sidebar-item-btn {
            justify-content: space-between !important;
            padding-left: 0.75rem !important;
            padding-right: 0.75rem !important;
        }

        #sidebar-wrapper.is-mini:hover #sidebar .sidebar-item-link i,
        #sidebar-wrapper.is-mini.is-expanded #sidebar .sidebar-item-link i,
        #sidebar-wrapper.is-mini:hover #sidebar .sidebar-item-btn i:first-child,
        #sidebar-wrapper.is-mini.is-expanded #sidebar .sidebar-item-btn i:first-child {
            margin-right: 0.75rem !important;
        }

        #sidebar-wrapper.is-mini:hover #sidebar .sidebar-logo-full,
        #sidebar-wrapper.is-mini.is-expanded #sidebar .sidebar-logo-full {
            display: block !important;
        }

        #sidebar-wrapper.is-mini:hover #sidebar .sidebar-logo-mini,
        #sidebar-wrapper.is-mini.is-expanded #sidebar .sidebar-logo-mini {
            display: none !important;
        }

        @keyframes sidebarFadeIn {
            from { opacity: 0; transform: translateX(-6px); }
            to { opacity: 1; transform: translateX(0); }
        }
    </style>
    @stack('styles')
    @php
        $isEmbeddedLayout = request()->boolean('embedded');
    @endphp
</head>
<body class="font-sans leading-normal tracking-normal text-slate-800 {{ $isEmbeddedLayout ? 'is-embedded' : '' }}">

    <!-- Global Page Loader -->
    <div id="global-loader" class="fixed inset-0 z-9999 bg-slate-900/50 backdrop-blur-sm items-center justify-center hidden">
        <div class="bg-white p-6 rounded-2xl shadow-2xl flex flex-col items-center">
            <i class="fas fa-spinner fa-spin text-4xl text-emerald-600 mb-4"></i>
            <p class="text-slate-700 font-semibold animate-pulse">Cargando...</p>
        </div>
    </div>

    <div class="flex h-screen overflow-hidden">
        @if(!$isEmbeddedLayout)
        <aside id="sidebar-wrapper" class="shrink-0 transition-all duration-300 md:block w-64">
            <div id="sidebar" class="fixed md:relative z-50 inset-y-0 left-0 w-64 h-full bg-linear-to-b from-slate-900 via-slate-900 to-slate-950 text-slate-300 flex flex-col shrink-0 shadow-2xl border-r border-slate-800/80 -translate-x-full md:translate-x-0 transition-all duration-300 ease-out">
                <div class="h-20 flex items-center justify-center border-b border-slate-800/80 shrink-0 bg-white/95 px-4 overflow-hidden">
                    <img src="{{ asset('img/logo.png') }}" alt="LEON PLAST" class="h-11 w-auto object-contain sidebar-logo-full">
                    <div class="sidebar-logo-mini hidden items-center justify-center">
                        <span class="w-10 h-10 rounded-xl bg-emerald-600 text-white font-extrabold flex items-center justify-center text-base shadow-sm">LP</span>
                    </div>
                </div>

                <nav class="flex-1 mt-4 px-3 pb-4 overflow-y-auto scrollbar-thin space-y-6">
                    <div class="space-y-1">
                        @php
                            $routeDash = '#';
                            if(Auth::user()->rol == 'Administrador') $routeDash = route('admin.dashboard');
                            elseif(Auth::user()->rol == 'Supervisor') $routeDash = route('supervisor.dashboard');
                            elseif(Auth::user()->rol == 'Especialista') $routeDash = route('especialista.dashboard');
                            elseif(Auth::user()->rol == 'ALMACEN') $routeDash = route('almacen.dashboard');
                            else $routeDash = route('dashboard');
                        @endphp
                        <a href="{{ $routeDash }}" class="sidebar-item-link flex items-center p-3 text-sm font-semibold rounded-xl {{ request()->routeIs('*.dashboard') ? 'bg-linear-to-r from-emerald-600 to-emerald-700 text-white shadow-lg shadow-emerald-950/50' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }} transition-all duration-150" title="Dashboard">
                            <i class="fas fa-chart-line w-6 text-base"></i>
                            <span class="sidebar-text">Dashboard</span>
                        </a>

                        @if(Auth::user()->hasAnyAccess(['procesos_produccion.index', 'formulas.index', 'productos.index', 'productos_proceso.index', 'tipos_producto.index', 'unidades_medida.index', 'operaciones_produccion.index', 'centros_trabajo.index', 'trabajadores.index', 'proveedores.index', 'actividades.index', 'moldes.index', 'colores.index']))
                        <div>
                            <button type="button" id="btnTablasMaestras" class="sidebar-item-btn w-full flex items-center justify-between p-3 text-sm font-semibold rounded-xl text-slate-400 hover:bg-slate-800/60 hover:text-white transition-all duration-150 focus:outline-none cursor-pointer" title="Tablas Maestras">
                                <div class="flex items-center">
                                    <i class="fas fa-database w-6 text-base"></i>
                                    <span class="sidebar-text">Tablas Maestras</span>
                                </div>
                                <i id="iconTablasMaestras" class="sidebar-chevron fas fa-chevron-down text-xs transition-transform duration-200"></i>
                            </button>

                            <div id="menuTablasMaestras" class="sidebar-submenu hidden flex-col mt-1 pl-9 pr-1 space-y-1">
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
                            <button type="button" id="btnCuentasPagar" class="sidebar-item-btn w-full flex items-center justify-between p-3 text-sm font-semibold rounded-xl text-slate-400 hover:bg-slate-800/60 hover:text-white transition-all duration-150 focus:outline-none cursor-pointer" title="Cuentas por Pagar">
                                <div class="flex items-center">
                                    <i class="fas fa-file-invoice-dollar w-6 text-base"></i>
                                    <span class="sidebar-text">Cuentas por Pagar</span>
                                </div>
                                <i id="iconCuentasPagar" class="sidebar-chevron fas fa-chevron-down text-xs transition-transform duration-200"></i>
                            </button>

                            <div id="menuCuentasPagar" class="sidebar-submenu hidden flex-col mt-1 pl-9 pr-1 space-y-1">
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
                            <button type="button" id="btnInventario" class="sidebar-item-btn w-full flex items-center justify-between p-3 text-sm font-semibold rounded-xl text-slate-400 hover:bg-slate-800/60 hover:text-white transition-all duration-150 focus:outline-none cursor-pointer" title="Inventario">
                                <div class="flex items-center">
                                    <i class="fas fa-boxes w-6 text-base"></i>
                                    <span class="sidebar-text">Inventario</span>
                                </div>
                                <i id="iconInventario" class="sidebar-chevron fas fa-chevron-down text-xs transition-transform duration-200"></i>
                            </button>

                            <div id="menuInventario" class="sidebar-submenu hidden flex-col mt-1 pl-9 pr-1 space-y-1">
                                @if(Auth::user()->hasAccess('requerimientos_materiales.atender') || Auth::user()->hasAccess('inventario.despachos'))
                                <a href="{{ route('inventario.despachos.index') }}" class="block p-2 text-xs font-semibold {{ request()->routeIs('inventario.despachos*') ? 'bg-emerald-600/20 text-emerald-400 font-bold border-l-2 border-emerald-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }} rounded-r-lg transition-all"><i class="fas fa-box-open mr-1 text-indigo-300"></i> Despachos</a>
                                @endif

                                @if(Auth::user()->hasAccess('inventario.index'))<a href="{{ route('inventario.index') }}" class="block p-2 text-xs font-semibold {{ request()->routeIs('inventario.index') ? 'bg-emerald-600/20 text-emerald-400 font-bold border-l-2 border-emerald-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }} rounded-r-lg transition-all"><i class="fas fa-boxes mr-1.5"></i>Existencias</a>@endif

                                @if(Auth::user()->hasAccess('inventario.kardex_fisico') || Auth::user()->hasAccess('inventario.index'))
                                <a href="{{ route('inventario.kardex_fisico') }}" class="block p-2 text-xs font-semibold {{ request()->routeIs('inventario.kardex_fisico') ? 'bg-emerald-600/20 text-emerald-400 font-bold border-l-2 border-emerald-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }} rounded-r-lg transition-all"><i class="fas fa-clipboard-list mr-1.5 text-teal-400"></i>Kardex Físico</a>
                                @endif

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
                            <button type="button" id="btnContabilidad" class="sidebar-item-btn w-full flex items-center justify-between p-3 text-sm font-semibold rounded-xl text-slate-400 hover:bg-slate-800/60 hover:text-white transition-all duration-150 focus:outline-none cursor-pointer" title="Contabilidad">
                                <div class="flex items-center">
                                    <i class="fas fa-book w-6 text-base"></i>
                                    <span class="sidebar-text">Contabilidad</span>
                                </div>
                                <i id="iconContabilidad" class="sidebar-chevron fas fa-chevron-down text-xs transition-transform duration-200"></i>
                            </button>

                            <div id="menuContabilidad" class="sidebar-submenu hidden flex-col mt-1 pl-9 pr-1 space-y-1">
                                @if(Auth::user()->hasAccess('inventario.kardex'))
                                <a href="{{ route('inventario.kardex') }}" class="block p-2 text-xs font-semibold {{ request()->routeIs('inventario.kardex') ? 'bg-emerald-600/20 text-emerald-400 font-bold border-l-2 border-emerald-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }} rounded-r-lg transition-all"><i class="fas fa-book-open mr-1 text-emerald-400"></i> Kardex Movimientos</a>
                                @endif
                            </div>
                        </div>
                        @endif

                        @if(Auth::user()->hasAnyAccess(['produccion.ordenes.index', 'ordenes.procesos.index', 'ordenes.procesos.ejecutar', 'mermas.index', 'produccion.ingresos', 'requerimientos_materiales.index', 'produccion.transferencias.index']))
                        <div>
                            <button type="button" id="btnProduccion" class="sidebar-item-btn w-full flex items-center justify-between p-3 text-sm font-semibold rounded-xl text-slate-400 hover:bg-slate-800/60 hover:text-white transition-all duration-150 focus:outline-none cursor-pointer" title="Producción">
                                <div class="flex items-center">
                                    <i class="fas fa-industry w-6 text-base"></i>
                                    <span class="sidebar-text">Producción</span>
                                </div>
                                <i id="iconProduccion" class="sidebar-chevron fas fa-chevron-down text-xs transition-transform duration-200"></i>
                            </button>

                            <div id="menuProduccion" class="sidebar-submenu hidden flex-col mt-1 pl-9 pr-1 space-y-1">
                                @if(Auth::user()->hasAccess('requerimientos_materiales.index'))
                                <a href="{{ route('requerimientos_materiales.index') }}" class="block p-2 text-xs font-semibold {{ request()->routeIs('requerimientos_materiales*') ? 'bg-emerald-600/20 text-emerald-400 font-bold border-l-2 border-emerald-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }} rounded-r-lg transition-all"><i class="fas fa-clipboard-list w-4 mr-1"></i> Requerimientos</a>
                                @endif
                                @if(Auth::user()->hasAccess('produccion.ordenes.index') || Auth::user()->hasAccess('ordenes.procesos.index') || Auth::user()->hasAccess('ordenes.procesos.ejecutar'))
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
                        <a href="{{ route('almacenes.index') }}" class="sidebar-item-link flex items-center p-3 text-sm font-semibold rounded-xl {{ request()->routeIs('almacenes.*') ? 'bg-linear-to-r from-emerald-600 to-emerald-700 text-white shadow-lg shadow-emerald-950/50' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }} transition-all duration-150" title="Almacén">
                            <i class="fas fa-warehouse w-6 text-base"></i>
                            <span class="sidebar-text">Almacén</span>
                        </a>
                        @endif

                        @if(Auth::user()->hasAccess('terceros.salidas.index'))
                        <a href="{{ route('terceros.salidas.index') }}" class="sidebar-item-link flex items-center p-3 text-sm font-semibold rounded-xl {{ request()->routeIs('terceros.salidas.*') ? 'bg-linear-to-r from-emerald-600 to-emerald-700 text-white shadow-lg shadow-emerald-950/50' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }} transition-all duration-150" title="Terceros - Envíos">
                            <i class="fas fa-truck-loading w-6 text-base"></i>
                            <span class="sidebar-text">Terceros - Envíos</span>
                        </a>
                        @endif
                        @if(Auth::user()->hasAccess('terceros.liquidacion.index'))
                        <a href="{{ route('terceros.liquidacion.index') }}" class="sidebar-item-link flex items-center p-3 text-sm font-semibold rounded-xl {{ request()->routeIs('terceros.liquidacion.*') ? 'bg-linear-to-r from-emerald-600 to-emerald-700 text-white shadow-lg shadow-emerald-950/50' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }} transition-all duration-150" title="Terceros">
                            <i class="fas fa-balance-scale w-6 text-base"></i>
                            <span class="sidebar-text">Terceros</span>
                        </a>
                        @endif

                        @if(Auth::user()->hasAccess('reportes.index'))
                        <a href="{{ route('reportes.index') }}" class="sidebar-item-link flex items-center p-3 text-sm font-semibold rounded-xl {{ request()->routeIs('reportes.index') || request()->routeIs('reportes.produccion') || request()->routeIs('reportes.inventario') ? 'bg-linear-to-r from-emerald-600 to-emerald-700 text-white shadow-lg shadow-emerald-950/50' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }} transition-all duration-150" title="Reportes">
                            <i class="fas fa-file-invoice-dollar w-6 text-base"></i>
                            <span class="sidebar-text">Reportes</span>
                        </a>
                        @endif
                        @if(Auth::user()->hasAccess('reportes.trazabilidad'))
                        <a href="{{ route('reportes.trazabilidad') }}" class="sidebar-item-link flex items-center p-3 text-sm font-semibold rounded-xl {{ request()->routeIs('reportes.trazabilidad') ? 'bg-linear-to-r from-emerald-600 to-emerald-700 text-white shadow-lg shadow-emerald-950/50' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }} transition-all duration-150" title="Trazabilidad">
                            <i class="fas fa-project-diagram w-6 text-base"></i>
                            <span class="sidebar-text">Trazabilidad</span>
                        </a>
                        @endif

                        @if(Auth::user()->hasAccess('usuarios.index'))
                        <a href="{{ route('usuarios.index') }}" class="sidebar-item-link flex items-center p-3 text-sm font-semibold rounded-xl {{ request()->routeIs('usuarios.*') ? 'bg-linear-to-r from-emerald-600 to-emerald-700 text-white shadow-lg shadow-emerald-950/50' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }} transition-all duration-150" title="Usuarios">
                            <i class="fas fa-users-cog w-6 text-base"></i>
                            <span class="sidebar-text">Usuarios</span>
                        </a>
                        @endif

                        @if(Auth::user()->hasAccess('roles.index'))
                        <a href="{{ route('roles.index') }}" class="sidebar-item-link flex items-center p-3 text-sm font-semibold rounded-xl {{ request()->routeIs('roles.*') ? 'bg-linear-to-r from-emerald-600 to-emerald-700 text-white shadow-lg shadow-emerald-950/50' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }} transition-all duration-150" title="Roles y Permisos">
                            <i class="fas fa-user-shield w-6 text-base"></i>
                            <span class="sidebar-text">Roles y Permisos</span>
                        </a>
                        @endif
                    </div>

                    <div class="mt-4 pt-4 border-t border-slate-800/80 shrink-0">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="sidebar-item-btn w-full flex items-center p-3 text-sm font-semibold rounded-xl text-red-400 hover:bg-red-500/10 transition-all duration-150 cursor-pointer" title="Cerrar Sesión">
                                <i class="fas fa-sign-out-alt w-6 text-base"></i>
                                <span class="sidebar-text">Cerrar Sesión</span>
                            </button>
                        </form>
                    </div>
                </nav>
            </div>
        </aside>
        @endif

        <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
            <div id="overlay" class="fixed inset-0 bg-black/40 backdrop-blur-2xs z-40 hidden transition-all"></div>

            @if(!$isEmbeddedLayout)
            <header class="bg-white/95 backdrop-blur-md border-b border-slate-200/80 h-20 shrink-0 flex items-center justify-between px-4 sm:px-8 z-30 shadow-xs">
                <div class="flex items-center space-x-3">
                    <button id="menuBtn" type="button" class="text-slate-600 p-2.5 rounded-xl hover:bg-slate-100 hover:text-slate-900 transition-colors focus:outline-none cursor-pointer" title="Alternar menú lateral">
                        <i class="fas fa-bars text-xl"></i>
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
            @endif

            <main class="flex-1 {{ $isEmbeddedLayout ? 'p-2 sm:p-4' : 'p-4 md:p-6 lg:p-8' }} overflow-y-auto scrollbar-thin">
                @yield('content')
            </main>

            @if(!$isEmbeddedLayout)
            <footer class="bg-white border-t border-slate-200 p-3 shrink-0 z-30 no-print">
                <p class="text-center text-xs text-slate-400">
                    &copy; {{ date('Y') }} Leon Plast — Sistema de Control de Producción.
                </p>
            </footer>
            @endif
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

    <!-- Scripts Base Globales (jQuery & Select2 con Configuración Corporativa) -->
    <script src="/vendor/jquery/jquery.min.js"></script>
    <script src="/vendor/select2/select2.min.js"></script>
    <script>
        if (window.jQuery && typeof window.jQuery.fn.select2 !== 'undefined') {
            jQuery.fn.select2.defaults.set('width', '100%');
            jQuery.fn.select2.defaults.set('language', {
                noResults: function() { return "No se encontraron resultados"; },
                searching: function() { return "Buscando..."; },
                inputTooShort: function(args) { return "Ingrese más caracteres..."; }
            });
        }
    </script>

    @stack('scripts')

    <script>
        if (window.self !== window.top) {
            document.body.classList.add('is-embedded');
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('a[href]').forEach(function(a) {
                    var href = a.getAttribute('href');
                    if (href && !href.startsWith('#') && !href.startsWith('javascript:') && !href.startsWith('mailto:') && !href.includes('embedded=1') && !a.target) {
                        var sep = href.includes('?') ? '&' : '?';
                        a.setAttribute('href', href + sep + 'embedded=1');
                    }
                });
                document.querySelectorAll('form').forEach(function(f) {
                    if (!f.querySelector('input[name="embedded"]')) {
                        var hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = 'embedded';
                        hidden.value = '1';
                        f.appendChild(hidden);
                    }
                });
            });
        }

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

        // ==========================================
        // Sidebar Responsive (Tablet / Desktop / Móvil)
        // ==========================================
        const menuBtn = document.getElementById('menuBtn');
        const sidebarWrapper = document.getElementById('sidebar-wrapper');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');

        function isTabletOrTouchDevice() {
            // 1. Detectar si el dispositivo es táctil primario (típico de tablets y móviles)
            const isTouchPrimary = window.matchMedia && (
                window.matchMedia('(hover: none) and (pointer: coarse)').matches ||
                (window.matchMedia('(pointer: coarse)').matches && navigator.maxTouchPoints > 0)
            );

            // 2. Detección de tablets comunes por User-Agent y iPadOS
            const ua = navigator.userAgent.toLowerCase();
            const isTabletUA = /ipad|tablet|(android(?!.*mobile))|(windows(?!.*phone)(.*touch))|kindle|playbook|silk/i.test(ua) ||
                               (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

            // 3. Es Tablet si cuenta con hardware táctil y resolución típica de tablet (hasta 1366px en horizontal)
            return (isTouchPrimary || isTabletUA) && window.innerWidth <= 1366;
        }

        function updateSidebarMode() {
            if (!sidebarWrapper || !sidebar) return;
            const width = window.innerWidth;
            const savedMode = localStorage.getItem('leonplast_sidebar_mode');

            if (width < 768) {
                // Móvil: drawer offcanvas
                sidebarWrapper.classList.remove('is-mini', 'is-expanded');
                sidebar.classList.add('-translate-x-full');
            } else {
                const isTablet = isTabletOrTouchDevice();

                if (!isTablet) {
                    // En PC de escritorio / monitor con mouse: siempre COMPLETO por defecto
                    if (savedMode === 'mini') {
                        sidebarWrapper.classList.add('is-mini');
                        sidebarWrapper.classList.remove('is-expanded');
                    } else {
                        sidebarWrapper.classList.remove('is-mini', 'is-expanded');
                    }
                } else {
                    // En Tablet (vertical y horizontal): Modo Riel (mini) por defecto
                    if (savedMode === 'full') {
                        sidebarWrapper.classList.remove('is-mini', 'is-expanded');
                    } else {
                        sidebarWrapper.classList.add('is-mini');
                        sidebarWrapper.classList.remove('is-expanded');
                    }
                }
                sidebar.classList.remove('-translate-x-full');
            }
        }

        updateSidebarMode();

        let resizeDebounce;
        window.addEventListener('resize', () => {
            clearTimeout(resizeDebounce);
            resizeDebounce = setTimeout(() => {
                if (window.innerWidth >= 768 && overlay) {
                    overlay.classList.add('hidden');
                }
                updateSidebarMode();
            }, 100);
        });

        window.addEventListener('orientationchange', () => {
            setTimeout(updateSidebarMode, 150);
        });

        // Alternar menú (botón hamburguesa)
        if (menuBtn && sidebarWrapper && sidebar) {
            menuBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (window.innerWidth < 768) {
                    sidebar.classList.toggle('-translate-x-full');
                    overlay?.classList.toggle('hidden');
                } else {
                    sidebarWrapper.classList.toggle('is-mini');
                    sidebarWrapper.classList.remove('is-expanded');
                    const isMini = sidebarWrapper.classList.contains('is-mini');
                    localStorage.setItem('leonplast_sidebar_mode', isMini ? 'mini' : 'full');
                }
            });
        }

        // Clic en overlay cierra móvil o repliega tablet expandido
        overlay?.addEventListener('click', () => {
            if (sidebar) sidebar.classList.add('-translate-x-full');
            if (sidebarWrapper) sidebarWrapper.classList.remove('is-expanded');
            overlay?.classList.add('hidden');
        });

        // Interacción táctil en tablet (vertical y horizontal): al tocar el sidebar en modo mini
        if (sidebarWrapper) {
            sidebarWrapper.addEventListener('click', (e) => {
                // Si el usuario hizo clic directamente en un enlace <a> de navegación, NUNCA bloquear ni retrasar
                const link = e.target.closest('a');
                if (link && link.getAttribute('href') && !link.getAttribute('href').startsWith('#')) {
                    return;
                }

                if (sidebarWrapper.classList.contains('is-mini') && window.innerWidth >= 768) {
                    if (!sidebarWrapper.classList.contains('is-expanded')) {
                        sidebarWrapper.classList.add('is-expanded');
                        overlay?.classList.remove('hidden');
                    }
                }
            });

            // Si se toca fuera del sidebar en modo táctil expandido, replegar a mini
            document.addEventListener('click', (e) => {
                if (sidebarWrapper.classList.contains('is-expanded') && !sidebarWrapper.contains(e.target) && !menuBtn?.contains(e.target)) {
                    sidebarWrapper.classList.remove('is-expanded');
                    overlay?.classList.add('hidden');
                }
            });
        }

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

        // ==========================================
        // Detección de Modo Embebido (Iframes / Workbench)
        // ==========================================
        if (window.self !== window.top) {
            document.body.classList.add('is-embedded');
        }
    </script>
</body>
</html>
