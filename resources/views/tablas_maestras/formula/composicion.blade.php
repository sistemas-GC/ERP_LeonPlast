@extends('layouts.app')
@section('title', 'Composición de Fórmula')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    /* Select2 Tailwind override */
    .select2-container .select2-selection--single {
        height: 40px !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 0.5rem !important;
        display: flex;
        align-items: center;
        background-color: #fff !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 38px !important; right: 8px !important; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { 
        color: #1e293b !important; padding-left: 0.75rem !important; line-height: normal !important; font-size: 0.875rem !important; font-weight: 500;
    }
    /* Dark variant for formula header */
    .select-dark + .select2-container .select2-selection--single {
        background-color: #0f172a !important; /* bg-slate-900 */
        border-color: #334155 !important; /* border-slate-700 */
    }
    .select-dark + .select2-container .select2-selection--single .select2-selection__rendered {
        color: #f8fafc !important;
    }
</style>

<div class="container mx-auto pb-8 md:pb-10">
    <!-- Header de Página -->
    <x-page-header title="Diseño de Fórmula" subtitle="Composición de insumos y materiales para la fórmula seleccionada">
        <x-slot:actions>
            <a href="{{ route('formulas.index') }}" class="btn-secondary">
                <i class="fas fa-arrow-left"></i>
                <span class="hidden sm:inline ml-2">Volver al Maestro</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <form action="{{ route('formulas.storeComposicion', $formula->codigo) }}" method="POST" id="formComposicion">
        @csrf
        
        <!-- Tarjeta de Fórmula Header -->
        <div class="bg-slate-800 rounded-xl shadow-md border border-slate-700/80 p-5 md:p-6 mb-5 text-white">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-5">
                <div class="flex-1 w-full">
                    <p class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1">📌 Fórmula en proceso</p>
                    <h2 class="text-2xl md:text-3xl font-extrabold text-white tracking-tight">{{ $formula->codigo }}</h2>
                    <p class="text-slate-300 mt-1 text-sm md:text-base font-medium">{{ $formula->descripcion }}</p>
                </div>

                <div class="bg-slate-900/80 p-3.5 md:p-4 rounded-xl border border-slate-700 w-full lg:w-auto">
                    <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wide mb-1.5">⚡ Asignación Global de Molde</label>
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
                        <div class="w-full sm:w-64 text-slate-800">
                            <select id="moldeGlobal" class="w-full select-dark">
                                <option value="">Seleccione molde...</option>
                                @foreach($moldes as $molde)
                                    <option value="{{ $molde->codigo }}">{{ $molde->descripcion }} ({{ $molde->codigo }})</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="button" onclick="aplicarMoldeGlobal()" class="btn-primary bg-emerald-600 hover:bg-emerald-700 text-white h-10 px-4 text-xs font-bold uppercase tracking-wide whitespace-nowrap">
                            <i class="fas fa-sync-alt"></i>
                            <span>Aplicar</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-4 border-t border-slate-700/80 flex justify-start lg:justify-end">
                <button type="button" onclick="abrirModal()" class="btn-primary bg-emerald-600 hover:bg-emerald-700 text-white h-10 px-5 font-bold text-xs uppercase tracking-wide shadow-md">
                    <i class="fas fa-plus-circle text-sm"></i>
                    <span>Agregar Insumo</span>
                </button>
            </div>
        </div>

        <!-- Tabla de Composición -->
        <div class="bg-white rounded-xl shadow-md border border-slate-200/80 mb-5 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[800px]">
                    <thead>
                        <tr class="bg-slate-800 text-white text-[11px] uppercase tracking-wider font-bold select-none">
                            <th class="py-3 px-4 md:px-6 cursor-pointer hover:bg-slate-700/80 transition group" onclick="sortTable('codigo')">
                                <div class="flex items-center gap-1.5">
                                    <span>Código</span>
                                    <i class="fas fa-sort text-slate-400 group-hover:text-white transition text-[10px]" id="sort-icon-codigo"></i>
                                </div>
                            </th>
                            <th class="py-3 px-4 md:px-6 cursor-pointer hover:bg-slate-700/80 transition group" onclick="sortTable('producto')">
                                <div class="flex items-center gap-1.5">
                                    <span>Producto (Materia Prima)</span>
                                    <i class="fas fa-sort text-slate-400 group-hover:text-white transition text-[10px]" id="sort-icon-producto"></i>
                                </div>
                            </th>
                            <th class="py-3 px-4 md:px-6 cursor-pointer hover:bg-slate-700/80 transition group" onclick="sortTable('tipo')">
                                <div class="flex items-center gap-1.5">
                                    <span>Tipo</span>
                                    <i class="fas fa-sort text-slate-400 group-hover:text-white transition text-[10px]" id="sort-icon-tipo"></i>
                                </div>
                            </th>
                            <th class="py-3 px-4 md:px-6 text-center cursor-pointer hover:bg-slate-700/80 transition group" onclick="sortTable('nominal')">
                                <div class="flex items-center justify-center gap-1.5">
                                    <span>C. Nominal</span>
                                    <i class="fas fa-sort text-slate-400 group-hover:text-white transition text-[10px]" id="sort-icon-nominal"></i>
                                </div>
                            </th>
                            <th class="py-3 px-4 md:px-6 text-center cursor-pointer hover:bg-slate-700/80 transition group" onclick="sortTable('real')">
                                <div class="flex items-center justify-center gap-1.5">
                                    <span>C. Real</span>
                                    <i class="fas fa-sort text-slate-400 group-hover:text-white transition text-[10px]" id="sort-icon-real"></i>
                                </div>
                            </th>
                            <th class="py-3 px-4 md:px-6 text-center cursor-pointer hover:bg-slate-700/80 transition group" onclick="sortTable('unidad')">
                                <div class="flex items-center justify-center gap-1.5">
                                    <span>U.M.</span>
                                    <i class="fas fa-sort text-slate-400 group-hover:text-white transition text-[10px]" id="sort-icon-unidad"></i>
                                </div>
                            </th>
                            <th class="py-3 px-4 md:px-6 cursor-pointer hover:bg-slate-700/80 transition group" onclick="sortTable('molde')">
                                <div class="flex items-center gap-1.5">
                                    <span>Molde Asignado</span>
                                    <i class="fas fa-sort text-slate-400 group-hover:text-white transition text-[10px]" id="sort-icon-molde"></i>
                                </div>
                            </th>
                            <th class="py-3 px-4 md:px-6 text-center w-28">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white" id="tbComposicion">
                        @foreach($composiciones as $idx => $comp)
                            <tr class="hover:bg-slate-50/80 transition duration-150" data-id="row-{{ $idx }}">
                                <td class="px-4 md:px-6 py-2.5 font-bold text-xs md:text-sm text-slate-900 whitespace-nowrap text-codigo">{{ $comp->codigo_producto }}</td>
                                <td class="px-4 md:px-6 py-2.5 font-semibold text-xs md:text-sm text-slate-800 text-producto">{{ $comp->producto ? $comp->producto->descripcion : $comp->codigo_producto }}</td>
                                <td class="px-4 md:px-6 py-2.5 text-xs text-slate-600 uppercase font-medium text-tipo-desc">{{ $comp->producto && $comp->producto->tipo ? $comp->producto->tipo->descripcion : $comp->codigo_tipo_producto }}</td>
                                <td class="px-4 md:px-6 py-2.5 text-center text-xs font-semibold text-slate-700 text-nominal">{{ number_format(floor($comp->cantidad_nominal * 10000) / 10000, 4, '.', '') }}</td>
                                <td class="px-4 md:px-6 py-2.5 text-center font-bold text-xs md:text-sm text-slate-900 text-real">{{ number_format(floor($comp->cantidad_real * 10000) / 10000, 4, '.', '') }}</td>
                                <td class="px-4 md:px-6 py-2.5 text-center text-xs font-semibold text-slate-600 text-unidad">{{ $comp->codigo_unidad_medida ?? 'N/A' }}</td>
                                <td class="px-4 md:px-6 py-2.5 text-xs font-semibold text-molde">
                                    <span class="inline-flex px-2 py-0.5 rounded-md text-xs font-semibold {{ !empty($comp->codigo_molde) ? 'bg-purple-50 text-purple-700 border border-purple-100' : 'text-slate-400 font-normal' }}">
                                        {{ $comp->codigo_molde ?? 'Sin Molde' }}
                                    </span>
                                </td>
                                <td class="px-4 md:px-6 py-2.5 text-center whitespace-nowrap">
                                    <input type="hidden" name="productos[]" value="{{ $comp->codigo_producto }}" class="input-producto">
                                    <input type="hidden" name="tipos[]" value="{{ $comp->codigo_tipo_producto }}" class="input-tipo">
                                    <input type="hidden" name="cantidades_nominales[]" value="{{ $comp->cantidad_nominal }}" class="input-nominal">
                                    <input type="hidden" name="cantidades_reales[]" value="{{ $comp->cantidad_real }}" class="input-real">
                                    <input type="hidden" name="unidades[]" value="{{ $comp->codigo_unidad_medida }}" class="input-unidad">
                                    <input type="hidden" name="moldes[]" value="{{ $comp->codigo_molde }}" class="input-molde">
                                    <input type="hidden" class="input-tipo-desc" value="{{ $comp->producto && $comp->producto->tipo ? $comp->producto->tipo->descripcion : '' }}">
                                    
                                    <div class="flex items-center justify-center gap-1">
                                        <button type="button" class="text-primary hover:text-primary-dark transition p-1" onclick="abrirModal('row-{{ $idx }}')" title="Editar item">
                                            <i class="fas fa-edit text-xs md:text-sm"></i>
                                        </button>
                                        <button type="button" class="text-red-500 hover:text-red-700 transition btn-eliminar p-1" title="Eliminar item">
                                            <i class="fas fa-trash-alt text-xs md:text-sm"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div id="msgVacio" class="{{ $composiciones->isEmpty() ? 'flex' : 'hidden' }} flex-col items-center justify-center py-8 md:py-10 text-center px-4 bg-slate-50/50">
                <div class="h-12 w-12 bg-slate-100 rounded-full flex items-center justify-center mb-3 text-slate-400">
                    <i class="fas fa-layer-group text-xl"></i>
                </div>
                <p class="text-slate-600 text-sm font-semibold">No hay componentes en esta fórmula.</p>
                <p class="text-xs text-slate-400 mt-1">Haga clic en "+ Agregar Insumo" para incorporar materiales.</p>
            </div>
            
            <div class="flex justify-between items-center bg-slate-50 px-4 py-3 border-t border-slate-100" id="paginationWrapper" style="display: none;">
                <div class="text-xs text-slate-500 font-medium" id="paginationInfo">
                    Mostrando <span id="pageStart" class="font-bold"></span> a <span id="pageEnd" class="font-bold"></span> de <span id="pageTotal" class="font-bold"></span> registros
                </div>
                <div class="flex gap-1 flex-wrap text-xs" id="paginationButtons"></div>
            </div>
        </div>
        
        <!-- Botón Guardar Final -->
        <div class="flex justify-end pt-2">
            <button type="submit" class="btn-primary">
                <i class="fas fa-save"></i>
                <span>Finalizar Composición</span>
            </button>
        </div>
    </form>
