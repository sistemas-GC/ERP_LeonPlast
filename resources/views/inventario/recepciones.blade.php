@extends('layouts.app')
@section('title', 'Recepciones de Almacén')

@section('content')
<div class="container mx-auto pb-8 md:pb-10">
    <div class="flex justify-between items-center mb-6 gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-800">Registro de Recepciones</h1>
            <p class="text-xs sm:text-sm text-slate-600">Control de inventario y movimientos de almacén</p>
        </div>
    </div>

    @if (session('success'))
    <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-r-xl">
        <p class="text-sm text-green-700">{{ session('success') }}</p>
    </div>
    @endif

    @if (session('error'))
    <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-xl">
        <p class="text-sm text-red-700">{{ session('error') }}</p>
    </div>
    @endif

    @if ($errors->any())
    <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-xl">
        <ul class="list-disc list-inside text-sm text-red-700">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- PANEL DE TARJETAS (DASHBOARD) -->
    <div id="cards-panel" class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        
        <!-- Tarjeta Compras -->
        <div onclick="showSection('section-compras')" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 cursor-pointer hover:shadow-lg hover:border-primary transition-all group flex flex-col items-center text-center">
            <div class="w-16 h-16 rounded-full bg-primary-50 text-primary flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                <i class="fas fa-shopping-cart text-2xl"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-800 mb-2">Mercadería en Tránsito</h3>
            <p class="text-sm text-slate-500 mb-4">Recepciones de facturas de compra</p>
            <div class="mt-auto">
                <span class="inline-flex items-center gap-2 bg-primary-100 text-primary px-4 py-1.5 rounded-full text-sm font-bold">
                    {{ $comprasPendientes->count() }} pendientes
                </span>
            </div>
        </div>

        <!-- Tarjeta Guias -->
        <div onclick="showSection('section-guias')" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 cursor-pointer hover:shadow-lg hover:border-blue-500 transition-all group flex flex-col items-center text-center">
            <div class="w-16 h-16 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                <i class="fas fa-truck text-2xl"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-800 mb-2">Guías de Remisión</h3>
            <p class="text-sm text-slate-500 mb-4">Transferencias desde almacén transitorio</p>
            <div class="mt-auto">
                <span class="inline-flex items-center gap-2 bg-blue-100 text-blue-800 px-4 py-1.5 rounded-full text-sm font-bold">
                    {{ $guiasPendientes->count() }} por ubicar
                </span>
            </div>
        </div>
    </div>

    <!-- SECCIÓN DE COMPRAS -->
    <div id="section-compras" class="hidden">
        <button onclick="showCards()" class="mb-4 inline-flex items-center gap-2 text-slate-500 hover:text-slate-800 font-medium transition-colors">
            <i class="fas fa-arrow-left"></i> Volver a opciones
        </button>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6">
            <div class="px-6 py-4 border-b bg-slate-50 flex flex-col sm:flex-row justify-between gap-4 items-center">
                <div>
                    <h2 class="text-lg font-bold text-slate-800">Mercadería en Tránsito (Compras)</h2>
                    <p class="text-sm text-slate-500">Confirme el almacén destino y cantidades de proveedores.</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('inventario.recepciones.historial') }}"
                       class="inline-flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 px-3 py-1.5 rounded-lg text-sm font-semibold transition">
                        <i class="fas fa-history"></i> Historial
                    </a>
                </div>
            </div>

            <div class="p-6 space-y-4 bg-slate-50">
                @forelse($comprasPendientes as $compra)
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden">
                        <div class="px-6 py-5 cursor-pointer border-l-4 border-amber-400 flex flex-col lg:flex-row justify-between gap-4"
                             onclick="toggleAccordion('compra-{{ $compra->id_compra }}')">

                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
                                    <i class="fas fa-box-open text-xl"></i>
                                </div>

                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-bold text-slate-800 text-sm uppercase">
                                            {{ $compra->tipo_documento }} {{ $compra->serie_documento }}-{{ $compra->numero_documento }}
                                        </h3>
                                        <x-badge color="amber">Pendiente</x-badge>
                                    </div>
                                    <p class="text-sm text-slate-500 mt-1">
                                        {{ $compra->datosProveedor->razon_social ?? $compra->proveedor }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-6">
                                <div class="text-right">
                                    <p class="text-xs uppercase text-slate-400">Fecha</p>
                                    <p class="font-bold text-slate-700">
                                        {{ \Carbon\Carbon::parse($compra->fecha_compra)->format('d/m/Y') }}
                                    </p>
                                </div>
                                <div class="w-10 h-10 rounded-xl border border-slate-200 flex items-center justify-center">
                                    <i class="fas fa-chevron-down transition-transform duration-300" id="icon-compra-{{ $compra->id_compra }}"></i>
                                </div>
                            </div>
                        </div>

                        <div id="compra-{{ $compra->id_compra }}" class="hidden border-t bg-slate-50">
                            <form action="{{ route('inventario.procesar_recepcion', $compra->id_compra) }}" method="POST" class="p-6">
                                @csrf
                                <div class="flex flex-col sm:flex-row sm:items-center justify-end gap-3 mb-4">
                                    <!-- Almacén de Destino (Global para la recepción) -->
                                    <div class="flex items-center gap-2.5 bg-white border border-slate-200 px-3.5 py-2 rounded-xl shadow-2xs">
                                        <label class="text-xs font-bold text-slate-700 uppercase whitespace-nowrap flex items-center gap-1.5">
                                            <i class="fas fa-warehouse text-emerald-600"></i> Almacén de destino:
                                        </label>
                                        <select name="codigo_almacen_destino" class="border-none focus:ring-0 text-xs font-bold text-slate-800 bg-transparent pr-7 py-0.5 cursor-pointer select-almacen-destino-global" required onchange="sincronizarAlmacenDestino(this, 'compra-{{ $compra->id_compra }}')">
                                            <option value="">Seleccione destino...</option>
                                            @foreach($almacenes as $almacen)
                                                <option value="{{ $almacen->codigo_almacen }}" {{ $almacen->codigo_almacen === 'ALM03' ? 'selected' : '' }}>
                                                    {{ $almacen->descripcion }} ({{ $almacen->codigo_almacen }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Fecha Ingreso a Almacén -->
                                    <div class="flex items-center gap-2.5 bg-white border border-slate-200 px-3.5 py-2 rounded-xl shadow-2xs">
                                        <label class="text-xs font-bold text-slate-700 uppercase whitespace-nowrap flex items-center gap-1.5">
                                            <i class="fas fa-calendar-check text-blue-600"></i> Fecha Ingreso a Almacén:
                                        </label>
                                        <input type="date" name="fecha_ingreso" value="{{ date('Y-m-d') }}" class="border-none focus:ring-0 text-xs font-bold text-slate-800 bg-transparent py-0.5 cursor-pointer" required max="{{ date('Y-m-d') }}">
                                    </div>
                                </div>
                                <div class="space-y-4">
                                    @foreach($compra->detalles as $detalle)
                                        <div class="bg-white border border-slate-200 rounded-xl p-4 grid grid-cols-1 lg:grid-cols-12 gap-4 items-center">
                                            <div class="lg:col-span-3">
                                                <p class="font-semibold text-slate-800 text-sm">
                                                    {{ $detalle->producto->descripcion ?? $detalle->descripcion_producto }}
                                                </p>
                                                <p class="text-xs text-slate-400">
                                                    Código: {{ $detalle->codigo_producto }}
                                                </p>
                                            </div>

                                            <div class="lg:col-span-2">
                                                <label class="block text-xs font-semibold text-slate-500 mb-1">Almacén origen</label>
                                                @php
                                                    $almOrigenObj = $almacenes->firstWhere('codigo_almacen', $detalle->codigo_almacen);
                                                    $nombreAlmOrigen = $almOrigenObj ? $almOrigenObj->descripcion : ($detalle->codigo_almacen ?? 'ALM04');
                                                @endphp
                                                <div class="bg-slate-100/90 border border-slate-200 rounded-xl px-2.5 py-2 text-xs font-bold text-slate-700 truncate" title="{{ $nombreAlmOrigen }} ({{ $detalle->codigo_almacen }})">
                                                    <i class="fas fa-sign-out-alt text-slate-400 mr-1"></i>
                                                    {{ $nombreAlmOrigen }}
                                                </div>
                                                <input type="hidden" name="items[{{ $detalle->id_detalle_compra }}][codigo_almacen_origen]" value="{{ $detalle->codigo_almacen }}">
                                                <input type="hidden" name="items[{{ $detalle->id_detalle_compra }}][codigo_almacen]" class="item-almacen-destino" value="{{ $detalle->codigo_almacen !== 'ALM04' ? $detalle->codigo_almacen : 'ALM03' }}">
                                            </div>

                                            <div class="lg:col-span-1">
                                                <label class="block text-xs font-semibold text-slate-500 mb-1">Fact.</label>
                                                <div class="bg-slate-100 rounded-xl px-2 py-2 text-center font-bold text-slate-700 text-sm">
                                                    {{ number_format($detalle->cantidad, 2) }}
                                                </div>
                                            </div>

                                            <div class="lg:col-span-2">
                                                <label class="block text-xs font-semibold text-primary mb-1">Recibido</label>
                                                <input type="number" name="items[{{ $detalle->id_detalle_compra }}][cantidad]" value="{{ $detalle->cantidad }}" step="0.01" class="w-full rounded-xl border-2 border-primary/20 bg-primary-50 text-center font-bold text-primary py-2 focus:ring-4 focus:ring-primary/20" required>
                                                <input type="hidden" name="items[{{ $detalle->id_detalle_compra }}][codigo_producto]" value="{{ $detalle->codigo_producto }}">
                                                <input type="hidden" name="items[{{ $detalle->id_detalle_compra }}][precio]" value="{{ $detalle->precio_unitario }}">
                                                <input type="hidden" name="items[{{ $detalle->id_detalle_compra }}][codigo_unidad_medida]" value="{{ $detalle->codigo_unidad_medida }}">
                                            </div>

                                            <div class="lg:col-span-2">
                                                <label class="block text-xs font-semibold text-slate-500 mb-1">Lote</label>
                                                <input type="text" name="items[{{ $detalle->id_detalle_compra }}][lote]" value="{{ $detalle->lote }}" placeholder="Obligatorio" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:ring-2 focus:ring-primary" required>
                                            </div>

                                            <div class="lg:col-span-2">
                                                <label class="block text-xs font-semibold text-slate-500 mb-1">Vencimiento</label>
                                                <input type="date" name="items[{{ $detalle->id_detalle_compra }}][fecha_vencimiento]" value="{{ $detalle->fecha_vencimiento }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:ring-2 focus:ring-primary">
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="mt-6 flex flex-col sm:flex-row justify-end gap-3">
                                    <button type="button" onclick="toggleAccordion('compra-{{ $compra->id_compra }}')" class="px-6 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-600 hover:bg-slate-50 font-semibold">Cancelar</button>
                                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-linear-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold shadow-lg">
                                        <i class="fas fa-check mr-2"></i> Ingresar al Kardex
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-16">
                        <i class="fas fa-clipboard-check text-5xl text-slate-300 mb-4"></i>
                        <h3 class="text-lg font-bold text-slate-700">Inventario al día</h3>
                        <p class="text-slate-500">No existen órdenes pendientes de recepción.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- SECCIÓN DE GUÍAS -->
    <div id="section-guias" class="hidden">
        <button onclick="showCards()" class="mb-4 inline-flex items-center gap-2 text-slate-500 hover:text-slate-800 font-medium transition-colors">
            <i class="fas fa-arrow-left"></i> Volver a opciones
        </button>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6">
            <div class="px-6 py-4 border-b bg-blue-50 flex flex-col sm:flex-row justify-between gap-4 items-center">
                <div>
                    <h2 class="text-lg font-bold text-blue-800">Guías de Remisión en Tránsito (Por Ubicar)</h2>
                    <p class="text-sm text-blue-600">Transfiera los productos desde el almacén transitorio (ALM04) a su almacén definitivo.</p>
                </div>
            </div>

            <div class="p-6 space-y-4 bg-slate-50">
                @forelse($guiasPendientes as $guia)
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden">
                        <div class="px-6 py-5 cursor-pointer border-l-4 border-blue-400 flex flex-col lg:flex-row justify-between gap-4"
                             onclick="toggleAccordion('guia-{{ $guia->id_guia }}')">

                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center">
                                    <i class="fas fa-file-invoice text-xl"></i>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-bold text-slate-800 text-sm uppercase">Guía #{{ $guia->numero_guia }}</h3>
                                        <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-xs font-semibold">En Tránsito</span>
                                    </div>
                                    <p class="text-sm text-slate-500 mt-1">Proveedor: {{ $guia->datosProveedor->razon_social ?? $guia->proveedor }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-6">
                                <div class="text-right">
                                    <p class="text-xs uppercase text-slate-400">Emisión</p>
                                    <p class="font-bold text-slate-700">{{ \Carbon\Carbon::parse($guia->fecha_emision)->format('d/m/Y') }}</p>
                                </div>
                                <div class="w-10 h-10 rounded-xl border border-slate-200 flex items-center justify-center">
                                    <i class="fas fa-chevron-down transition-transform duration-300" id="icon-guia-{{ $guia->id_guia }}"></i>
                                </div>
                            </div>
                        </div>

                        <div id="guia-{{ $guia->id_guia }}" class="hidden border-t bg-slate-50">
                            <form action="{{ route('inventario.procesar_ubicacion_guia', $guia->id_guia) }}" method="POST" class="p-6">
                                @csrf
                                <div class="space-y-4">
                                    @foreach($guia->detalles as $detalle)
                                        <div class="bg-white border border-slate-200 rounded-xl p-4 grid grid-cols-1 lg:grid-cols-12 gap-4 items-center">
                                            <div class="lg:col-span-4">
                                                <p class="font-semibold text-slate-800 text-sm">{{ $detalle->producto->descripcion ?? $detalle->descripcion_producto }}</p>
                                                <p class="text-xs text-slate-400">Código: {{ $detalle->codigo_producto }} | Lote: {{ $detalle->lote ?? 'N/A' }}</p>
                                            </div>
                                            <div class="lg:col-span-3">
                                                <label class="block text-xs font-semibold text-slate-500 mb-1">Ubicación Actual</label>
                                                <div class="bg-slate-100 rounded-xl px-3 py-2 text-center text-sm text-slate-600">ALM04 - COMPRAS NAC/IMP</div>
                                            </div>
                                            <div class="lg:col-span-3">
                                                <label class="block text-xs font-semibold text-blue-600 mb-1">Transferir al Almacén Destino</label>
                                                <div class="relative">
                                                    <select name="items[{{ $detalle->id_detalle_guia }}][codigo_almacen]" class="w-full pl-3 pr-8 py-2 rounded-xl border-2 border-blue-200 bg-blue-50 text-blue-800 text-sm focus:ring-2 focus:ring-blue-500" required>
                                                        <option value="ALM04">No transferir</option>
                                                        @foreach($almacenes as $almacen)
                                                            @if($almacen->codigo_almacen !== 'ALM04')
                                                                <option value="{{ $almacen->codigo_almacen }}">{{ $almacen->descripcion }}</option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="lg:col-span-2">
                                                <label class="block text-xs font-semibold text-slate-500 mb-1">Cantidad</label>
                                                <div class="bg-slate-100 rounded-xl px-3 py-2 text-center font-bold text-slate-700">
                                                    {{ number_format($detalle->cantidad, 2) }} {{ $detalle->codigo_unidad_medida }}
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="mt-6 flex flex-col sm:flex-row justify-end gap-3">
                                    <button type="button" onclick="toggleAccordion('guia-{{ $guia->id_guia }}')" class="px-6 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-600 hover:bg-slate-50 font-semibold">Cancelar</button>
                                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-linear-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold shadow-lg">
                                        <i class="fas fa-exchange-alt mr-2"></i> Confirmar Transferencias
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-16">
                        <i class="fas fa-check-circle text-5xl text-slate-300 mb-4"></i>
                        <h3 class="text-lg font-bold text-slate-700">Todo Ubicado</h3>
                        <p class="text-slate-500">No hay guías de remisión pendientes de ubicación.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- SECCIÓN DE GUÍAS TERMINA AQUÍ -->
</div>

<script>
const sections = ['section-compras', 'section-guias'];
const cardsPanel = document.getElementById('cards-panel');

function showSection(sectionId) {
    if (cardsPanel) cardsPanel.classList.add('hidden');
    sections.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.classList.add('hidden');
    });
    const sec = document.getElementById(sectionId);
    if (sec) sec.classList.remove('hidden');
}

function showCards() {
    sections.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.classList.add('hidden');
    });
    if (cardsPanel) cardsPanel.classList.remove('hidden');
}

