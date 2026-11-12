@extends('layouts.app')
@section('title', 'Kardex Valorizado')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-7xl">

    <x-page-header 
        title="Kardex Valorizado" 
        subtitle="Historial cronológico valorizado de entradas, salidas y saldos."
    >
        <x-slot name="actions">
            <a href="{{ route('inventario.kardex.exportar.excel', request()->query()) }}" id="btn-export-excel" data-no-loader="true" class="btn-primary bg-emerald-600 hover:bg-emerald-700 flex items-center gap-2 no-print">
                <i class="fas fa-file-excel"></i> Exportar Excel
            </a>
            <a href="{{ route('inventario.kardex.exportar.pdf', request()->query()) }}" id="btn-export-pdf" data-no-loader="true" class="btn-primary bg-red-600 hover:bg-red-700 flex items-center gap-2 no-print">
                <i class="fas fa-file-pdf"></i> Exportar PDF
            </a>
        </x-slot>
    </x-page-header>

    <x-filter-bar action="{{ route('inventario.kardex') }}">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 w-full">
            <div>
                <label class="block text-[10px] uppercase font-bold text-slate-500 mb-1">Tipo Documento</label>
                <select name="documento" id="inputDocumento" class="input-field appearance-none py-2">
                    <option value="">Todos</option>
                    @foreach($tiposDocumento as $td)
                        <option value="{{ $td }}" {{ request('documento') == $td ? 'selected' : '' }}>{{ $td }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[10px] uppercase font-bold text-slate-500 mb-1">Producto</label>
                <input type="text" name="codigo_producto" id="inputProducto" value="{{ request('codigo_producto') }}" placeholder="Código o descripción..." class="input-field py-2">
            </div>
            <div>
                <label class="block text-[10px] uppercase font-bold text-slate-500 mb-1">Almacén</label>
                <select name="codigo_almacen" id="inputAlmacen" class="input-field appearance-none py-2">
                    <option value="">Todos</option>
                    @foreach($almacenes as $alm)
                        <option value="{{ $alm->codigo_almacen }}" {{ request('codigo_almacen') == $alm->codigo_almacen ? 'selected' : '' }}>{{ $alm->descripcion }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[10px] uppercase font-bold text-slate-500 mb-1">Fecha Desde</label>
                <input type="date" name="fecha_desde" id="inputFechaDesde" value="{{ request('fecha_desde') }}" class="input-field py-2" max="{{ date('Y-m-d') }}">
            </div>
            <div>
                <label class="block text-[10px] uppercase font-bold text-slate-500 mb-1">Fecha Hasta</label>
                <input type="date" name="fecha_hasta" id="inputFechaHasta" value="{{ request('fecha_hasta') }}" class="input-field py-2" max="{{ date('Y-m-d') }}">
            </div>
        </div>
    </x-filter-bar>

    <div id="table-container" class="transition-opacity duration-300">

    @if(isset($resumen))
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4 no-print">
        <div class="bg-green-50 border border-green-200 rounded-xl p-3 text-center">
            <p class="text-xs text-green-600 font-semibold uppercase">Total Entradas</p>
            <p class="text-lg font-black text-green-700">{{ number_format($resumen->total_entradas, 2) }}</p>
            <p class="text-xs text-green-500">S/ {{ number_format($resumen->total_entradas_val, 2) }}</p>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-xl p-3 text-center">
            <p class="text-xs text-red-600 font-semibold uppercase">Total Salidas</p>
            <p class="text-lg font-black text-red-700">{{ number_format($resumen->total_salidas, 2) }}</p>
            <p class="text-xs text-red-500">S/ {{ number_format($resumen->total_salidas_val, 2) }}</p>
        </div>
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-3 text-center">
            <p class="text-xs text-blue-600 font-semibold uppercase">Saldo Final (Cant.)</p>
            <p class="text-lg font-black text-blue-700">{{ number_format($resumen->saldo_final_cantidad, 2) }}</p>
        </div>
        <div class="bg-primary-50 border border-primary-200 rounded-xl p-3 text-center">
            <p class="text-xs text-primary-600 font-semibold uppercase">Saldo Final (Valorizado)</p>
            <p class="text-lg font-black text-primary-700">S/ {{ number_format($resumen->saldo_final_valor, 2) }}</p>
        </div>
    </div>
    @endif

    <div class="card overflow-x-auto shadow-sm border border-slate-300 rounded-2xl">
        <table class="w-full text-left border-collapse text-xs sm:text-sm min-w-[1000px]">
            <thead>
                <tr class="bg-slate-900 text-slate-200 uppercase tracking-wider font-bold text-center text-xs">
                    <th class="p-3 border border-slate-800 align-middle w-24" rowspan="2">Fecha</th>
                    <th class="p-3 border border-slate-800 align-middle w-24" rowspan="2">Tipo Operac.</th>
                    <th class="p-3 border border-slate-800 align-middle w-48" rowspan="2">Documento / Ref.</th>
                    <th class="p-2 border border-slate-800 text-emerald-400" colspan="3">Entradas</th>
                    <th class="p-2 border border-slate-800 text-red-400" colspan="3">Salidas</th>
                    <th class="p-2 border border-slate-800 text-blue-300" colspan="3">Saldo Final</th>
                </tr>
                <tr class="bg-slate-800 text-slate-300 text-[10px] uppercase tracking-wider font-bold text-center">
                    <th class="p-2 border border-slate-700 w-24 text-emerald-400">Cantidad</th>
                    <th class="p-2 border border-slate-700 w-24">C. Unitario</th>
                    <th class="p-2 border border-slate-700 w-28 text-emerald-400">Costo Total</th>
                    <th class="p-2 border border-slate-700 w-24 text-red-400">Cantidad</th>
                    <th class="p-2 border border-slate-700 w-24">C. Unitario</th>
                    <th class="p-2 border border-slate-700 w-28 text-red-400">Costo Total</th>
                    <th class="p-2 border border-slate-700 w-24 text-blue-300">Cantidad</th>
                    <th class="p-2 border border-slate-700 w-24">C. Promedio</th>
                    <th class="p-2 border border-slate-700 w-28 text-blue-300">Total Valorizado</th>
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
                        <td colspan="12" class="py-2.5 px-4">
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
                    <!-- PRODUCTOS EN ESTE ALMACÉN -->
                    <!-- ========================================== -->
                    @foreach($productosDelAlmacen as $prod)
                        @php
                            $key = $prod->codigo_almacen . '__' . $prod->codigo_producto;
                            $movs = $movimientosPorGrupo->get($key, collect());
                            $invData = $stockActualPorGrupo->get($key, []);
                            $ultimoMov = $movs->last();
                            $saldoCant = $invData['stock_actual'] ?? ($ultimoMov ? $ultimoMov->cantidad_saldo : 0);
                            $saldoCostoProm = $invData['costo_promedio'] ?? ($ultimoMov ? $ultimoMov->costo_promedio : 0);
                            $saldoTotalVal = $invData['total_valorizado'] ?? ($ultimoMov ? $ultimoMov->total_saldo : 0);
                            $totEntradaCant = $movs->sum('cantidad_entrada');
                            $totEntradaVal  = $movs->sum('total_entrada');
                            $totSalidaCant  = $movs->sum('cantidad_salida');
                            $totSalidaVal   = $movs->sum('total_salida');
                        @endphp

                        <!-- FILA ENCABEZADO DE PRODUCTO -->
                        <tr class="bg-indigo-50/90 text-indigo-950 border-y border-indigo-200">
                            <td colspan="12" class="py-2 px-4 font-bold">
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
                                    <div class="flex flex-wrap items-center gap-2.5 text-xs">
                                        <span class="text-emerald-700 font-bold" title="Total entradas en este almacén">
                                            Entradas: +{{ number_format($totEntradaCant, 2) }} <span class="text-[10px] text-emerald-600 font-normal">(S/ {{ number_format($totEntradaVal, 2) }})</span>
                                        </span>
                                        <span class="text-red-700 font-bold" title="Total salidas en este almacén">
                                            Salidas: -{{ number_format($totSalidaCant, 2) }} <span class="text-[10px] text-red-600 font-normal">(S/ {{ number_format($totSalidaVal, 2) }})</span>
                                        </span>
                                        <span class="px-2.5 py-0.5 rounded bg-blue-100 text-blue-900 font-black border border-blue-200" title="Saldo actual en este almacén">
                                            Saldo: {{ number_format($saldoCant, 2) }} {{ $prod->codigo_unidad_medida }} | C.P.: S/ {{ number_format($saldoCostoProm, 4) }} | Total: S/ {{ number_format($saldoTotalVal, 2) }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                        </tr>

                        <!-- MOVIMIENTOS VALORIZADOS DEL PRODUCTO EN ESTE ALMACÉN -->
                        @forelse($movs as $mov)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                {{-- Fecha --}}
                                <td class="p-2.5 border-r border-slate-100 text-slate-600 whitespace-nowrap text-xs text-center font-medium">
                                    <span class="font-bold text-slate-800 block text-[11px]">
                                        {{ \Carbon\Carbon::parse($mov->fecha_movimiento)->format('d/m/Y') }}
                                    </span>
                                    <span class="text-[10px] text-slate-400">
                                        {{ \Carbon\Carbon::parse($mov->fecha_movimiento)->format('H:i') }}
                                    </span>
                                </td>

                                {{-- Tipo Operación --}}
                                <td class="p-2.5 border-r border-slate-100 text-center whitespace-nowrap">
                                    @php
                                        $badgeColor = [
                                            'INGRESO' => 'green',
                                            'SALIDA'  => 'red',
                                            'AJUSTE'  => 'amber',
                                            'EXTORNO' => 'purple',
                                        ][$mov->tipo_movimiento] ?? 'slate';
                                    @endphp
                                    <x-badge color="{{ $badgeColor }}">{{ $mov->tipo_movimiento }}</x-badge>
                                </td>

                                {{-- Documento / Referencia --}}
                                <td class="p-2.5 border-r border-slate-100">
                                    <div class="flex items-center gap-2">
                                        <div>
                                            <span class="text-slate-800 font-bold text-xs block">{{ $mov->documento ?? 'SIN DOCUMENTO' }}</span>
                                            @if($mov->numero_documento)
                                                <span class="font-mono text-[10px] text-slate-500 font-medium">#{{ $mov->numero_documento }}</span>
                                            @endif
                                            @if($mov->observaciones)
                                                <p class="text-[10px] text-slate-400 truncate max-w-[180px]" title="{{ $mov->observaciones }}">
                                                    {{ $mov->observaciones }}
                                                </p>
                                            @endif
                                        </div>
                                        @if($mov->tipo_movimiento === 'INGRESO' && in_array($mov->documento, ['RECEPCION_PEP', 'RECEPCION_PEP_GLOBAL', 'PRODUCCION', 'MERMA']))
                                            <button type="button" onclick="verDesgloseCosto({{ $mov->id_kardex }})" class="text-blue-500 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 p-1 rounded transition-colors shrink-0 ml-auto" title="Ver desglose de costo">
                                                <i class="fas fa-eye text-xs"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>

                                <!-- ENTRADAS -->
                                <td class="p-2.5 border-r border-slate-100 text-right font-semibold text-emerald-700 bg-emerald-50/20 whitespace-nowrap">
                                    {{ $mov->cantidad_entrada > 0 ? number_format($mov->cantidad_entrada, 2) : '-' }}
                                </td>
                                <td class="p-2.5 border-r border-slate-100 text-right text-slate-600 bg-emerald-50/20 whitespace-nowrap font-mono text-[11px]">
                                    @if($mov->cantidad_entrada > 0)
                                        {{ number_format($mov->costo_entrada > 0 ? $mov->costo_entrada : ($mov->total_entrada / $mov->cantidad_entrada), 4) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="p-2.5 border-r border-slate-200 text-right font-bold text-emerald-700 bg-emerald-50/30 whitespace-nowrap font-mono">
                                    {{ $mov->total_entrada > 0 ? number_format($mov->total_entrada, 2) : '-' }}
                                </td>

                                <!-- SALIDAS -->
                                <td class="p-2.5 border-r border-slate-100 text-right font-semibold text-red-700 bg-red-50/20 whitespace-nowrap">
                                    {{ $mov->cantidad_salida > 0 ? number_format($mov->cantidad_salida, 2) : '-' }}
                                </td>
                                <td class="p-2.5 border-r border-slate-100 text-right text-slate-600 bg-red-50/20 whitespace-nowrap font-mono text-[11px]">
                                    @if($mov->cantidad_salida > 0)
                                        {{ number_format($mov->costo_salida > 0 ? $mov->costo_salida : ($mov->total_salida / $mov->cantidad_salida), 4) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="p-2.5 border-r border-slate-200 text-right font-bold text-red-700 bg-red-50/30 whitespace-nowrap font-mono">
                                    {{ $mov->total_salida > 0 ? number_format($mov->total_salida, 2) : '-' }}
                                </td>

                                <!-- SALDO FINAL -->
                                <td class="p-2.5 border-r border-slate-100 text-right font-black text-slate-900 bg-blue-50/20 whitespace-nowrap font-mono">
                                    {{ number_format($mov->cantidad_saldo, 2) }}
                                </td>
                                <td class="p-2.5 border-r border-slate-100 text-right text-slate-700 bg-blue-50/20 whitespace-nowrap font-mono text-[11px]">
                                    {{ $mov->costo_promedio > 0 ? number_format($mov->costo_promedio, 4) : '0.0000' }}
                                </td>
                                <td class="p-2.5 text-right font-black text-indigo-950 bg-blue-50/30 whitespace-nowrap font-mono">
                                    {{ $mov->total_saldo > 0 ? number_format($mov->total_saldo, 2) : '0.00' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="p-3 text-center text-slate-400 italic">
                                    Sin movimientos registrados para este producto en los filtros seleccionados.
                                </td>
                            </tr>
                        @endforelse

                    @endforeach
                @empty
                    <tr>
                        <td colspan="12" class="p-12 text-center text-slate-400">
                            <i class="fas fa-history text-4xl mb-3 opacity-20"></i>
                            <p class="font-semibold">No hay movimientos registrados en el historial para los filtros seleccionados.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        
        @if($gruposPaginados->hasPages())
            <div class="p-4 border-t border-slate-200 bg-slate-50 no-print">
                {{ $gruposPaginados->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Scripts section omitted for brevity but intact -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('filter-bar-form'); // x-filter-bar uses id="filter-bar-form"
    const inputProducto = document.getElementById('inputProducto');
    const tableContainer = document.getElementById('table-container');
    let timeout = null;

    function fetchResults(url = null) {
        if (!url) {
            url = new URL(window.location.href);
            const formData = new FormData(form);
            for (const [key, val] of formData) {
                if (val) {
                    url.searchParams.set(key, val);
                } else {
                    url.searchParams.delete(key);
                }
            }
            url.searchParams.delete('page');
        }
        window.history.pushState({}, '', url);
        
        const currentParams = url.searchParams.toString();
        document.getElementById('btn-export-excel').href = "{{ route('inventario.kardex.exportar.excel') }}?" + currentParams;
        document.getElementById('btn-export-pdf').href = "{{ route('inventario.kardex.exportar.pdf') }}?" + currentParams;
        
        tableContainer.classList.add('opacity-50', 'pointer-events-none');

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newContainer = doc.getElementById('table-container');
                if (newContainer) {
                    tableContainer.innerHTML = newContainer.innerHTML;
                }
            })
            .catch(error => console.error('Error al filtrar:', error))
            .finally(() => {
                tableContainer.classList.remove('opacity-50', 'pointer-events-none');
            });
    }

    if(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            fetchResults();
        });
    }

    if(inputProducto) {
        inputProducto.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => fetchResults(), 400);
        });
    }

    if(form) {
        form.querySelectorAll('select, input[type="date"]').forEach(function(el) {
            el.addEventListener('change', function() {
                fetchResults();
            });
        });
    }

    tableContainer.addEventListener('click', function(e) {
        const aTag = e.target.closest('nav[role="navigation"] a');
        if (aTag) {
            e.preventDefault();
            fetchResults(new URL(aTag.href));
        }
    });
});

window.verDesgloseCosto = function(id) {
    const modal = document.getElementById('modalDesgloseCosto');
    const content = document.getElementById('modalDesgloseContent');
    modal.classList.remove('hidden');
    content.innerHTML = '<div class="flex justify-center p-4"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div></div>';

    fetch(`/admin/inventario/kardex/${id}/desglose-costo`)
        .then(res => {
            if (!res.ok) throw new Error('Error en red o servidor');
            return res.json();
        })
        .then(data => {
            if (data.html) {
                content.innerHTML = data.html;
            } else if (data.error) {
                content.innerHTML = `<p class="text-red-500 text-sm p-4">${data.error}</p>`;
            }
        })
        .catch(err => {
            content.innerHTML = `<p class="text-red-500 text-sm p-4">Error al cargar el desglose. Es posible que el registro ya no exista o el servidor esté fallando.</p>`;
            console.error(err);
        });
};

window.cerrarModalDesglose = function() {
    document.getElementById('modalDesgloseCosto').classList.add('hidden');
};
</script>

<!-- Modal Desglose de Costo -->
<div id="modalDesgloseCosto" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-sm overflow-y-auto">
    <div class="flex min-h-screen items-center justify-center p-4">
        <div class="relative w-full max-w-2xl rounded-2xl bg-white shadow-2xl overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h3 class="text-lg font-bold text-slate-800">Desglose de Costo de Producción</h3>
                <button onclick="cerrarModalDesglose()" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div id="modalDesgloseContent" class="p-6">
                <!-- Contenido cargado por AJAX -->
            </div>
            <div class="bg-slate-50 px-6 py-4 flex justify-end">
                <button onclick="cerrarModalDesglose()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 rounded-lg text-sm font-medium transition-colors">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        .container { max-width: 100% !important; width: 100% !important; }
        nav, form, button, .no-print { display: none !important; }
        .shadow-sm { box-shadow: none !important; border: 1px solid #e2e8f0 !important; }
        table { font-size: 10px !important; }
    }
</style>
@endsection
