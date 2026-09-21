@extends('layouts.app')

@section('title', 'Nueva Transferencia de Producción')

@section('content')
<div class="container mx-auto pb-8 md:pb-10" x-data="produccionTransferenciaApp()">
    
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                <a href="{{ route('produccion.transferencias.index') }}" class="hover:text-blue-600 transition flex items-center gap-1.5 font-medium">
                    <i class="fas fa-arrow-left text-xs"></i> Volver a Transferencias de Producción
                </a>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">Registrar Transferencia de Producción</h1>
            <p class="text-xs text-slate-500 mt-0.5">Selección de productos con stock totalizado por almacén y deducción automática FIFO por lotes.</p>
        </div>
        <button type="button" @click="submitForm" :disabled="isSubmitting || items.length === 0" class="btn-primary inline-flex items-center gap-2 disabled:opacity-50 h-10 px-5 shadow-md">
            <span x-show="!isSubmitting"><i class="fas fa-save"></i> Procesar Transferencia</span>
            <span x-show="isSubmitting"><i class="fas fa-spinner fa-spin"></i> Procesando...</span>
        </button>
    </div>

    <x-alert :errors="$errors" type="danger" />

    <form id="formProduccionTransferencia" method="POST" action="{{ route('produccion.transferencias.store') }}">
        @csrf
        
        <!-- Tarjeta de Detalles Generales -->
        <div class="bg-white rounded-xl shadow-xs border border-slate-200/80 p-5 md:p-6 mb-6">
            <div class="flex items-center gap-2 border-b border-slate-100 pb-3 mb-4">
                <i class="fas fa-sliders-h text-emerald-600 text-base"></i>
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">1. Parámetros de la Transferencia</h3>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                        📅 Fecha de Transferencia <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="fecha_transferencia" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs" required>
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                        🏭 Almacén de Origen <span class="text-red-500">*</span>
                    </label>
                    <select name="codigo_almacen_origen" x-model="origen" @change="fetchProductos()" class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs" required>
                        <option value="">Seleccione Origen...</option>
                        @foreach($almacenes as $a)
                            <option value="{{ $a->codigo_almacen }}">{{ $a->descripcion }} ({{ $a->codigo_almacen }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                        📍 Almacén de Destino <span class="text-red-500">*</span>
                    </label>
                    <select name="codigo_almacen_destino" x-model="destino" class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs" required>
                        <option value="">Seleccione Destino...</option>
                        @foreach($almacenes as $a)
                            <option value="{{ $a->codigo_almacen }}" x-show="origen !== '{{ $a->codigo_almacen }}'">{{ $a->descripcion }} ({{ $a->codigo_almacen }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                        📝 Observaciones
                    </label>
                    <input type="text" name="observaciones" placeholder="Motivo o detalle del traslado..." class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs">
                </div>
            </div>
        </div>

        <!-- Panel de Transferencia (2 Columnas) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <!-- Panel Izquierdo: Buscador y Catálogo de Productos Totalizados -->
            <div class="lg:col-span-5 bg-white rounded-xl shadow-md border border-slate-200/80 flex flex-col h-[560px] overflow-hidden">
                <div class="p-4 border-b border-slate-100 bg-slate-50/70">
                    <div class="flex items-center justify-between mb-2.5">
                        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="fas fa-boxes text-blue-600"></i> Stock Total en Origen
                        </h3>
                        <span class="text-[11px] font-semibold text-slate-500" x-show="origen">
                            <span x-text="productosDisponibles.length"></span> producto(s)
                        </span>
                    </div>
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-3 text-slate-400 text-xs"></i>
                        <input type="text" x-model="search" @input.debounce.400ms="fetchProductos()" placeholder="Buscar por código o descripción..." 
                            class="w-full pl-8 pr-3 py-2 text-xs rounded-lg border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none bg-white disabled:opacity-50 shadow-2xs" :disabled="!origen">
                    </div>
                </div>
                
                <div class="flex-1 overflow-y-auto p-3 space-y-2">
                    <div x-show="!origen" class="text-center py-16 text-slate-400 text-xs italic px-4">
                        <i class="fas fa-warehouse text-3xl mb-2 text-slate-300 block"></i>
                        Seleccione primero el almacén de origen para consultar el inventario disponible.
                    </div>
                    <div x-show="loading" class="text-center py-16 text-blue-600">
                        <i class="fas fa-circle-notch fa-spin text-2xl mb-2 block"></i>
                        <span class="text-xs font-semibold">Consultando existencias...</span>
                    </div>
                    <div x-show="origen && !loading && productosDisponibles.length === 0" class="text-center py-16 text-slate-400 text-xs px-4">
                        <i class="fas fa-box-open text-3xl mb-2 text-slate-300 block"></i>
                        No se encontraron productos con stock en este almacén.
                    </div>
                    
                    <template x-for="prod in productosDisponibles" :key="prod.codigo_producto">
                        <div class="p-3 bg-white border border-slate-200 rounded-xl hover:border-blue-400 hover:shadow-xs transition duration-150 cursor-pointer flex justify-between items-center gap-3"
                             @click="agregarProducto(prod)"
                             :class="{'opacity-50 pointer-events-none bg-slate-50': isAdded(prod.codigo_producto)}">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1.5 mb-1">
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 uppercase" x-text="prod.codigo_tipo_producto || 'ART'"></span>
                                    <span class="text-xs font-extrabold text-slate-900 truncate" x-text="prod.codigo_producto"></span>
                                </div>
                                <p class="text-xs text-slate-700 font-medium line-clamp-1" x-text="prod.producto_descripcion"></p>
                                <p class="text-[10px] text-slate-400 mt-0.5">
                                    <i class="fas fa-layer-group text-[9px] mr-0.5"></i> <span x-text="prod.total_lotes"></span> lote(s) en almacén
                                </p>
                            </div>
                            <div class="text-right flex-shrink-0 flex flex-col items-end">
                                <span class="inline-block text-xs font-black text-emerald-700 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded-md" 
                                      x-text="parseFloat(prod.stock_total_disponible).toFixed(4) + ' ' + (prod.codigo_unidad_medida || 'UND')"></span>
                                <span class="mt-1.5 text-xs font-semibold text-blue-600 flex items-center gap-1" x-show="!isAdded(prod.codigo_producto)">
                                    <i class="fas fa-plus-circle"></i> Agregar
                                </span>
                                <span class="mt-1.5 text-xs font-semibold text-emerald-600 flex items-center gap-1" x-show="isAdded(prod.codigo_producto)">
                                    <i class="fas fa-check-circle"></i> Agregado
                                </span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Panel Derecho: Bandeja de Productos a Transferir -->
            <div class="lg:col-span-7 bg-white rounded-xl shadow-md border border-slate-200/80 overflow-hidden flex flex-col h-[560px]">
                <div class="p-4 border-b border-slate-100 bg-slate-50/70 flex justify-between items-center">
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                        <i class="fas fa-dolly-flatbed text-emerald-600"></i> 2. Productos a Transferir
                    </h3>
                    <span class="text-xs font-bold text-slate-600" x-show="items.length > 0">
                        Total Items: <span class="text-emerald-600 font-black" x-text="items.length"></span>
                    </span>
                </div>
                
                <div class="flex-1 overflow-y-auto">
                    <table class="w-full text-left text-sm border-collapse min-w-[500px]">
                        <thead class="bg-slate-800 text-white sticky top-0 shadow-xs z-10 text-[11px] uppercase tracking-wider font-bold">
                            <tr>
                                <th class="p-3 pl-4">Producto</th>
                                <th class="p-3 text-center">Stock Disp.</th>
                                <th class="p-3 text-center w-36">Cant. a Transferir</th>
                                <th class="p-3 text-center w-12 pr-4"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr x-show="items.length === 0">
                                <td colspan="4" class="p-16 text-center text-slate-400">
                                    <div class="h-12 w-12 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3 text-slate-400">
                                        <i class="fas fa-hand-pointer text-xl"></i>
                                    </div>
                                    <p class="text-sm font-semibold text-slate-600">Bandeja de transferencia vacía</p>
                                    <p class="text-xs text-slate-400 mt-1">Seleccione productos del panel izquierdo para incorporarlos.</p>
                                </td>
                            </tr>
                            <template x-for="(item, index) in items" :key="item.codigo_producto">
                                <tr class="hover:bg-slate-50/80 transition duration-150">
                                    <td class="p-3 pl-4">
                                        <p class="font-extrabold text-slate-900 text-xs" x-text="item.codigo_producto"></p>
                                        <p class="text-xs text-slate-600 font-medium line-clamp-1" x-text="item.descripcion"></p>
                                        <!-- Inputs Ocultos -->
                                        <input type="hidden" :name="`items[${index}][codigo_producto]`" :value="item.codigo_producto">
                                    </td>
                                    <td class="p-3 text-center">
                                        <span class="inline-block bg-slate-100 text-slate-700 px-2 py-0.5 rounded text-xs font-bold" 
                                              x-text="parseFloat(item.stock_max).toFixed(4) + ' ' + (item.unidad || 'UND')"></span>
                                    </td>
                                    <td class="p-3">
                                        <div class="relative">
                                            <input type="number" step="0.0001" min="0.0001" :max="item.stock_max" :name="`items[${index}][cantidad]`" x-model="item.cantidad"
                                                class="w-full text-center border border-slate-300 rounded-lg focus:border-primary focus:ring-2 focus:ring-primary/20 py-1.5 font-black text-sm text-slate-800 outline-none shadow-2xs"
                                                :class="{'text-red-600 border-red-400 bg-red-50': parseFloat(item.cantidad) > parseFloat(item.stock_max) || parseFloat(item.cantidad) <= 0}">
                                        </div>
                                    </td>
                                    <td class="p-3 text-center pr-4">
                                        <button type="button" @click="quitarProducto(index)" class="inline-flex items-center justify-center w-7 h-7 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition" title="Quitar item">
                                            <i class="fas fa-trash-alt text-xs"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Footer con Resumen Total -->
                <div class="p-3.5 bg-slate-50 border-t border-slate-200 flex justify-between items-center text-xs font-semibold text-slate-600" x-show="items.length > 0">
                    <span>Deducción FIFO automática al procesar</span>
                    <span class="text-slate-800 font-bold">
                        Total a trasladar: <span class="text-emerald-700 font-black text-sm" x-text="calcularTotalGeneral()"></span>
                    </span>
                </div>
            </div>

        </div>
    </form>
</div>

<!-- Alpine.js script -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
    function produccionTransferenciaApp() {
        return {
            origen: '',
            destino: '',
            search: '',
            loading: false,
            productosDisponibles: [],
            items: [],
            isSubmitting: false,

            fetchProductos() {
                if (!this.origen) {
                    this.productosDisponibles = [];
                    this.items = [];
                    return;
                }

                this.loading = true;
                fetch(`{{ route('produccion.transferencias.buscar_productos') }}?codigo_almacen_origen=${this.origen}&search=${encodeURIComponent(this.search)}`)
                    .then(res => res.json())
                    .then(data => {
                        this.productosDisponibles = data.data || [];
                    })
                    .catch(err => {
                        console.error(err);
                        window.toast('Error al consultar productos del almacén.', 'error');
                    })
                    .finally(() => {
                        this.loading = false;
                    });
            },

            isAdded(codigo_producto) {
                return this.items.some(i => i.codigo_producto === codigo_producto);
            },

            agregarProducto(prod) {
                if (this.isAdded(prod.codigo_producto)) return;
                
                const stockMax = parseFloat(prod.stock_total_disponible);
                this.items.push({
                    codigo_producto: prod.codigo_producto,
                    descripcion: prod.producto_descripcion,
                    unidad: prod.codigo_unidad_medida,
                    stock_max: stockMax,
                    cantidad: stockMax
                });
            },

            quitarProducto(index) {
                this.items.splice(index, 1);
            },

            calcularTotalGeneral() {
                let sum = 0;
                this.items.forEach(i => {
                    const cant = parseFloat(i.cantidad) || 0;
                    sum += cant;
                });
                return sum.toFixed(4);
            },

            submitForm() {
                if (!this.origen || !this.destino) {
                    Swal.fire('Atención', 'Debe seleccionar tanto el almacén de origen como el almacén de destino.', 'warning');
                    return;
                }
                if (this.origen === this.destino) {
                    Swal.fire('Atención', 'El almacén de destino no puede ser igual al de origen.', 'warning');
                    return;
                }
                if (this.items.length === 0) {
                    Swal.fire('Atención', 'Debe agregar al menos un producto a transferir.', 'warning');
                    return;
                }
                
                let hasErrors = false;
                let mensajeError = '';
                this.items.forEach(item => {
                    let cant = parseFloat(item.cantidad);
                    let max = parseFloat(item.stock_max);
                    if (isNaN(cant) || cant <= 0) {
                        hasErrors = true;
                        mensajeError = `La cantidad para el producto ${item.codigo_producto} debe ser mayor a 0.`;
                    } else if (cant > max) {
                        hasErrors = true;
                        mensajeError = `La cantidad (${cant}) para el producto ${item.codigo_producto} excede el stock disponible (${max}).`;
                    }
                });

                if (hasErrors) {
                    Swal.fire('Error en cantidades', mensajeError, 'error');
                    return;
                }

                Swal.fire({
                    title: '¿Confirmar Transferencia de Producción?',
                    text: `Se trasladarán ${this.items.length} producto(s) desde ${this.origen} hacia ${this.destino} aplicando deducción FIFO de lotes.`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#10b981',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Sí, procesar traslado',
                    cancelButtonText: 'Revisar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.isSubmitting = true;
                        document.getElementById('formProduccionTransferencia').submit();
                    }
                });
            }
        }
    }
</script>
@endsection