// Manejo de Acordeones
function toggleAccordion(id) {
    const content = document.getElementById(id);
    const icon = document.getElementById('icon-' + id);

    // Si es compra, guia o un OP principal, cerramos los del mismo nivel.
    // No cerramos automáticamente los sub-niveles (prod- y pep-) para no volver loco al usuario.
    if (id.startsWith('compra-') || id.startsWith('guia-') || id.startsWith('op-')) {
        document.querySelectorAll('[id^="compra-"], [id^="guia-"], [id^="op-"]').forEach(el => {
            if (el.id !== id && !el.classList.contains('hidden')) {
                el.classList.add('hidden');
                const otherIcon = document.getElementById('icon-' + el.id);
                if (otherIcon) otherIcon.classList.remove('rotate-180');
            }
        });
    }

    content.classList.toggle('hidden');
    if (icon) icon.classList.toggle('rotate-180');
}

function sincronizarAlmacenDestino(selectEl, containerId) {
    const contenedor = document.getElementById(containerId);
    if (!contenedor) return;
    const destinoVal = selectEl.value;
    contenedor.querySelectorAll('.item-almacen-destino').forEach(input => {
        input.value = destinoVal;
    });
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.select-almacen-destino-global').forEach(select => {
        const form = select.closest('form');
        if (form && select.value) {
            form.querySelectorAll('.item-almacen-destino').forEach(input => input.value = select.value);
        }
    });
});
</script>
@endsection