@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.default.min.css" rel="stylesheet">
<style>
    /* Estilos personalizados para TomSelect alineados al diseño industrial del ERP */
    .ts-control {
        border: 1px solid #cbd5e1 !important;
        border-radius: 0.5rem !important;
        padding: 0.45rem 0.85rem !important;
        font-size: 0.875rem !important;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
        background-color: #ffffff !important;
    }
    .ts-control.focus {
        border-color: #059669 !important;
        box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15) !important;
    }
    .ts-dropdown {
        border-radius: 0.5rem !important;
        border: 1px solid #cbd5e1 !important;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
        font-size: 0.875rem !important;
    }
    .ts-dropdown .option.active {
        background-color: #ecfdf5 !important;
        color: #047857 !important;
        font-weight: 600 !important;
    }
</style>
@endpush

@extends('layouts.app')
@section('title', 'Nueva Guía de Remisión')

@section('content')
<div class="container mx-auto max-w-5xl pb-8 md:pb-10" x-data="guiaForm()">
    <!-- Header de Página -->
    <x-page-header title="Nueva Guía de Remisión" subtitle="Registra la recepción física en el almacén de tránsito">
        <x-slot:actions>
            <a href="{{ route('guia_compras.index') }}" class="btn-secondary">
                <i class="fas fa-arrow-left"></i>
                <span class="hidden sm:inline ml-2">Volver</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    @if ($errors->any())
    <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-xl shadow-xs">
        <h3 class="text-red-800 font-bold text-sm mb-1.5 flex items-center gap-1.5">
            <i class="fas fa-exclamation-circle"></i> Se encontraron los siguientes errores:
        </h3>
        <ul class="list-disc list-inside text-xs text-red-700 space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('guia_compras.store') }}" method="POST" id="form-guia">
        @csrf
        
        <!-- 1. Datos Generales -->
        <div class="bg-white rounded-xl shadow-md border border-slate-200/80 p-5 md:p-6 mb-5">
            <div class="flex items-center gap-2 border-b border-slate-100 pb-3 mb-4">
                <i class="fas fa-file-invoice text-emerald-600 text-base"></i>
                <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wide">1. Datos Generales</h2>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                        🏢 Proveedor <span class="text-red-500">*</span>
                    </label>
                    <select name="ruc_proveedor" class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs" x-model="ruc_proveedor" @change="updateProveedorName" required>
                        <option value="">Seleccione un proveedor...</option>
                        @foreach($proveedores as $prov)
                            <option value="{{ $prov->ruc }}" data-razon="{{ $prov->razon_social }}">{{ $prov->ruc }} - {{ $prov->razon_social }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="proveedor" x-model="proveedor_nombre">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                        📄 Guía N° <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="numero_guia" value="{{ old('numero_guia') }}" class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs uppercase" placeholder="Ej. T001-00045" required>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                        📅 Fecha Emisión <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="fecha_emision" value="{{ old('fecha_emision', date('Y-m-d')) }}" class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs" required max="{{ date('Y-m-d') }}">
                </div>
            </div>
        </div>

        <!-- 2. Detalle de Productos -->
        <div class="bg-white rounded-xl shadow-md border border-slate-200/80 p-5 md:p-6 mb-5">
            <div class="flex items-center gap-2 border-b border-slate-100 pb-3 mb-4">
                <i class="fas fa-boxes text-emerald-600 text-base"></i>
                <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wide">2. Detalle de Productos</h2>
            </div>

            <!-- Buscador y Botón Agregar -->
            <div class="flex flex-col sm:flex-row sm:items-end gap-3 mb-4 bg-slate-50/80 p-3.5 rounded-xl border border-slate-200/80">
                <div class="flex-1">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">📌 Buscar Producto</label>
                    <select id="select-producto" class="w-full">
                        <option value="">Seleccione un producto...</option>
                        @foreach($productos as $prod)
                            <option value="{{ $prod->codigo }}" data-desc="{{ $prod->descripcion }}" data-um="{{ $prod->codigo_unidad_medida ?? 'NIU' }}">
                                {{ $prod->codigo }} - {{ $prod->descripcion }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="button" @click="agregarProducto()" class="btn-primary h-10 px-4 whitespace-nowrap">
                    <i class="fas fa-plus"></i>
                    <span>Agregar</span>
                </button>
            </div>

            <!-- Tabla de Detalles -->
            <div class="overflow-x-auto rounded-lg border border-slate-200">
                <table class="w-full text-left border-collapse min-w-[700px]">
                    <thead>
                        <tr class="bg-slate-800 text-white text-[11px] uppercase tracking-wider font-bold">
                            <th class="py-2.5 px-3 border-r border-slate-700/60 w-12 text-center">Item</th>
                            <th class="py-2.5 px-3 border-r border-slate-700/60">Código - Descripción</th>
                            <th class="py-2.5 px-3 border-r border-slate-700/60 w-32 text-center">Cant. <span class="text-red-400">*</span></th>
                            <th class="py-2.5 px-3 border-r border-slate-700/60 w-28 text-center">U.M.</th>
                            <th class="py-2.5 px-3 border-r border-slate-700/60 w-32 text-center">Lote</th>
                            <th class="py-2.5 px-3 border-r border-slate-700/60 w-36 text-center">Venc.</th>
                            <th class="py-2.5 px-3 w-12 text-center"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white" id="tabla-detalles">
                        <template x-for="(item, index) in detalles" :key="item.id">
                            <tr class="hover:bg-slate-50/80 transition duration-150">
                                <td class="py-2 px-3 text-center text-xs font-bold text-slate-500" x-text="index + 1"></td>
                                <td class="py-2 px-3">
                                    <input type="hidden" :name="`productos[${index}][codigo_producto]`" :value="item.codigo">
                                    <div class="text-xs font-bold text-slate-900" x-text="item.codigo"></div>
                                    <div class="text-xs text-slate-600 truncate max-w-xs lg:max-w-md" x-text="item.descripcion" :title="item.descripcion"></div>
                                </td>
                                <td class="py-2 px-3">
                                    <input type="number" step="0.01" min="0.01" class="w-full border border-slate-300 rounded-md text-xs text-center px-2 py-1.5 font-semibold text-slate-800 focus:border-primary focus:ring-1 focus:ring-primary shadow-xs" :name="`productos[${index}][cantidad]`" x-model="item.cantidad" required>
                                </td>
                                <td class="py-2 px-3 text-center">
                                    <select class="w-full border border-slate-300 bg-white rounded-md text-xs text-center focus:border-primary focus:ring-1 focus:ring-primary px-1 py-1.5 font-medium text-slate-800 shadow-xs" :name="`productos[${index}][codigo_unidad_medida]`" x-model="item.um">
                                        @foreach($unidades_medida as $um)
                                            <option value="{{ $um->codigo }}">{{ $um->codigo }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="py-2 px-3">
                                    <input type="text" class="w-full border border-slate-300 rounded-md text-xs text-center px-2 py-1.5 focus:border-primary focus:ring-1 focus:ring-primary uppercase shadow-xs" placeholder="Lote..." :name="`productos[${index}][lote]`" x-model="item.lote">
                                </td>
                                <td class="py-2 px-3">
                                    <input type="date" class="w-full border border-slate-300 rounded-md text-xs text-center px-1.5 py-1.5 focus:border-primary focus:ring-1 focus:ring-primary shadow-xs" :name="`productos[${index}][fecha_vencimiento]`" x-model="item.vencimiento">
                                </td>
                                <td class="py-2 px-3 text-center">
                                    <button type="button" @click="eliminarProducto(index)" class="text-slate-400 hover:text-red-600 transition-colors p-1" title="Eliminar fila">
                                        <i class="fas fa-trash-alt text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="detalles.length === 0">
                            <td colspan="7" class="py-8 px-4 text-center text-slate-400 text-xs bg-slate-50/50">
                                <i class="fas fa-list-ol text-2xl mb-2 opacity-50 block text-slate-400"></i>
                                Agregue productos a la guía
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 3. Observaciones -->
        <div class="bg-white rounded-xl shadow-md border border-slate-200/80 p-5 md:p-6 mb-6">
            <div class="flex items-center gap-2 border-b border-slate-100 pb-3 mb-4">
                <i class="fas fa-comment-alt text-emerald-600 text-base"></i>
                <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wide">3. Observaciones</h2>
            </div>
            <textarea name="observaciones" rows="2.5" class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs" placeholder="Anotaciones adicionales sobre la recepción..."></textarea>
        </div>

        <!-- Botones de Acción -->
        <div class="flex items-center justify-end space-x-3 pt-2">
            <a href="{{ route('guia_compras.index') }}" class="btn-secondary">Cancelar</a>
            <button type="button" @click="guardar()" class="btn-primary">
                <i class="fas fa-save"></i>
                <span>Guardar e Ingresar a Kardex</span>
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        window.tsInstance = new TomSelect("#select-producto",{
            create: false,
            sortField: {
                field: "text",
                direction: "asc"
            }
        });
    });

    document.addEventListener('alpine:init', () => {
        Alpine.data('guiaForm', () => ({
            ruc_proveedor: '{{ old('ruc_proveedor') }}',
            proveedor_nombre: '{{ old('proveedor') }}',
            detalles: [],
            counter: 0,
            
            updateProveedorName() {
                const select = document.querySelector('select[name="ruc_proveedor"]');
                const selectedOption = select.options[select.selectedIndex];
                if (selectedOption && selectedOption.value) {
                    this.proveedor_nombre = selectedOption.getAttribute('data-razon');
                } else {
                    this.proveedor_nombre = '';
                }
            },

            obtenerLoteSugerido() {
                const numGuia = (document.querySelector('input[name="numero_guia"]')?.value || '').trim().toUpperCase();
                const fecha = document.querySelector('input[name="fecha_emision"]')?.value || '';
                let fechaStr = '';
                if (fecha) {
                    const parts = fecha.split('-');
                    if (parts.length === 3) {
                        fechaStr = `${parts[2]}${parts[1]}${parts[0]}`; // DDMMYYYY ej: 13062026
                    }
                }
                if (numGuia && fechaStr) return `${numGuia}-${fechaStr}`;
                if (numGuia) return numGuia;
                return '';
            },

            agregarProducto() {
                const select = document.getElementById('select-producto');
                if(!select.value) {
                    alert('Por favor seleccione un producto primero.');
                    return;
                }
                const option = select.options[select.selectedIndex];
                
                // Verificar si ya existe
                const existe = this.detalles.find(d => d.codigo === select.value);
                if(existe) {
                    alert('El producto ya está en la lista. Si son diferentes lotes, agréguelo y cambie el lote.');
                }

                this.detalles.push({
                    id: this.counter++,
                    codigo: select.value,
                    descripcion: option.getAttribute('data-desc'),
                    um: option.getAttribute('data-um'),
                    lote: this.obtenerLoteSugerido(),
                    vencimiento: document.querySelector('input[name="fecha_emision"]')?.value || '',
                    cantidad: 1
                });
                if (window.tsInstance) {
                    window.tsInstance.clear();
                } else {
                    select.value = '';
                }
            },

            eliminarProducto(index) {
                this.detalles.splice(index, 1);
            },

            guardar() {
                if(this.detalles.length === 0) {
                    alert('Debe agregar al menos un producto.');
                    return;
                }
                const form = document.getElementById('form-guia');
                if(form.reportValidity()) {
                    if(confirm('¿Está seguro de registrar esta Guía de Remisión? Se actualizará el inventario inmediatamente.')){
                        form.submit();
                    }
                }
            }
        }));
    });
</script>

@if(session('success_ask'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            if(!confirm("{{ session('success_ask') }}")) {
                window.location.href = "{{ route('guia_compras.index') }}";
            }
        }, 100);
    });
</script>
@endif

@endpush
@endsection
