@extends('layouts.app')
@section('title', 'Ajuste Manual de Inventario')

@section('content')
<link href="/vendor/select2/select2.min.css" rel="stylesheet" />
<style>
    /* Custom Select2 Styling para coincidir con la línea gráfica del sistema */
    .select2-container--default .select2-selection--single {
        background-color: #fff;
        border: 1px solid #cbd5e1;
        border-radius: 0.5rem;
        height: 2.5rem;
        padding: 0.35rem 0.85rem;
        transition: all 0.2s;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #1e293b;
        line-height: 1.65rem;
        padding-left: 0;
        font-size: 0.875rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 2.375rem;
        right: 0.75rem;
    }
    .select2-container--default.select2-container--focus .select2-selection--single,
    .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #059669;
        box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15);
        outline: none;
    }
    .select2-dropdown {
        border: 1px solid #cbd5e1;
        border-radius: 0.5rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        margin-top: 4px;
        font-size: 0.875rem;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field {
        border: 1px solid #cbd5e1;
        border-radius: 0.375rem;
        padding: 0.4rem 0.6rem;
        font-size: 0.875rem;
        outline: none;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field:focus {
        border-color: #059669;
    }
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #ecfdf5;
        color: #047857;
        font-weight: 600;
    }
    .select2-container--default .select2-results__option[aria-selected=true] {
        background-color: #d1fae5;
        color: #065f46;
    }
</style>

<div class="container mx-auto max-w-3xl pb-8 md:pb-10">
    <!-- Header de Página -->
    <x-page-header title="Ajuste Manual de Inventario" subtitle="Registra correcciones por merma, sobrante o validación de inventario físico">
        <x-slot:actions>
            <a href="{{ route('inventario.index') }}" class="btn-secondary">
                <i class="fas fa-arrow-left"></i>
                <span class="hidden sm:inline ml-2">Volver al Inventario</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- Card Principal -->
    <div class="bg-white rounded-xl shadow-md border border-slate-200/80 overflow-hidden">
        
        <!-- Franja de Cabecera -->
        <div class="bg-linear-to-r from-emerald-600 to-teal-600 px-6 py-3.5 text-white flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <i class="fas fa-sliders-h text-base"></i>
                <h2 class="text-sm font-bold uppercase tracking-wide">Formulario de Registro de Ajuste</h2>
            </div>
            <span class="text-xs bg-white/20 px-2.5 py-0.5 rounded-full font-medium">Kardex Directo</span>
        </div>

        <form action="{{ route('inventario.store_ajuste') }}" method="POST" class="p-6 md:p-7 space-y-4.5">
            @csrf

            <!-- Producto -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                    📌 Producto <span class="text-red-500">*</span>
                </label>
                <select name="codigo_producto" id="selectProducto"
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs"
                    required>
                    <option value="">-- Buscar producto --</option>
                    @foreach($productos as $p)
                        <option value="{{ $p->codigo }}">
                            {{ $p->codigo }} - {{ $p->descripcion }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Fila: Almacén y Tipo de Operación -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Almacén -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                        🏭 Almacén afectado <span class="text-red-500">*</span>
                    </label>
                    <select name="codigo_almacen"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs"
                        required>
                        <option value="">-- Seleccione --</option>
                        @foreach($almacenes as $a)
                            <option value="{{ $a->codigo_almacen }}">
                                {{ $a->descripcion }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Tipo -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                        🔄 Tipo de operación <span class="text-red-500">*</span>
                    </label>
                    <select name="tipo" id="selectTipo"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs"
                        required>
                        <option value="INGRESO">INGRESO (+)</option>
                        <option value="SALIDA">SALIDA (-)</option>
                    </select>
                </div>
            </div>

            <!-- Fila: Lote y Costo Unitario -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                        🏷️ Lote <span class="text-red-500">*</span>
                    </label>
                    <!-- Para Ingresos -->
                    <input type="text" name="lote" id="inputLote" placeholder="Ej. LOTE-001"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs"
                        required>
                    <!-- Para Salidas -->
                    <select name="lote_select" id="selectLote" style="display:none;"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs">
                        <option value="">-- Seleccione lote --</option>
                    </select>
                </div>

                <div id="divCosto">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                        💵 Costo Unitario (S/.)
                    </label>
                    <input type="number" name="costo_unitario" id="inputCosto" step="0.0001" min="0"
                        placeholder="Automático o Ingresar"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs">
                </div>
            </div>

            <!-- Fila: Cantidad y Unidad de Medida -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                        ⚖️ Cantidad <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="cantidad" step="0.01" min="0.01"
                        placeholder="0.00"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs"
                        required>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                        📏 Unidad de Medida <span class="text-red-500">*</span>
                    </label>
                    <select name="codigo_unidad_medida"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs"
                        required>
                        <option value="">-- Seleccione --</option>
                        @foreach($unidadesMedida as $u)
                            <option value="{{ $u->codigo }}">
                                {{ $u->codigo }} - {{ $u->descripcion }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Motivo -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                    📝 Motivo del ajuste <span class="text-red-500">*</span>
                </label>
                <textarea name="observaciones" rows="2.5"
                    placeholder="Escriba el motivo detallado del ajuste..."
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs"
                    required></textarea>
            </div>

            <!-- Botones de Acción -->
            <div class="flex items-center justify-end space-x-3 pt-5 border-t border-slate-100 mt-6">
                <a href="{{ route('inventario.index') }}" class="btn-secondary">
                    Cancelar
                </a>

                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i>
                    <span>Procesar Ajuste</span>
                </button>
            </div>

        </form>
    </div>
</div>

<script src="/vendor/jquery/jquery.min.js"></script>
<script src="/vendor/select2/select2.min.js"></script>
<script>
    $(document).ready(function() {
        if (typeof $().select2 !== 'undefined') {
            $('#selectProducto').select2({
                placeholder: "-- Buscar producto --",
                allowClear: true,
                width: '100%'
            });
        }

        function updateLoteField() {
            const tipo = $('#selectTipo').val();
            if (tipo === 'SALIDA') {
                $('#inputLote').removeAttr('name').hide().prop('required', false);
                $('#selectLote').attr('name', 'lote').show().prop('required', true);
                $('#divCosto').hide();
                $('#inputCosto').val('');
                cargarLotes();
            } else {
                $('#selectLote').removeAttr('name').hide().prop('required', false);
                $('#inputLote').attr('name', 'lote').show().prop('required', true);
                $('#divCosto').show();
            }
        }

        function cargarLotes() {
            const producto = $('#selectProducto').val();
            const almacen = $('select[name="codigo_almacen"]').val();
            
            if ($('#selectTipo').val() !== 'SALIDA') return;

            if (!producto) {
                $('#selectLote').html('<option value="">-- Primero seleccione un producto --</option>');
                return;
            }
            if (!almacen) {
                $('#selectLote').html('<option value="">-- Seleccione un almacén para ver stock --</option>');
                return;
            }
            
            $('#selectLote').html('<option value="">Cargando lotes disponibles...</option>');
            $.get("{{ route('inventario.lotes_ajax') }}", { producto: producto, almacen: almacen }, function(data) {
                $('#selectLote').empty();
                if(!data || data.length === 0) {
                    $('#selectLote').append('<option value="">⚠️ Sin stock disponible en este almacén</option>');
                } else {
                    $('#selectLote').append('<option value="">-- Seleccione lote disponible (' + data.length + ') --</option>');
                    data.forEach(function(lote) {
                        const nombreLote = (lote.lote && lote.lote.trim() !== '') ? lote.lote : 'SIN LOTE / N/A';
                        const valorLote = lote.lote ? lote.lote : '';
                        const cantDisp = parseFloat(lote.stock_actual).toFixed(2);
                        $('#selectLote').append(`<option value="${valorLote}">${nombreLote} (Disponible: ${cantDisp} KG)</option>`);
                    });
                }
            }).fail(function() {
                $('#selectLote').html('<option value="">Error al cargar lotes del almacén</option>');
            });
        }

        $('#selectTipo').on('change', updateLoteField);
        $('select[name="codigo_almacen"]').on('change', function() {
            if ($('#selectTipo').val() === 'SALIDA') {
                cargarLotes();
            }
        });
        $('#selectProducto').on('change select2:select select2:clear', function() {
            if ($('#selectTipo').val() === 'SALIDA') {
                cargarLotes();
            }
        });
        
        updateLoteField();
    });
</script>
@endsection