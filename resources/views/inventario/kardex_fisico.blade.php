@extends('layouts.app')
@section('title', 'Kardex Físico')

@section('content')
<div class="container mx-auto pb-8 md:pb-10">

    <!-- Encabezado de Página -->
    <x-page-header 
        title="Kardex Físico" 
        subtitle="Control cronológico de existencias en unidades físicas por almacén y producto."
    >
        <x-slot:actions>
            <a href="{{ route('inventario.kardex_fisico.exportar.excel', request()->query()) }}" data-no-loader="true" class="inline-flex items-center gap-2 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs sm:text-sm font-bold rounded-lg shadow-sm transition">
                <i class="fas fa-file-excel"></i>
                <span>Exportar Excel</span>
            </a>
            <button onclick="window.print()" type="button" class="inline-flex items-center gap-2 px-3.5 py-2 bg-slate-700 hover:bg-slate-800 text-white text-xs sm:text-sm font-bold rounded-lg shadow-sm transition no-print">
                <i class="fas fa-print"></i>
                <span>Imprimir</span>
            </button>
        </x-slot:actions>
    </x-page-header>

    <!-- Barra de Filtros Personalizada con Estándar Corporativo -->
    <div class="bg-white rounded-xl shadow-md border border-slate-200/80 p-4 md:p-5 mb-5 no-print">
        <form method="GET" action="{{ route('inventario.kardex_fisico') }}" id="filterForm">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                
                {{-- 1. Filtro Almacén (Custom Dropdown) --}}
                <div class="relative" id="wrapper_dropdown_almacen">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                        <i class="fas fa-warehouse text-emerald-600 mr-1"></i> Almacén
                    </label>
                    <input type="hidden" name="codigo_almacen" id="val_almacen" value="{{ request('codigo_almacen') }}">
                    
                    @php
                        $almSel = $almacenes->firstWhere('codigo_almacen', request('codigo_almacen'));
                    @endphp
                    
                    <button type="button" id="btn_toggle_almacen" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-left bg-white text-slate-800 shadow-xs flex items-center justify-between focus:border-primary focus:ring-2 focus:ring-primary/20 transition cursor-pointer">
                        <span id="label_almacen" class="truncate font-medium text-xs sm:text-sm text-slate-800">
                            {{ $almSel ? $almSel->codigo_almacen . ' - ' . $almSel->descripcion : '-- Todos los Almacenes --' }}
                        </span>
                        <i id="chevron_almacen" class="fas fa-chevron-down text-xs text-slate-400 transition-transform duration-200 shrink-0 ml-2"></i>
                    </button>

                    <div id="menu_almacen" class="hidden absolute left-0 right-0 z-50 mt-1 bg-white border border-slate-200 rounded-xl shadow-2xl overflow-hidden">
                        <div class="p-2 border-b border-slate-100 bg-slate-50">
                            <div class="relative">
                                <input type="text" id="search_almacen" placeholder="Buscar almacén..." class="w-full pl-8 pr-3 py-1.5 text-xs border border-slate-300 rounded-lg focus:border-primary focus:ring-1 focus:ring-primary outline-none bg-white">
                                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            </div>
                        </div>
                        <div id="options_almacen" class="max-h-56 overflow-y-auto divide-y divide-slate-50 text-xs">
                            <div class="p-2.5 hover:bg-emerald-50 text-slate-700 cursor-pointer transition flex items-center justify-between option-alm {{ !request('codigo_almacen') ? 'bg-emerald-50/60 font-bold text-emerald-800' : '' }}" onclick="selectAlmacen('', '-- Todos los Almacenes --')">
                                <span class="font-medium">-- Todos los Almacenes --</span>
                                @if(!request('codigo_almacen'))
                                    <i class="fas fa-check text-emerald-600"></i>
                                @endif
                            </div>
                            @foreach($almacenes as $alm)
                                <div class="p-2.5 hover:bg-emerald-50 text-slate-700 cursor-pointer transition flex items-center justify-between option-alm {{ request('codigo_almacen') == $alm->codigo_almacen ? 'bg-emerald-50/60 font-bold text-emerald-800' : '' }}" data-search="{{ strtolower($alm->codigo_almacen . ' ' . $alm->descripcion) }}" onclick="selectAlmacen('{{ $alm->codigo_almacen }}', '{{ $alm->codigo_almacen }} - {{ addslashes($alm->descripcion) }}')">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono font-bold text-[10px] shrink-0">{{ $alm->codigo_almacen }}</span>
                                        <span class="truncate">{{ $alm->descripcion }}</span>
                                    </div>
                                    @if(request('codigo_almacen') == $alm->codigo_almacen)
                                        <i class="fas fa-check text-emerald-600 shrink-0 ml-2"></i>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- 2. Filtro Producto / Material (Predictivo Elegante Custom) --}}
                <div class="relative" id="wrapper_dropdown_producto">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                        <i class="fas fa-cube text-indigo-600 mr-1"></i> Producto / Material
                    </label>
                    <input type="hidden" name="codigo_producto" id="val_producto" value="{{ request('codigo_producto') }}">
                    
                    <div class="relative">
                        <input 
                            type="text" 
                            id="search_producto_input" 
                            value="{{ $productoSeleccionado ? $productoSeleccionado->codigo . ' - ' . $productoSeleccionado->descripcion : request('codigo_producto') }}" 
                            placeholder="Buscar por código o descripción..." 
                            class="w-full rounded-lg border border-slate-300 pl-3 pr-8 py-2 text-sm text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs font-medium"
                            autocomplete="off"
                        >
                        <button type="button" id="btn_clear_producto" onclick="clearProducto()" class="{{ request('codigo_producto') ? '' : 'hidden' }} absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-red-500 transition cursor-pointer p-1" title="Limpiar filtro de producto">
                            <i class="fas fa-times-circle text-xs"></i>
                        </button>
                        <i id="icon_search_producto" class="{{ request('codigo_producto') ? 'hidden' : '' }} fas fa-search absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400 pointer-events-none"></i>
                    </div>

                    <div id="menu_producto" class="hidden absolute left-0 right-0 z-50 mt-1 bg-white border border-slate-200 rounded-xl shadow-2xl overflow-hidden max-h-60 flex flex-col">
                        <div id="options_producto" class="overflow-y-auto divide-y divide-slate-100 text-xs flex-1">
                            {{-- Se genera dinámicamente con JS --}}
                        </div>
                        <div id="empty_producto" class="hidden p-4 text-center text-xs text-slate-400">
                            No se encontraron productos o materiales
                        </div>
                    </div>
                </div>

                {{-- 3. Filtro Tipo de Movimiento (Custom Dropdown) --}}
                <div class="relative" id="wrapper_dropdown_tipo">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                        <i class="fas fa-exchange-alt text-amber-600 mr-1"></i> Tipo Movimiento
                    </label>
                    <input type="hidden" name="tipo_movimiento" id="val_tipo_movimiento" value="{{ request('tipo_movimiento') }}">
                    
                    <button type="button" id="btn_toggle_tipo" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-left bg-white text-slate-800 shadow-xs flex items-center justify-between focus:border-primary focus:ring-2 focus:ring-primary/20 transition cursor-pointer">
                        <span id="label_tipo" class="truncate font-medium text-xs sm:text-sm">
                            @if(request('tipo_movimiento') === 'INGRESO')
                                <span class="text-emerald-700 font-bold"><i class="fas fa-arrow-down mr-1"></i> INGRESO (Entradas)</span>
                            @elseif(request('tipo_movimiento') === 'SALIDA')
                                <span class="text-red-600 font-bold"><i class="fas fa-arrow-up mr-1"></i> SALIDA (Egresos)</span>
                            @else
                                <span class="text-slate-800">-- Todos (Ingresos y Salidas) --</span>
                            @endif
                        </span>
                        <i id="chevron_tipo" class="fas fa-chevron-down text-xs text-slate-400 transition-transform duration-200 shrink-0 ml-2"></i>
                    </button>

                    <div id="menu_tipo" class="hidden absolute left-0 right-0 z-50 mt-1 bg-white border border-slate-200 rounded-xl shadow-2xl overflow-hidden text-xs divide-y divide-slate-50">
                        <div class="p-2.5 hover:bg-slate-50 text-slate-700 cursor-pointer transition flex items-center justify-between {{ !request('tipo_movimiento') ? 'bg-slate-100 font-bold' : '' }}" onclick="selectTipoMovimiento('', '-- Todos (Ingresos y Salidas) --')">
                            <span>-- Todos (Ingresos y Salidas) --</span>
                            @if(!request('tipo_movimiento')) <i class="fas fa-check text-primary"></i> @endif
                        </div>
                        <div class="p-2.5 hover:bg-emerald-50 text-emerald-800 cursor-pointer transition flex items-center justify-between {{ request('tipo_movimiento') === 'INGRESO' ? 'bg-emerald-50/80 font-bold' : '' }}" onclick="selectTipoMovimiento('INGRESO', 'INGRESO (Entradas)')">
                            <span class="flex items-center gap-2"><i class="fas fa-arrow-down text-emerald-600 text-xs"></i> INGRESO (Entradas)</span>
                            @if(request('tipo_movimiento') === 'INGRESO') <i class="fas fa-check text-emerald-600"></i> @endif
                        </div>
                        <div class="p-2.5 hover:bg-red-50 text-red-700 cursor-pointer transition flex items-center justify-between {{ request('tipo_movimiento') === 'SALIDA' ? 'bg-red-50/80 font-bold' : '' }}" onclick="selectTipoMovimiento('SALIDA', 'SALIDA (Egresos)')">
                            <span class="flex items-center gap-2"><i class="fas fa-arrow-up text-red-600 text-xs"></i> SALIDA (Egresos)</span>
                            @if(request('tipo_movimiento') === 'SALIDA') <i class="fas fa-check text-red-600"></i> @endif
                        </div>
                    </div>
                </div>

                {{-- 4. Filtro Tipo de Documento (Custom Dropdown) --}}
                <div class="relative" id="wrapper_dropdown_doc">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                        <i class="fas fa-file-alt text-blue-600 mr-1"></i> Documento
                    </label>
                    <input type="hidden" name="documento" id="val_documento" value="{{ request('documento') }}">
                    
                    <button type="button" id="btn_toggle_doc" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-left bg-white text-slate-800 shadow-xs flex items-center justify-between focus:border-primary focus:ring-2 focus:ring-primary/20 transition cursor-pointer">
                        <span id="label_doc" class="truncate font-medium text-xs sm:text-sm">
                            {{ request('documento') ? request('documento') : '-- Todos los Documentos --' }}
                        </span>
                        <i id="chevron_doc" class="fas fa-chevron-down text-xs text-slate-400 transition-transform duration-200 shrink-0 ml-2"></i>
                    </button>

                    <div id="menu_doc" class="hidden absolute left-0 right-0 z-50 mt-1 bg-white border border-slate-200 rounded-xl shadow-2xl overflow-hidden">
                        <div class="p-2 border-b border-slate-100 bg-slate-50">
                            <div class="relative">
                                <input type="text" id="search_doc" placeholder="Buscar documento..." class="w-full pl-8 pr-3 py-1.5 text-xs border border-slate-300 rounded-lg focus:border-primary outline-none bg-white">
                                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            </div>
                        </div>
                        <div id="options_doc" class="max-h-56 overflow-y-auto divide-y divide-slate-50 text-xs">
                            <div class="p-2.5 hover:bg-slate-50 text-slate-700 cursor-pointer transition flex items-center justify-between option-doc {{ !request('documento') ? 'bg-slate-100 font-bold' : '' }}" onclick="selectDocumento('', '-- Todos los Documentos --')">
                                <span>-- Todos los Documentos --</span>
                                @if(!request('documento')) <i class="fas fa-check text-primary"></i> @endif
                            </div>
                            @foreach($tiposDocumento as $td)
                                <div class="p-2.5 hover:bg-blue-50 text-slate-700 cursor-pointer transition flex items-center justify-between option-doc {{ request('documento') == $td ? 'bg-blue-50/70 font-bold text-blue-900' : '' }}" data-search="{{ strtolower($td) }}" onclick="selectDocumento('{{ $td }}', '{{ $td }}')">
                                    <div class="flex items-center gap-2 truncate">
                                        <i class="fas fa-file-invoice text-slate-400 text-[11px]"></i>
                                        <span class="truncate">{{ $td }}</span>
                                    </div>
                                    @if(request('documento') == $td)
                                        <i class="fas fa-check text-blue-600 shrink-0 ml-2"></i>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-end justify-between gap-4 pt-3 border-t border-slate-100">
                <div class="flex flex-wrap items-center gap-3">
                    {{-- Fecha Desde --}}
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Desde:</label>
                        <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs text-slate-800 focus:border-primary outline-none shadow-xs">
                    </div>
                    {{-- Fecha Hasta --}}
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Hasta:</label>
                        <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs text-slate-800 focus:border-primary outline-none shadow-xs">
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary hover:bg-primary-dark text-white text-xs sm:text-sm font-bold rounded-lg shadow-sm transition cursor-pointer">
                        <i class="fas fa-filter"></i>
                        <span>Aplicar Filtros</span>
                    </button>
                    @if(request()->anyFilled(['codigo_almacen', 'codigo_producto', 'tipo_movimiento', 'documento', 'fecha_desde', 'fecha_hasta']))
                        <a href="{{ route('inventario.kardex_fisico') }}" class="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs sm:text-sm font-semibold rounded-lg transition" title="Limpiar todos los filtros">
                            <i class="fas fa-undo"></i>
                            <span>Limpiar</span>
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- Tarjetas KPI de Resumen Físico --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 mb-5">
        <div class="bg-white rounded-xl shadow-xs border border-emerald-200 p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg shrink-0">
                <i class="fas fa-arrow-down"></i>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] font-bold text-emerald-800 uppercase tracking-wider">Total Entradas</p>
                <p class="text-lg sm:text-xl font-black text-emerald-700 truncate">
                    + {{ number_format($resumen->total_entradas, 2) }}
                </p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-xs border border-red-200 p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-red-50 text-red-600 flex items-center justify-center text-lg shrink-0">
                <i class="fas fa-arrow-up"></i>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] font-bold text-red-800 uppercase tracking-wider">Total Salidas</p>
                <p class="text-lg sm:text-xl font-black text-red-700 truncate">
                    - {{ number_format($resumen->total_salidas, 2) }}
                </p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-xs border border-blue-200 p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-lg shrink-0">
                <i class="fas fa-boxes"></i>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] font-bold text-blue-800 uppercase tracking-wider">
                    {{ $productoSeleccionado ? 'Stock Actual' : 'Saldo Físico' }}
                </p>
                <p class="text-lg sm:text-xl font-black text-blue-700 truncate">
                    {{ number_format($resumen->saldo_final, 2) }}
                    @if($productoSeleccionado)
                        <span class="text-xs font-normal text-blue-600 ml-0.5">{{ $productoSeleccionado->codigo_unidad_medida }}</span>
                    @endif
                </p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-xs border border-slate-200 p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-slate-50 text-slate-600 flex items-center justify-center text-lg shrink-0">
                <i class="fas fa-list-ol"></i>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] font-bold text-slate-700 uppercase tracking-wider">Movimientos</p>
                <p class="text-lg sm:text-xl font-black text-slate-800 truncate">
                    {{ number_format($resumen->total_registros) }}
                </p>
            </div>
        </div>
    </div>

    {{-- Ficha Informativa cuando se selecciona un producto específico --}}
    @if($productoSeleccionado)
    <div class="bg-linear-to-r from-slate-800 to-slate-900 rounded-xl shadow-md p-4 text-white mb-5 border-l-4 border-emerald-500 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-lg bg-white/10 flex items-center justify-center text-emerald-400 text-xl shrink-0">
                <i class="fas fa-box-open"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-300 font-mono text-xs font-bold rounded border border-emerald-500/30">
                        {{ $productoSeleccionado->codigo }}
                    </span>
                    <span class="px-2 py-0.5 bg-white/10 text-slate-200 text-xs rounded">
                        Tipo: {{ $productoSeleccionado->codigo_tipo_producto ?? 'N/A' }}
                    </span>
                    <span class="px-2 py-0.5 bg-white/10 text-slate-200 text-xs rounded">
                        U.M.: {{ $productoSeleccionado->unidad_medida_desc ?? $productoSeleccionado->codigo_unidad_medida }}
                    </span>
                </div>
                <h3 class="text-sm sm:text-base font-bold text-white mt-1 uppercase tracking-wide">
                    {{ $productoSeleccionado->descripcion }}
                </h3>
            </div>
        </div>
        <div class="text-right">
            <p class="text-[11px] uppercase tracking-wider text-slate-300 font-semibold">
                {{ request('codigo_almacen') ? 'Stock en Almacén Filtrado' : 'Stock Total Consolidado' }}
            </p>
            <p class="text-xl sm:text-2xl font-black text-emerald-400">
                {{ number_format($stockActualEnAlmacen ?? 0, 2) }} <span class="text-xs font-bold text-white">{{ $productoSeleccionado->codigo_unidad_medida }}</span>
            </p>
        </div>
    </div>
    @endif

    <!-- Encabezado de la Sección de Movimientos -->
    <div class="flex flex-wrap items-center justify-between gap-3 mb-3 no-print">
        <div class="flex items-center gap-2">
            <h2 class="text-sm sm:text-base font-bold text-slate-800 flex items-center gap-2">
                <i class="fas fa-boxes-stacked text-primary"></i>
                <span>Movimientos Físicos (Agrupados por Almacén y Producto)</span>
            </h2>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                {{ $gruposPaginados->total() }} {{ Str::plural('ítem', $gruposPaginados->total()) }}
            </span>
        </div>
        <div class="text-xs text-slate-500">
            Control cronológico continuo con saldo por almacén
        </div>
    </div>

    <!-- Tabla Principal de Movimientos Físicos Continuos -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-300 overflow-hidden mb-5">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse min-w-[950px]">
                <thead>
                    <tr class="bg-slate-900 text-white text-[11px] uppercase tracking-wider font-bold">
                        <th class="py-3 px-3 text-center w-24">Fecha</th>
                        <th class="py-3 px-3 text-center w-24">Operación</th>
                        <th class="py-3 px-3 w-44">Doc. Referencia</th>
                        <th class="py-3 px-3 text-center w-28">Lote</th>
                        <th class="py-3 px-4">Concepto / Motivo / Observaciones</th>
                        <th class="py-3 px-3 text-right w-28 text-emerald-400">Entrada</th>
                        <th class="py-3 px-3 text-right w-28 text-red-400">Salida</th>
                        <th class="py-3 px-3 text-right w-32 text-blue-300 bg-slate-950/40">Saldo Físico</th>
                        <th class="py-3 px-3 text-center w-20">Usuario</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-sans">
                    @forelse($almacenesConProductos as $codAlmacen => $productosDelAlmacen)
                        @php
                            $primerItem = $productosDelAlmacen->first();
                            $almacenNombre = $primerItem->almacen_descripcion ?? $codAlmacen;
                        @endphp

                        <!-- ========================================== -->
                        <!-- SECCIÓN ALMACÉN -->
                        <!-- ========================================== -->
                        <tr class="bg-slate-800 text-white border-t-2 border-slate-900">
                            <td colspan="9" class="py-2.5 px-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2.5">
                                        <span class="px-2 py-0.5 rounded bg-emerald-500 text-slate-950 font-mono font-black text-xs">
                                            {{ $codAlmacen }}
                                        </span>
                                        <span class="font-black text-sm uppercase tracking-wide">
                                            {{ $almacenNombre }}
                                        </span>
                                    </div>
                                    <span class="text-xs text-slate-300 font-semibold">
                                        <i class="fas fa-warehouse mr-1 text-slate-400"></i> Almacén Físico
                                    </span>
                                </div>
                            </td>
                        </tr>

                        <!-- ========================================== -->
                        <!-- PRODUCTOS DENTRO DEL ALMACÉN -->
                        <!-- ========================================== -->
                        @foreach($productosDelAlmacen as $prod)
                            @php
                                $key = $prod->codigo_almacen . '__' . $prod->codigo_producto;
                                $movs = $movimientosPorGrupo->get($key, collect());
                                $stockActual = $stockActualPorGrupo->get($key, null);
                                $ultimoSaldo = $movs->last() ? $movs->last()->cantidad_saldo : 0;
                                $saldoMostrar = $stockActual ?? $ultimoSaldo;
                                $totalEntradas = $movs->sum('cantidad_entrada');
                                $totalSalidas = $movs->sum('cantidad_salida');
                            @endphp

                            <!-- FILA ENCABEZADO DE PRODUCTO -->
                            <tr class="bg-indigo-50/90 text-indigo-950 border-y border-indigo-200">
                                <td colspan="9" class="py-2 px-4 font-bold">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div class="flex items-center gap-2">
                                            <span class="text-indigo-600 font-black">PRODUCTO :</span>
                                            <span class="font-mono bg-white px-2 py-0.5 rounded border border-indigo-200 text-indigo-900 text-xs">
                                                {{ $prod->codigo_producto }}
                                            </span>
                                            <span class="uppercase font-black text-slate-900">
                                                {{ $prod->producto_descripcion }}
                                            </span>
                                            @if($prod->codigo_tipo_producto)
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold 
                                                    @if($prod->codigo_tipo_producto === 'PEP') bg-blue-100 text-blue-800
                                                    @elseif($prod->codigo_tipo_producto === 'MTP') bg-emerald-100 text-emerald-800
                                                    @elseif($prod->codigo_tipo_producto === 'INS') bg-amber-100 text-amber-800
                                                    @else bg-slate-200 text-slate-700
                                                    @endif">
                                                    {{ $prod->codigo_tipo_producto }}
                                                </span>
                                            @endif
                                            <span class="text-slate-500 font-normal text-xs">
                                                (U.M.: {{ $prod->unidad_medida_desc ?? $prod->codigo_unidad_medida }})
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-3 text-xs">
                                            <span class="text-emerald-700 font-bold">
                                                Entradas: +{{ number_format($totalEntradas, 2) }}
                                            </span>
                                            <span class="text-red-700 font-bold">
                                                Salidas: -{{ number_format($totalSalidas, 2) }}
                                            </span>
                                            <span class="px-2 py-0.5 rounded bg-blue-100 text-blue-900 font-black border border-blue-200">
                                                Saldo en este Almacén: {{ number_format($saldoMostrar, 2) }} {{ $prod->codigo_unidad_medida }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <!-- FILAS DE MOVIMIENTOS DEL PRODUCTO -->
                            @forelse($movs as $mov)
                                <tr class="hover:bg-slate-50/80 transition">
                                    {{-- Fecha --}}
                                    <td class="py-2 px-3 text-center font-medium text-slate-600 whitespace-nowrap">
                                        <span class="font-bold text-slate-800 block text-[11px]">
                                            {{ \Carbon\Carbon::parse($mov->fecha_movimiento)->format('d/m/Y') }}
                                        </span>
                                        <span class="text-[10px] text-slate-400">
                                            {{ \Carbon\Carbon::parse($mov->fecha_movimiento)->format('H:i') }}
                                        </span>
                                    </td>

                                    {{-- Operación --}}
                                    <td class="py-2 px-3 text-center whitespace-nowrap">
                                        @if($mov->tipo_movimiento === 'INGRESO')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                <i class="fas fa-arrow-down text-[8px]"></i> INGRESO
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700 border border-red-200">
                                                <i class="fas fa-arrow-up text-[8px]"></i> SALIDA
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Documento Referencia --}}
                                    <td class="py-2 px-3 whitespace-nowrap">
                                        <span class="font-bold text-slate-800 block text-xs">
                                            {{ $mov->documento ?? 'SIN DOCUMENTO' }}
                                        </span>
                                        @if($mov->numero_documento)
                                            <span class="font-mono text-[10px] text-slate-500 font-medium">
                                                #{{ $mov->numero_documento }}
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Lote --}}
                                    <td class="py-2 px-3 text-center whitespace-nowrap">
                                        @if($mov->lote)
                                            <span class="inline-flex px-1.5 py-0.5 rounded font-mono text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                                {{ $mov->lote }}
                                            </span>
                                        @else
                                            <span class="text-slate-300">-</span>
                                        @endif
                                    </td>

                                    {{-- Concepto / Motivo / Observaciones --}}
                                    <td class="py-2 px-4 text-slate-700 max-w-[280px] truncate" title="{{ $mov->observaciones }}">
                                        {{ $mov->observaciones ? $mov->observaciones : '-' }}
                                    </td>

                                    {{-- Entrada --}}
                                    <td class="py-2 px-3 text-right font-mono font-bold whitespace-nowrap">
                                        @if($mov->cantidad_entrada > 0)
                                            <span class="text-emerald-700">
                                                + {{ number_format($mov->cantidad_entrada, 2) }}
                                            </span>
                                        @else
                                            <span class="text-slate-300">-</span>
                                        @endif
                                    </td>

                                    {{-- Salida --}}
                                    <td class="py-2 px-3 text-right font-mono font-bold whitespace-nowrap">
                                        @if($mov->cantidad_salida > 0)
                                            <span class="text-red-600">
                                                - {{ number_format($mov->cantidad_salida, 2) }}
                                            </span>
                                        @else
                                            <span class="text-slate-300">-</span>
                                        @endif
                                    </td>

                                    {{-- Saldo Físico --}}
                                    <td class="py-2 px-3 text-right font-mono font-black text-slate-900 bg-blue-50/40 whitespace-nowrap">
                                        {{ number_format($mov->cantidad_saldo, 2) }}
                                    </td>

                                    {{-- Usuario --}}
                                    <td class="py-2 px-3 text-center text-slate-500 whitespace-nowrap text-[11px]">
                                        {{ $mov->nombre_usuario ?? 'Sistema' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="py-3 px-4 text-center text-slate-400 italic">
                                        Sin movimientos registrados en este producto para los filtros seleccionados.
                                    </td>
                                </tr>
                            @endforelse

                        @endforeach
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-12 px-4">
                                <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3 text-2xl">
                                    <i class="fas fa-clipboard-list"></i>
                                </div>
                                <h4 class="text-base font-bold text-slate-700">No se encontraron movimientos registrados</h4>
                                <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                                    Prueba modificando los filtros de almacén, producto o rango de fechas para visualizar los registros.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Paginación --}}
    @if($gruposPaginados->hasPages())
        <div class="mt-4 px-6 py-4 bg-white rounded-xl border border-slate-200/80 shadow-xs no-print">
            {{ $gruposPaginados->links() }}
        </div>
    @endif
