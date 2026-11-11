@extends('layouts.app')

@section('title', 'Procesos de Producción')

@section('content')
<div class="container mx-auto pb-8 md:pb-10">
    <div class="flex justify-between items-center mb-6 gap-4">
        <div>
            <nav class="flex items-center text-sm text-slate-500 mb-2">
                <a href="{{ route('produccion.ordenes.index') }}" 
                   onclick="if (window.parent && window.parent.cambiarPestana) { event.preventDefault(); window.parent.cambiarPestana('lista'); }" 
                   class="hover:text-primary font-medium transition-colors inline-flex items-center gap-1 cursor-pointer">
                    <i class="fas fa-arrow-left text-xs"></i>
                    <span>Volver a Órdenes</span>
                </a>
                <span class="mx-2">›</span>
                <span class="text-slate-700 font-semibold">Orden #{{ $orden->codigo_op }}</span>
            </nav>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-800 flex items-center gap-3">
                Procesos de Producción
                <span class="text-sm bg-slate-100 px-3 py-1 rounded-full text-slate-700 font-medium border border-slate-200">
                    {{ $orden->codigo_op }}
                </span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-600 mt-1">
                Producto: <span class="font-medium text-slate-700">{{ $orden->descripcion_producto_proceso ?? 'N/A' }}</span> | 
                Fecha: <span class="font-medium text-slate-700">{{ \Carbon\Carbon::parse($orden->fecha)->format('d/m/Y') }}</span>
            </p>
        </div>
        <div class="flex items-center gap-2">
            @if($orden->estado !== 'COMPLETADO' && Auth::user()->hasAccess('produccion.ordenes.finalizar'))
                <form action="{{ route('produccion.ordenes.finalizar', $orden->idop) }}" method="POST" onsubmit="return confirm('¿Está seguro de que desea CERRAR DEFINITIVAMENTE esta Orden de Producción? Ya no podrá agregar nuevos procesos o ingresos.');">
                    @csrf
                    <button type="submit" class="shrink-0 flex items-center justify-center bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-3 sm:px-4 rounded-lg shadow transition">
                        <i class="fas fa-check-circle"></i>
                        <span class="hidden sm:inline ml-2">Cerrar OP</span>
                    </button>
                </form>
            @endif
            @if($orden->estado !== 'COMPLETADO' && Auth::user()->hasAccess('ordenes.procesos.create'))
                <a href="{{ route('ordenes.procesos.create', $orden->idop) }}" class="shrink-0 flex items-center justify-center bg-primary hover:bg-primary-dark text-white font-semibold py-2 px-3 sm:px-4 rounded-lg shadow transition">
                    <i class="fas fa-plus"></i>
                    <span class="hidden sm:inline ml-2">Agregar Proceso</span>
                </a>
            @endif
        </div>
    </div>

    {{-- Tarjeta Principal --}}
    {{-- <div class="bg-white p-3 md:p-4 rounded-xl shadow-md mb-6 flex justify-between items-center bg-linear-to-r from-blue-500 to-purple-500">
        <h2 class="text-lg font-medium text-white">Procesos Activos</h2>
        <span class="bg-white bg-opacity-20 text-white px-3 py-1 rounded-full text-xs font-semibold">
            {{ count($procesos) }} proceso(s)
        </span>
    </div> --}}

    <div id="table-container" class="transition-opacity duration-300">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            @if(count($procesos) > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs sm:text-sm">
                    <thead>
                        <tr class="bg-slate-900 text-slate-200 text-xs uppercase tracking-wider font-bold">
                            <th class="p-4 border-r border-slate-800 text-center">Secuencia</th>
                            <th class="p-4 border-r border-slate-800 text-center">Proceso</th>
                            <th class="p-4 border-r border-slate-800 text-center">Estado de Avance</th>
                            <th class="p-4 border-r border-slate-800 text-center">Materiales</th>
                            <th class="p-4 border-r border-slate-800 last:border-r-0 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                    @foreach($procesos as $p)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-badge color="slate">Prioridad: {{ $p->prioridad ?? 'Normal' }}</x-badge>
                            </td>
                            <td class="px-6 py-4">
                                <strong class="text-slate-900 font-medium">{{ $p->proceso_desc }}</strong>
                                @if($p->observaciones)
                                    <div class="text-xs text-slate-500 mt-1">
                                        <i class="fas fa-info-circle mr-1"></i> {{ $p->observaciones }}
                                    </div>
                                @endif
                                @if($p->nombres_componentes)
                                    <div class="text-xs text-indigo-600 mt-1 font-semibold truncate max-w-sm" title="{{ $p->nombres_componentes }}">
                                        <i class="fas fa-layer-group mr-1"></i> {{ $p->nombres_componentes }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $estado = $p->estado_avance ?? 'PENDIENTE';
                                @endphp
                                @if($estado == 'COMPLETADO')
                                    <x-badge color="green">✅ Completado</x-badge>
                                @elseif($estado == 'EN_PROCESO')
                                    <x-badge color="blue">🔄 En Proceso</x-badge>
                                @elseif($estado == 'CANCELADO')
                                    <x-badge color="red">❌ Cancelado</x-badge>
                                @else
                                    <x-badge color="yellow">⏳ Pendiente</x-badge>
                                @endif
                            </td>
                            <td class="p-4 border-r border-slate-200 text-center">
                                <x-badge color="indigo">📦 {{ $p->total_componentes }} items</x-badge>
                            </td>
                            <td class="p-4 border-r border-slate-200 text-center">
                                @if($orden->estado !== 'COMPLETADO')
                                <div class="flex justify-center gap-2">
                                    @if(Auth::user()->hasAccess('ordenes.procesos.ejecutar'))
                                    <a href="{{ route('ordenes.procesos.ejecutar', [$orden->idop, $p->id]) }}" class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-primary hover:bg-primary-dark shadow-sm transition-colors" title="Ejecutar proceso">
                                        <i class="fas fa-play mr-1"></i> Ejecutar
                                    </a>
                                    @endif
                                    @if(Auth::user()->hasAccess('ordenes.procesos.finalizar'))
                                    <button onclick="delProc({{ $p->id }}, '{{ addslashes($p->proceso_desc) }}')" class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-red-500 hover:bg-red-600 shadow-sm" title="Anular proceso">
                                        <i class="fas fa-trash mr-1"></i> Anular
                                    </button>
                                    @endif
                                </div>
                                @else
                                <span class="text-xs text-slate-400"><i class="fas fa-lock mr-1"></i>Bloqueado</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="p-8 text-center text-slate-500">
            <i class="fas fa-folder-open text-4xl text-slate-300 mb-3 block"></i>
            No hay procesos registrados para esta orden.
        </div>
        @endif
    </div>
    </div>
</div>



{{-- Formulario oculto para eliminar --}}
<form id="formDel" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>

<script>
    function delProc(id, name) {
        if(confirm("¿Está seguro de ANULAR el proceso '" + name + "'?\n\n⚠️ Esta acción:\n• Revertirá el stock de materias primas consumidas\n• Anulará los ingresos de productos en proceso\n• Validará que el stock no haya sido utilizado posteriormente\n\nEsta acción no se puede deshacer.")){
            const form = document.getElementById('formDel');
            form.action = `/produccion/procesos/${id}`;
            form.submit();
        }
    }


</script>
@endsection
