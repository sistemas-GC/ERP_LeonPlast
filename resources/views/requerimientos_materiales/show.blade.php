@extends('layouts.app')
@section('title', 'Requerimiento ' . $requerimiento->codigo)

@section('content')
<div class="container mx-auto px-4 py-6 max-w-7xl">

    <x-page-header title="Requerimiento {{ $requerimiento->codigo }}" subtitle="Creado el {{ \Carbon\Carbon::parse($requerimiento->fecha_creacion)->format('d/m/Y H:i') }}">
        <x-slot:actions>
            <a href="{{ route('requerimientos_materiales.index') }}" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver al Listado
            </a>
            @if($requerimiento->estado === 'BORRADOR')
                <a href="{{ route('requerimientos_materiales.edit', $requerimiento->id_requerimiento) }}" class="btn-primary">
                    <i class="fas fa-edit"></i> Editar
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-8 space-y-6">

            <x-card>
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                    <h2 class="text-base font-bold text-slate-800">
                        <i class="fas fa-info-circle text-primary"></i> Información General
                    </h2>
                    @php
                        $colors = ['BORRADOR' => 'slate', 'PENDIENTE' => 'yellow', 'APROBADO' => 'green', 'RECHAZADO' => 'red', 'ATENDIDO_PARCIAL' => 'blue', 'ATENDIDO_TOTAL' => 'emerald', 'ANULADO' => 'red'];
                    @endphp
                    <x-badge color="{{ $colors[$requerimiento->estado] ?? 'slate' }}">{{ $requerimiento->estado }}</x-badge>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="text-xs text-slate-500 font-semibold uppercase">Código</span>
                        <p class="font-bold text-slate-800">{{ $requerimiento->codigo }}</p>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500 font-semibold uppercase">Creado Por</span>
                        <p class="text-slate-700">{{ $requerimiento->creador->nombre_usuario ?? 'Sistema' }}</p>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500 font-semibold uppercase">Fecha</span>
                        <p class="text-slate-700">{{ \Carbon\Carbon::parse($requerimiento->fecha_requerimiento ?? $requerimiento->fecha_creacion)->format('d/m/Y') }}</p>
                    </div>
                    @if($requerimiento->ordenProduccion)
                    <div>
                        <span class="text-xs text-slate-500 font-semibold uppercase">Orden Producción</span>
                        <p class="text-slate-700">{{ $requerimiento->ordenProduccion->codigo_op ?? 'OP#' . $requerimiento->idop }}</p>
                    </div>
                    @endif
                    <div>
                        <span class="text-xs text-slate-500 font-semibold uppercase">Almacén Destino</span>
                        <p class="text-slate-700">{{ $requerimiento->almacen->descripcion ?? $requerimiento->codigo_almacen ?? '-' }}</p>
                    </div>
                    @if($requerimiento->aprobador)
                    <div>
                        <span class="text-xs text-slate-500 font-semibold uppercase">Aprobado Por</span>
                        <p class="text-slate-700">{{ $requerimiento->aprobador->nombre_usuario }}</p>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500 font-semibold uppercase">Fecha Aprobación</span>
                        <p class="text-slate-700">{{ $requerimiento->fecha_aprobacion ? \Carbon\Carbon::parse($requerimiento->fecha_aprobacion)->format('d/m/Y H:i') : '-' }}</p>
                    </div>
                    @endif
                    @if($requerimiento->motivo)
                    <div class="md:col-span-3">
                        <span class="text-xs text-slate-500 font-semibold uppercase">Motivo</span>
                        <p class="text-slate-700">{{ $requerimiento->motivo }}</p>
                    </div>
                    @endif
                    @if($requerimiento->observaciones)
                    <div class="md:col-span-3">
                        <span class="text-xs text-slate-500 font-semibold uppercase">Observaciones</span>
                        <p class="text-slate-700">{{ $requerimiento->observaciones }}</p>
                    </div>
                    @endif
                </div>
            </x-card>

            <x-card>
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h2 class="text-base font-bold text-slate-800">
                        <i class="fas fa-boxes text-primary"></i> Productos Solicitados
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-900 text-[11px] uppercase text-slate-200 tracking-wider font-bold">
                                <th class="p-3 font-semibold">Producto</th>
                                <th class="p-3 font-semibold">Origen</th>
                                <th class="p-3 font-semibold">Destino</th>
                                <th class="p-3 font-semibold text-right">Solicitado</th>
                                <th class="p-3 font-semibold text-right">Atendido</th>
                                <th class="p-3 font-semibold text-center">Progreso</th>
                                <th class="p-3 font-semibold text-center">Lote(s)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($requerimiento->detalles as $det)
                            @php
                                $pct = $det->cantidad_solicitada > 0 ? min(100, round(($det->cantidad_atendida / $det->cantidad_solicitada) * 100)) : 0;
                                $completa = $det->cantidad_atendida >= $det->cantidad_solicitada;
                                $lotesDespachados = $requerimiento->despachosLotes ? $requerimiento->despachosLotes->where('id_detalle', $det->id_detalle) : collect();
                            @endphp
                            <tr class="{{ $completa ? 'bg-green-50/30' : '' }}">
                                <td class="p-3 font-semibold text-slate-800">{{ $det->producto->descripcion ?? $det->codigo_producto }}</td>
                                <td class="p-3 text-slate-600">{{ $det->almacenOrigen->descripcion ?? $det->codigo_almacen_origen }}</td>
                                <td class="p-3 text-slate-600">{{ $det->almacenDestino->descripcion ?? $det->codigo_almacen_destino }}</td>
                                <td class="p-3 text-right font-semibold">{{ number_format($det->cantidad_solicitada, 2) }}</td>
                                <td class="p-3 text-right {{ $completa ? 'text-green-600 font-bold' : 'text-slate-600' }}">{{ number_format($det->cantidad_atendida, 2) }}</td>
                                <td class="p-3 text-center">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 bg-slate-200 rounded-full h-2">
                                            <div class="h-2 rounded-full {{ $pct == 100 ? 'bg-green-500' : 'bg-blue-500' }}" style="width: {{ $pct }}%"></div>
                                        </div>
                                        <span class="text-[10px] font-semibold {{ $pct == 100 ? 'text-green-600' : 'text-slate-500' }}">{{ $pct }}%</span>
                                    </div>
                                </td>
                                <td class="p-3 text-center">
                                    @if($lotesDespachados->count() > 0)
                                        <div class="flex flex-col gap-1 items-center">
                                        @foreach($lotesDespachados as $ld)
                                            <div class="text-[10px] font-mono bg-emerald-50 text-emerald-700 border border-emerald-200 rounded px-1.5 py-0.5 inline-block whitespace-nowrap" title="Despachado: {{ number_format($ld->cantidad, 2) }} kg">{{ $ld->lote }}</div>
                                        @endforeach
                                        </div>
                                    @else
                                        @if($det->lote_preferente)
                                            <div class="text-[10px] text-slate-400" title="Lote Preferente Solicitado">Pref: {{ $det->lote_preferente }}</div>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>

            @if($requerimiento->despachosLotes->count() > 0)
            <x-card>
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h2 class="text-base font-bold text-slate-800">
                        <i class="fas fa-history text-primary"></i> Historial de Despachos
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-900 text-[11px] uppercase text-slate-200 tracking-wider font-bold">
                                <th class="p-3 font-semibold">Lote</th>
                                <th class="p-3 font-semibold text-right">Cantidad</th>
                                <th class="p-3 font-semibold">Fecha Despacho</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($requerimiento->despachosLotes as $dl)
                            <tr>
                                <td class="p-3 font-mono text-slate-800">{{ $dl->lote }}</td>
                                <td class="p-3 text-right font-semibold text-green-600">{{ number_format($dl->cantidad, 2) }}</td>
                                <td class="p-3 text-slate-600">{{ \Carbon\Carbon::parse($dl->fecha_despacho)->format('d/m/Y H:i') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>
            @endif
        </div>

        <div class="lg:col-span-4 space-y-4">
            <div class="bg-slate-800 rounded-2xl shadow-lg border border-slate-700 overflow-hidden">
                <div class="p-6">
                    <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-tasks text-primary"></i> Acciones
                    </h3>
                    <div class="space-y-3">

                        @if($requerimiento->estado === 'BORRADOR')
                            <form action="{{ route('requerimientos_materiales.enviar', $requerimiento->id_requerimiento) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl transition flex items-center justify-center gap-2" onclick="return confirm('¿Enviar este requerimiento a aprobación?')">
                                    <i class="fas fa-paper-plane"></i> Enviar a Aprobación
                                </button>
                            </form>
                        @endif

                        @if($requerimiento->estado === 'PENDIENTE' && Auth::user()->hasAccess('requerimientos_materiales.aprobar'))
                            <div class="bg-slate-700 p-3 rounded-xl mb-3 border border-slate-600">
                                <form action="{{ route('requerimientos_materiales.aprobar', $requerimiento->id_requerimiento) }}" method="POST" onsubmit="return confirm('¿Está seguro de aprobar este requerimiento con la fecha indicada?')">
                                    @csrf
                                    <label class="block text-[10px] text-slate-300 mb-1 font-semibold uppercase tracking-wider">Fecha Aprobación</label>
                                    <input type="date" name="fecha_aprobacion" value="{{ now()->format('Y-m-d') }}" class="w-full p-2 mb-3 rounded-lg text-sm bg-slate-50 text-slate-900 border border-slate-300 focus:ring-green-500 focus:border-green-500 shadow-inner" required>
                                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2.5 px-4 rounded-lg transition flex items-center justify-center gap-2">
                                        <i class="fas fa-check"></i> Aprobar
                                    </button>
                                </form>
                            </div>
                            <button type="button" onclick="mostrarFormRechazar()" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-xl transition flex items-center justify-center gap-2">
                                <i class="fas fa-times"></i> Rechazar
                            </button>
                            <div id="formRechazar" class="hidden mt-3 p-3 bg-slate-700 rounded-lg">
                                <form action="{{ route('requerimientos_materiales.rechazar', $requerimiento->id_requerimiento) }}" method="POST">
                                    @csrf
                                    <textarea name="observaciones" class="w-full p-2 rounded text-sm text-slate-800" rows="2" placeholder="Motivo del rechazo (obligatorio)..." required></textarea>
                                    <button type="submit" class="w-full mt-2 bg-red-500 hover:bg-red-600 text-white font-bold py-2 rounded-lg text-sm transition">
                                        Confirmar Rechazo
                                    </button>
                                </form>
                            </div>
                        @endif

                        @if(!in_array($requerimiento->estado, ['ATENDIDO_TOTAL', 'ANULADO']) && Auth::user()->rol === 'Administrador')
                            <form action="{{ route('requerimientos_materiales.anular', $requerimiento->id_requerimiento) }}" method="POST" onsubmit="return confirm('¿Está seguro de anular este requerimiento?')">
                                @csrf
                                <button type="submit" class="w-full bg-slate-600 hover:bg-slate-700 text-white font-bold py-3 px-4 rounded-xl transition flex items-center justify-center gap-2">
                                    <i class="fas fa-ban"></i> Anular Requerimiento
                                </button>
                            </form>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function mostrarFormRechazar() {
        document.getElementById('formRechazar').classList.toggle('hidden');
    }
</script>
@endsection
