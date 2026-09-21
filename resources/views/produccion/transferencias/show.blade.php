@extends('layouts.app')

@section('title', 'Comprobante de Transferencia ' . $transferencia->numero_transferencia)

@section('content')
<div class="container mx-auto pb-8 md:pb-10 max-w-5xl">
    
    <!-- Barra Superior -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 print:hidden">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                <a href="{{ route('produccion.transferencias.index') }}" class="hover:text-blue-600 transition flex items-center gap-1.5 font-medium">
                    <i class="fas fa-arrow-left text-xs"></i> Volver al Historial
                </a>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight flex items-center gap-2.5">
                <span>Transferencia de Producción</span>
                <span class="text-emerald-700 font-mono">{{ $transferencia->numero_transferencia }}</span>
            </h1>
        </div>

        <div class="flex items-center gap-2.5">
            <button onclick="window.print()" class="btn-secondary h-10 px-4 text-xs font-bold uppercase tracking-wide">
                <i class="fas fa-print mr-1.5"></i> Imprimir
            </button>
            @if ($transferencia->estado === 'COMPLETADO')
                <form action="{{ route('produccion.transferencias.anular', $transferencia->id_transferencia) }}" method="POST" id="formAnularShow">
                    @csrf
                    <button type="button" onclick="confirmarAnulacion()" class="inline-flex items-center justify-center h-10 px-4 text-xs font-bold uppercase tracking-wide bg-red-50 text-red-600 border border-red-200 hover:bg-red-600 hover:text-white rounded-lg transition shadow-2xs">
                        <i class="fas fa-ban mr-1.5"></i> Anular
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Comprobante / Card Principal -->
    <div class="bg-white rounded-xl shadow-md border border-slate-200/80 overflow-hidden mb-6">
        
        <!-- Header del Comprobante -->
        <div class="bg-slate-800 text-white p-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs font-extrabold uppercase tracking-wider text-emerald-400 bg-emerald-950/80 px-2.5 py-0.5 rounded border border-emerald-800/80">
                            TRAP - Producción
                        </span>
                        @if ($transferencia->estado === 'COMPLETADO')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">
                                <i class="fas fa-check-circle mr-1 text-[10px]"></i> COMPLETADO
                            </span>
                        @elseif ($transferencia->estado === 'ANULADO')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-500/20 text-red-300 border border-red-500/40">
                                <i class="fas fa-ban mr-1 text-[10px]"></i> ANULADO
                            </span>
                        @endif
                    </div>
                    <h2 class="text-2xl font-black tracking-tight text-white">{{ $transferencia->numero_transferencia }}</h2>
                </div>

                <div class="text-right">
                    <p class="text-xs text-slate-400 uppercase font-semibold">Fecha de Traslado</p>
                    <p class="text-lg font-bold text-white">{{ \Carbon\Carbon::parse($transferencia->fecha_transferencia)->format('d/m/Y') }}</p>
                </div>
            </div>
        </div>

        <!-- Metadatos de la Transferencia -->
        <div class="p-6 border-b border-slate-100 bg-slate-50/50">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-5">
                <div>
                    <span class="block text-[11px] font-bold text-slate-500 uppercase tracking-wide mb-1">🏭 Almacén Origen</span>
                    <p class="text-sm font-bold text-slate-900">
                        {{ $transferencia->almacenOrigen ? $transferencia->almacenOrigen->descripcion : $transferencia->codigo_almacen_origen }}
                    </p>
                    <span class="text-xs text-slate-500 font-mono">{{ $transferencia->codigo_almacen_origen }}</span>
                </div>

                <div>
                    <span class="block text-[11px] font-bold text-slate-500 uppercase tracking-wide mb-1">📍 Almacén Destino</span>
                    <p class="text-sm font-bold text-slate-900">
                        {{ $transferencia->almacenDestino ? $transferencia->almacenDestino->descripcion : $transferencia->codigo_almacen_destino }}
                    </p>
                    <span class="text-xs text-slate-500 font-mono">{{ $transferencia->codigo_almacen_destino }}</span>
                </div>

                <div>
                    <span class="block text-[11px] font-bold text-slate-500 uppercase tracking-wide mb-1">👤 Registrado Por</span>
                    <p class="text-sm font-bold text-slate-900">
                        {{ $transferencia->usuario ? $transferencia->usuario->nombre_completo : 'Sistema' }}
                    </p>
                    <span class="text-xs text-slate-500">{{ $transferencia->created_at ? $transferencia->created_at->format('H:i:s') : '' }}</span>
                </div>

                <div>
                    <span class="block text-[11px] font-bold text-slate-500 uppercase tracking-wide mb-1">📝 Observaciones</span>
                    <p class="text-xs font-medium text-slate-700 italic">
                        {{ $transferencia->observaciones ?: 'Sin observaciones registradas.' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Sección 1: Resumen por Producto Totalizado -->
        <div class="p-6">
            <div class="flex items-center gap-2 mb-4">
                <i class="fas fa-boxes text-emerald-600 text-base"></i>
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Resumen de Productos Transferidos</h3>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-200/80 mb-6">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-800 text-white text-[11px] uppercase tracking-wider font-bold">
                            <th class="py-3 px-4 w-32">Código</th>
                            <th class="py-3 px-4">Descripción del Artículo</th>
                            <th class="py-3 px-4 text-center">Tipo</th>
                            <th class="py-3 px-4 text-center">U.M.</th>
                            <th class="py-3 px-4 text-center">Lotes Afectados</th>
                            <th class="py-3 px-4 text-right pr-6">Cantidad Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($resumenProductos as $res)
                            <tr class="hover:bg-slate-50/80 transition duration-150">
                                <td class="px-4 py-3 font-extrabold text-xs text-slate-900 font-mono">{{ $res['codigo_producto'] }}</td>
                                <td class="px-4 py-3 text-xs font-medium text-slate-800 uppercase">
                                    {{ $res['producto'] ? $res['producto']->descripcion : $res['codigo_producto'] }}
                                </td>
                                <td class="px-4 py-3 text-center text-xs">
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 uppercase">
                                        {{ $res['producto'] && $res['producto']->tipo ? $res['producto']->tipo->descripcion : ($res['producto']->codigo_tipo_producto ?? 'N/A') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center text-xs font-bold text-slate-600">
                                    {{ $res['producto']->codigo_unidad_medida ?? 'UND' }}
                                </td>
                                <td class="px-4 py-3 text-center text-xs">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 text-xs font-semibold border border-blue-100">
                                        <i class="fas fa-layer-group text-[10px]"></i> {{ $res['total_lotes'] }} lote(s)
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right pr-6 font-black text-xs md:text-sm text-emerald-700">
                                    {{ number_format($res['cantidad_total'], 4, '.', '') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Sección 2: Desglose Lote por Lote (Trazabilidad FIFO) -->
            <div class="flex items-center gap-2 mb-4">
                <i class="fas fa-stream text-blue-600 text-base"></i>
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Desglose de Lotes Descontados e Ingresados (Trazabilidad FIFO)</h3>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-200/80">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-100 text-slate-700 text-[10px] uppercase tracking-wider font-extrabold border-b border-slate-200">
                            <th class="py-2.5 px-4 w-12 text-center">#</th>
                            <th class="py-2.5 px-4 w-32">Código Producto</th>
                            <th class="py-2.5 px-4">Lote Original</th>
                            <th class="py-2.5 px-4 text-right pr-6">Cantidad Lote</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($transferencia->detalles as $idx => $det)
                            <tr class="hover:bg-slate-50/50 text-xs transition">
                                <td class="px-4 py-2 text-center text-slate-400 font-bold">{{ $idx + 1 }}</td>
                                <td class="px-4 py-2 font-mono font-bold text-slate-900">{{ $det->codigo_producto }}</td>
                                <td class="px-4 py-2">
                                    <span class="inline-block bg-slate-100 text-slate-800 px-2 py-0.5 rounded font-mono font-bold text-xs border border-slate-200">
                                        {{ $det->lote ?: 'Sin Lote' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-right pr-6 font-mono font-bold text-slate-900">
                                    {{ number_format($det->cantidad, 4, '.', '') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>

    </div>
</div>

@push('scripts')
<script>
    function confirmarAnulacion() {
        Swal.fire({
            title: '¿Anular esta Transferencia?',
            text: 'Se verificará que el stock siga en el almacén destino y se devolverá a los lotes originales del almacén de origen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Sí, anular',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('formAnularShow').submit();
            }
        });
    }
</script>
@endpush
@endsection
