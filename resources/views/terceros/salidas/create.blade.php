@extends('layouts.app')
@section('title', 'Nueva Guía de Salida a Tercero')

@section('content')
<div class="container mx-auto max-w-5xl pb-8 md:pb-10" x-data="guiaSalidaForm()">
    <!-- Header de Página -->
    <x-page-header title="Nueva Guía de Salida a Tercero" subtitle="Registra el envío físico de inventario para maquila/servicios externos">
        <x-slot:actions>
            <a href="{{ route('terceros.salidas.index') }}" class="btn-secondary">
                <i class="fas fa-arrow-left"></i>
                <span class="hidden sm:inline ml-2">Volver</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <form id="form-guia-salida">
        @csrf
        
        <!-- 1. Datos Generales -->
        <div class="bg-white rounded-xl shadow-md border border-slate-200/80 p-5 md:p-6 mb-5">
            <div class="flex items-center gap-2 border-b border-slate-100 pb-3 mb-4">
                <i class="fas fa-file-export text-emerald-600 text-base"></i>
                <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wide">1. Datos Generales</h2>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                        🏢 Proveedor Destino <span class="text-red-500">*</span>
                    </label>
                    <select name="proveedor_destino" class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs" required>
                        <option value="">Seleccione Proveedor...</option>
                        @foreach($proveedores as $prov)
                            <option value="{{ $prov->razon_social }}">{{ $prov->ruc }} - {{ $prov->razon_social }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                        📄 Guía N° <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="numero_guia" class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs uppercase" placeholder="Ej. T001-00045" required>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                        📅 Fecha Emisión <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="fecha_emision" value="{{ date('Y-m-d') }}" class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs" required max="{{ date('Y-m-d') }}">
                </div>
                
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                        🏭 Almacén Origen <span class="text-red-500">*</span>
                    </label>
                    <select name="codigo_almacen_origen" x-model="almacenSeleccionado" @change="fetchProductos($event.target.value)" class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs" required>
                        <option value="">Seleccione Almacén...</option>
                        @foreach($almacenes as $almacen)
                            <option value="{{ $almacen->codigo_almacen }}">{{ $almacen->codigo_almacen }} - {{ $almacen->descripcion }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- 2. Detalle de Productos -->
        <div class="bg-white rounded-xl shadow-md border border-slate-200/80 p-5 md:p-6 mb-5">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                <div class="flex items-center gap-2">
                    <i class="fas fa-boxes text-emerald-600 text-base"></i>
                    <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wide">2. Detalle de Productos (PEPs)</h2>
                </div>
                <span class="text-xs font-bold text-slate-600" x-show="detalles.length > 0">
                    Total Items: <span class="text-emerald-600 font-extrabold" x-text="detalles.length"></span>
                </span>
            </div>

            <!-- Buscador y Agregar -->
            <div class="flex flex-col sm:flex-row sm:items-end gap-3 mb-4 bg-slate-50/80 p-3.5 rounded-xl border border-slate-200/80">
                <div class="flex-1">
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide">📌 Buscar Producto PEP</label>
                        <span class="text-[11px] font-semibold text-slate-500" x-show="almacenSeleccionado && !loading && productosDisponibles.length > 0">
                            <span class="text-emerald-600 font-bold" x-text="productosDisponibles.length"></span> disponible(s) con stock
                        </span>
                    </div>
                    <select id="select-producto" 
                            class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs disabled:bg-slate-100 disabled:text-slate-400"
                            :disabled="!almacenSeleccionado || loading">
                        <option value="" x-text="!almacenSeleccionado ? 'Seleccione primero un almacén de origen...' : (loading ? 'Consultando existencias de PEPs...' : (productosDisponibles.length === 0 ? 'No hay productos PEP con stock en este almacén' : 'Seleccione un producto...'))"></option>
                        <template x-for="prod in productosDisponibles" :key="prod.codigo">
                            <option :value="prod.codigo" 
                                    :data-desc="prod.descripcion"
                                    :data-stock="prod.stock_total"
                                    :data-um="prod.unidad_medida || 'KG'"
                                    x-text="`${prod.codigo} - ${prod.descripcion} [Stock: ${parseFloat(prod.stock_total).toFixed(2)} ${prod.unidad_medida || 'KG'}]`">
                            </option>
                        </template>
                    </select>
                </div>
                <button type="button" @click="agregarProducto()" :disabled="!almacenSeleccionado || loading" class="btn-primary h-10 px-4 whitespace-nowrap disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-plus"></i>
                    <span>Agregar</span>
                </button>
            </div>

            <!-- Tabla de Detalles -->
            <div class="overflow-x-auto rounded-lg border border-slate-200">
                <table class="w-full text-left border-collapse min-w-[550px]">
                    <thead>
                        <tr class="bg-slate-800 text-white text-[11px] uppercase tracking-wider font-bold">
                            <th class="py-2.5 px-3 border-r border-slate-700/60 w-12 text-center">Item</th>
                            <th class="py-2.5 px-3 border-r border-slate-700/60">Código - Descripción</th>
                            <th class="py-2.5 px-3 border-r border-slate-700/60 w-36 text-center">Stock Disp.</th>
                            <th class="py-2.5 px-3 border-r border-slate-700/60 w-36 text-center">Cant. a Enviar <span class="text-red-400">*</span></th>
                            <th class="py-2.5 px-3 w-12 text-center"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <template x-for="(item, index) in detalles" :key="item.id">
                            <tr class="hover:bg-slate-50/80 transition duration-150">
                                <td class="py-2 px-3 text-center text-xs font-bold text-slate-500" x-text="index + 1"></td>
                                <td class="py-2 px-3">
                                    <input type="hidden" :name="`productos[${index}][codigo]`" :value="item.codigo">
                                    <div class="text-xs font-bold text-slate-900" x-text="item.codigo"></div>
                                    <div class="text-xs text-slate-600 truncate max-w-xs lg:max-w-md" x-text="item.descripcion" :title="item.descripcion"></div>
                                </td>
                                <td class="py-2 px-3 text-center">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-2xs" 
                                          x-text="parseFloat(item.stock_disponible || 0).toFixed(2) + ' ' + (item.um || 'KG')">
                                    </span>
                                </td>
                                <td class="py-2 px-3">
                                    <div class="relative">
                                        <input type="number" step="0.01" min="0.01" :max="item.stock_disponible" 
                                               class="w-full border rounded-md text-xs text-center px-2 py-1.5 font-bold text-slate-800 focus:ring-1 shadow-xs transition" 
                                               :class="parseFloat(item.cantidad) > parseFloat(item.stock_disponible) || parseFloat(item.cantidad) <= 0 || isNaN(parseFloat(item.cantidad)) ? 'border-red-500 text-red-700 focus:border-red-500 focus:ring-red-500 bg-red-50' : 'border-slate-300 focus:border-primary focus:ring-primary'" 
                                               :name="`productos[${index}][cantidad]`" 
                                               x-model="item.cantidad" required>
                                    </div>
                                    <div x-show="parseFloat(item.cantidad) > parseFloat(item.stock_disponible)" class="text-[10px] text-red-600 font-bold text-center mt-1 flex items-center justify-center gap-1">
                                        <i class="fas fa-exclamation-triangle"></i> Excede stock (<span x-text="parseFloat(item.stock_disponible).toFixed(2)"></span>)
                                    </div>
                                </td>
                                <td class="py-2 px-3 text-center">
                                    <button type="button" @click="eliminarProducto(index)" class="text-slate-400 hover:text-red-600 transition-colors p-1" title="Eliminar fila">
                                        <i class="fas fa-trash-alt text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="detalles.length === 0">
                            <td colspan="5" class="py-8 px-4 text-center text-slate-400 text-xs bg-slate-50/50">
                                <i class="fas fa-list-ol text-2xl mb-2 opacity-50 block text-slate-400"></i>
                                Agregue productos a la guía
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-slate-50 border-t border-slate-200" x-show="detalles.length > 0">
                        <tr>
                            <td colspan="3" class="py-2.5 px-3 text-xs font-bold text-slate-600 text-right">
                                Total a trasladar:
                            </td>
                            <td class="py-2.5 px-3 text-center text-xs font-extrabold text-emerald-700" x-text="calcularTotalCantidad()">
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- 3. Observaciones -->
        <div class="bg-white rounded-xl shadow-md border border-slate-200/80 p-5 md:p-6 mb-6">
            <div class="flex items-center gap-2 border-b border-slate-100 pb-3 mb-4">
                <i class="fas fa-comment-alt text-emerald-600 text-base"></i>
                <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wide">3. Observaciones</h2>
            </div>
            <textarea name="observaciones" rows="2.5" class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs" placeholder="Anotaciones adicionales sobre la salida a terceros..."></textarea>
        </div>

        <!-- Botones de Acción -->
        <div class="flex items-center justify-end space-x-3 pt-2">
            <a href="{{ route('terceros.salidas.index') }}" class="btn-secondary">Cancelar</a>
            <button type="button" @click="guardar()" :disabled="isSubmitting" class="btn-primary">
                <i class="fas" :class="isSubmitting ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                <span x-text="isSubmitting ? 'Procesando...' : 'Guardar y Descontar Stock'"></span>
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('guiaSalidaForm', () => ({
            detalles: [],
            productosDisponibles: [],
            almacenSeleccionado: '',
            loading: false,
            isSubmitting: false,
            counter: 0,

            fetchProductos(almacen) {
                this.almacenSeleccionado = almacen || '';
                if (!this.almacenSeleccionado) {
                    this.productosDisponibles = [];
                    this.detalles.forEach(d => d.stock_disponible = 0);
                    return;
                }
                this.loading = true;
                fetch(`{{ route('terceros.salidas.productos_con_stock') }}?almacen=${encodeURIComponent(this.almacenSeleccionado)}`)
                    .then(res => res.json())
                    .then(data => {
                        this.productosDisponibles = data || [];
                        // Actualizar stock_disponible de items ya en la lista
                        this.detalles.forEach(item => {
                            const found = this.productosDisponibles.find(p => p.codigo === item.codigo);
                            if (found) {
                                item.stock_disponible = parseFloat(found.stock_total);
                                item.um = found.unidad_medida || item.um || 'KG';
                            } else {
                                item.stock_disponible = 0;
                            }
                        });
                    })
                    .catch(err => {
                        console.error(err);
                        if (typeof Swal !== 'undefined') {
                            Swal.fire('Error', 'Error al consultar existencias del almacén.', 'error');
                        } else {
                            alert('Error al consultar existencias del almacén.');
                        }
                    })
                    .finally(() => {
                        this.loading = false;
                    });
            },

            agregarProducto() {
                if (!this.almacenSeleccionado) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Atención', 'Seleccione primero el almacén de origen.', 'warning');
                    } else {
                        alert('Seleccione primero el almacén de origen.');
                    }
                    return;
                }

                const select = document.getElementById('select-producto');
                if (!select.value) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Atención', 'Por favor seleccione un producto PEP.', 'warning');
                    } else {
                        alert('Por favor seleccione un producto PEP.');
                    }
                    return;
                }

                const option = select.options[select.selectedIndex];
                
                // Verificar si ya existe
                const existe = this.detalles.find(d => d.codigo === select.value);
                if (existe) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Atención', 'El producto ya está en la lista.', 'info');
                    } else {
                        alert('El producto ya está en la lista.');
                    }
                    return;
                }

                const selectedProd = this.productosDisponibles.find(p => p.codigo === select.value);
                const stock = selectedProd ? parseFloat(selectedProd.stock_total) : parseFloat(option.getAttribute('data-stock') || 0);
                const um = (selectedProd && selectedProd.unidad_medida) ? selectedProd.unidad_medida : (option.getAttribute('data-um') || 'KG');

                this.detalles.push({
                    id: this.counter++,
                    codigo: select.value,
                    descripcion: option.getAttribute('data-desc') || (selectedProd ? selectedProd.descripcion : ''),
                    stock_disponible: stock,
                    um: um,
                    cantidad: 1
                });
                select.value = '';
            },

            eliminarProducto(index) {
                this.detalles.splice(index, 1);
            },

            calcularTotalCantidad() {
                let sum = 0;
                this.detalles.forEach(d => {
                    const val = parseFloat(d.cantidad) || 0;
                    sum += val;
                });
                return sum.toFixed(2) + ' KG';
            },

            guardar() {
                const notify = (title, text, icon) => {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire(title, text, icon);
                    } else {
                        alert(`${title}: ${text}`);
                    }
                };

                if (!this.almacenSeleccionado) {
                    notify('Atención', 'Seleccione un almacén de origen.', 'warning');
                    return;
                }

                if (this.detalles.length === 0) {
                    notify('Atención', 'Debe agregar al menos un producto a la guía.', 'warning');
                    return;
                }

                // Validar campos requeridos en el form
                const form = document.getElementById('form-guia-salida');
                if (!form.reportValidity()) {
                    return;
                }

                // Validar cantidades y stock disponible
                let errorMsg = null;
                for (let item of this.detalles) {
                    let cant = parseFloat(item.cantidad);
                    let stock = parseFloat(item.stock_disponible);
                    if (isNaN(cant) || cant <= 0) {
                        errorMsg = `La cantidad para el producto ${item.codigo} debe ser mayor a 0.`;
                        break;
                    }
                    if (cant > stock) {
                        errorMsg = `La cantidad indicada (${cant} ${item.um}) para el producto ${item.codigo} excede el stock disponible (${stock} ${item.um}).`;
                        break;
                    }
                }

                if (errorMsg) {
                    notify('Stock insuficiente', errorMsg, 'error');
                    return;
                }

                const confirmAction = () => {
                    this.isSubmitting = true;
                    let formData = new FormData(form);
                    fetch("{{ route('terceros.salidas.store') }}", {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    title: '¡Éxito!',
                                    text: data.message,
                                    icon: 'success',
                                    confirmButtonColor: '#10b981'
                                }).then(() => {
                                    window.location.href = "{{ route('terceros.salidas.index') }}";
                                });
                            } else {
                                alert(data.message);
                                window.location.href = "{{ route('terceros.salidas.index') }}";
                            }
                        } else {
                            notify('Error al registrar', data.message || 'Ocurrió un error inesperado.', 'error');
                            this.isSubmitting = false;
                        }
                    })
                    .catch(error => {
                        console.error(error);
                        notify('Error', 'Error de conexión al procesar la solicitud.', 'error');
                        this.isSubmitting = false;
                    });
                };

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: '¿Confirmar Guía de Salida?',
                        text: `Se registrará la salida de ${this.detalles.length} producto(s) y se descontará el stock inmediatamente mediante FIFO.`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#10b981',
                        cancelButtonColor: '#64748b',
                        confirmButtonText: 'Sí, registrar salida',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            confirmAction();
                        }
                    });
                } else {
                    if (confirm('¿Está seguro de registrar esta Guía de Salida a Tercero? Se actualizará el inventario inmediatamente.')) {
                        confirmAction();
                    }
                }
            }
        }));
    });
</script>
@endpush
@endsection
