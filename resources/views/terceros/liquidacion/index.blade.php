@extends('layouts.app')
@section('title', 'Liquidación y Control de Terceros')

@section('content')
<div class="container mx-auto pb-8 md:pb-10">

    <!-- Cabecera Estándar -->
    <x-page-header 
        title="Liquidación y Control de Terceros" 
        subtitle="Kardex cronológico de envíos a maquila, retornos amortizados y liquidación de mermas."
    >
        <x-slot:actions>
            <a href="{{ route('terceros.salidas.create') }}" class="btn-primary">
                <i class="fas fa-plus"></i>
                <span class="hidden sm:inline ml-2">Nueva Guía Salida</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- Barra de Filtros -->
    <div class="bg-white rounded-xl shadow-md border border-slate-200/80 p-4 md:p-5 mb-5">
        <form method="GET" action="{{ route('terceros.liquidacion.index') }}" id="filterForm">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                        <i class="fas fa-calendar-alt text-emerald-600 mr-1"></i> Desde
                    </label>
                    <input type="date" name="fecha_desde" value="{{ $fechaDesde }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                        <i class="fas fa-calendar-check text-emerald-600 mr-1"></i> Hasta
                    </label>
                    <input type="date" name="fecha_hasta" value="{{ $fechaHasta }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                        <i class="fas fa-building text-emerald-600 mr-1"></i> Proveedor
                    </label>
                    <select name="proveedor" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs">
                        <option value="">-- Todos --</option>
                        @foreach($proveedores as $prov)
                            <option value="{{ $prov }}" {{ request('proveedor') == $prov ? 'selected' : '' }}>
                                {{ Str::limit($prov, 35) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                        <i class="fas fa-tag text-emerald-600 mr-1"></i> Estado
                    </label>
                    <select name="estado" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs">
                        <option value="">-- Todos --</option>
                        <option value="PENDIENTE" {{ request('estado') == 'PENDIENTE' ? 'selected' : '' }}>PENDIENTE</option>
                        <option value="PARCIAL" {{ request('estado') == 'PARCIAL' ? 'selected' : '' }}>PARCIAL</option>
                        <option value="COMPLETADO" {{ request('estado') == 'COMPLETADO' ? 'selected' : '' }}>COMPLETADO</option>
                        <option value="CERRADO_CON_MERMA" {{ request('estado') == 'CERRADO_CON_MERMA' ? 'selected' : '' }}>MERMA</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                        <i class="fas fa-search text-emerald-600 mr-1"></i> Buscar
                    </label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Guía, producto, proveedor..." class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs">
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <a href="{{ route('terceros.liquidacion.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs sm:text-sm font-semibold rounded-lg border border-slate-200 transition">
                    <i class="fas fa-undo-alt"></i> Mes Actual
                </a>
                <button type="submit" class="inline-flex items-center gap-1.5 px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs sm:text-sm font-bold rounded-lg shadow-sm transition cursor-pointer">
                    <i class="fas fa-filter"></i> Aplicar Filtros
                </button>
            </div>
        </form>
    </div>

    <!-- Tarjetas de Resumen KPI -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
        <div class="bg-white rounded-xl shadow-xs border border-slate-200/80 p-4 flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg shrink-0">
                <i class="fas fa-truck-loading"></i>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Enviado</p>
                <p class="text-lg sm:text-xl font-extrabold text-slate-800">{{ number_format($resumen->total_enviado, 2) }} <span class="text-xs font-semibold text-slate-400">KG</span></p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-xs border border-slate-200/80 p-4 flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg shrink-0">
                <i class="fas fa-dolly"></i>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Retornado</p>
                <p class="text-lg sm:text-xl font-extrabold text-emerald-600">+ {{ number_format($resumen->total_devuelto, 2) }} <span class="text-xs font-semibold text-emerald-400">KG</span></p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-xs border border-slate-200/80 p-4 flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-red-50 text-red-600 flex items-center justify-center text-lg shrink-0">
                <i class="fas fa-balance-scale-right"></i>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Mermas Liquidadas</p>
                <p class="text-lg sm:text-xl font-extrabold text-red-600">{{ number_format($resumen->total_merma, 2) }} <span class="text-xs font-semibold text-red-400">KG</span></p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-xs border border-slate-200/80 p-4 flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg shrink-0">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Saldo en Terceros</p>
                <p class="text-lg sm:text-xl font-extrabold text-blue-700">{{ number_format($resumen->saldo_pendiente, 2) }} <span class="text-xs font-semibold text-blue-400">KG</span></p>
            </div>
        </div>
    </div>

    <!-- Encabezado de Listado -->
    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
        <div class="flex items-center gap-2">
            <h2 class="text-sm sm:text-base font-bold text-slate-800 flex items-center gap-2">
                <i class="fas fa-clipboard-list text-primary"></i>
                <span>Kardex de Liquidación por Guía de Envío</span>
            </h2>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                {{ $detalles->total() }} {{ Str::plural('registro', $detalles->total()) }}
            </span>
        </div>
        <span class="text-xs text-slate-500">
            Mostrando cronología de salidas, ingresos por guía de compra y saldos
        </span>
    </div>

    <!-- Tarjetas Agrupadas Tipo Kardex (Opción B) -->
    @forelse($detalles as $det)
        @php
            $saldoPendiente = max(0, $det->cantidad_enviada - $det->cantidad_devuelta - $det->cantidad_merma);
            $conciliaciones = $conciliacionesPorDetalle->get($det->id_detalle_salida, collect());
            $saldoProgresivo = floatval($det->cantidad_enviada);

            $estadoBadges = [
                'PENDIENTE'         => 'bg-amber-100 text-amber-800 border-amber-200',
                'PARCIAL'           => 'bg-blue-100 text-blue-800 border-blue-200',
                'COMPLETADO'        => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                'CERRADO_CON_MERMA' => 'bg-red-100 text-red-700 border-red-200',
            ];
            $badgeClass = $estadoBadges[$det->estado_detalle] ?? 'bg-slate-100 text-slate-700 border-slate-200';
            $estadoLabel = $det->estado_detalle == 'CERRADO_CON_MERMA' ? 'MERMA' : $det->estado_detalle;
        @endphp

        <div class="bg-white rounded-xl shadow-md border border-slate-200/90 overflow-hidden mb-5">
            <!-- Cabecera de la Tarjeta -->
            <div class="bg-slate-50/90 border-b border-slate-200 px-4 py-3 sm:px-5 flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('terceros.salidas.show', $det->id_guia_salida) }}" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-slate-900 hover:bg-slate-800 text-white font-mono text-xs font-bold transition shadow-2xs" title="Ver Guía de Salida">
                        <i class="fas fa-file-export text-amber-400"></i>
                        <span>{{ $det->numero_guia }}</span>
                    </a>

                    <div>
                        <div class="text-sm font-extrabold text-slate-800 flex items-center gap-2">
                            <span>{{ $det->proveedor_destino }}</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase border {{ $badgeClass }}">
                                {{ $estadoLabel }}
                            </span>
                        </div>
                        <div class="text-xs text-slate-500 mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-0.5">
                            <span><strong class="text-slate-700">Material Enviado:</strong> {{ $det->codigo_producto }} — {{ $det->producto_descripcion ?? 'Producto' }}</span>
                            <span><i class="fas fa-warehouse text-slate-400 mr-1"></i>Origen: {{ $det->codigo_almacen_origen }}</span>
                        </div>
                    </div>
                </div>

                <!-- Totales de la tarjeta + Acción A Merma -->
                <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                    <div class="flex items-center gap-2 text-xs bg-white px-3 py-1.5 rounded-lg border border-slate-200 shadow-2xs">
                        <span class="text-slate-500">Enviado: <strong class="text-amber-700">{{ number_format($det->cantidad_enviada, 2) }}</strong></span>
                        <span class="text-slate-300">|</span>
                        <span class="text-slate-500">Devuelto: <strong class="text-emerald-600">{{ number_format($det->cantidad_devuelta, 2) }}</strong></span>
                        <span class="text-slate-300">|</span>
                        @if($det->estado_detalle == 'CERRADO_CON_MERMA')
                            <span class="text-slate-500">Merma: <strong class="text-red-600">{{ number_format($det->cantidad_merma, 2) }} KG</strong></span>
                        @else
                            <span class="text-slate-500">Saldo: <strong class="{{ $saldoPendiente > 0.01 ? 'text-blue-700' : 'text-slate-700' }}">{{ number_format($saldoPendiente, 2) }} KG</strong></span>
                        @endif
                    </div>

                    @if(in_array($det->estado_detalle, ['PENDIENTE', 'PARCIAL']) && $saldoPendiente > 0.009)
                        <button type="button" onclick="liquidarMerma({{ $det->id_detalle_salida }})" class="inline-flex items-center px-3 py-1.5 bg-red-50 hover:bg-red-600 text-red-600 hover:text-white border border-red-200 rounded-lg text-xs font-bold transition shadow-2xs cursor-pointer">
                            <i class="fas fa-balance-scale-right mr-1.5"></i>A Merma
                        </button>
                    @endif
                </div>
            </div>

            <!-- Tabla Mini-Kardex Cronológico -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[850px] text-xs">
                    <thead>
                        <tr class="bg-slate-800 text-white text-[10px] uppercase tracking-wider font-bold">
                            <th class="py-2.5 px-4 w-28 text-center">Fecha</th>
                            <th class="py-2.5 px-4 w-40 text-center">Operación</th>
                            <th class="py-2.5 px-4 w-48">Doc. Referencia</th>
                            <th class="py-2.5 px-4">Producto / Material</th>
                            <th class="py-2.5 px-4 w-40">Lote / Almacén</th>
                            <th class="py-2.5 px-4 w-28 text-right text-amber-300">Salida (-)</th>
                            <th class="py-2.5 px-4 w-28 text-right text-emerald-300">Ingreso (+)</th>
                            <th class="py-2.5 px-4 w-32 text-right">Saldo Tercero</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <!-- 1. Movimiento Inicial: Salida hacia Tercero -->
                        <tr class="hover:bg-amber-50/30 transition-colors">
                            <td class="py-2.5 px-4 text-center font-medium text-slate-600 whitespace-nowrap">
                                {{ date('d/m/Y', strtotime($det->fecha_emision)) }}
                            </td>
                            <td class="py-2.5 px-4 text-center">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                    <i class="fas fa-arrow-up text-[9px]"></i> SALIDA A TERCERO
                                </span>
                            </td>
                            <td class="py-2.5 px-4 font-bold text-slate-800 whitespace-nowrap">
                                <span class="text-[10px] text-slate-400 uppercase block font-semibold">Guía Salida</span>
                                {{ $det->numero_guia }}
                            </td>
                            <td class="py-2.5 px-4 text-slate-700">
                                <span class="font-bold text-slate-900">{{ $det->codigo_producto }}</span>
                                <span class="text-slate-500 block text-[11px]">{{ $det->producto_descripcion ?? '' }}</span>
                            </td>
                            <td class="py-2.5 px-4 text-slate-600 text-[11px]">
                                Origen: <strong>{{ $det->codigo_almacen_origen }}</strong>
                            </td>
                            <td class="py-2.5 px-4 text-right font-bold text-amber-700 whitespace-nowrap">
                                - {{ number_format($det->cantidad_enviada, 2) }}
                            </td>
                            <td class="py-2.5 px-4 text-right text-slate-300">-</td>
                            <td class="py-2.5 px-4 text-right font-extrabold text-slate-800 whitespace-nowrap bg-slate-50/60">
                                {{ number_format($saldoProgresivo, 2) }} <span class="text-[10px] font-normal text-slate-400">{{ $det->codigo_unidad_medida ?? 'KG' }}</span>
                            </td>
                        </tr>

                        <!-- 2. Movimientos de Ingreso / Retorno Amortizados -->
                        @foreach($conciliaciones as $conc)
                            @php
                                $saldoProgresivo -= floatval($conc->cantidad_amortizada);
                                $fechaRet = $conc->fecha_retorno ?? $conc->fecha_conciliacion;
                            @endphp
                            <tr class="hover:bg-emerald-50/30 transition-colors">
                                <td class="py-2.5 px-4 text-center font-medium text-slate-600 whitespace-nowrap">
                                    {{ date('d/m/Y', strtotime($fechaRet)) }}
                                </td>
                                <td class="py-2.5 px-4 text-center">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        <i class="fas fa-arrow-down text-[9px]"></i> RETORNO / INGRESO
                                    </span>
                                </td>
                                <td class="py-2.5 px-4 font-bold text-slate-800 whitespace-nowrap">
                                    <span class="text-[10px] text-slate-400 uppercase block font-semibold">Guía Compra</span>
                                    {{ $conc->guia_retorno }}
                                </td>
                                <td class="py-2.5 px-4 text-slate-700">
                                    <span class="font-bold text-slate-900">{{ $conc->producto_retornado }}</span>
                                    <span class="text-slate-500 block text-[11px]">{{ $conc->producto_retornado_desc }}</span>
                                </td>
                                <td class="py-2.5 px-4 text-slate-600 text-[11px]">
                                    @if($conc->lote)
                                        <span class="font-mono bg-slate-100 px-1.5 py-0.5 rounded text-slate-700">{{ $conc->lote }}</span>
                                    @endif
                                    <span class="block text-slate-400 mt-0.5">Dest: {{ $conc->almacen_destino }}</span>
                                </td>
                                <td class="py-2.5 px-4 text-right text-slate-300">-</td>
                                <td class="py-2.5 px-4 text-right font-bold text-emerald-600 whitespace-nowrap">
                                    + {{ number_format($conc->cantidad_amortizada, 2) }}
                                </td>
                                <td class="py-2.5 px-4 text-right font-extrabold text-slate-800 whitespace-nowrap bg-slate-50/60">
                                    {{ number_format(max(0, $saldoProgresivo), 2) }} <span class="text-[10px] font-normal text-slate-400">{{ $det->codigo_unidad_medida ?? 'KG' }}</span>
                                </td>
                            </tr>
                        @endforeach

                        <!-- 3. Si no hay ingresos y sigue pendiente -->
                        @if($conciliaciones->isEmpty() && $det->cantidad_merma <= 0)
                            <tr class="bg-slate-50/40">
                                <td colspan="8" class="py-2.5 px-4 text-center text-slate-400 italic text-xs">
                                    <i class="fas fa-clock mr-1"></i> Aún no se han registrado guías de ingreso / retorno para este envío.
                                </td>
                            </tr>
                        @endif

                        <!-- 4. Si se liquidó saldo residual a Merma -->
                        @if($det->cantidad_merma > 0)
                            @php
                                $saldoProgresivo -= floatval($det->cantidad_merma);
                            @endphp
                            <tr class="bg-red-50/20 hover:bg-red-50/40 transition-colors">
                                <td class="py-2.5 px-4 text-center text-slate-500">—</td>
                                <td class="py-2.5 px-4 text-center">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700">
                                        <i class="fas fa-exclamation-triangle text-[9px]"></i> MERMA LIQUIDADA
                                    </span>
                                </td>
                                <td class="py-2.5 px-4 font-semibold text-red-700">
                                    Cierre de Liquidación
                                </td>
                                <td class="py-2.5 px-4 text-slate-600 italic" colspan="2">
                                    Saldo residual cerrado a merma de maquila
                                </td>
                                <td class="py-2.5 px-4 text-right text-slate-300">-</td>
                                <td class="py-2.5 px-4 text-right font-bold text-red-600 whitespace-nowrap">
                                    Merma: {{ number_format($det->cantidad_merma, 2) }}
                                </td>
                                <td class="py-2.5 px-4 text-right font-extrabold text-slate-800 whitespace-nowrap bg-slate-50/60">
                                    {{ number_format(max(0, $saldoProgresivo), 2) }} <span class="text-[10px] font-normal text-slate-400">{{ $det->codigo_unidad_medida ?? 'KG' }}</span>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-12 text-center text-slate-400">
            <i class="fas fa-balance-scale text-4xl mb-3 opacity-30"></i>
            <p class="font-semibold text-slate-600">No se encontraron movimientos de terceros en el rango seleccionado.</p>
            <p class="text-xs text-slate-400 mt-1">Prueba modificando las fechas o limpiando los filtros aplicados.</p>
        </div>
    @endforelse

    <!-- Paginación -->
    @if($detalles->hasPages())
        <div class="mt-4 px-5 py-3.5 bg-white rounded-xl border border-slate-200/80 shadow-xs">
            {{ $detalles->links() }}
        </div>
    @endif
</div>

@push('scripts')
<script>
function liquidarMerma(id) {
    if (confirm('¿Está seguro de cerrar este saldo pendiente como MERMA? Esta acción no generará más retornos de esta guía.')) {
        fetch(`{{ url('terceros/liquidacion') }}/${id}/cerrar`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert(data.message || 'Error al procesar.');
            }
        })
        .catch(() => {
            alert('Error de red al intentar cerrar la merma.');
        });
    }
}
</script>
@endpush
@endsection
