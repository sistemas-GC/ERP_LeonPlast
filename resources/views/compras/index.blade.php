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

    <div class="bg-white p-3 md:p-4 rounded-xl shadow-md mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <form method="GET" action="{{ route('compras.index') }}" class="w-full flex flex-col md:flex-row gap-4">
            
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
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por documento o proveedor..." 
                        class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary text-sm outline-none">
                </div>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-5 py-2 rounded-lg text-sm font-bold transition-colors">
                    <i class="fas fa-filter mr-1"></i> Filtrar
                </button>
                <a href="{{ route('compras.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-lg text-sm font-bold transition-colors border border-slate-300">
                    Limpiar
                </a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left whitespace-nowrap">
                <thead>
                    <tr class="bg-slate-900 text-slate-200 text-xs uppercase tracking-wider font-bold">
                        <th class="px-4 md:px-6 py-3.5 border-r border-slate-800">N° Documento / Fecha</th>
                        <th class="px-4 md:px-6 py-3.5 border-r border-slate-800">Proveedor</th>
                        <th class="px-4 md:px-6 py-3.5 border-r border-slate-800 text-right">Total</th>
                        <th class="px-4 md:px-6 py-3.5 border-r border-slate-800">Creado Por</th>
                        <th class="px-4 md:px-6 py-3.5 border-r border-slate-800 text-center">Estado</th>
                        <th class="px-4 md:px-6 py-3.5 border-r border-slate-800 last:border-r-0 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs md:text-sm">
                    @forelse ($compras as $compra)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-4 md:px-6 py-3 md:py-4">
                                <div class="font-bold text-slate-900 uppercase">
                                    {{ $compra->tipo_documento }} {{ $compra->serie_documento }}-{{ $compra->numero_documento }}
                                </div>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-xs text-slate-500">
                                        {{ \Carbon\Carbon::parse($compra->fecha_compra)->format('d/m/Y') }}
                                    </span>
                                    @if(($compra->condicion_pago ?? 'CONTADO') === 'CREDITO')
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800" title="Vencimiento: {{ $compra->fecha_vencimiento ? \Carbon\Carbon::parse($compra->fecha_vencimiento)->format('d/m/Y') : '-' }}">
                                            CRÉDITO
                                        </span>
                                    @else
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-600">
                                            CONTADO
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 md:px-6 py-3 md:py-4">
                                <div class="text-slate-800 font-medium">
                                    {{ $compra->datosProveedor->razon_social ?? 'Proveedor No Encontrado' }}
                                </div>
                                <div class="text-xs text-slate-500">RUC: {{ $compra->ruc_proveedor ?? '-' }}</div>
                            </td>
                            <td class="px-4 md:px-6 py-3 md:py-4 text-right font-bold text-primary">
                                {{ $compra->moneda === 'USD' ? '$' : 'S/' }} {{ number_format($compra->total, 2) }}
                            </td>
                            <td class="px-4 md:px-6 py-3 md:py-4 text-slate-600">
                                {{ $compra->creador->nombre_usuario ?? 'Sistema' }}
                            </td>
                            <td class="px-4 md:px-6 py-3 md:py-4 text-center">
                                @switch($compra->estado)
                                    @case('PENDIENTE')
                                        <x-badge color="yellow">PENDIENTE</x-badge>
                                        @break
                                    @case('RECIBIDA')
                                        <x-badge color="green">RECIBIDA</x-badge>
                                        @break
                                    @case('CANCELADA')
                                        <x-badge color="red">ANULADA</x-badge>
                                        @break
                                    @default
                                        <x-badge color="slate">{{ $compra->estado }}</x-badge>
                                @endswitch
                            </td>
                            <td class="px-4 md:px-6 py-3 md:py-4 text-center">
                                <div class="flex items-center justify-center gap-3">
                                    
                                    {{-- Botón VER: Siempre visible --}}
                                    <a href="{{ route('compras.show', $compra->id_compra) }}" class="text-slate-400 hover:text-primary transition-colors" title="Ver Detalle">
                                        <i class="fas fa-eye text-lg"></i>
                                    </a>

                                    {{-- Botones EDITAR y ANULAR: Solo visibles si está PENDIENTE --}}
                                    @if($compra->estado === 'PENDIENTE')
                                        <a href="{{ route('compras.edit', $compra->id_compra) }}" class="text-slate-400 hover:text-amber-600 transition-colors" title="Editar">
                                            <i class="fas fa-edit text-lg"></i>
                                        </a>
                                        
                                        <button type="button" data-id="{{ $compra->id_compra }}" data-documento="{{ $compra->numero_documento }}" class="btn-anular text-slate-400 hover:text-red-600 transition-colors" title="Anular">
                                            <i class="fas fa-ban text-lg"></i>
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
                            <td colspan="6" class="px-6 py-10 text-center text-slate-500 italic">No se encontraron compras registradas.</td>
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
        const numero = btn.getAttribute('data-documento') || 'S/N';

        if (confirm('¿Está seguro de anular el documento ' + numero + '? Escriba ANULAR para confirmar.')) {
            const palabra = prompt('Escriba ANULAR para desactivar el registro:');
            if (palabra === 'ANULAR') {
                document.getElementById('form-anular-' + id).submit();
            }
        }
    });
</script>
@endsection