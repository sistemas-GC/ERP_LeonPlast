@extends('layouts.app')
@section('title', 'Control de Inventario')

@section('content')
<div class="container mx-auto pb-8 md:pb-10">

    <!-- Header de Página -->
    <x-page-header title="Control de Almacenes" subtitle="Consulta de saldos físicos y movimientos del inventario" />

    <!-- Buscador y Filtros -->
    <x-filter-bar action="{{ route('inventario.index') }}">
        <div class="relative flex-1 min-w-[200px]">
            <input 
                type="text"
                id="searchInput"
                name="search"
                value="{{ request('search') }}"
                placeholder="Buscar por código o descripción..."
                class="input-field pl-11 shadow-xs"
            >
            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
        </div>
        <div class="relative sm:w-64">
            <select id="almacenFilter" name="almacen" class="input-field pl-10 appearance-none shadow-xs">
                <option value="todos">Todos los almacenes</option>
                @foreach($almacenes as $almacen)
                    <option value="{{ $almacen->codigo_almacen }}"
                        {{ request('almacen', 'todos') == $almacen->codigo_almacen ? 'selected' : '' }}>
                        {{ $almacen->descripcion }}
                    </option>
                @endforeach
            </select>
            <i class="fas fa-warehouse absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
        </div>
        <div class="relative sm:w-64">
            <select id="modoVistaFilter" name="modo_vista" class="input-field pl-10 appearance-none shadow-xs font-semibold text-slate-700">
                <option value="original" {{ ($modoVista ?? request('modo_vista', 'original')) === 'original' ? 'selected' : '' }}>
                    Ver en: Unidad del Producto
                </option>
                <option value="unidades" {{ ($modoVista ?? request('modo_vista')) === 'unidades' ? 'selected' : '' }}>
                    Ver en: Unidades (UND)
                </option>
            </select>
            <i class="fas fa-exchange-alt absolute left-4 top-1/2 -translate-y-1/2 text-primary"></i>
        </div>
    </x-filter-bar>

    <!-- Tabla -->
    <div id="table-container" class="transition-opacity duration-300">
        <div class="bg-white rounded-xl shadow-md border border-slate-200/80 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[800px]">
                    <thead>
                        <tr class="bg-slate-800 text-white text-[11px] uppercase tracking-wider font-bold">
                            <th class="py-3 px-4 md:px-6">Código</th>
                            <th class="py-3 px-4 md:px-6">Producto / Insumo</th>
                            <th class="py-3 px-4 md:px-6 text-center">Almacén</th>
                            <th class="py-3 px-4 md:px-6 text-right">Stock</th>
                            <th class="py-3 px-4 md:px-6 text-right">Stock Mín</th>
                            <th class="py-3 px-4 md:px-6 text-right">Stock Máx</th>
                            <th class="py-3 px-4 md:px-6 text-center">Último Movimiento</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($stocks as $stock)
                            @php
                                $bajoMinimo = $stock->stock_minimo > 0 && $stock->stock_actual < $stock->stock_minimo;
                            @endphp
                            <tr class="hover:bg-slate-50/80 transition duration-150 {{ $bajoMinimo ? 'bg-red-50/40' : '' }}">
                                <td class="px-4 md:px-6 py-3 font-bold text-slate-900 text-xs">
                                    {{ $stock->codigo_producto }}
                                </td>

                                <td class="px-4 md:px-6 py-3">
                                    <span class="font-bold text-slate-800 text-xs md:text-sm">
                                        {{ $stock->producto }}
                                    </span>
                                    @if($bajoMinimo)
                                        <span class="ml-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-[10px] font-bold">
                                            <i class="fas fa-exclamation-triangle"></i> BAJO MÍNIMO
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 md:px-6 py-3 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-full bg-emerald-50 text-emerald-800 border border-emerald-100/80">
                                        <i class="fas fa-warehouse text-[10px] text-emerald-600"></i> {{ $stock->almacen }}
                                    </span>
                                </td>

                                <td class="px-4 md:px-6 py-3 text-right whitespace-nowrap">
                                    @php
                                        $verEnUnidades = (($modoVista ?? request('modo_vista', 'original')) === 'unidades') && !empty($stock->tiene_conversion);
                                    @endphp
                                    @if($verEnUnidades)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-xs md:text-sm font-bold shadow-2xs bg-blue-50 text-blue-700 border border-blue-200/80" title="Convertido con peso: {{ rtrim(rtrim(number_format($stock->peso_referencia_gr ?? 0, 4, '.', ''), '0'), '.') }} gr/und">
                                            {{ number_format($stock->stock_unidades ?? 0) }} <span class="text-[10px] font-extrabold">UND</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-xs md:text-sm font-bold shadow-2xs
                                            {{ $bajoMinimo || $stock->stock_actual <= 0
                                                ? 'bg-red-50 text-red-700 border border-red-200/80'
                                                : 'bg-emerald-50 text-emerald-700 border border-emerald-200/80' }}">
                                            {{ number_format($stock->stock_actual, 2) }} <span class="text-[10px] font-semibold opacity-75">{{ $stock->codigo_unidad_medida ?? 'KG' }}</span>
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 md:px-6 py-3 text-right text-xs font-medium text-slate-600 whitespace-nowrap">
                                    {{ number_format($stock->stock_minimo, 2, '.', '') }}
                                </td>

                                <td class="px-4 md:px-6 py-3 text-right text-xs font-medium text-slate-600 whitespace-nowrap">
                                    {{ $stock->stock_maximo ? number_format($stock->stock_maximo, 2, '.', '') : '-' }}
                                </td>

                                <td class="px-4 md:px-6 py-3 text-center text-slate-500 text-xs font-medium whitespace-nowrap">
                                    {{ $stock->fecha_ultimo_movimiento 
                                        ? \Carbon\Carbon::parse($stock->fecha_ultimo_movimiento)->format('d/m/Y H:i') 
                                        : 'N/A' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-10 text-center text-slate-500 bg-slate-50/50">
                                    <i class="fas fa-box-open text-4xl text-slate-300 mb-3 block"></i>
                                    <p class="text-sm font-bold text-slate-700">
                                        No hay registros de inventario
                                    </p>
                                    <p class="text-xs text-slate-400 mt-1">
                                        Aún no existen movimientos para mostrar.
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            @if($stocks->hasPages())
                <div class="border-t border-slate-100 bg-slate-50 px-4 md:px-6 py-3.5">
                    {{ $stocks->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    const tableContainer = document.getElementById('table-container');
    let timeout = null;

    function fetchResults(url = null) {
        if (!url) {
            url = new URL(window.location.href);
            if (searchInput.value) {
                url.searchParams.set('search', searchInput.value);
            } else {
                url.searchParams.delete('search');
            }
            const almFilter = document.getElementById('almacenFilter');
            if (almFilter && almFilter.value !== 'todos') {
                url.searchParams.set('almacen', almFilter.value);
            } else {
                url.searchParams.delete('almacen');
            }
            const modoFilter = document.getElementById('modoVistaFilter');
            if (modoFilter && modoFilter.value !== 'original') {
                url.searchParams.set('modo_vista', modoFilter.value);
            } else {
                url.searchParams.delete('modo_vista');
            }
            url.searchParams.delete('page');
        }
        window.history.pushState({}, '', url);
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

    searchInput.addEventListener('input', function () {
        clearTimeout(timeout);
        timeout = setTimeout(() => fetchResults(), 400);
    });

    document.getElementById('almacenFilter').addEventListener('change', function () {
        fetchResults();
    });

    const modoVistaEl = document.getElementById('modoVistaFilter');
    if (modoVistaEl) {
        modoVistaEl.addEventListener('change', function () {
            fetchResults();
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
</script>
@endsection
