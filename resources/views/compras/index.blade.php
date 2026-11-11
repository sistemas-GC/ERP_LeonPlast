@extends('layouts.app')
@section('title', 'Registro de Compras')

@section('content')
<div class="container mx-auto pb-8 md:pb-10">
    <x-page-header title="Registro de Compras" subtitle="Gestión administrativa de adquisiciones">
        <x-slot:actions>
            <a href="{{ route('compras.create') }}" class="btn-primary">
                <i class="fas fa-plus"></i>
                <span class="hidden sm:inline ml-2">Nueva Compra</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="bg-white p-4 md:p-5 rounded-xl shadow-md border border-slate-200/80 mb-6">
        <form method="GET" action="{{ route('compras.index') }}" class="flex flex-col md:flex-row gap-4 items-end">
            
            <div class="flex items-center gap-3 w-full md:w-auto">
                <div class="flex-1 md:w-36">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                        <i class="far fa-calendar-alt text-slate-400 mr-1"></i> Desde
                    </label>
                    <input type="date" name="fecha_desde" value="{{ $fecha_desde }}" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs" max="{{ date('Y-m-d') }}">
                </div>
                <div class="flex-1 md:w-36">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                        <i class="far fa-calendar-alt text-slate-400 mr-1"></i> Hasta
                    </label>
                    <input type="date" name="fecha_hasta" value="{{ $fecha_hasta }}" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs" max="{{ date('Y-m-d') }}">
                </div>
            </div>

            <div class="flex-1 w-full">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                    <i class="fas fa-search text-slate-400 mr-1"></i> Búsqueda Rápida
                </label>
                <div class="relative w-full">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <i class="fas fa-search text-slate-400 text-sm"></i>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por serie, número o proveedor..." 
                        class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm text-slate-800 outline-none transition bg-white shadow-xs">
                </div>
            </div>

            <div class="flex items-center gap-2 w-full md:w-auto shrink-0">
                <button type="submit" class="btn-primary h-10 px-5">
                    <i class="fas fa-filter mr-1.5"></i> Filtrar
                </button>
                <a href="{{ route('compras.index') }}" class="inline-flex items-center justify-center h-10 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-sm font-semibold transition border border-slate-300 shadow-xs" title="Limpiar filtros">
                    <i class="fas fa-undo-alt mr-1.5 text-xs text-slate-500"></i> Limpiar
                </a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-md border border-slate-200/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left whitespace-nowrap border-collapse min-w-[750px]">
                <thead>
                    <tr class="bg-slate-800 text-white text-[11px] uppercase tracking-wider font-bold">
                        <th class="py-3 px-4 md:px-6 border-r border-slate-700/60">N° Documento / Fecha</th>
                        <th class="py-3 px-4 md:px-6 border-r border-slate-700/60">Proveedor</th>
                        <th class="py-3 px-4 md:px-6 border-r border-slate-700/60 text-right">Total</th>
                        <th class="py-3 px-4 md:px-6 border-r border-slate-700/60">Registrado Por</th>
                        <th class="py-3 px-4 md:px-6 border-r border-slate-700/60 text-center">Estado</th>
                        <th class="py-3 px-4 md:px-6 text-center w-36">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs md:text-sm">
                    @forelse ($compras as $compra)
                        <tr class="hover:bg-slate-50/80 transition duration-150">
                            <td class="px-4 md:px-6 py-3">
                                <div class="font-bold text-slate-900 uppercase">
                                    {{ $compra->tipo_documento }} {{ $compra->serie_documento }}-{{ $compra->numero_documento }}
                                </div>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-xs text-slate-500">
                                        <i class="far fa-calendar-alt text-slate-400 mr-1 text-[11px]"></i>
                                        {{ \Carbon\Carbon::parse($compra->fecha_compra)->format('d/m/Y') }}
                                    </span>
                                    @if(($compra->condicion_pago ?? 'CONTADO') === 'CREDITO')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200" title="Vencimiento: {{ $compra->fecha_vencimiento ? \Carbon\Carbon::parse($compra->fecha_vencimiento)->format('d/m/Y') : '-' }}">
                                            <i class="fas fa-clock text-[9px] mr-1 text-amber-600"></i> CRÉDITO
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                            CONTADO
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 md:px-6 py-3">
                                <div class="text-slate-800 font-semibold">
                                    {{ $compra->datosProveedor->razon_social ?? 'Proveedor No Encontrado' }}
                                </div>
                                <div class="text-xs text-slate-500 font-mono mt-0.5">RUC: {{ $compra->ruc_proveedor ?? '-' }}</div>
                            </td>
                            <td class="px-4 md:px-6 py-3 text-right font-black text-slate-900 text-sm">
                                <span class="text-xs font-semibold text-slate-500 mr-0.5">{{ $compra->moneda === 'USD' ? '$' : 'S/' }}</span>{{ number_format($compra->total, 2) }}
                            </td>
                            <td class="px-4 md:px-6 py-3 text-slate-600 text-xs">
                                <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md bg-slate-50 border border-slate-200 font-medium">
                                    <i class="fas fa-user-circle text-slate-400"></i>
                                    {{ $compra->creador->nombre_usuario ?? 'Sistema' }}
                                </span>
                            </td>
                            <td class="px-4 md:px-6 py-3 text-center">
                                @switch($compra->estado)
                                    @case('PENDIENTE')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5 animate-pulse"></span> PENDIENTE
                                        </span>
                                        @break
                                    @case('RECIBIDA')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span> RECIBIDA
                                        </span>
                                        @break
                                    @case('CANCELADA')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-50 text-red-800 border border-red-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 mr-1.5"></span> ANULADA
                                        </span>
                                        @break
                                    @default
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                            {{ $compra->estado }}
                                        </span>
                                @endswitch
                            </td>
                            <td class="px-4 md:px-6 py-3 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    
                                    {{-- Botón VER: Estándar 32x32px --}}
                                    <a href="{{ route('compras.show', $compra->id_compra) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-blue-600 bg-blue-50 hover:bg-blue-600 hover:text-white transition shadow-2xs" title="Ver Detalle de Compra">
                                        <i class="fas fa-eye text-xs"></i>
                                    </a>

                                    {{-- Botones EDITAR y ANULAR: Solo si está PENDIENTE --}}
                                    @if($compra->estado === 'PENDIENTE')
                                        <a href="{{ route('compras.edit', $compra->id_compra) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-emerald-600 bg-emerald-50 hover:bg-emerald-600 hover:text-white transition shadow-2xs" title="Editar Compra">
                                            <i class="fas fa-pencil-alt text-xs"></i>
                                        </a>
                                        
                                        <button type="button" data-id="{{ $compra->id_compra }}" data-documento="{{ $compra->tipo_documento }} {{ $compra->serie_documento }}-{{ $compra->numero_documento }}" class="btn-anular inline-flex items-center justify-center w-8 h-8 rounded-lg text-red-600 bg-red-50 hover:bg-red-600 hover:text-white transition shadow-2xs" title="Anular Compra">
                                            <i class="fas fa-ban text-xs"></i>
                                        </button>
                                        
                                        <form id="form-anular-{{ $compra->id_compra }}" action="{{ route('compras.anular', $compra->id_compra) }}" method="POST" class="hidden">
                                            @csrf
                                            <input type="hidden" name="confirmacion" value="ANULAR">
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="fas fa-receipt text-slate-300 text-4xl mb-3"></i>
                                    <p class="font-medium text-slate-600">No se encontraron compras registradas</p>
                                    <p class="text-xs text-slate-400 mt-0.5">Ajuste los filtros o registre una nueva compra para comenzar.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 md:px-6 py-3 border-t bg-slate-50/50">
            {{ $compras->links() }}
        </div>
    </div>