</div>

<!-- Modal Componente -->
<div id="modalComponente" class="fixed inset-0 bg-slate-900/60 hidden z-50 flex items-center justify-center backdrop-blur-xs px-3 py-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-xl overflow-hidden flex flex-col max-h-[92vh]">
        
        <div class="bg-slate-800 px-5 py-3.5 text-white flex justify-between items-center flex-shrink-0">
            <h3 class="text-sm font-bold uppercase tracking-wider text-white" id="modalTitle">Nuevo Componente</h3>
            <button type="button" onclick="cerrarModal()" class="text-slate-400 hover:text-white transition p-1"><i class="fas fa-times text-base"></i></button>
        </div>
        
        <form id="formModal" class="p-5 overflow-y-auto flex-1 allow-double-submit">
            <input type="hidden" id="modalRowId">
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2 text-slate-800">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Materia Prima / Producto <span class="text-red-500">*</span></label>
                    <select id="modalProducto" class="w-full" required>
                        <option value="">Seleccione producto...</option>
                    </select>
                </div>

                <div class="text-slate-800">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Tipo de Componente</label>
                    <input type="text" id="modalTipoDesc" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-slate-100 text-slate-600 font-semibold cursor-not-allowed outline-none" readonly placeholder="Automático...">
                    <input type="hidden" id="modalTipo">
                </div>

                <div class="text-slate-800">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Unidad de Medida</label>
                    <select id="modalUnidad" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none">
                        @foreach($unidades as $um)
                            <option value="{{ $um->codigo }}">{{ $um->descripcion }} ({{ $um->codigo }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">C. Nominal</label>
                    <input type="number" step="0.0001" id="modalNominal" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-800 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="0.0000">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">C. Real <span class="text-red-500">*</span></label>
                    <input type="number" step="0.0001" id="modalReal" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm font-bold text-slate-900 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="0.0000" required>
                </div>

                <div class="sm:col-span-2 text-slate-800">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Molde Individual <span class="text-[10px] text-slate-400 font-normal">(Opcional)</span></label>
                    <select id="modalMolde" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none">
                        <option value="">Ninguno</option>
                        @foreach($moldes as $molde)
                            <option value="{{ $molde->codigo }}">{{ $molde->descripcion }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-5 pt-3.5 border-t border-slate-100 flex justify-end gap-2.5">
                <button type="button" onclick="cerrarModal()" class="btn-secondary">Cancelar</button>
                <button type="submit" class="btn-primary">Confirmar ítem</button>
            </div>
        </form>
    </div>
</div>

<template id="rowTemplate">
    <tr class="hover:bg-slate-50 transition border-b border-slate-50">
        <td class="px-4 md:px-6 py-2.5 font-bold text-xs md:text-sm text-slate-900 whitespace-nowrap text-codigo"></td>
        <td class="px-4 md:px-6 py-2.5 font-semibold text-xs md:text-sm text-slate-800 text-producto"></td>
        <td class="px-4 md:px-6 py-2.5 text-xs text-slate-600 uppercase font-medium text-tipo-desc"></td>
        <td class="px-4 md:px-6 py-2.5 text-center text-xs font-semibold text-slate-700 text-nominal"></td>
        <td class="px-4 md:px-6 py-2.5 text-center font-bold text-xs md:text-sm text-slate-900 text-real"></td>
        <td class="px-4 md:px-6 py-2.5 text-center text-xs font-semibold text-slate-600 text-unidad"></td>
        <td class="px-4 md:px-6 py-2.5 text-xs font-semibold text-molde"></td>
        <td class="px-4 md:px-6 py-2.5 text-center whitespace-nowrap">
            <input type="hidden" name="productos[]" class="input-producto">
            <input type="hidden" name="tipos[]" class="input-tipo">
            <input type="hidden" name="cantidades_nominales[]" class="input-nominal">
            <input type="hidden" name="cantidades_reales[]" class="input-real">
            <input type="hidden" name="unidades[]" class="input-unidad">
            <input type="hidden" name="moldes[]" class="input-molde">
            <input type="hidden" class="input-tipo-desc">
            
            <div class="flex items-center justify-center gap-1">
                <button type="button" class="text-primary hover:text-primary-dark transition btn-editar p-1"><i class="fas fa-edit text-xs md:text-sm"></i></button>
                <button type="button" class="text-red-500 hover:text-red-700 transition btn-eliminar p-1"><i class="fas fa-trash-alt text-xs md:text-sm"></i></button>
            </div>
        </td>
    </tr>
</template>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        // Inicializar Select2
        $('#moldeGlobal').select2({ width: '100%' });
        
        $('#modalMolde').select2({
            width: '100%',
            dropdownParent: $('#modalComponente'),
            placeholder: 'Ninguno / Seleccione molde...',
            allowClear: true
        });

        $('#modalProducto').select2({
            width: '100%',
            dropdownParent: $('#modalComponente'),
            ajax: {
                url: '/productos/search-ajax',
                dataType: 'json',
                delay: 300,
                data: function(p) { return { q: p.term }; },
                processResults: function(d) { return { results: d }; },
                cache: true
            },
            minimumInputLength: 0,
            placeholder: 'Seleccione producto...'
        });

        $('#modalProducto').on('select2:select', function(e) {
            const d = e.params.data;
            $('#modalTipo').val(d.codigo_tipo_producto || '');
            $('#modalTipoDesc').val(d.descripcion_tipo_producto || d.codigo_tipo_producto || '');
        });
    });

    const modal = document.getElementById('modalComponente');
    const tbComposicion = document.getElementById('tbComposicion');
    const msgVacio = document.getElementById('msgVacio');
    const template = document.getElementById('rowTemplate');
    let rowCounter = 1000;
    let currentPage = 1;
    const rowsPerPage = 10;

    function updatePagination() {
        const rows = Array.from(tbComposicion.querySelectorAll('tr'));
        const totalRows = rows.length;
        const totalPages = Math.ceil(totalRows / rowsPerPage) || 1;
        
        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        let start = (currentPage - 1) * rowsPerPage;
        let end = start + rowsPerPage;

        rows.forEach((row, index) => {
            if (index >= start && index < end) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });

        if (totalRows > rowsPerPage) {
            document.getElementById('paginationWrapper').style.display = 'flex';
            document.getElementById('pageStart').innerText = totalRows === 0 ? 0 : start + 1;
            document.getElementById('pageEnd').innerText = Math.min(end, totalRows);
            document.getElementById('pageTotal').innerText = totalRows;

            let buttonsHtml = '';
            buttonsHtml += `<button type="button" onclick="changePage(${currentPage - 1})" class="px-2.5 py-1 rounded border border-slate-300 ${currentPage === 1 ? 'text-slate-400 bg-slate-50 cursor-not-allowed' : 'text-slate-700 hover:bg-slate-100'}" ${currentPage === 1 ? 'disabled' : ''}>Anterior</button>`;
            
            for (let i = 1; i <= totalPages; i++) {
                if (i === currentPage) {
                    buttonsHtml += `<button type="button" class="px-2.5 py-1 rounded bg-slate-800 text-white font-bold">${i}</button>`;
                } else if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                    buttonsHtml += `<button type="button" onclick="changePage(${i})" class="px-2.5 py-1 rounded border border-slate-300 text-slate-700 hover:bg-slate-100">${i}</button>`;
                } else if (i === currentPage - 2 || i === currentPage + 2) {
                    buttonsHtml += `<span class="px-1.5 py-1 text-slate-400">...</span>`;
                }
            }

            buttonsHtml += `<button type="button" onclick="changePage(${currentPage + 1})" class="px-2.5 py-1 rounded border border-slate-300 ${currentPage === totalPages ? 'text-slate-400 bg-slate-50 cursor-not-allowed' : 'text-slate-700 hover:bg-slate-100'}" ${currentPage === totalPages ? 'disabled' : ''}>Siguiente</button>`;
            
            document.getElementById('paginationButtons').innerHTML = buttonsHtml;
        } else {
            document.getElementById('paginationWrapper').style.display = 'none';
        }
    }

    function changePage(page) {
        currentPage = page;
        updatePagination();
    }

    let currentSortColumn = null;
    let currentSortDirection = 'asc';

    function sortTable(column) {
        const rows = Array.from(tbComposicion.querySelectorAll('tr'));
        if (rows.length === 0) return;

        if (currentSortColumn === column) {
            currentSortDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            currentSortColumn = column;
            currentSortDirection = 'asc';
        }

        // Actualizar iconos de cabecera
        const columns = ['codigo', 'producto', 'tipo', 'nominal', 'real', 'unidad', 'molde'];
        columns.forEach(col => {
            const icon = document.getElementById('sort-icon-' + col);
            if (icon) {
                if (col === currentSortColumn) {
                    icon.className = `fas fa-sort-${currentSortDirection === 'asc' ? 'up text-amber-400' : 'down text-amber-400'} text-[11px]`;
                } else {
                    icon.className = 'fas fa-sort text-slate-400 group-hover:text-white transition text-[10px]';
                }
            }
        });

        // Ordenamiento según el tipo de dato de cada columna
        rows.sort((rowA, rowB) => {
            let valA, valB;

            switch (column) {
                case 'codigo':
                    valA = rowA.querySelector('.text-codigo')?.innerText.trim() || '';
                    valB = rowB.querySelector('.text-codigo')?.innerText.trim() || '';
                    return currentSortDirection === 'asc'
                        ? valA.localeCompare(valB, undefined, { numeric: true, sensitivity: 'base' })
                        : valB.localeCompare(valA, undefined, { numeric: true, sensitivity: 'base' });

                case 'producto':
                    valA = rowA.querySelector('.text-producto')?.innerText.trim() || '';
                    valB = rowB.querySelector('.text-producto')?.innerText.trim() || '';
                    return currentSortDirection === 'asc'
                        ? valA.localeCompare(valB, undefined, { sensitivity: 'base' })
                        : valB.localeCompare(valA, undefined, { sensitivity: 'base' });

                case 'tipo':
                    valA = rowA.querySelector('.text-tipo-desc')?.innerText.trim() || '';
                    valB = rowB.querySelector('.text-tipo-desc')?.innerText.trim() || '';
                    return currentSortDirection === 'asc'
                        ? valA.localeCompare(valB, undefined, { sensitivity: 'base' })
                        : valB.localeCompare(valA, undefined, { sensitivity: 'base' });

                case 'nominal':
                    valA = parseFloat(rowA.querySelector('.text-nominal')?.innerText.trim()) || 0;
                    valB = parseFloat(rowB.querySelector('.text-nominal')?.innerText.trim()) || 0;
                    return currentSortDirection === 'asc' ? valA - valB : valB - valA;

                case 'real':
                    valA = parseFloat(rowA.querySelector('.text-real')?.innerText.trim()) || 0;
                    valB = parseFloat(rowB.querySelector('.text-real')?.innerText.trim()) || 0;
                    return currentSortDirection === 'asc' ? valA - valB : valB - valA;

                case 'unidad':
                    valA = rowA.querySelector('.text-unidad')?.innerText.trim() || '';
                    valB = rowB.querySelector('.text-unidad')?.innerText.trim() || '';
                    return currentSortDirection === 'asc'
                        ? valA.localeCompare(valB)
                        : valB.localeCompare(valA);

                case 'molde':
                    valA = rowA.querySelector('.text-molde')?.innerText.trim() || '';
                    valB = rowB.querySelector('.text-molde')?.innerText.trim() || '';
                    if (valA === 'Sin Molde') valA = currentSortDirection === 'asc' ? 'ZZZZ' : '';
                    if (valB === 'Sin Molde') valB = currentSortDirection === 'asc' ? 'ZZZZ' : '';
                    return currentSortDirection === 'asc'
                        ? valA.localeCompare(valB, undefined, { numeric: true, sensitivity: 'base' })
                        : valB.localeCompare(valA, undefined, { numeric: true, sensitivity: 'base' });
            }
            return 0;
        });

        // Reinsertar las filas ordenadas
        rows.forEach(r => tbComposicion.appendChild(r));

        // Volver a la primera página y actualizar la paginación
        currentPage = 1;
        updatePagination();
    }

    $(document).ready(function() {
        updatePagination();
    });

    function aplicarMoldeGlobal() {
        const moldeCodigo = $('#moldeGlobal').val();
        const moldeTexto = $('#moldeGlobal option:selected').text();
        if (!moldeCodigo) { window.toast('Seleccione un molde global primero.', 'warning'); return; }

        const filas = tbComposicion.querySelectorAll('tr');
        if (filas.length === 0) { window.toast('La grilla está vacía.', 'warning'); return; }

        if (confirm(`¿Asignar "${moldeTexto}" a todos los items?`)) {
            filas.forEach(f => {
                const badgeHtml = `<span class="inline-flex px-2 py-0.5 rounded-md text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-100">${moldeCodigo}</span>`;
                f.querySelector('.text-molde').innerHTML = badgeHtml;
                f.querySelector('.input-molde').value = moldeCodigo;
            });
        }
    }

    function abrirModal(rowId = null) {
        const form = document.getElementById('formModal');
        form.reset();
        
        if (rowId) {
            document.getElementById('modalTitle').innerText = 'Editar Componente';
            const fila = document.querySelector(`tr[data-id="${rowId}"]`);
            document.getElementById('modalRowId').value = rowId;
            document.getElementById('modalNominal').value = fila.querySelector('.input-nominal').value;
            document.getElementById('modalReal').value = fila.querySelector('.input-real').value;
            document.getElementById('modalUnidad').value = fila.querySelector('.input-unidad').value;
            $('#modalMolde').val(fila.querySelector('.input-molde').value || '').trigger('change');
            
            const prodVal = fila.querySelector('.input-producto').value;
            const prodTxt = fila.querySelector('.text-producto').innerText;
            if ($('#modalProducto').find("option[value='" + prodVal + "']").length) {
                $('#modalProducto').val(prodVal).trigger('change');
            } else {
                const newOption = new Option(prodTxt, prodVal, true, true);
                $('#modalProducto').append(newOption).trigger('change');
            }
            
            document.getElementById('modalTipo').value = fila.querySelector('.input-tipo').value;
            document.getElementById('modalTipoDesc').value = fila.querySelector('.input-tipo-desc').value;
        } else {
            document.getElementById('modalTitle').innerText = 'Nuevo Componente';
            document.getElementById('modalRowId').value = '';
            $('#modalProducto').val('').trigger('change');
            document.getElementById('modalTipo').value = '';
            document.getElementById('modalTipoDesc').value = '';
            $('#modalMolde').val($('#moldeGlobal').val() || '').trigger('change');
        }
        modal.classList.remove('hidden');
    }

    function cerrarModal() { modal.classList.add('hidden'); }

    document.getElementById('formModal').addEventListener('submit', function(e) {
        e.preventDefault();
        const rowId = document.getElementById('modalRowId').value;
        
        const data = {
            prodVal: $('#modalProducto').val(),
            prodTxt: $('#modalProducto option:selected').text(),
            tipoVal: $('#modalTipo').val(),
            tipoTxt: $('#modalTipoDesc').val(),
            nominal: (Math.floor(parseFloat(document.getElementById('modalNominal').value || 0) * 10000) / 10000).toFixed(4),
            real: (Math.floor(parseFloat(document.getElementById('modalReal').value || 0) * 10000) / 10000).toFixed(4),
            uniVal: document.getElementById('modalUnidad').value,
            molde: document.getElementById('modalMolde').value || 'Sin Molde'
        };

        let fila;
        if (rowId) {
            fila = document.querySelector(`tr[data-id="${rowId}"]`);
        } else {
            rowCounter++;
            const newId = `row-${rowCounter}`;
            fila = template.content.cloneNode(true).querySelector('tr');
            fila.dataset.id = newId;
            fila.querySelector('.btn-editar').onclick = () => abrirModal(newId);
            msgVacio.classList.add('hidden');
        }

        fila.querySelector('.text-codigo').innerText = data.prodVal;
        fila.querySelector('.text-producto').innerText = data.prodTxt;
        fila.querySelector('.text-tipo-desc').innerText = data.tipoTxt;
        fila.querySelector('.text-nominal').innerText = data.nominal;
        fila.querySelector('.text-real').innerText = data.real;
        fila.querySelector('.text-unidad').innerText = data.uniVal;
        
        const badgeHtml = data.molde !== 'Sin Molde'
            ? `<span class="inline-flex px-2 py-0.5 rounded-md text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-100">${data.molde}</span>`
            : `<span class="text-slate-400 font-normal">Sin Molde</span>`;
        fila.querySelector('.text-molde').innerHTML = badgeHtml;

        fila.querySelector('.input-producto').value = data.prodVal;
        fila.querySelector('.input-tipo').value = data.tipoVal;
        fila.querySelector('.input-tipo-desc').value = data.tipoTxt;
        fila.querySelector('.input-nominal').value = data.nominal;
        fila.querySelector('.input-real').value = data.real;
        fila.querySelector('.input-unidad').value = data.uniVal;
        fila.querySelector('.input-molde').value = data.molde === 'Sin Molde' ? '' : data.molde;

        if (!rowId) tbComposicion.appendChild(fila);
        updatePagination();
        cerrarModal();
    });

    tbComposicion.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-eliminar');
        if (btn) {
            btn.closest('tr').remove();
            if (tbComposicion.children.length === 0) msgVacio.classList.remove('hidden');
            updatePagination();
        }
    });
</script>
@endsection