@extends('layouts.app')
@section('title', 'Editar Requerimiento de Materiales')

@section('content')
<link href="/vendor/select2/select2.min.css" rel="stylesheet" />
<div class="container mx-auto px-4 py-6 max-w-7xl">

    <x-page-header title="Editar Requerimiento" subtitle="{{ $requerimiento->codigo }}">
        <x-slot:actions>
            <a href="{{ route('requerimientos_materiales.show', $requerimiento->id_requerimiento) }}" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver al Detalle
            </a>
        </x-slot:actions>
    </x-page-header>

    <form action="{{ route('requerimientos_materiales.update', $requerimiento->id_requerimiento) }}" method="POST" id="formRequerimiento">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

            <div class="lg:col-span-8 xl:col-span-9 space-y-6">
                <x-card>
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                        <h2 class="text-base font-bold text-slate-800 flex items-center gap-2">
                            <i class="fas fa-clipboard-list text-primary"></i> Datos del Requerimiento
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-5">
                            <x-form-group class="md:col-span-6" label="Fecha de Requerimiento" required>
                                <input type="date" name="fecha_requerimiento" class="input-field" value="{{ $requerimiento->fecha_requerimiento ? \Carbon\Carbon::parse($requerimiento->fecha_requerimiento)->format('Y-m-d') : date('Y-m-d') }}" required>
                            </x-form-group>
                            <x-form-group class="md:col-span-6" label="Orden de Producción (opcional)">
                                <select name="idop" id="select_idop" class="input-field">
                                    <option value="">-- Requerimiento Libre --</option>
                                    @foreach($ordenes as $op)
                                        <option value="{{ $op->idop }}" {{ $requerimiento->idop == $op->idop ? 'selected' : '' }}>{{ $op->codigo_op ?? 'OP#' . $op->idop }}</option>
                                    @endforeach
                                </select>
                            </x-form-group>
                            <div id="div_proceso" class="md:col-span-6 {{ $requerimiento->idop ? '' : 'hidden' }}">
                                <x-form-group label="Proceso a Abastecer">
                                    <select name="id_proceso" id="select_proceso" class="input-field" data-selected="{{ $requerimiento->id_proceso }}">
                                        <option value="">-- Seleccione Proceso --</option>
                                    </select>
                                </x-form-group>
                            </div>
                            <x-form-group class="md:col-span-12" label="Motivo">
                                <input type="text" name="motivo" class="input-field" value="{{ $requerimiento->motivo }}" maxlength="500">
                            </x-form-group>
                            <x-form-group class="md:col-span-12" label="Almacén de Destino" required>
                                <select name="codigo_almacen" class="input-field" required>
                                    <option value="">-- Seleccione Almacén --</option>
                                    @foreach($almacenes as $almacen)
                                        <option value="{{ $almacen->codigo_almacen }}" {{ $requerimiento->codigo_almacen == $almacen->codigo_almacen ? 'selected' : '' }}>{{ $almacen->descripcion }}</option>
                                    @endforeach
                                </select>
                            </x-form-group>
                        </div>
                        <x-form-group label="Observaciones">
                            <textarea name="observaciones" class="input-field" rows="2">{{ $requerimiento->observaciones }}</textarea>
                        </x-form-group>
                    </div>
                </x-card>

                <x-card>
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                        <h2 class="text-base font-bold text-slate-800 flex items-center gap-2">
                            <i class="fas fa-boxes text-primary"></i> Productos Solicitados
                        </h2>
                    </div>
                    <div class="p-6">
                        <!-- Buscador Inline Integrado (Clean & Industrial) -->
                        <div class="flex flex-col sm:flex-row sm:items-end gap-3 mb-4 bg-slate-50/80 p-3.5 rounded-xl border border-slate-200/80">
                            <div class="flex-1">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                                    <i class="fas fa-search text-emerald-600 mr-1"></i> Buscar Materia Prima o Insumo (Almacén de Materias Primas)
                                </label>
                                <select id="selectProductoInline" class="w-full" style="width:100%"></select>
                            </div>
                            <button type="button" id="btnAgregarProductoInline" class="btn-primary h-10 px-5 whitespace-nowrap shadow-xs">
                                <i class="fas fa-plus mr-1"></i>
                                <span>Agregar</span>
                            </button>
                        </div>

                        <div class="overflow-x-auto mb-3 rounded-xl border border-slate-200 shadow-2xs">
                            <table class="w-full text-left text-xs" id="tablaProductos">
                                <thead>
                                    <tr class="bg-slate-800 text-[11px] uppercase text-white tracking-wider font-bold">
                                        <th class="py-2.5 px-3 font-bold border-r border-slate-700/60">Materia Prima / Insumo</th>
                                        <th class="py-2.5 px-3 font-bold text-center border-r border-slate-700/60 w-36">Cantidad Solicitada</th>
                                        <th class="py-2.5 px-3 font-bold text-center border-r border-slate-700/60 w-24">U.M.</th>
                                        <th class="py-2.5 px-3 font-bold text-center w-12"><i class="fas fa-cog"></i></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white" id="tbodyProductos">
                                    @foreach($requerimiento->detalles as $det)
                                    <tr class="fila-producto hover:bg-slate-50/70 transition-colors">
                                        <td class="py-2.5 px-3">
                                            <div class="leading-tight">
                                                <span class="texto-prod text-xs font-bold text-slate-900 block" title="{{ $det->producto->descripcion ?? $det->codigo_producto }}">{{ $det->producto->descripcion ?? $det->codigo_producto }}</span>
                                                <span class="text-[10px] text-slate-500 font-mono mt-0.5 block">{{ $det->codigo_producto }}</span>
                                            </div>
                                            <input type="hidden" class="input-cod" name="productos[{{ $loop->index }}][codigo_producto]" value="{{ $det->codigo_producto }}">
                                        </td>
                                        <td class="py-2.5 px-3 text-center">
                                            <input type="number" step="0.01" min="0.01" class="w-full border border-slate-300 bg-white text-center rounded-lg text-xs font-bold text-slate-800 px-2 py-1.5 focus:border-primary focus:ring-1 focus:ring-primary shadow-xs" name="productos[{{ $loop->index }}][cantidad]" value="{{ $det->cantidad_solicitada }}" required>
                                        </td>
                                        <td class="py-2.5 px-3 text-center">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200">{{ $det->producto->unidad->codigo ?? $det->producto->codigo_unidad_medida ?? 'N/A' }}</span>
                                        </td>
                                        <td class="py-2.5 px-3 text-center">
                                            <button type="button" class="text-slate-400 hover:text-red-500 hover:bg-red-50 w-7 h-7 rounded-md inline-flex items-center justify-center btn-del transition text-xs" title="Eliminar"><i class="fas fa-trash-alt"></i></button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </x-card>
            </div>

            <div class="lg:col-span-4 xl:col-span-3">
                <div class="bg-slate-800 rounded-2xl shadow-lg border border-slate-700 sticky top-6 overflow-hidden">
                    <div class="p-6">
                        <h2 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                            <i class="fas fa-save text-primary"></i> Guardar
                        </h2>
                        <button type="submit" class="w-full btn-primary py-3 rounded-xl font-bold text-base flex justify-center gap-2">
                            <i class="fas fa-save"></i> Actualizar Borrador
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="/vendor/jquery/jquery.min.js"></script>
<script src="/vendor/select2/select2.min.js"></script>
<script>
    let filaIdx = {{ $requerimiento->detalles->count() }};
    let tablaBody;

    function getProductName(text) {
        const m = text.match(/\]\s*(.*?)(\s*—\s*Stock:.*|\s*\(Stock:.*)?$/);
        return m ? m[1] : text;
    }

    function generarTemplateHTML(idx, codigo, nombre, unidad) {
        return `
        <tr class="fila-producto hover:bg-slate-50/70 transition-colors">
            <td class="py-2.5 px-3">
                <div class="leading-tight">
                    <span class="texto-prod text-xs font-bold text-slate-900 block" title="${nombre}">${nombre}</span>
                    <span class="text-[10px] text-slate-500 font-mono mt-0.5 block">${codigo}</span>
                </div>
                <input type="hidden" class="input-cod" name="productos[${idx}][codigo_producto]" value="${codigo}">
            </td>
            <td class="py-2.5 px-3 text-center">
                <input type="number" step="0.01" min="0.01" class="w-full border border-slate-300 bg-white text-center rounded-lg text-xs font-bold text-slate-800 px-2 py-1.5 focus:border-primary focus:ring-1 focus:ring-primary shadow-xs" name="productos[${idx}][cantidad]" placeholder="0.00" value="1.00" required>
            </td>
            <td class="py-2.5 px-3 text-center">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200">${unidad || 'N/A'}</span>
            </td>
            <td class="py-2.5 px-3 text-center">
                <button type="button" class="text-slate-400 hover:text-red-500 hover:bg-red-50 w-7 h-7 rounded-md inline-flex items-center justify-center btn-del transition text-xs" title="Eliminar fila">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        </tr>
        `;
    }

    function agregarFila(producto) {
        const codigo = producto.id;
        let existe = false;
        document.querySelectorAll('.input-cod').forEach(input => {
            if (input.value === codigo) {
                existe = true;
                const fila = input.closest('tr');
                const cantInput = fila.querySelector('input[name*="[cantidad]"]');
                if (cantInput) {
                    cantInput.value = (parseFloat(cantInput.value || 0) + 1).toFixed(2);
                    cantInput.focus();
                }
            }
        });

        if (existe) {
            if (typeof window.toast === 'function') {
                window.toast('El producto ya estaba en la lista. Se aumentó la cantidad.', 'info');
            }
            return;
        }

        const idx = filaIdx++;
        const nombre = producto.descripcion || getProductName(producto.text);
        const unidad = producto.unidad_medida || producto.codigo_unidad_medida || 'N/A';
        const html = generarTemplateHTML(idx, codigo, nombre, unidad);
        tablaBody.insertAdjacentHTML('beforeend', html);
    }

    $(document).ready(function () {
        tablaBody = document.getElementById('tbodyProductos');
        const searchUrl = '/productos/search-ajax?solo_materias_primas=1&almacen=ALM03';

        function loadProcesos(idop, selectedProceso = null) {
            const selectProceso = $('#select_proceso');
            const divProceso = $('#div_proceso');
            
            selectProceso.empty().append('<option value="">-- Seleccione Proceso --</option>');
            
            if (idop) {
                divProceso.removeClass('hidden');
                $.get(`/produccion/ordenes/${idop}/procesos-ajax`, function(data) {
                    data.forEach(function(p) {
                        const isSelected = selectedProceso == p.id ? 'selected' : '';
                        selectProceso.append(`<option value="${p.id}" ${isSelected}>${p.descripcion_proceso}</option>`);
                    });
                });
            } else {
                divProceso.addClass('hidden');
            }
        }

        const initialIdop = $('#select_idop').val();
        if (initialIdop) {
            loadProcesos(initialIdop, $('#select_proceso').data('selected'));
        }

        $('#select_idop').on('change', function() {
            loadProcesos($(this).val());
        });

        if (typeof $().select2 !== 'undefined') {
            $('#selectProductoInline').select2({
                ajax: {
                    url: searchUrl,
                    dataType: 'json',
                    delay: 250,
                    data: function(p) { return { q: p.term }; },
                    processResults: function(d) { return { results: d }; },
                    cache: true
                },
                minimumInputLength: 0,
                placeholder: 'Buscar materia prima por código o descripción...',
                width: '100%',
                templateResult: function(item) {
                    if (!item.id) return item.text;
                    const desc = item.descripcion || item.text.replace(/\[.*?\]\s*/, '').replace(/—\s*Stock:.*$/, '');
                    const tipo = item.descripcion_tipo_producto || item.codigo_tipo_producto || '';
                    const um = item.unidad_medida || item.codigo_unidad_medida || '';
                    const stock = item.stock !== null && item.stock !== undefined ? parseFloat(item.stock).toFixed(2) : null;
                    return $(`
                        <div class="flex items-center justify-between py-1 px-0.5">
                            <div class="truncate mr-2">
                                <span class="font-bold text-slate-900">${item.id}</span>
                                <span class="text-slate-700 ml-1.5 font-medium">${desc}</span>
                            </div>
                            <div class="flex items-center gap-1.5 shrink-0">
                                ${stock !== null ? `<span class="text-[10px] font-bold px-1.5 py-0.5 ${parseFloat(stock) > 0 ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-red-50 text-red-800 border-red-200'} rounded border">Stock: ${stock} ${um}</span>` : ''}
                                ${tipo ? `<span class="text-[10px] uppercase font-bold px-1.5 py-0.5 bg-slate-100 text-slate-600 rounded border border-slate-200">${tipo}</span>` : ''}
                            </div>
                        </div>
                    `);
                },
                templateSelection: function(item) {
                    return item.text || item.id;
                }
            });

            $('#selectProductoInline').on('select2:select', function(e) {
                agregarFila(e.params.data);
                $('#selectProductoInline').val(null).trigger('change');
            });

            $('#btnAgregarProductoInline').on('click', function() {
                const data = $('#selectProductoInline').select2('data');
                if (data && data.length > 0 && data[0].id) {
                    agregarFila(data[0]);
                    $('#selectProductoInline').val(null).trigger('change');
                } else {
                    $('#selectProductoInline').select2('open');
                }
            });
        }

        $(document).on('click', '.btn-del', function () {
            $(this).closest('tr').remove();
        });
    });
</script>
</script>
@endsection
