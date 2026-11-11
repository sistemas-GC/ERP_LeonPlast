@extends('layouts.app')
@section('title', 'Detalle de Compra #' . $compra->serie_documento . '-' . $compra->numero_documento)

@section('content')
<div class="container mx-auto pb-8 md:pb-10">
    
    <x-page-header title="{{ $compra->tipo_documento }}: {{ $compra->serie_documento }}-{{ $compra->numero_documento }}" subtitle="Registrado el {{ \Carbon\Carbon::parse($compra->fecha_creacion)->format('d/m/Y H:i') }}">
        <x-slot:actions>
            <div class="flex items-center gap-2">
                @switch($compra->estado)
                    @case('PENDIENTE')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5 animate-pulse"></span> PENDIENTE
                        </span>
                        @break
                    @case('CANCELADA')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-red-50 text-red-800 border border-red-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 mr-1.5"></span> ANULADA
                        </span>
                        @break
                    @default
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span> RECIBIDA
                        </span>
                @endswitch

                <a href="{{ route('compras.index') }}" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    <span class="hidden sm:inline ml-1">Volver</span>
                </a>
                <button onclick="window.print()" class="inline-flex items-center justify-center px-4 py-2 bg-slate-800 text-white rounded-lg text-sm font-semibold hover:bg-slate-700 transition shadow-xs">
                    <i class="fas fa-print mr-1.5"></i> Imprimir
                </button>
            </div>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl shadow-md border border-slate-200/80 overflow-hidden">
                <div class="bg-slate-50/70 border-b border-slate-100 px-6 py-3.5">
                    <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
                        <i class="fas fa-file-invoice text-emerald-600 text-sm"></i> 1. Información del Documento
                    </h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-6">
                        <div class="col-span-2 sm:col-span-1">
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Proveedor</p>
                            <p class="text-sm font-bold text-slate-800 mt-0.5">{{ $compra->proveedor }}</p>
                            <p class="text-xs text-slate-500 font-mono mt-0.5">RUC: {{ $compra->ruc_proveedor }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Fecha Emisión</p>
                            <p class="text-sm font-bold text-slate-800 mt-0.5">{{ \Carbon\Carbon::parse($compra->fecha_compra)->format('d/m/Y') }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Condición</p>
                            <div class="mt-1">
                                @if(($compra->condicion_pago ?? 'CONTADO') === 'CREDITO')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                        <i class="fas fa-clock text-[9px] text-amber-600"></i> Crédito
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                        <i class="fas fa-check-circle text-[9px] text-emerald-600"></i> Contado
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Fecha Vencimiento</p>
                            <p class="text-sm font-bold text-slate-800 mt-0.5">
                                {{ $compra->fecha_vencimiento ? \Carbon\Carbon::parse($compra->fecha_vencimiento)->format('d/m/Y') : '-' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Moneda</p>
                            <p class="text-sm font-bold text-slate-800 mt-0.5">{{ $compra->moneda ?? 'PEN' }}</p>
                            @if(($compra->moneda ?? 'PEN') === 'USD' && $compra->tipo_cambio)
                                <p class="text-xs text-slate-500 font-mono mt-0.5">T/C: {{ number_format($compra->tipo_cambio, 3) }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @if(($compra->condicion_pago ?? '') === 'CREDITO' && $compra->cuotas && $compra->cuotas->count() > 0)
            <div class="bg-white rounded-xl shadow-md border border-slate-200/80 overflow-hidden">
                <div class="bg-slate-50/70 border-b border-slate-100 px-6 py-3.5 flex items-center justify-between">
                    <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
                        <i class="fas fa-calendar-alt text-amber-500 text-sm"></i> Cuotas de la Factura
                    </h2>
                    <span class="text-xs font-bold px-2.5 py-0.5 bg-amber-50 text-amber-800 border border-amber-200 rounded-full">
                        {{ $compra->cuotas->count() }} {{ Str::plural('cuota', $compra->cuotas->count()) }}
                    </span>
                </div>
                <div class="p-5">
                    <div class="overflow-x-auto rounded-lg border border-slate-200 shadow-xs">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-800 text-white uppercase text-[11px] font-bold">
                                <tr>
                                    <th class="py-2.5 px-4 w-16 text-center border-r border-slate-700/60">N°</th>
                                    <th class="py-2.5 px-4 border-r border-slate-700/60">Fecha de Vencimiento</th>
                                    <th class="py-2.5 px-4 text-center border-r border-slate-700/60">Días para Vencer</th>
                                    <th class="py-2.5 px-4 text-right">Monto</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-medium">
                                @foreach($compra->cuotas as $cuota)
                                @php
                                    $dias = $cuota->fecha_vencimiento ? \Carbon\Carbon::now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($cuota->fecha_vencimiento)->startOfDay(), false) : null;
                                @endphp
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="py-2.5 px-4 text-center">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-slate-700 font-bold text-xs">
                                            {{ $cuota->numero_cuota }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-4 text-slate-800 font-semibold">
                                        {{ $cuota->fecha_vencimiento ? \Carbon\Carbon::parse($cuota->fecha_vencimiento)->format('d/m/Y') : '-' }}
                                    </td>
                                    <td class="py-2.5 px-4 text-center">
                                        @if($dias !== null)
                                            @if($dias > 0)
                                                <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-blue-50 text-blue-700">
                                                    En {{ $dias }} {{ Str::plural('día', $dias) }}
                                                </span>
                                            @elseif($dias === 0)
                                                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-amber-50 text-amber-700">
                                                    Vence hoy
                                                </span>
                                            @else
                                                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-red-50 text-red-700">
                                                    Venció hace {{ abs($dias) }} {{ Str::plural('día', abs($dias)) }}
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-4 text-right font-bold text-slate-900 text-sm">
                                        {{ $compra->moneda === 'USD' ? '$' : 'S/' }} {{ number_format($cuota->monto, 2) }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-slate-50 border-t border-slate-200 font-bold text-xs">
                                <tr>
                                    <td colspan="3" class="py-2.5 px-4 text-right text-slate-700 uppercase">Total Cuotas:</td>
                                    <td class="py-2.5 px-4 text-right text-primary text-sm font-black">
                                        {{ $compra->moneda === 'USD' ? '$' : 'S/' }} {{ number_format($compra->cuotas->sum('monto'), 2) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <div class="bg-white rounded-xl shadow-md border border-slate-200/80 overflow-hidden">
                <div class="bg-slate-50/70 border-b border-slate-100 px-6 py-3.5">
                    <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
                        <i class="fas fa-boxes text-emerald-600 text-sm"></i> 2. Detalle de Items y Productos
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse table-fixed">
                        <colgroup>
                            <col class="w-[26%]">
                            <col class="w-[12%]">
                            <col class="w-[16%]">
                            <col class="w-[14%]">
                            <col class="w-[10%]">
                            <col class="w-[10%]">
                            <col class="w-[12%]">
                        </colgroup>
                        <thead>
                            <tr class="bg-slate-800 text-[11px] uppercase text-white tracking-wider font-bold">
                                <th class="p-2.5 font-bold border-r border-slate-700/60">Producto</th>
                                <th class="p-2.5 font-bold text-center border-r border-slate-700/60">Almacén</th>
                                <th class="p-2.5 font-bold text-center border-r border-slate-700/60">Lote</th>
                                <th class="p-2.5 font-bold text-center border-r border-slate-700/60">Venc.</th>
                                <th class="p-2.5 font-bold text-center border-r border-slate-800">Cant.</th>
                                <th class="p-2.5 font-bold text-right border-r border-slate-800">P. Unit.</th>
                                <th class="p-2.5 font-bold text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($compra->detalles as $detalle)
                            <tr class="text-xs hover:bg-slate-50/50 transition-colors">
                                <td class="p-2">
                                    <div class="truncate font-medium text-slate-800" title="{{ $detalle->descripcion_producto }}">{{ $detalle->descripcion_producto }}</div>
                                    <div class="text-[10px] text-slate-500">{{ $detalle->codigo_producto }}</div>
                                </td>
                                <td class="p-2 text-center">
                                    <span class="px-1.5 py-0.5 bg-slate-100 rounded text-[10px] font-medium text-slate-600">
                                        {{ $detalle->codigo_almacen }}
                                    </span>
                                </td>
                                <td class="p-2 text-center text-slate-600">
                                    {{ $detalle->lote ?: '-' }}
                                </td>
                                <td class="p-2 text-center text-slate-600">
                                    {{ $detalle->fecha_vencimiento ? \Carbon\Carbon::parse($detalle->fecha_vencimiento)->format('d/m/Y') : '-' }}
                                </td>
                                <td class="p-2 text-center font-semibold text-slate-700">
                                    {{ number_format($detalle->cantidad, 2) }}
                                </td>
                                <td class="p-2 text-right text-slate-600">
                                    {{ number_format($detalle->precio_unitario, 2) }}
                                </td>
                                <td class="p-2 text-right font-semibold text-slate-900">
                                    {{ $compra->moneda === 'USD' ? '$' : 'S/' }} {{ number_format($detalle->total, 2) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="lg:col-span-1 space-y-6">
            <div class="bg-slate-800 rounded-2xl shadow-lg border border-slate-700 overflow-hidden">
                <div class="p-6">
                    <h2 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                        <i class="fas fa-calculator text-primary"></i> Resumen de Pago
                    </h2>
                    <div class="space-y-4">
                        <div class="flex justify-between text-slate-300 text-sm">
                            <span>Subtotal:</span> 
                            <span class="font-medium text-white">{{ $compra->moneda === 'USD' ? '$' : 'S/' }} {{ number_format($compra->subtotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-slate-300 text-sm">
                            <span>IGV (18%):</span> 
                            <span class="font-medium text-white">{{ $compra->moneda === 'USD' ? '$' : 'S/' }} {{ number_format($compra->igv, 2) }}</span>
                        </div>
                        <div class="pt-4 mt-4 border-t border-slate-600 flex justify-between items-center">
                            <span class="text-slate-200 font-bold">TOTAL:</span> 
                            <span class="text-2xl font-black text-primary">{{ $compra->moneda === 'USD' ? '$' : 'S/' }} {{ number_format($compra->total, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4 border-b border-slate-100 pb-2">Guías de Remisión Vinculadas</h3>
                @if($compra->guias && $compra->guias->count() > 0)
                    <ul class="space-y-3">
                        @foreach($compra->guias as $guiaVinculada)
                        <li class="flex items-center justify-between p-2 hover:bg-slate-50 rounded-lg transition-colors border border-transparent hover:border-slate-100">
                            <div>
                                <p class="text-sm font-bold text-slate-800">{{ $guiaVinculada->numero_guia }}</p>
                                <p class="text-xs text-slate-500 flex items-center gap-2">
                                    <span>{{ \Carbon\Carbon::parse($guiaVinculada->fecha_emision)->format('d/m/Y') }}</span>
                                    @php
                                        $guiaBadgeColor = [
                                            'RECIBIDA' => 'bg-green-100 text-green-700',
                                            'UBICADA' => 'bg-blue-100 text-blue-700',
                                            'ANULADA' => 'bg-red-100 text-red-700'
                                        ][$guiaVinculada->estado] ?? 'bg-slate-100 text-slate-700';
                                    @endphp
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold tracking-wider {{ $guiaBadgeColor }}">
                                        {{ $guiaVinculada->estado }}
                                    </span>
                                </p>
                            </div>
                            <a href="{{ route('guia_compras.show', $guiaVinculada->id_guia) }}" class="text-primary hover:text-primary-600 hover:underline text-xs font-bold px-3 py-1 bg-primary-50 rounded-lg transition-colors">
                                Ver
                            </a>
                        </li>
                        @endforeach
                    </ul>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-link text-slate-300 text-2xl mb-2"></i>
                        <p class="text-sm text-slate-500 italic">No hay guías vinculadas aún.</p>
                    </div>
                @endif
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-xs font-bold text-slate-400 uppercase mb-4">Auditoría</h3>
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center text-primary font-bold uppercase">
                        {{ substr($compra->creador->nombre_usuario ?? 'U', 0, 1) }}
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-800">{{ $compra->creador->nombre_usuario ?? 'N/A' }}</p>
                        <p class="text-xs text-slate-500">Registró esta operación</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        header, footer, nav, aside, button, .no-print {
            display: none !important;
        }
        .container {
            max-width: 100% !important;
            width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .shadow-sm, .shadow-lg {
            shadow: none !important;
            border: 1px solid #e2e8f0 !important;
        }
        .bg-slate-800 {
            background-color: white !important;
            color: black !important;
        }
        .text-primary, .text-white, .text-slate-300 {
            color: black !important;
        }
    }
</style>
@endsection