</div>

<script>
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-anular');
        if (!btn) return;

        const id = btn.getAttribute('data-id');
        const documento = btn.getAttribute('data-documento') || 'Documento';

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '¿Anular Comprobante de Compra?',
                html: `<div class="text-left text-sm">
                    <p class="text-slate-600 mb-2">Está a punto de anular el comprobante <strong>${documento}</strong>.</p>
                    <p class="text-amber-700 bg-amber-50 p-2.5 rounded-lg border border-amber-200 text-xs mb-3">
                        <i class="fas fa-exclamation-triangle mr-1"></i> Esta acción revertirá los saldos y estados asociados.
                    </p>
                    <p class="text-xs text-slate-500 font-semibold mb-1">Para confirmar, escriba <strong class="text-red-600">ANULAR</strong>:</p>
                </div>`,
                input: 'text',
                inputPlaceholder: 'ANULAR',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="fas fa-trash-alt mr-1"></i> Sí, Anular Compra',
                cancelButtonText: 'Cancelar',
                inputValidator: (value) => {
                    if (value !== 'ANULAR') {
                        return 'Debe escribir exactamente la palabra ANULAR para proceder';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById('form-anular-' + id);
                    if (form) form.submit();
                }
            });
        } else {
            if (confirm('¿Está seguro de anular el documento ' + documento + '?')) {
                const form = document.getElementById('form-anular-' + id);
                if (form) form.submit();
            }
        }
    });
</script>
@endsection