@extends('layouts.app')

@section('title', 'Transferencias de Producción')

@section('content')
<div class="container mx-auto pb-8 md:pb-10">
    <x-page-header title="Transferencias de Producción" subtitle="Historial y registro de transferencias y traspasos entre almacenes de planta">
        <x-slot:actions>
            <a href="{{ route('produccion.transferencias.create') }}" class="btn-primary">
                <i class="fas fa-plus"></i>
                <span class="hidden sm:inline ml-2">Nueva Transferencia</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- Filtros de Búsqueda -->
    <div class="bg-white rounded-xl shadow-xs border border-slate-200/80 p-4 md:p-5 mb-5">
        <form method="GET" action="{{ route('produccion.transferencias.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                    📅 Desde
                </label>
                <input type="date" name="fecha_desde" value="{{ $fecha_desde }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                    📅 Hasta
                </label>
                <input type="date" name="fecha_hasta" value="{{ $fecha_hasta }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                    🔍 Buscar
                </label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="N° Documento, observaciones..." class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs">
            </div>

            <div class="flex gap-2">
                <button type="submit" class="btn-primary flex-1 h-[38px] justify-center text-xs uppercase tracking-wide font-bold">
                    <i class="fas fa-filter mr-1.5"></i> Filtrar
                </button>
                <a href="{{ route('produccion.transferencias.index') }}" class="btn-secondary h-[38px] px-3 flex items-center justify-center text-xs" title="Limpiar Filtros">
                    <i class="fas fa-undo"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Tabla de Transferencias -->
    <div class="bg-white rounded-xl shadow-md border border-slate-200/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[760px]">
                <thead>
                    <tr class="bg-slate-800 text-white text-[11px] uppercase tracking-wider font-bold">
                        <th class="py-3 px-4 md:px-6 w-36">N° Documento</th>
                        <th class="py-3 px-4 md:px-6">Fecha</th>
                        <th class="py-3 px-4 md:px-6">Almacén Origen</th>
                        <th class="py-3 px-4 md:px-6">Almacén Destino</th>
                        <th class="py-3 px-4 md:px-6">Usuario</th>
                        <th class="py-3 px-4 md:px-6 text-center">Estado</th>
                        <th class="py-3 px-4 md:px-6 text-center w-28">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($transferencias as $t)
                        <tr class="hover:bg-slate-50/80 transition duration-150">
                            <td class="px-4 md:px-6 py-3 font-bold text-xs md:text-sm text-slate-900 whitespace-nowrap">
                                <a href="{{ route('produccion.transferencias.show', $t->id_transferencia) }}" class="text-blue-600 hover:text-blue-800 hover:underline">
                                    {{ $t->numero_transferencia }}
                                </a>
                            </td>
                            <td class="px-4 md:px-6 py-3 text-xs text-slate-700 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($t->fecha_transferencia)->format('d/m/Y') }}
                            </td>
                            <td class="px-4 md:px-6 py-3 text-xs font-semibold text-slate-800">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-slate-100 text-slate-800 border border-slate-200">
                                    <i class="fas fa-warehouse text-slate-400 text-[10px]"></i>
                                    {{ $t->almacenOrigen ? $t->almacenOrigen->descripcion : $t->codigo_almacen_origen }}
                                </span>
                            </td>
                            <td class="px-4 md:px-6 py-3 text-xs font-semibold text-slate-800">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-800 border border-emerald-100">
                                    <i class="fas fa-arrow-right text-emerald-500 text-[10px]"></i>
                                    {{ $t->almacenDestino ? $t->almacenDestino->descripcion : $t->codigo_almacen_destino }}
                                </span>
                            </td>
                            <td class="px-4 md:px-6 py-3 text-xs text-slate-600">
                                {{ $t->usuario ? $t->usuario->nombre_completo : 'Sistema' }}
                            </td>
                            <td class="px-4 md:px-6 py-3 text-center">
                                @if ($t->estado === 'COMPLETADO')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        <i class="fas fa-check-circle mr-1 text-[10px]"></i> COMPLETADO
                                    </span>
                                @elseif ($t->estado === 'ANULADO')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700 border border-red-200">
                                        <i class="fas fa-ban mr-1 text-[10px]"></i> ANULADO
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                        {{ $t->estado }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 md:px-6 py-3 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('produccion.transferencias.show', $t->id_transferencia) }}" class="inline-flex items-center justify-center w-8 h-8 text-blue-600 bg-blue-50 hover:bg-blue-600 hover:text-white rounded-lg transition-all shadow-2xs" title="Ver Comprobante">
                                        <i class="fas fa-eye text-xs"></i>
                                    </a>
                                    @if ($t->estado === 'COMPLETADO')
                                        <form action="{{ route('produccion.transferencias.anular', $t->id_transferencia) }}" method="POST" class="inline-block form-anular">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center justify-center w-8 h-8 text-red-600 bg-red-50 hover:bg-red-600 hover:text-white rounded-lg transition-all shadow-2xs" title="Anular Transferencia">
                                                <i class="fas fa-trash-alt text-xs"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400 bg-slate-50/50">
                                <div class="h-12 w-12 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3 text-slate-400">
                                    <i class="fas fa-dolly-flatbed text-xl"></i>
                                </div>
                                <p class="text-sm font-semibold text-slate-600">No se encontraron transferencias de producción registradas.</p>
                                <p class="text-xs text-slate-400 mt-1">Haga clic en "+ Nueva Transferencia" para registrar el primer traslado entre plantas.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($transferencias->hasPages())
            <div class="px-4 py-3 border-t border-slate-100 bg-slate-50/50">
                {{ $transferencias->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.form-anular').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: '¿Anular Transferencia de Producción?',
                    text: 'Se revertirá el inventario de destino devolviendo las cantidades a los lotes originales en el almacén de origen.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Sí, anular transferencia',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    });
</script>
@endpush
@endsection
