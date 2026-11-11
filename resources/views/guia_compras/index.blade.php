@extends('layouts.app')
@section('title', 'Guías de Remisión de Compra')

@section('content')
<div class="container mx-auto pb-8 md:pb-10">
    <x-page-header title="Guías de Remisión de Compra" subtitle="Recepción física de materias primas e insumos en almacén de tránsito ALM04">
        <x-slot:actions>
            <a href="{{ route('guia_compras.create') }}" class="btn-primary">
                <i class="fas fa-plus"></i>
                <span class="hidden sm:inline ml-2">Nueva Guía</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    {{-- Filtros --}}
    <div class="bg-white p-4 md:p-5 rounded-xl shadow-md border border-slate-200/80 mb-6">
        <form method="GET" action="{{ route('guia_compras.index') }}" class="flex flex-col md:flex-row gap-4 items-end">
            
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
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por Guía N° o Proveedor..." 
                        class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm text-slate-800 outline-none transition bg-white shadow-xs">
                </div>
            </div>

            <div class="flex items-center gap-2 w-full md:w-auto shrink-0">
                <button type="submit" class="btn-primary h-10 px-5">
                    <i class="fas fa-filter mr-1.5"></i> Filtrar
                </button>
                <a href="{{ route('guia_compras.index') }}" class="inline-flex items-center justify-center h-10 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-sm font-semibold transition border border-slate-300 shadow-xs" title="Limpiar filtros">
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
                        <th class="py-3 px-4 md:px-6 border-r border-slate-700/60 w-32">Fecha Emisión</th>
                        <th class="py-3 px-4 md:px-6 border-r border-slate-700/60">Guía N°</th>
                        <th class="py-3 px-4 md:px-6 border-r border-slate-700/60">Proveedor</th>
                        <th class="py-3 px-4 md:px-6 border-r border-slate-700/60 text-center w-36">Cant. Total</th>
                        <th class="py-3 px-4 md:px-6 border-r border-slate-700/60 text-center w-32">Estado</th>
                        <th class="py-3 px-4 md:px-6 text-center w-28">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs md:text-sm">
                    @forelse($guias as $guia)
                    <tr class="hover:bg-slate-50/80 transition duration-150">
                        <td class="px-4 md:px-6 py-3 text-slate-500 font-medium">
                            <i class="far fa-calendar-alt text-slate-400 mr-1 text-[11px]"></i>
                            {{ \Carbon\Carbon::parse($guia->fecha_emision)->format('d/m/Y') }}
                        </td>
                        <td class="px-4 md:px-6 py-3 font-bold text-slate-900 uppercase">
                            {{ $guia->numero_guia }}
                        </td>
                        <td class="px-4 md:px-6 py-3">
                            <div class="font-semibold text-slate-800">{{ Str::limit($guia->proveedor, 35) }}</div>
                            <div class="text-xs text-slate-400 font-mono">RUC: {{ $guia->ruc_proveedor ?? '-' }}</div>
                        </td>
                        <td class="px-4 md:px-6 py-3 text-center font-black text-slate-900 text-sm">
                            {{ number_format($guia->detalles_sum_cantidad, 2) }}
                        </td>
                        <td class="px-4 md:px-6 py-3 text-center">
                            @switch($guia->estado)
                                @case('RECIBIDA')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span> RECIBIDA
                                    </span>
                                    @break
                                @case('FACTURADA')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-800 border border-blue-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-1.5"></span> FACTURADA
                                    </span>
                                    @break
                                @case('ANULADA')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-50 text-red-800 border border-red-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 mr-1.5"></span> ANULADA
                                    </span>
                                    @break
                                @default
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ $guia->estado }}
                                    </span>
                            @endswitch
                        </td>
                        <td class="px-4 md:px-6 py-3 text-center">
                            <a href="{{ route('guia_compras.show', $guia->id_guia) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-blue-600 bg-blue-50 hover:bg-blue-600 hover:text-white transition shadow-2xs" title="Ver Detalle de Guía">
                                <i class="fas fa-eye text-xs"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                            <div class="flex flex-col items-center justify-center">
                                <i class="fas fa-truck-loading text-slate-300 text-4xl mb-3"></i>
                                <p class="font-medium text-slate-600">No hay guías de remisión registradas</p>
                                <p class="text-xs text-slate-400 mt-0.5">Registre una nueva guía de recepción para ingresar stock.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($guias->hasPages())
        <div class="px-4 md:px-6 py-3 border-t bg-slate-50/50">
            {{ $guias->links() }}
        </div>
        @endif
    </div>
</div>
@endsection