</div>

{{-- Scripts para las Listas Desplegables Personalizadas --}}
<script>
    const productosData = @json($productosList);

    document.addEventListener('DOMContentLoaded', function () {
        setupCustomDropdownAlmacen();
        setupCustomDropdownProducto();
        setupCustomDropdownTipo();
        setupCustomDropdownDoc();
    });

    // 1. Almacén Dropdown
    function setupCustomDropdownAlmacen() {
        const btn = document.getElementById('btn_toggle_almacen');
        const menu = document.getElementById('menu_almacen');
        const search = document.getElementById('search_almacen');
        const chevron = document.getElementById('chevron_almacen');
        const options = document.querySelectorAll('.option-alm');

        if (!btn || !menu) return;

        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            closeAllDropdowns(menu);
            const isOpen = !menu.classList.contains('hidden');
            if (isOpen) {
                menu.classList.add('hidden');
                chevron.classList.remove('rotate-180');
            } else {
                menu.classList.remove('hidden');
                chevron.classList.add('rotate-180');
                if (search) {
                    search.value = '';
                    options.forEach(opt => opt.classList.remove('hidden'));
                    setTimeout(() => search.focus(), 50);
                }
            }
        });

        if (search) {
            search.addEventListener('input', function () {
                const q = this.value.toLowerCase().trim();
                options.forEach(opt => {
                    const text = opt.getAttribute('data-search') || opt.innerText.toLowerCase();
                    if (text.includes(q)) {
                        opt.classList.remove('hidden');
                    } else {
                        opt.classList.add('hidden');
                    }
                });
            });
        }
    }

    function selectAlmacen(codigo, label) {
        document.getElementById('val_almacen').value = codigo;
        document.getElementById('label_almacen').innerText = label;
        document.getElementById('menu_almacen').classList.add('hidden');
        document.getElementById('chevron_almacen').classList.remove('rotate-180');
    }

    // 2. Producto Predictivo Searchable Dropdown
    function setupCustomDropdownProducto() {
        const input = document.getElementById('search_producto_input');
        const hidden = document.getElementById('val_producto');
        const menu = document.getElementById('menu_producto');
        const optionsContainer = document.getElementById('options_producto');
        const emptyState = document.getElementById('empty_producto');
        const btnClear = document.getElementById('btn_clear_producto');
        const iconSearch = document.getElementById('icon_search_producto');

        if (!input || !menu) return;

        function renderProducts(query = '') {
            const q = query.toLowerCase().trim();
            const filtered = productosData.filter(p => {
                const cod = (p.codigo || '').toLowerCase();
                const desc = (p.descripcion || '').toLowerCase();
                return cod.includes(q) || desc.includes(q);
            }).slice(0, 50); // Límite de 50 para máxima velocidad

            optionsContainer.innerHTML = '';
            if (filtered.length === 0) {
                emptyState.classList.remove('hidden');
            } else {
                emptyState.classList.add('hidden');
                filtered.forEach(p => {
                    const row = document.createElement('div');
                    row.className = 'p-2.5 hover:bg-emerald-50 text-slate-700 cursor-pointer transition flex items-center justify-between border-b border-slate-50';
                    row.innerHTML = `
                        <div class="flex items-center gap-2 truncate pr-2">
                            <span class="px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 font-mono font-bold text-[10px] shrink-0 border border-indigo-100">${escapeHtml(p.codigo)}</span>
                            <span class="truncate font-semibold text-slate-800 text-xs">${escapeHtml(p.descripcion)}</span>
                        </div>
                        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-500 font-mono text-[10px] shrink-0 font-bold">${escapeHtml(p.codigo_unidad_medida || '')}</span>
                    `;
                    row.onclick = () => {
                        input.value = `${p.codigo} - ${p.descripcion}`;
                        hidden.value = p.codigo;
                        menu.classList.add('hidden');
                        if (btnClear) btnClear.classList.remove('hidden');
                        if (iconSearch) iconSearch.classList.add('hidden');
                    };
                    optionsContainer.appendChild(row);
                });
            }
        }

        input.addEventListener('focus', function () {
            closeAllDropdowns(menu);
            renderProducts(this.value);
            menu.classList.remove('hidden');
        });

        input.addEventListener('input', function () {
            hidden.value = this.value;
            if (this.value) {
                if (btnClear) btnClear.classList.remove('hidden');
                if (iconSearch) iconSearch.classList.add('hidden');
            } else {
                if (btnClear) btnClear.classList.add('hidden');
                if (iconSearch) iconSearch.classList.remove('hidden');
            }
            renderProducts(this.value);
            menu.classList.remove('hidden');
        });
    }

    function clearProducto() {
        const input = document.getElementById('search_producto_input');
        const hidden = document.getElementById('val_producto');
        const btnClear = document.getElementById('btn_clear_producto');
        const iconSearch = document.getElementById('icon_search_producto');
        const menu = document.getElementById('menu_producto');

        if (input) input.value = '';
        if (hidden) hidden.value = '';
        if (btnClear) btnClear.classList.add('hidden');
        if (iconSearch) iconSearch.classList.remove('hidden');
        if (menu) menu.classList.add('hidden');
    }

    // 3. Tipo Movimiento Dropdown
    function setupCustomDropdownTipo() {
        const btn = document.getElementById('btn_toggle_tipo');
        const menu = document.getElementById('menu_tipo');
        const chevron = document.getElementById('chevron_tipo');

        if (!btn || !menu) return;

        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            closeAllDropdowns(menu);
            const isOpen = !menu.classList.contains('hidden');
            if (isOpen) {
                menu.classList.add('hidden');
                chevron.classList.remove('rotate-180');
            } else {
                menu.classList.remove('hidden');
                chevron.classList.add('rotate-180');
            }
        });
    }

    function selectTipoMovimiento(val, label) {
        document.getElementById('val_tipo_movimiento').value = val;
        const elLabel = document.getElementById('label_tipo');
        if (val === 'INGRESO') {
            elLabel.innerHTML = '<span class="text-emerald-700 font-bold"><i class="fas fa-arrow-down mr-1"></i> INGRESO (Entradas)</span>';
        } else if (val === 'SALIDA') {
            elLabel.innerHTML = '<span class="text-red-600 font-bold"><i class="fas fa-arrow-up mr-1"></i> SALIDA (Egresos)</span>';
        } else {
            elLabel.innerHTML = '<span class="text-slate-800">-- Todos (Ingresos y Salidas) --</span>';
        }
        document.getElementById('menu_tipo').classList.add('hidden');
        document.getElementById('chevron_tipo').classList.remove('rotate-180');
    }

    // 4. Documento Dropdown
    function setupCustomDropdownDoc() {
        const btn = document.getElementById('btn_toggle_doc');
        const menu = document.getElementById('menu_doc');
        const search = document.getElementById('search_doc');
        const chevron = document.getElementById('chevron_doc');
        const options = document.querySelectorAll('.option-doc');

        if (!btn || !menu) return;

        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            closeAllDropdowns(menu);
            const isOpen = !menu.classList.contains('hidden');
            if (isOpen) {
                menu.classList.add('hidden');
                chevron.classList.remove('rotate-180');
            } else {
                menu.classList.remove('hidden');
                chevron.classList.add('rotate-180');
                if (search) {
                    search.value = '';
                    options.forEach(opt => opt.classList.remove('hidden'));
                    setTimeout(() => search.focus(), 50);
                }
            }
        });

        if (search) {
            search.addEventListener('input', function () {
                const q = this.value.toLowerCase().trim();
                options.forEach(opt => {
                    const text = opt.getAttribute('data-search') || opt.innerText.toLowerCase();
                    if (text.includes(q)) {
                        opt.classList.remove('hidden');
                    } else {
                        opt.classList.add('hidden');
                    }
                });
            });
        }
    }

    function selectDocumento(val, label) {
        document.getElementById('val_documento').value = val;
        document.getElementById('label_doc').innerText = label;
        document.getElementById('menu_doc').classList.add('hidden');
        document.getElementById('chevron_doc').classList.remove('rotate-180');
    }

    // Utilidades Globales
    function closeAllDropdowns(exceptElement = null) {
        const menus = [
            { el: document.getElementById('menu_almacen'), ch: document.getElementById('chevron_almacen') },
            { el: document.getElementById('menu_producto'), ch: null },
            { el: document.getElementById('menu_tipo'), ch: document.getElementById('chevron_tipo') },
            { el: document.getElementById('menu_doc'), ch: document.getElementById('chevron_doc') }
        ];

        menus.forEach(m => {
            if (m.el && m.el !== exceptElement) {
                m.el.classList.add('hidden');
                if (m.ch) m.ch.classList.remove('rotate-180');
            }
        });
    }

    document.addEventListener('click', function (e) {
        const wrappers = [
            document.getElementById('wrapper_dropdown_almacen'),
            document.getElementById('wrapper_dropdown_producto'),
            document.getElementById('wrapper_dropdown_tipo'),
            document.getElementById('wrapper_dropdown_doc')
        ];
        
        const clickedInside = wrappers.some(w => w && w.contains(e.target));
        if (!clickedInside) {
            closeAllDropdowns();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeAllDropdowns();
        }
    });

    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    }
</script>
@endsection
