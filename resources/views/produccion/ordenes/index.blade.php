@extends('layouts.app')

@section('title', 'Gestión de Órdenes de Producción')

@section('content')
<div class="w-full max-w-[1750px] mx-auto pb-6">
    {{-- Barra de Pestañas Multi-OP --}}
    <div class="mb-5 bg-white p-2 rounded-2xl shadow-xs border border-slate-200 flex items-center gap-2 overflow-x-auto scrollbar-none">
        <button type="button" id="tab_btn_lista" onclick="cambiarPestana('lista')" class="tab-btn inline-flex items-center gap-2 px-3.5 py-2 text-xs font-bold rounded-xl transition-all shadow-xs bg-primary text-white border border-primary shrink-0 cursor-pointer">
            <i class="fas fa-list-check"></i>
            <span>Órdenes de Planta</span>
        </button>
        <div class="h-6 w-px bg-slate-200 mx-1 shrink-0"></div>
        <div id="contenedor_pestanas_ops" class="flex items-center gap-2 overflow-x-auto scrollbar-none">
            {{-- Pestañas de OPs abiertas dinámicamente --}}
        </div>
    </div>

    {{-- Contenido de Pestaña Principal: Lista de Órdenes --}}
    <div id="contenido_tab_lista" class="tab-pane-ordenes block">
        <div class="flex justify-between items-center mb-6 gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-slate-800">Órdenes de Producción</h1>
                <p class="text-xs sm:text-sm text-slate-600">Administración y seguimiento de órdenes de planta</p>
            </div>
            @if(Auth::user()->hasAccess('produccion.ordenes.create'))
            <a href="{{ route('produccion.ordenes.create') }}" class="shrink-0 flex items-center justify-center bg-primary hover:bg-primary-dark text-white font-semibold py-2 px-3 sm:px-4 rounded-lg shadow transition">
                <i class="fas fa-plus"></i>
                <span class="hidden sm:inline ml-2">Nueva Orden</span>
            </a>
            @endif
        </div>

    {{-- Pestañas de Estado --}}
    <div class="flex flex-wrap items-center gap-2 mb-4">
        <a href="{{ route('produccion.ordenes.index', array_merge(request()->except(['page', 'estado']), ['estado' => 'activas'])) }}" 
           class="inline-flex items-center gap-2 px-3.5 py-2 text-xs sm:text-sm font-bold rounded-xl transition-all shadow-xs {{ $estado === 'activas' ? 'bg-emerald-600 text-white shadow-emerald-600/20' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
            <i class="fas fa-bolt {{ $estado === 'activas' ? 'text-amber-300' : 'text-amber-500' }}"></i>
            <span>Activas</span>
            <span class="inline-flex items-center justify-center px-2 py-0.5 text-[11px] font-extrabold rounded-full {{ $estado === 'activas' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                {{ $counts['activas'] ?? 0 }}
            </span>
        </a>

        <a href="{{ route('produccion.ordenes.index', array_merge(request()->except(['page', 'estado']), ['estado' => 'EN_PROCESO'])) }}" 
           class="inline-flex items-center gap-2 px-3.5 py-2 text-xs sm:text-sm font-bold rounded-xl transition-all shadow-xs {{ $estado === 'EN_PROCESO' ? 'bg-blue-600 text-white shadow-blue-600/20' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
            <i class="fas fa-spinner fa-spin-pulse {{ $estado === 'EN_PROCESO' ? 'text-blue-200' : 'text-blue-500' }}"></i>
            <span>En Proceso</span>
            <span class="inline-flex items-center justify-center px-2 py-0.5 text-[11px] font-extrabold rounded-full {{ $estado === 'EN_PROCESO' ? 'bg-white/20 text-white' : 'bg-blue-50 text-blue-700 border border-blue-200' }}">
                {{ $counts['EN_PROCESO'] ?? 0 }}
            </span>
        </a>

        <a href="{{ route('produccion.ordenes.index', array_merge(request()->except(['page', 'estado']), ['estado' => 'PENDIENTE'])) }}" 
           class="inline-flex items-center gap-2 px-3.5 py-2 text-xs sm:text-sm font-bold rounded-xl transition-all shadow-xs {{ $estado === 'PENDIENTE' ? 'bg-amber-500 text-white shadow-amber-500/20' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
            <i class="fas fa-clock {{ $estado === 'PENDIENTE' ? 'text-amber-100' : 'text-amber-500' }}"></i>
            <span>Pendientes</span>
            <span class="inline-flex items-center justify-center px-2 py-0.5 text-[11px] font-extrabold rounded-full {{ $estado === 'PENDIENTE' ? 'bg-white/20 text-white' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                {{ $counts['PENDIENTE'] ?? 0 }}
            </span>
        </a>

        <a href="{{ route('produccion.ordenes.index', array_merge(request()->except(['page', 'estado']), ['estado' => 'COMPLETADO'])) }}" 
           class="inline-flex items-center gap-2 px-3.5 py-2 text-xs sm:text-sm font-bold rounded-xl transition-all shadow-xs {{ $estado === 'COMPLETADO' ? 'bg-slate-700 text-white shadow-slate-700/20' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
            <i class="fas fa-check-circle {{ $estado === 'COMPLETADO' ? 'text-emerald-300' : 'text-emerald-500' }}"></i>
            <span>Completadas</span>
            <span class="inline-flex items-center justify-center px-2 py-0.5 text-[11px] font-extrabold rounded-full {{ $estado === 'COMPLETADO' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                {{ $counts['COMPLETADO'] ?? 0 }}
            </span>
        </a>

        <a href="{{ route('produccion.ordenes.index', array_merge(request()->except(['page', 'estado']), ['estado' => 'todas'])) }}" 
           class="inline-flex items-center gap-2 px-3.5 py-2 text-xs sm:text-sm font-bold rounded-xl transition-all shadow-xs {{ $estado === 'todas' ? 'bg-slate-900 text-white shadow-slate-900/20' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
            <i class="fas fa-list-ul text-slate-400"></i>
            <span>Todas</span>
            <span class="inline-flex items-center justify-center px-2 py-0.5 text-[11px] font-extrabold rounded-full {{ $estado === 'todas' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                {{ $counts['todas'] ?? 0 }}
            </span>
        </a>
    </div>

    {{-- Filtros Secundarios --}}
    <div class="bg-white p-3 md:p-4 rounded-xl shadow-sm border border-slate-200 mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <form method="GET" action="{{ route('produccion.ordenes.index') }}" class="w-full flex flex-col md:flex-row gap-4">
            <input type="hidden" name="estado" value="{{ $estado }}">

            <div class="flex items-center gap-2">
                <div class="flex flex-col">
                    <label class="text-[10px] uppercase text-slate-500 font-bold mb-1">Fecha Desde</label>
                    <input type="date" name="fecha_desde" value="{{ $fecha_desde }}" class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary outline-none" max="{{ date('Y-m-d') }}">
                </div>
                <div class="flex flex-col">
                    <label class="text-[10px] uppercase text-slate-500 font-bold mb-1">Fecha Hasta</label>
                    <input type="date" name="fecha_hasta" value="{{ $fecha_hasta }}" class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary outline-none" max="{{ date('Y-m-d') }}">
                </div>
            </div>

            <div class="flex-1 flex flex-col">
                <label class="text-[10px] uppercase text-slate-500 font-bold mb-1">Búsqueda</label>
                <div class="relative w-full">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-slate-400"></i>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por OP o producto..." 
                        class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary text-sm outline-none">
                </div>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-5 py-2 rounded-lg text-sm font-bold transition-colors shadow-xs">
                    <i class="fas fa-filter mr-1"></i> Filtrar
                </button>
                <a href="{{ route('produccion.ordenes.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-lg text-sm font-bold transition-colors border border-slate-300">
                    Limpiar
                </a>
            </div>
        </form>
    </div>

    {{-- Tabla de Órdenes --}}
    <div id="table-container" class="transition-opacity duration-300">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs sm:text-sm">
                    <thead>
                        <tr class="bg-slate-900 text-slate-200 text-xs uppercase tracking-wider font-bold">
                            <th class="p-4 border-r border-slate-800 text-center">OP</th>
                            <th class="p-4 border-r border-slate-800 text-center">Producto</th>
                            <th class="p-4 border-r border-slate-800 text-center">Descripción</th>
                            <th class="p-4 border-r border-slate-800 text-center">Fecha</th>
                            <th class="p-4 border-r border-slate-800 text-center">Estado</th>
                            <th class="p-4 border-r border-slate-800 last:border-r-0 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        @forelse($ordenes as $o)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="p-4 border-r border-slate-200 text-center font-bold text-slate-900 whitespace-nowrap">
                                    {{ $o->codigo_op }}
                                </td>
                                <td class="p-4 border-r border-slate-200 font-semibold text-slate-800">
                                    {{ $o->descripcion_producto_proceso ?? 'N/A' }}
                                </td>
                                <td class="p-4 border-r border-slate-200 text-sm text-slate-600">
                                    {{ $o->texto_obs ?? '-' }}
                                </td>
                                <td class="p-4 border-r border-slate-200 text-center text-slate-600 whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($o->fecha)->format('d/m/Y') }}
                                </td>
                                <td class="p-4 border-r border-slate-200 text-center whitespace-nowrap">
                                    @if($o->estado == 'COMPLETADO')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            <i class="fas fa-check-circle mr-1 text-[10px]"></i> COMPLETADO
                                        </span>
                                    @elseif($o->estado == 'EN_PROCESO')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                            <i class="fas fa-spinner fa-spin-pulse mr-1 text-[10px]"></i> EN PROCESO
                                        </span>
                                    @elseif($o->estado == 'CANCELADO')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200">
                                            <i class="fas fa-ban mr-1 text-[10px]"></i> ANULADO
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                            <i class="fas fa-clock mr-1 text-[10px]"></i> {{ str_replace('_', ' ', $o->estado ?? 'PENDIENTE') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="p-3 border-r border-slate-200 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center justify-center gap-2">
                                        @if(Auth::user()->hasAccess('ordenes.procesos.index') || Auth::user()->hasAccess('ordenes.procesos.ejecutar'))
                                        <button type="button" 
                                                onclick="abrirOpPestana('{{ $o->idop }}', '{{ $o->codigo_op }}', '{{ addslashes($o->descripcion_producto_proceso ?? 'Sin Producto') }}')" 
                                                class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-lg text-white bg-primary hover:bg-primary-dark shadow-xs transition-all min-w-[95px] cursor-pointer" 
                                                title="Abrir Procesos en Pestaña">
                                             <i class="fas fa-cogs"></i>
                                             <span>Procesos</span>
                                        </button>
                                        @endif
                                        @if(Auth::user()->hasAccess('mermas.index'))
                                        <button type="button" onclick="abrirModalReporte('{{ $o->idop }}', '{{ $o->codigo_op }}')" class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-lg text-white bg-red-600 hover:bg-red-700 shadow-xs transition-all min-w-[90px] cursor-pointer" title="Vista Previa e Impresión del Reporte">
                                            <i class="fas fa-file-pdf"></i>
                                            <span>Reporte</span>
                                        </button>
                                        @endif
                                        <div class="w-8 h-8 inline-flex items-center justify-center shrink-0">
                                            @if($o->estado == 'PENDIENTE' && Auth::user()->hasAccess('produccion.ordenes.finalizar'))
                                            <form action="{{ route('produccion.ordenes.destroy', $o->idop) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Está seguro de que desea eliminar/anular esta Orden de Producción?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="w-8 h-8 inline-flex items-center justify-center text-slate-600 bg-slate-100 hover:bg-red-600 hover:text-white rounded-lg transition-all border border-slate-200 shadow-2xs" title="Eliminar / Anular Orden">
                                                    <i class="fas fa-trash-alt text-xs"></i>
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-500">
                                    <i class="fas fa-folder-open text-4xl text-slate-300 mb-3 block"></i>
                                    No se encontraron órdenes de producción en la pestaña seleccionada.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($ordenes->hasPages())
                <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                    {{ $ordenes->links() }}
                </div>
            @endif
        </div>
    </div>
    </div>{{-- Fin #contenido_tab_lista --}}

    {{-- Contenedor de Pestañas Dinámicas de OPs (Iframes aislados) --}}
    <div id="contenedor_contenidos_ops" class="tab-panes-ops hidden"></div>
</div>

<!-- Modal Vista Previa de Reporte -->
<div id="modal_reporte_preview" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs hidden p-2 sm:p-4 transition-all duration-300" onclick="if(event.target === this) cerrarModalReporte()">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-7xl max-h-[96vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        <!-- Encabezado Modal -->
        <div class="px-6 py-3.5 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-red-100 text-red-600 flex items-center justify-center shrink-0 shadow-xs">
                    <i class="fas fa-file-invoice text-lg"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                        <span>Reporte de Control de Producción</span>
                        <span id="modal_reporte_op_label" class="text-xs font-mono font-bold bg-slate-200 text-slate-700 px-2 py-0.5 rounded border border-slate-300">OP-XXXX</span>
                    </h3>
                    <p class="text-xs text-slate-500">Vista previa interactiva del documento oficial de control de planta</p>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <!-- Botón Imprimir -->
                <button type="button" onclick="imprimirReportePreview()" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-lg shadow-xs transition cursor-pointer" title="Imprimir directamente">
                    <i class="fas fa-print"></i>
                    <span class="hidden sm:inline">Imprimir</span>
                </button>
                
                <!-- Botón Descargar PDF -->
                <a id="btn_descargar_pdf_modal" href="#" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-lg shadow-xs transition cursor-pointer" title="Descargar archivo PDF">
                    <i class="fas fa-download"></i>
                    <span class="hidden sm:inline">Descargar PDF</span>
                </a>

                <!-- Botón Abrir en Nueva Pestaña -->
                <a id="btn_nueva_pestana_modal" href="#" target="_blank" class="inline-flex items-center justify-center w-9 h-9 bg-white hover:bg-slate-100 text-slate-600 text-xs font-semibold rounded-lg border border-slate-300 transition shadow-2xs" title="Abrir en pestaña nueva">
                    <i class="fas fa-external-link-alt"></i>
                </a>

                <div class="h-6 w-px bg-slate-300 mx-1"></div>

                <!-- Botón Cerrar -->
                <button type="button" onclick="cerrarModalReporte()" class="w-9 h-9 inline-flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-200/80 transition cursor-pointer" title="Cerrar ventana">
                    <i class="fas fa-times text-base"></i>
                </button>
            </div>
        </div>

        <!-- Cuerpo del Modal: iFrame de visualización a pantalla completa -->
        <div class="relative w-full flex-1 bg-slate-100 overflow-hidden" style="height: 80vh; min-height: 580px;">
            <!-- Loader -->
            <div id="modal_reporte_loader" class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-white/95 backdrop-blur-2xs gap-3 transition-opacity duration-300">
                <div class="w-10 h-10 border-4 border-red-200 border-t-red-600 rounded-full animate-spin"></div>
                <div class="text-center">
                    <p class="text-sm font-bold text-slate-700">Generando vista previa del reporte...</p>
                    <p class="text-xs text-slate-400">Procesando registros de producción y mermas</p>
                </div>
            </div>

            <!-- Frame PDF con posicionamiento absoluto para ocupar 100% real -->
            <iframe id="iframe_reporte_preview" src="" class="absolute inset-0 w-full h-full border-0 bg-white" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none;"></iframe>
        </div>

        <!-- Pie del Modal -->
        <div class="px-6 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs text-slate-500">
            <span class="flex items-center gap-1.5 text-slate-500">
                <i class="fas fa-info-circle text-slate-400"></i>
                Utilice el visor interactivo para navegar páginas, hacer zoom, imprimir o descargar.
            </span>
            <button type="button" onclick="cerrarModalReporte()" class="px-4 py-1.5 bg-white hover:bg-slate-100 text-slate-700 font-semibold rounded-lg border border-slate-300 shadow-2xs transition cursor-pointer">
                Cerrar
            </button>
        </div>
    </div>
</div>

<script>
    // =========================================================================
    // Multi-Pestañas Workbench de Órdenes de Producción
    // =========================================================================
    const urlProcesosTemplate = "{{ url('/produccion/ordenes') }}/:idop/procesos?embedded=1";
    const openTabs = new Set();
    let activeTabId = 'lista';

    function abrirOpPestana(idop, codigoOp, productoDesc) {
        if (!openTabs.has(idop)) {
            openTabs.add(idop);
            crearBotonPestana(idop, codigoOp, productoDesc);
            crearContenidoPestana(idop, codigoOp);
        }
        cambiarPestana(idop);
    }

    function crearBotonPestana(idop, codigoOp, productoDesc) {
        const container = document.getElementById('contenedor_pestanas_ops');
        if (!container) return;

        const tabBtn = document.createElement('div');
        tabBtn.id = `tab_btn_op_${idop}`;
        tabBtn.className = 'tab-btn inline-flex items-center gap-2 px-3.5 py-2 text-xs font-bold rounded-xl transition-all cursor-pointer bg-white text-slate-600 hover:bg-slate-50 border border-slate-200 shadow-xs shrink-0 select-none';
        tabBtn.onclick = function() { cambiarPestana(idop); };

        const prodShort = productoDesc && productoDesc !== 'Sin Producto' && productoDesc !== 'N/A' 
            ? ` (${productoDesc.substring(0, 18)}${productoDesc.length > 18 ? '...' : ''})` 
            : '';

        tabBtn.innerHTML = `
            <i class="fas fa-industry text-emerald-600 text-[11px]"></i>
            <span class="font-mono tracking-tight">${codigoOp}</span>
            <span class="text-[10px] text-slate-400 font-normal hidden lg:inline max-w-[120px] truncate">${prodShort}</span>
            <button type="button" onclick="event.stopPropagation(); cerrarPestanaOp('${idop}')" class="ml-1 w-4 h-4 rounded-full flex items-center justify-center hover:bg-red-100 text-slate-400 hover:text-red-600 transition" title="Cerrar pestaña">
                <i class="fas fa-times text-[10px]"></i>
            </button>
        `;
        container.appendChild(tabBtn);
    }

    function crearContenidoPestana(idop, codigoOp) {
        const container = document.getElementById('contenedor_contenidos_ops');
        if (!container) return;

        const pane = document.createElement('div');
        pane.id = `tab_pane_op_${idop}`;
        pane.className = 'tab-pane-op hidden w-full rounded-2xl overflow-hidden bg-white shadow-sm border border-slate-200 relative';
        pane.style.height = 'calc(100vh - 195px)';
        pane.style.minHeight = '700px';

        const url = urlProcesosTemplate.replace(':idop', idop);

        pane.innerHTML = `
            <div id="loader_tab_op_${idop}" class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-white/95 backdrop-blur-2xs gap-3">
                <div class="w-10 h-10 border-4 border-emerald-200 border-t-emerald-600 rounded-full animate-spin"></div>
                <div class="text-center">
                    <p class="text-sm font-bold text-slate-700">Cargando procesos de ${codigoOp}...</p>
                    <p class="text-xs text-slate-400">Espere un momento por favor</p>
                </div>
            </div>
            <iframe id="iframe_op_${idop}" src="${url}" class="w-full h-full border-0 block" style="width: 100%; height: 100%; border: none;" onload="const l=document.getElementById('loader_tab_op_${idop}'); if(l) l.classList.add('hidden');"></iframe>
        `;
        container.appendChild(pane);
    }

    function cambiarPestana(targetId) {
        activeTabId = targetId;

        // Estilos para pestaña Lista de Órdenes
        const btnLista = document.getElementById('tab_btn_lista');
        if (btnLista) {
            if (targetId === 'lista') {
                btnLista.className = 'tab-btn inline-flex items-center gap-2 px-3.5 py-2 text-xs font-bold rounded-xl transition-all shadow-xs bg-emerald-600 text-white border border-emerald-600 shrink-0 cursor-pointer';
            } else {
                btnLista.className = 'tab-btn inline-flex items-center gap-2 px-3.5 py-2 text-xs font-bold rounded-xl transition-all shadow-xs bg-white text-slate-600 hover:bg-slate-50 border border-slate-200 shrink-0 cursor-pointer';
            }
        }

        // Estilos para pestañas de OPs
        openTabs.forEach(idop => {
            const btn = document.getElementById(`tab_btn_op_${idop}`);
            if (btn) {
                if (targetId === idop) {
                    btn.className = 'tab-btn inline-flex items-center gap-2 px-3.5 py-2 text-xs font-bold rounded-xl transition-all shadow-xs bg-slate-900 text-white border border-slate-900 shrink-0 cursor-pointer select-none';
                } else {
                    btn.className = 'tab-btn inline-flex items-center gap-2 px-3.5 py-2 text-xs font-bold rounded-xl transition-all shadow-xs bg-white text-slate-600 hover:bg-slate-50 border border-slate-200 shrink-0 cursor-pointer select-none';
                }
            }
        });

        // Alternar vistas
        const paneLista = document.getElementById('contenido_tab_lista');
        const paneOpsContainer = document.getElementById('contenedor_contenidos_ops');

        if (targetId === 'lista') {
            if (paneLista) paneLista.classList.remove('hidden');
            if (paneOpsContainer) paneOpsContainer.classList.add('hidden');
        } else {
            if (paneLista) paneLista.classList.add('hidden');
            if (paneOpsContainer) paneOpsContainer.classList.remove('hidden');

            openTabs.forEach(idop => {
                const opPane = document.getElementById(`tab_pane_op_${idop}`);
                if (opPane) {
                    if (idop === targetId) {
                        opPane.classList.remove('hidden');
                    } else {
                        opPane.classList.add('hidden');
                    }
                }
            });
        }
    }

    function cerrarPestanaOp(idop) {
        openTabs.delete(idop);

        const btn = document.getElementById(`tab_btn_op_${idop}`);
        if (btn) btn.remove();

        const pane = document.getElementById(`tab_pane_op_${idop}`);
        if (pane) pane.remove();

        if (activeTabId === idop) {
            const remaining = Array.from(openTabs);
            if (remaining.length > 0) {
                cambiarPestana(remaining[remaining.length - 1]);
            } else {
                cambiarPestana('lista');
            }
        }
    }

    // Exponer globalmente para comunicación desde iframes o botones
    window.abrirOpPestana = abrirOpPestana;
    window.cambiarPestana = cambiarPestana;
    window.cerrarPestanaOp = cerrarPestanaOp;

    // =========================================================================
    // Modal de Vista Previa de Reportes
    // =========================================================================
    const routeReporteTemplate = "{{ route('mermas.reporte_pdf', ':idop') }}";

    function abrirModalReporte(idop, codigoOp) {
        const modal = document.getElementById('modal_reporte_preview');
        const loader = document.getElementById('modal_reporte_loader');
        const iframe = document.getElementById('iframe_reporte_preview');
        const opLabel = document.getElementById('modal_reporte_op_label');
        const btnDownload = document.getElementById('btn_descargar_pdf_modal');
        const btnNewTab = document.getElementById('btn_nueva_pestana_modal');

        if (!modal || !iframe) return;

        const streamUrl = routeReporteTemplate.replace(':idop', idop);
        const downloadUrl = streamUrl + (streamUrl.includes('?') ? '&' : '?') + 'download=1';

        opLabel.innerText = codigoOp || `OP #${idop}`;
        btnDownload.href = downloadUrl;
        btnNewTab.href = streamUrl;

        loader.classList.remove('hidden');
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');

        iframe.onload = function() {
            if (loader && iframe.src && iframe.src !== 'about:blank') {
                loader.classList.add('hidden');
            }
        };

        // Forzar zoom horizontal y barra de herramientas nativa
        iframe.src = streamUrl + '#toolbar=1&navpanes=0&view=FitH';
    }

    function cerrarModalReporte() {
        const modal = document.getElementById('modal_reporte_preview');
        const iframe = document.getElementById('iframe_reporte_preview');
        if (modal) {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            if (iframe) iframe.src = '';
        }
    }

    function imprimirReportePreview() {
        const iframe = document.getElementById('iframe_reporte_preview');
        if (!iframe || !iframe.src) return;

        try {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
        } catch (e) {
            console.warn("Impresión directa por iframe interceptada, abriendo ventana:", e);
            const printWin = window.open(iframe.src, '_blank');
            if (printWin) {
                printWin.onload = function() {
                    printWin.focus();
                    printWin.print();
                };
            }
        }
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            cerrarModalReporte();
        }
    });
</script>
@endsection
